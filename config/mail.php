<?php
/**
 * OD9 Mail Configuration — environment-aware.
 *
 *  - PROD: authenticated SMTP via the host mailserver (noreply@offda9.com),
 *    which DKIM-signs outbound with offda9.com's published key. Falls back to
 *    PHP mail() only if SMTP creds are absent.
 *  - LOCAL (XAMPP): Mailtrap Sandbox HTTP API — captures mail without delivering.
 *
 * Secrets (SMTP password, Mailtrap token) live in the gitignored sibling
 * config/mail.config.php (see config/mail.config.example.php). On prod that file
 * must be mode 600 + owner offda9:offda9.
 */

$_od9_mail_is_local = (
    in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || (PHP_OS_FAMILY === 'Windows' && strpos(__DIR__, 'xampp') !== false)
);

// Sender identity (same on both environments).
define('MAIL_FROM_EMAIL', 'noreply@offda9.com');
define('MAIL_FROM_NAME',  'The OD9 Movement');
define('MAIL_REPLY_TO',   'contact@offda9.com');

// Load secrets (defines SMTP_* and/or MAILTRAP_* if present).
$_secretsPath = __DIR__ . '/mail.config.php';
if (file_exists($_secretsPath)) {
    require $_secretsPath;
} else {
    error_log('[mail] config/mail.config.php missing — see mail.config.example.php');
}

$_hasSmtp = defined('SMTP_USER') && SMTP_USER !== ''
         && defined('SMTP_PASS') && SMTP_PASS !== '' && SMTP_PASS !== 'REPLACE_WITH_NOREPLY_PASSWORD';

define('MAIL_ENVIRONMENT', $_od9_mail_is_local ? 'local' : 'production');
define('MAIL_DRIVER',
    $_od9_mail_is_local ? 'mailtrap_sandbox'
                        : ($_hasSmtp ? 'smtp' : 'mail_function'));

if (MAIL_DRIVER === 'mailtrap_sandbox') {
    define('MAILTRAP_API_BASE', 'https://sandbox.api.mailtrap.io/api/send');
}
