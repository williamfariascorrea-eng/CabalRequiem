<?php

$request = $_SERVER['REQUEST_URI'];
$rootDir = __DIR__;

if (strpos($request, '/backend/api') === 0) {
    $apiPath = __DIR__ . '/backend/api/index.php';
    if (file_exists($apiPath)) {
        require_once $apiPath;
        exit;
    }
}

if (strpos($request, '/api') === 0) {
    $apiPath = __DIR__ . '/backend/api/index.php';
    if (file_exists($apiPath)) {
        require_once $apiPath;
        exit;
    }
}

$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);

if ($path === '/' || $path === '' || $path === '/index.html') {
    $filePath = $rootDir . '/index.html';
} else {
    $filePath = $rootDir . $path;
}

if (is_file($filePath) && is_readable($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'php' => 'text/html'
    ];
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'text/plain'));
    readfile($filePath);
    exit;
}

header('Content-Type: text/html');
echo '<!DOCTYPE html>
<html>
<head>
    <title>Cabal Online</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #1a1a2e; color: #fff; }
        h1 { color: #00d4ff; }
    </style>
</head>
<body>
    <h1>Cabal Réquiem</h1>
    <p>Em breve...</p>
</body>
</html>';