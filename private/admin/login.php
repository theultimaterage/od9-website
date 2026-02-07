<?php
/**
 * OD9 Admin Login Page
 * 
 * Wrapper that delegates to the shared platform login page.
 * Site branding is automatically loaded from site configuration.
 */

// Define site path for bootstrap (SITE_PATH required by shared Bootstrap.php)
define('SITE_PATH', dirname(dirname(__DIR__)));

// Load shared platform login (handles all authentication logic and rendering)
require_once SITE_PATH . '/config/bootstrap.php';
require_once SHARED_PATH . '/admin/login.php';
