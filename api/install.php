<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// LeadFlow — Kurulum Scripti (Genel / Sektör-Bağımsız)
// İlk deploy'da bir kez çalıştırılır: tabloları oluşturur, varsayılan ayarları yükler
// Sektöre özel seed data (hizmetler, galeri, yorumlar) sektör install.php'sinden gelir
// Kurulumdan sonra bu dosyayı silmeniz önerilir

require_once __DIR__ . '/config.php';

// Kurulum zaten tamamlandıysa erişimi engelle
$lockFile = __DIR__ . '/.install_completed';
if (file_exists($lockFile)) {
    die('<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;padding:40px;text-align:center;">
    <h2 style="color:#dc2626;">Kurulum Zaten Tamamlandı</h2>
    <p>Bu dosya güvenlik nedeniyle devre dışı bırakılmıştır.</p>
    <p style="color:#666;font-size:14px;">Tekrar kurulum gerekiyorsa <code>.install_completed</code> dosyasını silin.</p>
    </body></html>');
}

// Basit güvenlik — sadece kurulum token'ı ile çalışsın
$installToken = $_GET['token'] ?? '';
if ($installToken !== ADMIN_API_TOKEN) {
    die('<!DOCTYPE html><html><body style="font-family:Inter,sans-serif;padding:40px;text-align:center;">
    <h2>Kurulum Korumalı</h2>
    <p>Kurulumu çalıştırmak için URL\'ye token ekleyin:</p>
    <code>install.php?token=ADMIN_API_TOKEN_DEGERI</code>
    </body></html>');
}

$db = getDB();
$messages = [];
$errors = [];

// ══════════════════════════════════════
// 1. TABLOLARI OLUŞTUR
// ══════════════════════════════════════
try {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt) && stripos($stmt, 'CREATE') !== false) {
            $db->exec($stmt);
        }
    }
    $messages[] = 'Tablolar basariyla olusturuldu.';
} catch (Exception $e) {
    $errors[] = 'Tablo olusturma hatasi: ' . $e->getMessage();
}

// ══════════════════════════════════════
// 1.5 MIGRATION — Mevcut tablolara yeni kolonlar ekle
// ══════════════════════════════════════
$migrations = [
    "ALTER TABLE `blogs` ADD COLUMN `cover_image` VARCHAR(500) AFTER `tags`",
    "ALTER TABLE `services` ADD COLUMN `image` VARCHAR(500) AFTER `icon`",
    "ALTER TABLE `testimonials` ADD COLUMN `role` VARCHAR(255) DEFAULT '' AFTER `name`",
    "ALTER TABLE `blogs` ADD COLUMN `is_featured` TINYINT(1) DEFAULT 0 AFTER `status`",
];
foreach ($migrations as $mig) {
    try {
        $db->exec($mig);
        $messages[] = 'Migration: ' . substr($mig, 0, 60) . '...';
    } catch (Exception $e) {
        // Kolon zaten varsa hata verir, sorun değil
    }
}

// ══════════════════════════════════════
// 2. VARSAYILAN AYARLARI YÜKLE (boşsa)
// ══════════════════════════════════════
try {
    $forceReset = isset($_GET['force']);
    $count = $db->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($forceReset && (int)$count > 0) {
        $db->exec('DELETE FROM settings');
        $messages[] = 'Eski ayarlar silindi (force reset).';
        $count = 0;
    }
    if ((int)$count === 0) {
        $defaults = [
            'business_name'      => 'Bank Halı Yıkama',
            'phone'              => '0 545 687 61 61',
            'phone_raw'          => '05456876161',
            'email'              => '',
            'address'            => 'Plevne Mahallesi Plevne Sokak No:5/a Sincan/Ankara',
            'working_hours'      => '7/24 Hizmetinizdeyiz',
            'working_hours_short'=> '7/24',
            'whatsapp_number'    => '905456876161',
            'phone2'             => '',
            'phone2_raw'         => '',
            'hero_subtitle'      => 'Sincan\'ın Güvenilir Halı Yıkama Merkezi',
            'footer_description' => 'Sincan Plevne\'de kurulu, Ankara\'nın güvenilir halı yıkama merkezi. Kapıdan kapıya ücretsiz servis, memnuniyet garantisi.',
            'cta_title'          => 'Halılarınız Bizimle Güvende',
            'cta_description'    => 'Endüstriyel makineler ve deneyimli ekibimizle halılarınızı kapıdan alıp tertemiz teslim ediyoruz.',
            'map_embed_url'      => 'https://maps.google.com/maps?q=39.9669408,32.5897397&z=17&output=embed&hl=tr',
            'map_link_url'       => 'https://www.google.com/maps?q=39.9669408,32.5897397&z=17&hl=tr',
            'instagram'          => 'https://www.instagram.com/hayati_bank?utm_source=qr&igsh=MXBkZHppeDhydGZzNg==',
            'tiktok'             => 'https://www.tiktok.com/@hayati_bank1?_r=1&_t=ZS-966kPmHot61',
            'facebook'           => '',
            'blog_author_name'   => 'Bank Halı Yıkama Uzman Ekibi',
            'blog_author_bio'    => 'Profesyonel ekibimiz, sektördeki bilgi birikimini sizlerle paylaşmayı amaçlamaktadır.',
            'admin_password_hash'=> '',
        ];

        $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)');
        foreach ($defaults as $k => $v) {
            $stmt->execute([':k' => $k, ':v' => $v]);
        }
        $messages[] = 'Varsayilan ayarlar yuklendi (' . count($defaults) . ' kayit).';
    } else {
        $messages[] = 'Ayarlar zaten mevcut (' . $count . ' kayit), atlanıyor.';
    }
} catch (Exception $e) {
    $errors[] = 'Ayar yukleme hatasi: ' . $e->getMessage();
}

