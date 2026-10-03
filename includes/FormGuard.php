<?php
/**
 * FormGuard — the checks OD9's contact form passes before its message is sent.
 *
 * WHY THIS EXISTS (2026-10-02)
 * ----------------------------
 * From June 16 to October 2 the OD9 contact form handed 236 messages to the mail
 * system and every one was bot spam: prize and crypto links, the same "what is
 * your price" in 17 languages, SEO pitches. The handler checked that the fields
 * were filled in and nothing else, so any program that posted at it was taken.
 *
 * This is a port of the band site's guard (freshthaband_v2
 * private/core/FormGuard.php, as of 431495a), which was written after the same
 * kind of flood there. The checks and the refusal log are the same, so one
 * reading of either file explains both. ONE DIFFERENCE: the band's forms store a
 * row per submission and count a sender in the database; OD9's form stores
 * nothing, so the hourly limit is counted in a small file instead
 * (logs/form-guard-rate.json, keyed by a hash of the address, never the address).
 *
 * WHAT IT CHECKS, in this order
 * -----------------------------
 *   trap       a field no person can see or reach is filled in
 *   no_page    the form arrived without the page having been rendered in this
 *              session (a program posting straight at the handler)
 *   too_fast   it arrived sooner than MIN_SECONDS after the page was rendered;
 *              the render time lives in the SESSION, never in the request
 *   no_script  the value the page's script copies into the form is missing or wrong
 *   rate       more than SENDS_PER_HOUR messages from one address in an hour
 *
 * It does not stop a program that drives a real browser slowly. That case is
 * made visible by the refusal log and by the daily form canary on the bot
 * (crons/contact_form_canary.py), which posts through this guard like a browser.
 *
 * A REFUSAL IS TOLD AS A REFUSAL: the visitor reads that the message was not
 * taken and how to try again. Each refusal is one line in logs/form-guard.jsonl.
 */

declare(strict_types=1);

final class FormGuardRefused extends RuntimeException
{
    /** @var string one of FormGuard::REASONS */
    public string $reason;

    public function __construct(string $message, string $reason)
    {
        parent::__construct($message);
        $this->reason = $reason;
    }
}

final class FormGuard
{
    /** A person needs longer than this to read a form and type a message. */
    public const MIN_SECONDS = 3;

    /** Messages one address may send in an hour. */
    public const SENDS_PER_HOUR = 5;

    public const SESSION_KEY = 'form_guard';

    /** The trap. Named after nothing an autofill profile holds. */
    public const TRAP_FIELD = 'fg_ref';

    public const PROOF_FIELD = 'fg_proof';

    public const REASONS = ['trap', 'no_page', 'too_fast', 'no_script', 'rate'];

    public const REFUSED = "We couldn't take that just now. Please reload the page and try again.";
    public const LIMITED = 'Too many messages from this connection. Please try again in an hour.';

