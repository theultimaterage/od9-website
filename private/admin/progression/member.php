<?php
/**
 * OD9 Admin - ASCEND Member Detail Wrapper
 * 
 * Routes to the shared-platform member profile page.
 */

// Define site path before loading shared page (SITE_PATH required by shared Bootstrap.php)
define('SITE_PATH', dirname(dirname(dirname(__DIR__))));

// Load the shared member detail page
require_once SITE_PATH . '/config/bootstrap.php';
require_once SHARED_PATH . '/admin/progression/member.php';
