<?php
/**
 * Discord login diagnostics stay out of the PHP error log (2026-10-07). Standalone:
 *
 *   php tests/test_oauth_log.php        (exit 0 = all pass)
 *
 * Every visit to the login page used to error_log() an [oauth-dbg] line carrying
 * the visitor's browser. The bot's error-log sentinel alerts on any line shape it
 * has not seen, so each new browser lit up the production board as a "new fault".
 * They now go through od9_oauth_log() to logs/oauth-flow.log. This fails if any
 * code sends [oauth-dbg] to error_log() again (the helper's own last-resort
 * fallback excepted), and proves the helper writes the line where it says.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

// 1. No [oauth-dbg] reaches error_log() outside the helper's fallback.
$offenders = [];
$scanned = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $path = str_replace('\\', '/', $f->getPathname());
    if (substr($path, -4) !== '.php' || preg_match('#/(\.git|vendor|node_modules|tools|tests)/#', $path)) {
        continue;
    }
    $scanned++;
    foreach (file($path) as $n => $line) {
        if (strpos($line, 'error_log(') !== false && strpos($line, 'oauth-dbg') !== false) {
            $offenders[] = $path . ':' . ($n + 1);
        }
    }
}
ok($scanned > 50, "the scan read the site's PHP ({$scanned} files)");
$allowed = array_filter($offenders, fn($o) => str_starts_with($o, './dashboard/includes/auth.php:'));
ok(count($allowed) === 1, 'the helper keeps exactly one error_log fallback');
ok(count($offenders) === 1, 'no other code sends [oauth-dbg] to the error log'
   . (count($offenders) > 1 ? ': ' . implode(', ', array_diff($offenders, $allowed)) : ''));

// The scan must be able to find something: a known-positive line is caught.
$probe = "error_log('[oauth-dbg] start ua=x');";
ok(strpos($probe, 'error_log(') !== false && strpos($probe, 'oauth-dbg') !== false,
   'the scan rule matches a known offending line');

// 2. The helper writes the line to oauth-flow.log.
require_once 'dashboard/includes/auth.php';
$marker = 'test-' . bin2hex(random_bytes(6));
$candidates = [dirname(getcwd()) . '/logs/oauth-flow.log', getcwd() . '/logs/oauth-flow.log'];
od9_oauth_log("start ua={$marker}");
$found = '';
foreach ($candidates as $c) {
    if (is_file($c) && strpos((string)file_get_contents($c), $marker) !== false) {
        $found = $c;
        break;
    }
}
ok($found !== '', 'od9_oauth_log wrote the line to oauth-flow.log' . ($found ? " ({$found})" : ''));
if ($found) {
    $lines = file($found, FILE_IGNORE_NEW_LINES);
    $hit = array_values(array_filter($lines, fn($l) => strpos($l, $marker) !== false));
    ok((bool)preg_match('/^\[\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\] \[oauth-dbg\] start ua=' . $marker . '$/', $hit[0] ?? ''),
       'the line carries a UTC timestamp and the [oauth-dbg] tag');
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
