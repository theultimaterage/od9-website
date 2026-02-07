<?php
/**
 * OD9 Media Center - Edit Image Wrapper
 * Routes to shared platform edit page
 */

// Set OD9 context
define('SITE_ID', 'od9');
define('SITE_PATH', dirname(__DIR__, 3));

// Include shared edit page
require_once __DIR__ . '/../../../../shared-platform/admin/media/images/edit.php';
