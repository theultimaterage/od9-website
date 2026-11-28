<?php
/**
 * The site's forms, asked over HTTP on local Apache the way a visitor, a program and the bot ask them (2026-11-28,
 * piece 4 of the form checks). Standalone, no PHPUnit:
 *
 *   php tests/test_forms_http.php        (exit 0 = all pass; local XAMPP must be running)
 *
 * tests/test_public_forms_use_the_guard.php proves the code calls the guard in order; this proves the calls WORK
 * through the real pages and handlers: the session and the stamp, the proof the page prints, the popup's stamp door,
 * the guard's refusals as JSON and as redirects, the bot's signed pass (one line in the pass log), and the health
 * answer's fields. Every post that should get past the guard carries an address that is invalid on purpose, so
 * nothing is stored and nothing is mailed even if a check here fails open: what is read is WHICH step answered.
 *
 * It asks 127.0.0.1, not offda9.com: the Cloudflare-only rule in .htaccess refuses a request naming the site from an
 * address that is not Cloudflare's, and this is not a test of that rule (tests/check_cloudflare_only.py is).
 * On this checkout the guard's logs are logs/ (includes/od9_form_guard.php), so the pass this file sends is counted
 * there, in the local evidence and never the server's.
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

const BASE = 'http://127.0.0.1/od9';
const WAIT_US = 3_300_000;   // past FormGuard::MIN_SECONDS (3), as a person's typing is

/** One visitor: a cookie jar of its own. */
final class Visitor
{
    private string $jar;

    public function __construct()
    {
        $this->jar = (string) tempnam(sys_get_temp_dir(), 'od9fh');
    }

