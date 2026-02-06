<?php
/**
 * OD9 Admin Wrapper Generator
 * Creates wrapper files that route to shared-platform admin modules
 * Run once, then delete this file
 */

$sharedPlatformPath = 'C:/xampp/htdocs/shared-platform/admin';
$od9AdminPath = 'C:/xampp/htdocs/od9/private/admin';

// Files to create wrappers for (excluding root admin files we already have)
$wrapperFiles = [
    'analytics/dashboard.php',
    'customers/list.php',
    'customers/view.php',
    'discord-bot/dashboard.php',
    'email/campaign-create.php',
    'email/campaign-send.php',
    'email/campaign-view.php',
    'email/campaigns.php',
    'email/dashboard.php',
    'events/delete.php',
    'events/list.php',
    'events/update-status.php',
    'marketing/analytics.php',
    'marketing/promo-codes.php',
    'media/dashboard.php',
    'members/list.php',
    'orders/list.php',
    'orders/refund.php',
    'orders/resend-tickets.php',
    'orders/view.php',
    'products/create.php',
    'products/delete.php',
    'products/duplicate.php',
    'products/edit.php',
    'products/list.php',
    'products/stock-history.php',
    'products/view.php',
    'resources/list.php',
    'support/list.php',
    'support/view.php',
    'tickets/designer.php',
    'tickets/list.php',
    'tickets/scanner-api.php',
    'tickets/scanner.php',
    'tickets/template-api.php',
    'tiers/list.php',
];

$created = 0;
$errors = [];

foreach ($wrapperFiles as $file) {
    $targetPath = $od9AdminPath . '/' . $file;
    $sharedPath = $sharedPlatformPath . '/' . $file;
    
    // Ensure directory exists
    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Calculate relative path to shared-platform from wrapper location
    $depth = substr_count($file, '/');
    $relativePath = str_repeat('../', $depth + 3) . 'shared-platform/admin/' . $file;
    
    // Create wrapper content
    $wrapperContent = "<?php
/**
 * OD9 Admin Wrapper - Routes to shared-platform
 * File: {$file}
 */

// Set site context before including shared module
define('SITE_ID', 'od9');
define('SITE_PATH', 'C:/xampp/htdocs/od9');

// Include the shared-platform version
require_once '{$sharedPath}';
";

    if (file_put_contents($targetPath, $wrapperContent)) {
        $created++;
        echo "Created: {$file}\n";
    } else {
        $errors[] = $file;
        echo "FAILED: {$file}\n";
    }
}

echo "\n=============================\n";
echo "Created: {$created} wrapper files\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nFailed files:\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
}

echo "\nDone! You can delete this file now.\n";
