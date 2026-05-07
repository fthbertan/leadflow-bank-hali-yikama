<?php
// LeadFlow — Authentication API (PHP + MySQL)
// POST /api/auth.php              → Login (e-posta + kullanıcı adı + şifre)
// POST /api/auth.php?action=logout → Logout (session temizle)
// GET  /api/auth.php              → Session kontrolü (giriş yapılmış mı?)

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ══════════════════════════════════════
// RATE LIMITING — Brute-force koruması
// ══════════════════════════════════════
function getRateLimitFile() {
    $dir = __DIR__ . '/../data/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir . '.rate_limit.json';
}

function checkRateLimit($ip) {
    $file = getRateLimitFile();
    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: [];
    }

    $ipHash = hash('sha256', $ip . date('Y-m-d'));
    $entry = $data[$ipHash] ?? ['attempts' => 0, 'locked_until' => 0];

    // Kilit süresi dolmuşsa sıfırla
    if ($entry['locked_until'] > 0 && time() > $entry['locked_until']) {
        $entry = ['attempts' => 0, 'locked_until' => 0];
        $data[$ipHash] = $entry;
        file_put_contents($file, json_encode($data));
    }

    if ($entry['locked_until'] > 0 && time() < $entry['locked_until']) {
        $remaining = $entry['locked_until'] - time();
        return ['blocked' => true, 'remaining' => $remaining, 'attempts' => $entry['attempts']];
    }

    return ['blocked' => false, 'attempts' => $entry['attempts']];
}

function recordFailedAttempt($ip) {
    $file = getRateLimitFile();
    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: [];
    }

    $ipHash = hash('sha256', $ip . date('Y-m-d'));
    $entry = $data[$ipHash] ?? ['attempts' => 0, 'locked_until' => 0];
    $entry['attempts']++;

    // Kademeli kilitleme: 5=30sn, 10=2dk, 15=5dk, 20+=15dk
    if ($entry['attempts'] >= 5) {
        $lockDurations = [30, 120, 300, 900];
        $lockIndex = min(intdiv($entry['attempts'] - 5, 5), count($lockDurations) - 1);
        $entry['locked_until'] = time() + $lockDurations[$lockIndex];
    }

    $data[$ipHash] = $entry;

    // Eski kayıtları temizle (1 günden eski)
    foreach ($data as $key => $val) {
        if (isset($val['locked_until']) && $val['locked_until'] > 0 && time() - $val['locked_until'] > 86400) {
            unset($data[$key]);
        }
    }

    file_put_contents($file, json_encode($data));
    return $entry['attempts'];
}

function clearRateLimit($ip) {
    $file = getRateLimitFile();
    if (!file_exists($file)) return;
    $data = json_decode(file_get_contents($file), true) ?: [];
    $ipHash = hash('sha256', $ip . date('Y-m-d'));
    unset($data[$ipHash]);
    file_put_contents($file, json_encode($data));
}

// ══════════════════════════════════════
// HASH FONKSİYONLARI (JS ile aynı algoritma)
// ══════════════════════════════════════
function hashPassword($pass) {
    $salt = 'leadflow_2026_secure_salt';
    return 'lf$' . hash('sha256', $salt . $pass . $salt);
}

function hashUser($user) {
    $salt = 'leadflow_user_verify_2026';
    return 'us$' . hash('sha256', $salt . trim($user) . $salt);
}

function hashEmail($email) {
    $salt = 'leadflow_email_verify_2026';
    return 'em$' . hash('sha256', $salt . strtolower(trim($email)) . $salt);
}

// ══════════════════════════════════════
// SESSION YÖNETİMİ
// ══════════════════════════════════════
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => 86400,        // 24 saat
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly'  => true,         // JavaScript erişemez
            'samesite'  => 'Strict',    // CSRF koruması
        ]);
        session_name('lf_session');
        session_start();
    }
}

