<?php
/**
 * od9_form_guard.php — what offda9.com hands the shared form checks, in one place (2026-11-28, piece 4 of the form
 * checks: https://claude.ai/artifact/4UWXZQzkUhCvdCZ9Y9b4nR).
 *
 * WHY
 * ---
 * This site's forms had two answers to one problem: the contact form ran a port of the band site's guard
 * (includes/FormGuard.php, 2026-10-02, after 236 spam messages), and the email signup (subscribe.php, from the
 * popup every page carries, join.php, wake-up.php and the Discord bot) had a rate limit and nothing else, while
 * every signup mails the address typed in. The band site's signup forms were used by a program on 64 of 66 days in
 * 2026 and again through Tor on 26 November. The checks now live ONCE, in the engine (shared-platform core/), and
 * this site carries GENERATED copies of them under includes/form-checks/ (FormGuard, HumanCheck, SignupBrake,
 * SignupVolume), proven equal to the engine's at every deploy by tests/check_form_checks.py. Nobody edits a copy.
 *
 * THIS FILE IS THE SITE'S HALF, the part the engine's files leave to the site that runs them:
 *
 *   - where the guard's logs go: /home/<account>/form-guard on the server, outside the docroot (the engine's rule:
 *     the refusal log holds visitors' addresses); the checkout's logs/ locally, which git ignores. FormGuard's own
 *     FORM_GUARD_LOGDIR variable moves both logs and the rate file below, which is how the tests keep their
 *     refusals out of the real evidence.
 *   - who the visitor is: REMOTE_ADDR, as PHP has it. The server loads mod_remoteip, which rewrites REMOTE_ADDR to
 *     the visitor from Cloudflare's header and believes that header only from Cloudflare's own addresses (measured on
 *     the box 2026-11-23, od9 memory origin-refusals-and-cloudflare-bypass), so FormGuard's default is right here and
 *     no reader is handed in.
 *   - the session: this account's own session folder (includes/session_store.php), started before any output.
 *   - how the human check asks Cloudflare: od9_human_check_transport(), curl, HTTPS only, no redirects, the
 *     timeouts HumanCheck hands in. Cloudflare's server-side validation page (developers.cloudflare.com/turnstile/
 *     get-started/server-side-validation/, read 2026-11-28): POST to /turnstile/v0/siteverify, form-encoded or JSON,
 *     always answered in JSON; its own PHP example uses curl with a connect and a total timeout.
 *   - the widget's two keys: config/form-checks.config.php when it exists (putenv of TURNSTILE_SITEKEY and
 *     TURNSTILE_SECRET_KEY; a *.config.php, so git ignores it and the deploy never ships it; config/ is refused at
 *     the origin). With no file the check is OFF and every form works as before. Piece 5 writes the file.
 *   - the passes for the two callers that are not a person's browser: the bot's daily contact-form canary
 *     (crons/contact_form_canary.py) and its /link_email and /subscribe_email (cogs/email_link.py). Each signs a
 *     header with the secret the bot and this site already share (OD9_WEBHOOK_SECRET; the bot calls it
 *     OD9_DRIP_WEBHOOK_SECRET), good for 60 seconds and naming the caller, and each still loads a page, waits and
 *     sends the page's proof like a browser: a pass skips Cloudflare's question and nothing else (FormGuard.php,
 *     PASSES). FormGuard writes every use to its pass log, and the health answer counts them.
 *   - the hourly limit per visitor, moved here from the retired includes/FormGuard.php: the engine's guard keeps no
 *     limit, each site keeps the limits its handlers had, and tests/test_public_forms_use_the_guard.php requires
 *     one between the guard and the write.
 *
 * Every page or handler that uses the guard loads it through this file (the public-forms test refuses a direct
 * require of a copy), and this file configures FormGuard as it loads, so no entry can forget to: a check given no
 * transport refuses every visitor the day its keys are set.
 *
 * Provers: tests/test_form_guard.php (this file), tests/test_public_forms_use_the_guard.php (every door, in order),
 * tests/test_forms_http.php (the real pages and handlers on local Apache), tests/check_form_checks.py (the copies).
 *
 * Docs: https://developers.cloudflare.com/turnstile/get-started/server-side-validation/ (read 2026-11-28)
 * Docs: https://developers.cloudflare.com/turnstile/troubleshooting/testing/ (the published test keys the tests use)
 */

declare(strict_types=1);

require_once __DIR__ . '/form-checks/FormGuard.php';     // loads HumanCheck.php beside it
require_once __DIR__ . '/form-checks/SignupBrake.php';   // loads FormGuard.php and SignupVolume.php
require_once __DIR__ . '/session_store.php';

