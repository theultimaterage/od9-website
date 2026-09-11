<?php
/**
 * Atlas sessions in a UTC window — read endpoint for the bot's arc ledger.
 *
 * The Fifty-Two Sundays pre-registration counts "Atlas sessions in the 48 hours
 * after each Sunday service". The beacon events live in this account's home
 * (/home/offda9/atlas-events, written by atlas-ping.php), which the bot's
 * account cannot read, so the bot asks here. Token-gated exactly like
 * pulse.php (OD9_PULSE_TOKEN in pulse_secret.config.php, constant-time
 * compare); GET only; never cached.
 *
 *   GET /api/v1/atlas-window.php?token=<secret>&since=<ISO-8601>&until=<ISO-8601>
 *   -> {since, until, files_read, events, sessions, arrivals, by_event}
 *
 * Defaults to the last 48 hours. A window over 14 days is refused (400).
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/atlas_window.php';

header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$secretFile = __DIR__ . '/pulse_secret.config.php';
if (!file_exists($secretFile)) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "atlas-window not configured: missing pulse_secret.config.php\n";
    exit;
}
require_once $secretFile;
if (!defined('OD9_PULSE_TOKEN') || OD9_PULSE_TOKEN === '') {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "atlas-window not configured: OD9_PULSE_TOKEN missing\n";
    exit;
}
$tokenIn = (string)($_GET['token'] ?? '');
if ($tokenIn === '' || !hash_equals(OD9_PULSE_TOKEN, $tokenIn)) {
    http_response_code(401);
    header('Content-Type: text/plain');
    echo "unauthorized\n";
    exit;
}

/* prod: the account's home, outside the docroot; anywhere else: the temp dir (same rule as atlas-ping.php) */
$dir = is_dir('/home/offda9') ? '/home/offda9/atlas-events' : sys_get_temp_dir() . '/atlas-events';

$until = (string)($_GET['until'] ?? gmdate('Y-m-d\TH:i:s\Z'));
$since = (string)($_GET['since'] ?? gmdate('Y-m-d\TH:i:s\Z', time() - 48 * 3600));

try {
    $summary = atlas_window_summary($dir, $since, $until);
} catch (InvalidArgumentException $e) {
    // Refused, and said so twice: to the caller (400 + reason) and to the error log,
    // so a bot misbuilding its window shows up here, not as a silent empty row.
    error_log('atlas-window: refused window since=' . $since . ' until=' . $until . ': ' . $e->getMessage());
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'bad window: ' . $e->getMessage() . "\n";
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($summary, JSON_UNESCAPED_SLASHES), "\n";
