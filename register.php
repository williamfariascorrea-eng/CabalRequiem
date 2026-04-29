<?php
// Simple registration using JSON file instead of database
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $personal_code = trim($_POST['personal_code'] ?? '');

    if (empty($username) || empty($password) || empty($email) || empty($full_name) || empty($personal_code)) {
        header('Location: register.html?error=all_fields_required');
        exit;
    }

    if (strlen($username) < 4 || strlen($username) > 16) {
        header('Location: register.html?error=username_length');
        exit;
    }

    if (strlen($password) < 6 || strlen($password) > 16) {
        header('Location: register.html?error=password_length');
        exit;
    }

    // Use JSON file for users
    $usersFile = __DIR__ . '/../../backend/data/users.json';
    $users = [];
    
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?: [];
    }

    // Check if user exists
    foreach ($users as $user) {
        if ($user['username'] === $username || $user['email'] === $email || $user['personal_code'] === $personal_code) {
            header('Location: register.html?error=user_exists');
            exit;
        }
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Save user
    $newUser = [
        'id' => count($users) + 1,
        'username' => $username,
        'password_hash' => $password_hash,
        'email' => $email,
        'full_name' => $full_name,
        'personal_code' => $personal_code,
        'role' => 'player',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $users[] = $newUser;
    
    // Create data directory if not exists
    $dataDir = __DIR__ . '/../../backend/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
        header('Location: login.html?success=registered');
        exit;
    } else {
        header('Location: register.html?error=save_failed');
        exit;
    }
} else {
    header('Location: register.html');
    exit;
}