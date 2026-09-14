<?php
/**
 * env.php — the one answer to "is this the local dev environment?".
 *
 * This expression was written out by hand in eight places (board.php three
 * times, bunker.php, welcome.php, world.php, ztrans.php and config.php), and on
 * 2026-09-14 exactly one of those copies was wrong: config.php defaulted
 * SERVER_NAME to 'localhost' instead of '', and since CLI has no SERVER_NAME,
 * every command-line script on PROD resolved to the dev branch and got the
 * Windows bot-DB path. Seven copies agreed and one did not, which is the whole
 * argument for having one.
 *
 * THE PATH LEADS. It is the reliable signal in BOTH SAPIs — the dev checkout
 * lives under xampp, prod does not — and it is what makes this correct under
 * CLI. SERVER_NAME only gets a vote when there actually is one, which covers a
 * dev server running outside xampp.
 *
 * DELIBERATELY NOT CONSOLIDATED HERE, each for a stated reason:
 *   - config/database.php and config/mail.php use a stricter predicate of their
 *     own (they also accept 127.0.0.1 and require PHP_OS_FAMILY === 'Windows'),
 *     they answer a different question (ENVIRONMENT / DEBUG_MODE / credentials),
 *     and they are DEPLOY-EXCLUDED — prod keeps its own copies, so a local edit
 *     would fork repo from production rather than unify anything.
 *   - _board_preview.php guards a harness that fakes the founder's session. Its
 *     `!== 'localhost'` test is deliberately STRICTER than this helper (no path
 *     fallback); widening it would be a security regression, not a cleanup.
 *   - dashboard/includes/config.php is also deploy-excluded. It will adopt this
 *     helper only once env.php is live on prod, because a config the whole
 *     dashboard requires must never reference a file that has not shipped yet.
 *
 * The boolean is shared; the CONSEQUENCES are not. Callers still write their own
 * ternary because the local asset prefixes genuinely differ ('/od9/images/board'
 * on the board, '/od9/public/images/board' on welcome) and collapsing those into
 * one "base path" helper would have quietly broken one of them.
 *
 *   php dashboard/includes/env.php --selftest
 */
declare(strict_types=1);

/**
 * The pure decision, separated from the ambient globals so it can be tested.
 * $dir is the caller's directory; $serverName is $_SERVER['SERVER_NAME'] or null
 * when there is none (CLI).
 */
function od9_detect_local(string $dir, ?string $serverName): bool
{
    return strpos($dir, 'xampp') !== false
        || ($serverName ?? '') === 'localhost';
}

/** Is this the local dev environment? Memoised; the answer cannot change mid-request. */
function od9_is_local(): bool
{
    static $cached = null;
    if ($cached === null) {
        $cached = od9_detect_local(__DIR__, $_SERVER['SERVER_NAME'] ?? null);
    }
    return $cached;
}

// ---------------------------------------------------------------------------
// Selftest. Drives the pure decision over every SAPI/host combination that
// exists, INCLUDING the one that was wrong: prod under CLI. A detector that
// only ever sees the passing case is the failure this file was created to fix.
// ---------------------------------------------------------------------------
// The realpath() test is load-bearing, not belt-and-braces. config.php requires
// this file and EVERY dashboard page requires config.php, so without it a
// `--selftest` anywhere in $argv made this block fire from inside someone else's
// run and exit(0) before they finished. That is exactly what happened the moment
// config.php adopted the helper: `rail-labels.php --selftest` started reporting
// "env selftest: PASS" and returning 0 while its own curriculum checks never
// executed — a deploy gate passing without checking anything.
if (PHP_SAPI === 'cli'
    && in_array('--selftest', $argv ?? [], true)
    && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $dev  = 'C:\\xampp\\htdocs\\od9\\dashboard\\includes';
    $prod = '/home/offda9/public_html/dashboard/includes';
    $cases = [
        // [label,                      dir,   serverName,     expected]
        ['dev  web  (localhost)',       $dev,  'localhost',    true],
        ['dev  web  (127.0.0.1 host)',  $dev,  '127.0.0.1',    true],  // path carries it
        ['dev  CLI  (no SERVER_NAME)',  $dev,  null,           true],
        ['prod web  (offda9.com)',      $prod, 'offda9.com',   false],
        ['prod CLI  (no SERVER_NAME)',  $prod, null,           false], // the 2026-09-14 bug
        ['non-xampp dev server',        '/srv/od9/dashboard/includes', 'localhost', true],
    ];
    $fail = 0;
    foreach ($cases as [$label, $dir, $name, $want]) {
        $got = od9_detect_local($dir, $name);
        $ok  = ($got === $want);
        if (!$ok) { $fail++; }
        printf("  %s %-28s -> %-5s (want %s)\n", $ok ? ' ok ' : 'FAIL', $label,
               var_export($got, true), var_export($want, true));
    }
    // Vacuity: a detector that always returns the same thing proves nothing.
    $values = array_map(static fn($c) => od9_detect_local($c[1], $c[2]), $cases);
    if (count(array_unique($values, SORT_REGULAR)) < 2) {
        echo "  FAIL vacuity: the detector never returns both answers\n";
        $fail++;
    }
    // Memoisation must not leak between the pure form and the ambient one.
    if (od9_is_local() !== od9_detect_local(__DIR__, $_SERVER['SERVER_NAME'] ?? null)) {
        echo "  FAIL od9_is_local() disagrees with the pure decision for this machine\n";
        $fail++;
    }
    printf("\nenv selftest: %d case(s), %s\n", count($cases),
           $fail ? "$fail FAILURE(S)" : 'PASS');
    exit($fail ? 1 : 0);
}
