<?php
/**
 * Admin Page Migration Script
 * 
 * Scans all OD9 admin pages and reports which ones need to be migrated
 * to the new universal page template system.
 */

echo "=== Admin Page Design Standards Audit ===\n\n";

// Directories to scan
$adminDir = __DIR__;
$pagesScanned = 0;
$pagesNeedMigration = [];
$pagesAlreadyMigrated = [];

// Scan for all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($adminDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $relativePath = str_replace($adminDir . DIRECTORY_SEPARATOR, '', $filePath);
        
        // Skip certain files
        $skipFiles = ['login.php', 'logout.php', 'generate-wrappers.php', 'check_tables.php'];
        if (in_array(basename($filePath), $skipFiles)) {
            continue;
        }
        
        $pagesScanned++;
        $content = file_get_contents($filePath);
        
        // Check if using new template
        if (strpos($content, 'renderAdminPage') !== false) {
            $pagesAlreadyMigrated[] = $relativePath;
        } else {
            // Check if it's a page that should use the template
            $hasHtmlStructure = (
                strpos($content, '<!DOCTYPE html>') !== false ||
                strpos($content, 'AdminNavigation::renderSidebar') !== false ||
                strpos($content, '<html') !== false
            );
            
            $isSimpleWrapper = (
                strpos($content, 'require_once') !== false &&
                strpos($content, 'shared-platform') !== false &&
                count(explode("\n", $content)) < 15
            );
            
            if ($hasHtmlStructure && !$isSimpleWrapper) {
                $pagesNeedMigration[] = $relativePath;
            }
        }
    }
}

// Report results
echo "Pages scanned: $pagesScanned\n";
echo "Pages already using template: " . count($pagesAlreadyMigrated) . "\n";
echo "Pages needing migration: " . count($pagesNeedMigration) . "\n\n";

if (!empty($pagesAlreadyMigrated)) {
    echo "✅ Already Migrated:\n";
    foreach ($pagesAlreadyMigrated as $page) {
        echo "  - $page\n";
    }
    echo "\n";
}

if (!empty($pagesNeedMigration)) {
    echo "⚠️  Needs Migration:\n";
    foreach ($pagesNeedMigration as $page) {
        echo "  - $page\n";
    }
    echo "\n";
    
    echo "To migrate these pages:\n";
    echo "1. Replace custom HTML structure with renderAdminPage()\n";
    echo "2. Move logic above template call\n";
    echo "3. Configure \$pageConfig array\n";
    echo "4. Pass content rendering function\n";
    echo "\nSee DESIGN_STANDARDS.md for examples.\n";
} else {
    echo "✅ All pages follow design standards!\n";
}

echo "\n=== Audit Complete ===\n";
