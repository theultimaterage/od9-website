<?php
/**
 * atlas-ping.php — the Atlas's own measurement (2026-09-05, audit gap #1).
 *
 * The spec names four success measures — live-link opens, deep-link
 * arrivals from Shorts and Discord, card→codex click-through, and whether
 * the first frame lands — and none were counted: a #chNN arrival never
 * reaches the server log. js/atlas.js sends small events here with
 * navigator.sendBeacon; this appends one JSON line per event to a daily
 * file OUTSIDE the docroot. tools/atlas_stats.py (bot repo) reads them.
 *
 * Privacy by construction: no cookie, no IP, no user agent beyond
 * desktop/phone; the session id is a random string the page keeps for the
 * tab's lifetime. Only allow-listed event names are stored, props are
 * trimmed, and a body over 2 KB is dropped.
 */
declare(strict_types=1);

const EVENTS = ['arrive', 'arrival_end', 'card', 'codex', 'onward', 'tour', 'tour_end', 'find', 'sound',
                'guide', 'timeline', 'live_strip', 'section', 'error', 'me'];
const MAX_BODY = 2048;

/* prod: the account's home, outside the docroot; anywhere else: the temp dir */
$dir = is_dir('/home/offda9') ? '/home/offda9/atlas-events' : sys_get_temp_dir() . '/atlas-events';

header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > MAX_BODY) {
    http_response_code(413);
    exit;
}
$j = json_decode($raw, true);
if (!is_array($j) || !isset($j['event']) || !in_array($j['event'], EVENTS, true)) {
    http_response_code(400);
    exit;
}
$sid = preg_replace('/[^a-z0-9]/', '', strtolower((string)($j['sid'] ?? '')));
$props = [];
foreach ((array)($j['props'] ?? []) as $k => $v) {
    if (!is_string($k) || !preg_match('/^[a-z_]{1,24}$/', $k)) continue;
    if (is_bool($v) || is_int($v) || is_float($v)) { $props[$k] = $v; continue; }
    if (is_string($v)) { $props[$k] = mb_substr($v, 0, 160); }
}
$line = json_encode([
    'ts'    => gmdate('Y-m-d\TH:i:s\Z'),
    'sid'   => substr($sid, 0, 16),
    'event' => $j['event'],
    'vp'    => in_array($j['vp'] ?? '', ['phone', 'desktop'], true) ? $j['vp'] : 'unknown',
    'props' => $props,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException("cannot create $dir");
    }
    $file = $dir . '/' . gmdate('Y-m-d') . '.jsonl';
    if (file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException("cannot append to $file");
    }
} catch (Throwable $e) {
    error_log('atlas-ping: ' . $e->getMessage());   /* the visitor never waits on this; the log does */
}
http_response_code(204);
