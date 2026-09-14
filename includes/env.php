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

if (!function_exists('od9_local_time')) {
    /**
     * Format a bot timestamp for display in OD9's local timezone.
     *
     * The bot stores activity/streak times via SQLite's CURRENT_TIMESTAMP,
     * which is UTC. We convert to America/Chicago and label with a DST-aware
     * abbreviation (CST/CDT) so the dashboard never shows bare UTC. Falls back
     * to the raw string on parse failure rather than throwing.
     */
    function od9_local_time(?string $utc, string $fmt = 'M j, g:i A T'): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }
        try {
            $dt = new DateTime($utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('America/Chicago'));
            return $dt->format($fmt);
        } catch (Throwable $e) {
            return $utc;
        }
    }
}

// ---------------------------------------------------------------------------
// Selftest (2026-09-14). Added after a second od9_is_local() was written at
// dashboard/includes/env.php in ignorance of this one — both guarded with
// function_exists, so include order silently decided which semantics a page got.
// This definition is the one that survived, and deliberately so: it is path-only,
// because SERVER_NAME is absent under CLI and spoofable over HTTP. That is not an
// omission, it is the point, and the CLI row below is what pins it.
// ---------------------------------------------------------------------------
if (PHP_SAPI === 'cli'
    && in_array('--selftest', $argv ?? [], true)
    && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $fail = 0;
    // The decision is __DIR__-based, so drive the predicate directly rather than
    // pretending we can relocate this file mid-run.
    $probe = static fn(string $dir): bool => stripos($dir, 'xampp') !== false;
    $cases = [
        ['dev  (xampp path)',        'C:\xampp\htdocs\od9\includes',        true],
        ['dev  (mixed case XAMPP)',  'C:\XAMPP\htdocs\od9\includes',        true],
        ['prod (cPanel path)',       '/home/offda9/public_html/includes',       false],
        ['prod-like, no xampp',      '/srv/od9/includes',                       false],
    ];
    foreach ($cases as [$label, $dir, $want]) {
        $got = $probe($dir);
        $ok  = ($got === $want);
        if (!$ok) { $fail++; }
        printf("  %s %-26s -> %-5s (want %s)\n", $ok ? ' ok ' : 'FAIL', $label,
               var_export($got, true), var_export($want, true));
    }
    // Vacuity: a predicate that never returns both answers proves nothing.
    if (count(array_unique(array_map(static fn($c) => $probe($c[1]), $cases))) < 2) {
        echo "  FAIL vacuity: the predicate never returns both answers\n"; $fail++;
    }
    // And the live function must agree with the predicate for THIS machine —
    // the guard that catches a second definition winning the include race.
    if (od9_is_local() !== $probe(__DIR__)) {
        echo "  FAIL od9_is_local() disagrees with this file's own rule — another\n"
           . "       definition of od9_is_local() loaded first. There must be only one.\n";
        $fail++;
    }
    if (od9_cookie_secure() === od9_is_local()) {
        echo "  FAIL od9_cookie_secure() must be the inverse of od9_is_local()\n"; $fail++;
    }
    printf("\nenv selftest: %d case(s), %s\n", count($cases), $fail ? "$fail FAILURE(S)" : 'PASS');
    exit($fail ? 1 : 0);
}
