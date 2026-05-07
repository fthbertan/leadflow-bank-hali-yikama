<?php
// LeadFlow — Sağlık Kontrol Sayfası
// Kullanım: /api/health.php?token=API_TOKEN
require_once __DIR__ . '/config.php';

// Token kontrolü
$token = $_GET['token'] ?? '';
if ($token !== ADMIN_API_TOKEN) {
    http_response_code(403);
    die('Yetkisiz erişim');
}

header('Content-Type: text/html; charset=utf-8');

$checks = [];

// 1. PHP Versiyonu
$phpVer = phpversion();
$phpOk = version_compare($phpVer, '7.4.0', '>=');
$checks[] = ['PHP Versiyonu', $phpVer, $phpOk, $phpOk ? 'PHP 7.4+ ✓' : 'PHP 7.4+ gerekli!'];

// 2. PHP Uzantıları
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'session'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = ['PHP: ' . $ext, $loaded ? 'Yüklü' : 'EKSIK', $loaded, ''];
}
$gdLoaded = extension_loaded('gd');
$checks[] = ['PHP: gd (görsel işleme)', $gdLoaded ? 'Yüklü' : 'Eksik (opsiyonel)', $gdLoaded, 'Görsel resize/WebP için gerekli'];

// 3. Veritabanı Bağlantısı
$dbOk = false;
$dbMsg = '';
$tableCount = 0;
$missingTables = [];
try {
    $db = getDB();
    $dbOk = true;
    $dbMsg = 'Bağlantı başarılı';
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    $expectedTables = ['settings','blogs','services','testimonials','messages','gallery','visitors','whatsapp_templates','service_items','special_days'];
    $missingTables = array_diff($expectedTables, $tables);
} catch (Exception $e) {
    $dbMsg = 'HATA: ' . $e->getMessage();
}
$checks[] = ['Veritabanı Bağlantısı', $dbMsg, $dbOk, 'Host: ' . DB_HOST . ' / DB: ' . DB_NAME];
$checks[] = ['Tablo Sayısı', $tableCount . ' tablo', $tableCount >= 9, $tableCount >= 9 ? 'Tüm tablolar mevcut' : 'install.php çalıştırılmalı'];
if (!empty($missingTables)) {
    $checks[] = ['Eksik Tablolar', implode(', ', $missingTables), false, 'install.php çalıştırın'];
}

// 4. Ayar & Veri sayıları
if ($dbOk && $tableCount > 0) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM settings");
        $settingCount = (int)$stmt->fetchColumn();
        $checks[] = ['Ayar Kayıtları', $settingCount . ' kayıt', $settingCount > 5, $settingCount > 5 ? 'Ayarlar yüklenmiş' : 'install.php çalıştırılmalı'];

        $stmt = $db->query("SELECT COUNT(*) FROM blogs");
        $blogCount = (int)$stmt->fetchColumn();
        $checks[] = ['Blog Kayıtları', $blogCount . ' blog', $blogCount > 0, $blogCount > 0 ? '' : 'Blog import gerekli'];

        $stmt = $db->query("SELECT COUNT(*) FROM services");
        $svcCount = (int)$stmt->fetchColumn();
        $checks[] = ['Hizmet Kayıtları', $svcCount . ' hizmet', $svcCount > 0, ''];
    } catch (Exception $e) {
        $checks[] = ['Veri Kontrolü', 'HATA', false, $e->getMessage()];
    }
}

// 5. Dizin İzinleri
$dirs = [
    __DIR__ . '/../img/uploads/' => 'img/uploads/ (görsel yükleme)',
    __DIR__ . '/../images/' => 'images/ (OG görseller)',
    __DIR__ . '/../data/' => 'data/ (veri dosyaları)',
    __DIR__ . '/' => 'api/ (kilit dosyaları)',
];
foreach ($dirs as $path => $label) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $status = !$exists ? 'KLASÖR YOK' : ($writable ? 'Yazılabilir' : 'YAZMA İZNİ YOK');
    $checks[] = [$label, $status, $writable, $exists ? (decoct(fileperms($path) & 0777)) : 'mkdir gerekli'];
}

// 6. Kritik Dosyalar
$files = [
    __DIR__ . '/config.php' => 'api/config.php',
    __DIR__ . '/schema.sql' => 'api/schema.sql',
    __DIR__ . '/auth.php' => 'api/auth.php',
    __DIR__ . '/install.php' => 'api/install.php',
    __DIR__ . '/../.htaccess' => '.htaccess',
    __DIR__ . '/../seo-data.php' => 'seo-data.php',
    __DIR__ . '/../seo-service.php' => 'seo-service.php',
    __DIR__ . '/../seo-location.php' => 'seo-location.php',
    __DIR__ . '/../blog/post.php' => 'blog/post.php',
];
foreach ($files as $path => $label) {
    $exists = file_exists($path);
    $checks[] = [$label, $exists ? 'Mevcut' : 'EKSIK!', $exists, ''];
}

