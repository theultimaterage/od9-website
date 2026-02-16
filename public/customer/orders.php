<?php
// Customer portal wrapper for OD9
define('SITE_PATH', dirname(__DIR__, 2));
// Load shared Bootstrap with OD9 context
require_once dirname(SITE_PATH) . '/shared-platform/core/Bootstrap.php';
require_once dirname(SITE_PATH) . '/shared-platform/core/CustomerAuth.php';
// Load shared customer template
require_once dirname(SITE_PATH) . '/shared-platform/templates/public/customer/orders.php';
