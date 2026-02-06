<?php
/**
 * OD9 Media Center - Upload Image Wrapper
 * Routes to shared platform upload page
 */

// Set OD9 context
define('SITE_ID', 1);
define('SITE_PATH', __DIR__ . '/../../../');

// Include shared upload page
require_once __DIR__ . '/../../../../shared-platform/admin/media/images/upload.php';
