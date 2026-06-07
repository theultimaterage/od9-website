<?php
/**
 * OD9 Contact Form Handler.
 *
 * Receives the contact.php POST, validates + sanitizes, and emails the message
 * to contact@offda9.com. Sends through od9_send_mail() (Brevo/DKIM on prod) and
 * falls back to PHP mail() only if the mail backbone can't be loaded — so it is
 * never worse than the legacy mail()-only handler.
 *
 * Runs as the web user (offda9) on prod, so it can read the mode-600
 * config/mail.config.php that config/mail.php loads.
 */
declare(strict_types=1);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: contact.php');
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
    header('Location: contact.php?error=empty');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: contact.php?error=email');
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

$sent = false;

// Preferred path: the unified Brevo mailer. Resolve config/includes for both
// prod (flattened into public_html/) and local (repo-root, one level up).
$cfg = __DIR__ . '/config/mail.php';
if (!is_file($cfg)) { $cfg = __DIR__ . '/../config/mail.php'; }
$lib = __DIR__ . '/includes/mail.php';
if (!is_file($lib)) { $lib = __DIR__ . '/../includes/mail.php'; }
if (is_file($cfg) && is_file($lib)) {
    require_once $cfg;
    require_once $lib;
    if (function_exists('od9_send_mail')) {
        $html = '<p>New message from the OD9 website contact form.</p>'
              . '<p><strong>Name:</strong> ' . $name . '<br>'
              . '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES) . '<br>'
              . '<strong>Subject:</strong> ' . $subject_text . '</p>'
              . '<p><strong>Message:</strong><br>' . nl2br($message) . '</p>';
        $sent = od9_send_mail($to, $email_subject, $html, ['reply_to' => $email]);
    }
}

// Fallback: legacy mail() (only if the backbone was unavailable or failed).
if (!$sent) {
    $body = "You have received a new message from the OD9 website contact form.\n\n"
          . "Name: {$name}\nEmail: {$email}\nSubject: {$subject_text}\n\nMessage:\n{$message}\n";
    $headers = "From: noreply@offda9.com\r\nReply-To: {$email}\r\n";
    $sent = @mail($to, $email_subject, $body, $headers);
}

header('Location: contact.php?' . ($sent ? 'success=1' : 'error=send'));
exit;
