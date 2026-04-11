<?php
/**
 * OD9 API v1 - /me/settings Endpoint
 *
 * GET /api/v1/me/settings - Get user settings
 * PATCH /api/v1/me/settings - Update user settings
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$discordId = requireAuth();
$guildId = OD9_GUILD_ID ?? '1309609816934559785';

checkRateLimit("settings_{$discordId}", 20, 60);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    handleGetSettings($discordId, $guildId);
} elseif ($method === 'PATCH') {
    handlePatchSettings($discordId, $guildId);
} else {
    apiError('Method not allowed', 405);
}

function handleGetSettings(string $discordId, string $guildId): void {
    $db = getBotDatabase();

    try {
        $stmt = $db->prepare("
            SELECT profile_public, show_credits, show_streak, show_pathways,
                   show_achievements, show_tier_history
            FROM user_settings
            WHERE discord_id = ? AND guild_id = ?
            LIMIT 1
        ");
        $stmt->execute([$discordId, $guildId]);
        $settings = $stmt->fetch();

        // Return defaults if no settings exist
        if (!$settings) {
            $settings = [
                'profile_public' => 1,
                'show_credits' => 0,
                'show_streak' => 1,
                'show_pathways' => 0,
                'show_achievements' => 1,
                'show_tier_history' => 0
            ];
        }

        apiSuccess([
            'privacy' => [
                'profile_public' => (bool)$settings['profile_public'],
                'show_credits' => (bool)$settings['show_credits'],
                'show_streak' => (bool)$settings['show_streak'],
                'show_pathways' => (bool)$settings['show_pathways'],
                'show_achievements' => (bool)$settings['show_achievements'],
                'show_tier_history' => (bool)$settings['show_tier_history']
            ]
        ]);

    } catch (PDOException $e) {
        error_log("API GET /me/settings error: " . $e->getMessage());
        apiError('Database error', 500);
    }
}

function handlePatchSettings(string $discordId, string $guildId): void {
    $db = getBotDatabaseWriteable();
    $body = getRequestBody();

    // Validate input
    if (!isset($body['privacy']) || !is_array($body['privacy'])) {
        apiError('Invalid request body. Expected: {"privacy": {...}}', 400);
    }

    $privacy = $body['privacy'];
    $allowedKeys = ['profile_public', 'show_credits', 'show_streak', 'show_pathways', 'show_achievements', 'show_tier_history'];

    // Filter to only allowed keys and convert to int (0/1)
    $updates = [];
    foreach ($allowedKeys as $key) {
        if (isset($privacy[$key])) {
            $updates[$key] = $privacy[$key] ? 1 : 0;
        }
    }

    if (empty($updates)) {
        apiError('No valid settings to update', 400);
    }

    try {
        // Check if settings exist
        $stmt = $db->prepare("SELECT 1 FROM user_settings WHERE discord_id = ? AND guild_id = ?");
        $stmt->execute([$discordId, $guildId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Update existing
            $setClauses = [];
            $params = [];
            foreach ($updates as $key => $value) {
                $setClauses[] = "$key = ?";
                $params[] = $value;
            }
            $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $discordId;
            $params[] = $guildId;

            $sql = "UPDATE user_settings SET " . implode(', ', $setClauses) . " WHERE discord_id = ? AND guild_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            // Insert new with defaults
            $defaults = [
                'profile_public' => 1,
                'show_credits' => 0,
                'show_streak' => 1,
                'show_pathways' => 0,
                'show_achievements' => 1,
                'show_tier_history' => 0
            ];
            $merged = array_merge($defaults, $updates);

            $stmt = $db->prepare("
                INSERT INTO user_settings
                (discord_id, guild_id, profile_public, show_credits, show_streak, show_pathways, show_achievements, show_tier_history)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $discordId,
                $guildId,
                $merged['profile_public'],
                $merged['show_credits'],
                $merged['show_streak'],
                $merged['show_pathways'],
                $merged['show_achievements'],
                $merged['show_tier_history']
            ]);
        }

        // Return updated settings
        $stmt = $db->prepare("
            SELECT profile_public, show_credits, show_streak, show_pathways,
                   show_achievements, show_tier_history
            FROM user_settings
            WHERE discord_id = ? AND guild_id = ?
        ");
        $stmt->execute([$discordId, $guildId]);
        $settings = $stmt->fetch();

        apiSuccess([
            'success' => true,
            'settings' => [
                'privacy' => [
                    'profile_public' => (bool)$settings['profile_public'],
                    'show_credits' => (bool)$settings['show_credits'],
                    'show_streak' => (bool)$settings['show_streak'],
                    'show_pathways' => (bool)$settings['show_pathways'],
                    'show_achievements' => (bool)$settings['show_achievements'],
                    'show_tier_history' => (bool)$settings['show_tier_history']
                ]
            ]
        ]);

    } catch (PDOException $e) {
        error_log("API PATCH /me/settings error: " . $e->getMessage());
        apiError('Database error', 500);
    }
}
