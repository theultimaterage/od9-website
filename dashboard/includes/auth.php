<?php
/**
 * OD9 Dashboard auth + persistent "remember me" login (task #7).
 *
 * Centralizes the dashboard's session bootstrap and adds a secure split-token
 * remember-me cookie so a returning member stays logged in for up to 30 days
 * (sliding) without re-running Discord OAuth, even after PHP has garbage-
 * collected the server-side session. Design + security analysis:
 * docs/persistent-login-spec.md.
 *
 * Cookie = "<selector>:<validator>". Only sha256(validator) is stored, so a DB
 * read leak yields no usable cookie. selector is the indexed lookup key (no
 * timing leak); validator is compared with hash_equals. Rotated on each use; a
 * selector-hit with a wrong validator is treated as theft and purges all of the
 * user's tokens. Authorization (still an OD9 member?) is re-checked on every
 * revival — never trusted from the cookie.
 *
 * Entry points:
 *   od9_dashboard_boot()              top of every gated page + the auth flow
 *   od9_issue_remember_token($id)     after a successful OAuth (callback.php)
 *   od9_clear_remember_token()        on logout (logout.php)
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';                  // getDatabaseConnection()
require_once __DIR__ . '/../../includes/env.php';  // od9_cookie_secure(), od9_is_local()

const OD9_REMEMBER_COOKIE = 'od9_remember';
const OD9_REMEMBER_TTL    = 2592000;     // 30 days, in seconds (sliding)
const OD9_REMEMBER_PATH   = '/dashboard/';

/**
 * Start the session with consistent secure cookie params, then — if there is no
 * live session — try to rebuild one from the remember cookie. Call once at the
 * very top of every dashboard entry point (before any output).
 */
function od9_dashboard_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime'  => OD9_REMEMBER_TTL,
        'path'      => '/',
        'secure'    => od9_cookie_secure(),
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    // Best-effort: keep server-side session data alive as long as the cookie.
    // (Shared hosting may ignore this — the remember token is the real backstop.)
    @ini_set('session.gc_maxlifetime', (string) OD9_REMEMBER_TTL);
    session_start();

    if (empty($_SESSION['discord_id'])) {
        od9_try_remember_login();
    }
}

/**
 * CSRF token for the dashboard write-forms (security audit #2). Lazily mints one
 * per session and reuses it across the board/bunker forms; board-action.php
 * verifies it on the credit-granting actions. Assumes the session is started
 * (od9_dashboard_boot()).
 */
function od9_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['csrf_token'];
}

