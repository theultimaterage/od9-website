<?php
declare(strict_types=1);
/**
 * OD9 -> F.R.E.S.H. platform email client  (OD9-side half of the email migration).
 *
 * Routes OD9 email through the shared-platform engine instead of the standalone
 * offda9 SMTP layer (includes/mail.php). The transport UNDERNEATH is the SAME
 * Brevo relay as noreply@offda9.com, so deliverability is identical (SPF/DKIM/
 * DMARC inherited) -- the win is centralization + open/click tracking + the
 * email_queue system-of-record + future campaign/sequence migration.
 *
 * Contract (built + E2E-verified platform-side 2026-06-11):
 *   docs OD9-EMAIL-SEND-PATH.md (in the shared-platform repo).
 *   POST https://admin.freshthaplatform.com/api/od9-send-email.php
 *   Auth: HMAC-SHA256 of the raw JSON body, hex, header X-OD9-Signature.
 *         Secret = OD9_SYNC_WEBHOOK_SECRET -- the SAME secret member-sync uses
 *         (one OD9<->platform trust channel; zero new provisioning on that axis).
 *   send: {action,to,subject,template='od9-transmission',track,vars{...}} where
 *         vars.body_html is the rendered INNER card HTML (OD9 keeps its markdown
 *         pipeline); the platform's od9-transmission template -- a port of
 *         od9_email_layout() -- provides the shell. Do NOT pass od9_email_layout()
 *         output as body_html (double shell).
 *
 * Cloudflare: the public edge 403s bare clients. On-box senders pin the endpoint
 * hostname to the origin IP (OD9_PLATFORM_EMAIL_RESOLVE, default 72.167.54.213)
 * and relax peer verification in resolve mode only -- the request still goes TLS
 * to our own box and never crosses the public edge (CF Origin cert isn't publicly
 * trusted). Set OD9_PLATFORM_EMAIL_RESOLVE='' to force the public edge instead.
 *
 * Self-access (per ops standard): every call appends a line to
 * logs/platform_email.jsonl (no secrets, no full body) so a failure is greppable
 * without relaying anything.
 *
 * Secret resolution order: getenv('OD9_SYNC_WEBHOOK_SECRET'), then a
 * OD9_SYNC_WEBHOOK_SECRET constant from config/platform.config.php (gitignored,
 * mode 600 on prod). Until that file is provisioned on offda9, send() fails
 * closed with a clear error and the caller can fall back to od9_send_mail().
 */

$_od9_plat_cfg = __DIR__ . '/../config/platform.config.php';
if (is_file($_od9_plat_cfg)) {
    require_once $_od9_plat_cfg;
}

