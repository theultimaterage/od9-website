<?php
/**
 * Template for config/sp-credentials.php (the real, gitignored file).
 * Shared-Platform DB credentials for cross-DB access (subscribe.php writes to
 * the email marketing system). Copy to sp-credentials.php, fill real values;
 * mode 600 + owner offda9:offda9 on prod.
 *
 * ROTATE: change the freshthaband_spadmin MySQL password in the shared
 * platform's cPanel, then update 'pass' here and redeploy.
 */
return [
    'dsn'  => 'mysql:host=localhost;dbname=freshthaband_shared_platform;charset=utf8mb4',
    'user' => 'freshthaband_spadmin',
    'pass' => 'CHANGE_ME',
];
