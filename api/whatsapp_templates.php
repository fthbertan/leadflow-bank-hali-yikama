<?php
// LeadFlow — WhatsApp Templates API (PHP + MySQL)
// GET    /api/whatsapp_templates.php   → Public, tum sablonlari dondurur
// PUT    /api/whatsapp_templates.php   → Auth gerekli, sablonlari gunceller (delete all + insert)

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Public ──
if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM whatsapp_templates ORDER BY sort_order ASC');
    $templates = $stmt->fetchAll();
    jsonResponse(['templates' => $templates]);
}

// ── PUT — Admin (tum sablonlari guncelle) ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $templates = $data['templates'] ?? [];

    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM whatsapp_templates');
        $stmt = $db->prepare('INSERT INTO whatsapp_templates (title, message, sort_order) VALUES (:t, :m, :s)');
        foreach ($templates as $i => $t) {
            $stmt->execute([
                ':t' => trim($t['title'] ?? ''),
                ':m' => trim($t['message'] ?? ''),
                ':s' => $i,
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
