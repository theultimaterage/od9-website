<?php
/**
 * Prover for api/v1/lib/atlas_window.php — the arc ledger's Atlas count.
 * Standalone, no PHPUnit, no server, no real event files:
 *
 *   php tests/test_atlas_window.php        (run from repo root)
 *
 * Writes a fixture into a temp dir: two UTC day files, events inside and
 * outside the window, one probe event, one repeated session, one broken line.
 * Exit code 1 on any failed assertion.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));
require 'api/v1/lib/atlas_window.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  ok   $name\n";
    } else {
        $fail++;
        echo "  FAIL $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

$dir = sys_get_temp_dir() . '/atlas-window-test-' . getmypid();
mkdir($dir, 0700, true);
$line = static fn(string $ts, string $sid, string $event, array $props = []): string =>
    json_encode(['ts' => $ts, 'sid' => $sid, 'event' => $event, 'vp' => 'desktop', 'props' => $props]) . "\n";

// Sunday service 2026-09-20 21:00Z; window = [21:00Z Sunday, 21:00Z Tuesday].
file_put_contents($dir . '/2026-09-20.jsonl',
    $line('2026-09-20T20:59:00Z', 'aaa1', 'arrive')                      // before the window
  . $line('2026-09-20T21:05:00Z', 'bbb2', 'arrive')                      // in
  . $line('2026-09-20T21:06:00Z', 'bbb2', 'card')                        // in, same session
  . $line('2026-09-20T22:00:00Z', 'ppp9', 'arrive', ['probe' => true])   // probe: ignored
  . "this line is not json\n"
);
file_put_contents($dir . '/2026-09-22.jsonl',
    $line('2026-09-22T20:30:00Z', 'ccc3', 'codex')                       // in
  . $line('2026-09-22T21:00:01Z', 'ddd4', 'arrive')                      // one second after: out
);
// 2026-09-21 has no file at all — must not error.

$r = atlas_window_summary($dir, '2026-09-20T21:00:00Z', '2026-09-22T21:00:00Z');
check('files read = 2 (the missing day is skipped)', $r['files_read'] === 2, json_encode($r));
check('events in window = 3', $r['events'] === 3, json_encode($r));
check('sessions = 2 distinct (bbb2, ccc3)', $r['sessions'] === 2, json_encode($r));
check('arrivals = 1 (bbb2 only; probe + out-of-window excluded)', $r['arrivals'] === 1, json_encode($r));
check('by_event counts', ($r['by_event'] ?? []) === ['arrive' => 1, 'card' => 1, 'codex' => 1], json_encode($r['by_event'] ?? null));
check('echoes the window in ISO Z', $r['since'] === '2026-09-20T21:00:00Z' && $r['until'] === '2026-09-22T21:00:00Z');

$empty = atlas_window_summary($dir, '2026-10-01T00:00:00Z', '2026-10-02T00:00:00Z');
check('empty window reads as zeros, not an error', $empty['events'] === 0 && $empty['sessions'] === 0 && $empty['files_read'] === 0);

$threw = false;
try {
    atlas_window_summary($dir, '2026-09-22T00:00:00Z', '2026-09-20T00:00:00Z');
} catch (InvalidArgumentException $e) {
    $threw = true;
}
check('until before since is refused', $threw);

$threw = false;
try {
    atlas_window_summary($dir, '2026-01-01T00:00:00Z', '2026-03-01T00:00:00Z');
} catch (InvalidArgumentException $e) {
    $threw = true;
}
check('a window over 14 days is refused', $threw);

$threw = false;
try {
    atlas_window_summary($dir, 'yesterday-ish', 'now-ish');
} catch (InvalidArgumentException $e) {
    $threw = true;
}
check('garbage timestamps are refused', $threw);

array_map('unlink', glob($dir . '/*.jsonl') ?: []);
rmdir($dir);

echo "\natlas-window: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
