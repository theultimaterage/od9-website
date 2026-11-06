<?php
/**
 * OD9 session store: this account's OWN folder for PHP session files (2026-11-06).
 *
 * Every PHP site on the box left session.save_path unset, so all of them wrote into
 * cPanel's shared, root-owned, sticky folder (/var/cpanel/php/sessions/ea-php85, mode
 * 1733). A session file another Unix account created there cannot be reopened by this
 * one ("session_start(): open(.../sess_<id>, O_RDWR) failed: Permission denied (13)",
 * the freshthaplatform admin, 2026-11-06 03:39:58 CT), and cPanel's root cleaner
 * (/usr/local/cpanel/scripts/clean_user_php_sessions, :09 and :39 hourly) deletes
 * files there by the GLOBAL session.gc_maxlifetime of each PHP version and "only
 * recognizes the global" value (docs.cpanel.net/whm/scripts/the-clean_user_php_sessions-
 * script, read 2026-11-06), so the dashboard's @ini_set of OD9_REMEMBER_TTL never held
 * for the files: its comment "Shared hosting may ignore this" was exactly right.
 *
 * The cure: /home/<account>/tmp/php_sessions/<app>, one sub-folder per app so apps with
 * different lifetimes never garbage-collect each other's files (PHP's gc deletes by the
 * CURRENT request's gc_maxlifetime inside the CURRENT save_path), mode 0700, created by
 * the account itself on first use, PHP's own gc turned on there (cPanel's cleaner sweeps
 * only its default path). session.save_path, gc_probability and gc_divisor are INI_ALL
 * (php.net session.configuration, read 2026-11-06): a runtime ini_set before
 * session_start() is the whole mechanism, under any handler. Locally (XAMPP, no /home/)
 * the system temp dir is used. Same shape as shared-platform core/SessionStore.php.
 *
 * Call od9_session_store('<app>') before session_start(): includes/FormGuard.php
 * (public forms, "site"), dashboard/includes/auth.php and api/v1/bootstrap.php
 * ("dashboard"), includes/patron_gate.php ("dashboard"). Guarded so repeated includes
 * are safe. Prover: tests/test_session_store.php.
 */

if (!function_exists('od9_account_home')) {
    /** /home/<account> when `$path` (default: this file) lives under one; null elsewhere. */
    function od9_account_home(?string $path = null): ?string
    {
        $p = str_replace('\\', '/', $path ?? __DIR__);
        return preg_match('#^(/home/[^/]+)/#', $p, $m) ? $m[1] : null;
    }
}

if (!function_exists('od9_session_root')) {
    /** $SESSION_STORE_ROOT when set, else /home/<account>/tmp/php_sessions, else the temp dir. */
    function od9_session_root(?string $path = null): string
    {
        $override = getenv('SESSION_STORE_ROOT');
        if (is_string($override) && $override !== '') {
            return $override;
        }
        $home = od9_account_home($path);
        return $home !== null ? $home . '/tmp/php_sessions' : rtrim(sys_get_temp_dir(), '/\\') . '/php_sessions';
    }
}

if (!function_exists('od9_session_dir')) {
    function od9_session_dir(string $app, ?string $root = null): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $app) ?: 'site';
        return rtrim($root ?? od9_session_root(), '/\\') . '/' . $safe;
    }
}

if (!function_exists('od9_session_app')) {
    /**
     * The folder a session NAME belongs to. Every od9 session (the public forms, the
     * dashboard's Discord login, the API, the patron gate) runs under PHP's default
     * cookie name, so they are ONE session per visitor and must share ONE folder: a
     * folder per call site would have had a form submission start a fresh session
     * under the same cookie and sign the dashboard out (use_strict_mode regenerates
     * an id whose file is not in the current folder). Any other name gets its own.
     */
    function od9_session_app(?string $name = null): string
    {
        $name = $name ?? session_name();
        $safe = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $name) ?: '');
        return ($safe === '' || $safe === 'phpsessid') ? 'site' : $safe;
    }
}

if (!function_exists('od9_session_store')) {
    /**
     * Point PHP's session files at this account's own folder, before session_start().
     * The folder follows the session name in force (od9_session_app), so call it after
     * any session_name(). Returns the folder, or null when it could not be prepared
     * (logged; PHP then stays on its default path).
     *
     * The lifetime is raised to a week for every session in the folder, because PHP's
     * gc deletes by the CURRENT request's gc_maxlifetime: with the public forms at the
     * default 24 minutes, a form submission would have swept the dashboard's logins.
     * The dashboard raises its own lifetime further after this call; the remember
     * token restores a login the sweep did take, as before.
     */
    function od9_session_store(?string $app = null, ?string $root = null): ?string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return null;                              // too late to move a running session
        }
        $dir = od9_session_dir($app ?? od9_session_app(), $root);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            error_log("od9_session_store: cannot create {$dir}; sessions stay on PHP's default path");
            return null;
        }
        if (!is_writable($dir)) {
            error_log("od9_session_store: {$dir} is not writable; sessions stay on PHP's default path");
            return null;
        }
        // PHP refuses a session ini change once output has started: say so, loudly.
        if (@ini_set('session.save_path', $dir) === false) {
            error_log("od9_session_store: PHP refused session.save_path={$dir} (output already started?); sessions stay on PHP's default path");
            return null;
        }
        ini_set('session.gc_probability', '1');       // cPanel's cleaner never looks here
        ini_set('session.gc_divisor', '100');
        if ((int) ini_get('session.gc_maxlifetime') < 604800) {
            ini_set('session.gc_maxlifetime', '604800');   // a week, the public forms' own cookie lifetime
        }
        return $dir;
    }
}
