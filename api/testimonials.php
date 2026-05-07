<?php
// LeadFlow — Testimonials API (PHP + MySQL)
// GET  /api/testimonials.php   → Public, tüm yorumları döndürür
// PUT  /api/testimonials.php   → Auth gerekli, yorumları günceller

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Herkese açık ──
if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC');
    $testimonials = $stmt->fetchAll();
    jsonResponse(['testimonials' => $testimonials]);
}

// ── PUT — Tüm testimonials array'i güncelle ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $testimonials = $data['testimonials'] ?? [];

    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM testimonials');

        $stmt = $db->prepare('
            INSERT INTO testimonials (name, role, rating, text, date, sort_order)
            VALUES (:name, :role, :rating, :text, :date, :sort_order)
        ');

        foreach ($testimonials as $i => $t) {
            $stmt->execute([
                ':name'       => $t['name'] ?? '',
                ':role'       => $t['role'] ?? '',
                ':rating'     => (int)($t['rating'] ?? 5),
                ':text'       => $t['text'] ?? '',
                ':date'       => $t['date'] ?? '',
                ':sort_order' => $i,
            ]);
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
