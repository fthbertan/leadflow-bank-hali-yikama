<?php
// LeadFlow — Visitor Tracking API (PHP + MySQL)
// POST /api/track.php       → Public, ziyaretci kaydeder (tracking pixel)
// GET  /api/track.php       → Auth gerekli, istatistikleri dondurur
// GET  /api/track.php?pixel → 1x1 transparent GIF dondurur + kayit yapar

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET with ?pixel — Tracking Pixel (1x1 GIF) ──
if ($method === 'GET' && isset($_GET['pixel'])) {
    // Ziyaretci kaydet
    $page = trim($_GET['page'] ?? '/');
    $ipRaw = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = hash('sha256', $ipRaw . date('Y-m-d')); // Gunluk hash, KVKK uyumlu
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $referrer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);

    // Bot filtresi
    $botPatterns = ['bot', 'crawl', 'spider', 'slurp', 'feed', 'index'];
    $isBot = false;
    foreach ($botPatterns as $p) {
        if (stripos($ua, $p) !== false) { $isBot = true; break; }
    }

    if (!$isBot && $ipRaw) {
        try {
            $stmt = $db->prepare('INSERT INTO visitors (page, ip_hash, user_agent, referrer) VALUES (:p, :h, :u, :r)');
            $stmt->execute([':p' => $page, ':h' => $ipHash, ':u' => $ua, ':r' => $referrer]);
        } catch (Exception $e) {
            // Sessizce devam et
        }
    }

    // 1x1 transparent GIF
    header('Content-Type: image/gif');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// ── POST — Public (JS tracking) ──
if ($method === 'POST') {
    setCorsHeaders();
    $data = getJsonBody();

    $page = trim($data['page'] ?? '/');
    $ipRaw = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = hash('sha256', $ipRaw . date('Y-m-d'));
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $referrer = substr($data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500);

    $stmt = $db->prepare('INSERT INTO visitors (page, ip_hash, user_agent, referrer) VALUES (:p, :h, :u, :r)');
    $stmt->execute([':p' => $page, ':h' => $ipHash, ':u' => $ua, ':r' => $referrer]);

    jsonResponse(['success' => true]);
}

// ── GET — Admin (istatistikler) ──
if ($method === 'GET') {
    setCorsHeaders();
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');

    // Bugun
    $todayCount = $db->prepare('SELECT COUNT(*) FROM visitors WHERE DATE(created_at) = :d');
    $todayCount->execute([':d' => $today]);
    $todayVisitors = (int)$todayCount->fetchColumn();

    // Bu hafta (Pazartesiden itibaren)
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekCount = $db->prepare('SELECT COUNT(*) FROM visitors WHERE DATE(created_at) >= :d');
    $weekCount->execute([':d' => $weekStart]);
    $weekVisitors = (int)$weekCount->fetchColumn();

    // Bu ay
    $monthStart = date('Y-m-01');
    $monthCount = $db->prepare('SELECT COUNT(*) FROM visitors WHERE DATE(created_at) >= :d');
    $monthCount->execute([':d' => $monthStart]);
    $monthVisitors = (int)$monthCount->fetchColumn();

    // Toplam
    $totalCount = $db->query('SELECT COUNT(*) FROM visitors')->fetchColumn();

    // Benzersiz ziyaretci (bugun, ip_hash bazli)
    $uniqueToday = $db->prepare('SELECT COUNT(DISTINCT ip_hash) FROM visitors WHERE DATE(created_at) = :d');
    $uniqueToday->execute([':d' => $today]);
    $uniqueTodayCount = (int)$uniqueToday->fetchColumn();

    // Son 14 gun — gunluk ziyaretci sayisi (bar grafik icin)
    $dailyStmt = $db->prepare('
        SELECT DATE(created_at) as day, COUNT(*) as count, COUNT(DISTINCT ip_hash) as unique_count
        FROM visitors
        WHERE DATE(created_at) >= DATE_SUB(:d, INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ');
    $dailyStmt->execute([':d' => $today]);
    $dailyData = $dailyStmt->fetchAll();

    // Son 14 gunu doldur (bos gunler icin 0)
    $daily = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $daily[$d] = ['day' => $d, 'count' => 0, 'unique_count' => 0];
    }
    foreach ($dailyData as $row) {
        if (isset($daily[$row['day']])) {
            $daily[$row['day']]['count'] = (int)$row['count'];
            $daily[$row['day']]['unique_count'] = (int)$row['unique_count'];
        }
    }

    // En cok ziyaret edilen sayfalar
    $topPages = $db->query('
        SELECT page, COUNT(*) as count
        FROM visitors
        GROUP BY page
        ORDER BY count DESC
        LIMIT 10
    ')->fetchAll();

    jsonResponse([
        'today' => $todayVisitors,
        'today_unique' => $uniqueTodayCount,
        'week' => $weekVisitors,
        'month' => $monthVisitors,
        'total' => (int)$totalCount,
        'daily' => array_values($daily),
        'top_pages' => $topPages,
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
