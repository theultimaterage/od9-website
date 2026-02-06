<?php
/**
 * Public Admin Router
 * Routes /od9/public/admin/* to /od9/private/admin/*
 */

// Get the requested path after /admin/
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$adminPath = str_replace('/od9/public/admin/', '', $requestUri);
$adminPath = str_replace('/od9/public/admin', '', $adminPath);
$adminPath = ltrim($adminPath, '/');

// Default to dashboard if no path specified
if (empty($adminPath) || $adminPath === 'index.php') {
    $adminPath = 'dashboard.php';
}

// Security: prevent directory traversal
if (strpos($adminPath, '..') !== false) {
    http_response_code(403);
    die('Forbidden');
}

// Build the real file path
$realFile = dirname(dirname(__DIR__)) . '/private/admin/' . $adminPath;

// Check if file exists
if (!file_exists($realFile)) {
    http_response_code(404);
    die('Not Found: ' . htmlspecialchars($adminPath));
}

// Include the admin file
require $realFile;
