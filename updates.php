<?php
/**
 * updates.php — RETIRED (2026-07-09).
 *
 * The hand-written build-in-public log went stale (last entry March 2026) and
 * community updates ship in-community via /announce-update (Discord). The
 * page's outsider-facing "is this project alive?" job is done by the live
 * ledgers instead. 301 preserved because the old page was indexed.
 */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
http_response_code(301);
header('Location: ' . $base . '/progress.php');
exit;
