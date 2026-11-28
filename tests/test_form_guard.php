<?php
/**
 * This site's half of the shared form checks (includes/od9_form_guard.php) does what the engine's files leave to
 * the site: it configures the guard as it loads, puts the logs outside the docroot, starts the session, prints the
 * fields and the script, loads the widget's keys, hands the human check a transport that really reaches Cloudflare,
 * checks the signed passes of the canary and the bot, and keeps the hourly limit. Standalone, no PHPUnit:
 *
 *   php tests/test_form_guard.php        (exit 0 = all pass)
 *
 * The guard's own rules (each refusal reason, the pass log, the brake) are the engine's and are proven in the
 * engine's FormGuardTest, HumanCheckTest and SignupBrakeTest against the same bytes this site carries
 * (tests/check_form_checks.py proves the copies equal). This file proves the part that differs per site, and two
 * end-to-end steps through the copies: a clean submission and a pass.
 *
 * NETWORK: one check asks Cloudflare's real siteverify with its published always-pass TEST secret and dummy token
 * (developers.cloudflare.com/turnstile/troubleshooting/testing/), which is the only way to know the transport's
 * curl, TLS and encoding work. No answer is a FAIL that says so, never a pass.
 *
 * Until 2026-11-28 this file tested includes/FormGuard.php, this site's own port of the band's guard, retired in
 * piece 4 of the form checks for the engine's copies.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));

// Before any output: the session must start before anything is printed, and the logs go to a scratch folder.
$tmp = sys_get_temp_dir() . '/od9_fg_' . bin2hex(random_bytes(4));
mkdir($tmp);
putenv('FORM_GUARD_LOGDIR=' . $tmp);
putenv('SESSION_STORE_ROOT=' . $tmp . '/sessions');
putenv('TURNSTILE_SITEKEY');
putenv('TURNSTILE_SECRET_KEY');
require 'includes/od9_form_guard.php';
od9_form_guard_session();

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

// Cloudflare's PUBLISHED test keys (developers.cloudflare.com/turnstile/troubleshooting/testing/), built from their
// documented shape so no key-shaped literal sits in the repository: "1x" passes everyone, "2x" fails everyone.
$cfSite = static fn(string $kind, string $tail): string => $kind . 'x' . str_repeat('0', 20) . $tail;
$cfSecret = static fn(string $kind): string => $kind . 'x' . str_repeat('0', 31) . 'AA';
$cfToken = 'XXXX.DUMMY.TOKEN.XXXX';

// ---- where the logs go, and the configure the file does as it loads ----
ok(od9_form_guard_log_dir('/home/offda9/public_html/includes') === '/home/offda9/form-guard',
   'on the server the guard\'s logs go outside the docroot, in the account\'s own folder');
ok(od9_form_guard_log_dir('C:/xampp/htdocs/od9/includes') === 'C:/xampp/htdocs/od9/logs', 'locally they go to the checkout\'s logs/, which git ignores');
ok(FormGuard::logFile() === $tmp . '/form-guard.jsonl' && od9_form_rate_file() === $tmp . '/form-guard-rate.json',
   'FORM_GUARD_LOGDIR moves the refusal log and the rate file together (the tests\' own refusals never reach the real logs)');
$transportProp = new ReflectionProperty(FormGuard::class, 'humanCheckTransport');
$configured = $transportProp->getValue();
ok($configured instanceof Closure, 'loading the file configured the guard with a transport (a check with none refuses every visitor)');
$threw = null;
try {
    $configured('http://challenges.cloudflare.com/turnstile/v0/siteverify', [], 1, 1);
} catch (RuntimeException $e) {
    $threw = $e->getMessage();
}
ok($threw === 'the human check asks Cloudflare over HTTPS only', 'the configured transport is this site\'s: it refuses anything but HTTPS');

// ---- the transport really reaches Cloudflare (network) ----
$live = new HumanCheck($cfSite('1', 'AA'), $cfSecret('1'), od9_human_check_transport());
$verdict = $live->verify($cfToken, '203.0.113.9');
ok($verdict['ok'] === true && $verdict['detail'] === 'passed',
   'Cloudflare\'s real siteverify, asked through this transport with the published test secret, says passed (got ' . $verdict['detail'] . ')');
$no = new HumanCheck($cfSite('2', 'AB'), $cfSecret('2'), od9_human_check_transport());
$verdict = $no->verify($cfToken);
ok($verdict['ok'] === false && strpos($verdict['detail'], 'rejected:') === 0,
   'and with the always-fail test secret it says no, as a rejection and not an outage (got ' . $verdict['detail'] . ')');

// ---- the widget's keys ----
ok(HumanCheck::fromEnvironment()->state() === HumanCheck::OFF && od9_form_guard_load_keys($tmp . '/none.config.php') === false,
   'with no keys file the human check is off');
file_put_contents($tmp . '/form-checks.config.php', sprintf("<?php\nputenv(%s);\nputenv(%s);\n",
    var_export(HumanCheck::SITE_KEY_ENV . '=' . $cfSite('1', 'AA'), true), var_export(HumanCheck::SECRET_KEY_ENV . '=' . $cfSecret('1'), true)));
ok(od9_form_guard_load_keys($tmp . '/form-checks.config.php') === true && HumanCheck::fromEnvironment()->state() === HumanCheck::ON,
   'a keys file of putenv lines switches the check on');
ok(HumanCheck::fromEnvironment()->stateBehind(null) === HumanCheck::TEST_KEYS,
   'and the health answer would say test_keys, never on, for Cloudflare\'s published keys');
putenv('TURNSTILE_SITEKEY');
putenv('TURNSTILE_SECRET_KEY');

// ---- fields and script ----
FormGuard::open($_SESSION);
$proof = $_SESSION[FormGuard::SESSION_KEY]['proof'] ?? '';
$fields = od9_form_guard_fields();
ok($proof !== '' && strpos($fields, 'data-fg="' . $proof . '"') !== false && strpos($fields, 'name="' . FormGuard::TRAP_FIELD . '"') !== false,
   'on a page with a session the fields carry the trap and the session\'s proof');
$script = od9_form_guard_script();
ok(strpos($script, FormGuard::PROOF_FIELD) !== false && od9_form_guard_script() === '', 'the script is printed once per page, however many forms call for it');

// ---- the signed passes ----
$secret = 'selftest-shared-secret';
$body = '{"email":"fan@example.com","source":"discord_link"}';
ok(od9_form_caller_signature($secret, 'discord-bot', 1800000000, $body) === '4c6aecfdca26038a68cec3c66a74df4d77b1f892b64212ad1cfbf1a466feaa98',
   'the signature matches the vector the bot\'s tests pin (computed with Python\'s hmac): the two sides cannot drift apart quietly');
$now = 1_800_000_000;
$signed = static function (string $name, int $time, string $sentBody) use ($secret): array {
    return ['HTTP_X_OD9_CALLER' => $name, 'HTTP_X_OD9_CALLER_TIME' => (string) $time,
            'HTTP_X_OD9_CALLER_SIGNATURE' => od9_form_caller_signature($secret, $name, $time, $sentBody)];
};
$bot = [OD9_FORM_CALLER_BOT];
ok(od9_form_caller($bot, $body, $signed('discord-bot', $now, $body), $secret, $now) === 'discord-bot', 'a signed bot request gets its pass');
ok(od9_form_caller($bot, $body, $signed('discord-bot', $now - 60, $body), $secret, $now) === 'discord-bot'
   && od9_form_caller($bot, $body, $signed('discord-bot', $now + 60, $body), $secret, $now) === 'discord-bot', 'sixty seconds either way is inside the window');
ok(od9_form_caller($bot, $body, $signed('discord-bot', $now - 61, $body), $secret, $now) === null, 'a signature older than sixty seconds gets none');
ok(od9_form_caller($bot, $body, $signed('discord-bot', $now + 61, $body), $secret, $now) === null, 'nor one from sixty-one seconds ahead');
ok(od9_form_caller($bot, str_replace('fan@', 'stranger@', $body), $signed('discord-bot', $now, $body), $secret, $now) === null,
   'a signature over another body gets none: it cannot be reused for another address');
ok(od9_form_caller($bot, $body, $signed('discord-bot', $now, $body), 'another-secret', $now) === null, 'a signature made with another secret gets none');
ok(od9_form_caller([OD9_FORM_CALLER_CONTACT], $body, $signed('discord-bot', $now, $body), $secret, $now) === null,
   'a form that does not take the bot gives the bot no pass, even signed');
ok(od9_form_caller($bot, $body, $signed('stranger', $now, $body), $secret, $now) === null, 'a caller no form takes gets none');
ok(od9_form_caller($bot, $body, $signed('discord-bot', $now, $body), '', $now) === null, 'a server with no shared secret gives no pass');
$broken = $signed('discord-bot', $now, $body);
$broken['HTTP_X_OD9_CALLER_SIGNATURE'] = strtoupper($broken['HTTP_X_OD9_CALLER_SIGNATURE']);
ok(od9_form_caller($bot, $body, $broken, $secret, $now) === null, 'a signature not in the lower-case hex form gets none');
$broken = $signed('discord-bot', $now, $body);
$broken['HTTP_X_OD9_CALLER_TIME'] = 'soon';
ok(od9_form_caller($bot, $body, $broken, $secret, $now) === null, 'a time that is not a number gets none');
ok(od9_form_caller($bot, $body, [], $secret, $now) === null, 'a request that names no caller is a browser\'s');

// ---- the guard, end to end through the copies ----
$session = [];
FormGuard::open($session, $now);
$good = ['email' => 'fan@example.com', FormGuard::TRAP_FIELD => '', FormGuard::PROOF_FIELD => $session[FormGuard::SESSION_KEY]['proof']];
$admitted = true;
try {
    FormGuard::admit('od9-subscribe', $session, $good, ['REMOTE_ADDR' => '198.51.100.20'], $now + 10, null);
} catch (FormGuardRefused $e) {
    $admitted = false;
}
ok($admitted, 'a person who loaded the page, waited and ran its script is admitted while the check is off');
$refused = null;
try {
    FormGuard::admit('od9-subscribe', [], $good, ['REMOTE_ADDR' => '198.51.100.21'], $now + 10, 'discord-bot');
} catch (FormGuardRefused $e) {
    $refused = $e->reason;
}
ok($refused === 'no_page', 'a pass skips Cloudflare\'s question and nothing else: a pass with no page behind it is refused');
FormGuard::admit('od9-subscribe', $session, $good, ['REMOTE_ADDR' => '198.51.100.22'], $now + 10, 'discord-bot');
ok(FormGuard::countPasses(86400, time()) === 1, 'a pass that is used is one line in the pass log, which the health answer counts');

// ---- the hourly limit (moved from the retired guard, now per form) ----
$over = [];
for ($i = 0; $i < OD9_FORM_SENDS_PER_HOUR + 1; $i++) {
    $over[] = od9_form_over_limit('contact', '198.51.100.7', OD9_FORM_SENDS_PER_HOUR, 3600, $now + $i);
}
ok(array_slice($over, 0, OD9_FORM_SENDS_PER_HOUR) === array_fill(0, OD9_FORM_SENDS_PER_HOUR, false), 'the first five from an address inside an hour are allowed');
ok(end($over) === true, 'the sixth is over the limit');
ok(od9_form_over_limit('contact', '198.51.100.8', OD9_FORM_SENDS_PER_HOUR, 3600, $now + 10) === false, 'another address is counted on its own');
ok(od9_form_over_limit('od9-subscribe', '198.51.100.7', OD9_FORM_SENDS_PER_HOUR, 3600, $now + 10) === false, 'another form is counted on its own');
ok(od9_form_over_limit('contact', '198.51.100.7', OD9_FORM_SENDS_PER_HOUR, 3600, $now + 3601 + OD9_FORM_SENDS_PER_HOUR) === false, 'an hour later the address may send again');
ok(strpos((string) file_get_contents(od9_form_rate_file()), '198.51.100') === false, 'the rate file holds hashes, never an address');
$limited = null;
try {
    for ($i = 0; $i < OD9_FORM_SENDS_PER_HOUR + 1; $i++) {
        od9_form_limit('contact', ['REMOTE_ADDR' => '192.0.2.44'], $now + 20 + $i);
    }
} catch (FormGuardRefused $e) {
    $limited = $e;
}
ok($limited instanceof FormGuardRefused && $limited->reason === 'rate' && $limited->getMessage() === OD9_FORM_LIMITED, 'od9_form_limit() throws the rate refusal');
$log = is_file(FormGuard::logFile()) ? file(FormGuard::logFile(), FILE_IGNORE_NEW_LINES) : [];
$row = $log ? json_decode((string) end($log), true) : null;
ok(is_array($row) && $row['form'] === 'contact' && $row['reason'] === 'rate' && $row['ip'] === '192.0.2.44', 'the refusal is one line in the guard\'s log, with its reason');

session_write_close();
$clean = static function (string $dir) use (&$clean): void {
    foreach (glob($dir . '/{,.}*', GLOB_BRACE) ?: [] as $p) {
        if (basename($p) === '.' || basename($p) === '..') { continue; }
        is_dir($p) ? $clean($p) : unlink($p);
    }
    rmdir($dir);
};
$clean($tmp);
echo "{$pass} passed, {$fail} failed\n";
exit($fail === 0 && $pass > 0 ? 0 : 1);
