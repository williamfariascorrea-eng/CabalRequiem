<?php

$request = $_SERVER['REQUEST_URI'];

if (strpos($request, '/api') === 0) {
    require_once __DIR__ . '/../api/index.php';
} else {
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
    <h1>Cabal Online API</h1>
    <p>API está online. Acesse /api/* para os endpoints.</p>
</body>
</html>';
}