/** Callers that may carry a signed pass, and the forms that take each. */
const OD9_FORM_CALLER_CONTACT = 'contact-canary';
const OD9_FORM_CALLER_BOT = 'discord-bot';

/** How far a caller's signed time may be from this server's clock, either way. */
const OD9_FORM_CALLER_WINDOW_SECONDS = 60;

/** Messages or signups one visitor may send through one form in an hour (the old guard's number). */
const OD9_FORM_SENDS_PER_HOUR = 5;

const OD9_FORM_LIMITED = 'Too many tries from this connection. Please try again in an hour.';

/** /home/<account>/form-guard on the server; the checkout's logs/ locally. $here stands in for this folder in tests. */
function od9_form_guard_log_dir(?string $here = null): string
{
    $here = $here ?? __DIR__;
    $home = od9_account_home($here);
    return $home !== null ? $home . '/form-guard' : dirname($here) . '/logs';
}

/** Where the hourly counts live: beside the guard's logs, moved by FORM_GUARD_LOGDIR as they are. */
function od9_form_rate_file(): string
{
    $env = getenv('FORM_GUARD_LOGDIR');
    $dir = is_string($env) && $env !== '' ? rtrim($env, '/\\') : od9_form_guard_log_dir();
    return $dir . '/form-guard-rate.json';
}

/** The widget's keys into the process, when the site has them (piece 5 writes the file). */
function od9_form_guard_load_keys(?string $file = null): bool
{
    $file = $file ?? dirname(__DIR__) . '/config/form-checks.config.php';
    if (!is_file($file)) {
        return false;
    }
    require_once $file;
    return true;
}

/**
 * How the human check asks Cloudflare on this site. It throws when no answer came back, so HumanCheck refuses the
 * form and writes the cause to the error log; a half answer never lets a form through.
 *
 * @return callable(string, array<string, string>, int, int): string
 */
function od9_human_check_transport(): callable
{
    return static function (string $url, array $fields, int $connectSeconds, int $totalSeconds): string {
        if (strncmp($url, 'https://', 8) !== 0) {
            throw new RuntimeException('the human check asks Cloudflare over HTTPS only');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('this PHP has no curl, so the human check cannot ask Cloudflare');
        }
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl could not start a request');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $connectSeconds,
            CURLOPT_TIMEOUT        => $totalSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if (!is_string($body) || $error !== '' || $status === 0) {
            throw new RuntimeException($error !== '' ? $error : 'no answer came back');
        }
        return $body;
    };
}

/** Said once, as this file loads: every entry that includes it is configured. */
function od9_form_guard_configure(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    od9_form_guard_load_keys();
    FormGuard::configure(od9_form_guard_log_dir(), null, od9_human_check_transport());
}

/** Start the session with the site's own cookie settings, before any output. A no-op when it runs already. */
function od9_form_guard_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 604800,
        'path'     => '/',
        'secure'   => strpos(__DIR__, 'xampp') === false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    od9_session_store();                          // this account's own folder, never cPanel's shared one
    session_start();
}

/**
 * The guard's markup for one form: the trap, the proof and, while the check is on, the widget's place. On a page
 * that started no session (the popup on every page) the proof is empty, and the popup's script asks form-stamp.php
 * for one when it opens.
 */
function od9_form_guard_fields(): string
{
    $session = session_status() === PHP_SESSION_ACTIVE && isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
    return FormGuard::fields($session);
}

/** FormGuard's script (the proof copied into each form; Cloudflare's widget while on), once per page. */
function od9_form_guard_script(): string
{
    static $printed = false;
    if ($printed) {
        return '';
    }
    $printed = true;
    return FormGuard::script();
}

/** The signature a caller sends: hex HMAC-SHA256 over its name, its time and the SHA-256 of the body. Pure. */
function od9_form_caller_signature(string $secret, string $name, int $time, string $body): string
{
    return hash_hmac('sha256', $name . "\n" . $time . "\n" . hash('sha256', $body), $secret);
}

