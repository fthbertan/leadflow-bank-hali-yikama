<?php
// LeadFlow — Blogs API (PHP + MySQL)
// GET    /api/blogs.php            → Public, tüm blogları döndürür
// GET    /api/blogs.php?slug=x     → Public, tek blog döndürür
// PUT    /api/blogs.php            → Auth gerekli, tüm blogs array'i günceller
// DELETE /api/blogs.php?id=x       → Auth gerekli, tek blog siler

require_once __DIR__ . '/config.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── GET — Herkese açık ──
if ($method === 'GET') {
    $slug = $_GET['slug'] ?? null;

    if ($slug) {
        $stmt = $db->prepare('SELECT * FROM blogs WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $blog = $stmt->fetch();

        if (!$blog) {
            jsonResponse(['error' => 'Blog not found'], 404);
        }
        // tags JSON string'den array'e çevir
        $blog['tags'] = json_decode($blog['tags'] ?? '[]', true) ?: [];
        jsonResponse($blog);
    }

    // Tüm bloglar (featured filtresi opsiyonel)
    $featured = $_GET['featured'] ?? null;

    // is_featured kolonu yoksa otomatik ekle
    try {
        $db->exec("ALTER TABLE `blogs` ADD COLUMN `is_featured` TINYINT(1) DEFAULT 0 AFTER `status`");
    } catch (Exception $e) {} // zaten varsa sessizce geç

    if ($featured) {
        $stmt = $db->query("SELECT * FROM blogs WHERE is_featured = 1 AND status = 'published' ORDER BY date DESC LIMIT 3");
    } else {
        $stmt = $db->query('SELECT * FROM blogs ORDER BY date DESC');
    }
    $blogs = $stmt->fetchAll();

    foreach ($blogs as &$b) {
        $b['tags'] = json_decode($b['tags'] ?? '[]', true) ?: [];
        $b['is_featured'] = (int)($b['is_featured'] ?? 0);
    }

    jsonResponse(['blogs' => $blogs]);
}

// ── PUT — Tüm blogs array'i güncelle (admin panel uyumluluğu) ──
if ($method === 'PUT') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = getJsonBody();
    $blogs = $data['blogs'] ?? [];

    // Mevcut tüm blogları temizle ve yeniden yaz (admin panel tüm array'i gönderir)
    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM blogs');

        $stmt = $db->prepare('
            INSERT INTO blogs (id, slug, title, summary, content, category, date, read_time, tags, cover_image, status, is_featured, created_at, updated_at)
            VALUES (:id, :slug, :title, :summary, :content, :category, :date, :read_time, :tags, :cover_image, :status, :is_featured, :created_at, :updated_at)
        ');

        foreach ($blogs as $blog) {
            $stmt->execute([
                ':id'          => $blog['id'] ?? uniqid('blog_'),
                ':slug'        => $blog['slug'] ?? '',
                ':title'       => $blog['title'] ?? '',
                ':summary'     => $blog['summary'] ?? '',
                ':content'     => $blog['content'] ?? '',
                ':category'    => $blog['category'] ?? '',
                ':date'        => $blog['date'] ?? date('Y-m-d'),
                ':read_time'   => (int)($blog['read_time'] ?? 5),
                ':tags'        => json_encode($blog['tags'] ?? [], JSON_UNESCAPED_UNICODE),
                ':cover_image' => $blog['cover_image'] ?? '',
                ':status'      => $blog['status'] ?? 'draft',
                ':is_featured' => (int)($blog['is_featured'] ?? 0),
                ':created_at'  => $blog['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at'  => $blog['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        $db->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// ── DELETE — Tek blog sil ──
if ($method === 'DELETE') {
    if (!authCheck()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'id parameter required'], 400);
    }

    $stmt = $db->prepare('DELETE FROM blogs WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $remaining = $db->query('SELECT COUNT(*) FROM blogs')->fetchColumn();
    jsonResponse(['success' => true, 'remaining' => (int)$remaining]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