// ══════════════════════════════════════
// 3. VARSAYILAN WHATSAPP ŞABLONLARI
// ══════════════════════════════════════
try {
    $count = $db->query('SELECT COUNT(*) FROM whatsapp_templates')->fetchColumn();
    if ((int)$count === 0) {
        $templates = [
            ['Fiyat Bilgisi', 'Merhaba! Fiyat bilgisi almak istiyorum.'],
            ['Randevu Talebi', 'Merhaba! Randevu almak istiyorum. Uygun zamanlarinizi ogrenebilir miyim?'],
            ['Adres Tarifi', 'Merhaba! Isletmenizin adres tarifini alabilir miyim?'],
            ['Genel Bilgi', 'Merhaba! Web sitenizden ulasiyorum. Hizmetleriniz hakkinda bilgi almak istiyorum.'],
        ];
        $stmt = $db->prepare('INSERT INTO whatsapp_templates (title, message, sort_order) VALUES (:t, :m, :s)');
        foreach ($templates as $i => $t) {
            $stmt->execute([':t' => $t[0], ':m' => $t[1], ':s' => $i]);
        }
        $messages[] = 'WhatsApp sablonlari yuklendi (' . count($templates) . ' sablon).';
    }
} catch (Exception $e) {
    $errors[] = 'WhatsApp sablon yukleme hatasi: ' . $e->getMessage();
}

// ══════════════════════════════════════
// 3.2 Eski fiyat tablolarını temizle (artık service_items kullanılıyor)
// ══════════════════════════════════════
try {
    $db->exec('DROP TABLE IF EXISTS price_items');
    $db->exec('DROP TABLE IF EXISTS price_categories');
    $db->exec('DROP TABLE IF EXISTS price_list');
} catch (Exception $e) { /* yoksay */ }

// ══════════════════════════════════════
// 3.5 DOSYA İZİNLERİNİ DÜZELT
// ══════════════════════════════════════
$uploadDir = __DIR__ . '/../img/uploads/';
if (is_dir($uploadDir)) {
    $fixed = 0;
    foreach (glob($uploadDir . '*') as $file) {
        if (is_file($file)) {
            chmod($file, 0644);
            $fixed++;
        }
    }
    if ($fixed > 0) {
        $messages[] = 'Görsel dosya izinleri düzeltildi (' . $fixed . ' dosya).';
    }
} else {
    @mkdir($uploadDir, 0755, true);
    $messages[] = 'img/uploads/ dizini oluşturuldu.';
}

// ══════════════════════════════════════
// 4. SEKTÖRE ÖZEL SEED DATA (varsa)
// ══════════════════════════════════════
// Sektör install.php'si bu dosyanın yanında sector_seed.php olarak bulunabilir
$sectorSeed = __DIR__ . '/sector_seed.php';
if (file_exists($sectorSeed)) {
    include $sectorSeed;
    $messages[] = 'Sektöre özel veriler yüklendi.';
}

// ══════════════════════════════════════
// 4.5 DATA JSON IMPORT — /data/*.json dosyalarından gerçek verileri DB'ye aktar
// ══════════════════════════════════════
// sector_seed.php fallback veriler yükler, bu bölüm Gemini ile üretilmiş
// gerçek verileri (/data/*.json) override olarak DB'ye yazar.
$dataDir = __DIR__ . '/../data/';

