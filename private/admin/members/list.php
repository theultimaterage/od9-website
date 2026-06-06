<?php
/**
 * OD9 Admin Wrapper - Routes to shared-platform
 * File: members/list.php
 */

// Set site context before including shared module
define('SITE_ID', 'od9');
define('SITE_PATH', 'C:/xampp/htdocs/od9');

// Bootstrap shared admin (loads AdminAuth, Database, etc.)
require_once 'C:/xampp/htdocs/freshthaband/public_html/shared-platform/admin-bootstrap.php';

// Include the shared-platform version
require_once 'C:/xampp/htdocs/freshthaband/public_html/shared-platform/admin/members/list.php';
