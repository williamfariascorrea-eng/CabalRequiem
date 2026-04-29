<?php
session_start();
require_once __DIR__ . '/backend/vendor/autoload.php';

use CabalOnline\Auth;
use CabalOnline\Database;

// Get token from session if exists
$token = $_SESSION['token'] ?? '';

// Try to invalidate token on server side (if using session table)
if (!empty($token)) {
    try {
        $db = Database::getInstance()->getConnection();
        $auth = new Auth($db);
        $auth->logout($token);
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Clear session
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.html?message=logged_out');
exit;