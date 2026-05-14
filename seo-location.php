<?php
// LeadFlow — Dinamik Lokasyon SEO Sayfası (Genel Versiyon)
// .htaccess ile /sincan-hali-yikama → seo-location.php?loc=sincan&svc=hali-yikama yönlendirmesi
// Her ilçe+hizmet kombinasyonu için benzersiz SEO sayfası üretir
// $locations, $services, $slugAliases seo-data.php'den gelir

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/seo-data.php';   // $locations, $services, $slugAliases
require_once __DIR__ . '/api/config.php';

$locSlug = $_GET['loc'] ?? '';
$svcSlug = $_GET['svc'] ?? '';

// SVG ikon helper (Material Symbols yerine)
function svgIcon($name, $class = '', $size = '1em') {
    $icons = [
        'call' => 'M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z',
        'local_shipping' => 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z',
        'dry_cleaning' => 'M19.56 11.36L13 8.44V7c0-.55-.45-1-1-1s-1 .45-1 1v1.44l-6.56 2.92c-.88.39-.88 1.63 0 2.02L11 16.36V19c0 .55.45 1 1 1s1-.45 1-1v-2.64l6.56-2.92c.88-.39.88-1.63 0-2.08zM12 14.3l-4.74-2.12L12 10.06l4.74 2.12L12 14.3z',
        'verified' => 'M23 12l-2.44-2.79.34-3.69-3.61-.82-1.89-3.2L12 2.96 8.6 1.5 6.71 4.69 3.1 5.5l.34 3.7L1 12l2.44 2.79-.34 3.7 3.61.82L8.6 22.5l3.4-1.47 3.4 1.46 1.89-3.19 3.61-.82-.34-3.69L23 12zm-12.91 4.72l-3.8-3.8 1.48-1.48 2.32 2.33 5.85-5.87 1.48 1.48-7.33 7.34z',
        'eco' => 'M6.05 8.05c-2.73 2.73-2.73 7.17 0 9.9C7.42 19.32 9.21 20 11 20s3.58-.68 4.95-2.05C19.43 14.47 20 4 20 4S9.53 4.57 6.05 8.05zm8.49 8.49c-.95.94-2.2 1.46-3.54 1.46-.89 0-1.73-.25-2.48-.68.92-2.88 4.02-6.03 6.93-7.05-.82 2.72-2.2 4.91-2.91 6.27z',
        'payments' => 'M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z',
        'schedule' => 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z',
        'cleaning_services' => 'M16 11h-1V3c0-.55-.45-1-1-1h-4c-.55 0-1 .45-1 1v8H8c-2.76 0-5 2.24-5 5v7h18v-7c0-2.76-2.24-5-5-5z',
        'local_laundry_service' => 'M9.17 16.83c1.56 1.56 4.1 1.56 5.66 0 1.56-1.56 1.56-4.1 0-5.66l-5.66 5.66zM18 2.01L6 2c-1.11 0-2 .89-2 2v16c0 1.11.89 2 2 2h12c1.11 0 2-.89 2-2V4c0-1.11-.89-1.99-2-1.99zM10 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM7 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm5 16c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z',
        'checkroom' => 'M21.6 18.2L13 11.75v-.91c1.65-.49 2.8-2.17 2.43-4.05-.26-1.31-1.3-2.4-2.61-2.7C10.54 3.57 8.5 5.3 8.5 7.5h2c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5c0 .84-.69 1.52-1.53 1.5-.54-.01-.97.45-.97.99v1.76L2.4 18.2c-.77.58-.36 1.8.6 1.8h18c.96 0 1.37-1.22.6-1.8zM6 18l6-4.5 6 4.5H6z',
        'home_repair_service' => 'M18 16h-2v-1H8v1H6v-1H2v5h20v-5h-4v1zm2-8h-3V6c0-1.1-.9-2-2-2H9c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v4h4v-2h2v2h8v-2h2v2h4v-4c0-1.1-.9-2-2-2zm-5 0H9V6h6v2z',
        'weekend' => 'M21 9V7c0-1.65-1.35-3-3-3H6C4.35 4 3 5.35 3 7v2c-1.65 0-3 1.35-3 3v5c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2v-5c0-1.65-1.35-3-3-3z',
        'bed' => 'M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z',
        'star' => 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z',
    ];
    $path = $icons[$name] ?? ($icons['cleaning_services']);
    $c = htmlspecialchars($class);
    return '<svg class="'.$c.'" style="width:'.$size.';height:'.$size.';vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="'.$path.'"/></svg>';
}

