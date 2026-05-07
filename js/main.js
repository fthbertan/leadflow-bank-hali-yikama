// ═══════════════════════════════════════
// LeadFlow — main.js
// Tüm site JavaScript'i
// ═══════════════════════════════════════

// Galeri buton CSS fix — JS'den enjekte (SW cache'den bağımsız çalışır)
(function(){
  var s = document.createElement('style');
  s.textContent =
    '#galleryDesktopGrid > a[href="/galeri"]{transition:transform .5s,box-shadow .5s,background .5s}' +
    '#galleryDesktopGrid > a[href="/galeri"]:hover{transform:scale(1.03);box-shadow:0 25px 50px -12px rgba(26,82,118,.4);background:linear-gradient(to top left,#5B2C87,#8E5CC0)!important}' +
    '#galleryDesktopGrid > a[href="/galeri"]:hover svg:first-child{transform:scale(1.15)}' +
    '#galleryDesktopGrid > a[href="/galeri"]:hover svg:last-child{transform:translateX(6px);color:#fff}' +
    '#galleryDesktopGrid > a[href="/galeri"] svg{transition:transform .5s,color .5s}' +
    '@media(max-width:1023px){#galleryDesktopGrid>a[href="/galeri"]{aspect-ratio:auto!important;flex-direction:row!important;padding:1.25rem 1.5rem!important;gap:.75rem!important}}';
  document.head.appendChild(s);
})();

