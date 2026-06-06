<?php
/**
 * Database Configuration - OD9
 *
 * Environment-aware: detects local XAMPP vs prod cPanel via SERVER_NAME +
 * filesystem path. Local uses XAMPP's root/no-password default with the
 * od9_tickets db; prod uses the offda9_od9admin user with the prod db.
 *
 * Same file ships to both environments. The detection is the only switch.
 *
 * If a local DB query fails because tables are missing (XAMPP doesn't have
 * the full prod schema), the calling page's try/catch handles the
 * PDOException - getDatabaseConnection() itself throws on connection
 * failure but does NOT log to error_log on local context (so we don't get
 * recurring "Access denied" noise on every dev hit).
 */

$_od9_is_local = (
    in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || (PHP_OS_FAMILY === 'Windows' && strpos(__DIR__, 'xampp') !== false)
);

define('ENVIRONMENT', $_od9_is_local ? 'local' : 'production');
define('DEBUG_MODE', $_od9_is_local);

if ($_od9_is_local) {
    // XAMPP defaults - root with no password, local db name
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'od9_tickets');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', 3306);
} else {
    // Production cPanel — credentials live in the gitignored sibling
    // config/database.config.php (see config/database.config.example.php).
    // Rotate the MySQL password in cPanel, then update that file + redeploy.
    $_prodDb = __DIR__ . '/database.config.php';
    if (!is_file($_prodDb)) {
        error_log('[database.php] production config/database.config.php missing');
        throw new RuntimeException('Database configuration missing on production');
    }
    $_db = require $_prodDb;
    define('DB_HOST', $_db['host'] ?? 'localhost');
    define('DB_NAME', $_db['name'] ?? 'offda9_od9_tickets');
    define('DB_USER', $_db['user'] ?? '');
    define('DB_PASS', $_db['pass'] ?? '');
    define('DB_PORT', (int)($_db['port'] ?? 3306));
}

define('DB_CHARSET', 'utf8mb4');
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function getDatabaseConnection() {
    static $connection = null;
    if ($connection === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s;port=%d', DB_HOST, DB_NAME, DB_CHARSET, DB_PORT);
        try {
            $connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            // On local: stay quiet (don't spam error_log every page hit).
            // On prod: re-throw so the page's try/catch can log + degrade.
            if (ENVIRONMENT === 'local') {
                throw $e;  // still throw so callers' try/catch fires, but no error_log spam
            }
            error_log('[database.php] connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    return $connection;
}
