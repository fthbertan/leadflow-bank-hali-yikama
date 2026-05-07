<?php
// LeadFlow — Special Days API (PHP + MySQL)
// GET    /api/special_days.php          → Public, ozel gunleri/tatilleri dondurur
// GET    /api/special_days.php?today=1  → Public, bugun acik mi kontrol eder
// PUT    /api/special_days.php          → Auth gerekli, ozel gunleri gunceller (delete all + insert)

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Public ──
if ($method === 'GET') {
    // Bugun acik mi kontrolu
    if (isset($_GET['today'])) {
        $today = date('Y-m-d');
        $dayOfWeek = (int)date('w'); // 0=Pazar, 6=Cumartesi

        // Ozel gun kontrolu
        $stmt = $db->prepare('SELECT * FROM special_days WHERE date = :d');
        $stmt->execute([':d' => $today]);
        $specialDay = $stmt->fetch();

        if ($specialDay) {
            jsonResponse([
                'is_open' => !((int)$specialDay['is_closed']),
                'reason' => $specialDay['title'],
                'is_special_day' => true,
            ]);
        }

        // Pazar kapali (varsayilan)
        $isOpen = ($dayOfWeek !== 0);
        jsonResponse([
            'is_open' => $isOpen,
            'reason' => $isOpen ? '' : 'Pazar günü kapalıyız',
            'is_special_day' => false,
        ]);
    }

    // Tum ozel gunler
    $stmt = $db->query('SELECT * FROM special_days ORDER BY date ASC');
    $days = $stmt->fetchAll();
    jsonResponse(['special_days' => $days]);
}

// ── PUT — Admin ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $days = $data['special_days'] ?? [];

    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM special_days');
        $stmt = $db->prepare('INSERT INTO special_days (date, title, is_closed) VALUES (:d, :t, :c)');
        foreach ($days as $d) {
            if (empty($d['date']) || empty($d['title'])) continue;
            $stmt->execute([
                ':d' => $d['date'],
                ':t' => trim($d['title']),
                ':c' => isset($d['is_closed']) ? (int)$d['is_closed'] : 1,
            ]);
        }
        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Kaydetme hatasi: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