// ── 4.5a Settings (sosyal medya, harita vb.) ──
try {
    $settingsFile = $dataDir . 'settings.json';
    if (file_exists($settingsFile)) {
        $jsonData = json_decode(file_get_contents($settingsFile), true);
        if (is_array($jsonData)) {
            $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2');
            $imported = 0;
            foreach ($jsonData as $k => $v) {
                $val = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
                $stmt->execute([':k' => $k, ':v' => $val, ':v2' => $val]);
                $imported++;
            }
            $messages[] = 'Settings JSON import: ' . $imported . ' ayar aktarıldı.';
        }
    }
} catch (Exception $e) {
    $errors[] = 'Settings JSON import hatası: ' . $e->getMessage();
}

// ── 4.5b Testimonials ──
try {
    $testimonialsFile = $dataDir . 'testimonials.json';
    if (file_exists($testimonialsFile)) {
        $jsonData = json_decode(file_get_contents($testimonialsFile), true);
        $items = $jsonData['testimonials'] ?? [];
        if (!empty($items)) {
            $db->exec('DELETE FROM testimonials');
            $stmt = $db->prepare('INSERT INTO testimonials (name, role, rating, text, sort_order) VALUES (:name, :role, :rating, :text, :sort)');
            foreach ($items as $i => $item) {
                $stmt->execute([
                    ':name'   => $item['name'] ?? '',
                    ':role'   => $item['role'] ?? '',
                    ':rating' => $item['rating'] ?? 5,
                    ':text'   => $item['text'] ?? '',
                    ':sort'   => $i,
                ]);
            }
            $messages[] = 'Testimonials JSON import: ' . count($items) . ' yorum aktarıldı.';
        }
    }
} catch (Exception $e) {
    $errors[] = 'Testimonials JSON import hatası: ' . $e->getMessage();
}

