<?php
/**
 * OD9 Mail Configuration
 *
 * Environment-aware. Mirrors database.php's local-vs-prod detection.
 *
 *  - LOCAL (XAMPP): routes outgoing mail through Mailtrap Sandbox HTTP API
 *    so verification + drip emails get captured in the sandbox inbox without
 *    real delivery. Lets us iterate on rendering / copy / link correctness.
 *  - PROD (cPanel): passes through to PHP mail() / local exim, preserving
 *    existing delivery behavior. Same -f envelope-from + DKIM/SPF/DMARC
 *    alignment we already have configured for offda9.com.
 *
 * Sensitive credentials (Mailtrap inbox ID + API token) live in the
 * gitignored sibling `mail.config.php`. See `mail.config.example.php` for
 * the expected shape. On prod that file should be mode 600 + offda9:offda9.
 */

$_od9_mail_is_local = (
    in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || (PHP_OS_FAMILY === 'Windows' && strpos(__DIR__, 'xampp') !== false)
);

define('MAIL_ENVIRONMENT', $_od9_mail_is_local ? 'local' : 'production');
define('MAIL_DRIVER', $_od9_mail_is_local ? 'mailtrap_sandbox' : 'mail_function');

if (MAIL_DRIVER === 'mailtrap_sandbox') {
    define('MAILTRAP_API_BASE', 'https://sandbox.api.mailtrap.io/api/send');
    $_secretsPath = __DIR__ . '/mail.config.php';
    if (file_exists($_secretsPath)) {
        require $_secretsPath;  // defines MAILTRAP_INBOX_ID + MAILTRAP_API_TOKEN
    } else {
        error_log('[mail] config/mail.config.php missing — sandbox sends will fail. See mail.config.example.php.');
    }
}
