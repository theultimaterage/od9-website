<?php
/**
 * Template for config/database.config.php (the real, gitignored file).
 * Copy to database.config.php on production and fill real values; mode 600.
 * Local XAMPP does NOT use this file (it connects as root with no password).
 */
return [
    'host' => 'localhost',
    'name' => 'offda9_od9_tickets',
    'user' => 'offda9_od9admin',
    'pass' => 'CHANGE_ME',
    'port' => 3306,
];