/** Issue a fresh remember token + cookie for a just-authenticated member. */
function od9_issue_remember_token(string $discordId): void
{
    try {
        $pdo = getDatabaseConnection();
        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $pdo->prepare(
            'INSERT INTO od9_remember_tokens
                (selector, validator_hash, discord_id, expires_at, user_agent)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $selector,
            hash('sha256', $validator),
            $discordId,
            date('Y-m-d H:i:s', time() + OD9_REMEMBER_TTL),
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
        od9_set_remember_cookie($selector, $validator);
    } catch (Throwable $e) {
        // Non-fatal: the OAuth session still logs them in; they just won't be
        // remembered past this session.
        error_log('[od9 remember] issue failed: ' . $e->getMessage());
    }
}

/** Rebuild the session from a valid remember cookie (no-op if absent/invalid). */
function od9_try_remember_login(): void
{
    $raw = (string) ($_COOKIE[OD9_REMEMBER_COOKIE] ?? '');
    if (strpos($raw, ':') === false) {
        return;
    }
    [$selector, $validator] = explode(':', $raw, 2);
    if (strlen($selector) !== 24 || !ctype_xdigit($selector) || !ctype_xdigit($validator)) {
        od9_clear_remember_cookie();
        return;
    }

    try {
        $pdo = getDatabaseConnection();
        // Opportunistic GC of expired rows (cheap; only runs when session-less).
        $pdo->exec('DELETE FROM od9_remember_tokens WHERE expires_at < NOW()');

        $stmt = $pdo->prepare(
            'SELECT validator_hash, discord_id FROM od9_remember_tokens
             WHERE selector = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
        if (!$row) {
            od9_clear_remember_cookie();
            return;
        }

        // Constant-time validator check.
        if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
            // Valid selector, wrong validator => theft/forgery. Purge every token
            // for this user so the attacker (and the real user) are forced to
            // re-authenticate.
            $pdo->prepare('DELETE FROM od9_remember_tokens WHERE discord_id = ?')
                ->execute([$row['discord_id']]);
            od9_clear_remember_cookie();
            error_log('[od9 remember] validator mismatch on selector ' . $selector . ' — tokens purged');
            return;
        }

        $discordId = (string) $row['discord_id'];

        // Re-verify authorization (they may have left the OD9 server).
        if (!od9_remember_is_member($pdo, $discordId)) {
            $pdo->prepare('DELETE FROM od9_remember_tokens WHERE discord_id = ?')
                ->execute([$discordId]);
            od9_clear_remember_cookie();
            return;
        }

        // Establish the session. The pages re-query everything by discord_id, so
        // this is the only field they need.
        session_regenerate_id(true);
        $_SESSION['discord_id']  = $discordId;
        $_SESSION['auth_time']   = time();
        $_SESSION['via_remember'] = true;

        // Rotate the token (new secret, slide the 30-day window).
        od9_rotate_remember_token($pdo, $selector);
    } catch (Throwable $e) {
        // Transient DB error: leave them logged out but DON'T clear the cookie,
        // so a healthy retry can still succeed.
        error_log('[od9 remember] consume failed: ' . $e->getMessage());
    }
}

/** Delete the current device's token and clear the cookie (logout). */
function od9_clear_remember_token(): void
{
    $raw = (string) ($_COOKIE[OD9_REMEMBER_COOKIE] ?? '');
    if (strpos($raw, ':') !== false) {
        [$selector] = explode(':', $raw, 2);
        if (strlen($selector) === 24 && ctype_xdigit($selector)) {
            try {
                getDatabaseConnection()
                    ->prepare('DELETE FROM od9_remember_tokens WHERE selector = ?')
                    ->execute([$selector]);
            } catch (Throwable $e) {
                error_log('[od9 remember] clear failed: ' . $e->getMessage());
            }
        }
    }
    od9_clear_remember_cookie();
}

/* ---- internals ---- */

function od9_rotate_remember_token(PDO $pdo, string $oldSelector): void
{
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $pdo->prepare(
        'UPDATE od9_remember_tokens
            SET selector = ?, validator_hash = ?, expires_at = ?, last_used_at = NOW()
          WHERE selector = ?'
    )->execute([
        $selector,
        hash('sha256', $validator),
        date('Y-m-d H:i:s', time() + OD9_REMEMBER_TTL),
        $oldSelector,
    ]);
    od9_set_remember_cookie($selector, $validator);
}

function od9_remember_is_member(PDO $pdo, string $discordId): bool
{
    // Membership via the read seam (od9_read). If the read path is unreachable we
    // fail safe: no auto-login via remember token, the user logs in fresh. The
    // $pdo arg (MySQL, remember_tokens) is retained for signature compatibility.
    require_once __DIR__ . '/../../includes/od9_read.php';
    try {
        return od9_read('member_exists', ['user_id' => $discordId]) !== null;
    } catch (Throwable $e) {
        error_log('[auth] remember member check failed: ' . $e->getMessage());
        return false;
    }
}

function od9_set_remember_cookie(string $selector, string $validator): void
{
    $value = $selector . ':' . $validator;
    setcookie(OD9_REMEMBER_COOKIE, $value, [
        'expires'  => time() + OD9_REMEMBER_TTL,
        'path'     => OD9_REMEMBER_PATH,
        'secure'   => od9_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[OD9_REMEMBER_COOKIE] = $value; // reflect within the current request
}

function od9_clear_remember_cookie(): void
{
    setcookie(OD9_REMEMBER_COOKIE, '', [
        'expires'  => time() - 42000,
        'path'     => OD9_REMEMBER_PATH,
        'secure'   => od9_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[OD9_REMEMBER_COOKIE]);
}
