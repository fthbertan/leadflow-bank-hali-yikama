<?php
// LeadFlow — Reservations API (PHP + MySQL)
// GET  /api/reservations.php           → Auth gerekli, tüm randevuları döndürür
// GET  /api/reservations.php?status=X  → Auth gerekli, duruma göre filtreli
// POST /api/reservations.php           → Public, yeni randevu oluşturur
// PUT  /api/reservations.php           → Auth gerekli, randevu günceller (kısmi)

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Admin ──
if ($method === 'GET') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $params = [];
    $where  = '';

    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $allowed = ['pending','confirmed','picked_up','in_progress','delivered','cancelled'];
        $status  = $_GET['status'];
        if (!in_array($status, $allowed, true)) {
            jsonResponse(['error' => 'Geçersiz status değeri'], 400);
        }
        $where    = 'WHERE status = :status';
        $params[':status'] = $status;
    }

    $stmt = $db->prepare("SELECT * FROM reservations $where ORDER BY pickup_date DESC, id DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $reservations = array_map(function($r) {
        return [
            'id'              => (int)$r['id'],
            'name'            => $r['name'],
            'phone'           => $r['phone'],
            'district'        => $r['district'],
            'address'         => $r['address'],
            'service_type'    => $r['service_type'],
            'service_item'    => $r['service_item'],
            'quantity'        => $r['quantity'] !== null ? (float)$r['quantity'] : null,
            'unit'            => $r['unit'],
            'estimated_price' => $r['estimated_price'] !== null ? (float)$r['estimated_price'] : null,
            'pickup_date'     => $r['pickup_date'],
            'pickup_slot'     => $r['pickup_slot'],
            'delivery_date'   => $r['delivery_date'],
            'notes'           => $r['notes'],
            'status'          => $r['status'],
            'created_at'      => $r['created_at'],
        ];
    }, $rows);

    jsonResponse(['reservations' => $reservations]);
}

// ── POST — Public (yeni randevu) ──
if ($method === 'POST') {
    $data = getJsonBody();

    // Zorunlu alan validasyonu
    $errors = [];
    if (empty(trim($data['name'] ?? '')))        $errors[] = 'Ad Soyad zorunludur';
    if (empty(trim($data['phone'] ?? '')))       $errors[] = 'Telefon zorunludur';
    if (empty(trim($data['pickup_date'] ?? ''))) $errors[] = 'Alım tarihi zorunludur';

    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 422);
    }

    // Tarih formatı kontrolü
    $pickupDate = trim($data['pickup_date']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
        jsonResponse(['error' => 'Alım tarihi YYYY-MM-DD formatında olmalıdır'], 422);
    }

    // pickup_slot kontrolü
    $allowedSlots = ['sabah', 'oglen', 'aksam', ''];
    $pickupSlot   = trim($data['pickup_slot'] ?? '');
    if ($pickupSlot !== '' && !in_array($pickupSlot, $allowedSlots, true)) {
        jsonResponse(['error' => 'Geçersiz alım saati'], 422);
    }

    // String alanları temizle
    $clean = function($v) {
        return htmlspecialchars(trim($v ?? ''), ENT_QUOTES, 'UTF-8');
    };

    $name          = $clean($data['name']);
    $phone         = $clean($data['phone']);
    $district      = $clean($data['district'] ?? '');
    $address       = $clean($data['address'] ?? '');
    $serviceType   = $clean($data['service_type'] ?? '');
    $serviceItem   = $clean($data['service_item'] ?? '');
    $unit          = $clean($data['unit'] ?? '');
    $notes         = $clean($data['notes'] ?? '');

    // Sayısal alanlar
    $quantity       = isset($data['quantity'])       && $data['quantity'] !== '' ? (float)$data['quantity']       : null;
    $estimatedPrice = isset($data['estimated_price']) && $data['estimated_price'] !== '' ? (float)$data['estimated_price'] : null;

    $stmt = $db->prepare('
        INSERT INTO reservations
            (name, phone, district, address, service_type, service_item,
             quantity, unit, estimated_price, pickup_date, pickup_slot, notes, status)
        VALUES
            (:name, :phone, :district, :address, :service_type, :service_item,
             :quantity, :unit, :estimated_price, :pickup_date, :pickup_slot, :notes, :status)
    ');

    try {
        $stmt->execute([
            ':name'            => $name,
            ':phone'           => $phone,
            ':district'        => $district,
            ':address'         => $address,
            ':service_type'    => $serviceType,
            ':service_item'    => $serviceItem,
            ':quantity'        => $quantity,
            ':unit'            => $unit,
            ':estimated_price' => $estimatedPrice,
            ':pickup_date'     => $pickupDate,
            ':pickup_slot'     => $pickupSlot !== '' ? $pickupSlot : null,
            ':notes'           => $notes,
            ':status'          => 'pending',
        ]);
        $newId = (int)$db->lastInsertId();
        jsonResponse(['success' => true, 'id' => $newId], 201);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Randevu kaydedilemedi: ' . $e->getMessage()], 500);
    }
}

// ── PUT — Admin (kısmi güncelleme) ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();

    if (empty($data['id'])) {
        jsonResponse(['error' => 'id zorunludur'], 400);
    }
    $id = (int)$data['id'];

    // Güncellenebilir alanlar ve izin verilen değerler
    $allowedStatuses = ['pending','confirmed','picked_up','in_progress','delivered','cancelled'];

    $set    = [];
    $params = [':id' => $id];

    if (array_key_exists('status', $data)) {
        if (!in_array($data['status'], $allowedStatuses, true)) {
            jsonResponse(['error' => 'Geçersiz status değeri'], 422);
        }
        $set[]            = 'status = :status';
        $params[':status'] = $data['status'];
    }

    if (array_key_exists('delivery_date', $data)) {
        $dd = $data['delivery_date'];
        if ($dd !== null && $dd !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
            jsonResponse(['error' => 'delivery_date YYYY-MM-DD formatında olmalıdır'], 422);
        }
        $set[]                  = 'delivery_date = :delivery_date';
        $params[':delivery_date'] = ($dd === '' ? null : $dd);
    }

    if (array_key_exists('notes', $data)) {
        $set[]           = 'notes = :notes';
        $params[':notes'] = htmlspecialchars(trim($data['notes'] ?? ''), ENT_QUOTES, 'UTF-8');
    }

    if (empty($set)) {
        jsonResponse(['error' => 'Güncellenecek alan belirtilmedi (status, delivery_date, notes)'], 400);
    }

    $sql  = 'UPDATE reservations SET ' . implode(', ', $set) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);

    try {
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) {
            jsonResponse(['error' => 'Randevu bulunamadı'], 404);
        }
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Güncelleme başarısız: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
