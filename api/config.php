<?php
// LeadFlow — Veritabanı Bağlantı Ayarları
// Bu değerler hosting cPanel'den alınır (MySQL Veritabanları bölümü)

// ══════════════════════════════════════
// AYARLAR — Her müşteri sitesi için değiştir
// ══════════════════════════════════════
define('DB_HOST', 'localhost');         // Genelde: localhost
define('DB_NAME', 'anka7332_bank_db');         // cPanel'de oluşturduğun DB adı
define('DB_USER', 'anka7332_bank_user');         // cPanel'de oluşturduğun DB kullanıcısı
define('DB_PASS', 'hb.3155197');         // DB kullanıcı şifresi

// Cloudflare Turnstile anahtarları (CAPTCHA)
define('TURNSTILE_SITE_KEY',   '0x4AAAAAADD0xuy_yCwNaJvi');   // Görünür — HTML'e gömülür
define('TURNSTILE_SECRET_KEY', '0x4AAAAAADD0xkeLC4QqN5fllzV4mcBAASs'); // Gizli — sadece sunucu tarafı

// Admin API güvenlik token'ı — sadece sunucu tarafında kullanılır (JS'e gönderilmez)
define('ADMIN_API_TOKEN', 'e88f6e0d181c98f844fc8b58ca9024cba5566528d42e531d971d6ddd57da8a7a');

// Default credential hash'leri — ilk kurulumda kullanılır, panelden değiştirilebilir
// Python site_generator.py tarafından önceden hesaplanıp gömülür
define('ADMIN_DEFAULT_PASS_HASH', 'lf$ee5c44c6863e1656c347cfa429172b393b6c3d7e2db75e1ca8bf1b65ad5365ea');
define('ADMIN_DEFAULT_USER_HASH', 'us$10887d68b711789164025ca747b708a5a2198ed7c89bfa069b18d25e6f25cf39');
define('ADMIN_DEFAULT_EMAIL_HASHES', '["em$129891864d6a556a622990208db33130429fa6dd8a2a2839380795c3e652f668"]');

// ══════════════════════════════════════
// PDO BAĞLANTISI
// ══════════════════════════════════════
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            echo json_encode(['error' => 'Veritabani baglantisi kurulamadi']);
            exit;
        }
    }
    return $pdo;
}

// ══════════════════════════════════════
// YARDIMCI FONKSİYONLAR
// ══════════════════════════════════════

// JSON yanıt gönder
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// CORS başlıkları — sadece aynı origin (dış erişim engellenir)
function setCorsHeaders() {
    // Same-origin istekler için CORS header gerekmez
    // Farklı origin'den gelen istekleri engelle
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Aynı site içi istekler veya origin yoksa (same-origin) izin ver
    if ($origin === '' || $origin === 'https://' . $host || $origin === 'http://' . $host) {
        if ($origin !== '') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
    }
    // Farklı origin'den gelen istekler CORS header almaz → tarayıcı bloklar

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('X-Content-Type-Options: nosniff');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Bearer token doğrulama (install.php ve import_blogs.php için — sadece sunucu tarafı)
function authCheck() {
    // Önce session-based auth dene (admin panelden gelen istekler)
    if (sessionAuthCheck()) {
        return true;
    }
    // Fallback: Bearer token (geriye uyumluluk — sadece server-side scriptler)
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    return $header === 'Bearer ' . ADMIN_API_TOKEN;
}

// Session-based authentication (admin panel istekleri)
function sessionAuthCheck() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => 86400,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly'  => true,
            'samesite'  => 'Strict',
        ]);
        session_name('lf_session');
        session_start();
    }

    if (isset($_SESSION['lf_authenticated']) && $_SESSION['lf_authenticated'] === true) {
        // 24 saat session süresi kontrolü
        if (isset($_SESSION['lf_login_time']) && (time() - $_SESSION['lf_login_time']) < 86400) {
            return true;
        }
        // Süresi dolmuş — temizle
        session_destroy();
    }
    return false;
}

// POST/PUT body'yi JSON olarak oku
function getJsonBody() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'Gecersiz JSON'], 400);
    }
    return $data;
}

// İletişim formu e-posta bildirimi
function sendNotificationEmail($db, $name, $phone, $service, $date, $time, $notes) {
    $emailStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'email'");
    $emailStmt->execute();
    $toEmail = $emailStmt->fetchColumn();

    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return;

    $subject = '=?UTF-8?B?' . base64_encode('Yeni İletişim Talebi - ' . $name) . '?=';
    $body  = "<h2>Yeni İletişim Talebi</h2>";
    $body .= "<p><strong>Ad Soyad:</strong> " . htmlspecialchars($name) . "</p>";
    $body .= "<p><strong>Telefon:</strong> " . htmlspecialchars($phone ?: '-') . "</p>";
    $body .= "<p><strong>Hizmet:</strong> " . htmlspecialchars($service ?: '-') . "</p>";
    $body .= "<p><strong>Tarih:</strong> " . htmlspecialchars($date ?: '-') . "</p>";
    $body .= "<p><strong>Saat:</strong> " . htmlspecialchars($time ?: '-') . "</p>";
    $body .= "<p><strong>Not:</strong> " . htmlspecialchars($notes ?: '-') . "</p>";
    $body .= "<hr><p><em>Bu mesaj web sitesi iletişim formundan gönderildi.</em></p>";

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@" . $host . "\r\n";

    @mail($toEmail, $subject, $body, $headers);
}
