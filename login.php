<?php
// Simple login using JSON file
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header('Location: login.html?error=missing_fields');
        exit;
    }

    // Load users from JSON
    $usersFile = __DIR__ . '/../../backend/data/users.json';
    
    if (!file_exists($usersFile)) {
        header('Location: login.html?error=no_users');
        exit;
    }

    $users = json_decode(file_get_contents($usersFile), true);
    
    // Find user
    $foundUser = null;
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $foundUser = $user;
            break;
        }
    }

    if (!$foundUser) {
        header('Location: login.html?error=invalid_user');
        exit;
    }

    if (!password_verify($password, $foundUser['password_hash'])) {
        header('Location: login.html?error=invalid_password');
        exit;
    }

    // Login successful - create simple session
    $_SESSION['user_id'] = $foundUser['id'];
    $_SESSION['username'] = $foundUser['username'];
    $_SESSION['role'] = $foundUser['role'];

    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.html');
    exit;
}