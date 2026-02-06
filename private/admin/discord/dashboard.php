<?php
/**
 * OD9 Admin Wrapper - Routes to shared-platform
 * File: discord/dashboard.php
 */

// Set site context before including shared module
define('SITE_ID', 'od9');
define('SITE_PATH', 'C:/xampp/htdocs/od9');

// Include the shared-platform version (discord-bot folder)
require_once 'C:/xampp/htdocs/shared-platform/admin/discord-bot/dashboard.php';
