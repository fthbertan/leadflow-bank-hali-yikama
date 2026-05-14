<?php
// LeadFlow — Online Randevu Sayfası (5 Adımlı Form)
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
$phone        = $settings['phone'] ?? '0 545 687 61 61';
$phoneRaw     = $settings['phone_raw'] ?? '05456876161';
$whatsapp     = $settings['whatsapp_number'] ?? '905456876161';

// Hizmetler (hesaplayıcı ile aynı kaynak)
$_services = [];
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
        $items = [];
        foreach ($allItems as $it) {
            if ((int)$it['service_id'] === $svcId) {
                $items[] = ['name' => $it['name'], 'price' => $it['price'], 'unit' => $it['unit']];
            }
        }
        $_services[] = ['name' => $r['title'], 'icon' => $r['icon'], 'price' => $r['price'], 'items' => $items];
    }
} catch (Exception $e) {}

// JSON fallback
if (empty($_services)) {
    $jsonPath = __DIR__ . '/data/services.json';
    if (file_exists($jsonPath)) {
        $json = json_decode(file_get_contents($jsonPath), true);
        foreach (($json['services'] ?? []) as $s) {
            $items = [];
            foreach (($s['items'] ?? []) as $it) {
                $items[] = ['name' => $it['name'] ?? '', 'price' => $it['price'] ?? '', 'unit' => $it['unit'] ?? ''];
            }
            $_services[] = ['name' => $s['name'] ?? '', 'icon' => $s['icon'] ?? '', 'price' => $s['price'] ?? '', 'items' => $items];
        }
    }
}

// Hizmet bölgeleri
$_areas = [];
try {
    $aStmt = $db->query('SELECT * FROM service_areas WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    foreach ($aStmt->fetchAll() as $a) {
        $days = json_decode($a['available_days'], true);
        $_areas[] = ['id' => $a['id'], 'name' => $a['name'], 'available_days' => is_array($days) ? $days : []];
    }
} catch (Exception $e) {}

