<?php
/**
 * Template for config/mail.config.php (which is gitignored).
 *
 * Copy this file:
 *   cp config/mail.config.example.php config/mail.config.php
 * Then fill in real values.
 *
 * On prod:
 *   sudo chown offda9:offda9 config/mail.config.php
 *   sudo chmod 600 config/mail.config.php
 *
 * LOCAL dev needs NO secrets — includes/mail.php uses the 'file' driver, writing
 * the rendered .eml to logs/mail-outbox/ (no send, no quota, no network). You can
 * leave config/mail.config.php absent on local. (The Mailtrap sandbox was retired
 * 2026-06-26: its free-tier cap kept exhausting and an over-quota Mailtrap hung the
 * deploy gate; the file sink replaced it.)
 *
 * PROD sends via the F.R.E.S.H. platform (Brevo) as primary (config/platform.config.php);
 * the SMTP creds below are the fallback transport — authenticated send through the
 * host mailserver as noreply@offda9.com, DKIM-signed by exim.
 */

// SMTP fallback (prod). Host is the cPanel mailserver (e.g. mail.offda9.com, or
// 'localhost' on the same box). 465 = implicit SSL; 587 = STARTTLS.
define('SMTP_HOST', 'REPLACE_WITH_SMTP_HOST');
define('SMTP_PORT', 465);
define('SMTP_USER', 'noreply@offda9.com');
define('SMTP_PASS', 'REPLACE_WITH_NOREPLY_PASSWORD');
// define('SMTP_SECURE', 'ssl');   // optional — inferred from SMTP_PORT if omitted
// define('SMTP_VERIFY', true);    // set false only when connecting to the host by IP
