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

    // ── Katman 1: Honeypot — bot doldurursa sessizce reddet (200 OK ile yanılt) ──
    if (!empty($data['website'])) {
        jsonResponse(['success' => true]);
    }

    // ── Katman 2: Cloudflare Turnstile doğrulama ──
    $turnstileToken = trim($data['cf-turnstile-response'] ?? '');
    if (defined('TURNSTILE_SECRET_KEY') && TURNSTILE_SECRET_KEY !== '___TURNSTILE_SECRET_BURAYA___') {
        if ($turnstileToken === '') {
            jsonResponse(['error' => 'Güvenlik doğrulaması tamamlanamadı. Lütfen sayfayı yenileyip tekrar deneyin.'], 403);
        }
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $verifyData = http_build_query([
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $turnstileToken,
            'remoteip' => $clientIp,
        ]);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $verifyData,
            'timeout' => 5,
        ]]);
        $verifyResult = @file_get_contents($verifyUrl, false, $ctx);
        if ($verifyResult) {
            $verifyJson = json_decode($verifyResult, true);
            if (empty($verifyJson['success'])) {
                jsonResponse(['error' => 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.'], 403);
            }
        }
        // Cloudflare'a erişilemezse sessizce geç (best-effort)
    }

    $name    = trim($data['name'] ?? '');
    $phone   = trim($data['phone'] ?? '');
    $service = trim($data['service'] ?? '');
    $date    = trim($data['date'] ?? '');
    $time    = trim($data['time'] ?? '');
    $notes   = trim($data['notes'] ?? '');

    // Zorunlu alan kontrolleri
    if ($name === '') {
        jsonResponse(['error' => 'Ad Soyad alanı zorunludur.'], 400);
    }
    if ($phone === '') {
        jsonResponse(['error' => 'Telefon alanı zorunludur.'], 400);
    }

    // TR telefon formatı: 05XX XXX XX XX (boşluk/tire/parantez opsiyonel, 10-11 hane)
    $phoneDigits = preg_replace('/\D/', '', $phone);
    if (!preg_match('/^(0?5\d{9}|905\d{9})$/', $phoneDigits)) {
        jsonResponse(['error' => 'Geçerli bir cep telefonu numarası giriniz (05XX XXX XX XX).'], 400);
    }

    // XSS / header injection sanitization
    $name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $phone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $service = htmlspecialchars($service, ENT_QUOTES, 'UTF-8');
    $notes   = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');

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

    // E-posta bildirimi gonder (SMTP — best-effort)
    try {
        $emailStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'email'");
        $emailStmt->execute();
        $toEmail = $emailStmt->fetchColumn();

        if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $subject = 'Yeni İletişim Talebi - ' . $name;
            $body  = "<h2 style='color:#333;'>Yeni İletişim Talebi</h2>";
            $body .= "<table style='border-collapse:collapse;width:100%;max-width:500px;'>";
            $body .= "<tr><td style='padding:8px;font-weight:bold;border-bottom:1px solid #eee;'>Ad Soyad</td><td style='padding:8px;border-bottom:1px solid #eee;'>" . $name . "</td></tr>";
            $body .= "<tr><td style='padding:8px;font-weight:bold;border-bottom:1px solid #eee;'>Telefon</td><td style='padding:8px;border-bottom:1px solid #eee;'>" . $phone . "</td></tr>";
            $body .= "<tr><td style='padding:8px;font-weight:bold;border-bottom:1px solid #eee;'>Hizmet</td><td style='padding:8px;border-bottom:1px solid #eee;'>" . ($service ?: '-') . "</td></tr>";
            if ($date) $body .= "<tr><td style='padding:8px;font-weight:bold;border-bottom:1px solid #eee;'>Tarih</td><td style='padding:8px;border-bottom:1px solid #eee;'>" . htmlspecialchars($date) . "</td></tr>";
            if ($time) $body .= "<tr><td style='padding:8px;font-weight:bold;border-bottom:1px solid #eee;'>Saat</td><td style='padding:8px;border-bottom:1px solid #eee;'>" . htmlspecialchars($time) . "</td></tr>";
            $body .= "<tr><td style='padding:8px;font-weight:bold;border-bottom:1px solid #eee;'>Mesaj</td><td style='padding:8px;border-bottom:1px solid #eee;'>" . ($notes ?: '-') . "</td></tr>";
            $body .= "</table>";
            $body .= "<hr style='margin:20px 0;border:none;border-top:1px solid #eee;'>";
            $body .= "<p style='color:#999;font-size:12px;'>Bu mesaj web sitesi iletişim formundan gönderildi.</p>";

            sendNotificationEmail($toEmail, $subject, $body);
        }
    } catch (Exception $e) {
        // E-posta gönderilemese bile mesaj DB'ye kaydedildi, sessizce devam et
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
