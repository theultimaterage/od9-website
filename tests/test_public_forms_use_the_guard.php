<?php
/**
 * No public form of offda9.com stores or mails what a visitor typed without passing the shared form guard first.
 * Standalone, no PHPUnit:
 *
 *   php tests/test_public_forms_use_the_guard.php    (exit 0 = all pass)
 *
 * This site's form of the band site's and the engine's tests of the same name (2026-11-28, piece 4 of the form
 * checks: https://claude.ai/artifact/4UWXZQzkUhCvdCZ9Y9b4nR).
 *
 * WHY THIS IS A TEST OF THE SOURCE. The band site's signup handler had no checks for ten weeks of abuse, twenty lines
 * from a contact page that had three; this site's own signup handler had a rate limit and nothing else for as long as
 * its contact form had a guard. A test of the guard cannot prove a page CALLS it, and "the guard exists and one
 * handler forgot it" is exactly the state both sites were in. So this reads the code, with PHP comments removed (the
 * files name the old checks on purpose, and a detector that cannot tell code from prose is weakened the first time
 * someone documents what it guards):
 *
 *   - every file that ships and takes a POST is in DOORS or in NOT_A_DOOR, with what it is;
 *   - a door calls FormGuard::admit(), then (for the signup) the brake, then the hourly limit, BEFORE its write and its
 *     mail, takes only the pass its own caller signs, and loads the guard through includes/od9_form_guard.php;
 *   - every INSERT into email_signups is in a door, and the platform mirror (od9_sp_audience_sync) is called only from
 *     the files listed;
 *   - every page that posts to a door is in FORM_PAGES, carries the guard's fields INSIDE its form, prints the guard's
 *     script, and (posting with fetch and staying put) asks the widget for a fresh token after a refusal; a page that
 *     renders its own proof stamps the session before any output, and the popup asks form-stamp.php;
 *   - nothing loads a copy under includes/form-checks/ but includes/od9_form_guard.php, and the retired guard stays gone;
 *   - nothing this site ships sends a Content-Security-Policy, which is what the health answer's stateBehind(null)
 *     rests on (with the human check on, a policy that blocks Cloudflare refuses every visitor).
 * Each scan has a vacuity guard: it must see the doors, the forms and the files it is known to cover.
 *
 * WHEN A NEW PUBLIC FORM IS ADDED: its handler lands on the POST scan and its page on the door-URL scan, and this
 * test names both. Put the handler in DOORS, guarded, and the page in FORM_PAGES.
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

/** Files that take a POST and keep a stranger's words or mail them: the guard's doors. */
const DOORS = [
    'subscribe.php' => [
        'why'    => 'the email signup (the popup, join.php, wake-up.php, the bot): stores email_signups and mails a confirmation',
        'pass'   => 'OD9_FORM_CALLER_BOT',
        'brake'  => true,
        'writes' => ['INSERT INTO email_signups', 'od9_send_mail('],
    ],
    'contact-handler.php' => [
        'why'    => 'the contact form: mails the message to contact@offda9.com',
        'pass'   => 'OD9_FORM_CALLER_CONTACT',
        'brake'  => false,
        'writes' => ['od9_send_mail('],
    ],
];

/** Files that take a POST and are not a door for a stranger's words, with what each is. */
const NOT_A_DOOR = [
    'api/discord/link_status.php' => 'read-only, and only for a request HMAC-signed by the bot',
    'api/v1/bootstrap.php'        => 'the v1 API\'s shared bootstrap: it answers a signed-in member\'s session only (its auth check), and stores nothing a stranger typed',
    'api/drip/webhook.php'        => 'the drip webhook: refuses any request without the HMAC signature its senders share',
    'api/v1/atlas-ping.php'       => 'the Atlas measurement beacon: one anonymous line per event in a file outside the docroot, no address kept, nothing mailed',
    'api/v1/error-beacon.php'     => 'the page-fault beacon: one line per fault in a file outside the docroot, nothing mailed',
    'dashboard/board-action.php'  => 'the dashboard: a signed-in member only (dashboard/includes/auth.php)',
    'dashboard/coach-ask.php'     => 'the dashboard: a signed-in member only',
    'dashboard/guide-ask.php'     => 'the dashboard: a signed-in member only',
    'dashboard/settings.php'      => 'the dashboard: a signed-in member only',
    'dashboard/tour-state.php'    => 'the dashboard: a signed-in member only',
    'dashboard/welcome.php'       => 'the dashboard: a signed-in member only',
    'unsubscribe.php'             => 'changes a stored row and never adds one or mails; it acts on a typed address alone when the row has no token (its own header): found 2026-11-27, outside the form checks, a line in ~/.claude/TIER2.md',
];

