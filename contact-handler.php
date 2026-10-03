<?php
/**
 * OD9 Contact Form Handler.
 *
 * Receives the contact.php POST, passes it through FormGuard (includes/FormGuard.php),
 * validates and sanitizes it, and emails it to contact@offda9.com through
 * od9_send_mail(). contact@ is a mailbox on this server, so od9_mail_route()
 * delivers it with PHP mail() and the server's own sendmail. It never goes through
 * Brevo, which accepted and dropped every notice from 2026-06-16 to 2026-10-02
 * (236 of them) while this handler reported success.
 *
 * Every outcome is one line in logs/contact-form.jsonl (no name, no address, no
 * message). The daily form canary on the bot (crons/contact_form_canary.py) posts
 * through this handler like a browser and checks that the message reached the
 * mailbox with a clean From and Reply-To.
 *
 * The old fallback that called mail() with hand-built headers is gone: it was the
 * same transport as the 'local' route, and its trailing CRLF folded the visitor's
 * Reply-To into the From header of every notice it ever sent.
 *
 * Runs as the web user (offda9) on prod.
 */
declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: contact.php');
    exit;
}

require_once __DIR__ . '/includes/FormGuard.php';

/** One line per outcome in logs/contact-form.jsonl. Never throws; a failed write is logged with its cause. */
function od9_contact_log(string $outcome, array $extra = []): void
{
    $line = json_encode(['ts' => gmdate('Y-m-d\TH:i:s\Z'), 'outcome' => $outcome] + $extra, JSON_UNESCAPED_SLASHES);
    $file = __DIR__ . '/logs/contact-form.jsonl';
    error_clear_last();
    // silent-failure:ignore — checked below, and logged with its cause
    if ($line === false || @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log('[contact-handler] could not write ' . $file . ' (' . (error_get_last()['message'] ?? 'PHP gave no reason') . ')');
    }
}

FormGuard::bootSession();
try {
    FormGuard::admit('contact', $_SESSION, $_POST, $_SERVER);
} catch (FormGuardRefused $e) { // silent-failure:ignore — logged by FormGuard::record() before the throw; its text is shown to the visitor
    od9_contact_log('refused', ['reason' => $e->reason]);
    header('Location: contact.php?error=guard');
    exit;
}

// Strip CR/LF before anything reaches a mail header (subject/from) — prevents
// email header injection via the free-text name field.
$strip   = static fn(string $s): string => trim(str_replace(["\r", "\n"], ' ', $s));
$name    = htmlspecialchars($strip((string) ($_POST['name'] ?? '')), ENT_QUOTES);
$email   = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
$subject = $strip((string) ($_POST['subject'] ?? ''));
$message = htmlspecialchars(trim((string) ($_POST['message'] ?? '')), ENT_QUOTES);

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    od9_contact_log('invalid', ['reason' => 'empty']);
    header('Location: contact.php?error=empty');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    od9_contact_log('invalid', ['reason' => 'email']);
    header('Location: contact.php?error=email');
    exit;
}

// Counted only for a message that is about to be sent, so a visitor fixing a typo is
// not charged for it.
try {
    FormGuard::limit('contact', $_SERVER);
} catch (FormGuardRefused $e) { // silent-failure:ignore — logged by FormGuard::record() before the throw; its text is shown to the visitor
    od9_contact_log('refused', ['reason' => $e->reason]);
    header('Location: contact.php?error=rate');
    exit;
}

// Whitelist the subject so nothing attacker-controlled lands in the header.
$subjects = [
    'general' => 'General Inquiry',
    'collab'  => 'Collaboration / Features',
    'booking' => 'Booking / Events',
    'press'   => 'Press / Media',
    'support' => 'Technical Support',
    'other'   => 'Other',
];
$subject_text  = $subjects[$subject] ?? 'Contact Form';
$to            = 'contact@offda9.com';
$email_subject = "[OD9 Contact] {$subject_text} - from {$name}";

$sent  = false;
$route = 'no-library';

// Resolve config/includes for both prod (flattened into public_html/) and local
// (repo-root, one level up).
$cfg = __DIR__ . '/config/mail.php';
if (!is_file($cfg)) { $cfg = __DIR__ . '/../config/mail.php'; }
$lib = __DIR__ . '/includes/mail.php';
if (!is_file($lib)) { $lib = __DIR__ . '/../includes/mail.php'; }
if (is_file($cfg) && is_file($lib)) {
    require_once $cfg;
    require_once $lib;
    $route = od9_mail_route($to);
    $html = '<p>New message from the OD9 website contact form.</p>'
          . '<p><strong>Name:</strong> ' . $name . '<br>'
          . '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES) . '<br>'
          . '<strong>Subject:</strong> ' . $subject_text . '</p>'
          . '<p><strong>Message:</strong><br>' . nl2br($message) . '</p>';
    $sent = od9_send_mail($to, $email_subject, $html, ['reply_to' => $email]);
} else {
    error_log('[contact-handler] the mail library is missing: ' . $cfg . ' / ' . $lib);
}

od9_contact_log($sent ? 'sent' : 'failed', ['route' => $route, 'topic' => $subject_text]);
header('Location: contact.php?' . ($sent ? 'success=1' : 'error=send'));
exit;
