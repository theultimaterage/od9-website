<?php
/**
 * OD9 environment + base-path helpers (Tier-2, 2026-06-06).
 *
 * One place to answer "are we on local XAMPP or prod?" and the things that
 * depend on it (secure-cookie flag, base path), so pages stop re-implementing
 * `strpos(__DIR__, 'xampp')` inline — the OWN_ENV drift behind the /dashboard
 * base-path + secure-cookie bugs. Mirrors the convention already used by
 * includes/nav.php + includes/head.php. Guarded so multiple includes are safe.
 */

if (!function_exists('od9_is_local')) {
    /**
     * True on local XAMPP, false on prod. The entire repo lives under a path
     * containing "xampp" locally (C:\xampp\...) and never on prod
     * (/home/offda9/...), so __DIR__ is a reliable, request-independent signal
     * — unlike $_SERVER['SERVER_NAME'], which is absent on CLI and spoofable.
     * This preserves the exact semantics of the inline checks it replaces.
     */
    function od9_is_local(): bool
    {
        return stripos(__DIR__, 'xampp') !== false;
    }
}

if (!function_exists('od9_cookie_secure')) {
    /** Session cookies must be Secure on prod (https), not on local http. */
    function od9_cookie_secure(): bool
    {
        return !od9_is_local();
    }
}

if (!function_exists('od9_base_path')) {
    /**
     * Same-origin base path for assets/links. Honors a caller-set value
     * (subdir pages pass the site root); else derives from SCRIPT_NAME.
     */
    function od9_base_path(?string $override = null): string
    {
        if ($override !== null) {
            return rtrim($override, '/\\');
        }
        return rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
    }
}
