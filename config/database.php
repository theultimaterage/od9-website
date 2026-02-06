<?php
/**
 * Database Configuration - OD9
 * 
 * SECURITY NOTE: This file should NEVER be committed to version control.
 */

// Detect environment
$isLocalDevelopment = (
    ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' ||
    ($_SERVER['SERVER_NAME'] ?? '') === '127.0.0.1' ||
    strpos(__DIR__, 'xampp') !== false
);

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $isLocalDevelopment ? 'development' : 'production');
}

if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', ENVIRONMENT === 'development');
}

// Database configuration based on environment
if (ENVIRONMENT === 'development') {
    // Local XAMPP development settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'od9_tickets'); // OD9's own database
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_PORT', 3306);
    
} else {
    // Production server settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'od9_production'); // Update with real production DB
    define('DB_USER', 'od9_user');
    define('DB_PASS', 'CHANGE_THIS_PASSWORD');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_PORT', 3306);
}

// PDO options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    1002 => "SET NAMES " . DB_CHARSET, // PDO::MYSQL_ATTR_INIT_COMMAND
]);

/**
 * Get a PDO database connection
 */
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        static $connection = null;
        
        if ($connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s;port=%d',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET,
                    DB_PORT
                );
                
                $connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                if (DEBUG_MODE) {
                    die("Database Connection Error: " . $e->getMessage());
                } else {
                    die("Unable to connect to database. Please contact support.");
                }
            }
        }
        
        return $connection;
    }
}

return [
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'charset' => DB_CHARSET,
    'port' => DB_PORT
];
