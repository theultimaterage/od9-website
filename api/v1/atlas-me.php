<?php
/**
 * atlas-me.php — your constellation (2026-09-05, the ninth spectacle move).
 *
 * The Atlas asks this once per visit. Signed-in members (the dashboard's
 * Discord session; cookie path "/" so the map shares it) get the chapters
 * they have completed — every APPROVED content_completion row whose content
 * is a chapter's canon lesson — plus their tier and credits, so the map can
 * light those stars as theirs. Anonymous visitors get {signed_in:false} and
 * the door: a normal answer, never a 401, because most visitors are anonymous.
 *
 * Reads the bot's SQLite read-only through the same seam as /api/v1/me.
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
header('Cache-Control: no-store');

const ATLAS_LOGIN = '/dashboard/auth/discord.php?return=/atlas';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 604800,
        'path'     => '/',
        'secure'   => !str_contains(__DIR__, 'xampp'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
$discordId = (string)($_SESSION['discord_id'] ?? '');
if ($discordId === '' || !preg_match('/^\d{5,25}$/', $discordId)) {
    apiSuccess(['signed_in' => false, 'login' => ATLAS_LOGIN]);
}
checkRateLimit("atlas_me_{$discordId}", 60, 60);

$mapPath = __DIR__ . '/../../data/manifesto-map.json';
$mapRaw  = is_readable($mapPath) ? file_get_contents($mapPath) : false;
$map     = $mapRaw !== false ? json_decode($mapRaw, true) : null;
if (!is_array($map) || empty($map['nodes'])) {
    error_log('atlas-me: manifesto-map.json unreadable or empty at ' . $mapPath);
    apiError('The map is unavailable', 500);
}

try {
    $db = getBotDatabase();
    $guildId = defined('OD9_GUILD_ID') ? OD9_GUILD_ID : '1146833684952006769';

    $st = $db->prepare("SELECT username, current_tier, total_credits FROM users WHERE user_id = ? AND guild_id = ? LIMIT 1");
    $st->execute([$discordId, $guildId]);
    $user = $st->fetch();

    $st = $db->prepare("SELECT content_id, MIN(completed_date) AS at FROM content_completion
                        WHERE user_id = ? AND review_status = 'approved' GROUP BY content_id");
    $st->execute([$discordId]);
    $done = [];
    foreach ($st->fetchAll() as $row) {
        $done[(int)$row['content_id']] = (string)$row['at'];
    }
} catch (PDOException $e) {
    error_log('atlas-me: ' . $e->getMessage());
    apiError('The ledger is unavailable', 500);
}

$read = [];
$canonTotal = 0;
foreach ($map['nodes'] as $n) {
    if (($n['state'] ?? '') === 'canon') {
        $canonTotal++;
    }
    foreach ($n['canon'] ?? [] as $c) {
        $cid = (int)($c['content_id'] ?? 0);
        if ($cid && isset($done[$cid])) {
            $read[] = ['id' => (string)$n['id'], 'at' => substr($done[$cid], 0, 10)];
            break;
        }
    }
}

apiSuccess([
    'signed_in'   => true,
    'name'        => (string)($_SESSION['discord_global_name'] ?? $_SESSION['discord_username'] ?? $user['username'] ?? 'you'),
    'member'      => $user !== false,
    'tier'        => (string)($user['current_tier'] ?? 'observer'),
    'credits'     => (int)($user['total_credits'] ?? 0),
    'read'        => $read,
    'read_total'  => count($read),
    'canon_total' => $canonTotal,
]);
