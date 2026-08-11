<?php
/**
 * config/platform.config.php — copy this to that name per environment.
 *   GITIGNORED, mode 600 on prod (holds the shared HMAC secret).
 *
 * Provides + enables the F.R.E.S.H. platform email route used by
 * includes/platform_mail.php and od9_send_mail()'s platform-primary path
 * (includes/mail.php). See the [[od9-email-architecture]] memory + the platform
 * repo's docs/OD9-EMAIL-SEND-PATH.md for the full contract.
 *
 * On prod, mirror the value from the bot .env so there is ONE trust channel:
 *   sudo sh -c 'umask 177; printf "<?php\n define(\"OD9_SYNC_WEBHOOK_SECRET\", \"%s\");\n define(\"MAIL_USE_PLATFORM\", true);\n" \
 *     "$(grep -E ^OD9_SYNC_WEBHOOK_SECRET= /home/ultimaterage/od9-discord-bot/.env | cut -d= -f2-)" \
 *     > /home/offda9/public_html/config/platform.config.php'
 */

// HMAC-SHA256 secret shared with the platform — the SAME value member-sync uses.
define('OD9_SYNC_WEBHOOK_SECRET', 'CHANGEME_hex64_from_bot_env');

// Master toggle: route OD9 email through the platform engine (passthrough template,
// tracking OFF until the per-tenant offda9.com tracking domain is live). Set false
// to instantly revert every sender to the standalone SMTP path.
define('MAIL_USE_PLATFORM', true);

// --- Optional overrides (defaults are correct for prod on-box) ---
// putenv('OD9_PLATFORM_EMAIL_ENDPOINT=https://admin.freshthaplatform.com/api/od9-send-email.php');
// putenv('OD9_PLATFORM_EMAIL_RESOLVE=72.167.54.213'); // CF-origin pin; '' to force the public edge
