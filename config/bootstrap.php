<?php
/**
 * OD9 - Site Bootstrap
 * 
 * This file initializes the site by loading the shared platform
 * and site-specific configuration. Include this at the start of
 * every page instead of the old config files.
 */

// Define site path before loading shared bootstrap
if (!defined('SITE_PATH')) {
    define('SITE_PATH', dirname(__DIR__));
}

// Load the shared platform bootstrap
require_once __DIR__ . '/../../shared-platform/core/Bootstrap.php';

// Note: After this point, you have access to:
// - site($key)     - Get site config value
// - hasFeature($f) - Check if feature enabled  
// - db()           - Get database instance
// - BASE_URL, APP_NAME, ENVIRONMENT, DEBUG_MODE constants
// - sharedComponent($name) - Include shared UI component
