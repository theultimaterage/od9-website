<?php
/**
 * OD9 Admin Wrapper - Routes to shared-platform
 * File: media/dashboard.php
 */

// Set site context before including shared module
define('SITE_ID', 'od9');
define('SITE_PATH', dirname(dirname(dirname(__DIR__))));

// Bootstrap shared admin (loads AdminAuth, Database, etc.)
require_once 'C:/xampp/htdocs/freshthaband/public_html/shared-platform/admin-bootstrap.php';

// Include the shared-platform version
require_once 'C:/xampp/htdocs/freshthaband/public_html/shared-platform/admin/media/dashboard.php';
