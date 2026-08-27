<?php
/**
 * Template for config/sp-credentials.php (the real, gitignored file).
 *
 * F.R.E.S.H. platform DB credentials for the audience-mirror sync — the ONE
 * consumer is includes/sp_audience_sync.php (subscribe/verify/unsubscribe all
 * go through it). Copy to sp-credentials.php, fill real values; mode 600 +
 * owner offda9:offda9 on prod.
 *
 * TARGET DB IS freshthaplatform_shared_platform — the post-extraction
 * platform database. The pre-2026-08-27 file pointed at the abandoned
 * PRE-extraction shared DB (the freshthaband-account one), so every sync
 * write landed in a graveyard. If you are re-creating this file, do not copy
 * the DSN from an old backup. (The old DB's name is deliberately not spelled
 * out here: prod-opscheck greps every docroot for it as a drift invariant.)
 *
 * The MySQL user is deliberately NOT a full-DB account: offda9_spsync holds
 * SELECT/INSERT/UPDATE on the ONE table (email_subscribers) and nothing else.
 * ROTATE: ALTER USER in MariaDB as root, update Bitwarden item
 * "OD9 platform audience sync (offda9_spsync)", then update 'pass' here.
 */
return [
    'dsn'  => 'mysql:host=localhost;dbname=freshthaplatform_shared_platform;charset=utf8mb4',
    'user' => 'offda9_spsync',
    'pass' => 'CHANGE_ME',
];
