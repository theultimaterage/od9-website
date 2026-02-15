<?php
/**
 * OD9 Admin - ASCEND Dashboard Wrapper
 * 
 * Routes to the shared-platform ASCEND progression dashboard.
 */

// Define site path before loading shared page (SITE_PATH required by shared Bootstrap.php)
define('SITE_PATH', dirname(dirname(dirname(__DIR__))));

// Load the shared progression dashboard
require_once SITE_PATH . '/config/bootstrap.php';
require_once SHARED_PATH . '/admin/progression/index.php';
