<?php
/**
 * OD9 Admin Logout
 * 
 * Delegates logout to the unified portal which handles session cleanup
 * and redirects back to the login page.
 */

// Define site path for bootstrap (SITE_PATH required by shared Bootstrap.php)
define('SITE_PATH', dirname(dirname(__DIR__)));

// Load shared bootstrap
require_once SITE_PATH . '/config/bootstrap.php';

// Load shared admin auth
require_once SHARED_PATH . '/admin/AdminAuth.php';

// Perform logout
AdminAuth::logout();

// Redirect to unified portal login
header('Location: /shared-platform/admin/login.php');
exit;
