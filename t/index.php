<?php
declare(strict_types=1);
/**
 * offda9.com/t/{o,c}/{tracking_id} — OD9 email open/click tracking on OD9's OWN
 * (sender-aligned) domain, so Gmail trusts the links and DELIVERS tracked mail.
 *
 * The CNAME route (t.offda9.com -> platform) was a cross-account TLS dead-end
 * (HTTP 526: the platform server can't present a cert for offda9's subdomain).
 * So tracking is served here, on offda9.com itself, and this handler records the
 * event on the F.R.E.S.H. platform tracker (the system of record:
 * email_tracking_events) then serves the pixel / redirect.
 *
 * Recording: an origin-pinned call to the platform's /t/ endpoint (resolve to the
 * box IP, skipping Cloudflare) so we can pass the real client IP in
 * X-Forwarded-For, which the platform's api/email-track.php reads. Fire-and-forget
 * with a short timeout — tracking must never delay or break the pixel/redirect.
 *
 * Routed by t/.htaccess:  /t/o/{id} -> ?type=open&id={id} ;  /t/c/{id} -> ?type=click&id={id}&url=...
 * See the [[od9-email-architecture]] memory + the platform repo's OD9-EMAIL-SEND-PATH.md.
 */

$type = (($_GET['type'] ?? '') === 'click') ? 'click' : 'open';
$tid  = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_GET['id'] ?? ''));
$url  = (string) ($_GET['url'] ?? '');

// offda9.com is Cloudflare-fronted: CF-Connecting-IP is the real client. For opens
// that's typically Gmail's image proxy (expected); for clicks it's the recipient.
$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

if ($tid !== '') {
    $path = ($type === 'click')
        ? '/t/c/' . rawurlencode($tid) . '?url=' . rawurlencode($url)
        : '/t/o/' . rawurlencode($tid);
    $ch = curl_init('https://freshthaplatform.com' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RESOLVE        => ['freshthaplatform.com:443:72.167.54.213'], // skip CF -> we control XFF
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER     => ['X-Forwarded-For: ' . $clientIp],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

if ($type === 'click') {
    // Open-redirect guard: a click-tracker that echoed any ?url= would be an
    // offda9.com-branded phishing hop. Redirect only to OD9-controlled / OD9-linked
    // hosts (the email footer links YouTube/IG/FB/X; content links its own domain +
    // socials); anything else fails safe to the homepage and is logged so a new
    // legit target can be allowlisted. (Robust long-term fix: sign the tracking URL
    // at generation, platform-side — this is the local guard. See SECURITY.md.)
    $dest = ($url !== '' && preg_match('#^https?://#i', $url)) ? $url : '';
    $host = $dest !== '' ? strtolower((string) parse_url($dest, PHP_URL_HOST)) : '';
    $allowed = false;
    foreach ([
        'offda9.com', 'youtube.com', 'youtu.be', 'instagram.com', 'facebook.com',
        'x.com', 'twitter.com', 'discord.gg', 'discord.com', 'patreon.com',
        'spotify.com', 'soundcloud.com',
    ] as $b) {
        if ($host === $b || ($host !== '' && str_ends_with($host, '.' . $b))) { $allowed = true; break; }
    }
    if ($dest !== '' && $allowed) {
        header('Location: ' . $dest, true, 302);
    } elseif ($dest !== '') {
        error_log('[t] blocked off-allowlist redirect: ' . $dest);
        header('Location: https://offda9.com/', true, 302);
    } else {
        http_response_code(400);
    }
    exit;
}

// Open: 1x1 transparent GIF, never cached.
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
