<?php
// TEMPORARY prod diagnostic — proves whether prod's outbound SMTP to Brevo
// actually delivers via od9_send_mail. Captures the driver, the boolean
// result, and any [od9_send_mail] SMTP error to a file we can read back.
// Deleted immediately after the run.
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', '/tmp/od9_mailprobe_err.log');
@unlink('/tmp/od9_mailprobe_err.log');

require '/home/offda9/public_html/config/mail.php';
require '/home/offda9/public_html/includes/mail.php';

fwrite(STDOUT, 'DRIVER=' . MAIL_DRIVER . ' ENV=' . MAIL_ENVIRONMENT . "\n");

$to = $argv[1] ?? 'theultimaterage+brevoprod@gmail.com';
$ok = od9_send_mail(
    $to,
    'OD9 prod SMTP diagnostic',
    '<p>OD9 prod outbound SMTP diagnostic via od9_send_mail through the Brevo relay.</p>',
    ['from_email' => 'noreply@offda9.com', 'from_name' => 'The OD9 Movement', 'reply_to' => 'contact@offda9.com']
);
fwrite(STDOUT, 'RESULT=' . ($ok ? 'SENT_OK' : 'FAILED') . "\n");
