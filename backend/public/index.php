<?php

$request = $_SERVER['REQUEST_URI'];
$rootDir = dirname(__DIR__);

if (strpos($request, '/api') === 0) {
    require_once __DIR__ . '/../api/index.php';
    exit;
}

$path = parse_url($request, PHP_URL_PATH);

if ($path === '/' || $path === '') {
    $path = '/index.html';
}

$filePath = $rootDir . $path;

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
        'ico' => 'image/x-icon'
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
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Cabal Online</h1>
    <p>Site em manutenção</p>
</body>
</html>';