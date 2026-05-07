<?php // LeadFlow — Floating Butonlar + WhatsApp Popup + Cookie Banner (Bank Halı Yıkama) ?>

<!-- WhatsApp Popup -->
<div id="waPopup" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('waPopup').classList.add('hidden')"></div>
    <div class="absolute bottom-24 right-4 left-4 sm:left-auto sm:right-6 md:right-8 sm:w-72 bg-white rounded-2xl shadow-2xl overflow-hidden" style="animation: slideIn 0.3s ease">
        <div class="bg-gradient-to-r from-[#25D366] to-[#128C7E] px-4 py-3 flex items-center justify-between">
            <span class="text-white font-bold text-sm">WhatsApp ile Yazın</span>
            <button onclick="document.getElementById('waPopup').classList.add('hidden')" class="text-white/80 hover:text-white"><svg style="width:1em;height:1em;vertical-align:middle" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
        </div>
        <div id="waPopupList" class="p-3 space-y-2 max-h-60 overflow-y-auto"></div>
    </div>
</div>

<!-- Instagram Button -->
<a href="https://www.instagram.com/hayati_bank" target="_blank" rel="noopener noreferrer" data-kv-href="instagram_url" id="instagramButton" title="Instagram" class="fixed right-6 md:right-8 w-12 h-12 rounded-full text-white hover:scale-110 transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group" style="bottom:8.5rem;background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);box-shadow:0 0 24px rgba(214,36,159,0.45);">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
    <div class="absolute right-14 text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm" style="background:linear-gradient(135deg,#d6249f,#285AEB)">Instagram</div>
</a>
<!-- Call Button -->
<a href="tel:05456876161" data-kv-href="phone_raw" data-kv-href-prefix="tel:" style="background:linear-gradient(135deg,#4CAF50,#2E7D32);box-shadow:0 0 24px rgba(76,175,80,0.45);" class="fixed bottom-20 right-6 md:bottom-22 md:right-8 text-white w-12 h-12 rounded-full hover:scale-110 transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group" id="callButton">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
    <div class="absolute right-14 text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm" style="background:#2E7D32">Hemen Ara</div>
</a>
<!-- WhatsApp Button -->
<a href="https://wa.me/905456876161?text=Merhaba%21%20Web%20sitenizden%20ula%C5%9F%C4%B1yorum.%20Hizmetleriniz%20hakk%C4%B1nda%20bilgi%20almak%20istiyorum." target="_blank" class="fixed bottom-6 right-6 md:bottom-8 md:right-8 bg-gradient-to-br from-[#25D366] to-[#128C7E] text-white w-12 h-12 rounded-full shadow-[0_0_24px_rgba(37,211,102,0.4)] hover:scale-110 hover:shadow-[0_0_32px_rgba(37,211,102,0.5)] transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group wa-bounce" id="waButton" data-kv-wa="whatsapp_number">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.654-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    <div class="absolute right-14 bg-[#128C7E] text-white px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-bold text-sm">WhatsApp</div>
</a>

<!-- Scroll to Top -->
<button id="scrollTopBtn" class="fixed bottom-6 left-6 md:bottom-8 md:left-8 bg-[#5B2C87] text-white w-12 h-12 rounded-full shadow-lg shadow-[#5B2C87]/20 hover:scale-105 hover:shadow-[#5B2C87]/40 transition-all duration-500 ease-out z-[100] flex items-center justify-center" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Yukarı çık">
    <svg class="text-2xl" style="width:1em;height:1em;vertical-align:middle;flex-shrink:0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8z"/></svg>
</button>

<!-- Çerez Banner -->
<div id="cookieBanner" style="position:fixed;bottom:0;left:0;right:0;z-index:2147483647;transform:translateY(100%);transition:transform .5s ease;pointer-events:none;">
    <div style="max-width:56rem;margin:0 auto;padding:1rem;pointer-events:auto;">
        <div style="background:#fff;border-radius:1rem;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);border:1px solid #e5e7eb;padding:1.25rem;display:flex;flex-wrap:wrap;align-items:center;gap:1rem;">
            <div style="flex:1;min-width:200px;font-size:.875rem;color:#374151;line-height:1.6;">
                Bu web sitesi deneyiminizi iyileştirmek için çerezler kullanmaktadır.
                <a href="/gizlilik-politikasi" style="text-decoration:underline;color:#5B2C87;">Gizlilik Politikası</a> ve
                <a href="/kvkk" style="text-decoration:underline;color:#5B2C87;">KVKK Aydınlatma Metni</a>'ni inceleyebilirsiniz.
            </div>
            <div style="display:flex;gap:.5rem;flex-shrink:0;">
                <button id="cookieReject" style="padding:.5rem 1rem;font-size:.875rem;font-weight:500;color:#4b5563;border:1px solid #d1d5db;border-radius:.5rem;background:#fff;cursor:pointer;">Reddet</button>
                <button id="cookieAccept" style="padding:.5rem 1rem;font-size:.875rem;font-weight:500;color:#fff;background:#5B2C87;border:none;border-radius:.5rem;cursor:pointer;">Kabul Et</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/main.js" defer></script>
