<?php
/**
 * GET /form-stamp.php — the form guard's stamp for a form on a page that started no session (2026-11-28, piece 4 of
 * the form checks).
 *
 * The email-signup popup is on every page (includes/nav.php prints includes/email-popup.php), and a page prints it
 * after its output has begun, when a session can no longer start. Starting one at the top of every page would set a
 * cookie and write a session file for every visitor and every crawler of every page. So the popup's form is printed
 * with an empty proof, and its script asks here when the popup opens: this starts the visitor's session, stamps the
 * render time as a page that prints a guarded form does (FormGuard::open), and answers the proof the page's script
 * puts into the form. Everything FormGuard judges is unchanged: the render time lives in the session, a post sooner
 * than MIN_SECONDS after the stamp is refused, and a post with no stamp in its session is a post with no page.
 *
 * It stores nothing a visitor typed and sends nothing; a program that asks here and posts after the wait is the same
 * program that loads a page first, which the human check exists for.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'GET only']);
    exit;
}

require_once __DIR__ . '/includes/od9_form_guard.php';
od9_form_guard_session();
FormGuard::open($_SESSION);

echo json_encode([
    'proof'       => $_SESSION[FormGuard::SESSION_KEY]['proof'],
    'min_seconds' => FormGuard::MIN_SECONDS,
]);