/** The secret the bot and this site share, or '' when this server has none. */
function od9_form_caller_secret(): string
{
    if (!defined('OD9_WEBHOOK_SECRET')) {
        $file = dirname(__DIR__) . '/api/drip/webhook_secret.config.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
    return defined('OD9_WEBHOOK_SECRET') && is_string(OD9_WEBHOOK_SECRET) ? OD9_WEBHOOK_SECRET : '';
}

/**
 * The pass a caller that is not a person's browser carries, or null.
 *
 * Headers: X-OD9-Caller (its name), X-OD9-Caller-Time (unix seconds) and X-OD9-Caller-Signature
 * (od9_form_caller_signature over the exact body sent). Good for OD9_FORM_CALLER_WINDOW_SECONDS either side of this
 * server's clock, and only on a form that takes that caller ($accepted). A request that names no caller is a
 * browser's and gets null quietly; one that names a caller and fails gets null too, so it is judged as a browser
 * (the human check refuses it once it is on), and the reason goes to the error log, where a broken secret on either
 * side shows the same day.
 *
 * @param string[] $accepted the callers this form takes
 */
function od9_form_caller(array $accepted, string $rawBody, array $server, ?string $secret = null, ?int $now = null): ?string
{
    $name = $server['HTTP_X_OD9_CALLER'] ?? null;
    if (!is_string($name) || $name === '') {
        return null;
    }
    $time = $server['HTTP_X_OD9_CALLER_TIME'] ?? null;
    $signature = $server['HTTP_X_OD9_CALLER_SIGNATURE'] ?? null;
    $secret = $secret ?? od9_form_caller_secret();

    $why = null;
    if (!in_array($name, $accepted, true)) {
        $why = 'this form does not take that caller';
    } elseif ($secret === '') {
        $why = 'this server has no shared secret (api/drip/webhook_secret.config.php)';
    } elseif (!is_string($time) || preg_match('/^\d{9,11}$/', $time) !== 1) {
        $why = 'it sent no time';
    } elseif (abs(($now ?? time()) - (int) $time) > OD9_FORM_CALLER_WINDOW_SECONDS) {
        $why = 'its signed time is more than ' . OD9_FORM_CALLER_WINDOW_SECONDS . ' seconds from this server\'s clock';
    } elseif (!is_string($signature) || preg_match('/^[0-9a-f]{64}$/', $signature) !== 1) {
        $why = 'it sent no signature';
    } elseif (!hash_equals(od9_form_caller_signature($secret, $name, (int) $time, $rawBody), $signature)) {
        $why = 'its signature does not match the body';
    }
    if ($why !== null) {
        error_log('[od9 form caller] a request named itself "' . substr((string) preg_replace('/[^a-z0-9-]/', '?', $name), 0, 40)
            . '" and was not given its pass: ' . $why);
        return null;
    }
    return $name;
}

/**
 * Count one submission of $form from $address and say whether it is over the hourly limit. Counts are kept per
 * form and per hashed address (never the address), pruned to the window on every call, under an exclusive lock. A
 * file that cannot be opened lets the submission through and says so in the error log: a broken counter must not
 * silence the form. (Moved from the retired includes/FormGuard.php, 2026-10-02, with the form in the key.)
 */
function od9_form_over_limit(
    string $form,
    string $address,
    int $max = OD9_FORM_SENDS_PER_HOUR,
    int $windowSeconds = 3600,
    ?int $now = null,
    ?string $file = null
): bool {
    $now = $now ?? time();
    $file = $file ?? od9_form_rate_file();
    $dir = dirname($file);
    // silent-failure:ignore — the result is checked on the same line and logged
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log('od9 form limit could not create its folder: ' . $dir);
        return false;
    }
    // silent-failure:ignore — checked below, and logged with its cause
    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        error_log('od9 form limit could not open its file: ' . $file . ' (' . (error_get_last()['message'] ?? 'no reason') . ')');
        return false;
    }
    try {
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($data)) {
            $data = [];
        }
        $key = hash('sha256', 'od9-form-guard|' . $form . '|' . $address);
        foreach ($data as $k => $stamps) {
            $kept = array_values(array_filter(is_array($stamps) ? $stamps : [], fn($t) => is_int($t) && $t > $now - $windowSeconds));
            if ($kept === []) {
                unset($data[$k]);
            } else {
                $data[$k] = $kept;
            }
        }
        $mine = $data[$key] ?? [];
        $over = count($mine) >= $max;
        if (!$over) {
            $mine[] = $now;
            $data[$key] = $mine;
        }
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string) json_encode($data));
        fflush($fh);
        return $over;
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/**
 * od9_form_over_limit(), for a handler: over the limit is written to the guard's refusal log (reason `rate`) and
 * thrown, so the handler stops before it stores or sends anything.
 *
 * @throws FormGuardRefused
 */
function od9_form_limit(string $form, array $server, ?int $now = null): void
{
    $address = isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '';
    if (od9_form_over_limit($form, $address, OD9_FORM_SENDS_PER_HOUR, 3600, $now)) {
        FormGuard::record($form, 'rate', $server);
        throw new FormGuardRefused(OD9_FORM_LIMITED, 'rate');
    }
}

od9_form_guard_configure();
