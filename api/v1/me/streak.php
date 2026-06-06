<?php
/**
 * OD9 API v1 - /me/streak Endpoint
 *
 * GET /api/v1/me/streak - Get streak details and milestones
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$discordId = requireAuth();
$guildId = OD9_GUILD_ID ?? '1146833684952006769';

checkRateLimit("streak_{$discordId}", 30, 60);

$db = getBotDatabase();

try {
    // Get streak data
    $stmt = $db->prepare("
        SELECT current_streak, best_streak, streak_type, last_activity, 
               streak_start, streak_broken_count
        FROM user_streaks
        WHERE user_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $streak = $stmt->fetch();

    // Milestone definitions (from config.py)
    $milestones = [
        ['days' => 3, 'label' => 'Getting Started', 'rarity' => 'common', 'multiplier' => 1.1],
        ['days' => 7, 'label' => 'Week Warrior', 'rarity' => 'common', 'multiplier' => 1.5],
        ['days' => 14, 'label' => 'Fortnight Fighter', 'rarity' => 'uncommon', 'multiplier' => 2.0],
        ['days' => 30, 'label' => 'Monthly Master', 'rarity' => 'rare', 'multiplier' => 3.0],
        ['days' => 60, 'label' => 'Diamond Dedication', 'rarity' => 'epic', 'multiplier' => 4.0],
        ['days' => 90, 'label' => 'Legendary Loyalty', 'rarity' => 'legendary', 'multiplier' => 5.0],
        ['days' => 180, 'label' => 'Half Year Hero', 'rarity' => 'mythic', 'multiplier' => 7.5],
        ['days' => 365, 'label' => 'Year of Excellence', 'rarity' => 'mythic', 'multiplier' => 10.0]
    ];

    $currentStreak = (int)($streak['current_streak'] ?? 0);
    $bestStreak = (int)($streak['best_streak'] ?? 0);

    // Determine which milestones are achieved
    $formattedMilestones = array_map(function($m) use ($currentStreak, $bestStreak) {
        $achieved = $bestStreak >= $m['days'];
        return [
            'days' => $m['days'],
            'label' => $m['label'],
            'rarity' => $m['rarity'],
            'multiplier' => $m['multiplier'],
            'achieved' => $achieved,
            'achieved_at' => $achieved ? null : null // Would need to track this separately
        ];
    }, $milestones);

    // Calculate streak expiry (24h from last activity)
    $expiresAt = null;
    if ($streak && $streak['last_activity']) {
        $lastActivity = new DateTime($streak['last_activity']);
        $lastActivity->modify('+24 hours');
        $expiresAt = $lastActivity->format('c');
    }

    // Count completed streaks (streaks that hit any milestone)
    $completedStreaks = 0;
    foreach ($milestones as $m) {
        if ($bestStreak >= $m['days']) {
            $completedStreaks++;
        }
    }

    $response = [
        'current' => [
            'count' => $currentStreak,
            'type' => $streak['streak_type'] ?? 'daily_activity',
            'started_at' => formatTimestamp($streak['streak_start'] ?? null),
            'last_activity' => formatTimestamp($streak['last_activity'] ?? null),
            'expires_at' => $expiresAt
        ],
        'best' => [
            'count' => $bestStreak,
            'achieved_at' => null // Would need to track this
        ],
        'milestones' => $formattedMilestones,
        'total_streaks_completed' => $completedStreaks,
        'times_broken' => (int)($streak['streak_broken_count'] ?? 0)
    ];

    apiSuccess($response);

} catch (PDOException $e) {
    error_log("API /me/streak error: " . $e->getMessage());
    apiError('Database error', 500);
}
