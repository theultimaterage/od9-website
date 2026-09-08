<?php
/**
 * error-beacon.php — the rest of the site learns to report its own faults
 * (2026-09-08).
 *
 * The Atlas has had an error beacon since 2026-09-05 and it earned its keep in
 * a day: 87 identical TypeErrors from one phone session revealed that lifting
 * one finger out of a pinch froze the map. Nobody would ever have reported that
 * as a bug — it felt like a stutter. Every other page on this site had exactly
 * that blind spot, so this is the same idea pointed at all of them.
 *
 * Separate from atlas-ping.php on purpose. That endpoint measures the Atlas's
 * funnel, and mixing site-wide error noise into it would corrupt the numbers
 * tools/atlas_stats.py reports. Same shape, same privacy rules, different
 * ledger.
 *
 * Privacy by construction: no cookie, no IP, no user agent beyond phone or
 * desktop. The session id is a random string the tab keeps for its lifetime.
 * The page path is recorded because "an error happened somewhere" is not
 * actionable; the query string is dropped because it can carry identifiers.
 */
declare(strict_types=1);

const MAX_BODY = 4096;
const KEEP = ['page', 'msg', 'src', 'line', 'col', 'kind'];

$dir = is_dir('/home/offda9') ? '/home/offda9/site-errors' : sys_get_temp_dir() . '/site-errors';

header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '' || strlen($raw) > MAX_BODY) {
    http_response_code(413);
    exit;
}
$j = json_decode($raw, true);
if (!is_array($j)) {
    http_response_code(400);
    exit;
}

$row = [
    'at'  => gmdate('Y-m-d\TH:i:s\Z'),
    'sid' => substr(preg_replace('/[^a-z0-9]/', '', strtolower((string)($j['sid'] ?? ''))), 0, 16),
    'vp'  => in_array($j['vp'] ?? '', ['phone', 'desktop'], true) ? $j['vp'] : 'unknown',
];
foreach (KEEP as $k) {
    $v = $j[$k] ?? null;
    if (is_int($v)) {
        $row[$k] = $v;
    } elseif (is_string($v) && $v !== '') {
        $row[$k] = mb_substr($v, 0, $k === 'msg' ? 300 : 160);
    }
}
if (!isset($row['msg'])) {          // an error record with no error is noise
    http_response_code(400);
    exit;
}

try {
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException("cannot create $dir");
    }
    $file = $dir . '/' . gmdate('Y-m-d') . '.jsonl';
    if (file_put_contents($file, json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
                          FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException("cannot append to $file");
    }
} catch (Throwable $e) {
    error_log('error-beacon: ' . $e->getMessage());   /* the visitor never waits on this; the log does */
}
http_response_code(204);
