<?php
/**
 * OD9 mail helper — the ONE send path for the whole site.
 *
 * od9_send_mail() routes through a driver chosen by config/mail.php's MAIL_DRIVER:
 *   - 'smtp'             (prod) : authenticated SMTP to the host mailserver
 *                                  (offda9.com:465 SSL, or :587 STARTTLS) as
 *                                  noreply@offda9.com. exim DKIM-signs outbound
 *                                  with offda9.com's published key. Sends a
 *                                  multipart/alternative body (text + html) and
 *                                  a List-Unsubscribe header.
 *   - 'mailtrap_sandbox' (local): POST to Mailtrap Sandbox HTTP API (capture).
 *   - 'mail_function'    (fallback): PHP mail() / exim.
 *
 * Secrets live in config/mail.config.php (gitignored, mode 600 on prod).
 *
 * Usage:
 *   od9_send_mail($to, $subject, $html, [
 *       'text'             => '...',          // optional; auto from html if omitted
 *       'from_email'       => '...', 'from_name' => '...', 'reply_to' => '...',
 *       'list_unsubscribe' => '<https://offda9.com/unsubscribe.php?...>, <mailto:...>',
 *       'headers'          => ['X-Foo' => 'bar'],
 *   ]);
 */

$_od9_mail_cfg = __DIR__ . '/../config/mail.php';
if (!file_exists($_od9_mail_cfg)) $_od9_mail_cfg = __DIR__ . '/config/mail.php';
require_once $_od9_mail_cfg;


