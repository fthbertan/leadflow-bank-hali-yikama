<?php
// LeadFlow — Settings API (PHP + MySQL)
// GET  /api/settings.php        → Public, tüm ayarları döndürür
// PUT  /api/settings.php        → Auth gerekli, ayarları günceller

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

// ── GET — Herkese açık (hassas alanlar filtrelenir) ──
if ($method === 'GET') {
    $db = getDB();
    $stmt = $db->query('SELECT setting_key, setting_value FROM settings');
    $rows = $stmt->fetchAll();

    // Hassas ayarları gizle (admin credential hash'leri, token'lar)
    $sensitiveKeys = [
        'admin_password_hash', 'admin_user_hash', 'admin_email_hashes',
        'admin_emails_display', 'api_token'
    ];

    $settings = [];
    foreach ($rows as $row) {
        if (!in_array($row['setting_key'], $sensitiveKeys)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    jsonResponse($settings);
}

// ── PUT — Sadece admin ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    if (!$data || !is_array($data)) {
        jsonResponse(['error' => 'Gecersiz veri'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare('REPLACE INTO settings (setting_key, setting_value) VALUES (:k, :v)');

    foreach ($data as $key => $value) {
        $stmt->execute([':k' => $key, ':v' => $value]);
    }

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
