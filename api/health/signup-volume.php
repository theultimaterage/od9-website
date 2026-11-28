<?php
/**
 * GET /api/health/signup-volume.php on offda9.com (2026-11-28, piece 4 of the form checks): is the email signup
 * being used by a program, has the site stopped taking signups, is the human check running, and how many passes
 * were used in a day?
 *
 * Counts and verdicts only, never an address or a name:
 *
 *     {"status":"OK","signups":4,"confirmed":1,"window_days":7,"reason":"...",
 *      "paused":false,"recent_signups":1,"recent_confirmed":0,"recent_hours":24,
 *      "human_check":"off","passes_24h":1}
 *
 * The same fields as freshthaband.com's and freshthaplatform.com's answers, built by the same code (the engine's
 * SignupBrake::health, through this site's generated copies under includes/form-checks/), so one reader reads all
 * three: the OD9 bot's hourly crons/signup_volume.py. Each rule's reason is in its own file: SignupVolume.php (seven
 * days), SignupBrake.php (24 hours, and this answer's shape), HumanCheck.php (the widget's state), FormGuard.php
 * (the passes: the bot's /link_email and /subscribe_email and the contact canary make one or two a day).
 *
 * stateBehind(null): this site's pages send no Content-Security-Policy, so nothing on this server can block the
 * widget. That is a fact about other files, so a test holds it: tests/test_public_forms_use_the_guard.php fails the
 * day .htaccess or any shipping PHP starts sending one.
 *
 * It is public on purpose: it holds nothing private, a monitor that needs a password breaks when the password
 * rotates, and a status of FAIL with HTTP 500 is what the caller gets when this cannot run. A check that cannot run
 * must never read as healthy.
 */
declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

try {
    // The same database the signup writes: subscribe.php tries ../config first (the account's own copy on the
    // server), then config/ beside the pages (the checkout).
    $root = dirname(__DIR__, 2);
    $configPath = dirname($root) . '/config/database.php';
    if (!file_exists($configPath)) {
        $configPath = $root . '/config/database.php';
    }
    require_once $configPath;
    require_once $root . '/includes/od9_form_guard.php';

    $pdo = getDatabaseConnection();

    echo json_encode(SignupBrake::health(
        SignupVolume::count($pdo),
        SignupBrake::count($pdo),
        HumanCheck::fromEnvironment()->stateBehind(null),
        FormGuard::countPasses()
    ));
} catch (Throwable $e) {
    error_log('signup-volume health check could not run: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'FAIL',
        'reason' => 'the check could not read the signups table',
    ]);
}
