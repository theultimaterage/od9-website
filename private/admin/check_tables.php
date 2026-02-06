<?php
// Define constants required by admin-bootstrap
define('SITE_ID', 'od9');
define('SITE_PATH', dirname(dirname(__DIR__)));

// Load the shared platform bootstrap
require_once dirname(dirname(dirname(__DIR__))) . '/shared-platform/admin/admin-bootstrap.php';

// Get database connection
$pdo = db();

// Show all tables
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in od9_tickets database:\n";
echo str_repeat('=', 50) . "\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

// Check for members/users table
echo "\n";
if (in_array('members', $tables)) {
    echo "'members' table structure:\n";
    $columns = $pdo->query("DESCRIBE members")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
} elseif (in_array('users', $tables)) {
    echo "'users' table structure:\n";
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "No 'members' or 'users' table exists yet.\n";
    echo "Checking 'customers' table (if exists):\n";
    if (in_array('customers', $tables)) {
        $columns = $pdo->query("DESCRIBE customers")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  {$col['Field']} ({$col['Type']})\n";
        }
    }
}
