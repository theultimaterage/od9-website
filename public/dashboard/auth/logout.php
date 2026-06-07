<?php
/**
 * Logout Handler
 * OD9 Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Revoke the persistent remember token (delete the DB row + clear its cookie)
// before tearing down the session, so logout fully ends the device's login.
od9_clear_remember_token();

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