    /** @return array{status: int, headers: string, body: string} */
    public function ask(string $method, string $path, ?string $body = null, array $headers = []): array
    {
        $ch = curl_init(BASE . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $raw = is_string($raw) ? $raw : '';
        return ['status' => $status, 'headers' => substr($raw, 0, $size), 'body' => substr($raw, $size)];
    }

    public function proofFrom(string $page): string
    {
        $r = $this->ask('GET', $page);
        return preg_match('/name="fg_proof" value="" data-fg="([0-9a-f]{32})"/', $r['body'], $m) === 1 ? $m[1] : '';
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }
}

function json_of(array $r): array
{
    $d = json_decode($r['body'], true);
    return is_array($d) ? $d : [];
}

$probe = (new Visitor())->ask('GET', '/robots.txt');
if ($probe['status'] !== 200) {
    ok(false, 'local Apache answers ' . BASE . '/robots.txt (got ' . $probe['status'] . '): start XAMPP; this test cannot run without it');
    echo "{$pass} passed, {$fail} failed\n";
    exit(1);
}

// ---- the pages ----
$v = new Visitor();
$join = $v->ask('GET', '/join.php');
preg_match_all('/name="fg_proof" value="" data-fg="([0-9a-f]{32})"/', $join['body'], $m);
ok($join['status'] === 200 && count($m[1]) >= 2 && count(array_unique($m[1])) === 1,
   'join.php renders its form and the popup with the session\'s one proof (' . count($m[1]) . ' forms)');
ok(stripos($join['headers'], 'Set-Cookie:') !== false && strpos($join['body'], 'name="fg_ref"') !== false, 'join.php starts the session and carries the trap');
ok(strpos($join['body'], '<b>Warning</b>') === false && strpos($join['body'], 'Fatal error') === false, 'join.php renders without a PHP warning');
$about = (new Visitor())->ask('GET', '/about.php');
ok($about['status'] === 200 && strpos($about['body'], 'id="popupSignupForm"') !== false
   && strpos($about['body'], 'name="fg_proof" value="" data-fg=""') !== false,
   'a page with no form of its own carries the popup with an empty proof');
ok(stripos($about['headers'], 'Set-Cookie:') === false, 'and starts no session: a page view is not a session file');
$stampVisitor = new Visitor();
$stamp = $stampVisitor->ask('GET', '/form-stamp.php');
$stampJson = json_of($stamp);
ok($stamp['status'] === 200 && preg_match('/^[0-9a-f]{32}$/', (string) ($stampJson['proof'] ?? '')) === 1 && ($stampJson['min_seconds'] ?? null) === 3,
   'form-stamp.php answers a proof and the guard\'s wait');
ok(stripos($stamp['headers'], 'Cache-Control: no-store') !== false && stripos($stamp['headers'], 'Set-Cookie:') !== false,
   'form-stamp.php is never cached and starts the session it stamps');
ok((new Visitor())->ask('POST', '/form-stamp.php', '')['status'] === 405, 'form-stamp.php takes no POST');

// ---- the signup door, as a program and as a person ----
$bad = json_encode(['name' => 'OD9 test', 'email' => 'not-an-address', 'source' => 'join-page']);
$asProgram = (new Visitor())->ask('POST', '/subscribe.php', (string) $bad, ['Content-Type: application/json']);
$j = json_of($asProgram);
ok($asProgram['status'] === 403 && ($j['refused'] ?? '') === 'no_page' && ($j['success'] ?? null) === false,
   'a post straight at subscribe.php, with no page behind it, is refused as no_page (403, in JSON)');

$fast = new Visitor();
$proof = $fast->proofFrom('/join.php');
$r = $fast->ask('POST', '/subscribe.php', (string) json_encode(['name' => 'OD9 test', 'email' => 'not-an-address', 'fg_ref' => '', 'fg_proof' => $proof]), ['Content-Type: application/json']);
ok($r['status'] === 403 && (json_of($r)['refused'] ?? '') === 'too_fast', 'a post sent the instant the page arrived is refused as too_fast');

$person = new Visitor();
$proof = $person->proofFrom('/join.php');
usleep(WAIT_US);
$r = $person->ask('POST', '/subscribe.php', (string) json_encode(['name' => 'OD9 test', 'email' => 'not-an-address', 'fg_ref' => 'filled', 'fg_proof' => $proof]), ['Content-Type: application/json']);
ok($r['status'] === 403 && (json_of($r)['refused'] ?? '') === 'trap', 'a filled trap is refused as trap');
$r = $person->ask('POST', '/subscribe.php', (string) json_encode(['name' => 'OD9 test', 'email' => 'not-an-address', 'fg_ref' => '', 'fg_proof' => 'nope']), ['Content-Type: application/json']);
ok($r['status'] === 403 && (json_of($r)['refused'] ?? '') === 'no_script', 'a wrong proof is refused as no_script');
$r = $person->ask('POST', '/subscribe.php', (string) json_encode(['name' => 'OD9 test', 'email' => 'not-an-address', 'fg_ref' => '', 'fg_proof' => $proof]), ['Content-Type: application/json']);
ok($r['status'] === 400 && (json_of($r)['error'] ?? '') === 'Please enter a valid email address.',
   'a person who loaded join.php, waited and sent its proof gets past the guard to validation (an invalid address, so nothing is stored)');

$popup = new Visitor();
$stampJson = json_of($popup->ask('GET', '/form-stamp.php'));
usleep(WAIT_US);
$r = $popup->ask('POST', '/subscribe.php', (string) json_encode(['name' => 'OD9 test', 'email' => 'not-an-address', 'fg_ref' => '', 'fg_proof' => (string) ($stampJson['proof'] ?? '')]), ['Content-Type: application/json']);
ok($r['status'] === 400, 'the popup\'s path (the stamp from form-stamp.php, then the wait) gets past the guard too');

// ---- the bot's signed pass ----
require_once 'includes/od9_form_guard.php';
$secret = od9_form_caller_secret();
ok($secret !== '', 'this checkout has the shared secret the bot signs with (api/drip/webhook_secret.config.php)');
$passLog = 'logs/form-guard-passes.jsonl';
$lines = static fn(): int => is_file($passLog) ? count(file($passLog, FILE_IGNORE_NEW_LINES)) : 0;
$sign = static function (string $body, string $key, string $name = 'discord-bot'): array {
    $t = time();
    return ['Content-Type: application/json', 'X-OD9-Caller: ' . $name, 'X-OD9-Caller-Time: ' . $t,
            'X-OD9-Caller-Signature: ' . od9_form_caller_signature($key, $name, $t, $body)];
};
$bot = new Visitor();
$proof = $bot->proofFrom('/join.php');
usleep(WAIT_US);
$before = $lines();
$body = (string) json_encode(['email' => 'not-an-address', 'discord_user_id' => '123456789012345678', 'source' => 'discord_link',
                              'name' => 'OD9 test', 'fg_ref' => '', 'fg_proof' => $proof]);
$r = $bot->ask('POST', '/subscribe.php', $body, $sign($body, $secret));
$after = $lines();
$last = $after > 0 ? json_decode((string) (file($passLog, FILE_IGNORE_NEW_LINES)[$after - 1] ?? ''), true) : null;
ok($r['status'] === 400 && $after === $before + 1 && is_array($last) && ($last['pass'] ?? '') === 'discord-bot' && ($last['form'] ?? '') === 'od9-subscribe',
   'the bot, signed and like a browser, gets past the guard and its pass is one line in the pass log');
$before = $lines();
$r = $bot->ask('POST', '/subscribe.php', $body, $sign($body, 'not-the-secret'));
ok($r['status'] === 400 && $lines() === $before, 'a signature made with another secret gives no pass (judged as a browser while the check is off; nothing logged as a pass)');
$r = (new Visitor())->ask('POST', '/subscribe.php', $body, $sign($body, $secret));
ok($r['status'] === 403 && (json_of($r)['refused'] ?? '') === 'no_page', 'a signed request with no page behind it is still refused: the pass skips Cloudflare\'s question and nothing else');

// ---- the contact door ----
$c = new Visitor();
$proof = $c->proofFrom('/contact.php');
ok($proof !== '', 'contact.php carries the session\'s proof');
$form = static fn(array $f): string => http_build_query($f);
$r = $c->ask('POST', '/contact-handler.php', $form(['name' => 'OD9 test', 'email' => 'not-an-address', 'subject' => 'other', 'message' => 'x', 'fg_ref' => '', 'fg_proof' => '']));
ok($r['status'] === 302 && preg_match('/Location: contact\.php\?error=guard/i', $r['headers']) === 1, 'a contact post without the proof goes back with error=guard');
usleep(WAIT_US);
$r = $c->ask('POST', '/contact-handler.php', $form(['name' => 'OD9 test', 'email' => 'not-an-address', 'subject' => 'other', 'message' => 'x', 'fg_ref' => '', 'fg_proof' => $proof]));
ok($r['status'] === 302 && preg_match('/Location: contact\.php\?error=email/i', $r['headers']) === 1,
   'with the page, the wait and the proof it gets past the guard to validation (an invalid address, so nothing is mailed)');
$cv = new Visitor();
$proof = $cv->proofFrom('/contact.php');
usleep(WAIT_US);
$before = $lines();
$fields = $form(['name' => 'OD9 test', 'email' => 'not-an-address', 'subject' => 'other', 'message' => 'x', 'fg_ref' => '', 'fg_proof' => $proof]);
$canary = $sign($fields, $secret, 'contact-canary');
$canary[0] = 'Content-Type: application/x-www-form-urlencoded';
$r = $cv->ask('POST', '/contact-handler.php', $fields, $canary);
$after = $lines();
$last = $after > 0 ? json_decode((string) (file($passLog, FILE_IGNORE_NEW_LINES)[$after - 1] ?? ''), true) : null;
ok(preg_match('/error=email/', $r['headers']) === 1 && $after === $before + 1 && ($last['pass'] ?? '') === 'contact-canary',
   'the canary\'s signed form post gets its pass on the contact form, counted in the pass log');
$before = $lines();
$botOnContact = $sign($fields, $secret, 'discord-bot');
$botOnContact[0] = 'Content-Type: application/x-www-form-urlencoded';
$cv->ask('POST', '/contact-handler.php', $fields, $botOnContact);
ok($lines() === $before, 'the bot\'s signature is no pass on the contact form');
$page = (new Visitor())->ask('GET', '/contact.php?error=human');
ok(strpos($page['body'], 'confirm that a person sent this') !== false, 'contact.php tells a visitor refused by the human check what to do');

// ---- the health answer ----
$h = (new Visitor())->ask('GET', '/api/health/signup-volume.php');
$hj = json_of($h);
ok($h['status'] === 200 && in_array($hj['status'] ?? null, ['OK', 'FAIL'], true) && ($hj['human_check'] ?? null) === 'off'
   && is_int($hj['passes_24h'] ?? null) && is_bool($hj['paused'] ?? null) && is_int($hj['signups'] ?? null),
   'the health answer carries the fields the reader reads, with the human check off (' . substr($h['body'], 0, 160) . ')');
ok(stripos($h['headers'], 'Cache-Control: no-store') !== false, 'the health answer is never cached');

echo "{$pass} passed, {$fail} failed\n";
exit($fail === 0 && $pass > 0 ? 0 : 1);