$siteUrl   = 'https://bankhaliyikama.com.tr';
$pageTitle = 'Online Randevu | ' . $businessName;
$metaDesc  = $businessName . ' üzerinden kolayca online randevu alın. Halı yıkama, koltuk yıkama ve diğer tüm hizmetlerde kapınıza geliyoruz.';
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="tr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<link rel="canonical" href="<?= $siteUrl ?>/randevu"/>
<link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
<link rel="stylesheet" href="/css/style.css"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>document.fonts && document.fonts.ready.then(function(){ document.documentElement.classList.add('fonts-loaded'); });</script>
<style>
.material-symbols-outlined { font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; vertical-align:middle; font-size:0!important; overflow:hidden; }
.fonts-loaded .material-symbols-outlined { font-size:inherit!important; overflow:visible; }
body { font-family:'Inter',sans-serif; }
/* Steps */
.step-indicator { display:flex; align-items:center; justify-content:center; gap:0; }
.step-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; transition:all .3s; flex-shrink:0; border:2px solid; }
.step-dot.done { background:#6D31C4; border-color:#6D31C4; color:#fff; }
.step-dot.active { background:#6D31C4; border-color:#6D31C4; color:#fff; box-shadow:0 0 0 4px rgba(109,49,196,.2); }
.step-dot.upcoming { background:#fff; border-color:#d1d5db; color:#9ca3af; }
.step-line { flex:1; height:2px; min-width:16px; max-width:48px; transition:background .3s; }
.step-line.done { background:#6D31C4; }
.step-line.upcoming { background:#e5e7eb; }
/* Service selector */
.svc-card { cursor:pointer; border:2px solid #e5e7eb; border-radius:14px; padding:12px 16px; transition:all .25s; display:flex; align-items:center; gap:12px; }
.svc-card:hover { border-color:#6D31C4; background:#f5f0ff; }
.svc-card.selected { border-color:#6D31C4; background:#f5f0ff; }
.svc-card .svc-icon { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#6D31C4,#E0457B); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.25rem; flex-shrink:0; }
/* Area selector */
.area-btn { cursor:pointer; border:2px solid #e5e7eb; border-radius:12px; padding:12px 16px; transition:all .25s; text-align:left; width:100%; display:flex; justify-content:space-between; align-items:center; background:#fff; }
.area-btn:hover { border-color:#6D31C4; }
.area-btn.selected { border-color:#6D31C4; background:#f5f0ff; }
/* Date picker */
.date-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
.date-cell { border-radius:8px; padding:8px 4px; text-align:center; font-size:.75rem; cursor:pointer; border:1.5px solid #e5e7eb; transition:all .2s; background:#fff; }
.date-cell.available:hover { border-color:#6D31C4; background:#f5f0ff; }
.date-cell.selected { background:#6D31C4; border-color:#6D31C4; color:#fff; font-weight:700; }
.date-cell.unavailable { opacity:.4; cursor:not-allowed; background:#f9f9f9; }
.date-cell.today { font-weight:700; color:#6D31C4; }
.date-cell.today.selected { color:#fff; }
.cal-header { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-bottom:4px; }
.cal-header span { text-align:center; font-size:.65rem; color:#9ca3af; font-weight:600; padding:4px 0; }
/* Slot */
.slot-btn { cursor:pointer; border:2px solid #e5e7eb; border-radius:12px; padding:12px 16px; transition:all .25s; text-align:center; background:#fff; }
.slot-btn:hover { border-color:#6D31C4; }
.slot-btn.selected { border-color:#6D31C4; background:#f5f0ff; color:#6D31C4; font-weight:600; }
/* Form inputs */
.form-input { width:100%; border:1.5px solid #e5e7eb; border-radius:12px; padding:12px 16px; font-size:.9rem; outline:none; transition:border .2s; background:#fff; }
.form-input:focus { border-color:#6D31C4; box-shadow:0 0 0 3px rgba(109,49,196,.1); }
/* Summary */
.summary-row { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:10px 0; border-bottom:1px solid #f3f4f6; }
.summary-row:last-child { border-bottom:none; }
/* Buttons */
.btn-primary { background:linear-gradient(110deg,#6D31C4,#9D68DB); color:#fff; font-weight:700; padding:14px 32px; border-radius:14px; transition:all .2s; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:.95rem; }
.btn-primary:hover { opacity:.9; transform:translateY(-1px); }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.btn-secondary { background:#fff; color:#374151; font-weight:600; padding:14px 24px; border-radius:14px; transition:all .2s; border:1.5px solid #e5e7eb; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:.9rem; }
.btn-secondary:hover { border-color:#6D31C4; color:#6D31C4; }
.btn-wa { background:linear-gradient(110deg,#25D366,#128C7E); color:#fff; font-weight:700; padding:16px 32px; border-radius:14px; transition:all .2s; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:10px; font-size:1rem; }
.btn-wa:hover { filter:brightness(1.05); transform:translateY(-1px); }
.error-msg { color:#ef4444; font-size:.8rem; margin-top:4px; }
/* Quantity stepper */
.qty-stepper { display:flex; align-items:center; gap:0; border:1.5px solid #e5e7eb; border-radius:12px; overflow:hidden; width:fit-content; }
.qty-btn { width:40px; height:44px; border:none; background:#f9fafb; cursor:pointer; font-size:1.25rem; color:#374151; transition:background .2s; }
.qty-btn:hover { background:#f5f0ff; color:#6D31C4; }
.qty-val { width:64px; height:44px; text-align:center; border:none; border-left:1.5px solid #e5e7eb; border-right:1.5px solid #e5e7eb; font-size:.95rem; font-weight:600; outline:none; background:#fff; }
/* Slider */
.qty-slider { -webkit-appearance:none; appearance:none; width:100%; height:6px; border-radius:3px; background:#e5e7eb; outline:none; }
.qty-slider::-webkit-slider-thumb { -webkit-appearance:none; width:20px; height:20px; border-radius:50%; background:#6D31C4; cursor:pointer; }
.qty-slider::-moz-range-thumb { width:20px; height:20px; border-radius:50%; background:#6D31C4; cursor:pointer; border:none; }
/* Estimate */
.price-estimate { background:linear-gradient(135deg,#f5f0ff,#fff0f6); border:1.5px solid #e5e7eb; border-radius:14px; padding:16px 20px; }
/* Success */
.success-icon { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#6D31C4,#E0457B); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
@keyframes pop { 0%{transform:scale(0)} 70%{transform:scale(1.1)} 100%{transform:scale(1)} }
.success-icon { animation:pop .5s ease; }
.btn-cta { background:linear-gradient(110deg,#B33562,#E0457B,#B33562); background-size:200% 100%; animation:shimmer 4s linear infinite; color:#fff; font-weight:700; border:none; cursor:pointer; }
.btn-cta:hover { filter:brightness(1.1); }
@keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
#scrollTopBtn { opacity:0; pointer-events:none; transition:opacity .4s; }
#scrollTopBtn.visible { opacity:1; pointer-events:auto; }
</style>
<script>
var RDV_DATA = <?= json_encode([
    'wa'       => $whatsapp,
    'phone'    => $phoneRaw,
    'bizName'  => $businessName,
    'services' => $_services,
    'areas'    => $_areas,
], JSON_UNESCAPED_UNICODE) ?>;
</script>
</head>
<body class="bg-[#f8fafc] font-body text-gray-800">

<?php $pageType = 'sub'; include __DIR__ . '/includes/navbar.php'; ?>

<!-- Page Header -->
<header class="bg-gradient-to-br from-[#251560] via-[#6D31C4] to-[#251560] py-16 sm:py-20 text-center relative overflow-hidden">
    <div class="absolute top-10 right-10 w-64 h-64 bg-[#E0457B]/5 rounded-full blur-[80px]"></div>
    <div class="absolute bottom-0 left-10 w-48 h-48 bg-[#6D31C4]/20 rounded-full blur-[60px]"></div>
    <div class="relative z-10 max-w-4xl mx-auto px-4">
        <nav class="flex justify-center items-center gap-2 text-white/50 text-xs mb-8">
            <a href="/" class="hover:text-[#E0457B] transition-colors">Ana Sayfa</a>
            <svg style="width:1em;height:1em" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            <span class="text-[#E0457B]">Online Randevu</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-3">Online <span class="text-[#E0457B]">Randevu Al</span></h1>
        <p class="text-white/60 text-base max-w-xl mx-auto">Birkaç adımda kolayca randevunuzu oluşturun, biz sizin için kapınıza gelelim.</p>
    </div>
</header>

<!-- Form Container -->
<main class="max-w-2xl mx-auto px-4 py-10 sm:py-16">

    <!-- Step Indicator -->
    <div id="stepIndicator" class="step-indicator mb-8 select-none">
        <div class="step-dot active" id="dot-1">1</div>
        <div class="step-line upcoming" id="line-1"></div>
        <div class="step-dot upcoming" id="dot-2">2</div>
        <div class="step-line upcoming" id="line-2"></div>
        <div class="step-dot upcoming" id="dot-3">3</div>
        <div class="step-line upcoming" id="line-3"></div>
        <div class="step-dot upcoming" id="dot-4">4</div>
        <div class="step-line upcoming" id="line-4"></div>
        <div class="step-dot upcoming" id="dot-5">5</div>
    </div>

    <!-- ══ STEP 1: Hizmet Seçimi ══ -->
    <div id="step-1" class="step-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <h2 class="text-xl font-bold mb-1">Hizmet Seçin</h2>
        <p class="text-sm text-gray-500 mb-6">Hangi hizmeti almak istiyorsunuz?</p>
        <div id="svcList" class="space-y-3 mb-6"></div>

        <!-- Sub-item selector -->
        <div id="itemSelectWrap" class="hidden mb-6">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Tür Seçin</label>
            <select id="itemSelect" class="form-input" onchange="onItemChange()">
                <option value="">Seçiniz...</option>
            </select>
        </div>

        <!-- Quantity -->
        <div id="qtyWrap" class="hidden mb-6">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3" id="qtyLabel">Miktar</label>
            <div id="qtySliderWrap" class="hidden">
                <input type="range" class="qty-slider mb-3" id="qtySlider" min="1" max="300" value="10" step="1" oninput="onQtyChange()">
                <div class="flex justify-between text-xs text-gray-400 mb-2"><span>1 m²</span><span>300 m²</span></div>
                <div class="text-center text-2xl font-black text-purple-700" id="qtySliderVal">10 m²</div>
            </div>
            <div id="qtyStepperWrap" class="hidden flex-col items-start gap-2">
                <div class="qty-stepper">
                    <button class="qty-btn" type="button" onclick="stepQty(-1)">−</button>
                    <input class="qty-val" type="number" id="qtyStepper" min="1" max="50" value="1" oninput="onQtyChange()">
                    <button class="qty-btn" type="button" onclick="stepQty(1)">+</button>
                </div>
            </div>
        </div>

        <!-- Price estimate -->
        <div id="priceEstimate" class="hidden price-estimate mb-6">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Tahmini Fiyat</span>
                <span class="text-2xl font-black text-purple-700" id="estPrice">—</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Gerçek fiyat yerinde inceleme sonrası netleşir.</p>
        </div>

        <div id="step1Error" class="error-msg hidden"></div>
        <div class="flex justify-end mt-2">
            <button onclick="goToStep(2)" class="btn-primary">Devam Et <span class="material-symbols-outlined text-lg">arrow_forward</span></button>
        </div>
    </div>

    <!-- ══ STEP 2: Bölge Seçimi ══ -->
    <div id="step-2" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <h2 class="text-xl font-bold mb-1">Bölgenizi Seçin</h2>
        <p class="text-sm text-gray-500 mb-6">Bulunduğunuz ilçeyi veya semti seçin.</p>
        <div id="areaList" class="space-y-2 mb-4"></div>
        <div id="areaNoData" class="hidden text-sm text-gray-400 py-4 text-center">
            <span class="material-symbols-outlined text-3xl block mb-2">location_off</span>
            Hizmet bölgesi bilgisi henüz girilmemiş. Lütfen telefon ile randevu alın.
        </div>
        <div id="step2Error" class="error-msg hidden"></div>
        <div class="flex justify-between mt-6">
            <button onclick="goToStep(1)" class="btn-secondary"><span class="material-symbols-outlined text-lg">arrow_back</span> Geri</button>
            <button onclick="goToStep(3)" class="btn-primary">Devam Et <span class="material-symbols-outlined text-lg">arrow_forward</span></button>
        </div>
    </div>

    <!-- ══ STEP 3: Tarih & Saat ══ -->
    <div id="step-3" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <h2 class="text-xl font-bold mb-1">Tarih & Alım Saati</h2>
        <p class="text-sm text-gray-500 mb-6">Servisimizin gelebileceği gün ve saat diliminizi seçin.</p>

        <!-- Mini calendar -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <button type="button" onclick="prevMonth()" class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 hover:border-purple-400 transition-colors"><span class="material-symbols-outlined text-lg">chevron_left</span></button>
                <span id="calMonthLabel" class="font-bold text-sm"></span>
                <button type="button" onclick="nextMonth()" class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 hover:border-purple-400 transition-colors"><span class="material-symbols-outlined text-lg">chevron_right</span></button>
            </div>
            <div class="cal-header">
                <span>Paz</span><span>Pzt</span><span>Sal</span><span>Çar</span><span>Per</span><span>Cum</span><span>Cmt</span>
            </div>
            <div id="calGrid" class="date-grid"></div>
        </div>

        <!-- Slot -->
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Alım Saati</label>
            <div class="grid grid-cols-3 gap-3">
                <button type="button" class="slot-btn" data-slot="sabah" onclick="selectSlot('sabah')">
                    <div class="text-lg mb-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1">wb_sunny</span></div>
                    <div class="font-semibold text-sm">Sabah</div>
                    <div class="text-xs text-gray-400">08:00–12:00</div>
                </button>
                <button type="button" class="slot-btn" data-slot="oglen" onclick="selectSlot('oglen')">
                    <div class="text-lg mb-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1">partly_cloudy_day</span></div>
                    <div class="font-semibold text-sm">Öğlen</div>
                    <div class="text-xs text-gray-400">12:00–17:00</div>
                </button>
                <button type="button" class="slot-btn" data-slot="aksam" onclick="selectSlot('aksam')">
                    <div class="text-lg mb-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1">wb_twilight</span></div>
                    <div class="font-semibold text-sm">Akşam</div>
                    <div class="text-xs text-gray-400">17:00–20:00</div>
                </button>
            </div>
        </div>

        <div id="step3Error" class="error-msg hidden mt-3"></div>
        <div class="flex justify-between mt-6">
            <button onclick="goToStep(2)" class="btn-secondary"><span class="material-symbols-outlined text-lg">arrow_back</span> Geri</button>
            <button onclick="goToStep(4)" class="btn-primary">Devam Et <span class="material-symbols-outlined text-lg">arrow_forward</span></button>
        </div>
    </div>

    <!-- ══ STEP 4: Kişisel Bilgiler ══ -->
    <div id="step-4" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <h2 class="text-xl font-bold mb-1">İletişim Bilgileri</h2>
        <p class="text-sm text-gray-500 mb-6">Size ulaşabilmemiz için bilgilerinizi girin.</p>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Ad Soyad *</label>
                <input type="text" id="rdvName" class="form-input" placeholder="Adınız Soyadınız" autocomplete="name"/>
                <div id="errName" class="error-msg hidden">Ad soyad zorunludur.</div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Telefon *</label>
                <input type="tel" id="rdvPhone" class="form-input" placeholder="05XX XXX XX XX" autocomplete="tel"/>
                <div id="errPhone" class="error-msg hidden">Telefon numarası zorunludur.</div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Açık Adres (İsteğe Bağlı)</label>
                <input type="text" id="rdvAddress" class="form-input" placeholder="Mahalle, sokak, bina no..." autocomplete="street-address"/>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Not / Ek Bilgi (İsteğe Bağlı)</label>
                <textarea id="rdvNotes" class="form-input" rows="3" style="resize:vertical" placeholder="Halı türü, leke detayı, kapı kodu vb."></textarea>
            </div>
        </div>
        <div id="step4Error" class="error-msg hidden mt-2"></div>
        <div class="flex justify-between mt-6">
            <button onclick="goToStep(3)" class="btn-secondary"><span class="material-symbols-outlined text-lg">arrow_back</span> Geri</button>
            <button onclick="goToStep(5)" class="btn-primary">Özeti Gör <span class="material-symbols-outlined text-lg">arrow_forward</span></button>
        </div>
    </div>

    <!-- ══ STEP 5: Özet & Onay ══ -->
    <div id="step-5" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <h2 class="text-xl font-bold mb-1">Özet & Onay</h2>
        <p class="text-sm text-gray-500 mb-6">Bilgilerinizi kontrol edin ve randevuyu onaylayın.</p>
        <div id="summaryContent" class="mb-6"></div>
        <div id="step5Error" class="error-msg hidden mb-4"></div>
        <div class="flex flex-col sm:flex-row gap-3">
            <button onclick="goToStep(4)" class="btn-secondary"><span class="material-symbols-outlined text-lg">arrow_back</span> Düzenle</button>
            <button onclick="submitReservation()" id="submitBtn" class="btn-wa flex-1 justify-center">
                <svg viewBox="0 0 24 24" style="width:1.2em;height:1.2em;fill:currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp ile Onayla
            </button>
        </div>
        <p class="text-xs text-gray-400 text-center mt-4">WhatsApp üzerinden onaylayarak randevunuzu tamamlayın. Ekibimiz sizi arayacaktır.</p>
    </div>

    <!-- ══ SUCCESS ══ -->
    <div id="step-success" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="success-icon">
            <span class="material-symbols-outlined text-white text-4xl" style="font-variation-settings:'FILL' 1">check_circle</span>
        </div>
        <h2 class="text-2xl font-black mb-3">Randevunuz Alındı!</h2>
        <p class="text-gray-500 mb-6">WhatsApp üzerinden ekibimiz en kısa sürede sizinle iletişime geçecek.</p>
        <div class="flex flex-col gap-3 items-center">
            <a href="/" class="btn-secondary"><span class="material-symbols-outlined text-lg">home</span> Ana Sayfaya Dön</a>
            <a id="waLinkSuccess" href="#" target="_blank" class="btn-wa"><svg viewBox="0 0 24 24" style="width:1.1em;height:1.1em;fill:currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> Tekrar Mesaj At</a>
        </div>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- WhatsApp Floating Button -->
<?php if ($whatsapp): ?>
<div class="fixed right-6 md:right-8 bg-gradient-to-br from-[#25D366] to-[#128C7E] text-white w-12 h-12 rounded-full shadow-[0_0_24px_rgba(37,211,102,0.4)] hover:scale-110 hover:shadow-[0_0_32px_rgba(37,211,102,0.5)] transition-all duration-500 ease-out z-[100] flex items-center justify-center cursor-pointer group wa-bounce" id="waButton" onclick="window.open('https://wa.me/<?= htmlspecialchars($whatsapp) ?>','_blank')" style="bottom:80px;" aria-label="WhatsApp" role="button">
    <svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</div>
<?php endif; ?>

<!-- Scroll Top -->
<button id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" class="fixed bottom-6 right-6 md:right-8 w-12 h-12 rounded-full bg-[#251560] text-white shadow-lg flex items-center justify-center hover:bg-[#6D31C4] transition-colors z-[99]" aria-label="Yukarı Çık">
    <span class="material-symbols-outlined text-xl">arrow_upward</span>
</button>

<script src="/js/main.js" defer></script>
<script>
// ═══════════════════════════════════════════════════════
// RANDEVU STATE
// ═══════════════════════════════════════════════════════
var RDV = {
    step: 1,
    serviceIdx: -1,
    serviceName: '',
    serviceItem: '',
    itemPrice: '',
    itemUnit: '',
    quantity: 1,
    estimatedPrice: 0,
    areaName: '',
    areaAvailableDays: [],
    pickupDate: '',
    pickupSlot: '',
    name: '',
    phone: '',
    address: '',
    notes: ''
};

var calYear, calMonth;
var today = new Date();
calYear  = today.getFullYear();
calMonth = today.getMonth();

// ══ INIT ══
(function init() {
    renderSvcList();
    renderAreaList();
    renderCalendar();
    scrollTopSetup();
})();

// ══ STEP NAVIGATION ══
function goToStep(n) {
    if (n > RDV.step && !validateStep(RDV.step)) return;
    // collect data from current step
    collectStep(RDV.step);
    // transition
    hideStep(RDV.step);
    RDV.step = n;
    showStep(n);
    updateStepIndicator(n);
    if (n === 5) buildSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(n) {
    if (n === 1) {
        var err = document.getElementById('step1Error');
        if (RDV.serviceIdx < 0) { showErr(err, 'Lütfen bir hizmet seçin.'); return false; }
        var items = RDV_DATA.services[RDV.serviceIdx].items;
        if (items.length > 0 && !RDV.serviceItem) { showErr(err, 'Lütfen hizmet türünü seçin.'); return false; }
        hideErr(err); return true;
    }
    if (n === 2) {
        var err = document.getElementById('step2Error');
        if (RDV_DATA.areas.length > 0 && !RDV.areaName) { showErr(err, 'Lütfen hizmet bölgenizi seçin.'); return false; }
        hideErr(err); return true;
    }
    if (n === 3) {
        var err = document.getElementById('step3Error');
        if (!RDV.pickupDate) { showErr(err, 'Lütfen alım tarihi seçin.'); return false; }
        if (!RDV.pickupSlot) { showErr(err, 'Lütfen alım saati seçin.'); return false; }
        hideErr(err); return true;
    }
    if (n === 4) {
        var errName = document.getElementById('errName');
        var errPhone = document.getElementById('errPhone');
        var step4Err = document.getElementById('step4Error');
        var name  = document.getElementById('rdvName').value.trim();
        var phone = document.getElementById('rdvPhone').value.trim();
        var ok = true;
        if (!name)  { errName.classList.remove('hidden');  ok = false; } else errName.classList.add('hidden');
        if (!phone) { errPhone.classList.remove('hidden'); ok = false; } else errPhone.classList.add('hidden');
        if (!ok) { showErr(step4Err, ''); return false; }
        hideErr(step4Err); return true;
    }
    return true;
}

function collectStep(n) {
    if (n === 4) {
        RDV.name    = document.getElementById('rdvName').value.trim();
        RDV.phone   = document.getElementById('rdvPhone').value.trim();
        RDV.address = document.getElementById('rdvAddress').value.trim();
        RDV.notes   = document.getElementById('rdvNotes').value.trim();
    }
}

function showStep(n) {
    var el = n === 6 ? document.getElementById('step-success') : document.getElementById('step-' + n);
    if (el) el.classList.remove('hidden');
}
function hideStep(n) {
    var el = document.getElementById('step-' + n);
    if (el) el.classList.add('hidden');
}

function updateStepIndicator(active) {
    for (var i = 1; i <= 5; i++) {
        var dot  = document.getElementById('dot-' + i);
        var line = document.getElementById('line-' + i);
        if (i < active)       { dot.className = 'step-dot done';     dot.innerHTML = '<span class="material-symbols-outlined text-sm" style="font-variation-settings:\'FILL\' 1">check</span>'; }
        else if (i === active) { dot.className = 'step-dot active';   dot.textContent = i; }
        else                  { dot.className = 'step-dot upcoming'; dot.textContent = i; }
        if (line) { line.className = 'step-line ' + (i < active ? 'done' : 'upcoming'); }
    }
}

function showErr(el, msg) { if (!el) return; el.textContent = msg; el.classList.remove('hidden'); }
function hideErr(el) { if (!el) return; el.classList.add('hidden'); }

// ══ STEP 1: Service ══
function renderSvcList() {
    var container = document.getElementById('svcList');
    var html = '';
    var services = RDV_DATA.services || [];

    // Filter: only show services with items OR no items (show all)
    services.forEach(function(svc, idx) {
        html += '<div class="svc-card" id="svc-card-' + idx + '" onclick="selectService(' + idx + ')">' +
            '<div class="svc-icon"><span class="material-symbols-outlined text-xl" style="font-variation-settings:\'FILL\' 1">' + escH(svc.icon || 'cleaning_services') + '</span></div>' +
            '<div class="flex-1 min-w-0">' +
                '<div class="font-semibold text-sm">' + escH(svc.name) + '</div>' +
                '<div class="text-xs text-gray-400">' + escH(svc.price || '') + '</div>' +
            '</div>' +
            '<span class="material-symbols-outlined text-gray-300 text-xl svc-check-' + idx + '" style="display:none;font-variation-settings:\'FILL\' 1">check_circle</span>' +
        '</div>';
    });

    if (!html) {
        html = '<p class="text-sm text-gray-400">Hizmet bilgisi yüklenemedi. Lütfen telefon ile arayın.</p>';
    }
    container.innerHTML = html;
}

function selectService(idx) {
    // deselect old
    if (RDV.serviceIdx >= 0) {
        var oldCard = document.getElementById('svc-card-' + RDV.serviceIdx);
        if (oldCard) oldCard.classList.remove('selected');
        var oldCheck = document.querySelector('.svc-check-' + RDV.serviceIdx);
        if (oldCheck) oldCheck.style.display = 'none';
    }
    RDV.serviceIdx = idx;
    RDV.serviceName = RDV_DATA.services[idx].name;
    RDV.serviceItem = '';
    RDV.itemPrice = '';
    RDV.itemUnit = '';
    RDV.quantity = 1;
    RDV.estimatedPrice = 0;

    var card = document.getElementById('svc-card-' + idx);
    if (card) card.classList.add('selected');
    var check = document.querySelector('.svc-check-' + idx);
    if (check) check.style.display = '';

    // Items
    var items = RDV_DATA.services[idx].items || [];
    var itemWrap = document.getElementById('itemSelectWrap');
    var itemSel  = document.getElementById('itemSelect');
    var qtyWrap  = document.getElementById('qtyWrap');
    var priceEst = document.getElementById('priceEstimate');

    if (items.length > 0) {
        itemSel.innerHTML = '<option value="">Seçiniz...</option>';
        items.forEach(function(it, i) {
            var label = it.name + (it.price ? ' — ₺' + it.price + '/' + (it.unit || '') : '');
            itemSel.innerHTML += '<option value="' + i + '">' + escH(label) + '</option>';
        });
        itemWrap.classList.remove('hidden');
    } else {
        itemWrap.classList.add('hidden');
    }
    qtyWrap.classList.add('hidden');
    priceEst.classList.add('hidden');
}

function onItemChange() {
    var idx = RDV.serviceIdx;
    var sel = document.getElementById('itemSelect');
    var itemIdx = sel.value !== '' ? parseInt(sel.value) : -1;

    var qtyWrap  = document.getElementById('qtyWrap');
    var priceEst = document.getElementById('priceEstimate');

    if (itemIdx < 0) {
        RDV.serviceItem = '';
        qtyWrap.classList.add('hidden');
        priceEst.classList.add('hidden');
        return;
    }

    var item = RDV_DATA.services[idx].items[itemIdx];
    RDV.serviceItem = item.name;
    RDV.itemPrice   = parseFloat(item.price) || 0;
    RDV.itemUnit    = item.unit || '';

    // Quantity type
    var unit = (item.unit || '').toLowerCase().trim();
    var isArea = (unit === 'm²' || unit === 'm2');

    var sliderWrap  = document.getElementById('qtySliderWrap');
    var stepperWrap = document.getElementById('qtyStepperWrap');
    var qtyLabel    = document.getElementById('qtyLabel');

    qtyWrap.classList.remove('hidden');
    if (isArea) {
        sliderWrap.classList.remove('hidden');
        stepperWrap.classList.add('hidden');
        qtyLabel.textContent = 'Halı Alanı (m²)';
        var slider = document.getElementById('qtySlider');
        slider.value = RDV.quantity || 10;
        document.getElementById('qtySliderVal').textContent = (RDV.quantity || 10) + ' m²';
        RDV.quantity = parseInt(slider.value);
    } else {
        sliderWrap.classList.add('hidden');
        stepperWrap.classList.remove('hidden');
        stepperWrap.style.display = 'flex';
        qtyLabel.textContent = 'Adet / Takım';
        document.getElementById('qtyStepper').value = RDV.quantity || 1;
        RDV.quantity = parseInt(document.getElementById('qtyStepper').value);
    }
    calcEstimate();
    priceEst.classList.remove('hidden');
}

function onQtyChange() {
    var slider  = document.getElementById('qtySlider');
    var stepper = document.getElementById('qtyStepper');
    var sliderVisible = !document.getElementById('qtySliderWrap').classList.contains('hidden');

    if (sliderVisible) {
        var v = parseInt(slider.value);
        document.getElementById('qtySliderVal').textContent = v + ' m²';
        RDV.quantity = v;
    } else {
        RDV.quantity = parseInt(stepper.value) || 1;
    }
    calcEstimate();
}

function stepQty(delta) {
    var inp = document.getElementById('qtyStepper');
    var v = (parseInt(inp.value) || 1) + delta;
    v = Math.max(1, Math.min(50, v));
    inp.value = v;
    RDV.quantity = v;
    calcEstimate();
}

function calcEstimate() {
    if (!RDV.itemPrice) return;
    RDV.estimatedPrice = Math.round(RDV.itemPrice * RDV.quantity);
    document.getElementById('estPrice').textContent = '₺' + RDV.estimatedPrice.toLocaleString('tr-TR');
}

// ══ STEP 2: Areas ══
function renderAreaList() {
    var container = document.getElementById('areaList');
    var noData    = document.getElementById('areaNoData');
    var areas = RDV_DATA.areas || [];

    if (areas.length === 0) {
        container.innerHTML = '';
        noData.classList.remove('hidden');
        return;
    }
    noData.classList.add('hidden');

    var DAY_NAMES = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];
    var html = '';
    areas.forEach(function(area) {
        var dayLabels = (area.available_days || []).map(function(d) { return DAY_NAMES[d] || ''; }).join(', ');
        html += '<button type="button" class="area-btn" data-area="' + escH(area.name) + '" data-days="' + escH(JSON.stringify(area.available_days || [])) + '" onclick="selectArea(this)">' +
            '<div>' +
                '<div class="font-semibold text-sm">' + escH(area.name) + '</div>' +
                (dayLabels ? '<div class="text-xs text-gray-400 mt-0.5">Servis günleri: ' + escH(dayLabels) + '</div>' : '') +
            '</div>' +
            '<span class="material-symbols-outlined text-gray-300 area-check" style="display:none;font-variation-settings:\'FILL\' 1">check_circle</span>' +
        '</button>';
    });
    container.innerHTML = html;
}

function selectArea(btn) {
    document.querySelectorAll('.area-btn').forEach(function(b) {
        b.classList.remove('selected');
        var ch = b.querySelector('.area-check');
        if (ch) ch.style.display = 'none';
    });
    btn.classList.add('selected');
    var ch = btn.querySelector('.area-check');
    if (ch) ch.style.display = '';

    RDV.areaName = btn.dataset.area;
    try { RDV.areaAvailableDays = JSON.parse(btn.dataset.days); } catch(e) { RDV.areaAvailableDays = []; }

    // Reset date since available days changed
    RDV.pickupDate = '';
    renderCalendar();
}

// ══ STEP 3: Calendar ══
function renderCalendar() {
    var months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    document.getElementById('calMonthLabel').textContent = months[calMonth] + ' ' + calYear;

    var firstDay = new Date(calYear, calMonth, 1).getDay(); // 0=Sun
    var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    var todayStr = today.getFullYear() + '-' + pad(today.getMonth()+1) + '-' + pad(today.getDate());
    // Min date: tomorrow
    var minDate = new Date(today); minDate.setDate(minDate.getDate() + 1);
    var minStr  = minDate.getFullYear() + '-' + pad(minDate.getMonth()+1) + '-' + pad(minDate.getDate());

    var grid = document.getElementById('calGrid');
    var html = '';

    // Empty cells
    for (var i = 0; i < firstDay; i++) html += '<div></div>';

    for (var d = 1; d <= daysInMonth; d++) {
        var dateStr = calYear + '-' + pad(calMonth+1) + '-' + pad(d);
        var dayOfWeek = new Date(calYear, calMonth, d).getDay();
        var isToday   = dateStr === todayStr;
        var isPast    = dateStr < minStr;

        // available day check (if area has restrictions)
        var available = true;
        if (RDV.areaAvailableDays.length > 0) {
            available = RDV.areaAvailableDays.indexOf(dayOfWeek) !== -1;
        }

        var classes = 'date-cell';
        if (isPast || !available)    classes += ' unavailable';
        else if (dateStr === RDV.pickupDate) classes += ' selected';
        else                         classes += ' available';
        if (isToday) classes += ' today';

        var onclick = (isPast || !available) ? '' : ' onclick="selectDate(\'' + dateStr + '\')"';
        html += '<div class="' + classes + '"' + onclick + '>' + d + '</div>';
    }
    grid.innerHTML = html;
}

function prevMonth() {
    calMonth--;
    if (calMonth < 0) { calMonth = 11; calYear--; }
    renderCalendar();
}
function nextMonth() {
    calMonth++;
    if (calMonth > 11) { calMonth = 0; calYear++; }
    renderCalendar();
}

function selectDate(dateStr) {
    RDV.pickupDate = dateStr;
    renderCalendar();
}

function selectSlot(slot) {
    RDV.pickupSlot = slot;
    document.querySelectorAll('.slot-btn').forEach(function(b) {
        b.classList.toggle('selected', b.dataset.slot === slot);
    });
}

function pad(n) { return n < 10 ? '0' + n : '' + n; }

// ══ STEP 5: Summary ══
function buildSummary() {
    var SLOTS = { sabah: 'Sabah (08:00–12:00)', oglen: 'Öğlen (12:00–17:00)', aksam: 'Akşam (17:00–20:00)' };
    var rows = [
        ['Hizmet',     RDV.serviceName || '—'],
        ['Alt Tür',    RDV.serviceItem || '—'],
        ['Miktar',     RDV.quantity + ' ' + (RDV.itemUnit || '')],
        ['Tahmini Fiyat', RDV.estimatedPrice ? '₺' + RDV.estimatedPrice.toLocaleString('tr-TR') : '—'],
        ['Bölge',      RDV.areaName || (RDV_DATA.areas.length === 0 ? 'Belirtilmemiş' : '—')],
        ['Alım Tarihi', formatDate(RDV.pickupDate)],
        ['Alım Saati', SLOTS[RDV.pickupSlot] || '—'],
        ['Ad Soyad',   RDV.name],
        ['Telefon',    RDV.phone],
        ['Adres',      RDV.address || '—'],
        ['Not',        RDV.notes   || '—'],
    ];
    var html = '';
    rows.forEach(function(r) {
        html += '<div class="summary-row"><span class="text-xs text-gray-400 font-medium">' + r[0] + '</span>' +
                '<span class="text-sm font-semibold text-right">' + escH(String(r[1])) + '</span></div>';
    });
    document.getElementById('summaryContent').innerHTML = html;
}

function formatDate(str) {
    if (!str) return '—';
    var months = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
    var parts = str.split('-');
    if (parts.length !== 3) return str;
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0];
}

// ══ SUBMIT ══
async function submitReservation() {
    var btn = document.getElementById('submitBtn');
    var err = document.getElementById('step5Error');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-lg" style="animation:spin 1s linear infinite">sync</span> Gönderiliyor...';

    var body = {
        name:            RDV.name,
        phone:           RDV.phone,
        district:        RDV.areaName,
        address:         RDV.address,
        service_type:    RDV.serviceName,
        service_item:    RDV.serviceItem,
        quantity:        RDV.quantity || null,
        unit:            RDV.itemUnit,
        estimated_price: RDV.estimatedPrice || null,
        pickup_date:     RDV.pickupDate,
        pickup_slot:     RDV.pickupSlot,
        notes:           RDV.notes,
    };

    try {
        var res = await fetch('/api/reservations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        var data = await res.json();
        if (res.ok && data.success) {
            var waMsg = buildWaMessage();
            var waUrl = 'https://wa.me/' + RDV_DATA.wa + '?text=' + encodeURIComponent(waMsg);
            document.getElementById('waLinkSuccess').href = waUrl;

            hideStep(5);
            document.getElementById('step-success').classList.remove('hidden');
            document.getElementById('stepIndicator').classList.add('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Open WA
            setTimeout(function() { window.open(waUrl, '_blank'); }, 600);
        } else {
            showErr(err, data.error || 'Randevu kaydedilemedi. Lütfen tekrar deneyin.');
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" style="width:1.2em;height:1.2em;fill:currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> WhatsApp ile Onayla';
        }
    } catch(e) {
        showErr(err, 'Sunucu bağlantısı kurulamadı.');
        btn.disabled = false;
        btn.innerHTML = '<svg viewBox="0 0 24 24" style="width:1.2em;height:1.2em;fill:currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> WhatsApp ile Onayla';
    }
}

function buildWaMessage() {
    var SLOTS = { sabah: 'Sabah (08:00-12:00)', oglen: 'Öğlen (12:00-17:00)', aksam: 'Akşam (17:00-20:00)' };
    var lines = [
        'Merhaba ' + RDV_DATA.bizName + ', online randevu formu üzerinden randevu almak istiyorum.',
        '',
        '📋 *Randevu Bilgileri*',
        '👤 Ad Soyad: ' + RDV.name,
        '📞 Telefon: ' + RDV.phone,
        '🧹 Hizmet: ' + RDV.serviceName + (RDV.serviceItem ? ' – ' + RDV.serviceItem : ''),
        '📐 Miktar: ' + RDV.quantity + ' ' + (RDV.itemUnit || ''),
        (RDV.estimatedPrice ? '💰 Tahmini: ₺' + RDV.estimatedPrice.toLocaleString('tr-TR') : ''),
        (RDV.areaName ? '📍 Bölge: ' + RDV.areaName : ''),
        '📅 Alım Tarihi: ' + formatDate(RDV.pickupDate),
        '⏰ Saat: ' + (SLOTS[RDV.pickupSlot] || '—'),
        (RDV.address ? '🏠 Adres: ' + RDV.address : ''),
        (RDV.notes ? '📝 Not: ' + RDV.notes : ''),
    ];
    return lines.filter(function(l) { return l !== ''; }).join('\n');
}

// XSS helper
function escH(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// Scroll top
function scrollTopSetup() {
    window.addEventListener('scroll', function() {
        var btn = document.getElementById('scrollTopBtn');
        if (btn) btn.classList.toggle('visible', window.scrollY > 400);
    });
}
</script>
</body>
</html>