// 7. Include dosyaları kontrolü (PHP include sistemi)
$includeFiles = [
    __DIR__ . '/../includes/navbar.php' => 'includes/navbar.php',
    __DIR__ . '/../includes/footer.php' => 'includes/footer.php',
    __DIR__ . '/../includes/floating-buttons.php' => 'includes/floating-buttons.php',
    __DIR__ . '/../includes/head-common.php' => 'includes/head-common.php',
];
foreach ($includeFiles as $path => $label) {
    $exists = file_exists($path);
    $checks[] = [$label, $exists ? 'Mevcut' : 'EKSIK!', $exists, ''];
}

// 8. Install durumu
$installDone = file_exists(__DIR__ . '/.install_completed');
$checks[] = ['Kurulum Durumu', $installDone ? 'Tamamlanmış' : 'Henüz çalıştırılmadı', $installDone, $installDone ? '' : 'install.php?token=... çağırın'];

// 9. Config placeholder kontrolü
$configHasPlaceholder = (strpos(DB_HOST, '{{') !== false || strpos(DB_NAME, '{{') !== false);
$checks[] = ['Config Placeholder', $configHasPlaceholder ? 'DOLDURULMADI!' : 'Doldurulmuş', !$configHasPlaceholder, $configHasPlaceholder ? 'config.php\'deki {{}} değerleri gerçek bilgilerle değiştirilmeli' : ''];

// 10. mod_rewrite kontrolü
$modRewrite = (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()));
$checks[] = ['mod_rewrite', $modRewrite ? 'Aktif' : 'Kontrol edilemiyor', true, 'SEO sayfaları için gerekli'];

// 11. PHP upload ayarları
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$memLimit = ini_get('memory_limit');
$checks[] = ['upload_max_filesize', $uploadMax, true, '5MB+ önerilir'];
$checks[] = ['post_max_size', $postMax, true, '10MB+ önerilir'];
$checks[] = ['memory_limit', $memLimit, true, '128MB+ önerilir'];

// HTML Çıktı
$allOk = true;
foreach ($checks as $c) { if (!$c[2]) $allOk = false; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sistem Sağlık Kontrolü</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f7;color:#1a1a1a;padding:20px}
.container{max-width:800px;margin:0 auto}
h1{font-size:1.5rem;margin-bottom:8px}
.status-bar{padding:12px 20px;border-radius:12px;margin-bottom:20px;font-weight:bold;font-size:0.9rem}
.ok{background:#F5F0FF;color:#5B2C87}
.fail{background:#f8d7da;color:#721c24}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
th,td{padding:10px 16px;text-align:left;border-bottom:1px solid #eee;font-size:0.85rem}
th{background:#f8f9fa;font-weight:600;color:#666}
.pass{color:#E0457B}
.fail-text{color:#dc3545;font-weight:bold}
.note{color:#888;font-size:0.75rem}
</style>
</head>
<body>
<div class="container">
<h1>Bank Halı Yıkama — Sistem Sağlık Kontrolü</h1>
<p style="color:#888;margin-bottom:16px;font-size:0.85rem"><?= date('Y-m-d H:i:s') ?></p>

<div class="status-bar <?= $allOk ? 'ok' : 'fail' ?>">
<?= $allOk ? '✅ Tüm kontroller başarılı — sistem hazır!' : '⚠️ Bazı kontroller başarısız — aşağıdaki tabloyu inceleyin' ?>
</div>

<table>
<tr><th>Kontrol</th><th>Durum</th><th>Sonuç</th><th>Not</th></tr>
<?php foreach ($checks as $c): ?>
<tr>
<td><?= htmlspecialchars($c[0]) ?></td>
<td><?= htmlspecialchars($c[1]) ?></td>
<td class="<?= $c[2] ? 'pass' : 'fail-text' ?>"><?= $c[2] ? '✓ OK' : '✗ HATA' ?></td>
<td class="note"><?= htmlspecialchars($c[3]) ?></td>
</tr>
<?php endforeach; ?>
</table>

<p style="margin-top:20px;color:#888;font-size:0.75rem">Bu sayfa deploy sonrası kontrol içindir. Canlıda erişimi kapatmayı unutmayın.</p>
</div>
</body>
</html>
