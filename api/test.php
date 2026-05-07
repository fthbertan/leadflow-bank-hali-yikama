<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

// Login'i simüle et
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://bankhaliyikama.com.tr/api/auth.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => '',
    'user' => 'admin',
    'pass' => 'admin123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Login test:</h3>";
echo "HTTP Code: " . $httpCode . "<br>";
if ($error) echo "cURL Error: " . $error . "<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