// Geçersiz lokasyon veya hizmet kontrolü
if (!isset($locations[$locSlug]) || !isset($services[$svcSlug])) {
    http_response_code(404);
    header('Location: /');
    exit;
}

$loc = $locations[$locSlug];
$svc = $services[$svcSlug];

// Ayarları çek
$db = getDB();
$settings = [];
try {
    $stmtS = $db->query('SELECT setting_key, setting_value FROM settings');
    while ($row = $stmtS->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$businessName = $settings['business_name'] ?? 'İşletme Adı';
$phone = $settings['phone'] ?? '';
$phoneRaw = $settings['phone_raw'] ?? '';
$phone2 = $settings['phone2'] ?? '';
$phone2Raw = $settings['phone2_raw'] ?? '';
$whatsapp = $settings['whatsapp_number'] ?? '';
$instagramUrl = $settings['instagram'] ?? '';
$address = $settings['address'] ?? 'Ankara, Türkiye';

// SEO değişkenleri
$pageTitle = $loc['name'] . ' ' . $svc['name'] . ' | ' . $businessName;
$metaDesc = $loc['name'] . ' ' . strtolower($svc['name']) . ' hizmeti arıyorsanız doğru adrestesiniz. ' . $businessName . ' olarak ' . $loc['desc'] . ' profesyonel ' . strtolower($svc['name']) . ' hizmeti sunuyoruz. Ücretsiz servis, uygun fiyat.';
$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$canonicalUrl = $siteUrl . '/' . $locSlug . '-' . $svcSlug;
$fullSlug = $locSlug . '-' . $svcSlug;

// Diğer lokasyonlar (internal linking)
$otherLocations = [];
foreach ($locations as $lSlug => $lData) {
    if ($lSlug !== $locSlug) {
        $otherLocations[] = ['slug' => $lSlug, 'name' => $lData['name']];
    }
}

// Aynı bölgedeki diğer hizmetler
$otherServices = [];
foreach ($services as $sSlug => $sData) {
    if ($sSlug !== $svcSlug) {
        $otherServices[] = ['slug' => $sSlug, 'name' => $sData['name'], 'icon' => $sData['icon']];
    }
}

// Son blog yazıları
$recentBlogs = [];
try {
    $stmtB = $db->query("SELECT title, slug, summary FROM blogs WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $recentBlogs = $stmtB->fetchAll();
} catch (Exception $e) {}

// FAQ Schema JSON-LD
$faqSchema = [];
foreach ($svc['faq'] as $f) {
    $faqSchema[] = [
        '@type' => 'Question',
        'name' => $loc['name'] . '\'da ' . strtolower(substr(($f['Soru'] ?? $f[0] ?? ''), 0, 1)) . substr(($f['Soru'] ?? $f[0] ?? ''), 1),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['Cevap'] ?? $f[1] ?? '']
    ];
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="tr">
<head>
<meta charset="utf-8"/>
<?php $gaId = $settings['analytics_id'] ?? ''; if ($gaId): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaId) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag("js",new Date());gtag("config","<?= htmlspecialchars($gaId) ?>")</script>
<?php endif; ?>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<meta name="keywords" content="<?= htmlspecialchars($loc['name'] . ' ' . strtolower($svc['name']) . ', ' . strtolower($svc['name']) . ' ' . strtolower($loc['name']) . ', ' . strtolower($loc['name']) . ' temizlik, ankara ' . strtolower($svc['name']) . ', ' . strtolower($loc['name']) . ' ' . strtolower($svc['name']) . ' fiyat') ?>"/>
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>"/>
<meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<meta property="og:type" content="website"/>
<meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>"/>
<meta property="og:image" content="<?= htmlspecialchars($siteUrl) ?>/images/heroweb-1.webp"/>
<meta property="og:locale" content="tr_TR"/>
<meta property="og:site_name" content="<?= htmlspecialchars($businessName) ?>"/>
<meta name="twitter:card" content="summary_large_image"/>
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>"/>
<meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<meta name="twitter:image" content="<?= htmlspecialchars($siteUrl) ?>/images/heroweb-1.webp"/>
<meta name="geo.region" content="TR-06"/>
<meta name="geo.placename" content="<?= htmlspecialchars($loc['name']) ?>, Ankara"/>
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>"/>
<link rel="alternate" hreflang="tr" href="<?= htmlspecialchars($canonicalUrl) ?>"/>
<link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($canonicalUrl) ?>"/>
<link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
<link rel="stylesheet" href="/css/style.css"/>

<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<style>
        body{
  font-family:'Inter',sans-serif;
  background:#fff;
  color:var(--lf-dark);
  overflow-x:clip;
}
</style>
<style>
.fonts-loaded
.text-gradient-gold{background:linear-gradient(135deg,#6D31C4,#9D68DB,#9D68DB);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
.btn-shimmer{background:linear-gradient(110deg,#C93A6A 0%,#E0457B 25%,#E0457B 50%,#E0457B 75%,#C93A6A 100%);background-size:200% 100%;animation:shimmer 4s linear infinite}
.nav-link{position:relative}
.nav-link::after{content:'';position:absolute;bottom:-4px;left:50%;width:0;height:2px;background:#E0457B;transition:all .3s ease;transform:translateX(-50%)}
.nav-link:hover::after{width:100%}
</style>

<!-- Schema.org -->
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'LocalBusiness',
            'name' => $businessName,
            'description' => $metaDesc,
            'url' => $siteUrl,
            'image' => $siteUrl . '/images/heroweb-1.webp',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $loc['name'] . ', Ankara',
                'addressRegion' => 'Ankara',
                'addressCountry' => 'TR'
            ],
            'areaServed' => ['@type' => 'AdministrativeArea', 'name' => $loc['name'] . ', Ankara'],
            'priceRange' => '₺₺',
            'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => '4.9', 'bestRating' => '5', 'ratingCount' => '350'],
        ],
        [
            '@type' => 'Service',
            'serviceType' => $loc['name'] . ' ' . $svc['name'],
            'provider' => ['@type' => 'LocalBusiness', 'name' => $businessName],
            'areaServed' => ['@type' => 'AdministrativeArea', 'name' => $loc['name'] . ', Ankara'],
            'description' => $svc['desc'],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => $siteUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $loc['name'] . ' ' . $svc['name']],
            ]
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => $faqSchema,
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
</head>
<body class="bg-[#FAF9F6] font-body text-gray-800">

<nav aria-label="Ana navigasyon">
  <a href="/" class="logo">Bank Halı Yıkama</a>
  <ul class="nav-links">
    <li><a href="/#hizmetler">Hizmetler</a></li>
    <li><a href="/fiyatlar">Fiyatlar</a></li>
    <li><a href="/#hakkimizda">Hakkimizda</a></li>
    <li><a href="/#galeri">Galeri</a></li>
    <li><a href="/blog">Blog</a></li>
    <li><a href="/#iletisim">Iletisim</a></li>
  </ul>
  <a href="/#iletisim" class="nav-cta">Ucretsiz Kesif</a>
  <button class="nav-hamburger" aria-label="Menuyu ac" onclick="document.getElementById('mobileMenu').classList.add('open')">
    <span></span><span></span><span></span>
  </button>
</nav>
<div id="mobileMenu" class="mobile-menu" role="dialog" aria-label="Navigasyon menusu">
  <button class="mobile-close" onclick="document.getElementById('mobileMenu').classList.remove('open')" aria-label="Menuyu kapat">X</button>
  <a href="/#hizmetler" onclick="document.getElementById('mobileMenu').classList.remove('open')">Hizmetler</a>
  <a href="/fiyatlar" onclick="document.getElementById('mobileMenu').classList.remove('open')">Fiyatlar</a>
  <a href="/#hakkimizda" onclick="document.getElementById('mobileMenu').classList.remove('open')">Hakkimizda</a>
  <a href="/#galeri" onclick="document.getElementById('mobileMenu').classList.remove('open')">Galeri</a>
  <a href="/blog" onclick="document.getElementById('mobileMenu').classList.remove('open')">Blog</a>
  <a href="/#iletisim" onclick="document.getElementById('mobileMenu').classList.remove('open')">Iletisim</a>
</div>


<!-- Hero -->
<header class="relative bg-gradient-to-br from-[#251560] via-[#6D31C4] to-[#9D68DB] overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(ellipse 50% 50% at 80% 20%,#E0457B,transparent),radial-gradient(ellipse 40% 40% at 15% 85%,#9D68DB,transparent)"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 text-center">
        <!-- Breadcrumb -->
        <nav class="flex justify-center items-center gap-2 text-white/70 text-xs mb-8" aria-label="Breadcrumb">
            <a href="/" class="hover:text-[#E0457B] transition-colors">Ana Sayfa</a>
            <svg class="text-xs" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            <span class="text-[#E0457B]"><?= htmlspecialchars($loc['name']) ?> <?= htmlspecialchars($svc['name']) ?></span>
        </nav>
        <h1 class="font-headline text-4xl sm:text-5xl lg:text-6xl font-light text-white mb-6 tracking-wide">
            <?= htmlspecialchars($loc['name']) ?><br/>
            <span class="text-[#E0457B] italic"><?= htmlspecialchars($svc['name']) ?> Hizmeti</span>
        </h1>
        <p class="text-white/70 text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
            <?= htmlspecialchars($loc['desc']) ?> profesyonel <?= htmlspecialchars(strtolower($svc['name'])) ?> hizmeti sunuyoruz. Ücretsiz servis ile kapınızdan alıyor, tertemiz teslim ediyoruz.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/#iletisim" class="btn-shimmer text-white px-10 py-4 rounded-full font-semibold text-base shadow-xl hover:scale-105 transition-transform">Ücretsiz Keşif Al</a>
            <?php if ($phoneRaw): ?>
            <a href="tel:<?= htmlspecialchars($phoneRaw) ?>" class="border border-white/30 text-white px-10 py-4 rounded-full font-semibold text-base hover:bg-white/10 transition-all flex items-center gap-2">
                <svg class="" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg> Hemen Ara
            </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- İçerik Bölümü -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">

    <!-- Hizmet Açıklaması -->
    <section class="mb-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-[#9D68DB] font-semibold text-xs uppercase tracking-[0.2em] block mb-4"><?= htmlspecialchars($loc['name']) ?> Hizmet Detayı</span>
                <h2 class="font-headline text-3xl sm:text-4xl text-gray-900 mb-6"><?= htmlspecialchars($loc['name']) ?>'da Profesyonel <span class="text-gradient-gold italic"><?= htmlspecialchars($svc['name']) ?></span></h2>
                <p class="text-gray-600 text-lg leading-relaxed mb-6"><?= htmlspecialchars($svc['intro']) ?></p>
                <p class="text-gray-600 leading-relaxed mb-8"><?= htmlspecialchars($svc['desc']) ?></p>
                <div class="grid sm:grid-cols-2 gap-3">
                    <?php foreach ($svc['features'] as $feat): ?>
                    <div class="flex items-center gap-3">
                        <svg class="text-[#9D68DB] text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        <span class="text-gray-700 font-medium text-sm"><?= htmlspecialchars($feat) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-[4/3] rounded-3xl overflow-hidden shadow-2xl bg-gradient-to-br from-[#9D68DB]/20 to-[#6D31C4]/10">
                    <img src="/images/heroweb-1.webp" alt="<?= htmlspecialchars($loc['name'] . ' ' . $svc['name'] . ' hizmeti') ?>" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </section>

    <!-- Bölgeye Özel Bilgi -->
    <section class="mb-20">
        <div class="bg-gradient-to-br from-[#251560] to-[#6D31C4] rounded-3xl p-8 sm:p-12 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-full h-full" style="background:radial-gradient(ellipse 40% 40% at 90% 10%,rgba(var(--accent-rgb),0.1),transparent)"></div>
            <div class="relative z-10">
                <span class="text-[#E0457B] font-semibold text-xs uppercase tracking-[0.2em] block mb-4">Bölge Bilgisi</span>
                <h2 class="font-headline text-2xl sm:text-3xl mb-6"><?= htmlspecialchars($loc['name']) ?> Hakkında</h2>
                <p class="text-white/80 leading-relaxed mb-6"><?= htmlspecialchars($loc['long_desc']) ?></p>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-[#E0457B] mb-2 flex items-center gap-2"><svg class="text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">M17 11V3H7v4H3v14h8v-4h2v4h8V11h-4zM7 19H5v-2h2v2zm0-4H5v-2h2v2zm0-4H5V9h2v2zm4 4H9v-2h2v2zm0-4H9V9h2v2zm0-4H9V5h2v2zm4 8h-2v-2h2v2zm0-4h-2V9h2v2zm0-4h-2V5h2v2zm4 12h-2v-2h2v2zm0-4h-2v-2h2v2z</svg> Hizmet Verdiğimiz Mahalleler</h3>
                        <p class="text-white/70 text-sm leading-relaxed"><?= htmlspecialchars($loc['neighborhoods']) ?></p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[#E0457B] mb-2 flex items-center gap-2"><svg class="text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">M18 8c0-3.31-2.69-6-6-6S6 4.69 6 8c0 4.5 6 11 6 11s6-6.5 6-11zm-8 0c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zM5 20v2h14v-2H5z</svg> Konum Avantajımız</h3>
                        <p class="text-white/70 text-sm leading-relaxed"><?= htmlspecialchars($loc['landmarks']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Detaylı Bölge İçeriği (SEO) -->
    <?php if (!empty($loc['unique_content'])): ?>
    <section class="mb-20">
        <div class="max-w-4xl mx-auto">
            <h2 class="font-headline text-3xl sm:text-4xl text-gray-900 mb-8"><?= htmlspecialchars($loc['name']) ?> <span class="text-gradient-gold italic"><?= htmlspecialchars($svc['name']) ?></span></h2>
            <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed space-y-4">
                <?php
                $uc = $loc['unique_content'];
                $uc = htmlspecialchars($uc);
                $uc = preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-gray-800">$1</strong>', $uc);
                $uc = preg_replace('/^\d+\.\s+/m', '<br>• ', $uc);
                $uc = nl2br($uc);
                echo $uc;
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Neden Bizi Seçmelisiniz -->
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline text-3xl sm:text-4xl text-gray-900"><?= htmlspecialchars($loc['name']) ?>'da Neden <span class="text-gradient-gold italic">Bizi Seçmelisiniz?</span></h2>
            <p class="text-gray-500 mt-3 max-w-2xl mx-auto"><?= htmlspecialchars($loc['name']) ?> bölgesinde yılların deneyimiyle fark yaratan hizmet kalitemiz</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $reasons = [
                ['workspace_premium', 'Profesyonel Ekipman', $loc['name'] . '\'da en son teknoloji yıkama makineleri ve çevre dostu temizlik ürünleri kullanıyoruz. Tüm ekipmanlarımız Avrupa standartlarında hijyen sertifikalıdır.'],
                ['local_shipping', 'Ücretsiz Alım & Teslimat', $loc['name'] . ' ve çevresinde ücretsiz halı alma ve teslim hizmeti sunuyoruz. Randevulu sistem ile size uygun saatte geliyoruz.'],
                ['verified', 'Memnuniyet Garantisi', 'Yıkama sonrası memnun kalmadığınız durumda ücretsiz tekrar yıkama yapıyoruz. ' . $loc['name'] . ' bölgesinde güvenilir ve tercih edilen bir temizlik hizmetiyiz.'],
                ['payments', 'Uygun Fiyat Garantisi', $loc['name'] . ' bölgesinde en rekabetçi fiyatları sunuyoruz. Ücretsiz keşif ile peşin fiyat teklifi alın, sürpriz ücretle karşılaşmayın.'],
                ['schedule', 'Teslimat Süreci', 'Standart yıkama ortalama en kısa sürede tamamlanır. Teslimat öncesinde sizinle iletişime geçerek ' . $loc['name'] . ' adresinize uygun zamanı belirliyoruz.'],
                ['eco', 'Çevre Dostu Yıkama', 'Biyolojik olarak parçalanabilen, alerjenleri yok eden özel temizlik solüsyonları kullanıyoruz. Bebek ve evcil hayvan dostu formüllerle güvenle yıkama.'],
            ];
            foreach ($reasons as $r):
            ?>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-[#9D68DB]/10 flex items-center justify-center mb-4">
                    <?= svgIcon($r[0], 'text-[#9D68DB]', '1.25rem') ?>
                </div>
                <h3 class="font-headline text-lg text-gray-900 mb-2"><?= htmlspecialchars($r[1]) ?></h3>
                <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($r[2]) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Süreç -->
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline text-3xl sm:text-4xl text-gray-900"><?= htmlspecialchars($loc['name']) ?>'da <?= htmlspecialchars($svc['name']) ?> <span class="text-gradient-gold italic">Nasıl Yapılır?</span></h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $steps = [
                ['call', 'Bizi Arayın', $loc['name'] . '\'dan ücretsiz keşif talebi oluşturun veya bizi arayın.'],
                ['local_shipping', 'Ücretsiz Alım', 'Ekibimiz ' . $loc['name'] . ' adresinize gelerek halılarınızı ücretsiz alır.'],
                ['dry_cleaning', 'Profesyonel Yıkama', 'Modern tesisimizde özel ekipmanlarla derinlemesine temizlik yapılır.'],
                ['verified', 'Teslim & Garanti', 'Vakumlu paketleme ile ' . $loc['name'] . ' adresinize teslim edilir.'],
            ];
            foreach ($steps as $i => $step):
            ?>
            <div class="text-center p-6 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#9D68DB] to-[#6D31C4] flex items-center justify-center mx-auto mb-4">
                    <?= svgIcon($step[0], 'text-white', '1.5rem') ?>
                </div>
                <div class="text-[#E0457B] font-bold text-sm mb-2"><?= $i + 1 ?>. Adım</div>
                <h3 class="font-headline text-lg text-gray-900 mb-2"><?= htmlspecialchars($step[1]) ?></h3>
                <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($step[2]) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- SSS (FAQ) -->
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline text-3xl sm:text-4xl text-gray-900"><?= htmlspecialchars($loc['name']) ?> <?= htmlspecialchars($svc['name']) ?> <span class="text-gradient-gold italic">Sık Sorulan Sorular</span></h2>
        </div>
        <div class="max-w-3xl mx-auto space-y-4">
            <?php foreach ($svc['faq'] as $f): ?>
            <details class="group bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-gray-50 transition-colors">
                    <h3 class="font-semibold text-gray-900 text-base pr-4"><?= htmlspecialchars($f['Soru'] ?? $f[0] ?? '') ?></h3>
                    <svg class="text-[#9D68DB] group-open:rotate-180 transition-transform duration-300 flex-shrink-0" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/></svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed"><?= htmlspecialchars($f['Cevap'] ?? $f[1] ?? '') ?></div>
            </details>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Hizmet Detayları Özet Tablosu -->
    <section class="mb-20">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 sm:p-12">
            <h2 class="font-headline text-2xl sm:text-3xl text-gray-900 mb-8 text-center"><?= htmlspecialchars($loc['name']) ?> <?= htmlspecialchars($svc['name']) ?> <span class="text-gradient-gold italic">Hizmet Özeti</span></h2>
            <div class="grid grid-cols-3 gap-6 text-center">
                <div class="p-4">
                    <div class="text-2xl font-bold text-gray-900">8+</div>
                    <div class="text-gray-500 text-sm mt-1">Yıllık Tecrübe</div>
                </div>
                <div class="p-4">
                    <div class="text-2xl font-bold text-gray-900">100+</div>
                    <div class="text-gray-500 text-sm mt-1">Mutlu Müşteri</div>
                </div>
                <div class="p-4">
                    <div class="text-2xl font-bold text-gray-900">4.9</div>
                    <div class="text-gray-500 text-sm mt-1">Google Puanı</div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-100">
                <p class="text-gray-600 text-center leading-relaxed max-w-3xl mx-auto">
                    <?= htmlspecialchars($loc['name']) ?> bölgesinde <?= htmlspecialchars(strtolower($svc['name'])) ?> hizmeti arıyorsanız doğru adrestesiniz.
                    <?= htmlspecialchars($businessName) ?> olarak <?= htmlspecialchars($loc['name']) ?> ve tüm çevre mahallelere ücretsiz servis hizmeti sunuyoruz.
                    <?= htmlspecialchars($loc['neighborhoods']) ?> bölgelerinde düzenli hizmet rotamız mevcuttur.
                    Profesyonel ekibimiz ve modern ekipmanlarımızla <?= htmlspecialchars(strtolower($svc['name'])) ?> ihtiyaçlarınızı en iyi şekilde karşılıyoruz.
                </p>
            </div>
        </div>
    </section>

    <!-- Diğer Lokasyonlar (Internal Linking) -->
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline text-3xl text-gray-900">Diğer <span class="text-gradient-gold italic">Hizmet Bölgelerimiz</span></h2>
            <p class="text-gray-500 mt-3">Ankara'nın tüm ilçelerine <?= htmlspecialchars(strtolower($svc['name'])) ?> hizmeti veriyoruz</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($otherServices as $os): ?>
            <a href="/<?= htmlspecialchars($locSlug . '-' . $os['slug']) ?>" class="bg-white p-4 rounded-xl border border-[#E0457B]/30 text-center hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                <?= svgIcon($os['icon'], 'text-[#E0457B] mb-2 block', '1.5rem') ?>
                <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($loc['name']) ?></span>
                <span class="block text-[#E0457B] text-xs mt-1"><?= htmlspecialchars($os['name']) ?></span>
            </a>
            <?php endforeach; ?>
            <?php foreach ($otherLocations as $ol): ?>
            <a href="/<?= htmlspecialchars($ol['slug'] . '-' . $svcSlug) ?>" class="bg-white p-4 rounded-xl border border-gray-100 text-center hover:border-[#9D68DB]/30 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                <svg class="text-[#9D68DB] text-2xl mb-2 block" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($ol['name']) ?></span>
                <span class="block text-gray-400 text-xs mt-1"><?= htmlspecialchars($svc['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Blog Yazıları -->
    <?php if (!empty($recentBlogs)): ?>
    <section class="mb-16">
        <div class="text-center mb-12">
            <h2 class="font-headline text-3xl text-gray-900">Faydalı <span class="text-gradient-gold italic">Blog Yazıları</span></h2>
        </div>
        <div class="grid sm:grid-cols-3 gap-6">
            <?php foreach ($recentBlogs as $rb): ?>
            <a href="/blog/<?= htmlspecialchars($rb['slug']) ?>" class="bg-white p-6 rounded-2xl border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 block group">
                <h3 class="font-headline text-lg text-gray-900 mb-3 group-hover:text-[#9D68DB] transition-colors line-clamp-2"><?= htmlspecialchars($rb['title']) ?></h3>
                <p class="text-gray-500 text-sm leading-relaxed line-clamp-3 mb-4"><?= htmlspecialchars($rb['summary'] ?? '') ?></p>
                <span class="text-[#9D68DB] text-sm font-semibold flex items-center gap-1">Devamını Oku <svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<!-- CTA -->
<section class="bg-gradient-to-r from-[#251560] via-[#6D31C4] to-[#9D68DB] py-20 text-center px-4">
    <h2 class="font-headline text-3xl sm:text-4xl text-white mb-6"><?= htmlspecialchars($loc['name']) ?>'da <?= htmlspecialchars($svc['name']) ?> İçin <span class="text-[#E0457B] italic">Hemen Arayın</span></h2>
    <p class="text-white/70 text-lg mb-10 max-w-2xl mx-auto">Ücretsiz keşif ve fiyat teklifi için bizi arayın. <?= htmlspecialchars($loc['name']) ?> ve çevresine ücretsiz servis hizmeti sunuyoruz.</p>
    <div class="flex flex-wrap justify-center gap-4">
        <a href="/#iletisim" class="bg-[#E0457B] text-white px-10 py-4 rounded-full font-bold hover:bg-[#C93A6A] hover:scale-105 transition-all shadow-xl">Ücretsiz Keşif Al</a>
        <?php if ($phoneRaw): ?>
        <a href="tel:<?= htmlspecialchars($phoneRaw) ?>" class="border border-white/30 text-white px-10 py-4 rounded-full font-bold hover:bg-white/10 transition-all flex items-center gap-2">
            <svg class="" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg> <?= htmlspecialchars($phone) ?>
        </a>
        <?php endif; ?>
    </div>
</section>

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">Bank Halı Yıkama</div>
        <p class="footer-desc">Ankara genelinde profesyonel hali yikama, koltuk yikama ve perde yikama hizmetleri.</p>
      </div>
      <div>
        <div class="footer-col-title">Hizli Menu</div>
        <div class="footer-links">
          <a href="/#hizmetler">Hizmetler</a>
          <a href="/fiyatlar">Fiyatlar</a>
          <a href="/#hakkimizda">Hakkimizda</a>
          <a href="/#yorumlar">Yorumlar</a>
          <a href="/#iletisim">Iletisim</a>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Iletisim</div>
        <div class="footer-links">
          <a href="tel:05456876161" data-kv="phone">05456876161</a>
          <a href="mailto:" data-kv="email"></a>
          <span data-kv="address">Plevne Mahallesi Plevne Sokak No:5/a Sincan/Ankara</span>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Calisma Saatleri</div>
        <div class="footer-links">
          <span data-kv="working_hours_short">7/24</span>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span class="footer-copy">&copy; 2026 Bank Halı Yıkama. Tum Haklari Saklidir.</span>
      <div class="footer-legal">
        <a href="/kvkk">KVKK</a>
        <a href="/gizlilik">Gizlilik</a>
        <a href="/cerez-politikasi">Cerez Politikasi</a>
      </div>
    </div>
  </div>
</footer>

<?php if ($whatsapp): ?>
<?php endif; ?>

<!-- Ziyaretci Takip -->
<img src="/api/track.php?pixel&amp;page=/<?= htmlspecialchars($fullSlug) ?>" alt="" width="1" height="1" style="position:absolute;left:-9999px" loading="eager"/>

<!-- WhatsApp Template Popup -->
<div id="waPopup" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('waPopup').classList.add('hidden')"></div>
    <div class="absolute bottom-24 right-6 md:right-8 w-72 bg-white rounded-2xl shadow-2xl overflow-hidden" style="animation: slideIn 0.3s ease">
        <div class="bg-gradient-to-r from-[#25D366] to-[#128C7E] px-4 py-3 flex items-center justify-between">
            <span class="text-white font-bold text-sm">WhatsApp ile Yazın</span>
            <button onclick="document.getElementById('waPopup').classList.add('hidden')" class="text-white/80 hover:text-white">
                <svg class="text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <div id="waPopupList" class="p-3 space-y-2 max-h-60 overflow-y-auto"></div>
    </div>
</div>

<!-- Instagram Button -->
<a href="<?= $instagramUrl ? htmlspecialchars($instagramUrl) : '#' ?>" target="_blank" rel="noopener noreferrer" data-kv-href="instagram_url" id="instagramButton" title="Instagram" class="fixed right-6 md:right-8 w-12 h-12 rounded-full text-white hover:scale-110 transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group" style="bottom:8.5rem;background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);box-shadow:0 0 24px rgba(214,36,159,0.45);">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
    <div class="absolute right-14 text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm" style="background:linear-gradient(135deg,#d6249f,#285AEB)">Instagram</div>
</a>

<!-- Call Button -->
<a href="tel:<?= htmlspecialchars($phoneRaw) ?>" data-kv-href="phone_raw" data-kv-href-prefix="tel:" class="fixed bottom-20 right-6 md:bottom-22 md:right-8 bg-gradient-to-br from-[#4CAF50] to-[#2E7D32] text-white w-12 h-12 rounded-full shadow-[0_0_24px_rgba(76,175,80,0.4)] hover:scale-110 hover:shadow-[0_0_32px_rgba(76,175,80,0.5)] transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group" id="callButton">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
    <div class="absolute right-14 bg-[#251560] text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm">Hemen Ara</div>
</a>

<!-- WhatsApp Button -->
<a href="#" target="_blank" class="fixed bottom-6 right-6 md:bottom-8 md:right-8 bg-gradient-to-br from-[#25D366] to-[#128C7E] text-white w-12 h-12 rounded-full shadow-[0_0_24px_rgba(37,211,102,0.4)] hover:scale-110 hover:shadow-[0_0_32px_rgba(37,211,102,0.5)] transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group wa-bounce" id="waButton" data-kv-wa="whatsapp_number" onclick="toggleWaPopup(); return false;">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.654-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    <div class="absolute right-14 bg-[#128C7E] text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm">WhatsApp</div>
</a>

<!-- Scroll to Top -->
<button id="scrollTopBtn" class="fixed bottom-6 left-6 md:bottom-8 md:left-8 bg-[#1B3A5C] text-white w-12 h-12 rounded-full shadow-lg shadow-[#1B3A5C]/20 hover:scale-105 hover:shadow-[#1B3A5C]/40 transition-all duration-500 ease-out z-[100] flex items-center justify-center" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Yukarı çık">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8z"/></svg>
</button>

<script src="/js/main.js" defer></script>
</body>
</html>
