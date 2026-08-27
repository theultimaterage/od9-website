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
 *   - 'file'             (local): write the rendered .eml to logs/mail-outbox/
 *                                  (capture only — no send, no quota, no network).
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

    // ---- Platform-primary route (transitional cutover, 2026-06-14) ----
    // Send the fully-rendered, already-branded HTML through the F.R.E.S.H. platform
    // engine via the 'passthrough' template (echoes body_html verbatim — no double
    // shell), so EVERY OD9 sender is centralized in the platform email_queue with a
    // single change here (zero per-sender surgery). Tracking is OFF until the
    // per-tenant tracking domain is aligned to offda9.com — otherwise Gmail silently
    // drops the freshthaplatform.com link/pixel mismatch (proven 2026-06-14). ANY
    // failure falls through to the SMTP/transport driver below, so a platform hiccup
    // never drops mail mid-cutover. Enabled by MAIL_USE_PLATFORM in
    // config/platform.config.php (loaded by platform_mail.php); absent locally =>
    // skipped, so dev keeps using Mailtrap. Pass $opts['_no_platform']=true to force
    // the legacy path for one send.
    if (empty($opts['_no_platform'])) {
        $_pm = __DIR__ . '/platform_mail.php';
        if (is_file($_pm)) {
            require_once $_pm;
            if (defined('MAIL_USE_PLATFORM') && MAIL_USE_PLATFORM
                && function_exists('od9_send_via_platform')) {
                $_r = od9_send_via_platform($to, $subject, $html, [
                    'template' => 'passthrough',
                    'track'    => true,   // ON via offda9.com/t/ tracking domain (2026-06-14)
                    'title'    => $opts['title'] ?? $subject,
                ]);
                if (!empty($_r['success'])) {
                    return true;
                }
                error_log('[od9_send_mail] platform route failed (to=' . $to
                    . ', http=' . ($_r['http'] ?? '?') . ', err=' . ($_r['error'] ?? '?')
                    . ') — falling back to ' . (defined('MAIL_DRIVER') ? MAIL_DRIVER : 'mail_function'));
            }
        }
    }

    $driver = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'mail_function';
    switch ($driver) {
        case 'smtp':
            return _od9_mail_via_smtp($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
        case 'file':
            return _od9_mail_via_file($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
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


/** Local dev sink — write the fully rendered .eml to logs/mail-outbox/ instead of
 *  sending. No external service, no quota, no network hang; the captured message is
 *  inspectable on disk (and `_latest.eml` always points at the newest, which the
 *  smoke test reads to verify the email actually rendered). Replaced the Mailtrap
 *  sandbox driver 2026-06-26. */
function _od9_mail_via_file(string $to, string $subject, string $html, string $text,
                           string $from_email, string $from_name, string $reply_to,
                           ?string $listUnsub, array $extra): bool
{
    $dir = __DIR__ . '/../logs/mail-outbox';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        error_log("[od9_send_mail] file-sink: cannot create {$dir}");
        return false;
    }
    $msg = _od9_build_message($to, $subject, $html, $text, $from_email, $from_name, $reply_to, $listUnsub, $extra);
    $safeTo = preg_replace('/[^A-Za-z0-9._@-]/', '_', $to);
    $file = $dir . '/' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '-' . $safeTo . '.eml';
    $ok = @file_put_contents($file, $msg) !== false;
    @file_put_contents($dir . '/_latest.eml', $msg);   // deterministic pointer for the smoke test
    error_log($ok ? "[od9_send_mail] file-sink wrote {$file}"
                  : "[od9_send_mail] file-sink FAILED to write {$file}");
    return $ok;
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
