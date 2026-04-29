<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simple server status using JSON file instead of database
$statusFile = __DIR__ . '/status.json';

// Default status
$defaultStatus = [
    'players_online' => 0,
    'status' => 'offline',
    'server_name' => 'Cabal Réquiem',
    'last_updated' => date('Y-m-d H:i:s')
];

// GET request - return current status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $resource = $_GET['resource'] ?? '';
    
    if ($resource === 'server') {
        // Try to read from file, or use default
        if (file_exists($statusFile)) {
            $status = json_decode(file_get_contents($statusFile), true);
        } else {
            $status = $defaultStatus;
        }
        
        // Convert to Brasília timezone
        $dateTime = new DateTime($status['last_updated'], new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $brasiliaTime = $dateTime->format('Y-m-d H:i:s');
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'players_online' => (int)$status['players_online'],
                'status' => $status['status'],
                'server_name' => $status['server_name'],
                'last_updated' => $brasiliaTime,
                'indicator_class' => 'status-' . $status['status']
            ]
        ]);
        exit;
    }
    
    // Default API route
    echo json_encode(['success' => true, 'message' => 'Cabal Online API v1.0']);
    exit;
}

// POST request - update status (called by game server)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $newStatus = [
        'players_online' => (int)($input['players_online'] ?? 0),
        'status' => in_array($input['status'] ?? '', ['online', 'offline', 'maintenance']) ? $input['status'] : 'offline',
        'server_name' => $input['server_name'] ?? 'Cabal Réquiem',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Save to file
    if (file_put_contents($statusFile, json_encode($newStatus))) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to write status file']);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);