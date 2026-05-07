<?php
// LeadFlow — Veritabanı Bağlantı Ayarları
// Bu değerler hosting cPanel'den alınır (MySQL Veritabanları bölümü)

// ══════════════════════════════════════
// AYARLAR — Her müşteri sitesi için değiştir
// ══════════════════════════════════════
define('DB_HOST', '{{DB_HOST}}');         // Genelde: localhost
define('DB_NAME', '{{DB_NAME}}');         // cPanel'de oluşturduğun DB adı
define('DB_USER', '{{DB_USER}}');         // cPanel'de oluşturduğun DB kullanıcısı
define('DB_PASS', '{{DB_PASS}}');         // DB kullanıcı şifresi

// Admin API güvenlik token'ı — sadece sunucu tarafında kullanılır (JS'e gönderilmez)
define('ADMIN_API_TOKEN', '7075e9c1ece1ddab5bf2803d4955cd3b0bb906417c833af4d7bd0e21f791ea83');

// Default credential hash'leri — ilk kurulumda kullanılır, panelden değiştirilebilir
// Python site_generator.py tarafından önceden hesaplanıp gömülür
define('ADMIN_DEFAULT_PASS_HASH', 'lf$ee5c44c6863e1656c347cfa429172b393b6c3d7e2db75e1ca8bf1b65ad5365ea');
define('ADMIN_DEFAULT_USER_HASH', 'us$10887d68b711789164025ca747b708a5a2198ed7c89bfa069b18d25e6f25cf39');
define('ADMIN_DEFAULT_EMAIL_HASHES', '[]');

// ══════════════════════════════════════
// SMTP AYARLARI (Bildirim E-postaları)
// ══════════════════════════════════════
define('SMTP_HOST', '___SMTP_HOST___');         // mail.domain.com.tr
define('SMTP_PORT', 465);                        // SSL
define('SMTP_USER', '___SMTP_USER___');          // noreply@domain.com.tr
define('SMTP_PASS', '___SMTP_PASS___');          // SMTP şifresi
define('SMTP_FROM_NAME', '{{BUSINESS_NAME}}');

// ══════════════════════════════════════
// CLOUDFLARE TURNSTILE (Spam Koruması)
// ══════════════════════════════════════
define('TURNSTILE_SITE_KEY', '___TURNSTILE_SITE_KEY___');       // Frontend widget için (public)
define('TURNSTILE_SECRET_KEY', '___TURNSTILE_SECRET_BURAYA___'); // Backend doğrulama için (gizli)

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

// ══════════════════════════════════════
// E-POSTA GÖNDERİMİ (SMTP)
// ══════════════════════════════════════
function sendNotificationEmail($to, $subject, $htmlBody) {
    if (!$to || !defined('SMTP_HOST') || SMTP_PASS === '___SMTP_PASS___') return false;

    $toList = is_array($to) ? $to : array_map('trim', explode(',', $to));
    $toList = array_filter($toList, function($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); });
    if (empty($toList)) return false;

    // Tüm header'ları tek string olarak oluştur (Date + Message-ID zorunlu, yoksa Gmail düşürür)
    $messageId = '<' . bin2hex(random_bytes(16)) . '@' . SMTP_HOST . '>';
    $msg  = "Date: " . gmdate('D, d M Y H:i:s +0000') . "\r\n";
    $msg .= "Message-ID: " . $messageId . "\r\n";
    $msg .= "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n";
    $msg .= "To: " . implode(', ', $toList) . "\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "Reply-To: " . SMTP_USER . "\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "\r\n"; // header/body ayırıcı
    $msg .= $htmlBody . "\r\n.\r\n";

    // SMTP socket ile gönder
    $smtp = @fsockopen('ssl://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    if (!$smtp) return false;

    // Çok satırlı SMTP yanıtlarını tam oku (220-, 250- gibi devam satırları)
    $readResp = function() use ($smtp) {
        $full = '';
        while ($line = fgets($smtp, 515)) {
            $full .= $line;
            if (isset($line[3]) && $line[3] !== '-') break;
            if (strlen($line) < 4) break;
        }
        return $full;
    };
    $send = function($cmd) use ($smtp, $readResp) {
        fwrite($smtp, $cmd . "\r\n");
        return $readResp();
    };

    $readResp(); // banner (çok satırlı olabilir)
    $send('EHLO ' . SMTP_HOST); // EHLO yanıtı (çok satırlı)

    $send('AUTH LOGIN');
    $send(base64_encode(SMTP_USER));
    $authResp = $send(base64_encode(SMTP_PASS));
    if (strpos($authResp, '235') === false) { fclose($smtp); return false; }

    $send('MAIL FROM:<' . SMTP_USER . '>');
    foreach ($toList as $rcpt) { $send('RCPT TO:<' . $rcpt . '>'); }
    $send('DATA');
    fwrite($smtp, $msg); // Tüm header + body tek fwrite
    $result = $readResp();
    $send('QUIT');
    fclose($smtp);

    return strpos($result, '250') !== false;
}
