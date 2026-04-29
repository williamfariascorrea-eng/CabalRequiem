<?php
session_start();
require_once __DIR__ . '/backend/vendor/autoload.php';

use CabalOnline\Auth;
use CabalOnline\Database;

// Function to generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Verify email + personal code
    $email = trim($_POST['email'] ?? '');
    $personal_code = trim($_POST['personal_code'] ?? '');

    if (empty($email) || empty($personal_code)) {
        header('Location: recover.html?error=missing_fields');
        exit;
    }

    try {
        $db = Database::getInstance()->getConnection();
        $auth = new Auth($db);

        $user_id = $auth->verifyRecovery($email, $personal_code);
        if (!$user_id) {
            header('Location: recover.html?error=invalid_credentials');
            exit;
        }

        // Generate recovery token
        $token = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token
        $stmt = $db->prepare(
            "INSERT INTO password_resets (user_id, token, expires_at) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$user_id, $token, $expires]);

        // Redirect to reset page with token
        header('Location: reset.php?token=' . urlencode($token));
        exit;
    } catch (Exception $e) {
        error_log("Recovery error: " . $e->getMessage());
        header('Location: recover.html?error=system_error');
        exit;
    }
} else {
    // GET request, redirect to form
    header('Location: recover.html');
    exit;
}