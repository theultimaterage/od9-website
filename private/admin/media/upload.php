<?php
/**
 * OD9 Media Center - Upload Image Wrapper
 * Routes to shared platform upload page
 */

// Set OD9 context
define('SITE_ID', 'od9');
define('SITE_PATH', dirname(__DIR__, 3));

// Include shared upload page
require_once __DIR__ . '/../../../../shared-platform/admin/media/images/upload.php';