// SVG ikon mapping (Material Symbols yerine)
var SVG_ICONS = {
  cleaning_services: 'M16 11h-1V3c0-.55-.45-1-1-1h-4c-.55 0-1 .45-1 1v8H8c-2.76 0-5 2.24-5 5v7h18v-7c0-2.76-2.24-5-5-5z',
  dry_cleaning: 'M19.56 11.36L13 8.44V7c0-.55-.45-1-1-1s-1 .45-1 1v1.44l-6.56 2.92c-.88.39-.88 1.63 0 2.02L11 16.36V19c0 .55.45 1 1 1s1-.45 1-1v-2.64l6.56-2.92c.88-.39.88-1.63 0-2.08zM12 14.3l-4.74-2.12L12 10.06l4.74 2.12L12 14.3z',
  local_shipping: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z',
  local_laundry_service: 'M9.17 16.83c1.56 1.56 4.1 1.56 5.66 0 1.56-1.56 1.56-4.1 0-5.66l-5.66 5.66zM18 2.01L6 2c-1.11 0-2 .89-2 2v16c0 1.11.89 2 2 2h12c1.11 0 2-.89 2-2V4c0-1.11-.89-1.99-2-1.99zM10 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM7 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm5 16c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z',
  home_repair_service: 'M18 16h-2v-1H8v1H6v-1H2v5h20v-5h-4v1zm2-8h-3V6c0-1.1-.9-2-2-2H9c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v4h4v-2h2v2h8v-2h2v2h4v-4c0-1.1-.9-2-2-2zm-5 0H9V6h6v2z',
  checkroom: 'M21.6 18.2L13 11.75v-.91c1.65-.49 2.8-2.17 2.43-4.05-.26-1.31-1.3-2.4-2.61-2.7C10.54 3.57 8.5 5.3 8.5 7.5h2c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5c0 .84-.69 1.52-1.53 1.5-.54-.01-.97.45-.97.99v1.76L2.4 18.2c-.77.58-.36 1.8.6 1.8h18c.96 0 1.37-1.22.6-1.8zM6 18l6-4.5 6 4.5H6z',
  weekend: 'M21 9V7c0-1.65-1.35-3-3-3H6C4.35 4 3 5.35 3 7v2c-1.65 0-3 1.35-3 3v5c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2v-5c0-1.65-1.35-3-3-3zm-1 0c-1.65 0-3 1.35-3 3v1H7v-1c0-1.65-1.35-3-3-3V7c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v2z',
  bed: 'M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z',
  auto_awesome: 'M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z',
  wash: 'M13.36 4C10.89 4 8.8 5.79 8.42 8.21L7 18h12l-1.42-9.79C17.2 5.79 15.11 4 12.64 4h.72zM18 20H6c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2z',
  water_drop: 'M12 2c-5.33 4.55-8 8.48-8 11.8 0 4.98 3.8 8.2 8 8.2s8-3.22 8-8.2c0-3.32-2.67-7.25-8-11.8zm0 18c-3.35 0-6-2.57-6-6.2 0-2.34 1.95-5.44 6-9.14 4.05 3.7 6 6.79 6 9.14 0 3.63-2.65 6.2-6 6.2z',
  home: 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',
  search: 'M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z',
  eco: 'M6.05 8.05c-2.73 2.73-2.73 7.17 0 9.9C7.42 19.32 9.21 20 11 20s3.58-.68 4.95-2.05C19.43 14.47 20 4 20 4S9.53 4.57 6.05 8.05zm8.49 8.49c-.95.94-2.2 1.46-3.54 1.46-.89 0-1.73-.25-2.48-.68.92-2.88 4.02-6.03 6.93-7.05-.82 2.72-2.2 4.91-2.91 6.27z',
  verified: 'M23 12l-2.44-2.79.34-3.69-3.61-.82-1.89-3.2L12 2.96 8.6 1.5 6.71 4.69 3.1 5.5l.34 3.7L1 12l2.44 2.79-.34 3.7 3.61.82L8.6 22.5l3.4-1.47 3.4 1.46 1.89-3.19 3.61-.82-.34-3.69L23 12zm-12.91 4.72l-3.8-3.8 1.48-1.48 2.32 2.33 5.85-5.87 1.48 1.48-7.33 7.34z',
  workspace_premium: 'M9.68 13.69L12 11.93l2.32 1.76-.88-2.85L15.76 9h-2.93L12 6.19 11.17 9H8.24l2.32 1.84-.88 2.85zM20 10c0-4.42-3.58-8-8-8s-8 3.58-8 8c0 2.03.76 3.87 2 5.28V23l6-2 6 2v-7.72c1.24-1.41 2-3.25 2-5.28zm-8-6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6 2.69-6 6-6z',
  diamond: 'M19 3H5L2 9l10 12L22 9l-3-6zm-1.18 5h-3.09l1.57-3.14L17.82 8zm-8.36 0l1.77-3.54L13.01 8H9.46zM11 10l-2 5.46L5.82 10H11zm2 0h5.18L14 15.46 13 10zM6.18 8L4.82 5.68 6.27 8h-.09z',
  swipe: 'M19.75 16.25c0 .06-.01.13-.02.19l-.82 4.44c-.12.64-.66 1.12-1.32 1.12H11c-.45 0-.84-.26-1.03-.63l-2.87-5.78c-.12-.25-.04-.55.19-.72.16-.12.37-.14.56-.06l3.15 1.27V6.5c0-.55.45-1 1-1s1 .45 1 1V13h.91c.31 0 .62.07.89.21l4.09 2.04c.52.26.85.78.85 1.35v.65z',
  texture: 'M19.51 3.08L3.08 19.51c.09.34.27.65.51.9.25.24.56.42.9.51L20.93 4.49c-.19-.69-.73-1.23-1.42-1.41zM11.88 3L3 11.88v2.83L14.71 3h-2.83zM5 3c-1.1 0-2 .9-2 2v2l4-4H5zm14 18c.55 0 1.05-.22 1.41-.59.37-.36.59-.86.59-1.41v-2l-4 4h2zm-9.71 0h2.83L21 12.12V9.29L9.29 21z',
  factory: 'M22 10V2H2v20h20V10zM10 2h4v4l-2 2-2-2V2zM4 20V4h4v4l2 2 2-2V4h4v4l-4 4v8H4z',
  calendar_today: 'M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z',
  schedule: 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z',
  arrow_forward: 'M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z'
};
function svgIcon(name, cls, size) {
  var p = SVG_ICONS[name] || SVG_ICONS['cleaning_services'];
  var s = size ? 'width:'+size+';height:'+size+';' : 'width:1em;height:1em;';
  return '<svg class="'+(cls||'')+'" style="'+s+'vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="'+p+'"/></svg>';
}

// URL'den UTM ve tracking parametrelerini temizle
(function(){
    var url = new URL(window.location.href);
    var dirty = false;
    ['utm_source','utm_medium','utm_campaign','utm_content','utm_term','fbclid','gclid','igshid'].forEach(function(p){
        if(url.searchParams.has(p)){ url.searchParams.delete(p); dirty=true; }
    });
    if(dirty) history.replaceState(null,'',url.pathname + url.hash);
})();

// Repeating IntersectionObserver
var revealObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
        if (e.isIntersecting) { e.target.classList.add('visible'); }
        else { e.target.classList.remove('visible'); }
    });
}, { threshold: 0.15, rootMargin: '0px 0px -120px 0px' });
document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(function(el) { revealObserver.observe(el); });

// Process line fill animation
var processLine = document.querySelector('.process-line-fill');
if (processLine) {
    var processObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) { e.target.classList.add('animate'); }
        });
    }, { threshold: 0.3 });
    processObserver.observe(processLine);
}

