<?php
/**
 * OD9 Dashboard Database Functions
 * 
 * Handles all database operations for the progression dashboard.
 * Uses PDO with prepared statements for security.
 */

// Load database config
$configPath = __DIR__ . '/../../../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../../config/database.php';
}
require_once $configPath;

/**
 * Get or create a dashboard user by Discord ID
 */
function getDashboardUserByDiscordId(string $discordId): ?array {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT * FROM dashboard_users WHERE discord_id = ?");
    $stmt->execute([$discordId]);
    return $stmt->fetch() ?: null;
}

/**
 * Create or update a dashboard user
 */
function upsertDashboardUser(array $data): int {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        INSERT INTO dashboard_users (discord_id, discord_username, discord_discriminator, discord_avatar, display_name, last_synced_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            discord_username = VALUES(discord_username),
            discord_discriminator = VALUES(discord_discriminator),
            discord_avatar = VALUES(discord_avatar),
            display_name = VALUES(display_name),
            last_synced_at = NOW(),
            updated_at = NOW()
    ");
    
    $stmt->execute([
        $data['discord_id'],
        $data['username'] ?? null,
        $data['discriminator'] ?? null,
        $data['avatar'] ?? null,
        $data['display_name'] ?? $data['username'] ?? null
    ]);
    
    // Get the user ID (either inserted or existing)
    $user = getDashboardUserByDiscordId($data['discord_id']);
    return $user['id'];
}

/**
 * Update progression data for a user
 */
function updateProgression(int $userId, string $guildId, array $data): bool {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        INSERT INTO dashboard_progression 
            (user_id, guild_id, total_credits, tier, tier_progress_pct, 
             dim_analytical, dim_creative, dim_collaborative, dim_practical, dim_leadership)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            total_credits = VALUES(total_credits),
            tier = VALUES(tier),
            tier_progress_pct = VALUES(tier_progress_pct),
            dim_analytical = VALUES(dim_analytical),
            dim_creative = VALUES(dim_creative),
            dim_collaborative = VALUES(dim_collaborative),
            dim_practical = VALUES(dim_practical),
            dim_leadership = VALUES(dim_leadership),
            synced_at = NOW()
    ");
    
    return $stmt->execute([
        $userId,
        $guildId,
        $data['total_credits'] ?? 0,
        $data['tier'] ?? 'OBSERVER',
        $data['tier_progress_pct'] ?? 0,
        $data['dimensions']['analytical'] ?? 0,
        $data['dimensions']['creative'] ?? 0,
        $data['dimensions']['collaborative'] ?? 0,
        $data['dimensions']['practical'] ?? 0,
        $data['dimensions']['leadership'] ?? 0
    ]);
}

/**
 * Record an achievement
 */
function recordAchievement(int $userId, string $guildId, array $achievement): bool {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        INSERT IGNORE INTO dashboard_achievements 
            (user_id, guild_id, achievement_id, achievement_name, achievement_desc, rarity, earned_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $guildId,
        $achievement['id'],
        $achievement['name'] ?? null,
        $achievement['description'] ?? null,
        $achievement['rarity'] ?? 'common',
        $achievement['earned_at'] ?? date('Y-m-d H:i:s')
    ]);
}

/**
 * Record an activity event
 */
function recordActivity(int $userId, string $guildId, array $activity): bool {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        INSERT INTO dashboard_activity 
            (user_id, guild_id, action_type, description, credits_earned, dimension_affected, metadata, occurred_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $guildId,
        $activity['action_type'],
        $activity['description'] ?? null,
        $activity['credits_earned'] ?? 0,
        $activity['dimension'] ?? null,
        isset($activity['metadata']) ? json_encode($activity['metadata']) : null,
        $activity['occurred_at'] ?? date('Y-m-d H:i:s')
    ]);
}

/**
 * Log a sync event
 */
function logSyncEvent(string $eventType, ?string $discordId, array $payload, string $status = 'success', ?string $error = null): void {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        INSERT INTO dashboard_sync_log (event_type, discord_id, payload, status, error_message)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $eventType,
        $discordId,
        json_encode($payload),
        $status,
        $error
    ]);
}

/**
 * Get progression data for a user
 */
function getProgression(int $userId, string $guildId = '0'): ?array {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT * FROM dashboard_progression WHERE user_id = ? AND guild_id = ?");
    $stmt->execute([$userId, $guildId]);
    return $stmt->fetch() ?: null;
}

/**
 * Get achievements for a user
 */
function getAchievements(int $userId, string $guildId = '0', int $limit = 50): array {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT * FROM dashboard_achievements 
        WHERE user_id = ? AND guild_id = ?
        ORDER BY earned_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute([$userId, $guildId]);
    return $stmt->fetchAll();
}

/**
 * Get recent activity for a user
 */
function getRecentActivity(int $userId, string $guildId = '0', int $limit = 20): array {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT * FROM dashboard_activity 
        WHERE user_id = ? AND guild_id = ?
        ORDER BY occurred_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute([$userId, $guildId]);
    return $stmt->fetchAll();
}

/**
 * Prune old activity records (keep latest 100 per user/guild)
 */
function pruneOldActivity(int $userId, string $guildId, int $keepCount = 100): int {
    $db = getDatabaseConnection();
    
    // Get the ID threshold
    $stmt = $db->prepare("
        SELECT id FROM dashboard_activity 
        WHERE user_id = ? AND guild_id = ?
        ORDER BY occurred_at DESC
        LIMIT 1 OFFSET ?
    ");
    $stmt->execute([$userId, $guildId, $keepCount]);
    $threshold = $stmt->fetchColumn();
    
    if (!$threshold) {
        return 0;
    }
    
    // Delete older records
    $stmt = $db->prepare("
        DELETE FROM dashboard_activity 
        WHERE user_id = ? AND guild_id = ? AND id < ?
    ");
    $stmt->execute([$userId, $guildId, $threshold]);
    
    return $stmt->rowCount();
}

/**
 * Toggle public profile visibility
 */
function setPublicProfile(string $discordId, bool $isPublic): bool {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("UPDATE dashboard_users SET public_profile = ? WHERE discord_id = ?");
    return $stmt->execute([$isPublic ? 1 : 0, $discordId]);
}

/**
 * Check if a user's profile is public
 */
function isProfilePublic(string $discordId): bool {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT public_profile FROM dashboard_users WHERE discord_id = ?");
    $stmt->execute([$discordId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Get user by Discord ID with public profile info
 */
function getPublicUserByDiscordId(string $discordId): ?array {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT id, discord_id, discord_username, discord_avatar, display_name, public_profile 
        FROM dashboard_users 
        WHERE discord_id = ?
    ");
    $stmt->execute([$discordId]);
    return $stmt->fetch() ?: null;
}

/**
 * Get full profile data for public viewing (only if profile is public)
 */
function getPublicProfileData(string $discordId, string $guildId = null): ?array {
    $guildId = $guildId ?? (defined('OD9_GUILD_ID') ? OD9_GUILD_ID : '1309609816934559785');
    
    // Get user (must be public)
    $user = getPublicUserByDiscordId($discordId);
    if (!$user || !$user['public_profile']) {
        return null;
    }
    
    // Get progression
    $progression = getProgression($user['id'], $guildId);
    
    // Get achievements
    $achievements = getAchievements($user['id'], $guildId, 20);
    
    // Get recent activity (limited for public view)
    $activity = getRecentActivity($user['id'], $guildId, 10);
    
    return [
        'user' => $user,
        'progression' => $progression,
        'achievements' => $achievements,
        'activity' => $activity
    ];
}
