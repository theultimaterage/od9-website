<?php
/**
 * Template for config/mail.config.php (which is gitignored).
 *
 * Copy this file:
 *   cp config/mail.config.example.php config/mail.config.php
 * Then fill in real values.
 *
 * On prod (the file lives at public_html/config/mail.config.php):
 *   sudo chown offda9:offda9 config/mail.config.php
 *   sudo chmod 600 config/mail.config.php
 * (tools/deploy_mailer.sh installs it with these perms automatically.)
 */

// === Authenticated SMTP — the real PROD sender (Brevo transactional relay) ===
// Brevo DKIM-signs outbound, which is what gets offda9.com to inbox placement.
//
// !!! PORT 2525, NOT 587 !!!  GoDaddy shared hosting transparently REDIRECTS
// outbound 587/465/25 to its own secureserver.net mailserver — the connection
// to Brevo never arrives, mail goes out unsigned, deliverability craters. 2525
// is Brevo's alternate submission port that the host redirect leaves alone.
// SMTP_VERIFY=true is the guard: if a port is hijacked, the cert CN won't match
// brevo and the send fails LOUDLY instead of silently delivering through
// GoDaddy. Never flip it to false to "fix" a send. See the project memory
// od9-godaddy-smtp-redirect-port-2525.
define('SMTP_HOST',   'smtp-relay.brevo.com');
define('SMTP_PORT',   2525);            // NOT 587 — see note above
define('SMTP_SECURE', 'tls');           // STARTTLS
define('SMTP_VERIFY', true);            // mismatch = port hijack; keep ON
define('SMTP_USER',   'REPLACE_WITH_BREVO_SMTP_LOGIN');   // e.g. xxxxx@smtp-brevo.com
define('SMTP_PASS',   'REPLACE_WITH_BREVO_SMTP_KEY');     // the xsmtpsib-... key

// === Mailtrap Sandbox — LOCAL (XAMPP) capture only; never delivers ===
// Mailtrap Sandbox inbox ID — the numeric segment in:
//   https://mailtrap.io/sandboxes/<INBOX_ID>/messages
define('MAILTRAP_INBOX_ID', 'REPLACE_WITH_INBOX_ID');

// Mailtrap API token — https://mailtrap.io/account/api-tokens
// (any token with "Email Sending" + "Email Testing" scopes works)
define('MAILTRAP_API_TOKEN', 'REPLACE_WITH_API_TOKEN');