function od9_send_mail(string $to, string $subject, string $html, array $opts = []): bool
{
    $from_email = $opts['from_email'] ?? (defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@offda9.com');
    $from_name  = $opts['from_name']  ?? (defined('MAIL_FROM_NAME')  ? MAIL_FROM_NAME  : 'The OD9 Movement');
    $reply_to   = $opts['reply_to']   ?? (defined('MAIL_REPLY_TO')   ? MAIL_REPLY_TO   : 'contact@offda9.com');
    $text       = $opts['text']       ?? od9_html_to_text($html);
    $listUnsub  = $opts['list_unsubscribe'] ?? null;
    $extra      = $opts['headers'] ?? [];

    $driver = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'mail_function';
    switch ($driver) {
        case 'smtp':
            return _od9_mail_via_smtp($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
        case 'mailtrap_sandbox':
            return _od9_mail_via_mailtrap($to, $subject, $html, $from_email, $from_name, $reply_to, $listUnsub, $extra);
        default:
            return _od9_mail_via_mailfunc($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
    }
}


/** Crude-but-serviceable HTML -> plain text for the multipart/alternative text part. */
function od9_html_to_text(string $html): string
{
    $t = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', '', $html);
    $t = preg_replace('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $t);
    $t = preg_replace('/<br\s*\/?>/i', "\n", $t);
    $t = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $t);
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace("/[ \t]+/", ' ', $t);
    $t = preg_replace("/\n{3,}/", "\n\n", $t);
    return trim($t);
}


/** Build RFC-5322 message (headers + multipart/alternative body). */
function _od9_build_message(string $to, string $subject, string $html, string $text,
                           string $from_email, string $from_name, string $reply_to,
                           ?string $listUnsub, array $extra): string
{
    $boundary = '=_od9_' . bin2hex(random_bytes(12));
    $encName  = preg_match('/[\x80-\xFF]/', $from_name)
        ? '=?UTF-8?B?' . base64_encode($from_name) . '?='
        : $from_name;
    $encSubj  = preg_match('/[\x80-\xFF]/', $subject)
        ? '=?UTF-8?B?' . base64_encode($subject) . '?='
        : $subject;

    $h = [];
    $h[] = "From: {$encName} <{$from_email}>";
    $h[] = "To: <{$to}>";
    $h[] = "Subject: {$encSubj}";
    $h[] = "Reply-To: {$reply_to}";
    $h[] = 'Date: ' . date('r');
    $h[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@offda9.com>';
    $h[] = 'MIME-Version: 1.0';
    if ($listUnsub) {
        $h[] = "List-Unsubscribe: {$listUnsub}";
        $h[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
    }
    $h[] = 'X-Mailer: OD9-Mailer/2.0';
    foreach ($extra as $k => $v) {
        $h[] = "{$k}: {$v}";
    }
    $h[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($text) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($html) . "\r\n";
    $body .= "--{$boundary}--\r\n";

    return implode("\r\n", $h) . "\r\n\r\n" . $body;
}


/** Authenticated SMTP via raw sockets (no external deps). Handles 465 (implicit
 *  SSL) and 587 (STARTTLS). */
function _od9_mail_via_smtp(string $to, string $subject, string $html, string $text,
                           string $from_email, string $from_name, string $reply_to,
                           ?string $listUnsub, array $extra): bool
{
    foreach (['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS'] as $c) {
        if (!defined($c) || constant($c) === '') {
            error_log("[od9_send_mail] SMTP not configured (missing {$c})");
            return false;
        }
    }
    $port   = (int) SMTP_PORT;
    $secure = defined('SMTP_SECURE') ? SMTP_SECURE : ($port === 465 ? 'ssl' : 'tls');
    // Verify the cert by default; allow opt-out (SMTP_VERIFY=false) when connecting
    // to the mail server by IP, where the cert CN won't match the address.
    $verify = defined('SMTP_VERIFY') ? (bool) SMTP_VERIFY : true;
    $ctx = stream_context_create(['ssl' => [
        'verify_peer'       => $verify,
        'verify_peer_name'  => $verify,
        'allow_self_signed' => !$verify,
        'SNI_enabled'       => true,
    ]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . $port;
    $sock = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$sock) {
        error_log("[od9_send_mail] SMTP connect failed to {$remote} — {$errstr} ({$errno})");
        return false;
    }
    stream_set_timeout($sock, 20);

    $read = static function () use ($sock): string {
        $data = '';
        while (($line = fgets($sock, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;  // last line of a multiline reply
        }
        return $data;
    };
    $cmd = static function (string $c, string $expect) use ($sock, $read): bool {
        fwrite($sock, $c . "\r\n");
        $resp = $read();
        if (strncmp($resp, $expect, strlen($expect)) !== 0) {
            error_log("[od9_send_mail] SMTP '" . preg_replace('/\R/', ' ', substr($c, 0, 12))
                . "…' got: " . trim($resp) . " (wanted {$expect})");
            return false;
        }
        return true;
    };

    $ehlo = 'EHLO ' . (gethostname() ?: 'offda9.com');
    $ok = true;
    $read();                                  // 220 greeting
    $ok = $ok && $cmd($ehlo, '250');
    if ($ok && $secure === 'tls') {           // STARTTLS path (587)
        $ok = $ok && $cmd('STARTTLS', '220');
        $ok = $ok && stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $ok = $ok && $cmd($ehlo, '250');
    }
    $ok = $ok && $cmd('AUTH LOGIN', '334');
    $ok = $ok && $cmd(base64_encode(SMTP_USER), '334');
    $ok = $ok && $cmd(base64_encode(SMTP_PASS), '235');
    $ok = $ok && $cmd('MAIL FROM:<' . $from_email . '>', '250');
    $ok = $ok && $cmd('RCPT TO:<' . $to . '>', '250');
    $ok = $ok && $cmd('DATA', '354');
    if ($ok) {
        $msg = _od9_build_message($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
        $msg = preg_replace('/^\./m', '..', $msg);     // dot-stuffing
        fwrite($sock, $msg . "\r\n.\r\n");
        $resp = $read();
        if (strncmp($resp, '250', 3) !== 0) {
            error_log('[od9_send_mail] SMTP DATA-end got: ' . trim($resp));
            $ok = false;
        }
    }
    @fwrite($sock, "QUIT\r\n");
    fclose($sock);
    return $ok;
}


/** Mailtrap Sandbox HTTP API (local capture only — never delivers). */
function _od9_mail_via_mailtrap(string $to, string $subject, string $html,
                               string $from_email, string $from_name,
                               ?string $reply_to, ?string $listUnsub, array $extra): bool
{
    if (!defined('MAILTRAP_INBOX_ID') || !defined('MAILTRAP_API_TOKEN')
        || MAILTRAP_INBOX_ID === 'REPLACE_WITH_INBOX_ID' || MAILTRAP_API_TOKEN === 'REPLACE_WITH_API_TOKEN') {
        error_log('[od9_send_mail] mailtrap config missing — install config/mail.config.php');
        return false;
    }
    $headers = $extra;
    if ($reply_to)  $headers['Reply-To'] = $reply_to;
    if ($listUnsub) $headers['List-Unsubscribe'] = $listUnsub;
    $payload = [
        'from'    => ['email' => $from_email, 'name' => $from_name],
        'to'      => [['email' => $to]],
        'subject' => $subject,
        'html'    => $html,
        'text'    => od9_html_to_text($html),
    ];
    if ($headers) $payload['headers'] = $headers;
    $ch = curl_init(MAILTRAP_API_BASE . '/' . MAILTRAP_INBOX_ID);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MAILTRAP_API_TOKEN, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) return true;
    error_log("[od9_send_mail] mailtrap send failed: HTTP {$code} — " . substr((string) $resp, 0, 300));
    return false;
}


/** PHP mail() fallback — multipart, but no DKIM/auth. Last resort. */
function _od9_mail_via_mailfunc(string $to, string $subject, string $html, string $text,
                               string $from_email, string $from_name, string $reply_to,
                               ?string $listUnsub, array $extra): bool
{
    $full = _od9_build_message($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
    // Split the headers we built from the body for mail()'s separate args.
    [$headerBlock, $body] = explode("\r\n\r\n", $full, 2);
    $headers = preg_replace('/^(To|Subject):.*$/im', '', $headerBlock); // mail() sets To/Subject itself
    $headers = preg_replace('/\R{2,}/', "\r\n", trim($headers));
    return @mail($to, $subject, $body, $headers, '-f ' . $from_email);
}
