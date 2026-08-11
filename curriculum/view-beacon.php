<?php
/**
 * Canon-lesson VIEW beacon endpoint (2026-07-15).
 *
 * Hit by navigator.sendBeacon() from a Codex lesson page (see _codex.php). It runs
 * in the beacon's OWN request, so the lesson page render is NEVER blocked on the bot.
 * When a LOGGED-IN member views a lesson, it forwards a 0-credit `lesson_view` to the
 * bot over the same HMAC path board-action.php uses — closing the "reading blind spot"
 * the 2026-07-05 launch redteam surfaced: content_completion records only SUBMITTED
 * reflections, so a member who reads a lesson and doesn't reflect left no trace, making
 * "reflection-chokepoint friction" indistinguishable from "no demand".
 *
 * No CSRF token: this grants no credit and mutates no economy — the worst a forged
 * hit does is record an analytics view for the victim's own session (harmless noise),
 * so the board-action.php CSRF guard (which protects credit-granting actions) is N/A.
 * Anonymous readers, unknown paths, and an unset secret all no-op. Always 204 — a
 * beacon has no user-facing response and must never surface an error to the reader.
 */
declare(strict_types=1);

function beacon_end(): void { http_response_code(204); exit; }

// Same bootstrap order as board-action.php (config → auth → session), so OD9_GUILD_ID,
// the env secret, and $_SESSION resolve identically to the proven write-path.
require_once __DIR__ . '/../dashboard/includes/config.php';
require_once __DIR__ . '/../dashboard/includes/auth.php';
od9_dashboard_boot();

$discordId = (string)($_SESSION['discord_id'] ?? '');
if ($discordId === '') beacon_end();          // anonymous reader — nothing to attribute

// The lesson path the browser reported (?u=/curriculum/<tier>/<slug>.php). Validated
// hard: it feeds an idempotency key + a content_library URL match and is client-supplied.
$path = (string)($_GET['u'] ?? '');
if (!preg_match('#^/curriculum/[a-z0-9_\-/]+\.php$#i', $path)) beacon_end();

$url = 'https://offda9.com' . $path;          // the exact form content_library.url is keyed on

// Once per lesson per session — a refresh / scroll-reload / embed reload shouldn't
// re-beacon. (The bot's DAILY idempotency key is the real backstop; this just saves
// redundant requests.)
$seen = $_SESSION['lv_seen'] ?? [];
if (isset($seen[$url])) beacon_end();
$seen[$url] = 1;
$_SESSION['lv_seen'] = $seen;

$secret = getenv('MEMBER_ACTION_WEBHOOK_SECRET');
if (!$secret) beacon_end();                    // not wired (e.g. local XAMPP) — silent no-op

$day  = gmdate('Y-m-d');
$body = json_encode([
    'discord_id'      => $discordId,
    'action'          => 'lesson_view',
    'idempotency_key' => "lessonview:$url:$discordId:$day",
    'guild_id'        => defined('OD9_GUILD_ID') ? OD9_GUILD_ID : null,
    'payload'         => ['url' => $url],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$sig    = hash_hmac('sha256', $body, $secret);
$botUrl = getenv('BOT_INTERNAL_URL') ?: 'http://127.0.0.1:5000/member/action';

$ch = curl_init($botUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-OD9-Signature: ' . $sig],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 3,               // this is the beacon's own request, not the page render
    CURLOPT_CONNECTTIMEOUT => 1,
]);
@curl_exec($ch);                               // fire-and-forget; best-effort analytics
curl_close($ch);
beacon_end();
