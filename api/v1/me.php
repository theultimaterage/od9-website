<?php
/**
 * OD9 API v1 - /me Endpoint
 * 
 * GET /api/v1/me - Get current authenticated user's full profile
 */

require_once __DIR__ . '/bootstrap.php';

// Require authentication
$discordId = requireAuth();
$guildId = OD9_GUILD_ID ?? '1309609816934559785';

// Rate limit
checkRateLimit("me_{$discordId}", 60, 60); // 60 requests per minute

$db = getBotDatabase();

try {
    // Get user from bot database
    $stmt = $db->prepare("
        SELECT user_id, username, current_tier, total_credits, join_date, 
               observer_completed, guild_id
        FROM users 
        WHERE user_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $user = $stmt->fetch();

    if (!$user) {
        apiError('User not found in OD9', 404);
    }

    // Get tier info
    $tierName = ucfirst($user['current_tier'] ?? 'observer');
    $tierColor = getTierColor($tierName);
    $tierRank = match(strtolower($tierName)) {
        'observer' => 1,
        'theorist' => 2,
        'architect' => 3,
        'pioneer' => 4,
        'benefactor' => 5,
        default => 1
    };

    // Get credit stats
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) as lifetime_earned,
            COALESCE(SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END), 0) as lifetime_spent
        FROM credit_transactions 
        WHERE user_id = ? AND guild_id = ?
    ");
    $stmt->execute([$discordId, $guildId]);
    $creditStats = $stmt->fetch();

    // Get streak info
    $stmt = $db->prepare("
        SELECT current_streak, best_streak, streak_type, last_activity, streak_start
        FROM user_streaks 
        WHERE user_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $streak = $stmt->fetch();

    // Get achievement count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM user_achievements 
        WHERE user_id = ? AND guild_id = ?
    ");
    $stmt->execute([$discordId, $guildId]);
    $achievementCount = $stmt->fetchColumn() ?: 0;

    // Get Think Tank session count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM think_tank_attendance 
        WHERE user_id = ? AND guild_id = ?
    ");
    $stmt->execute([$discordId, $guildId]);
    $thinkTankCount = $stmt->fetchColumn() ?: 0;

    // Get user settings (privacy)
    $stmt = $db->prepare("
        SELECT profile_public, show_credits, show_streak, show_pathways, 
               show_achievements, show_tier_history
        FROM user_settings 
        WHERE discord_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $settings = $stmt->fetch();

    // Default settings if none exist
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

    // Build response
    $response = [
        'discord_id' => $discordId,
        'username' => $user['username'],
        'avatar' => $_SESSION['discord_avatar'] ?? getAvatarUrl($discordId, null),
        'tier' => [
            'id' => $tierRank,
            'name' => $tierName,
            'color' => $tierColor,
            'rank' => $tierRank
        ],
        'credits' => [
            'balance' => (int)($user['total_credits'] ?? 0),
            'lifetime_earned' => (int)($creditStats['lifetime_earned'] ?? 0),
            'lifetime_spent' => (int)($creditStats['lifetime_spent'] ?? 0)
        ],
        'streak' => $streak ? [
            'current' => (int)($streak['current_streak'] ?? 0),
            'type' => $streak['streak_type'] ?? 'daily_activity',
            'best' => (int)($streak['best_streak'] ?? 0),
            'last_activity' => formatTimestamp($streak['last_activity'])
        ] : [
            'current' => 0,
            'type' => 'daily_activity',
            'best' => 0,
            'last_activity' => null
        ],
        'member_since' => formatTimestamp($user['join_date']),
        'achievements_count' => (int)$achievementCount,
        'think_tank_sessions' => (int)$thinkTankCount,
        'privacy' => [
            'profile_public' => (bool)$settings['profile_public'],
            'show_credits' => (bool)$settings['show_credits'],
            'show_streak' => (bool)$settings['show_streak'],
            'show_pathways' => (bool)$settings['show_pathways'],
            'show_achievements' => (bool)$settings['show_achievements'],
            'show_tier_history' => (bool)$settings['show_tier_history']
        ]
    ];

    apiSuccess($response);

} catch (PDOException $e) {
    error_log("API /me error: " . $e->getMessage());
    apiError('Database error', 500);
}
