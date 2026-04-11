<?php
/**
 * OD9 Dashboard Webhook Receiver
 *
 * Receives progression updates from the Discord bot.
 * Endpoint: POST /dashboard/api/sync.php
 *
 * Security:
 * - Requires shared secret in X-Sync-Secret header
 * - Rate limited by IP
 * - Logs all events for audit
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Load configuration and database functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Get sync secret from environment or config
$syncSecret = getenv('OD9_WEB_SYNC_SECRET') ?: 'dev-sync-secret-change-in-production';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Sync-Secret');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verify secret
$providedSecret = $_SERVER['HTTP_X_SYNC_SECRET'] ?? '';
if (!hash_equals($syncSecret, $providedSecret)) {
    logSyncEvent('auth_failure', null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'], 'error', 'Invalid sync secret');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Rate limiting: max 100 requests per minute per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = sys_get_temp_dir() . '/od9_sync_' . md5($ip) . '.json';
$now = time();

if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    $rateData = array_filter($rateData, fn($ts) => ($now - $ts) < 60);
    if (count($rateData) >= 100) {
        logSyncEvent('rate_limit', null, ['ip' => $ip], 'error', 'Rate limit exceeded');
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
        exit;
    }
    $rateData[] = $now;
} else {
    $rateData = [$now];
}
file_put_contents($rateLimitFile, json_encode($rateData));

// Parse request body
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!$payload || !isset($payload['event'])) {
    logSyncEvent('invalid_payload', null, ['raw' => substr($rawBody, 0, 500)], 'error', 'Missing event type');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$event = $payload['event'];
$discordId = $payload['discord_id'] ?? null;
$guildId = $payload['guild_id'] ?? '0';
$data = $payload['data'] ?? [];

try {
    switch ($event) {
        case 'progression_update':
            // Full progression sync
            if (!$discordId) {
                throw new Exception('Missing discord_id');
            }

            $userId = upsertDashboardUser([
                'discord_id' => $discordId,
                'username' => $data['username'] ?? null,
                'discriminator' => $data['discriminator'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'display_name' => $data['display_name'] ?? null
            ]);

            updateProgression($userId, $guildId, $data);

            logSyncEvent($event, $discordId, $payload, 'success');
            echo json_encode(['success' => true, 'user_id' => $userId]);
            break;

        case 'achievement_earned':
            if (!$discordId || !isset($data['achievement'])) {
                throw new Exception('Missing discord_id or achievement data');
            }

            $user = getDashboardUserByDiscordId($discordId);
            if (!$user) {
                throw new Exception('User not found - sync progression first');
            }

            recordAchievement($user['id'], $guildId, $data['achievement']);

            logSyncEvent($event, $discordId, $payload, 'success');
            echo json_encode(['success' => true]);
            break;

        case 'activity':
            if (!$discordId || !isset($data['action_type'])) {
                throw new Exception('Missing discord_id or action_type');
            }

            $user = getDashboardUserByDiscordId($discordId);
            if (!$user) {
                throw new Exception('User not found - sync progression first');
            }

            recordActivity($user['id'], $guildId, $data);

            // Prune old activity to prevent unbounded growth
            pruneOldActivity($user['id'], $guildId, 100);

            logSyncEvent($event, $discordId, $payload, 'success');
            echo json_encode(['success' => true]);
            break;

        case 'tier_up':
            if (!$discordId) {
                throw new Exception('Missing discord_id');
            }

            $user = getDashboardUserByDiscordId($discordId);
            if (!$user) {
                throw new Exception('User not found - sync progression first');
            }

            // Update progression with new tier
            updateProgression($user['id'], $guildId, $data);

            // Record tier-up as activity
            recordActivity($user['id'], $guildId, [
                'action_type' => 'tier_advancement',
                'description' => "Advanced to {$data['tier']}",
                'credits_earned' => 0,
                'metadata' => [
                    'old_tier' => $data['old_tier'] ?? null,
                    'new_tier' => $data['tier']
                ]
            ]);

            logSyncEvent($event, $discordId, $payload, 'success');
            echo json_encode(['success' => true]);
            break;

        case 'bulk_sync':
            // Sync multiple users at once (for initial data load)
            if (!isset($data['users']) || !is_array($data['users'])) {
                throw new Exception('Missing users array');
            }

            $synced = 0;
            foreach ($data['users'] as $userData) {
                if (!isset($userData['discord_id'])) continue;

                $userId = upsertDashboardUser([
                    'discord_id' => $userData['discord_id'],
                    'username' => $userData['username'] ?? null,
                    'display_name' => $userData['display_name'] ?? null
                ]);

                if (isset($userData['progression'])) {
                    updateProgression($userId, $guildId, $userData['progression']);
                }

                $synced++;
            }

            logSyncEvent($event, null, ['count' => $synced], 'success');
            echo json_encode(['success' => true, 'synced' => $synced]);
            break;

        case 'ping':
            // Health check endpoint
            logSyncEvent($event, null, [], 'success');
            echo json_encode(['success' => true, 'message' => 'pong', 'timestamp' => date('c')]);
            break;

        default:
            logSyncEvent($event, $discordId, $payload, 'error', 'Unknown event type');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown event type']);
    }

} catch (Exception $e) {
    logSyncEvent($event, $discordId, $payload, 'error', $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
