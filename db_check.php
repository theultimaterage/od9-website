<?php
// Quick database check via web
define('SITE_ID', 'od9');
define('SITE_PATH', __DIR__);

require_once __DIR__ . '/../shared-platform/core/bootstrap.php';

$pdo = db()->getConnection();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Tables in od9_tickets:</h2><ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

// Check for members table
if (in_array('od9_members', $tables)) {
    echo "<h3>od9_members table structure:</h3><pre>";
    print_r($pdo->query("DESCRIBE od9_members")->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    echo "<h3>Sample data from od9_members (first 5 rows):</h3><pre>";
    print_r($pdo->query("SELECT * FROM od9_members LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} else {
    echo "<p><strong>No 'od9_members' table found. Need to create one.</strong></p>";
}

// Check tiers table
if (in_array('od9_tiers', $tables)) {
    echo "<h3>od9_tiers table structure:</h3><pre>";
    print_r($pdo->query("DESCRIBE od9_tiers")->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    echo "<h3>Sample data from od9_tiers:</h3><pre>";
    print_r($pdo->query("SELECT * FROM od9_tiers")->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
}

// Check Discord Bot table
if (in_array('od9_discord_bot_config', $tables)) {
    echo "<h3>od9_discord_bot_config table structure:</h3><pre>";
    print_r($pdo->query("DESCRIBE od9_discord_bot_config")->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
}
