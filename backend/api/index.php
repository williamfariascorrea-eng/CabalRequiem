<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use CabalOnline\Auth;
use CabalOnline\Database;

// Get database connection
try {
    $db = Database::getInstance()->getConnection();
    $auth = new Auth($db);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('?', $_SERVER['REQUEST_URI'])[0];
$parts = explode('/', trim($request, '/'));

// API version
if (isset($parts[0]) && $parts[0] === 'api') {
    array_shift($parts);
} else {
    // Not an API request
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

// Route handling
if (empty($parts)) {
    // Base API endpoint
    echo json_encode(['success' => true, 'message' => 'Cabal Online API v1.0']);
    exit;
}

$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;

// Authentication middleware for protected routes
$authenticate = function() use ($auth) {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization token required']);
        exit;
    }
    
    $userData = $auth->validateToken($token);
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
    
    return $userData;
};

// Route definitions
switch ($resource) {
    case 'register':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $auth->register(
                $input['username'] ?? '',
                $input['password'] ?? '',
                $input['email'] ?? '',
                $input['full_name'] ?? '',
                $input['personal_code'] ?? ''
            );
            http_response_code($result['success'] ? 201 : 400);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case 'login':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $auth->login(
                $input['username'] ?? '',
                $input['password'] ?? ''
            );
            http_response_code($result['success'] ? 200 : 401);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case 'dashboard':
        if ($method === 'GET') {
            $userData = $authenticate();
            $result = $auth->getDashboardData($userData['id']);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case 'logout':
        if ($method === 'POST') {
            $headers = getallheaders();
            $token = null;
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
                if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                    $token = $matches[1];
                }
            }
            
            if ($token) {
                $result = $auth->logout($token);
                http_response_code($result['success'] ? 200 : 400);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token required']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case 'profile':
        if ($method === 'GET') {
            $userData = $authenticate();
            // Get extended profile data
            $stmt = $db->prepare(
                "SELECT u.username, u.email, u.full_name, u.role, u.created_at, u.last_login,
                        gp.character_name, gp.level, gp.class, gp.experience, gp.gold, gp.last_played
                 FROM users u
                 LEFT JOIN game_profiles gp ON u.id = gp.user_id
                 WHERE u.id = ?"
            );
            $stmt->execute([$userData['id']]);
            $profile = $stmt->fetch();
            
            if ($profile) {
                http_response_code(200);
                echo json_encode(['success' => true, 'data' => $profile]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Profile not found']);
            }
        } elseif ($method === 'PUT') {
            $userData = $authenticate();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Update game profile
            $character_name = $input['character_name'] ?? null;
            $class = $input['class'] ?? null;
            
            // Validate inputs
            if ($character_name !== null && (strlen($character_name) < 2 || strlen($character_name) > 50)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Character name must be 2-50 characters']);
                exit;
            }
            
            if ($class !== null && (strlen($class) < 2 || strlen($class) > 50)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Class must be 2-50 characters']);
                exit;
            }
            
            // Update or insert game profile
            $stmt = $db->prepare(
                "INSERT INTO game_profiles (user_id, character_name, class) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                     character_name = VALUES(character_name),
                     class = VALUES(class)"
            );
            
            try {
                $stmt->execute([$userData['id'], $character_name, $class]);
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Profile updated']);
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case 'rankings':
        if ($method === 'GET') {
            $type = $_GET['type'] ?? 'level';
            $limit = min((int)($_GET['limit'] ?? 10), 100); // Max 100
            
            // Validate rank type
            $allowedTypes = ['level', 'pvp', 'pve', 'guild'];
            if (!in_array($type, $allowedTypes)) {
                $type = 'level';
            }
            
            $stmt = $db->prepare(
                "SELECT u.username, gp.level, gp.class, r.score, r.rank_position
                 FROM rankings r
                 JOIN users u ON r.user_id = u.id
                 LEFT JOIN game_profiles gp ON u.id = gp.user_id
                 WHERE r.rank_type = ?
                 ORDER BY r.score DESC, r.rank_position ASC
                 LIMIT ?"
            );
            $stmt->execute([$type, $limit]);
            $rankings = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $rankings, 'type' => $type]);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    case 'server':
        if ($method === 'GET') {
            // Get server status
            $stmt = $db->prepare(
                "SELECT players_online, status, last_updated, server_name 
                 FROM server_status ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute();
            $status = $stmt->fetch();
            
            if ($status) {
                // Convert last_updated to Brasília time (America/Sao_Paulo)
                $dateTime = new DateTime($status['last_updated'], new DateTimeZone('UTC'));
                $dateTime->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                $brasiliaTime = $dateTime->format('Y-m-d H:i:s');
                
                // Determine indicator class
                $indicatorClass = '';
                switch ($status['status']) {
                    case 'online':
                        $indicatorClass = 'status-online';
                        break;
                    case 'offline':
                        $indicatorClass = 'status-offline';
                        break;
                    case 'maintenance':
                        $indicatorClass = 'status-maintenance';
                        break;
                    default:
                        $indicatorClass = 'status-unknown';
                }
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'players_online' => (int)$status['players_online'],
                        'status' => $status['status'],
                        'server_name' => $status['server_name'],
                        'last_updated' => $brasiliaTime,
                        'indicator_class' => $indicatorClass
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server status not available']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resource not found']);
        break;
}