// Navbar scroll behavior
var nav = document.getElementById('mainNav');
var topBar = document.getElementById('topBar');
if (nav && topBar) {
    var hasHero = !!document.getElementById('hero');
    function updateNav() {
        var topBarH = topBar.offsetHeight;
        if (window.scrollY > topBarH) {
            nav.style.top = '0';
            nav.style.background = 'linear-gradient(135deg, rgba(46,21,72,0.97), rgba(26,82,118,0.95))';
            nav.style.backdropFilter = 'blur(20px)';
            nav.style.borderBottom = '1px solid rgba(224,69,123,0.15)';
            nav.style.boxShadow = '0 4px 30px rgba(0,0,0,0.2)';
            nav.classList.remove('nav-hero');
            nav.classList.add('nav-scrolled');
        } else if (hasHero) {
            // Sadece hero olan sayfalarda (anasayfa) transparent yap
            nav.style.top = topBarH + 'px';
            nav.style.background = 'transparent';
            nav.style.backdropFilter = 'none';
            nav.style.borderBottom = 'none';
            nav.style.boxShadow = 'none';
            nav.classList.remove('nav-scrolled');
            nav.classList.add('nav-hero');
        } else {
            // Hero olmayan sayfalarda (SEO sayfaları) topbar altında kal
            nav.style.top = topBarH + 'px';
            nav.classList.remove('nav-hero');
            nav.classList.add('nav-scrolled');
        }
    }
    window.addEventListener('scroll', updateNav);
    window.addEventListener('resize', updateNav);
    updateNav();
}

// Hero — görselleri API'den yükle (admin panelden yönetilebilir)
fetch('/api/gallery.php?category=hero').then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
  if(!d || !d.images || !d.images.length) return;
  var heroSlides = document.querySelectorAll('.hero-slide');
  d.images.forEach(function(img, idx){
    if(idx < heroSlides.length){
      var imgEl = heroSlides[idx].querySelector('.slide-bg img');
      if(imgEl){
        imgEl.src = '/' + img.filename;
        imgEl.alt = img.alt_text || imgEl.alt;
      }
    }
  });
}).catch(function(){});

// Hero Slider
var slides = document.querySelectorAll('.hero-slide');
var bullets = document.querySelectorAll('.hero-bullet');
if (slides.length > 0) {
    var currentSlide = 0;
    var slideInterval;

    function goToSlide(index) {
        slides[currentSlide].classList.remove('active');
        if (bullets[currentSlide]) bullets[currentSlide].classList.remove('active');
        currentSlide = index;
        if (currentSlide >= slides.length) currentSlide = 0;
        if (currentSlide < 0) currentSlide = slides.length - 1;
        slides[currentSlide].classList.add('active');
        if (bullets[currentSlide]) bullets[currentSlide].classList.add('active');
    }

    function startSlider() {
        slideInterval = setInterval(function() { goToSlide(currentSlide + 1); }, 6000);
    }

    var heroRight = document.querySelector('.hero-arrow-right');
    var heroLeft = document.querySelector('.hero-arrow-left');
    if (heroRight) heroRight.addEventListener('click', function() {
        clearInterval(slideInterval);
        goToSlide(currentSlide + 1);
        startSlider();
    });
    if (heroLeft) heroLeft.addEventListener('click', function() {
        clearInterval(slideInterval);
        goToSlide(currentSlide - 1);
        startSlider();
    });
    bullets.forEach(function(b) {
        b.addEventListener('click', function() {
            clearInterval(slideInterval);
            goToSlide(parseInt(this.dataset.slide));
            startSlider();
        });
    });
    startSlider();

    // Touch Swipe for Hero Slider
    var touchStartX = 0;
    var touchEndX = 0;
    var heroEl = document.getElementById('hero');
    if (heroEl) {
        heroEl.addEventListener('touchstart', function(e) { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        heroEl.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            var diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                clearInterval(slideInterval);
                if (diff > 0) { goToSlide(currentSlide + 1); }
                else { goToSlide(currentSlide - 1); }
                startSlider();
            }
        });
    }
}

// Scroll to Top
var scrollTopBtn = document.getElementById('scrollTopBtn');
if (scrollTopBtn) {
    function updateScrollTop() {
        if (window.scrollY > 600) { scrollTopBtn.classList.add('visible'); }
        else { scrollTopBtn.classList.remove('visible'); }
    }
    window.addEventListener('scroll', updateScrollTop);
    updateScrollTop();
}

