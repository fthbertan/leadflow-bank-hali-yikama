<?php
// LeadFlow — Dinamik Blog Detay Sayfası
// .htaccess ile slug.html → post.php?slug=xxx yönlendirmesi yapılır
// DB'den blog ve ayarları okuyarak HTML template'i doldurur

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../api/config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: index.html');
    exit;
}

$db = getDB();

// Blog verisini çek
$stmt = $db->prepare('SELECT * FROM blogs WHERE slug = :slug AND status = :status LIMIT 1');
$stmt->execute([':slug' => $slug, ':status' => 'published']);
$blog = $stmt->fetch();

if (!$blog) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Sayfa Bulunamadı</title>
    <script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="text-center"><h1 class="text-6xl font-bold text-gray-300 mb-4">404</h1>
    <p class="text-gray-500 mb-6">Aradığınız blog yazısı bulunamadı.</p>
    <a href="index.html" class="text-blue-600 hover:underline">Blog Listesine Dön</a></div></body></html>';
    exit;
}

$blog['tags'] = json_decode($blog['tags'] ?? '[]', true) ?: [];

// Ayarları çek
$settings = [];
$stmtS = $db->query('SELECT setting_key, setting_value FROM settings');
while ($row = $stmtS->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// İlgili blogları çek (aynı kategorideki diğer 3 blog)
$stmtR = $db->prepare('SELECT * FROM blogs WHERE status = :status AND slug != :slug ORDER BY created_at DESC LIMIT 3');
$stmtR->execute([':status' => 'published', ':slug' => $slug]);
$relatedBlogs = $stmtR->fetchAll();

// İlgili blog kartları HTML
$relatedHTML = '';
foreach ($relatedBlogs as $rb) {
    $rb['tags'] = json_decode($rb['tags'] ?? '[]', true) ?: [];
    $coverImg = $rb['cover_image'] ?? '';
    $coverHTML = $coverImg
        ? '<div class="h-48 relative overflow-hidden"><img src="' . htmlspecialchars($coverImg) . '" alt="' . htmlspecialchars($rb['title']) . '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"/><div class="absolute top-4 left-4"><span class="bg-primary-container/70 text-primary px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider">' . htmlspecialchars($rb['category'] ?: 'Blog') . '</span></div></div>'
        : '<div class="h-48 bg-gradient-to-br from-primary/10 via-surface-container-high to-secondary/10 flex items-center justify-center relative overflow-hidden"><svg class="text-on-surface-variant/10" style="width:80px;height:80px;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg><div class="absolute top-4 left-4"><span class="bg-primary-container/70 text-primary px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider">' . htmlspecialchars($rb['category'] ?: 'Blog') . '</span></div></div>';

    $relatedHTML .= '<a href="/blog/' . htmlspecialchars($rb['slug']) . '" class="group bg-surface-container rounded-2xl border border-outline-variant/10 overflow-hidden card-glow block">'
        . $coverHTML
        . '<div class="p-6">'
        . '<div class="flex items-center gap-3 text-on-surface-variant text-xs mb-3">'
        . '<span class="flex items-center gap-1"><svg class="text-xs" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/></svg> ' . htmlspecialchars($rb['date'] ?? '') . '</span>'
        . '<span class="flex items-center gap-1"><svg class="text-xs" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z</svg> ' . ($rb['read_time'] ?? '5') . ' dk</span>'
        . '</div>'
        . '<h3 class="font-headline text-lg text-on-background mb-3 group-hover:text-primary transition-colors duration-300 line-clamp-2">' . htmlspecialchars($rb['title']) . '</h3>'
        . '<p class="text-on-surface-variant text-sm leading-relaxed line-clamp-3 mb-4">' . htmlspecialchars($rb['summary'] ?? '') . '</p>'
        . '<span class="text-primary text-sm font-semibold flex items-center gap-1">Devamını Oku <svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg></span>'
        . '</div></a>';
}

// Tag HTML
$tagsHTML = '';
foreach ($blog['tags'] as $tag) {
    $tagsHTML .= '<span class="bg-surface-container text-on-surface-variant px-3 py-1 rounded-full text-xs">' . htmlspecialchars($tag) . '</span>';
}

// Kapak görseli HTML (başlıktan önce gösterilecek)
$coverImageHTML = '';
if (!empty($blog['cover_image'])) {
    $coverImageHTML = '<div class="max-w-4xl mx-auto mb-8 px-4 sm:px-6 lg:px-8"><div class="rounded-2xl overflow-hidden shadow-lg"><img src="' . htmlspecialchars($blog['cover_image']) . '" alt="' . htmlspecialchars($blog['title']) . '" class="w-full h-64 sm:h-80 lg:h-96 object-cover" onerror="this.parentElement.parentElement.style.display=\'none\'"/></div></div>';
}

// Dinamik site URL
$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

// Logo harfi
$businessName = $settings['business_name'] ?? '';
$logoLetter = mb_substr($businessName, 0, 1);

// Sosyal medya linkleri
$socialHTML = '';
$socialFooterHTML = '';
if (!empty($settings['instagram'])) {
    $socialHTML .= '<a href="' . htmlspecialchars($settings['instagram']) . '" target="_blank" class="hover:opacity-80 transition-opacity" title="Instagram"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>';
    $socialFooterHTML .= '<a href="' . htmlspecialchars($settings['instagram']) . '" target="_blank" class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:opacity-80 hover:bg-white/20 transition-all" title="Instagram"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>';
}
if (!empty($settings['facebook'])) {
    $socialHTML .= '<a href="' . htmlspecialchars($settings['facebook']) . '" target="_blank" class="hover:opacity-80 transition-opacity" title="Facebook"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>';
    $socialFooterHTML .= '<a href="' . htmlspecialchars($settings['facebook']) . '" target="_blank" class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:opacity-80 hover:bg-white/20 transition-all" title="Facebook"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>';
}

// Template dosyasını oku
$templatePath = __DIR__ . '/blog-detail-template.html';
if (!file_exists($templatePath)) {
    http_response_code(500);
    echo 'Blog template dosyası bulunamadı.';
    exit;
}
$html = file_get_contents($templatePath);

// Tüm placeholder'ları değiştir
$replacements = [
    // Blog bilgileri
    '{{BLOG_TITLE}}'            => htmlspecialchars($blog['title']),
    '{{BLOG_TITLE_SHORT}}'      => htmlspecialchars(mb_substr($blog['title'], 0, 50, 'UTF-8')),
    '{{BLOG_SUMMARY}}'          => htmlspecialchars($blog['summary'] ?? ''),
    '{{BLOG_CONTENT}}'          => $blog['content'] ?? '',
    '{{BLOG_CATEGORY}}'         => htmlspecialchars($blog['category'] ?? 'Blog'),
    '{{BLOG_DATE}}'             => (function() use ($blog) {
        $raw = $blog['date'] ?? '';
        if (!$raw || $raw === '0000-00-00') return '';
        $months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        return $dt ? (int)$dt->format('d') . ' ' . $months[(int)$dt->format('m') - 1] . ' ' . $dt->format('Y') : htmlspecialchars($raw);
    })(),
    '{{BLOG_READ_TIME}}'        => (int)($blog['read_time'] ?? 5),
    '{{BLOG_META_DESCRIPTION}}' => htmlspecialchars($blog['summary'] ?? ''),
    '{{BLOG_TAGS_HTML}}'        => $tagsHTML,
    '{{RELATED_POSTS_HTML}}'    => $relatedHTML,
    '{{BLOG_COVER_IMAGE_HTML}}' => $coverImageHTML,
    // SEO — OG URL, OG Image, Date ISO
    '{{BLOG_OG_URL}}'           => $siteUrl . '/blog/' . htmlspecialchars($slug),
    '{{BLOG_OG_IMAGE}}'         => !empty($blog['cover_image'])
        ? $siteUrl . '/' . ltrim($blog['cover_image'], '/')
        : $siteUrl . '/images/heroweb-1.webp',
    '{{BLOG_DATE_ISO}}'         => !empty($blog['created_at'])
        ? date('Y-m-d\TH:i:sP', strtotime($blog['created_at']))
        : date('Y-m-d\TH:i:sP'),
    // İşletme bilgileri
    '{{SITE_URL}}'              => $siteUrl,
    '{{BLOG_AUTHOR_NAME}}'      => htmlspecialchars($settings['blog_author_name'] ?? ($businessName . ' Uzman Ekibi')),
    '{{BLOG_AUTHOR_BIO}}'       => htmlspecialchars($settings['blog_author_bio'] ?? 'Profesyonel temizlik ekibimiz, sektördeki bilgi birikimini sizlerle paylaşmayı amaçlamaktadır.'),
    '{{BUSINESS_NAME}}'         => htmlspecialchars($businessName),
    '{{LOGO_LETTER}}'           => htmlspecialchars($logoLetter),
    '{{PHONE}}'                 => htmlspecialchars($settings['phone'] ?? ''),
    '{{PHONE_RAW}}'             => htmlspecialchars($settings['phone_raw'] ?? ''),
    '{{EMAIL}}'                 => htmlspecialchars($settings['email'] ?? ''),
    '{{ADDRESS}}'               => htmlspecialchars($settings['address'] ?? ''),
    '{{WORKING_HOURS}}'         => htmlspecialchars($settings['working_hours'] ?? ''),
    '{{WORKING_HOURS_SHORT}}'   => htmlspecialchars($settings['working_hours_short'] ?? ''),
    '{{WHATSAPP_NUMBER}}'       => htmlspecialchars($settings['whatsapp_number'] ?? ''),
    '{{PHONE2}}'                => htmlspecialchars($settings['phone2'] ?? ''),
    '{{PHONE2_RAW}}'            => htmlspecialchars($settings['phone2_raw'] ?? ''),
    '{{HERO_SUBTITLE}}'         => htmlspecialchars($settings['hero_subtitle'] ?? ''),
    '{{FOOTER_DESCRIPTION}}'    => htmlspecialchars($settings['footer_description'] ?? ''),
    '{{CTA_TITLE}}'             => htmlspecialchars($settings['cta_title'] ?? ''),
    '{{CTA_DESCRIPTION}}'       => htmlspecialchars($settings['cta_description'] ?? ''),
    '{{MAP_EMBED_URL}}'         => htmlspecialchars($settings['map_embed_url'] ?? ''),
    '{{MAP_LINK_URL}}'          => htmlspecialchars($settings['map_link_url'] ?? ''),
    '{{TOP_BAR_SOCIAL_HTML}}'   => $socialHTML,
    '{{SOCIAL_LINKS_HTML}}'     => $socialFooterHTML,
];

foreach ($replacements as $key => $value) {
    $html = str_replace($key, $value, $html);
}

// Kapak görseli: blog-content'ten hemen önce ekle
if (!empty($coverImageHTML)) {
    $html = str_replace('<!-- Blog Content -->', $coverImageHTML . "\n<!-- Blog Content -->", $html);
}

header('Content-Type: text/html; charset=utf-8');
echo $html;
