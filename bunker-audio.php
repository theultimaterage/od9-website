<?php
/**
 * bunker-audio.php — auth-gated, range-aware audio proxy for Bunker exclusives.
 *
 * The Bunker's exclusive audio (e.g. Phuryous Stylez Vol. 1) lives in
 * library/bunker/, whose directory denies direct HTTP access (library/.htaccess).
 * This proxy is the ONLY path to those bytes: it verifies the visitor is a
 * logged-in member (Observer+) BEFORE streaming, so the exclusive is real, not a
 * guessable link — the same model as download.php for gated PDFs.
 *
 * Unlike download.php (PDF, whole-body 200), this supports HTTP Range / 206 so
 * the browser <audio> element can scrub, seek, and resume without re-downloading.
 *
 *   bunker-audio.php?file=02-demons.mp3
 *
 * Tier threshold is intentionally Observer+ (any logged-in member): Phuryous is a
 * launch reward to drive engagement; future drops can raise the bar. The gate is
 * still real — the dir is denied and this checks login first.
 */
declare(strict_types=1);

// patron_gate.php sits beside the docroot locally but inside it on prod.
$gatePath = null;
foreach ([__DIR__ . '/includes/patron_gate.php', __DIR__ . '/../includes/patron_gate.php'] as $cand) {
    if (is_file($cand)) { $gatePath = $cand; break; }
}
if ($gatePath === null) {
    http_response_code(503);                       // fail CLOSED — never serve if the gate can't load
    header('Content-Type: text/plain; charset=utf-8');
    exit("Bunker temporarily unavailable.\n");
}
require_once $gatePath;

const BUNKER_DIR = __DIR__ . '/library/bunker';
const BUNKER_ARCHIVE_DIR = __DIR__ . '/library/bunker-archive';   // rotated-out drops (throwback/mixtape)
const MIN_TIER   = 'observer';                      // any logged-in member

$file = (string)($_GET['file'] ?? '');
// ?vault=1 streams a rotated-out track from the archive (throwback/mixtape). The
// Observer+ gate, allow-list, and anti-traversal are unchanged — only the base
// directory differs, so a vault link is exactly as safe as a live-drop link.
$fromVault = !empty($_GET['vault']) && $_GET['vault'] !== '0';
$baseDir = $fromVault ? BUNKER_ARCHIVE_DIR : BUNKER_DIR;

// Validate: bare *.mp3, no path parts.
if (basename($file) !== $file || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.mp3$/', $file)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Bad request.\n");
}

// Resolve + confirm the file is genuinely inside the chosen base dir (anti-traversal).
$real = realpath($baseDir . '/' . $file);
$base = realpath($baseDir);
if ($real === false || $base === false
    || strncmp($real, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Not found.\n");
}

// Authorize: must be a logged-in member at MIN_TIER or higher. Plain 403 (this is
// an asset endpoint hit by <audio>, not a page) — the card itself lives behind the
// dashboard login, so a 403 here is the abuse/guessing case.
if (!tier_at_least(MIN_TIER)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Forbidden — log in as a member to play Bunker exclusives.\n");
}

// ---- authorized: stream with HTTP Range support ----
$size = filesize($real);
$start = 0;
$end   = $size - 1;
$status = 200;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m)) {
    if ($m[1] === '' && $m[2] !== '') {            // suffix range: last N bytes
        $start = max(0, $size - (int)$m[2]);
        $end   = $size - 1;
    } else {
        $start = (int)$m[1];
        $end   = ($m[2] === '') ? $size - 1 : (int)$m[2];
    }
    $end = min($end, $size - 1);
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header("Content-Range: bytes */$size");
        exit;
    }
    $status = 206;
}

while (ob_get_level() > 0) { ob_end_clean(); }     // don't buffer (or gzip) audio
http_response_code($status);
header('Content-Type: audio/mpeg');
header('Accept-Ranges: bytes');
header('Content-Length: ' . ($end - $start + 1));
if ($status === 206) {
    header("Content-Range: bytes $start-$end/$size");
}
header('Content-Disposition: inline; filename="' . basename($real) . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');                   // exclusives never enter a search index
header('Cache-Control: private, max-age=0, must-revalidate');

$fp = fopen($real, 'rb');
if ($fp === false) { http_response_code(500); exit; }
fseek($fp, $start);
$remaining = $end - $start + 1;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, (int)min(8192, $remaining));
    if ($chunk === false) break;
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($fp);
exit;
