<?php
/**
 * The call-in door's live state for cross-origin readers — the show rundown
 * (a local file:// page, tools/build_rundown.py in the bot repo) polls this
 * every 30 s and paints DOOR OPEN / CLOSED in its header.
 *
 * WHY a PHP wrapper around a static file: Cloudflare blocks direct fetches of
 * /callin/state.json for everyone, browsers included (a WAF rule on the path;
 * seen 2026-09-04, Ray a360547e8d3211cd), while /callin/ itself is served.
 * Same bytes the bot publishes (utils/web_publish.py), plus the CORS header
 * and no-store so no cache ever answers with a stale door.
 */
declare(strict_types=1);

$path = __DIR__ . '/state.json';
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');
if (!is_file($path)) {
    http_response_code(404);
    echo '{"error":"no state published yet"}';
    exit;
}
readfile($path);
