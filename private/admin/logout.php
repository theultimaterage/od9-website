<?php
/**
 * OD9 Admin Logout
 */

// Define site root for bootstrap
define('SITE_ROOT', dirname(dirname(__DIR__)));

// Load shared bootstrap
require_once SITE_ROOT . '/config/bootstrap.php';

// Load shared admin auth
require_once SHARED_PATH . '/core/AdminAuth.php';

// Perform logout
AdminAuth::logout();

// Redirect to login
header('Location: login.php');
exit;