// ── GET — Session kontrolü veya email listesi ──
if ($method === 'GET') {
    startSecureSession();

    // E-posta listesi getir (auth gerekli)
    if ($action === 'get_emails') {
        if (!isset($_SESSION['lf_authenticated']) || $_SESSION['lf_authenticated'] !== true) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_emails_display'");
        $stmt->execute();
        $emailsJson = $stmt->fetchColumn();
        $emails = $emailsJson ? (json_decode($emailsJson, true) ?: []) : [];
        jsonResponse(['emails' => $emails]);
    }

    // Normal session kontrolü
    if (isset($_SESSION['lf_authenticated']) && $_SESSION['lf_authenticated'] === true) {
        // Session süresi kontrolü (24 saat)
        if (isset($_SESSION['lf_login_time']) && (time() - $_SESSION['lf_login_time']) < 86400) {
            jsonResponse([
                'authenticated' => true,
                'user' => $_SESSION['lf_user'] ?? 'admin'
            ]);
        }
        // Session süresi dolmuş
        session_destroy();
    }
    jsonResponse(['authenticated' => false], 401);
}

// ── POST — Login, Logout, Credential Change, Email Update ──
if ($method === 'POST') {

    // ── Credential değiştirme (kullanıcı adı / şifre) ──
    if ($action === 'change_credentials') {
        startSecureSession();
        if (!isset($_SESSION['lf_authenticated']) || $_SESSION['lf_authenticated'] !== true) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = getJsonBody();
        $db = getDB();
        $messages = [];

        // Kullanıcı adı değiştirme
        if (!empty($data['new_user'])) {
            $newUser = trim($data['new_user']);
            if (strlen($newUser) < 3) {
                jsonResponse(['error' => 'Kullanıcı adı en az 3 karakter olmalı'], 400);
            }
            $newUserHash = hashUser($newUser);
            $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('admin_user_hash', :v)");
            $stmt->execute([':v' => $newUserHash]);
            $messages[] = 'Kullanıcı adı güncellendi';
        }

        // Şifre değiştirme
        if (!empty($data['new_pass'])) {
            $oldPass = $data['old_pass'] ?? '';
            $newPass = $data['new_pass'];

            if (strlen($newPass) < 8) {
                jsonResponse(['error' => 'Yeni şifre en az 8 karakter olmalı'], 400);
            }

            // Mevcut şifreyi doğrula
            $stmtCur = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_password_hash'");
            $stmtCur->execute();
            $currentHash = $stmtCur->fetchColumn();
            if (empty($currentHash)) $currentHash = ADMIN_DEFAULT_PASS_HASH;

            $oldHash = hashPassword($oldPass);
            if ($oldHash !== $currentHash) {
                jsonResponse(['error' => 'Mevcut şifre hatalı'], 403);
            }

            $newHash = hashPassword($newPass);
            $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('admin_password_hash', :v)");
            $stmt->execute([':v' => $newHash]);
            $messages[] = 'Şifre güncellendi';
        }

        jsonResponse(['success' => true, 'message' => implode(', ', $messages)]);
    }

    // ── E-posta listesi güncelleme ──
    if ($action === 'update_emails') {
        startSecureSession();
        if (!isset($_SESSION['lf_authenticated']) || $_SESSION['lf_authenticated'] !== true) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = getJsonBody();
        $emails = $data['emails'] ?? [];
        $db = getDB();

        // E-postaları kaydet (görüntüleme için)
        $cleanEmails = array_map(function($e) { return strtolower(trim($e)); }, $emails);
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('admin_emails_display', :v)");
        $stmt->execute([':v' => json_encode($cleanEmails)]);

        // Hash'leri kaydet (doğrulama için)
        $hashes = array_map(function($e) { return hashEmail($e); }, $cleanEmails);
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('admin_email_hashes', :v)");
        $stmt->execute([':v' => json_encode($hashes)]);

        jsonResponse(['success' => true]);
    }

    // ── Logout ──
    if ($action === 'logout') {
        startSecureSession();
        $_SESSION = [];
        session_destroy();

        // Session cookie'yi temizle
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        jsonResponse(['success' => true]);
    }

    // ── Login ──
    $data = getJsonBody();
    $email = trim($data['email'] ?? '');
    $user  = trim($data['user'] ?? '');
    $pass  = $data['pass'] ?? '';

    if ($user === '' || $pass === '') {
        jsonResponse(['error' => 'Kullanıcı adı ve şifre zorunlu'], 400);
    }

    // Rate limit kontrolü
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $rateCheck = checkRateLimit($clientIp);
    if ($rateCheck['blocked']) {
        jsonResponse([
            'error' => 'Çok fazla başarısız deneme. Lütfen bekleyin.',
            'locked' => true,
            'remaining' => $rateCheck['remaining']
        ], 429);
    }

    // Giriş bilgilerini doğrula
    $db = getDB();

    // DB'den kayıtlı hash'leri al
    $stmtPass = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_password_hash'");
    $stmtPass->execute();
    $storedPassHash = $stmtPass->fetchColumn();

    $stmtUser = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_user_hash'");
    $stmtUser->execute();
    $storedUserHash = $stmtUser->fetchColumn();

    $stmtEmails = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_email_hashes'");
    $stmtEmails->execute();
    $storedEmailHashesJson = $stmtEmails->fetchColumn();

    // Default hash'ler (DB'de kayıtlı yoksa, yani ilk kurulumda)
    $defaultPassHash = ADMIN_DEFAULT_PASS_HASH;
    $defaultUserHash = ADMIN_DEFAULT_USER_HASH;
    $defaultEmailHashes = json_decode(ADMIN_DEFAULT_EMAIL_HASHES, true) ?: [];

    // Aktif hash'leri belirle (DB override > default)
    $activePassHash = (!empty($storedPassHash)) ? $storedPassHash : $defaultPassHash;
    $activeUserHash = (!empty($storedUserHash)) ? $storedUserHash : $defaultUserHash;
    $activeEmailHashes = [];
    if (!empty($storedEmailHashesJson)) {
        $activeEmailHashes = json_decode($storedEmailHashesJson, true) ?: [];
    }
    if (empty($activeEmailHashes)) {
        $activeEmailHashes = $defaultEmailHashes;
    }

    // Hash hesapla
    $inputPassHash = hashPassword($pass);
    $inputUserHash = hashUser($user);
    $inputEmailHash = hashEmail($email);

    // Şifre ve kullanıcı adı kontrolü
    $passMatch = ($inputPassHash === $activePassHash);
    $userMatch = ($inputUserHash === $activeUserHash);

    // E-posta kontrolü (e-posta tanımlı değilse atla)
    $emailMatch = true;
    if (!empty($activeEmailHashes)) {
        $emailMatch = in_array($inputEmailHash, $activeEmailHashes);
    }

    if ($passMatch && $userMatch && $emailMatch) {
        // Başarılı giriş
        clearRateLimit($clientIp);
        startSecureSession();
        // Mevcut session'ı yenile (session fixation önleme)
        session_regenerate_id(true);

        $_SESSION['lf_authenticated'] = true;
        $_SESSION['lf_user'] = $user;
        $_SESSION['lf_login_time'] = time();
        $_SESSION['lf_ip'] = $clientIp;

        jsonResponse(['success' => true, 'user' => $user]);
    }

    // Başarısız giriş
    $attempts = recordFailedAttempt($clientIp);
    $response = ['error' => 'E-posta, kullanıcı adı veya şifre hatalı', 'attempts' => $attempts];

    if ($attempts >= 5) {
        $lockDurations = [30, 120, 300, 900];
        $lockIndex = min(intdiv($attempts - 5, 5), count($lockDurations) - 1);
        $response['locked'] = true;
        $response['remaining'] = $lockDurations[$lockIndex];
    }

    jsonResponse($response, 401);
}

jsonResponse(['error' => 'Method not allowed'], 405);
