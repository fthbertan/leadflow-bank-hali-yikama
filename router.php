<?php
// PHP built-in server router — .html dosyalarını PHP olarak işler
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if (is_dir($file)) {
    $candidates = [$file . '/index.html', $file . '/index.php'];
    foreach ($candidates as $c) {
        if (file_exists($c)) { $file = $c; break; }
    }
}

if (file_exists($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext === 'html' || $ext === 'php') {
        include $file;
        return true;
    }
    return false; // static dosyaları (css, js, webp...) built-in server serve eder
}

// 404
http_response_code(404);
include __DIR__ . '/404.html';
return true;
