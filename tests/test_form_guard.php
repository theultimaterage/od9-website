<?php
/**
 * FormGuard (includes/FormGuard.php) — every refusal reason, the pass, the refusal
 * log, and the hourly limit. Standalone, no PHPUnit:
 *
 *   php tests/test_form_guard.php        (exit 0 = all pass)
 *
 * A guard that refuses nothing and a guard that refuses everyone both look fine
 * until a real person tries the form, so each reason is proven to fire AND the
 * clean submission is proven to pass.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));
require 'includes/FormGuard.php';

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

$tmp = sys_get_temp_dir() . '/od9_fg_' . bin2hex(random_bytes(4));
mkdir($tmp);
FormGuard::$logFileOverride  = $tmp . '/form-guard.jsonl';
FormGuard::$rateFileOverride = $tmp . '/form-guard-rate.json';

$now = 1_800_000_000;
$session = [];
FormGuard::open($session, $now);
$proof = $session[FormGuard::SESSION_KEY]['proof'];
$good = ['name' => 'A', FormGuard::TRAP_FIELD => '', FormGuard::PROOF_FIELD => $proof];

ok(FormGuard::check($session, $good, $now + 10) === null, 'a person who waited and ran the script passes');
ok(FormGuard::check($session, [FormGuard::TRAP_FIELD => 'x'] + $good, $now + 10) === 'trap', 'a filled trap is refused');
ok(FormGuard::check($session, [FormGuard::TRAP_FIELD => ['x']] + $good, $now + 10) === 'trap', 'a trap sent as an array is refused');
ok(FormGuard::check([], $good, $now + 10) === 'no_page', 'a post with no rendered page in the session is refused');
ok(FormGuard::check($session, $good, $now + 1) === 'too_fast', 'a post one second after the render is refused');
ok(FormGuard::check($session, $good, $now + FormGuard::MIN_SECONDS) === null, 'a post exactly MIN_SECONDS after the render passes');
ok(FormGuard::check($session, [FormGuard::PROOF_FIELD => ''] + $good, $now + 10) === 'no_script', 'an empty proof is refused');
ok(FormGuard::check($session, [FormGuard::PROOF_FIELD => 'nope'] + $good, $now + 10) === 'no_script', 'a wrong proof is refused');
$noProof = $good;
unset($noProof[FormGuard::PROOF_FIELD]);
ok(FormGuard::check($session, $noProof, $now + 10) === 'no_script', 'a missing proof is refused');

$threw = null;
try {
    FormGuard::admit('contact', $session, [FormGuard::TRAP_FIELD => 'bot'] + $good, ['REMOTE_ADDR' => '203.0.113.9'], $now + 10);
} catch (FormGuardRefused $e) {
    $threw = $e;
}
ok($threw instanceof FormGuardRefused && $threw->reason === 'trap', 'admit() throws with the reason');
$log = is_file(FormGuard::$logFileOverride) ? file(FormGuard::$logFileOverride, FILE_IGNORE_NEW_LINES) : [];
$row = $log ? json_decode((string) end($log), true) : null;
ok(is_array($row) && $row['form'] === 'contact' && $row['reason'] === 'trap' && $row['ip'] === '203.0.113.9', 'the refusal is one line in the log, with its reason');

$admitted = true;
try {
    FormGuard::admit('contact', $session, $good, [], $now + 10);
} catch (FormGuardRefused $e) {
    $admitted = false;
}
ok($admitted, 'admit() lets a clean submission through');

$over = [];
for ($i = 0; $i < FormGuard::SENDS_PER_HOUR + 1; $i++) {
    $over[] = FormGuard::overLimit('198.51.100.7', FormGuard::SENDS_PER_HOUR, 3600, $now + $i);
}
ok(array_slice($over, 0, FormGuard::SENDS_PER_HOUR) === array_fill(0, FormGuard::SENDS_PER_HOUR, false), 'the first SENDS_PER_HOUR messages from an address are allowed');
ok(end($over) === true, 'the next one inside the hour is over the limit');
ok(FormGuard::overLimit('198.51.100.8', FormGuard::SENDS_PER_HOUR, 3600, $now + 10) === false, 'another address is counted on its own');
ok(FormGuard::overLimit('198.51.100.7', FormGuard::SENDS_PER_HOUR, 3600, $now + 3601 + FormGuard::SENDS_PER_HOUR) === false, 'an hour later the address is allowed again');
$rate = (string) file_get_contents(FormGuard::$rateFileOverride);
ok(strpos($rate, '198.51.100') === false, 'the rate file holds hashes, never an address');

$limited = null;
try {
    for ($i = 0; $i < FormGuard::SENDS_PER_HOUR + 1; $i++) {
        FormGuard::limit('contact', ['REMOTE_ADDR' => '192.0.2.44'], $now + 20 + $i);
    }
} catch (FormGuardRefused $e) {
    $limited = $e;
}
ok($limited instanceof FormGuardRefused && $limited->reason === 'rate' && $limited->getMessage() === FormGuard::LIMITED, 'limit() throws the rate refusal');

$fields = FormGuard::fields($session);
ok(strpos($fields, 'name="' . FormGuard::TRAP_FIELD . '"') !== false && strpos($fields, 'data-fg="' . $proof . '"') !== false, 'the form markup carries the trap and the proof');
ok(strpos(FormGuard::script(), FormGuard::PROOF_FIELD) !== false, 'the script copies into the proof field');

array_map('unlink', glob($tmp . '/*') ?: []);
rmdir($tmp);
echo "{$pass} passed, {$fail} failed\n";
exit($fail === 0 && $pass > 0 ? 0 : 1);