// Toast bildirim fonksiyonu
function _showToast(message, type) {
    var existing = document.getElementById('lfToast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.id = 'lfToast';
    var bg = type === 'success' ? '#5B2C87' : type === 'error' ? '#dc2626' : '#2563eb';
    var icon = type === 'success' ? '&#10003;' : type === 'error' ? '&#10007;' : '&#8505;';
    toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);z-index:9999;background:' + bg + ';color:#fff;padding:14px 24px;border-radius:12px;font-size:14px;font-weight:500;box-shadow:0 10px 40px rgba(0,0,0,.2);display:flex;align-items:center;gap:10px;opacity:0;transition:opacity .3s,transform .3s;max-width:90vw;';
    toast.innerHTML = '<span style="font-size:18px;">' + icon + '</span><span>' + message + '</span>';
    document.body.appendChild(toast);
    requestAnimationFrame(function() { toast.style.opacity = '1'; toast.style.transform = 'translateX(-50%) translateY(0)'; });
    setTimeout(function() { toast.style.opacity = '0'; toast.style.transform = 'translateX(-50%) translateY(20px)'; setTimeout(function() { toast.remove(); }, 300); }, 4000);
}
function _isValidTRPhone(phone) {
    var digits = phone.replace(/\D/g, '');
    return /^(0?5\d{9}|905\d{9})$/.test(digits);
}

// Form → Sunucuya kaydet + WhatsApp
var appointmentForm = document.getElementById('appointmentForm');
if (appointmentForm) {
    appointmentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        var name = (fd.get('name') || '').trim();
        var phone = (fd.get('phone') || '').trim();
        var service = fd.get('service') || '';
        var date = fd.get('date') || '';
        var time = fd.get('time') || '';
        var notes = (fd.get('notes') || '').trim();

        // Honeypot kontrolü
        if (fd.get('website')) return;

        // Validasyon
        if (!name) { _showToast('Lütfen adınızı girin.', 'error'); return; }
        if (!phone) { _showToast('Lütfen telefon numaranızı girin.', 'error'); return; }
        if (!_isValidTRPhone(phone)) { _showToast('Geçerli bir telefon numarası girin (05XX XXX XX XX).', 'error'); return; }

        // Turnstile token
        var turnstileToken = '';
        var turnstileInput = form.querySelector('[name="cf-turnstile-response"]');
        if (turnstileInput) turnstileToken = turnstileInput.value;
        if (typeof turnstile !== 'undefined' && !turnstileToken) {
            _showToast('Lütfen güvenlik doğrulamasını bekleyin.', 'error');
            return;
        }

        // Loading state
        var submitBtn = form.querySelector('[type="submit"]');
        var originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Gönderiliyor...';

        // Sunucuya kaydet
        fetch('/api/messages.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: name, phone: phone, service: service,
                date: date, time: time, notes: notes,
                'cf-turnstile-response': turnstileToken
            })
        }).then(function(r) { return r.json().then(function(d) { return {ok: r.ok, data: d}; }); })
        .then(function(res) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            if (!res.ok) {
                _showToast(res.data.error || 'Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                if (typeof turnstile !== 'undefined') turnstile.reset();
                return;
            }
            _showToast('Talebiniz başarıyla gönderildi!', 'success');
            form.reset();
            if (typeof turnstile !== 'undefined') turnstile.reset();

            // WhatsApp'i ac (1.5sn sonra)
            setTimeout(function() {
                var msg = 'Merhaba! Ücretsiz keşif talebi göndermek istiyorum.\nAd: ' + name + '\nTelefon: ' + phone + '\nHizmet: ' + service;
                if (date) msg += '\nTarih: ' + date;
                if (time) msg += '\nSaat: ' + time;
                if (notes) msg += '\nNot: ' + notes;
                var waNum = window._waNumber || '905456876161';
                window.open('https://wa.me/' + waNum + '?text=' + encodeURIComponent(msg), '_blank');
            }, 1500);
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            _showToast('Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
            if (typeof turnstile !== 'undefined') turnstile.reset();
        });
    });
}

// Scroll Spy (grouped links)
var navLinks = document.querySelectorAll('.nav-link');
var sectionMap = {};
navLinks.forEach(function(link) {
    var href = link.getAttribute('href');
    if (href && href.startsWith('#')) {
        var sec = document.getElementById(href.substring(1));
        if (sec) {
            if (!sectionMap[href]) sectionMap[href] = { el: sec, links: [] };
            sectionMap[href].links.push(link);
        }
    }
});
var spySections = Object.values(sectionMap);
function updateActiveNav() {
    var scrollY = window.scrollY + 150;
    var active = null;
    spySections.forEach(function(s) { if (s.el.offsetTop <= scrollY) active = s; });
    navLinks.forEach(function(link) { link.classList.remove('active'); });
    if (active) { active.links.forEach(function(l) { l.classList.add('active'); }); }
}
window.addEventListener('scroll', updateActiveNav);
updateActiveNav();