/** Pages whose form posts to a door, the form's opening tag, and how its proof is made. */
const FORM_PAGES = [
    'contact.php'              => ['form' => '<form class="contact-form"', 'door' => 'contact-handler.php', 'proof' => 'session', 'fetch' => false],
    'join.php'                 => ['form' => '<form id="joinPageForm"', 'door' => 'subscribe.php', 'proof' => 'session', 'fetch' => true],
    'wake-up.php'              => ['form' => '<form id="wakeUpForm"', 'door' => 'subscribe.php', 'proof' => 'session', 'fetch' => true],
    'includes/email-popup.php' => ['form' => '<form class="email-signup-form" id="popupSignupForm"', 'door' => 'subscribe.php', 'proof' => 'stamp', 'fetch' => true],
];

/** Files that name a door's address and are not a form page, with why. */
const NAMES_A_DOOR = [
    'subscribe.php'       => 'the door itself',
    'contact-handler.php' => 'the door itself',
];

/** Callers of the platform mirror, which writes the platform's email_subscribers row for a state change. */
const MIRROR_CALLERS = [
    'subscribe.php'   => 'a signup, after the guard, the brake and the limit',
    'verify.php'      => 'a confirmation, from the link mailed to the address',
    'unsubscribe.php' => 'an unsubscribe (see NOT_A_DOOR)',
];

/** PHP with its comments removed; any other text as it is. */
function code_of(string $path): string
{
    $src = (string) file_get_contents($path);
    if (substr($path, -4) !== '.php') {
        return $src;
    }
    $code = '';
    foreach (token_get_all($src) as $t) {
        if (is_array($t)) {
            if ($t[0] !== T_COMMENT && $t[0] !== T_DOC_COMMENT) {
                $code .= $t[1];
            }
        } else {
            $code .= $t;
        }
    }
    return $code;
}

/** Every file the deploy would ship: tracked, or untracked and not ignored, minus what the deploy never sends. */
function shipping_files(): array
{
    $out = (string) shell_exec('git ls-files -co --exclude-standard');
    $files = [];
    foreach (preg_split('/\R/', $out) ?: [] as $p) {
        if ($p === '' || preg_match('#^(vendor|tests|tools|docs|database|migrations|logs|cache|\.claude|node_modules)/#', $p)) {
            continue;
        }
        if (is_file($p)) {
            $files[] = $p;
        }
    }
    return $files;
}

$files = shipping_files();
$php = array_values(array_filter($files, static fn(string $p): bool => substr($p, -4) === '.php'));
ok(count($php) > 150, 'the scan reads the site\'s PHP (' . count($php) . ' files that ship)');
$codes = [];
foreach ($php as $p) {
    $codes[$p] = code_of($p);
}

// ---- every file that takes a POST is listed ----
$posting = [];
foreach ($codes as $p => $code) {
    if (preg_match('/\$_POST\b|php:\/\/input|REQUEST_METHOD[^;\n]*[\'"]POST[\'"]/', $code) === 1) {
        $posting[] = $p;
    }
}
ok(in_array('subscribe.php', $posting, true) && in_array('contact-handler.php', $posting, true) && in_array('api/discord/link_status.php', $posting, true),
   'the POST scan sees the two doors and a signed endpoint it is known to cover');
foreach ($posting as $p) {
    ok(isset(DOORS[$p]) || isset(NOT_A_DOOR[$p]), "{$p} takes a POST and is on a list" . (isset(NOT_A_DOOR[$p]) ? ' (not a door: ' . NOT_A_DOOR[$p] . ')' : ' (a door)'));
}
foreach (array_keys(NOT_A_DOOR + DOORS) as $p) {
    ok(in_array($p, $posting, true), "{$p} is still a file that takes a POST (a list entry whose reason is gone fails)");
}

