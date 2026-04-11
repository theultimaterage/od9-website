<?php
/**
 * Logout Handler
 * OD9 Dashboard
 */

require_once __DIR__ . '/../includes/config.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to dashboard
header('Location: ' . (defined('DASHBOARD_BASE_URL') ? DASHBOARD_BASE_URL : '/dashboard/'));
exit;
