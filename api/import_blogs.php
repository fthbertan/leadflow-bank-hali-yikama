<?php
// Mevcut statik blog HTML dosyalarini veritabanina aktarir
// Kullanim: import_blogs.php?token=...

require_once __DIR__ . '/config.php';

// İmport zaten yapıldıysa tekrar çalıştırma
$lockFile = __DIR__ . '/.import_completed';
if (file_exists($lockFile)) {
    die('<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;padding:40px;text-align:center;">
    <h2 style="color:#dc2626;">Blog İmport Zaten Tamamlandı</h2>
    <p>Bu dosya güvenlik nedeniyle devre dışı bırakılmıştır.</p>
    <p style="color:#666;font-size:14px;">Tekrar import gerekiyorsa <code>.import_completed</code> dosyasını silin.</p>
    </body></html>');
}

$token = $_GET['token'] ?? '';
if ($token !== ADMIN_API_TOKEN) {
    die('Token gerekli: import_blogs.php?token=ADMIN_API_TOKEN');
}

$db = getDB();
$blogDir = realpath(__DIR__ . '/../blog');
if (!$blogDir || !is_dir($blogDir)) {
    die('blog/ klasoru bulunamadi');
}

$skip = ['index.html', 'blog-detail-template.html', 'blog-list-template.html'];
$files = glob($blogDir . '/*.html');
$imported = 0;
$skipped = 0;
$errors = [];

// Mevcut sluglari kontrol et (tekrar eklemeyi onle)
$existing = [];
try {
    $rows = $db->query('SELECT slug FROM blogs')->fetchAll(PDO::FETCH_COLUMN);
    $existing = array_flip($rows);
} catch (Exception $e) {
    // Tablo henuz yoksa devam et
}

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $skip)) continue;

    $slug = str_replace('.html', '', $filename);

    // Zaten varsa atla
    if (isset($existing[$slug])) {
        $skipped++;
        continue;
    }

    $html = file_get_contents($file);
    if (!$html) {
        $errors[] = "Okunamadi: $filename";
        continue;
    }

    // Title
    $title = '';
    if (preg_match('/<title>(.+?)(?:\s*\|.*)?<\/title>/s', $html, $m)) {
        $title = trim($m[1]);
    }

    // Meta description -> summary
    $summary = '';
    if (preg_match('/<meta\s+name="description"\s+content="([^"]*)"/i', $html, $m)) {
        $summary = trim($m[1]);
    }

    // Category
    $category = '';
    if (preg_match('/class="bg-primary-container[^"]*"[^>]*>([^<]+)</i', $html, $m)) {
        $category = trim($m[1]);
    }

    // Date
    $date = date('Y-m-d');
    if (preg_match('/calendar_today<\/span>\s*([^<]+)/i', $html, $m)) {
        $dateStr = trim($m[1]);
        // Turkce ay adlarini cevir
        $aylar = [
            'Ocak'=>'01','Şubat'=>'02','Mart'=>'03','Nisan'=>'04',
            'Mayıs'=>'05','Haziran'=>'06','Temmuz'=>'07','Ağustos'=>'08',
            'Eylül'=>'09','Ekim'=>'10','Kasım'=>'11','Aralık'=>'12'
        ];
        foreach ($aylar as $ay => $num) {
            if (strpos($dateStr, $ay) !== false) {
                $dateStr = str_replace($ay, $num, $dateStr);
                $parts = preg_split('/\s+/', trim($dateStr));
                if (count($parts) === 3) {
                    $date = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                }
                break;
            }
        }
    }

    // Read time
    $readTime = 5;
    if (preg_match('/(\d+)\s*dk\s*okuma/i', $html, $m)) {
        $readTime = (int)$m[1];
    }

    // Content (blog-content div icerigi)
    $content = '';
    if (preg_match('/<div class="blog-content">\s*(.*?)\s*<\/div>/s', $html, $m)) {
        $content = trim($m[1]);
    }

    // Tags (etiket span'larindan)
    $tags = [];
    if (preg_match_all('/bg-surface-container-high[^>]*>([^<]+)<\/span>/i', $html, $m)) {
        $tags = array_map('trim', $m[1]);
    }

    // ID olustur
    $id = 'blog_' . substr(md5($slug), 0, 10);

    try {
        $stmt = $db->prepare('
            INSERT INTO blogs (id, slug, title, summary, content, category, date, read_time, tags, cover_image, status, created_at, updated_at)
            VALUES (:id, :slug, :title, :summary, :content, :category, :date, :read_time, :tags, :cover_image, :status, :created_at, :updated_at)
        ');
        $stmt->execute([
            ':id'          => $id,
            ':slug'        => $slug,
            ':title'       => $title,
            ':summary'     => $summary,
            ':content'     => $content,
            ':category'    => $category,
            ':date'        => $date,
            ':read_time'   => $readTime,
            ':tags'        => json_encode($tags, JSON_UNESCAPED_UNICODE),
            ':cover_image' => '',
            ':status'      => 'published',
            ':created_at'  => date('Y-m-d H:i:s'),
            ':updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $imported++;
    } catch (Exception $e) {
        $errors[] = "$filename: " . $e->getMessage();
    }
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Blog Import</title></head><body style="font-family:Inter,sans-serif;max-width:700px;margin:40px auto;padding:20px;">';
echo '<h2 style="color:#6D31C4">Blog Import Sonucu</h2>';
echo '<p style="color:green;font-size:18px;font-weight:bold">' . $imported . ' blog basariyla aktarildi.</p>';
if ($skipped > 0) {
    echo '<p style="color:orange">' . $skipped . ' blog zaten mevcut, atlandi.</p>';
}
if (!empty($errors)) {
    echo '<h3 style="color:red">Hatalar:</h3><ul>';
    foreach ($errors as $err) {
        echo '<li>' . htmlspecialchars($err) . '</li>';
    }
    echo '</ul>';
}

// Toplam blog sayisi
$total = $db->query('SELECT COUNT(*) FROM blogs')->fetchColumn();
echo '<p>Veritabanindaki toplam blog: <b>' . $total . '</b></p>';

// Otomatik kilitle
if ($imported > 0) {
    file_put_contents($lockFile, json_encode(['completed_at' => date('Y-m-d H:i:s'), 'imported' => $imported]));
    echo '<p style="color:green;font-weight:bold">Import kilidi oluşturuldu — bu dosya artık tekrar çalıştırılamaz.</p>';
} else {
    echo '<p style="color:orange">Hiç blog aktarılmadı — kilit oluşturulmadı (tekrar denenebilir).</p>';
}
echo '</body></html>';
