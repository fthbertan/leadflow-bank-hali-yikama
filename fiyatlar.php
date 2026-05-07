<?php
// LeadFlow — Dinamik Fiyatlar Sayfası
// /api/services.php'den hizmet ve fiyat kalemlerini çeker, admin panelinden güncellenebilir

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/api/config.php';

$db = getDB();
$settings = [];
try {
    $stmtS = $db->query('SELECT setting_key, setting_value FROM settings');
    while ($row = $stmtS->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$businessName = $settings['business_name'] ?? 'Bank Halı Yıkama';
$phone = $settings['phone'] ?? '0 545 687 61 61';
$phoneRaw = $settings['phone_raw'] ?? '05456876161';
$whatsapp = $settings['whatsapp_number'] ?? '905456876161';

// Hizmetleri ve fiyat kalemlerini çek
$services = [];
try {
    $stmt = $db->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC');
    $rows = $stmt->fetchAll();

    $hasItems = false;
    try { $db->query('SELECT 1 FROM service_items LIMIT 1'); $hasItems = true; } catch (Exception $e) {}

    $allItems = [];
    if ($hasItems) {
        $allItems = $db->query('SELECT * FROM service_items ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    foreach ($rows as $r) {
        $svcId = (int)$r['id'];
        $items = array_values(array_filter($allItems, function($item) use ($svcId) {
            return (int)$item['service_id'] === $svcId;
        }));
        $services[] = [
            'id' => $r['id'],
            'name' => $r['title'],
            'desc' => $r['description'],
            'icon' => $r['icon'],
            'price' => $r['price'],
            'items' => $items,
        ];
    }
} catch (Exception $e) {}

$siteUrl = 'https://bankhaliyikama.com.tr';
$pageTitle = 'Fiyatlarımız | ' . $businessName;
$metaDesc = $businessName . ' hizmet fiyatları. Halı yıkama, koltuk yıkama, perde yıkama, yorgan yıkama ve tüm temizlik hizmetlerinde şeffaf fiyatlandırma.';

// SVG ikon helper
function svcIcon($name) {
    $icons = [
        'dry_cleaning' => 'M19.56 11.36L13 8.44V7c0-.55-.45-1-1-1s-1 .45-1 1v1.44l-6.56 2.92c-.88.39-.88 1.63 0 2.02L11 16.36V19c0 .55.45 1 1 1s1-.45 1-1v-2.64l6.56-2.92c.88-.39.88-1.63 0-2.08zM12 14.3l-4.74-2.12L12 10.06l4.74 2.12L12 14.3z',
        'weekend' => 'M21 9V7c0-1.65-1.35-3-3-3H6C4.35 4 3 5.35 3 7v2c-1.65 0-3 1.35-3 3v5c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2v-5c0-1.65-1.35-3-3-3z',
        'curtains' => 'M20 19V3H4v16H2v2h20v-2h-2zM12 11l-2-2V5h4v4l-2 2zm-6 8V5h2v5l4 4 4-4V5h2v14H6z',
        'bed' => 'M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z',
        'king_bed' => 'M20 10V7c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v3c-1.1 0-2 .9-2 2v5h1.33L4 19h1l.67-2h12.67l.66 2h1l.67-2H22v-5c0-1.1-.9-2-2-2z',
        'auto_awesome' => 'M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5z',
        'local_shipping' => 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4z',
        'cleaning_services' => 'M16 11h-1V3c0-.55-.45-1-1-1h-4c-.55 0-1 .45-1 1v8H8c-2.76 0-5 2.24-5 5v7h18v-7c0-2.76-2.24-5-5-5z',
    ];
    $path = $icons[$name] ?? $icons['cleaning_services'];
    return '<svg style="width:1.5em;height:1.5em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="'.$path.'"/></svg>';
}
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="tr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<link rel="canonical" href="<?= $siteUrl ?>/fiyatlar"/>
<link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
<link rel="stylesheet" href="/css/style.css"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<style>
.pricing-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    transition: all 0.4s ease;
    overflow: hidden;
}
.pricing-card:hover {
    box-shadow: 0 12px 40px rgba(46,21,72,0.08);
    border-color: #E0457B;
    transform: translateY(-4px);
}
.pricing-card-header {
    background: linear-gradient(135deg, #2E1548, #5B2C87);
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.pricing-card-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(224,69,123,0.2);
    color: #E0457B;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.pricing-card-header h3 {
    color: #fff;
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0;
}
.pricing-card-header .start-price {
    color: #E0457B;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 2px;
}
.pricing-card-body {
    padding: 0;
    flex: 1;
}
.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 28px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}
.price-row:last-child {
    border-bottom: none;
}
.price-row:hover {
    background: #f0fdf4;
}
.price-row-name {
    font-size: 0.9rem;
    color: #1f2937;
    font-weight: 500;
}
.price-row-desc {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 2px;
}
.price-row-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #2E1548;
    white-space: nowrap;
}
.pricing-card-footer {
    padding: 20px 28px;
    border-top: 1px solid #f3f4f6;
    margin-top: auto;
}
.pricing-card-footer a {
    display: block;
    text-align: center;
    background: linear-gradient(135deg, #B33562, #E0457B);
    color: #fff;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s;
}
.pricing-card-footer a:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(224,69,123,0.3);
}
.no-items-msg {
    padding: 20px 28px;
    color: #9ca3af;
    font-size: 0.85rem;
    text-align: center;
    font-style: italic;
}
@keyframes waBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.wa-bounce{animation:waBounce 2s ease-in-out infinite}
.wa-bounce:hover{animation:none}
#callButton{bottom:8.5rem!important}
#waButton{bottom:5rem!important}
#instagramButton{bottom:1.5rem!important}
@media(min-width:768px){#callButton{bottom:8rem!important}#waButton{bottom:5rem!important}#instagramButton{bottom:2rem!important}}
.nav-glass-pill { background: rgba(255,255,255,0.14) !important; border: 1px solid rgba(255,255,255,0.32); border-radius: 999px; padding: 6px !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); gap: 2px !important; box-shadow: 0 4px 24px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.2); }
.nav-link { position: relative; transition: all 0.3s ease; color: #FFFFFF; font-weight: 700; padding: 9px 18px !important; border-radius: 999px; }
.nav-link { color: white !important; }
.nav-link.active { color: #E0457B !important; font-weight: 600; }
.nav-link::after { content: ''; position: absolute; bottom: -4px; left: 50%; width: 0; height: 2px; background: #E0457B; transition: all 0.3s ease; transform: translateX(-50%); }
.nav-link:hover::after, .nav-link.active::after { width: 100%; }
.nav-link:hover { color: #E0457B !important; }
.nav-hero { background: transparent; }
.nav-hero .nav-link { color: rgba(255,255,255,0.95) !important; text-shadow: 0 1px 4px rgba(0,0,0,0.5); }
.nav-hero .nav-link:hover { color: #E0457B !important; }
.nav-hero .nav-link.active { color: #E0457B !important; }
.nav-hero .logo-text { color: white; }
.nav-hero #mobileMenuBtn { color: white !important; }
.nav-scrolled { background: #2E1548; box-shadow: 0 4px 30px rgba(0,0,0,0.3); backdrop-filter: blur(12px); }
.nav-scrolled .nav-link { color: rgba(255,255,255,0.85) !important; }
.nav-scrolled .nav-link:hover { color: #E0457B !important; }
.nav-scrolled .nav-link.active { color: #E0457B !important; }
.nav-scrolled .logo-text { color: white; }
.nav-scrolled #mobileMenuBtn { color: white !important; }
#mobileMenu .nav-link { color: white !important; }
#mobileMenu .nav-link:hover { color: #E0457B !important; }
#mobileMenu .nav-link.active { color: #E0457B !important; }
#mobileMenu { max-height: 0; opacity: 0; overflow: hidden; transition: max-height 0.35s cubic-bezier(0,1,0,1), opacity 0.25s ease, padding 0.3s ease; padding-top: 0; padding-bottom: 0; }
#mobileMenu.open { max-height: 600px; opacity: 1; padding-top: 0.5rem; padding-bottom: 1.5rem; overflow-y: auto; transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), opacity 0.3s ease, padding 0.3s ease; }
#mobileMenuBtn { color: white !important; }
#mobileMenuBtn .menu-icon { display: block; }
#mobileMenuBtn .close-icon { display: none; }
#mobileMenuBtn.active .menu-icon { display: none; }
#mobileMenuBtn.active .close-icon { display: block; animation: spinIn 0.3s ease; }
@keyframes spinIn { from { transform: rotate(-90deg) scale(0.5); opacity: 0; } to { transform: rotate(0) scale(1); opacity: 1; } }
.btn-cta { background: linear-gradient(110deg, #B33562 0%, #E0457B 25%, #B33562 50%, #E0457B 75%, #B33562 100%); background-size: 200% 100%; animation: shimmer 4s linear infinite; }
.btn-cta:hover { filter: brightness(1.1); transform: scale(1.05); }
@keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
#scrollTopBtn { opacity: 0; pointer-events: none; transition: opacity 0.4s ease; }
#scrollTopBtn.visible { opacity: 1; pointer-events: auto; }
</style>
</head>
<body class="bg-[#f8fafc] font-body text-gray-800">

<!-- Top Info Bar -->
<div id="topBar" class="bg-[#2E1548] border-b border-white/10 py-2 text-xs z-[60] relative">
    <div class="flex justify-between items-center px-4 sm:px-6 lg:px-10 max-w-full">
        <div class="flex items-center gap-5 text-white/70">
            <a href="tel:<?= htmlspecialchars($phoneRaw) ?>" class="flex items-center gap-1.5 hover:text-[#E0457B] transition-colors">
                <svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0;color:#5B2C87" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                <span data-kv="phone"><?= htmlspecialchars($phone) ?></span>
            </a>
            <span class="hidden lg:flex items-center gap-1.5">
                <svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0;color:#8E5CC0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <span data-kv="working_hours_short"><?= htmlspecialchars($settings['working_hours_short'] ?? '7/24 Hizmetinizdeyiz') ?></span>
            </span>
        </div>
        <div class="flex items-center justify-center gap-3 text-white/70 sm:w-[160px]" id="kvTopBarSocial">
            <a href="https://www.instagram.com/hayati_bank" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="Instagram"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
            <a href="https://www." target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="Facebook"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
            <a href="https://www.tiktok.com/@hayati_bank1" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="TikTok"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="white"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.52a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.43v-7.15a8.16 8.16 0 005.58 2.09V11.1a4.84 4.84 0 01-3.58-1.58V6.69h3.58z"/></svg></a>
        </div>
    </div>
</div>

<!-- Navbar -->
<nav id="mainNav" class="sticky w-full z-50 transition-all duration-500 nav-scrolled" role="navigation" aria-label="Ana navigasyon">
    <div class="flex justify-between items-center px-4 sm:px-6 lg:px-10 py-4 max-w-full">
        <a href="/" class="group flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#E0457B] flex items-center justify-center shadow-lg shadow-[#E0457B]/20 group-hover:scale-110 transition-transform duration-300">
                <span class="font-bold text-white text-xl"><?= htmlspecialchars(mb_substr($businessName, 0, 1)) ?></span>
            </div>
            <span class="logo-text text-2xl sm:text-3xl font-extrabold tracking-tight text-white whitespace-nowrap" data-kv="business_name"><?= htmlspecialchars($businessName) ?></span>
        </a>
        <div class="flex items-center gap-4">
            <div class="hidden lg:flex items-center nav-glass-pill">
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/#hizmetlerimiz">Hizmetlerimiz</a>
                <a class="nav-link active text-xs font-semibold uppercase tracking-[0.15em]" href="/fiyatlar">Fiyatlarımız</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/#surecimiz">Sürecimiz</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/#hakkimizda">Hakkımızda</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/galeri">Galeri</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/#yorumlar">Yorumlar</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/blog/">Blog</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.15em]" href="/#iletisim">İletişim</a>
            </div>
            <a class="hidden sm:inline-flex btn-cta text-white px-6 py-2.5 rounded-full font-semibold text-sm tracking-wide shadow-lg shadow-[#E0457B]/30 hover:scale-105 transition-transform duration-300" href="/#iletisim">Ücretsiz Keşif</a>
            <button id="mobileMenuBtn" class="lg:hidden p-2">
                <svg class="text-3xl menu-icon" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                <svg class="text-3xl close-icon" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
    </div>
    <div id="mobileMenu" class="lg:hidden w-full px-6 space-y-4 bg-[#2E1548]/95 backdrop-blur-xl border-t border-white/10">
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/#hizmetlerimiz">Hizmetlerimiz</a>
        <a class="nav-link active block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/fiyatlar">Fiyatlarımız</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/#surecimiz">Sürecimiz</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/#hakkimizda">Hakkımızda</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/galeri">Galeri</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/#yorumlar">Yorumlar</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/blog/">Blog</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/#iletisim">İletişim</a>
        <a class="block btn-cta text-white px-6 py-3 rounded-full font-semibold text-sm tracking-wide text-center sm:hidden" href="/#iletisim">Ücretsiz Keşif</a>
    </div>
</nav>

<!-- Page Header -->
<header class="bg-gradient-to-br from-[#2E1548] via-[#5B2C87] to-[#2E1548] py-16 sm:py-24 text-center relative overflow-hidden">
    <div class="absolute top-10 right-10 w-64 h-64 bg-[#E0457B]/5 rounded-full blur-[80px]"></div>
    <div class="absolute bottom-0 left-10 w-48 h-48 bg-[#5B2C87]/20 rounded-full blur-[60px]"></div>
    <div class="relative z-10 max-w-4xl mx-auto px-4">
        <nav class="flex justify-center items-center gap-2 text-white/50 text-xs mb-8">
            <a href="/" class="hover:text-[#E0457B] transition-colors">Ana Sayfa</a>
            <svg style="width:1em;height:1em" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            <span class="text-[#E0457B]">Fiyatlarımız</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight mb-4">Şeffaf & Uygun <span class="text-[#E0457B]">Fiyatlar</span></h1>
        <p class="text-white/50 text-lg max-w-2xl mx-auto">Tüm hizmetlerimizde net fiyatlandırma. Sürpriz ücret yok, ücretsiz keşif ile tam fiyat bilgisi alın.</p>
    </div>
</header>

<!-- Pricing Cards -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">

    <?php if (empty($services)): ?>
    <p class="text-center text-gray-400 text-lg">Fiyat bilgileri yükleniyor...</p>
    <?php else: ?>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($services as $svc): ?>
        <div class="pricing-card">
            <div class="pricing-card-header">
                <div class="pricing-card-header-icon">
                    <?= svcIcon($svc['icon'] ?? 'cleaning_services') ?>
                </div>
                <div>
                    <h3><?= htmlspecialchars($svc['name']) ?></h3>
                    <?php if ($svc['price']): ?>
                    <div class="start-price"><?= htmlspecialchars($svc['price']) ?>'den başlayan</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pricing-card-body">
                <?php if (!empty($svc['items'])): ?>
                    <?php foreach ($svc['items'] as $item): ?>
                    <div class="price-row">
                        <div>
                            <div class="price-row-name"><?= htmlspecialchars($item['name']) ?></div>
                            <?php if (!empty($item['description'])): ?>
                            <div class="price-row-desc"><?= htmlspecialchars($item['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="price-row-value">
                            <?php
                            $price = $item['price'] ?? '';
                            $unit = $item['unit'] ?? '';
                            if ($price) {
                                echo (strpos($price, '₺') === false ? '₺' : '') . htmlspecialchars($price);
                                if ($unit) echo '<span style="font-size:0.7rem;font-weight:400;color:#9ca3af;margin-left:2px">' . htmlspecialchars($unit) . '</span>';
                            } else {
                                echo '<span style="color:#E0457B;font-size:0.8rem">Teklif Al</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items-msg">Detaylı fiyat bilgisi için bizi arayın.</div>
                <?php endif; ?>
            </div>
            <div class="pricing-card-footer">
                <a href="tel:<?= htmlspecialchars($phoneRaw) ?>">Hemen Ara</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="text-center text-gray-400 text-sm mt-10">* Fiyatlar ürünün durumuna göre değişiklik gösterebilir. Kesin fiyat için ücretsiz keşif hizmeti sunuyoruz.</p>

    <?php endif; ?>

</main>

<!-- CTA -->
<section class="bg-gradient-to-r from-[#2E1548] via-[#5B2C87] to-[#2E1548] py-16 text-center px-4">
    <h2 class="text-2xl sm:text-3xl font-black text-white mb-4">Ücretsiz Keşif İçin <span class="text-[#E0457B]">Hemen Arayın</span></h2>
    <p class="text-white/50 text-base mb-8 max-w-xl mx-auto">Halılarınızı yerinde inceliyor, net fiyat teklifi sunuyoruz. Sürpriz ücret yok.</p>
    <div class="flex flex-wrap justify-center gap-4">
        <a href="/#iletisim" class="bg-[#E0457B] text-white px-8 py-3 rounded-full font-bold hover:scale-105 transition-all shadow-lg shadow-[#E0457B]/30">Ücretsiz Keşif Al</a>
        <?php if ($phoneRaw): ?>
        <a href="tel:<?= htmlspecialchars($phoneRaw) ?>" class="border border-white/30 text-white px-8 py-3 rounded-full font-bold hover:bg-white/10 transition-all"><?= htmlspecialchars($phone) ?></a>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer class="bg-[#2E1548] pt-16 sm:pt-24 pb-12 px-4 sm:px-6 lg:px-8 border-t border-white/10" role="contentinfo">
    <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 sm:gap-12">
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 sm:w-10 sm:h-10 rounded-xl bg-[#E0457B] flex items-center justify-center shadow-lg shadow-[#E0457B]/30">
                    <span class="font-bold text-white text-xl"><?= htmlspecialchars(mb_substr($businessName, 0, 1)) ?></span>
                </div>
                <span class="text-lg sm:text-xl font-extrabold tracking-tight text-white whitespace-nowrap" data-kv="business_name"><?= htmlspecialchars($businessName) ?></span>
            </div>
            <p class="text-sm text-white/70 mb-8 leading-relaxed"><?= htmlspecialchars($businessName) ?> olarak, halılarınızı profesyonel ekip ve ekipmanlarla en hijyenik şekilde yıkıyoruz.</p>
            <div class="flex gap-4" id="kvSocialLinks">
                <a href="https://www.instagram.com/hayati_bank" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:opacity-80 transition-all" title="Instagram"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                <a href="https://www." target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:opacity-80 transition-all" title="Facebook"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                <a href="https://www.tiktok.com/@hayati_bank1" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:opacity-80 transition-all" title="TikTok"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="white"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.52a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.43v-7.15a8.16 8.16 0 005.58 2.09V11.1a4.84 4.84 0 01-3.58-1.58V6.69h3.58z"/></svg></a>
            </div>
        </div>
        <div class="lg:pl-6">
            <h3 class="text-xs font-label uppercase tracking-widest text-[#E0457B] mb-6">Hızlı Menü</h3>
            <ul class="space-y-4">
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/#hizmetlerimiz">Hizmetlerimiz</a></li>
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/fiyatlar">Fiyatlarımız</a></li>
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/#surecimiz">Sürecimiz</a></li>
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/#hakkimizda">Hakkımızda</a></li>
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/galeri">Galeri</a></li>
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/blog/">Blog</a></li>
                <li><a class="text-white/70 hover:text-[#E0457B] transition-colors text-sm" href="/#iletisim">İletişim</a></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-label uppercase tracking-widest text-[#E0457B] mb-6">İletişim</h3>
            <ul class="space-y-4">
                <li><span class="text-white/70 text-sm" data-kv="phone"><?= htmlspecialchars($phone) ?></span></li>
                <li><span class="text-white/70 text-sm" data-kv="email"><?= htmlspecialchars($settings['email'] ?? '') ?></span></li>
                <li><span class="text-white/70 text-sm">info@hayati_bank1.com.tr</span></li>
                <li><span class="text-white/70 text-sm"><?= htmlspecialchars($settings['address'] ?? 'Plevne Mahallesi Plevne Sokak No:5/a Sincan/Ankara') ?></span></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-label uppercase tracking-widest text-[#E0457B] mb-6">Bülten</h3>
            <p class="text-sm text-white/70 mb-4">Bakım ipuçları ve kampanyalar için abone olun.</p>
            <div class="relative">
                <input id="newsletterEmail" class="w-full bg-white/10 border border-white/20 rounded-xl py-3 px-5 text-sm text-white placeholder-white/30 focus:ring-1 focus:ring-[#E0457B] transition-all" placeholder="E-posta adresiniz" type="email"/>
                <button type="button" onclick="var e=document.getElementById('newsletterEmail'),m=document.getElementById('newsletterMsg');if(e.value&&e.value.includes('@')){e.value='';m.textContent='✓ Aboneliğiniz alındı!';m.className='text-xs text-green-400 mt-2';setTimeout(function(){m.textContent=''},4000)}else{m.textContent='Geçerli bir e-posta girin.';m.className='text-xs text-red-400 mt-2'}" class="absolute right-2 top-1.5 btn-cta text-white p-1.5 rounded-lg"><svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button>
            </div>
            <div id="newsletterMsg" class="text-xs mt-2"></div>
            <div class="mt-8">
                <span class="text-[10px] text-white/25 uppercase tracking-widest">&copy; 2026 <?= htmlspecialchars($businessName) ?>. Tüm Hakları Saklıdır.</span>
            </div>
        </div>
    </div>
    <!-- Hizmet Bölgeleri — SEO Internal Links -->
    <div class="max-w-7xl mx-auto mt-12 pt-8 border-t border-white/10 space-y-6">
        <div>
            <h3 class="text-xs font-label uppercase tracking-widest text-[#E0457B] mb-3">Halı Yıkama Hizmet Bölgeleri</h3>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/60">
                <a href="/eryaman-hali-yikama" class="hover:text-[#E0457B] transition-colors">Eryaman Halı Yıkama</a>
                <a href="/sincan-hali-yikama" class="hover:text-[#E0457B] transition-colors">Sincan Halı Yıkama</a>
                <a href="/baglica-hali-yikama" class="hover:text-[#E0457B] transition-colors">Bağlıca Halı Yıkama</a>
                <a href="/batikent-hali-yikama" class="hover:text-[#E0457B] transition-colors">Batıkent Halı Yıkama</a>
                <a href="/etimesgut-hali-yikama" class="hover:text-[#E0457B] transition-colors">Etimesgut Halı Yıkama</a>
                <a href="/yasamkent-hali-yikama" class="hover:text-[#E0457B] transition-colors">Yaşamkent Halı Yıkama</a>
                <a href="/beytepe-hali-yikama" class="hover:text-[#E0457B] transition-colors">Beytepe Halı Yıkama</a>
                <a href="/yenikent-hali-yikama" class="hover:text-[#E0457B] transition-colors">Yenikent Halı Yıkama</a>
                <a href="/yapracik-hali-yikama" class="hover:text-[#E0457B] transition-colors">Yapracık Halı Yıkama</a>
                <a href="/cayyolu-hali-yikama" class="hover:text-[#E0457B] transition-colors">Çayyolu Halı Yıkama</a>
                <a href="/umitkoy-hali-yikama" class="hover:text-[#E0457B] transition-colors">Ümitköy Halı Yıkama</a>
                <a href="/konutkent-hali-yikama" class="hover:text-[#E0457B] transition-colors">Konutkent Halı Yıkama</a>
                <a href="/incek-hali-yikama" class="hover:text-[#E0457B] transition-colors">İncek Halı Yıkama</a>
            </div>
        </div>
        <div>
            <h3 class="text-xs font-label uppercase tracking-widest text-[#E0457B] mb-3">Tüm Hizmetlerimiz</h3>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/60">
                <a href="/hali-yikama" class="hover:text-[#E0457B] transition-colors">Halı Yıkama</a>
                <a href="/koltuk-yikama" class="hover:text-[#E0457B] transition-colors">Koltuk Yıkama</a>
                <a href="/yorgan-battaniye-yikama" class="hover:text-[#E0457B] transition-colors">Yorgan & Battaniye Yıkama</a>
                <a href="/yatak-yikama" class="hover:text-[#E0457B] transition-colors">Yatak Yıkama</a>
                <a href="/perde-yikama" class="hover:text-[#E0457B] transition-colors">Perde Yıkama</a>
                <a href="/ucretsiz-servis" class="hover:text-[#E0457B] transition-colors">Ücretsiz Servis</a>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto mt-8 pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-[10px] text-white/25 uppercase tracking-widest">
        <span>Designed with Precision</span>
        <div class="flex gap-6">
            <a class="hover:text-[#E0457B] transition-colors" href="/gizlilik-politikasi">Gizlilik Politikası</a>
            <a class="hover:text-[#E0457B] transition-colors" href="/kvkk">KVKK Aydınlatma</a>
            <a class="hover:text-[#E0457B] transition-colors" href="/kullanim-sartlari">Kullanım Şartları</a>
        </div>
    </div>
    <div class="max-w-7xl mx-auto mt-6 text-center">
        <a href="https://ankarayazilim.tr" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 mt-6 text-[12px] text-white/70 hover:text-[#E0457B] transition-colors duration-300">Tasarım: ankarayazilim.tr</a>
    </div>
</footer>

<!-- Instagram Button -->
<a href="https://www.instagram.com/hayati_bank" target="_blank" rel="noopener noreferrer" id="instagramButton" title="Instagram" class="fixed right-6 md:right-8 w-12 h-12 rounded-full text-white hover:scale-110 transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group" style="background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);box-shadow:0 0 24px rgba(214,36,159,0.45);">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
    <div class="absolute right-14 text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm" style="background:linear-gradient(135deg,#d6249f,#285AEB)">Instagram</div>
</a>
<!-- Call Button -->
<?php if ($phoneRaw): ?>
<a href="tel:<?= htmlspecialchars($phoneRaw) ?>" style="background:linear-gradient(135deg,#5B2C87,#2E1548);box-shadow:0 0 24px rgba(91,44,135,0.45);" class="fixed right-6 md:right-8 text-white w-12 h-12 rounded-full hover:scale-110 transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group" id="callButton">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
    <div class="absolute right-14 text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm" style="background:#2E1548">Hemen Ara</div>
</a>
<?php endif; ?>
<!-- WhatsApp Button -->
<?php if ($whatsapp): ?>
<div class="fixed right-6 md:right-8 bg-gradient-to-br from-[#25D366] to-[#128C7E] text-white w-12 h-12 rounded-full shadow-[0_0_24px_rgba(37,211,102,0.4)] hover:scale-110 hover:shadow-[0_0_32px_rgba(37,211,102,0.5)] transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group wa-bounce" id="waButton" onclick="window.open('https://wa.me/<?= htmlspecialchars($whatsapp) ?>','_blank')">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.654-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    <div class="absolute right-14 bg-[#128C7E] text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm">WhatsApp</div>
</div>
<?php endif; ?>
<!-- Scroll to Top -->
<button id="scrollTopBtn" class="fixed bottom-6 left-6 md:bottom-8 md:left-8 bg-[#5B2C87] text-white w-12 h-12 rounded-full shadow-lg shadow-[#5B2C87]/20 hover:scale-105 hover:shadow-[#5B2C87]/40 transition-all duration-500 ease-out z-[100] flex items-center justify-center" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Yukarı çık">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor"><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8z"/></svg>
</button>

<script src="/js/main.js" defer></script>
</body>
</html>
