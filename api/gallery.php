<?php
// LeadFlow — Gallery API (PHP + MySQL)
// GET    /api/gallery.php              → Public, tum gorselleri dondurur
// GET    /api/gallery.php?category=x   → Public, kategoriye gore filtreler
// POST   /api/gallery.php              → Auth gerekli, gorsel yukler
// PUT    /api/gallery.php              → Auth gerekli, gorsel bilgilerini gunceller
// DELETE /api/gallery.php?id=x         → Auth gerekli, gorsel siler

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// Upload dizini
$uploadDir = __DIR__ . '/../img/uploads/';

/**
 * Görseli resize + WebP'e dönüştür
 * @param string $srcPath Kaynak dosya yolu
 * @param int $maxWidth Maksimum genişlik (px)
 * @return string Sonuç dosya yolu (webp)
 */
function optimizeImage($srcPath, $maxWidth = 800) {
    if (!function_exists('imagecreatefromjpeg')) return $srcPath; // GD yoksa orijinali bırak

    $info = @getimagesize($srcPath);
    if (!$info) return $srcPath;

    $mime = $info['mime'];
    $origW = $info[0];
    $origH = $info[1];

    // Kaynak image oluştur
    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($srcPath); break;
        case 'image/png':  $src = @imagecreatefrompng($srcPath); break;
        case 'image/webp': $src = @imagecreatefromwebp($srcPath); break;
        case 'image/gif':  $src = @imagecreatefromgif($srcPath); break;
        default: return $srcPath;
    }
    if (!$src) return $srcPath;

    // Resize gerekiyorsa
    if ($origW > $maxWidth) {
        $newW = $maxWidth;
        $newH = (int)round($origH * ($maxWidth / $origW));
        $dst = imagecreatetruecolor($newW, $newH);
        // PNG/WebP transparanlık koruması
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);
        $src = $dst;
    }

    // WebP olarak kaydet (kalite: 82 — dosya boyutu / görsel kalite dengesi)
    $webpPath = preg_replace('/\.[^.]+$/', '.webp', $srcPath);
    if (imagewebp($src, $webpPath, 82)) {
        imagedestroy($src);
        // Orijinal dosyayı sil (artık webp var)
        if ($webpPath !== $srcPath) @unlink($srcPath);
        chmod($webpPath, 0644);
        return $webpPath;
    }

    // WebP başarısızsa orijinali bırak
    imagedestroy($src);
    return $srcPath;
}

// ── GET — Public (tum gorseller) ──
if ($method === 'GET') {
    if (isset($_GET['category'])) {
        $cat = trim($_GET['category']);
        $stmt = $db->prepare('SELECT * FROM gallery WHERE category = :cat ORDER BY sort_order ASC, created_at DESC');
        $stmt->execute([':cat' => $cat]);
    } else {
        $stmt = $db->query('SELECT * FROM gallery ORDER BY sort_order ASC, created_at DESC');
    }
    $images = $stmt->fetchAll();
    jsonResponse(['images' => $images]);
}

// ── POST — Admin (gorsel yukle) ──
if ($method === 'POST') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    if (!isset($_FILES['image'])) {
        jsonResponse(['error' => 'Gorsel dosyasi gerekli'], 400);
    }

    $file = $_FILES['image'];

    // Dosya dogrulama
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Dosya yukleme hatasi (kod: ' . $file['error'] . ')'], 400);
    }

    if ($file['size'] > $maxSize) {
        jsonResponse(['error' => 'Dosya boyutu 5MB\'dan buyuk olamaz'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        jsonResponse(['error' => 'Gecersiz dosya tipi. Sadece JPG, PNG, WebP ve GIF kabul edilir'], 400);
    }

    // Upload dizinini olustur
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Benzersiz dosya adi
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        jsonResponse(['error' => 'Dosya kaydedilemedi'], 500);
    }
    chmod($destPath, 0644);

    // DB'ye kaydedilecek verileri oku
    $category = $_POST['category'] ?? 'gallery';
    $altText = $_POST['alt_text'] ?? '';
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    // Otomatik resize + WebP dönüşümü (PageSpeed optimizasyonu)
    $maxWidth = 800; // Sitede gösterim boyutuna uygun
    if ($category === 'hero') $maxWidth = 1920;
    $destPath = optimizeImage($destPath, $maxWidth);
    // Dosya adını webp olarak güncelle
    $filename = basename($destPath);

    $stmt = $db->prepare('INSERT INTO gallery (filename, original_name, category, alt_text, sort_order) VALUES (:f, :o, :c, :a, :s)');
    $stmt->execute([
        ':f' => 'img/uploads/' . $filename,
        ':o' => $file['name'],
        ':c' => $category,
        ':a' => $altText,
        ':s' => $sortOrder,
    ]);

    jsonResponse([
        'success' => true,
        'image' => [
            'id' => (int)$db->lastInsertId(),
            'filename' => 'img/uploads/' . $filename,
            'original_name' => $file['name'],
            'category' => $category,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
        ]
    ]);
}

// ── PUT — Admin (gorsel bilgisi guncelle) ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $id = $data['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'id alani zorunlu'], 400);
    }

    $fields = [];
    $params = [':id' => (int)$id];

    if (isset($data['category'])) {
        $fields[] = 'category = :cat';
        $params[':cat'] = $data['category'];
    }
    if (isset($data['alt_text'])) {
        $fields[] = 'alt_text = :alt';
        $params[':alt'] = $data['alt_text'];
    }
    if (isset($data['sort_order'])) {
        $fields[] = 'sort_order = :sort';
        $params[':sort'] = (int)$data['sort_order'];
    }

    if (empty($fields)) {
        jsonResponse(['error' => 'Guncellenecek alan yok'], 400);
    }

    $stmt = $db->prepare('UPDATE gallery SET ' . implode(', ', $fields) . ' WHERE id = :id');
    $stmt->execute($params);

    jsonResponse(['success' => true]);
}

// ── DELETE — Admin ──
if ($method === 'DELETE') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'id parametresi zorunlu'], 400);
    }

    // Dosyayi diskten sil
    $stmt = $db->prepare('SELECT filename FROM gallery WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);
    $row = $stmt->fetch();

    if ($row && $row['filename']) {
        $filePath = __DIR__ . '/../' . $row['filename'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // DB'den sil
    $stmt = $db->prepare('DELETE FROM gallery WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
