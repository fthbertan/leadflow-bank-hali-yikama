<?php
// LeadFlow — Topbar + Navbar Include (Bank Halı Yıkama)
// Kullanim: Ana sayfa: include DIR/includes/navbar.php
//           Alt sayfa: $pageType = 'sub'; include DIR/includes/navbar.php
$isSub = isset($pageType) && $pageType === 'sub';
$navClass = $isSub ? 'sticky w-full z-50 transition-all duration-500 nav-scrolled' : 'fixed w-full z-50 transition-all duration-500 nav-hero';
$navStyle = $isSub ? '' : 'style="top:36px"';
$logoHref = $isSub ? '/' : '#';
$linkPrefix = $isSub ? '/' : '';
?>

<!-- Topbar -->
<div id="topBar" class="bg-[#251560] border-b border-white/10 py-2 text-xs z-[60] relative">
    <div class="flex justify-between items-center px-4 sm:px-6 lg:px-10 max-w-full">
        <div class="flex items-center gap-5 text-white/70">
            <a href="tel:05456876161" data-kv-href="phone_raw" data-kv-href-prefix="tel:" class="flex items-center gap-1.5 hover:text-white transition-colors">
                <svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                <span data-kv="phone">0 545 687 61 61</span>
            </a>
            <span class="hidden lg:flex items-center gap-1.5">
                <svg class="text-sm" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <span data-kv="working_hours_short">7/24 Hizmetinizdeyiz</span>
            </span>
        </div>
        <div class="flex items-center justify-center gap-3 text-white/70 sm:w-[160px]" id="kvTopBarSocial">
            <a href="https://www.instagram.com/hayati_bank?utm_source=qr&igsh=MXBkZHppeDhydGZzNg==" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="Instagram"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
            <a href="https://www.tiktok.com/@hayati_bank1?_r=1&_t=ZS-966kPmHot61" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="TikTok"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="white"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.52a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.43v-7.15a8.16 8.16 0 005.58 2.09V11.1a4.84 4.84 0 01-3.58-1.58V6.69h3.58z"/></svg></a>
        </div>
    </div>
</div>

<!-- Navbar -->
<nav id="mainNav" class="<?= $navClass ?>" <?= $navStyle ?> role="navigation" aria-label="Ana navigasyon">
    <div class="flex justify-between items-center px-4 sm:px-6 lg:px-10 py-4 max-w-full">
        <a href="<?= $logoHref ?>" class="group flex items-center gap-3 sm:gap-3">
            <div class="w-11 h-11 sm:w-10 sm:h-10 rounded-xl bg-[#E0457B] flex items-center justify-center shadow-lg shadow-[#E0457B]/30 group-hover:scale-110 transition-transform duration-300">
                <span class="font-bold text-white text-xl">B</span>
            </div>
            <span class="logo-text text-2xl sm:text-2xl md:text-3xl font-extrabold tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)] whitespace-nowrap" data-kv="business_name">Bank Halı Yıkama</span>
        </a>
        <div class="flex items-center gap-4">
            <div class="hidden lg:flex items-center nav-glass-pill">
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="<?= $linkPrefix ?>#hizmetlerimiz">Hizmetler</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="/fiyatlar">Fiyatlar</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="/randevu">Randevu</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="<?= $linkPrefix ?>#surecimiz">Süreç</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="<?= $linkPrefix ?>#hakkimizda">Hakkında</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="/galeri">Galeri</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="<?= $linkPrefix ?>#yorumlar">Yorumlar</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="/blog/">Blog</a>
                <a class="nav-link text-xs font-semibold uppercase tracking-[0.06em]" href="<?= $linkPrefix ?>#iletisim">İletişim</a>
            </div>
            <a class="hidden sm:inline-flex btn-cta text-white px-6 py-2.5 rounded-full font-semibold text-sm tracking-wide shadow-lg shadow-[#E0457B]/30 hover:scale-105 transition-transform duration-300" href="<?= $linkPrefix ?>#iletisim">Ücretsiz Keşif</a>
            <button id="mobileMenuBtn" class="lg:hidden p-2">
                <svg class="text-3xl menu-icon" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                <svg class="text-3xl close-icon" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
    </div>
    <div id="mobileMenu" class="lg:hidden w-full px-6 space-y-4 bg-[#251560]/95 backdrop-blur-xl border-t border-white/10">
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="<?= $linkPrefix ?>#hizmetlerimiz">Hizmetlerimiz</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/fiyatlar">Fiyatlarımız</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/randevu">Randevu Al</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="<?= $linkPrefix ?>#surecimiz">Sürecimiz</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="<?= $linkPrefix ?>#hakkimizda">Hakkımızda</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/galeri">Galeri</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="<?= $linkPrefix ?>#yorumlar">Yorumlar</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="/blog/">Blog</a>
        <a class="nav-link block text-sm font-semibold uppercase tracking-[0.15em] text-white hover:text-[#E0457B] py-2" href="<?= $linkPrefix ?>#iletisim">İletişim</a>
        <a class="block btn-cta text-white px-6 py-3 rounded-full font-semibold text-sm tracking-wide text-center sm:hidden" href="<?= $linkPrefix ?>#iletisim">Ücretsiz Keşif</a>
    </div>
</nav>
