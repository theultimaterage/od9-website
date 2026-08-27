<?php
/**
 * OD9 Mail Configuration — environment-aware.
 *
 *  - PROD: F.R.E.S.H. platform (Brevo) primary, then authenticated SMTP via the
 *    host mailserver (noreply@offda9.com, DKIM-signed by exim), then PHP mail()
 *    as last resort. (The platform route is enabled in config/platform.config.php
 *    and handled in includes/mail.php; the SMTP fallback uses the creds below.)
 *  - LOCAL (XAMPP): the 'file' sink — writes the rendered .eml to logs/mail-outbox/
 *    instead of sending. No external service, no quota, no network hang; inspect
 *    captured mail on disk (logs/mail-outbox/_latest.eml is always the newest).
 *    This REPLACED the Mailtrap sandbox (2026-06-26): its free-tier cap kept being
 *    exhausted by smoke runs, and an over-quota Mailtrap hung the deploy gate.
 *
 * Secrets (the SMTP password) live in the gitignored sibling config/mail.config.php
 * (see config/mail.config.example.php). On prod that file must be mode 600 +
 * owner offda9:offda9.
 */

$_od9_mail_is_local = (
    in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || (PHP_OS_FAMILY === 'Windows' && strpos(__DIR__, 'xampp') !== false)
);

// Sender identity (same on both environments).
define('MAIL_FROM_EMAIL', 'noreply@offda9.com');
define('MAIL_FROM_NAME',  'The OD9 Movement');
define('MAIL_REPLY_TO',   'contact@offda9.com');

// Load secrets (defines SMTP_* if present).
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
    $_od9_mail_is_local ? 'file'
                        : ($_hasSmtp ? 'smtp' : 'mail_function'));