// ---- each door runs the checks in their order ----
foreach (DOORS as $door => $d) {
    $code = $codes[$door] ?? '';
    $admit = strpos($code, 'FormGuard::admit(');
    $limit = strpos($code, 'od9_form_limit(');
    ok($admit !== false && $limit !== false && $admit < $limit, "{$door}: the guard comes before the hourly limit");
    foreach ($d['writes'] as $write) {
        $at = strpos($code, $write);
        ok($at !== false && $limit !== false && $limit < $at, "{$door}: the guard and the limit come before `{$write}`");
    }
    if ($d['brake']) {
        $brake = strpos($code, 'SignupBrake::admit(');
        ok($brake !== false && $admit < $brake && $brake < $limit, "{$door}: the brake comes after the guard and before the limit");
    }
    ok(preg_match('/FormGuard::admit\([^;]*od9_form_caller\(\s*\[\s*' . $d['pass'] . '\s*\]/s', $code) === 1,
       "{$door}: the only pass it takes is its own caller's ({$d['pass']}), checked inside the guard's call");
    ok(preg_match('#require_once\s+\$?[^;]*od9_form_guard\.php#', $code) === 1, "{$door}: loads the guard through includes/od9_form_guard.php");
}
foreach ($codes as $p => $code) {
    if (strpos($code, 'od9_form_caller(') !== false && !isset(DOORS[$p]) && $p !== 'includes/od9_form_guard.php') {
        ok(false, "{$p} checks a signed pass and is not a door: a pass belongs to a door's own caller");
    }
}

