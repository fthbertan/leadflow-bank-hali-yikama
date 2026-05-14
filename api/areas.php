<?php
// LeadFlow — Areas API (PHP + MySQL)
// GET  /api/areas.php  → Public, tüm aktif hizmet bölgelerini döndürür
// PUT  /api/areas.php  → Auth gerekli, tüm bölgeleri siler ve yeniden ekler

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Herkese açık ──
if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM service_areas ORDER BY sort_order ASC, id ASC');
    $rows = $stmt->fetchAll();

    $areas = array_map(function($r) {
        $days = json_decode($r['available_days'], true);
        return [
            'id'             => (int)$r['id'],
            'name'           => $r['name'],
            'available_days' => is_array($days) ? $days : [],
            'is_active'      => (int)$r['is_active'],
            'sort_order'     => (int)$r['sort_order'],
        ];
    }, $rows);

    jsonResponse(['areas' => $areas]);
}

// ── PUT — Admin ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data  = getJsonBody();
    $areas = $data['areas'] ?? [];

    if (!is_array($areas)) {
        jsonResponse(['error' => 'areas dizisi gerekli'], 400);
    }

    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM service_areas');

        $stmt = $db->prepare('
            INSERT INTO service_areas (name, available_days, is_active, sort_order)
            VALUES (:name, :available_days, :is_active, :sort_order)
        ');

        foreach ($areas as $i => $area) {
            if (empty($area['name'])) continue;

            $days = $area['available_days'] ?? [];
            if (!is_array($days)) $days = [];
            // Sadece 0-6 arasındaki geçerli gün değerlerini kabul et
            $days = array_values(array_filter($days, function($d) {
                return is_int($d) && $d >= 0 && $d <= 6;
            }));

            $stmt->execute([
                ':name'           => trim($area['name']),
                ':available_days' => json_encode($days),
                ':is_active'      => isset($area['is_active']) ? (int)(bool)$area['is_active'] : 1,
                ':sort_order'     => $i,
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