// Mobile Menu
var mobileMenuBtn = document.getElementById('mobileMenuBtn');
var mobileMenu = document.getElementById('mobileMenu');
if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', function() {
        mobileMenu.classList.remove('hidden');
        mobileMenu.classList.toggle('open');
        mobileMenuBtn.classList.toggle('active');
        if (!mobileMenu.classList.contains('open')) {
            setTimeout(function(){ mobileMenu.classList.add('hidden'); }, 350);
        }
    });
    mobileMenu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            mobileMenu.classList.remove('open');
            mobileMenuBtn.classList.remove('active');
            setTimeout(function(){ mobileMenu.classList.add('hidden'); }, 350);
        });
    });
}

// ═══════════════════════════════════════
// Dynamic Loader — API'den güncel veriyi yükleyip DOM'u günceller
// ═══════════════════════════════════════
(function(){
  fetch('/api/settings.php').then(function(r){ return r.ok ? r.json() : null; }).then(function(s){
    if(!s || !Object.keys(s).length) return;
    // CSS custom property renkleri settings.json'dan uygula
    var r = document.documentElement;
    if(s.color_primary)       r.style.setProperty('--cp',  s.color_primary);
    if(s.color_primary_light) r.style.setProperty('--cpl', s.color_primary_light);
    if(s.color_accent)        r.style.setProperty('--ca',  s.color_accent);
    if(s.color_accent_dim)    r.style.setProperty('--cad', s.color_accent_dim);
    if(s.color_dark)          r.style.setProperty('--cd',  s.color_dark);
    if(s.color_surface)       r.style.setProperty('--cs',  s.color_surface);
    // Text content güncelle
    document.querySelectorAll('[data-kv]').forEach(function(el){
      var k = el.getAttribute('data-kv');
      if(s[k]) el.textContent = s[k];
    });
    // Href güncelle (tel:, mailto:)
    document.querySelectorAll('[data-kv-href]').forEach(function(el){
      var k = el.getAttribute('data-kv-href');
      var prefix = el.getAttribute('data-kv-href-prefix') || '';
      if(s[k]) el.href = prefix + s[k];
    });
    // WhatsApp güncelle (fallback: whatsapp_number → phone_raw → phone2_raw)
    var _waN = s.whatsapp_number || s.phone_raw || s.phone2_raw || '';
    if(_waN) window._waNumber = _waN;
    document.querySelectorAll('[data-kv-wa]').forEach(function(el){
      if(_waN){
        var waUrl = 'https://wa.me/' + _waN + '?text=' + encodeURIComponent('Merhaba! Web sitenizden ulaşıyorum. Hizmetleriniz hakkında bilgi almak istiyorum.');
        if(el.tagName === 'A') el.href = waUrl;
        el.setAttribute('onclick', "window.open('" + waUrl + "','_blank'); return false;");
      }
    });
    // Sosyal medya güncelle (footer)
    var socialContainer = document.getElementById('kvSocialLinks');
    if(socialContainer){
      var html = '';
      if(s.instagram){
        var ig = s.instagram.trim();
        if(ig.indexOf('http') !== 0) ig = 'https://www.instagram.com/' + ig.replace('@','');
        html += '<a href="' + ig + '" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:opacity-80 transition-all" title="Instagram"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>';
      }
      if(s.facebook){
        var fb = s.facebook.trim();
        if(fb.indexOf('http') !== 0) fb = 'https://facebook.com/' + fb;
        html += '<a href="' + fb + '" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:opacity-80 transition-all" title="Facebook"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>';
      }
      if(s.tiktok){
        var tt = s.tiktok.trim();
        if(tt.indexOf('http') !== 0) tt = 'https://www.tiktok.com/@' + tt.replace('@','');
        html += '<a href="' + tt + '" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:opacity-80 transition-all" title="TikTok"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="white"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.52a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.43v-7.15a8.16 8.16 0 005.58 2.09V11.1a4.84 4.84 0 01-3.58-1.58V6.69h3.58z"/></svg></a>';
      }
      if(html) socialContainer.innerHTML = html;
    }
    // Sosyal medya güncelle (top bar)
    var topBarSocial = document.getElementById('kvTopBarSocial');
    if(topBarSocial){
      var tbHtml = '';
      if(s.instagram){
        var igTop = s.instagram.trim();
        if(igTop.indexOf('http') !== 0) igTop = 'https://www.instagram.com/' + igTop.replace('@','');
        tbHtml += '<a href="' + igTop + '" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="Instagram"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#E4405F"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>';
      }
      if(s.facebook){
        var fbTop = s.facebook.trim();
        if(fbTop.indexOf('http') !== 0) fbTop = 'https://facebook.com/' + fbTop;
        tbHtml += '<a href="' + fbTop + '" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="Facebook"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>';
      }
      if(s.tiktok){
        var ttTop = s.tiktok.trim();
        if(ttTop.indexOf('http') !== 0) ttTop = 'https://www.tiktok.com/@' + ttTop.replace('@','');
        tbHtml += '<a href="' + ttTop + '" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="TikTok"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="white"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.52a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.43v-7.15a8.16 8.16 0 005.58 2.09V11.1a4.84 4.84 0 01-3.58-1.58V6.69h3.58z"/></svg></a>';
      }
      if(s.youtube){
        var yt = s.youtube.trim();
        if(yt.indexOf('http') !== 0) yt = 'https://www.youtube.com/@' + yt.replace('@','');
        tbHtml += '<a href="' + yt + '" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity" title="YouTube"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="#FF0000"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>';
      }
      if(tbHtml) topBarSocial.innerHTML = tbHtml;
    }
  }).catch(function(){});

  // Hizmetler — API'den dinamik yükleme
  fetch('/api/services.php').then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
    if(!d || !d.services || !d.services.length) return;
    var container = document.getElementById('kvServices');
    if(!container) return;
    var html = '';
    d.services.forEach(function(svc){
      var priceHtml = svc.price ? '<span class="text-primary font-medium">' + svc.price + '</span>' : '';
      var visualHtml = '';
      if (svc.image) {
        visualHtml = '<div class="w-full h-48 rounded-xl overflow-hidden mb-8">'
          + '<img src="/' + svc.image + '" alt="' + (svc.name || '') + '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" width="400" height="192"/></div>';
      } else {
        visualHtml = '<div class="w-14 h-14 bg-surface-container-low rounded-full flex items-center justify-center text-primary mb-8 group-hover:bg-primary group-hover:text-on-primary transition-colors duration-500">'
          + svgIcon(svc.icon || 'cleaning_services', 'text-3xl') + '</div>';
      }
      // Hizmet slug'ı oluştur
      var svcSlug = (svc.name || '').toLowerCase()
        .replace(/ı/g,'i').replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ö/g,'o').replace(/ç/g,'c')
        .replace(/&/g,'').replace(/\s+/g,'-').replace(/[^a-z0-9-]/g,'').replace(/-+/g,'-').replace(/^-|-$/g,'');
      var svcLink = '/' + svcSlug;

      html += '<a href="' + svcLink + '" class="group bg-surface-container-lowest rounded-xl border border-transparent transition-all duration-500 hover:-translate-y-2 hover:shadow-xl flex flex-col" style="overflow:hidden;text-decoration:none;color:inherit;cursor:pointer" onmouseenter="this.style.borderColor=\'#E0457B\';this.style.boxShadow=\'0 20px 40px rgba(224,69,123,0.15)\'" onmouseleave="this.style.borderColor=\'transparent\';this.style.boxShadow=\'none\'">'
        + '<div class="p-6 sm:p-10 flex flex-col flex-1">'
        + visualHtml
        + '<h3 class="font-headline text-2xl mb-4">' + svc.name + '</h3>'
        + '<p class="text-on-surface-variant mb-6 text-sm leading-relaxed">' + (svc.desc || '') + '</p>'
        + '</div>'
        + '<div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;background:linear-gradient(135deg,rgba(26,82,118,0.03),rgba(46,134,193,0.06));border-top:1px solid rgba(26,82,118,0.08);transition:background 0.3s ease">'
        + (priceHtml || '<span></span>')
        + '<span style="font-size:0.8rem;font-weight:600;color:#5B2C87;display:flex;align-items:center;gap:0.35rem;letter-spacing:0.02em">Detaylar <svg style="width:1.1em;height:1.1em;transition:transform 0.3s ease" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg></span>'
        + '</div></a>';
    });
    container.innerHTML = html;
  }).catch(function(){});

  function esc(s){ return (s||'').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

  // Blog — Anasayfa öne çıkan bloglar (API'den)
  fetch('/api/blogs.php?featured=1').then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
    if(!d || !d.blogs || !d.blogs.length) return;
    var container = document.getElementById('blogGrid');
    if(!container) return;
    var months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    var html = '';
    d.blogs.forEach(function(blog, idx){
      var parts = (blog.date || '').split('-');
      var dateStr = parts.length === 3 ? parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0] : '';
      var coverHtml = blog.cover_image
        ? '<img src="' + blog.cover_image + '" alt="' + esc(blog.title) + '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"/>'
        : '<svg class="text-on-surface-variant/10 group-hover:text-primary/20 transition-all duration-700" style="width:80px;height:80px" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
      html += '<a href="/blog/' + blog.slug + '" class="group bg-surface-container rounded-2xl border border-outline-variant/10 overflow-hidden card-glow block reveal stagger-' + (idx+1) + '">'
        + '<div class="h-48 bg-gradient-to-br from-primary/10 via-surface-container-high to-secondary/10 flex items-center justify-center relative overflow-hidden">'
        + coverHtml
        + (blog.category ? '<div class="absolute top-4 left-4"><span class="bg-primary-container/70 text-primary px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wider">' + esc(blog.category) + '</span></div>' : '')
        + '</div>'
        + '<div class="p-6">'
        + '<div class="flex items-center gap-3 text-on-surface-variant text-xs mb-3">'
        + (dateStr ? '<span class="flex items-center gap-1">' + svgIcon('calendar_today','text-xs') + ' ' + dateStr + '</span>' : '')
        + '<span class="flex items-center gap-1">' + svgIcon('schedule','text-xs') + ' ' + (blog.read_time || '5') + ' dk</span>'
        + '</div>'
        + '<h3 class="font-headline text-lg text-on-background mb-3 group-hover:text-primary transition-colors duration-300 line-clamp-2">' + esc(blog.title) + '</h3>'
        + '<p class="text-on-surface-variant text-sm leading-relaxed line-clamp-3 mb-4">' + esc(blog.summary || '') + '</p>'
        + '<span class="text-primary text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all duration-300">Devamını Oku ' + svgIcon('arrow_forward','text-sm') + '</span>'
        + '</div></a>';
    });
    container.innerHTML = html;
    container.querySelectorAll('.reveal').forEach(function(el){ revealObserver.observe(el); });
  }).catch(function(){});

  // Yorumlar — API'den dinamik yükleme
  fetch('/api/testimonials.php').then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
    if(!d || !d.testimonials || !d.testimonials.length) return;
    var container = document.getElementById('kvTestimonials');
    if(!container) return;
    var html = '';
    d.testimonials.forEach(function(t){
      var initial = (t.name || '?')[0].toUpperCase();
      html += '<div class="bg-surface-container-lowest p-8 sm:p-10 rounded-xl border border-outline-variant/10 hover:border-outline-variant/30 transition-colors duration-500">'
        + '<div class="flex items-center gap-1 mb-6">'
        + '<svg class="text-[#E0457B] text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg><svg class="text-[#E0457B] text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg><svg class="text-[#E0457B] text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg><svg class="text-[#E0457B] text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg><svg class="text-[#E0457B] text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
        + '</div>'
        + '<p class="text-on-surface-variant text-sm leading-relaxed mb-8 italic">"' + t.text + '"</p>'
        + '<div class="flex items-center gap-4 pt-6 border-t border-outline-variant/10">'
        + '<div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-on-primary font-bold text-sm">' + initial + '</div>'
        + '<div><p class="font-semibold text-on-background text-sm">' + t.name + '</p>'
        + '<p class="text-xs text-on-surface-variant">' + (t.role || '') + '</p></div>'
        + '</div></div>';
    });
    container.innerHTML = html;
  }).catch(function(){});

  // Galeri — API'den dinamik yükleme (sadece gallery kategorisi, yoksa statikler kalır)
  var GALLERY_PREVIEW_COUNT = 8;
  fetch('/api/gallery.php').then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
    if(!d || !d.images) return;
    var galleryImgs = d.images.filter(function(img){ return img.category === 'gallery'; });
    if(!galleryImgs.length) return;
    var totalCount = galleryImgs.length;
    var imgs = galleryImgs.slice(0, GALLERY_PREVIEW_COUNT);
    function esc(s){ return (s||'').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
    // Desktop grid — 8 images + button cell (3x3)
    var desktopGrid = document.getElementById('galleryDesktopGrid');
    if(desktopGrid){
      var html = '';
      imgs.forEach(function(img){
        var alt = img.alt_text || 'Galeri';
        html += '<div class="aspect-[4/3] rounded-2xl overflow-hidden relative group cursor-pointer reveal">' +
          '<img src="/' + img.filename + '" alt="' + esc(alt) + '" title="' + esc(alt) + '" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out" loading="lazy" onerror="this.style.display=\'none\'">' +
          '<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>' +
          '<div class="absolute inset-x-0 bottom-0 p-5 translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500"><span class="text-white text-sm font-medium">' + esc(alt) + '</span></div>' +
          '</div>';
      });
      // 9. hücre: buton
      html += '<a href="/galeri" class="aspect-[4/3] rounded-2xl flex flex-col items-center justify-center gap-3 shadow-lg cursor-pointer reveal" style="background:linear-gradient(to bottom right,#5B2C87,#8E5CC0)">' +
        '<svg class="text-white/90 text-3xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>' +
        '<span class="text-white font-semibold text-sm tracking-wide">Tümünü Gör</span>' +
        '<svg class="text-white/60 text-lg" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>' +
        '</a>';
      desktopGrid.innerHTML = html;
      desktopGrid.querySelectorAll('.reveal').forEach(function(el){ revealObserver.observe(el); });
    }
    // Mobile scroll
    var mobileScroll = document.getElementById('galleryMobileScroll');
    if(mobileScroll){
      var mhtml = '';
      imgs.forEach(function(img){
        var alt = img.alt_text || 'Galeri';
        mhtml += '<div class="snap-center flex-shrink-0 w-[280px] aspect-[4/3] rounded-2xl overflow-hidden relative">' +
          '<img src="/' + img.filename + '" alt="' + esc(alt) + '" title="' + esc(alt) + '" class="absolute inset-0 w-full h-full object-cover" loading="lazy" onerror="this.style.display=\'none\'">' +
          '<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-3"><span class="text-white text-xs font-medium">' + esc(alt) + '</span></div>' +
          '</div>';
      });
      mobileScroll.innerHTML = mhtml;
    }
    // "Tümünü Gör" butonunu güncelle (toplam sayı)
    var viewAllBtn = document.getElementById('galleryViewAll');
    if(viewAllBtn && totalCount === 0){
      viewAllBtn.style.display = 'none';
    }
  }).catch(function(){});

  // WhatsApp sablonlari yukle
  fetch('/api/whatsapp_templates.php').then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
    if(!d || !d.templates || !d.templates.length) return;
    var list = document.getElementById('waPopupList');
    if(!list) return;
    var html = '';
    d.templates.forEach(function(t){
      html += '<button onclick="sendWaMessage(\'' + encodeURIComponent(t.message).replace(/'/g,"\\'") + '\')" class="w-full text-left px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors border border-gray-100">' +
        '<div class="font-medium text-sm text-gray-800">' + t.title + '</div>' +
        '<div class="text-xs text-gray-500 mt-0.5 truncate">' + t.message + '</div>' +
        '</button>';
    });
    list.innerHTML = html;
    window._waTemplatesLoaded = true;
  }).catch(function(){});
})();