// ── 4.5c Blogs ──
try {
    $blogsFile = $dataDir . 'blogs.json';
    if (file_exists($blogsFile)) {
        $jsonData = json_decode(file_get_contents($blogsFile), true);
        $items = $jsonData['blogs'] ?? [];
        if (!empty($items)) {
            $db->exec('DELETE FROM blogs');
            $stmt = $db->prepare('INSERT INTO blogs (id, slug, title, summary, content, category, date, read_time, tags, cover_image, status, is_featured, created_at, updated_at) VALUES (:id, :slug, :title, :summary, :content, :category, :date, :read_time, :tags, :cover, :status, :featured, :created, :updated)');
            foreach ($items as $item) {
                // Tarih formatı: "01 Mart 2026" → MySQL DATE
                $dateStr = $item['date'] ?? '';
                $trMonths = ['Ocak'=>'01','Şubat'=>'02','Mart'=>'03','Nisan'=>'04','Mayıs'=>'05','Haziran'=>'06','Temmuz'=>'07','Ağustos'=>'08','Eylül'=>'09','Ekim'=>'10','Kasım'=>'11','Aralık'=>'12'];
                $mysqlDate = null;
                foreach ($trMonths as $trName => $num) {
                    if (strpos($dateStr, $trName) !== false) {
                        $parts = explode(' ', $dateStr);
                        if (count($parts) === 3) {
                            $mysqlDate = $parts[2] . '-' . $num . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        }
                        break;
                    }
                }

                $tags = $item['tags'] ?? [];
                $stmt->execute([
                    ':id'       => $item['id'] ?? ('blog_' . ($item['slug'] ?? uniqid())),
                    ':slug'     => $item['slug'] ?? '',
                    ':title'    => $item['title'] ?? '',
                    ':summary'  => $item['summary'] ?? '',
                    ':content'  => $item['content'] ?? '',
                    ':category' => $item['category'] ?? '',
                    ':date'     => $mysqlDate,
                    ':read_time'=> (int)($item['read_time'] ?? 5),
                    ':tags'     => is_array($tags) ? json_encode($tags, JSON_UNESCAPED_UNICODE) : $tags,
                    ':cover'    => $item['cover_image'] ?? '',
                    ':status'   => $item['status'] ?? 'published',
                    ':featured' => (int)($item['is_featured'] ?? 0),
                    ':created'  => $item['created_at'] ?? date('Y-m-d H:i:s'),
                    ':updated'  => $item['updated_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
            $messages[] = 'Blogs JSON import: ' . count($items) . ' blog aktarıldı.';
        }
    }
} catch (Exception $e) {
    $errors[] = 'Blogs JSON import hatası: ' . $e->getMessage();
}

// ── 4.5d Services ──
try {
    $servicesFile = $dataDir . 'services.json';
    if (file_exists($servicesFile)) {
        $jsonData = json_decode(file_get_contents($servicesFile), true);
        $items = $jsonData['services'] ?? [];
        if (!empty($items)) {
            $db->exec('DELETE FROM services');
            $db->exec('DELETE FROM service_items');
            $stmt = $db->prepare('INSERT INTO services (title, description, icon, image, price, sort_order) VALUES (:title, :desc, :icon, :image, :price, :sort)');
            $stmtItem = $db->prepare('INSERT INTO service_items (service_id, name, description, price, unit, sort_order) VALUES (:sid, :name, :desc, :price, :unit, :sort)');
            $totalItems = 0;
            foreach ($items as $i => $item) {
                $stmt->execute([
                    ':title' => $item['name'] ?? $item['title'] ?? '',
                    ':desc'  => $item['desc'] ?? $item['description'] ?? '',
                    ':icon'  => $item['icon'] ?? '',
                    ':image' => $item['image'] ?? '',
                    ':price' => $item['price'] ?? '',
                    ':sort'  => $i,
                ]);
                $serviceId = $db->lastInsertId();
                $subItems = $item['items'] ?? [];
                foreach ($subItems as $j => $sub) {
                    $stmtItem->execute([
                        ':sid'  => $serviceId,
                        ':name' => $sub['name'] ?? '',
                        ':desc' => $sub['description'] ?? '',
                        ':price'=> $sub['price'] ?? '',
                        ':unit' => $sub['unit'] ?? '',
                        ':sort' => $j,
                    ]);
                    $totalItems++;
                }
            }
            $messages[] = 'Services JSON import: ' . count($items) . ' hizmet, ' . $totalItems . ' fiyat kalemi aktarıldı.';
        }
    }
} catch (Exception $e) {
    $errors[] = 'Services JSON import hatası: ' . $e->getMessage();
}

// ── 4.5e Gallery auto-scan ──
try {
    $imgDir = __DIR__ . '/../images/';
    $galleryFiles = glob($imgDir . 'gallery-*.{webp,jpg,jpeg,png}', GLOB_BRACE);
    if (!empty($galleryFiles)) {
        $db->exec("DELETE FROM gallery WHERE category = 'gallery'");
        // Doğal sıralama: gallery-1, gallery-2, ..., gallery-10
        natsort($galleryFiles);
        $stmt = $db->prepare('INSERT INTO gallery (filename, category, alt_text, sort_order) VALUES (:file, :cat, :alt, :sort)');
        $order = 0;
        foreach ($galleryFiles as $file) {
            $basename = basename($file);
            $stmt->execute([
                ':file' => 'images/' . $basename,
                ':cat'  => 'gallery',
                ':alt'  => 'Galeri görseli ' . ($order + 1),
                ':sort' => $order,
            ]);
            $order++;
        }
        $messages[] = 'Gallery auto-scan: ' . $order . ' görsel aktarıldı.';
    }
} catch (Exception $e) {
    $errors[] = 'Gallery auto-scan hatası: ' . $e->getMessage();
}

// ══════════════════════════════════════
// 5. KURULUM SONRASI OTOMATİK KİLİTLEME
// ══════════════════════════════════════
// Hata yoksa install.php'yi otomatik devre dışı bırak (güvenlik)
if (empty($errors)) {
    $lockFile = __DIR__ . '/.install_completed';
    file_put_contents($lockFile, json_encode([
        'completed_at' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]));
    $messages[] = 'Kurulum kilidi oluşturuldu — install.php artık tekrar çalıştırılamaz.';
}

// ══════════════════════════════════════
// 6. SONUÇ EKRANI
// ══════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>LeadFlow Kurulum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"/>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-lg w-full p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#9D68DB] to-[#6D31C4] flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">LeadFlow Kurulum</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <h3 class="font-bold text-red-700 mb-2">Hatalar:</h3>
                <?php foreach ($errors as $err): ?>
                    <p class="text-red-600 text-sm"><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <div class="bg-[#F5F0FF] border border-[#9D68DB] rounded-xl p-4 mb-4">
                <h3 class="font-bold text-[#6D31C4] mb-2">Basarili:</h3>
                <?php foreach ($messages as $msg): ?>
                    <p class="text-[#6D31C4] text-sm"><?= htmlspecialchars($msg) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <p class="text-amber-800 text-sm font-semibold">Guvenlik Uyarisi:</p>
            <p class="text-amber-700 text-sm mt-1">Kurulum tamamlandiktan sonra bu dosyayi (install.php) sunucudan silin veya yeniden adlandirin.</p>
        </div>

        <div class="space-y-3">
            <a href="../" class="block w-full bg-gradient-to-r from-[#9D68DB] to-[#6D31C4] text-white text-center font-bold py-3 rounded-xl hover:opacity-90 transition">Siteye Git</a>
            <a href="../admin/" class="block w-full bg-gray-100 text-gray-700 text-center font-bold py-3 rounded-xl hover:bg-gray-200 transition">Admin Paneli</a>
        </div>
    </div>
</body>
</html>
