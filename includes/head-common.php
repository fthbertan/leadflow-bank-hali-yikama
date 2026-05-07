<?php // LeadFlow — Ortak Head Bileşenleri (otomatik üretildi) ?>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<meta name="theme-color" content="#5B2C87"/>
<link rel="manifest" href="/manifest.json"/>
<link rel="stylesheet" href="/css/style.css"/>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

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
:root {
  --lf-primary: #5B2C87;
  --lf-primary-rgb: 91, 44, 135;
  --lf-primary-light: #8E5CC0;
  --lf-primary-light-rgb: 142, 92, 192;
  --lf-accent: #E0457B;
  --lf-accent-rgb: 224, 69, 123;
  --lf-accent-dim: #C93A6A;
  --lf-accent-dim-rgb: 201, 58, 106;
  --lf-accent-dark: #A52E55;
  --lf-accent-dark-rgb: 165, 46, 85;
  --lf-dark: #2E1548;
  --lf-dark-rgb: 46, 21, 72;
  --lf-surface: #F5F0FF;
  --lf-surface-rgb: 245, 240, 255;
  --lf-text: #1a1a2e;
  --lf-text-muted: #4e4e4e;
  --lf-text-light: #666666;
  --lf-bg-section: #f5f5f7;
}
/* Ortak stiller */
:focus-visible{outline:2px solid var(--lf-accent);outline-offset:2px}
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--lf-surface)}
::-webkit-scrollbar-thumb{background:var(--lf-primary);border-radius:3px}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
.btn-shimmer{background:linear-gradient(110deg,var(--lf-accent-dim) 0%,var(--lf-accent) 25%,var(--lf-accent) 50%,var(--lf-accent) 75%,var(--lf-accent-dim) 100%);background-size:200% 100%;animation:shimmer 4s linear infinite}
.btn-cta{background:linear-gradient(110deg,var(--lf-accent-dark) 0%,var(--lf-accent) 25%,var(--lf-accent-dim) 50%,var(--lf-accent) 75%,var(--lf-accent-dark) 100%);background-size:200% 100%;animation:shimmer 4s linear infinite}
.btn-cta:hover{filter:brightness(1.1);transform:scale(1.05)}
/* Mobil menü — GPU-hızlandırmalı animasyon (transform+opacity) */
#mobileMenu{position:absolute;left:0;right:0;top:100%;opacity:0;transform:translateY(-8px);pointer-events:none;max-height:0;overflow:hidden;transition:opacity .25s ease,transform .25s ease,max-height .3s ease;z-index:50;will-change:opacity,transform}
#mobileMenu.open{opacity:1;transform:translateY(0);pointer-events:auto;max-height:85vh;overflow-y:auto}
#mobileMenu .nav-link{opacity:0;transform:translateY(-8px);transition:opacity .25s ease,transform .25s ease,color .2s ease}
#mobileMenu.open .nav-link{opacity:1;transform:translateY(0)}
#mobileMenu.open .nav-link:nth-child(1){transition-delay:.05s}
#mobileMenu.open .nav-link:nth-child(2){transition-delay:.1s}
#mobileMenu.open .nav-link:nth-child(3){transition-delay:.15s}
#mobileMenu.open .nav-link:nth-child(4){transition-delay:.2s}
#mobileMenu.open .nav-link:nth-child(5){transition-delay:.25s}
#mobileMenu.open .nav-link:nth-child(6){transition-delay:.3s}
#mobileMenu.open .nav-link:nth-child(7){transition-delay:.35s}
#mobileMenu.open .nav-link:nth-child(8){transition-delay:.4s}
#mobileMenuBtn .menu-icon{display:block;transition:opacity .2s ease,transform .3s ease}
#mobileMenuBtn .close-icon{display:none;transition:opacity .2s ease,transform .3s ease}
#mobileMenuBtn.active .menu-icon{display:none}
#mobileMenuBtn.active .close-icon{display:block;animation:spinIn .3s ease}
@keyframes spinIn{from{transform:rotate(-90deg) scale(.5);opacity:0}to{transform:rotate(0) scale(1);opacity:1}}
#callButton{bottom:calc(8.5rem + env(safe-area-inset-bottom, 0px))!important}
#waButton{bottom:calc(5rem + env(safe-area-inset-bottom, 0px))!important}
#instagramButton{bottom:calc(1.5rem + env(safe-area-inset-bottom, 0px))!important}
/* ScrollTop ok */
#scrollTopBtn{position:fixed;left:1.5rem;bottom:calc(1.5rem + env(safe-area-inset-bottom, 0px))!important;z-index:100;opacity:0;pointer-events:none;filter:drop-shadow(0 2px 8px rgba(var(--lf-accent-rgb),0.35));transition:opacity .5s,filter .3s,transform .3s;background:none;border:none;cursor:pointer}
#scrollTopBtn.visible{opacity:1!important;pointer-events:auto!important}
#scrollTopBtn:hover{filter:drop-shadow(0 0 14px rgba(var(--lf-accent-rgb),0.6));transform:scale(1.1)}
@media(min-width:768px){#callButton{bottom:calc(8rem + env(safe-area-inset-bottom, 0px))!important}#waButton{bottom:calc(5rem + env(safe-area-inset-bottom, 0px))!important}#instagramButton{bottom:calc(2rem + env(safe-area-inset-bottom, 0px))!important}#scrollTopBtn{left:2rem;bottom:calc(2rem + env(safe-area-inset-bottom, 0px))!important}}
/* Sticky rezervasyon butonu (mobil) */
#stickyReservation{position:fixed;bottom:1.25rem;left:50%;z-index:90;transform:translateX(-50%) translateY(120px);transition:all .3s ease-out;padding-bottom:env(safe-area-inset-bottom, 0px)}
@media(min-width:1024px){#stickyReservation{display:none!important}}
@keyframes waBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.wa-bounce{animation:waBounce 2s ease-in-out infinite}
.wa-bounce:hover{animation:none}
</style>
