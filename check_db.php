<?php
require_once 'private/admin/config/database.php';

$pdo = db();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

// Check for members/users table
if (in_array('members', $tables)) {
    echo "\n'members' table found!\n";
    $columns = $pdo->query("DESCRIBE members")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} elseif (in_array('users', $tables)) {
    echo "\n'users' table found!\n";
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "\nNo 'members' or 'users' table found. Checking 'customers' table:\n";
    if (in_array('customers', $tables)) {
        $columns = $pdo->query("DESCRIBE customers")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
}
