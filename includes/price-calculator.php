<?php
// Fiyat Hesaplayıcı — includes/price-calculator.php
$calcWa = isset($whatsapp) ? $whatsapp : '905456876161';

$_calcServices = [];
try {
    if (function_exists('getDB')) {
        $_db = getDB();
        $_rows = $_db->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();
        $_items = [];
        try { $_items = $_db->query('SELECT * FROM service_items ORDER BY sort_order ASC, id ASC')->fetchAll(); } catch(Exception $e) {}
        foreach ($_rows as $_r) {
            $_sid = (int)$_r['id'];
            $_its = array_values(array_filter($_items, function($x) use($_sid){ return (int)$x['service_id'] === $_sid; }));
            $_calcServices[] = [
                'name'  => $_r['title'],
                'icon'  => $_r['icon'],
                'price' => $_r['price'],
                'items' => array_map(function($x){ return ['name'=>$x['name'],'price'=>$x['price'],'unit'=>$x['unit']]; }, $_its),
            ];
        }
    }
} catch (Exception $e) {}

if (empty($_calcServices)) {
    $_jsonPath = __DIR__ . '/../data/services.json';
    if (file_exists($_jsonPath)) {
        $_json = json_decode(file_get_contents($_jsonPath), true);
        foreach (($_json['services'] ?? []) as $_s) {
            $_calcServices[] = [
                'name'  => $_s['name']  ?? '',
                'icon'  => $_s['icon']  ?? 'cleaning_services',
                'price' => $_s['price'] ?? '',
                'items' => array_map(function($_it) {
                    return ['name' => $_it['name'] ?? '', 'price' => $_it['price'] ?? '', 'unit' => $_it['unit'] ?? ''];
                }, $_s['items'] ?? []),
            ];
        }
    }
}
?>
<style>
/* ── Price Calculator ── */
.prc-wrap { max-width:860px; margin:0 auto; }
.prc-card {
    background:#fff;
    border-radius:20px;
    border:1px solid #ede8ff;
    box-shadow:0 2px 24px rgba(109,49,196,.07);
    overflow:hidden;
}
.prc-card-header {
    padding:28px 32px 0;
    display:flex;
    align-items:center;
    gap:14px;
    border-bottom:1px solid #f3efff;
    padding-bottom:20px;
}
.prc-header-icon {
    width:44px; height:44px; border-radius:12px;
    background:linear-gradient(135deg,#6D31C4,#E0457B);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.prc-card-body { padding:28px 32px; }

/* Selects */
.prc-field { margin-bottom:20px; }
.prc-field:last-of-type { margin-bottom:0; }
.prc-label {
    display:block;
    font-size:.8rem;
    font-weight:700;
    color:#7c6a9e;
    margin-bottom:8px;
    letter-spacing:.01em;
}
.prc-sel {
    width:100%;
    padding:13px 40px 13px 16px;
    border:1.5px solid #e8e0ff;
    border-radius:12px;
    font-size:.95rem;
    font-weight:600;
    color:#251560;
    background:#faf8ff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='%236D31C4'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 14px center;
    appearance:none; -webkit-appearance:none;
    outline:none;
    cursor:pointer;
    transition:border-color .2s, box-shadow .2s;
}
.prc-sel:focus { border-color:#6D31C4; box-shadow:0 0 0 3px rgba(109,49,196,.1); }
.prc-sel:disabled { opacity:.45; cursor:not-allowed; }

/* Quantity */
.prc-qty-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.prc-slider {
    flex:1; min-width:120px;
    -webkit-appearance:none; appearance:none;
    height:6px; border-radius:999px;
    background:rgba(109,49,196,.15);
    outline:none; cursor:pointer;
}
.prc-slider::-webkit-slider-thumb {
    -webkit-appearance:none;
    width:20px; height:20px; border-radius:50%;
    background:#6D31C4;
    box-shadow:0 2px 8px rgba(109,49,196,.35);
    cursor:pointer;
}
.prc-slider::-moz-range-thumb { width:20px; height:20px; border-radius:50%; background:#6D31C4; border:none; cursor:pointer; }
.prc-qty-display {
    min-width:76px; text-align:center;
    font-size:1.1rem; font-weight:800;
    color:#251560;
    background:#f5f1ff;
    border:1.5px solid #e8e0ff;
    border-radius:10px;
    padding:9px 12px;
}
.prc-stepper { display:inline-flex; align-items:center; gap:0; border:1.5px solid #e8e0ff; border-radius:12px; overflow:hidden; }
.prc-step-btn {
    width:44px; height:44px;
    background:#faf8ff; border:none;
    font-size:1.25rem; font-weight:700; color:#6D31C4;
    cursor:pointer; transition:background .2s;
    display:flex; align-items:center; justify-content:center;
}
.prc-step-btn:hover { background:#ede8ff; }
.prc-step-val { min-width:52px; text-align:center; font-size:1.1rem; font-weight:800; color:#251560; background:#fff; }

/* Divider */
.prc-divider { height:1px; background:#f3efff; margin:24px 0; }

/* Result */
.prc-result {
    background:linear-gradient(120deg,#f5f0ff 0%,#fdf5ff 100%);
    border:1.5px solid #e8d8ff;
    border-radius:16px;
    padding:22px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
}
.prc-result-left {}
.prc-result-eyebrow {
    font-size:.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.1em; color:#9d8ac0; margin-bottom:4px;
}
.prc-result-price {
    font-size:2.4rem; font-weight:900;
    color:#251560; line-height:1;
    letter-spacing:-.02em;
}
.prc-result-note { font-size:.75rem; color:#9d8ac0; margin-top:5px; }
.prc-result-actions { display:flex; gap:10px; flex-wrap:wrap; }
.prc-btn-wa {
    display:inline-flex; align-items:center; gap:8px;
    background:#25D366; color:#fff;
    padding:12px 20px; border-radius:999px;
    font-weight:700; font-size:.83rem;
    text-decoration:none; white-space:nowrap;
    transition:all .22s;
    box-shadow:0 4px 14px rgba(37,211,102,.3);
}
.prc-btn-wa:hover { filter:brightness(1.08); transform:translateY(-1px); }
.prc-btn-rdv {
    display:inline-flex; align-items:center; gap:8px;
    background:#fff; color:#6D31C4;
    padding:12px 20px; border-radius:999px;
    font-weight:700; font-size:.83rem;
    text-decoration:none; white-space:nowrap;
    border:1.5px solid #d4c3f7;
    transition:all .22s;
}
.prc-btn-rdv:hover { background:#f5f0ff; border-color:#6D31C4; transform:translateY(-1px); }
.prc-empty { text-align:center; padding:20px 0 4px; color:#9d8ac0; font-size:.9rem; }
.prc-empty a { color:#E0457B; font-weight:700; text-decoration:none; }
.prc-hidden { display:none; }
@media(max-width:600px) {
    .prc-card-header, .prc-card-body { padding-left:20px; padding-right:20px; }
    .prc-result { flex-direction:column; align-items:flex-start; }
    .prc-result-price { font-size:2rem; }
}
</style>

<section id="fiyat-hesapla" class="py-20 sm:py-28" style="background:linear-gradient(180deg,#f0ebff 0%,#fdf8ff 100%)">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-10 reveal">
            <span class="inline-block text-[#E0457B] text-xs font-bold uppercase tracking-[0.25em] mb-4">Anlık Fiyat Hesabı</span>
            <h2 class="text-3xl sm:text-4xl font-black text-[#251560] tracking-tight mb-4">Ne kadar <span style="color:#E0457B">tutar?</span></h2>
            <p style="color:#7c6a9e;font-size:1rem;max-width:400px;margin:0 auto;line-height:1.7">Hizmet ve miktarı seçin, tahmini tutarı hemen görün. Sürpriz ücret yok.</p>
        </div>

        <div class="prc-wrap reveal">
            <div class="prc-card">

                <!-- Header -->
                <div class="prc-card-header">
                    <div class="prc-header-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    </div>
                    <div>
                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#9d8ac0;margin-bottom:2px">Ücretsiz hesaplama</div>
                        <div style="font-size:1rem;font-weight:800;color:#251560">Tahmini fiyatınızı öğrenin</div>
                    </div>
                    <div style="margin-left:auto;font-size:.75rem;color:#9d8ac0;text-align:right;line-height:1.5;display:none" id="prcLiveTag">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#25D366;margin-right:4px;vertical-align:middle"></span>Canlı hesaplama
                    </div>
                </div>

                <!-- Body -->
                <div class="prc-card-body">

                    <!-- Hizmet seçimi -->
                    <div class="prc-field">
                        <label class="prc-label" for="prcSvcSel">Hangi hizmet?</label>
                        <select class="prc-sel" id="prcSvcSel">
                            <option value="">— Seçiniz —</option>
                        </select>
                    </div>

                    <!-- Alt tür -->
                    <div class="prc-field prc-hidden" id="prcItemField">
                        <label class="prc-label" for="prcItemSel">Tür seçin</label>
                        <select class="prc-sel" id="prcItemSel">
                            <option value="">— Seçiniz —</option>
                        </select>
                    </div>

                    <!-- Miktar -->
                    <div class="prc-field prc-hidden" id="prcQtyField">
                        <label class="prc-label" id="prcQtyLabel">Miktar</label>
                        <!-- m² slider -->
                        <div id="prcSliderWrap" class="prc-hidden">
                            <div class="prc-qty-row">
                                <input type="range" class="prc-slider" id="prcSlider" min="1" max="300" value="20">
                                <div class="prc-qty-display" id="prcSliderVal">20 m²</div>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#9d8ac0;margin-top:6px"><span>1 m²</span><span>300 m²</span></div>
                        </div>
                        <!-- adet stepper -->
                        <div id="prcStepperWrap" class="prc-hidden">
                            <div class="prc-stepper">
                                <button class="prc-step-btn" id="prcMinus" type="button">−</button>
                                <span class="prc-step-val" id="prcStepVal">1</span>
                                <button class="prc-step-btn" id="prcPlus" type="button">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Keşif mesajı (items yoksa) -->
                    <div class="prc-empty prc-hidden" id="prcEmptyMsg">
                        Bu hizmet için fiyat, yerinde değerlendirme ile belirlenmektedir.
                        <div style="margin-top:8px"><a href="tel:05456876161">Ücretsiz keşif için arayın →</a></div>
                    </div>

                    <div class="prc-divider" id="prcDivider"></div>

                    <!-- Sonuç -->
                    <div class="prc-result" id="prcResult">
                        <div class="prc-result-left">
                            <div class="prc-result-eyebrow">Tahmini tutar</div>
                            <div class="prc-result-price" id="prcPrice">—</div>
                            <div class="prc-result-note" id="prcNote">Yukarıdan hizmet seçin, anında hesaplayalım.</div>
                        </div>
                        <div class="prc-result-actions prc-hidden" id="prcActions">
                            <a id="prcWaLink" href="#" target="_blank" rel="noopener" class="prc-btn-wa">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.654-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Teklif Al
                            </a>
                            <a href="/randevu" class="prc-btn-rdv">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                Randevu Al
                            </a>
                        </div>
                    </div>

                </div><!-- /prc-card-body -->
            </div><!-- /prc-card -->

            <p style="text-align:center;font-size:.75rem;color:#9d8ac0;margin-top:14px">* Fiyatlar yaklaşık hesaplama içindir. Kesin tutar için ücretsiz keşif talep edebilirsiniz.</p>
        </div>

        <script>var PRC_DATA=<?= json_encode(['wa'=>$calcWa,'services'=>$_calcServices],JSON_UNESCAPED_UNICODE) ?>;</script>
    </div>
</section>

<script>
(function () {
    var WA = '905456876161';
    var services = [];
    var state = { svcIdx: -1, itemIdx: -1, qty: 1 };

    var selSvc    = document.getElementById('prcSvcSel');
    var selItem   = document.getElementById('prcItemSel');
    var itemField = document.getElementById('prcItemField');
    var qtyField  = document.getElementById('prcQtyField');
    var qtyLabel  = document.getElementById('prcQtyLabel');
    var sliderWrap  = document.getElementById('prcSliderWrap');
    var stepperWrap = document.getElementById('prcStepperWrap');
    var slider    = document.getElementById('prcSlider');
    var sliderVal = document.getElementById('prcSliderVal');
    var stepVal   = document.getElementById('prcStepVal');
    var result    = document.getElementById('prcResult');
    var priceEl   = document.getElementById('prcPrice');
    var noteEl    = document.getElementById('prcNote');
    var waLink    = document.getElementById('prcWaLink');
    var actions   = document.getElementById('prcActions');
    var emptyMsg  = document.getElementById('prcEmptyMsg');
    var divider   = document.getElementById('prcDivider');
    var liveTag   = document.getElementById('prcLiveTag');

    function show(el) { el && el.classList.remove('prc-hidden'); }
    function hide(el) { el && el.classList.add('prc-hidden'); }
    function setDisplay(el, v) { if (el) el.style.display = v; }

    function parsePrice(str) {
        if (str === null || str === undefined || str === '') return null;
        var n = parseFloat(String(str).replace(/[^\d.,]/g, '').replace(',', '.'));
        return isNaN(n) ? null : n;
    }

    function getUnit(item) {
        var u = (item.unit || '').toLowerCase();
        if (u.indexOf('m²') >= 0 || u.indexOf('m2') >= 0) return 'm2';
        if (u.indexOf('takım') >= 0) return 'set';
        return 'piece';
    }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function parseSvcPriceStr(str) {
        if (!str) return { price: null, unit: '' };
        var m = str.match(/(\d+(?:[.,]\d+)?)\s*\/\s*([^'\s]+)/);
        if (m) { var p = parseFloat(m[1].replace(',','.')); return { price: isNaN(p)?null:p, unit: m[2].trim() }; }
        return { price: null, unit: '' };
    }

    function buildWaMsg() {
        var svc  = state.svcIdx >= 0 ? services[state.svcIdx] : null;
        var item = (svc && state.itemIdx >= 0) ? svc.items[state.itemIdx] : null;
        var price = calcPrice();
        var unit  = item ? getUnit(item) : 'piece';
        var unitLabel = unit === 'm2' ? 'm²' : unit === 'set' ? 'takım' : 'adet';
        var msg = 'Merhaba! Fiyat hesaplayıcıdan bilgi almak istiyorum.\n';
        if (svc)  msg += 'Hizmet: ' + svc.name + '\n';
        if (item && item.name !== (svc ? svc.name : '')) msg += 'Tür: ' + item.name + '\n';
        msg += 'Miktar: ' + state.qty + ' ' + unitLabel + '\n';
        if (price !== null) msg += 'Tahmini Fiyat: ₺' + Math.round(price).toLocaleString('tr-TR') + '\n';
        msg += 'Randevu almak istiyorum.';
        return encodeURIComponent(msg);
    }

    function calcPrice() {
        var svc  = state.svcIdx >= 0 ? services[state.svcIdx] : null;
        var item = (svc && state.itemIdx >= 0) ? svc.items[state.itemIdx] : null;
        if (!item) return null;
        var p = parsePrice(item.price);
        return p !== null ? p * state.qty : null;
    }

    function updateResult() {
        var price = calcPrice();
        var svc   = state.svcIdx >= 0 ? services[state.svcIdx] : null;
        var item  = (svc && state.itemIdx >= 0) ? svc.items[state.itemIdx] : null;

        if (item) {
            var unit = getUnit(item);
            if (unit === 'm2') {
                qtyLabel.textContent = 'Kaç metrekare?';
                show(sliderWrap); hide(stepperWrap);
                sliderVal.textContent = state.qty + ' m²';
                slider.value = state.qty;
            } else {
                qtyLabel.textContent = unit === 'set' ? 'Kaç takım?' : 'Kaç adet?';
                hide(sliderWrap);
                show(stepperWrap);
                stepVal.textContent = state.qty;
            }
            show(qtyField);
        } else {
            hide(qtyField);
        }

        if (price !== null) {
            priceEl.textContent = '₺' + Math.round(price).toLocaleString('tr-TR');
            if (noteEl) noteEl.textContent = 'Kesin fiyat için ücretsiz keşif yapıyoruz.';
            show(actions);
            waLink.href = 'https://wa.me/' + WA + '?text=' + buildWaMsg();
            if (liveTag) liveTag.style.display = '';
        } else if (item && parsePrice(item.price) === null) {
            priceEl.textContent = '—';
            if (noteEl) noteEl.textContent = 'Bu hizmet için yerinde değerlendirme yapıyoruz.';
            hide(actions);
        }
    }

    function onSvcChange() {
        var idx = parseInt(selSvc.value);
        if (isNaN(idx) || idx < 0) {
            state.svcIdx = -1; state.itemIdx = -1;
            hide(itemField); hide(qtyField); hide(emptyMsg); hide(actions);
            priceEl.textContent = '—';
            if (noteEl) noteEl.textContent = 'Yukarıdan hizmet seçin, anında hesaplayalım.';
            return;
        }
        state.svcIdx = idx;
        state.itemIdx = -1;
        var svc   = services[idx];
        var items = svc.items || [];

        hide(emptyMsg);
        hide(actions);
        hide(qtyField);
        priceEl.textContent = '—';
        if (noteEl) noteEl.textContent = 'Yukarıdan hizmet seçin, anında hesaplayalım.';

        if (items.length === 0) {
            hide(itemField);
            show(emptyMsg);
            return;
        }

        if (items.length === 1) {
            // Skip item selector, go straight to qty
            hide(itemField);
            state.itemIdx = 0;
            var unit = getUnit(items[0]);
            state.qty = unit === 'm2' ? 20 : 1;
            updateResult();
        } else {
            // Show item selector
            selItem.innerHTML = '<option value="">— Türü seçin —</option>';
            items.forEach(function(it, i) {
                var p = parsePrice(it.price);
                var suffix = p !== null ? ' (' + p + '₺/' + (it.unit || '') + ')' : '';
                var opt = document.createElement('option');
                opt.value = i;
                opt.textContent = it.name + suffix;
                selItem.appendChild(opt);
            });
            show(itemField);
        }
    }

    function onItemChange() {
        var idx = parseInt(selItem.value);
        if (isNaN(idx) || idx < 0) {
            state.itemIdx = -1; hide(qtyField); hide(actions);
            priceEl.textContent = '—';
            if (noteEl) noteEl.textContent = 'Türü seçin, anında hesaplayalım.';
            return;
        }
        state.itemIdx = idx;
        var svc  = services[state.svcIdx];
        var item = svc.items[idx];
        var unit = getUnit(item);
        state.qty = unit === 'm2' ? 20 : 1;
        updateResult();
    }

    // Bind events
    selSvc.addEventListener('change', onSvcChange);
    selItem.addEventListener('change', onItemChange);

    slider.addEventListener('input', function() {
        state.qty = Math.max(1, parseInt(slider.value) || 1);
        sliderVal.textContent = state.qty + ' m²';
        priceEl.textContent = '₺' + Math.round(calcPrice() || 0).toLocaleString('tr-TR');
        waLink.href = 'https://wa.me/' + WA + '?text=' + buildWaMsg();
    });

    document.getElementById('prcMinus').addEventListener('click', function() {
        if (state.qty > 1) { state.qty--; stepVal.textContent = state.qty; priceEl.textContent = '₺' + Math.round(calcPrice() || 0).toLocaleString('tr-TR'); waLink.href = 'https://wa.me/' + WA + '?text=' + buildWaMsg(); }
    });
    document.getElementById('prcPlus').addEventListener('click', function() {
        state.qty++; stepVal.textContent = state.qty; priceEl.textContent = '₺' + Math.round(calcPrice() || 0).toLocaleString('tr-TR'); waLink.href = 'https://wa.me/' + WA + '?text=' + buildWaMsg();
    });

    // Init
    if (typeof PRC_DATA === 'undefined') return;
    WA = PRC_DATA.wa || WA;
    services = (PRC_DATA.services || []).map(function(s) {
        if (s.items && s.items.length > 0) return s;
        var parsed = parseSvcPriceStr(s.price);
        if (parsed.price === null) return null;
        return { name: s.name, icon: s.icon, price: s.price,
            items: [{ name: s.name, price: parsed.price, unit: parsed.unit }] };
    }).filter(Boolean);

    if (services.length === 0) return;

    selSvc.innerHTML = '<option value="">— Hizmet seçin —</option>';
    services.forEach(function(s, i) {
        var opt = document.createElement('option');
        opt.value = i;
        opt.textContent = s.name;
        selSvc.appendChild(opt);
    });
})();
</script>