// ---- every write of a stranger's address is in a door ----
$writers = [];
foreach ($codes as $p => $code) {
    if (preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+`?email_signups\b/i', $code) === 1) {
        $writers[] = $p;
    }
}
ok($writers !== [], 'the write scan sees an INSERT into email_signups (' . implode(', ', $writers) . ')');
foreach ($writers as $p) {
    ok(isset(DOORS[$p]), "{$p} writes email_signups and is a guarded door");
}
$mirror = [];
foreach ($codes as $p => $code) {
    if (preg_match('/\bod9_sp_audience_sync\s*\(/', $code) === 1 && $p !== 'includes/sp_audience_sync.php') {
        $mirror[] = $p;
    }
}
ok(in_array('subscribe.php', $mirror, true), 'the mirror scan sees subscribe.php');
foreach ($mirror as $p) {
    ok(isset(MIRROR_CALLERS[$p]), "{$p} writes the platform mirror and is on its list" . (isset(MIRROR_CALLERS[$p]) ? ' (' . MIRROR_CALLERS[$p] . ')' : ''));
}

// ---- every page that posts to a door carries the guard ----
$naming = [];
foreach ($files as $p) {
    if (!preg_match('/\.(php|html?|js)$/', $p)) {
        continue;
    }
    $code = $codes[$p] ?? code_of($p);
    foreach (['subscribe.php', 'contact-handler.php'] as $door) {
        if (preg_match('#["\'/]' . preg_quote($door, '#') . '\b#', $code) === 1) {
            $naming[$p] = true;
        }
    }
}
ok(isset($naming['join.php'], $naming['includes/email-popup.php'], $naming['contact.php']), 'the door-address scan sees the pages it is known to cover');
foreach (array_keys($naming) as $p) {
    ok(isset(FORM_PAGES[$p]) || isset(NAMES_A_DOOR[$p]), "{$p} names a door's address and is a listed form page");
}
foreach (FORM_PAGES as $page => $f) {
    $code = $codes[$page] ?? '';
    $open = strpos($code, $f['form']);
    $end = $open === false ? false : strpos($code, '</form>', $open);
    $fields = $open === false ? false : strpos($code, 'od9_form_guard_fields()', $open);
    ok($open !== false && $fields !== false && $end !== false && $fields < $end, "{$page}: the guard's fields are inside its form");
    ok(strpos($code, 'od9_form_guard_script()') !== false, "{$page}: prints the guard's script");
    ok(strpos($code, $f['door']) !== false, "{$page}: posts to {$f['door']}");
    if ($f['proof'] === 'session') {
        $session = strpos($code, 'od9_form_guard_session()');
        $stamp = strpos($code, 'FormGuard::open($_SESSION)');
        $doctype = stripos($code, '<!DOCTYPE');
        ok($session !== false && $stamp !== false && $doctype !== false && $session < $stamp && $stamp < $doctype,
           "{$page}: starts the session and stamps the render before any output");
    } else {
        ok(strpos($code, '/form-stamp.php') !== false && strpos($code, 'MutationObserver') !== false,
           "{$page}: has no session of its own, so it asks form-stamp.php for the stamp when it opens");
    }
    if ($f['fetch']) {
        ok(substr_count($code, 'fgHuman.reset(') >= 1 && strpos($code, "credentials") !== false,
           "{$page}: posts with fetch, sends the session cookie, and asks the widget for a fresh token after a refusal");
    }
}

// ---- the copies are loaded through this site's half only, and the old guard stays gone ----
foreach ($codes as $p => $code) {
    if ($p !== 'includes/od9_form_guard.php' && strpos($p, 'includes/form-checks/') !== 0
        && preg_match('#(require|include)(_once)?[^;]*form-checks/#', $code) === 1) {
        ok(false, "{$p} loads a copy directly: load includes/od9_form_guard.php, which configures the guard as it loads");
    }
}
ok(strpos($codes['includes/od9_form_guard.php'] ?? '', "require_once __DIR__ . '/form-checks/FormGuard.php'") !== false
   && preg_match('/\nod9_form_guard_configure\(\);\s*$/', $codes['includes/od9_form_guard.php'] ?? '') === 1,
   'includes/od9_form_guard.php loads the copies and configures the guard as it loads');
ok(!is_file('includes/FormGuard.php') && !is_file('includes/email-signup.php'), 'the retired guard and the orphan popup stay gone');
$deploy = json_decode((string) file_get_contents('tools/prod-deploy-config.v2.json'), true);
$removed = $deploy['remove_remote'] ?? [];
ok(in_array('public_html/includes/FormGuard.php', $removed, true) && in_array('public_html/includes/email-signup.php', $removed, true),
   'the deploy removes both from the server (rsync never deletes)');

// ---- nothing this site ships sends a policy that could block the widget ----
$htaccess = array_values(array_filter($files, static fn(string $p): bool => basename($p) === '.htaccess'));
ok(count($htaccess) >= 5, 'the policy scan reads the site\'s .htaccess files (' . count($htaccess) . ')');
// What SENDS a policy: a header() call, a meta tag, or a live .htaccess line. A sentence that names the header (the
// engine's health message does) sends nothing.
$sendsPolicy = '/header\s*\(\s*["\']\s*Content-Security-Policy|http-equiv\s*=\s*["\']?\s*Content-Security-Policy/i';
ok(preg_match($sendsPolicy, "header('Content-Security-Policy: default-src \\'self\\'');") === 1
   && preg_match($sendsPolicy, '<meta http-equiv="Content-Security-Policy" content="default-src \'self\'">') === 1
   && preg_match($sendsPolicy, "\$problems[] = \"this server's Content-Security-Policy does not allow it\";") === 0,
   'detector: finds a policy sent by header() or a meta tag, and not a sentence that names one');
$csp = [];
foreach ($htaccess as $p) {
    foreach (preg_split('/\R/', (string) file_get_contents($p)) ?: [] as $line) {
        if (stripos($line, 'Content-Security-Policy') !== false && !preg_match('/^\s*#/', $line)) {
            $csp[] = $p;
        }
    }
}
foreach ($files as $p) {
    if (preg_match('/\.(php|html?)$/', $p) && preg_match($sendsPolicy, $codes[$p] ?? code_of($p)) === 1) {
        $csp[] = $p;
    }
}
ok($csp === [], 'nothing this site ships sends a Content-Security-Policy, so stateBehind(null) is true' . ($csp ? ' (found: ' . implode(', ', array_unique($csp)) . ')' : ''));
$health = $codes['api/health/signup-volume.php'] ?? '';
ok(strpos($health, 'stateBehind(null)') !== false && strpos($health, 'FormGuard::countPasses()') !== false && strpos($health, 'SignupBrake::health(') !== false,
   'the health answer is the engine\'s, with the passes and the policy fact above');

echo "{$pass} passed, {$fail} failed\n";
exit($fail === 0 && $pass > 0 ? 0 : 1);
