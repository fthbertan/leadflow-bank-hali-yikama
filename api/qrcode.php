<?php
// LeadFlow — QR Code API (PHP + MySQL)
// GET /api/qrcode.php?text=URL&size=300 → PNG QR kod goruntusunu dondurur
// QR Server API kullanir (harici kutuphane gerektirmez)

require_once __DIR__ . '/config.php';

$text = $_GET['text'] ?? '';
$size = min(500, max(100, (int)($_GET['size'] ?? 300)));

if (empty($text)) {
    setCorsHeaders();
    jsonResponse(['error' => 'text parametresi zorunlu'], 400);
}

// QR Server API (ucretsiz, guvenilir)
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($text) . '&format=png&margin=10';

// Proxy: QR goruntusunu sunucu uzerinden getir (CSP uyumluluk icin)
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'LeadFlow/1.0'
    ]
]);

$imageData = @file_get_contents($qrUrl, false, $context);

if ($imageData === false) {
    // Fallback: dogrudan yonlendir
    header('Location: ' . $qrUrl);
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
echo $imageData;