if (!function_exists('od9_platform_email_log')) {
    /** Append a structured, secret-free line to the self-access JSONL. */
    function od9_platform_email_log(array $row): void
    {
        $row = ['ts' => gmdate('c')] + $row;
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/platform_email.jsonl',
            json_encode($row, JSON_UNESCAPED_SLASHES) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}

if (!function_exists('od9_platform_email_secret')) {
    function od9_platform_email_secret(): string
    {
        $s = getenv('OD9_SYNC_WEBHOOK_SECRET');
        if (!is_string($s) || $s === '') {
            $s = defined('OD9_SYNC_WEBHOOK_SECRET') ? (string) OD9_SYNC_WEBHOOK_SECRET : '';
        }
        return trim((string) $s);
    }
}

if (!function_exists('od9_platform_email_endpoint')) {
    function od9_platform_email_endpoint(): string
    {
        $e = getenv('OD9_PLATFORM_EMAIL_ENDPOINT');
        return ($e && is_string($e)) ? $e : 'https://admin.freshthaplatform.com/api/od9-send-email.php';
    }
}

if (!function_exists('od9_platform_email_post')) {
    /**
     * Signed POST to the platform endpoint.
     *
     * @return array Decoded JSON response, always with bool 'success' + int 'http'.
     *               On dry-run: {success:true, dry_run:true, bytes, signed:true}.
     */
    function od9_platform_email_post(array $payload, bool $dryRun = false): array
    {
        $action = (string) ($payload['action'] ?? '?');
        $secret = od9_platform_email_secret();
        if ($secret === '') {
            $r = ['success' => false, 'http' => 0,
                  'error' => 'OD9_SYNC_WEBHOOK_SECRET not configured (env or config/platform.config.php)'];
            od9_platform_email_log(['action' => $action, 'success' => false, 'error' => $r['error']]);
            return $r;
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $sig  = hash_hmac('sha256', $body, $secret);

        if ($dryRun) {
            $r = ['success' => true, 'dry_run' => true, 'http' => 0, 'bytes' => strlen($body), 'signed' => true];
            od9_platform_email_log(['action' => $action, 'dry_run' => true, 'bytes' => $r['bytes']]);
            return $r;
        }

        $endpoint = od9_platform_email_endpoint();
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-OD9-Signature: ' . $sig],
            CURLOPT_USERAGENT      => 'od9-platform-mail/1.0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        // Cloudflare bypass: pin hostname -> origin IP (see file header).
        $resolveIp = getenv('OD9_PLATFORM_EMAIL_RESOLVE');
        if ($resolveIp === false) {
            $resolveIp = '72.167.54.213';
        }
        if ($resolveIp !== '') {
            $host   = parse_url($endpoint, PHP_URL_HOST);
            $scheme = parse_url($endpoint, PHP_URL_SCHEME);
            $port   = ($scheme === 'https') ? 443 : 80;
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:{$port}:{$resolveIp}"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        unset($ch); // curl_close() deprecated on PHP 8.5 (no-op since 8.0)

        if ($resp === false) {
            $r = ['success' => false, 'http' => $code, 'error' => "curl: {$err}"];
        } else {
            $decoded = json_decode((string) $resp, true);
            if (is_array($decoded)) {
                $r = $decoded;
                $r['http'] = $code;
                $r['success'] = (bool) ($decoded['success'] ?? ($code >= 200 && $code < 300));
            } else {
                $r = ['success' => false, 'http' => $code,
                      'error' => 'non-JSON response', 'raw' => substr((string) $resp, 0, 200)];
            }
        }

        od9_platform_email_log(array_intersect_key(
            ['action' => $action] + $r,
            array_flip(['action', 'success', 'http', 'error', 'tracking_id', 'queue_id', 'status'])
        ));
        return $r;
    }
}

if (!function_exists('od9_platform_email_test')) {
    /** Connectivity + signature check. Sends NO email. */
    function od9_platform_email_test(): array
    {
        return od9_platform_email_post(['action' => 'test']);
    }
}

if (!function_exists('od9_send_via_platform')) {
    /**
     * Send an OD9 email through the platform engine.
     *
     * @param string $to        Recipient.
     * @param string $subject   Subject (<=500 chars).
     * @param string $body_html Rendered INNER card HTML (OD9 markdown pipeline output) —
     *                          NOT od9_email_layout()-wrapped; the template adds the shell.
     * @param array  $opts      title, preheader, transmission ('07 / 33'),
     *                          unsubscribe_url (default offda9 unsubscribe), cta_label,
     *                          cta_url, track (bool, default true),
     *                          template (default 'od9-transmission'), dry_run (bool).
     * @return array Endpoint response: {success, tracking_id, queue_id, status, http, error?}.
     */
    function od9_send_via_platform(string $to, string $subject, string $body_html, array $opts = []): array
    {
        $vars = [
            'body_html' => $body_html,
            'title'     => (string) ($opts['title'] ?? $subject),
        ];
        if (empty($opts['unsubscribe_url'])) {
            $vars['unsubscribe_url'] = 'https://offda9.com/unsubscribe.php?email=' . rawurlencode($to);
        }
        foreach (['preheader', 'transmission', 'unsubscribe_url', 'cta_label', 'cta_url'] as $k) {
            if (!empty($opts[$k])) {
                $vars[$k] = (string) $opts[$k];
            }
        }

        $payload = [
            'action'   => 'send',
            'to'       => $to,
            'subject'  => $subject,
            'template' => (string) ($opts['template'] ?? 'od9-transmission'),
            'track'    => array_key_exists('track', $opts) ? (bool) $opts['track'] : true,
            'vars'     => $vars,
        ];
        return od9_platform_email_post($payload, !empty($opts['dry_run']));
    }
}

// --------------------------------------------------------------------------
// CLI self-test (runs ONLY when this file is executed directly, never on include):
//   php includes/platform_mail.php test                 # action:test (no send)
//   php includes/platform_mail.php dryrun               # build+sign, no POST
//   php includes/platform_mail.php send you@example.com # real branded test send
// Secret comes from $OD9_SYNC_WEBHOOK_SECRET in the environment.
// --------------------------------------------------------------------------
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $cmd = $argv[1] ?? 'test';
    if ($cmd === 'test') {
        $r = od9_platform_email_test();
    } elseif ($cmd === 'dryrun') {
        $r = od9_send_via_platform('verify@example.com', 'OD9 platform_mail dry-run',
            '<h1>Dry run</h1><p>Payload construction + HMAC only; nothing sent.</p>',
            ['transmission' => '00 / 33', 'cta_label' => 'Enter the Movement',
             'cta_url' => 'https://offda9.com', 'dry_run' => true]);
    } elseif ($cmd === 'send' && !empty($argv[2])) {
        $r = od9_send_via_platform($argv[2], 'OD9 Transmission 00 — OD9-side client test',
            '<h1>OD9-side client test</h1>'
            . '<p>This send was built by <code>includes/platform_mail.php</code> on the OD9 side, '
            . 'signed with the shared secret, and delivered through the F.R.E.S.H. platform engine '
            . '(Brevo relay, open/click tracking).</p>'
            . '<blockquote><p>Level up or get left behind.</p></blockquote>',
            ['transmission' => '00 / 33', 'preheader' => 'OD9-side platform client test.',
             'cta_label' => 'Enter the Movement', 'cta_url' => 'https://offda9.com']);
    } else {
        fwrite(STDERR, "usage: php includes/platform_mail.php [test|dryrun|send <email>]\n");
        exit(2);
    }
    echo json_encode($r, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    exit(!empty($r['success']) ? 0 : 1);
}