// WhatsApp popup fonksiyonlari
function toggleWaPopup(){
  if(window._waTemplatesLoaded){
    var popup = document.getElementById('waPopup');
    popup.classList.toggle('hidden');
  } else {
    var num = window._waNumber || '905456876161';
    window.open('https://wa.me/' + num + '?text=' + encodeURIComponent('Merhaba! Web sitenizden ulaşıyorum. Hizmetleriniz hakkında bilgi almak istiyorum.'),'_blank');
  }
}
function sendWaMessage(encodedMsg){
  document.getElementById('waPopup').classList.add('hidden');
  var num = window._waNumber || '905456876161';
  window.open('https://wa.me/' + num + '?text=' + encodedMsg, '_blank');
}

// ═══════════════════════════════════════
// Çerez Banner
// ═══════════════════════════════════════
(function(){
    var banner = document.getElementById('cookieBanner');
    if(!banner) return;
    var consent = localStorage.getItem('cookie_consent');
    if(consent) return;
    setTimeout(function(){ banner.style.transform='translateY(0)'; }, 1500);
    document.getElementById('cookieAccept').addEventListener('click', function(){
        localStorage.setItem('cookie_consent', 'accepted');
        banner.style.transform='translateY(100%)';
        // Analytics'i yükle
        var GA_ID = '';
        if(GA_ID && GA_ID !== '' && GA_ID.indexOf('{{') === -1){
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            function gtag(){ dataLayer.push(arguments); }
            window.gtag = gtag;
            gtag('js', new Date());
            gtag('config', GA_ID);
        }
    });
    document.getElementById('cookieReject').addEventListener('click', function(){
        localStorage.setItem('cookie_consent', 'rejected');
        banner.style.transform='translateY(100%)';
    });
})();

// ═══════════════════════════════════════
// Service Worker Kaydı (PWA)
// ═══════════════════════════════════════
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/js/sw.js?v=11').catch(function(){});
    });
}
