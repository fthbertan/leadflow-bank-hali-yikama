<?php
// LeadFlow — Messages API (PHP + MySQL)
// POST   /api/messages.php          → Public, yeni mesaj kaydeder + e-posta gonderir
// GET    /api/messages.php          → Auth gerekli, tum mesajlari dondurur
// GET    /api/messages.php?unread=1 → Auth gerekli, okunmamis sayisini dondurur
// PUT    /api/messages.php          → Auth gerekli, okundu durumunu gunceller
// DELETE /api/messages.php?id=x     → Auth gerekli, mesaj siler

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── POST — Public (iletisim formu) ──
if ($method === 'POST') {
    // Rate limiting — IP başına dakikada 3 mesaj
    $rateLimitDir = __DIR__ . '/../data/';
    if (!is_dir($rateLimitDir)) @mkdir($rateLimitDir, 0755, true);
    $rateLimitFile = $rateLimitDir . '.msg_rate_limit.json';
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ipHash = hash('sha256', $clientIp . date('Y-m-d-H'));
    $rateData = [];
    if (file_exists($rateLimitFile)) {
        $rateData = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    // Eski kayıtları temizle (1 saatten eski)
    foreach ($rateData as $k => $v) {
        if (isset($v['time']) && time() - $v['time'] > 3600) unset($rateData[$k]);
    }
    $entry = $rateData[$ipHash] ?? ['count' => 0, 'time' => time()];
    if (time() - $entry['time'] > 60) {
        $entry = ['count' => 0, 'time' => time()];
    }
    if ($entry['count'] >= 3) {
        jsonResponse(['error' => 'Çok fazla mesaj gönderdiniz. Lütfen biraz bekleyin.'], 429);
    }
    $entry['count']++;
    $rateData[$ipHash] = $entry;
    @file_put_contents($rateLimitFile, json_encode($rateData));

    $data = getJsonBody();

    // ── Honeypot kontrolü ──
    if (!empty($data['website'])) {
        // Bot yakalandı — sessizce başarılı gibi döndür
        jsonResponse(['success' => true]);
    }

    // ── Turnstile CAPTCHA doğrulama ──
    $turnstileResponse = trim($data['cf-turnstile-response'] ?? '');
    if (defined('TURNSTILE_SECRET_KEY') && TURNSTILE_SECRET_KEY !== '' && TURNSTILE_SECRET_KEY !== '___TURNSTILE_SECRET_KEY___') {
        if ($turnstileResponse === '') {
            jsonResponse(['error' => 'Güvenlik doğrulaması gerekli. Lütfen sayfayı yenileyip tekrar deneyin.'], 422);
        }
        $verifyData = [
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $turnstileResponse,
            'remoteip' => $clientIp,
        ];
        $verifyOpts = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($verifyData),
                'timeout' => 5,
            ]
        ];
        $verifyResult = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create($verifyOpts));
        $verifyJson = $verifyResult ? json_decode($verifyResult, true) : null;
        if (!$verifyJson || empty($verifyJson['success'])) {
            jsonResponse(['error' => 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.'], 422);
        }
    }

    // ── Sanitization ──
    $name    = mb_substr(trim($data['name'] ?? ''), 0, 100);
    $phone   = mb_substr(trim($data['phone'] ?? ''), 0, 20);
    $service = mb_substr(trim($data['service'] ?? ''), 0, 200);
    $date    = mb_substr(trim($data['date'] ?? ''), 0, 10);
    $time    = mb_substr(trim($data['time'] ?? ''), 0, 5);
    $notes   = mb_substr(trim($data['notes'] ?? ''), 0, 1000);

    if ($name === '') {
        jsonResponse(['error' => 'İsim alanı zorunlu'], 400);
    }

    // ── Telefon validasyonu (TR formatı) ──
    $phoneDigits = preg_replace('/\D/', '', $phone);
    if ($phone !== '' && !preg_match('/^(0?5\d{9}|905\d{9})$/', $phoneDigits)) {
        jsonResponse(['error' => 'Geçerli bir telefon numarası girin (05XX XXX XX XX).'], 400);
    }

    $stmt = $db->prepare('
        INSERT INTO messages (name, phone, service, preferred_date, preferred_time, notes)
        VALUES (:name, :phone, :service, :date, :time, :notes)
    ');
    $stmt->execute([
        ':name'    => $name,
        ':phone'   => $phone,
        ':service' => $service,
        ':date'    => $date,
        ':time'    => $time,
        ':notes'   => $notes,
    ]);

    // E-posta bildirimi gonder (best-effort)
    try {
        sendNotificationEmail($db, $name, $phone, $service, $date, $time, $notes);
    } catch (Exception $e) {
        // E-posta gonderilemese bile mesaj kaydedildi, sessizce devam et
    }

    jsonResponse(['success' => true]);
}

// ── GET — Admin (tum mesajlar veya okunmamis sayisi) ──
if ($method === 'GET') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    // Sadece okunmamis sayisi
    if (isset($_GET['unread'])) {
        $count = $db->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
        jsonResponse(['unread' => (int)$count]);
    }

    $stmt = $db->query('SELECT * FROM messages ORDER BY created_at DESC');
    $messages = $stmt->fetchAll();
    jsonResponse(['messages' => $messages]);
}

// ── PUT — Admin (okundu/okunmadi guncelle) ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $id = $data['id'] ?? null;
    $isRead = $data['is_read'] ?? null;

    if (!$id || $isRead === null) {
        jsonResponse(['error' => 'id ve is_read alanlari zorunlu'], 400);
    }

    $stmt = $db->prepare('UPDATE messages SET is_read = :is_read WHERE id = :id');
    $stmt->execute([':is_read' => (int)$isRead, ':id' => (int)$id]);

    jsonResponse(['success' => true]);
}

// ── DELETE — Admin ──
if ($method === 'DELETE') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'id parametresi zorunlu'], 400);
    }

    $stmt = $db->prepare('DELETE FROM messages WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
