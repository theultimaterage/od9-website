<?php
/**
 * OD9 API v1 - /member/{discord_id} Endpoint
 *
 * GET /api/v1/member?id=123456789 - Get public profile for a member
 */

require_once __DIR__ . '/bootstrap.php';

$discordId = $_GET['id'] ?? null;
$guildId = OD9_GUILD_ID ?? '1146833684952006769';

if (!$discordId || !preg_match('/^\d{17,19}$/', $discordId)) {
    apiError('Invalid or missing Discord ID', 400);
}

// Rate limit by IP for public endpoint
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
checkRateLimit("member_{$ip}", 60, 60);

$db = getBotDatabase();

try {
    // Get user
    $stmt = $db->prepare("
        SELECT user_id, username, current_tier, total_credits, join_date
        FROM users
        WHERE user_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $user = $stmt->fetch();

    if (!$user) {
        apiError('Member not found', 404);
    }

    // Get privacy settings
    $stmt = $db->prepare("
        SELECT profile_public, show_credits, show_streak, show_achievements
        FROM user_settings
        WHERE discord_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $settings = $stmt->fetch();

    // Default to public if no settings
    $isPublic = $settings ? (bool)$settings['profile_public'] : true;

    if (!$isPublic) {
        apiError('This profile is private', 404, 'PROFILE_PRIVATE');
    }

    $tierName = ucfirst($user['current_tier'] ?? 'observer');

    $response = [
        'discord_id' => $user['user_id'],
        'username' => $user['username'],
        'avatar' => getAvatarUrl($user['user_id'], null),
        'tier' => [
            'name' => $tierName,
            'color' => getTierColor($tierName)
        ],
        'member_since' => formatTimestamp($user['join_date'])
    ];

    // Show credits if allowed
    $showCredits = $settings ? (bool)$settings['show_credits'] : false;
    $response['credits'] = $showCredits ? (int)$user['total_credits'] : null;

    // Show streak if allowed
    $showStreak = $settings ? (bool)$settings['show_streak'] : true;
    if ($showStreak) {
        $stmt = $db->prepare("
            SELECT current_streak FROM user_streaks
            WHERE user_id = ? AND guild_id = ?
            LIMIT 1
        ");
        $stmt->execute([$discordId, $guildId]);
        $streak = $stmt->fetch();
        $response['streak'] = ['current' => (int)($streak['current_streak'] ?? 0)];
    } else {
        $response['streak'] = null;
    }

    // Show achievements if allowed
    $showAchievements = $settings ? (bool)$settings['show_achievements'] : true;
    if ($showAchievements) {
        // Get achievement count
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM user_achievements
            WHERE user_id = ? AND guild_id = ? AND earned_at IS NOT NULL
        ");
        $stmt->execute([$discordId, $guildId]);
        $achievementCount = (int)$stmt->fetchColumn();
        $response['achievements_count'] = $achievementCount;

        // Get top 5 rarest achievements
        $stmt = $db->prepare("
            SELECT a.achievement_id, a.name, a.icon, a.rarity
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.achievement_id
            WHERE ua.user_id = ? AND ua.guild_id = ? AND ua.earned_at IS NOT NULL
            ORDER BY CASE a.rarity
                WHEN 'mythic' THEN 1
                WHEN 'legendary' THEN 2
                WHEN 'epic' THEN 3
                WHEN 'rare' THEN 4
                WHEN 'uncommon' THEN 5
                ELSE 6
            END
            LIMIT 5
        ");
        $stmt->execute([$discordId, $guildId]);
        $topAchievements = $stmt->fetchAll();

        $response['public_achievements'] = array_map(function($a) {
            return [
                'id' => $a['achievement_id'],
                'name' => $a['name'],
                'icon' => $a['icon'] ?? '🏆',
                'rarity' => $a['rarity'] ?? 'common'
            ];
        }, $topAchievements);
    } else {
        $response['achievements_count'] = null;
        $response['public_achievements'] = [];
    }

    apiSuccess($response);

} catch (PDOException $e) {
    error_log("API /member error: " . $e->getMessage());
    apiError('Database error', 500);
}
