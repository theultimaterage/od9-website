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
 */

// Mailtrap Sandbox inbox ID — find at:
//   https://mailtrap.io/sandboxes/<INBOX_ID>/messages
// (the numeric segment in the URL is the inbox ID)
define('MAILTRAP_INBOX_ID', 'REPLACE_WITH_INBOX_ID');

// Mailtrap API token — find at:
//   https://mailtrap.io/account/api-tokens
// (any token with "Email Sending" + "Email Testing" scopes works)
define('MAILTRAP_API_TOKEN', 'REPLACE_WITH_API_TOKEN');
