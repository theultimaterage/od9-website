<?php
/**
 * OD9 Admin Index - Entry Point Redirect
 */

// Define site path for bootstrap (SITE_PATH required by shared Bootstrap.php)
define('SITE_PATH', dirname(dirname(__DIR__)));

// Load shared bootstrap
require_once SITE_PATH . '/config/bootstrap.php';

// Load shared admin auth
require_once SHARED_PATH . '/core/AdminAuth.php';

// Initialize auth
AdminAuth::init();

// Redirect based on login status
if (AdminAuth::isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