    /** Start the session with the site's own cookie settings, once. */
    public static function bootSession(): void
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
        session_start();
    }

    /**
     * Remember that a page carrying a guarded form is being rendered now. Call it
     * BEFORE the page prints its form.
     */
    public static function open(array &$session, ?int $now = null): void
    {
        $state = $session[self::SESSION_KEY] ?? null;
        if (!is_array($state)) {
            $state = [];
        }
        if (!isset($state['proof']) || !is_string($state['proof']) || $state['proof'] === '') {
            $state['proof'] = bin2hex(random_bytes(16));
        }
        $state['opened_at'] = $now ?? time();
        $session[self::SESSION_KEY] = $state;
    }

    /** The trap, the proof field and a line for a browser that runs no scripts. */
    public static function fields(array $session): string
    {
        $proof = '';
        $state = $session[self::SESSION_KEY] ?? null;
        if (is_array($state) && isset($state['proof']) && is_string($state['proof'])) {
            $proof = $state['proof'];
        }
        $proof = htmlspecialchars($proof, ENT_QUOTES, 'UTF-8');

        return '<div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">'
            . '<label>Leave this field empty <input type="text" name="' . self::TRAP_FIELD
            . '" value="" tabindex="-1" autocomplete="off"></label></div>'
            . '<input type="hidden" name="' . self::PROOF_FIELD . '" value="" data-fg="' . $proof . '">'
            . '<noscript><p>This form needs JavaScript switched on in your browser.</p></noscript>';
    }

    /** The script that copies the proof into the form. Print it once per page. */
    public static function script(): string
    {
        return '<script>(function(){function arm(){var f=document.querySelectorAll(\'input[name="'
            . self::PROOF_FIELD . '"]\');for(var i=0;i<f.length;i++){f[i].value=f[i].getAttribute("data-fg")||"";}}'
            . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",arm);}else{arm();}})();</script>';
    }

    /** Judge one submission. Null when it may go on, else the reason. Pure. */
    public static function check(array $session, array $post, ?int $now = null): ?string
    {
        $trap = $post[self::TRAP_FIELD] ?? '';
        if (!is_string($trap) || trim($trap) !== '') {
            return 'trap';
        }

        $state = $session[self::SESSION_KEY] ?? null;
        if (!is_array($state)
            || !isset($state['opened_at'], $state['proof'])
            || !is_int($state['opened_at'])
            || !is_string($state['proof'])
            || $state['proof'] === '') {
            return 'no_page';
        }

        if ((($now ?? time()) - $state['opened_at']) < self::MIN_SECONDS) {
            return 'too_fast';
        }

        $proof = $post[self::PROOF_FIELD] ?? '';
        if (!is_string($proof) || !hash_equals($state['proof'], $proof)) {
            return 'no_script';
        }

        return null;
    }

    /**
     * check(), for a page: a refusal is logged and thrown, so the handler stops
     * before it reads another field.
     *
     * @throws FormGuardRefused
     */
    public static function admit(string $form, array $session, array $post, array $server, ?int $now = null): void
    {
        $reason = self::check($session, $post, $now);
        if ($reason !== null) {
            self::record($form, $reason, $server);
            throw new FormGuardRefused(self::REFUSED, $reason);
        }
    }

    /** Tests point these at temporary files. Null means the real ones. */
    public static ?string $logFileOverride = null;
    public static ?string $rateFileOverride = null;

    public static function logFile(): string
    {
        return self::$logFileOverride ?? dirname(__DIR__) . '/logs/form-guard.jsonl';
    }

    public static function rateFile(): string
    {
        return self::$rateFileOverride ?? dirname(__DIR__) . '/logs/form-guard-rate.json';
    }

    /**
     * Count one accepted message for $address and say whether it is over the
     * hourly limit. The count is kept per hashed address, pruned to the window on
     * every call, under an exclusive lock. A file that cannot be opened lets the
     * message through and says so in the error log: a broken counter must not
     * silence the form.
     */
    public static function overLimit(string $address, int $max = self::SENDS_PER_HOUR, int $windowSeconds = 3600, ?int $now = null): bool
    {
        $now = $now ?? time();
        $file = self::rateFile();
        $dir = dirname($file);
        // silent-failure:ignore — the result is checked on the same line and logged
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            error_log('FormGuard could not create its rate directory: ' . $dir);
            return false;
        }
        // silent-failure:ignore — checked below, and logged with its cause
        $fh = @fopen($file, 'c+');
        if ($fh === false) {
            error_log('FormGuard could not open its rate file: ' . $file . ' (' . (error_get_last()['message'] ?? 'no reason') . ')');
            return false;
        }
        try {
            flock($fh, LOCK_EX);
            $raw = stream_get_contents($fh);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($data)) {
                $data = [];
            }
            $key = hash('sha256', 'od9-form-guard|' . $address);
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
     * overLimit(), for a page: over the limit is logged and thrown.
     *
     * @throws FormGuardRefused
     */
    public static function limit(string $form, array $server, ?int $now = null): void
    {
        $address = isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '';
        if (self::overLimit($address, self::SENDS_PER_HOUR, 3600, $now)) {
            self::record($form, 'rate', $server);
            throw new FormGuardRefused(self::LIMITED, 'rate');
        }
    }

    /** Write one refusal. Never throws; a failed write goes to the PHP error log with its cause. */
    public static function record(string $form, string $reason, array $server, ?string $logFile = null): bool
    {
        $logFile = $logFile ?? self::logFile();
        $line = json_encode([
            'ts'     => gmdate('Y-m-d\TH:i:s\Z'),
            'form'   => $form,
            'reason' => $reason,
            'ip'     => isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : null,
            'agent'  => isset($server['HTTP_USER_AGENT']) && is_string($server['HTTP_USER_AGENT'])
                ? substr($server['HTTP_USER_AGENT'], 0, 160)
                : null,
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        $directory = dirname($logFile);
        // silent-failure:ignore — the result is checked on the same line and logged
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            error_log('FormGuard could not create its log directory: ' . $directory);
            return false;
        }
        error_clear_last();
        // silent-failure:ignore — checked below, and logged with its cause
        if ($line === false || @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            $cause = $line === false
                ? 'the refusal could not be encoded as JSON'
                : (error_get_last()['message'] ?? 'PHP gave no reason');
            error_log('FormGuard could not write its refusal log: ' . $logFile . ' (' . $cause . ')');
            return false;
        }
        return true;
    }
}
