<?php
// LeadFlow — Services API (PHP + MySQL)
// GET  /api/services.php   → Public, tüm hizmetleri + fiyat kalemlerini döndürür
// PUT  /api/services.php   → Auth gerekli, hizmetleri + kalemlerini günceller

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Herkese açık ──
if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC');
    $rows = $stmt->fetchAll();

    // service_items tablosu var mı kontrol et
    $hasItems = false;
    try {
        $db->query('SELECT 1 FROM service_items LIMIT 1');
        $hasItems = true;
    } catch (Exception $e) {}

    $allItems = [];
    if ($hasItems) {
        $allItems = $db->query('SELECT * FROM service_items ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    $services = array_map(function($r) use ($allItems) {
        $svcId = (int)$r['id'];
        $items = array_values(array_filter($allItems, function($item) use ($svcId) {
            return (int)$item['service_id'] === $svcId;
        }));
        return [
            'id'    => $r['id'],
            'name'  => $r['title'],
            'desc'  => $r['description'],
            'icon'  => $r['icon'],
            'image' => $r['image'],
            'price' => $r['price'],
            'sort_order' => $r['sort_order'],
            'items' => array_map(function($item) {
                return [
                    'id'          => $item['id'],
                    'name'        => $item['name'],
                    'description' => $item['description'],
                    'price'       => $item['price'],
                    'unit'        => $item['unit'],
                    'sort_order'  => $item['sort_order'],
                ];
            }, $items)
        ];
    }, $rows);
    jsonResponse(['services' => $services]);
}

// ── PUT — Tüm services array'i güncelle ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $services = $data['services'] ?? [];

    $db->beginTransaction();
    try {
        // service_items tablosu var mı kontrol et
        $hasItems = false;
        try {
            $db->query('SELECT 1 FROM service_items LIMIT 1');
            $hasItems = true;
        } catch (Exception $e) {}

        if ($hasItems) {
            $db->exec('DELETE FROM service_items');
        }
        $db->exec('DELETE FROM services');

        $stmt = $db->prepare('
            INSERT INTO services (title, description, icon, image, price, sort_order)
            VALUES (:title, :description, :icon, :image, :price, :sort_order)
        ');

        $itemStmt = null;
        if ($hasItems) {
            $itemStmt = $db->prepare('
                INSERT INTO service_items (service_id, name, description, price, unit, sort_order)
                VALUES (:service_id, :name, :description, :price, :unit, :sort_order)
            ');
        }

        foreach ($services as $i => $svc) {
            $stmt->execute([
                ':title'       => $svc['name'] ?? $svc['title'] ?? '',
                ':description' => $svc['desc'] ?? $svc['description'] ?? '',
                ':icon'        => $svc['icon'] ?? '',
                ':image'       => $svc['image'] ?? '',
                ':price'       => $svc['price'] ?? '',
                ':sort_order'  => $i,
            ]);
            $svcId = $db->lastInsertId();

            // Fiyat kalemlerini kaydet
            if ($itemStmt && isset($svc['items']) && is_array($svc['items'])) {
                foreach ($svc['items'] as $ii => $item) {
                    $itemStmt->execute([
                        ':service_id'  => $svcId,
                        ':name'        => $item['name'] ?? '',
                        ':description' => $item['description'] ?? '',
                        ':price'       => $item['price'] ?? '',
                        ':unit'        => $item['unit'] ?? '',
                        ':sort_order'  => $ii,
                    ]);
                }
            }
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
