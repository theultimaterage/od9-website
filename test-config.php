<?php
define('SITE_PATH', 'C:/xampp/htdocs/od9');
require_once 'C:/xampp/htdocs/shared-platform/core/SiteConfig.php';
$config = SiteConfig::init(SITE_PATH);
echo 'Site ID: ' . $config->getValue('id', 'NOT_FOUND') . "\n";
echo 'Site Name: ' . $config->getName() . "\n";
echo 'DB Config exists: ' . (file_exists(SITE_PATH . '/config/database.php') ? 'YES' : 'NO') . "\n";
