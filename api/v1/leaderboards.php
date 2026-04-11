<?php
/**
 * OD9 API v1 - /leaderboards/{type} Endpoint
 *
 * GET /api/v1/leaderboards?type=credits-alltime - Get leaderboard data
 *
 * Types: credits-alltime, credits-monthly, streak-current, streak-best, achievements, think-tank
 */

require_once __DIR__ . '/bootstrap.php';

$type = $_GET['type'] ?? 'credits-alltime';
$limit = min((int)($_GET['limit'] ?? 25), 100);
$guildId = OD9_GUILD_ID ?? '1309609816934559785';

// Rate limit by IP for public endpoint
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
checkRateLimit("leaderboard_{$ip}", 30, 60);

$db = getBotDatabase();

try {
    $entries = [];
    $period = null;

    switch ($type) {
        case 'credits-alltime':
            $stmt = $db->prepare("
                SELECT user_id, username, current_tier, total_credits as value
                FROM users
                WHERE guild_id = ? AND total_credits > 0
                ORDER BY total_credits DESC
                LIMIT $limit
            ");
            $stmt->execute([$guildId]);
            $entries = $stmt->fetchAll();
            $label = 'credits';
            break;

        case 'credits-monthly':
            // Get credits earned this month
            $firstOfMonth = date('Y-m-01 00:00:00');
            $stmt = $db->prepare("
                SELECT u.user_id, u.username, u.current_tier,
                       COALESCE(SUM(ct.amount), 0) as value
                FROM users u
                LEFT JOIN credit_transactions ct ON u.user_id = ct.user_id
                    AND ct.guild_id = u.guild_id
                    AND ct.amount > 0
                    AND ct.timestamp >= ?
                WHERE u.guild_id = ?
                GROUP BY u.user_id
                HAVING value > 0
                ORDER BY value DESC
                LIMIT $limit
            ");
            $stmt->execute([$firstOfMonth, $guildId]);
            $entries = $stmt->fetchAll();
            $label = 'credits this month';
            $period = date('F Y');
            break;

        case 'streak-current':
            $stmt = $db->prepare("
                SELECT u.user_id, u.username, u.current_tier, us.current_streak as value
                FROM users u
                JOIN user_streaks us ON u.user_id = us.user_id AND u.guild_id = us.guild_id
                WHERE u.guild_id = ? AND us.current_streak > 0
                ORDER BY us.current_streak DESC
                LIMIT $limit
            ");
            $stmt->execute([$guildId]);
            $entries = $stmt->fetchAll();
            $label = 'day streak';
            break;

        case 'streak-best':
            $stmt = $db->prepare("
                SELECT u.user_id, u.username, u.current_tier, us.best_streak as value
                FROM users u
                JOIN user_streaks us ON u.user_id = us.user_id AND u.guild_id = us.guild_id
                WHERE u.guild_id = ? AND us.best_streak > 0
                ORDER BY us.best_streak DESC
                LIMIT $limit
            ");
            $stmt->execute([$guildId]);
            $entries = $stmt->fetchAll();
            $label = 'day best streak';
            break;

        case 'achievements':
            $stmt = $db->prepare("
                SELECT u.user_id, u.username, u.current_tier,
                       COALESCE(SUM(a.points), 0) as value
                FROM users u
                LEFT JOIN user_achievements ua ON u.user_id = ua.user_id AND u.guild_id = ua.guild_id
                LEFT JOIN achievements a ON ua.achievement_id = a.achievement_id
                WHERE u.guild_id = ? AND ua.earned_at IS NOT NULL
                GROUP BY u.user_id
                HAVING value > 0
                ORDER BY value DESC
                LIMIT $limit
            ");
            $stmt->execute([$guildId]);
            $entries = $stmt->fetchAll();
            $label = 'achievement points';
            break;

        case 'think-tank':
            $stmt = $db->prepare("
                SELECT u.user_id, u.username, u.current_tier,
                       COUNT(tta.id) as value
                FROM users u
                JOIN think_tank_attendance tta ON u.user_id = tta.user_id AND u.guild_id = tta.guild_id
                WHERE u.guild_id = ?
                GROUP BY u.user_id
                HAVING value > 0
                ORDER BY value DESC
                LIMIT $limit
            ");
            $stmt->execute([$guildId]);
            $entries = $stmt->fetchAll();
            $label = 'sessions attended';
            break;

        default:
            apiError('Invalid leaderboard type', 400);
    }

    // Format entries with rank
    $formattedEntries = [];
    $rank = 1;
    foreach ($entries as $entry) {
        $formattedEntries[] = [
            'rank' => $rank++,
            'discord_id' => $entry['user_id'],
            'username' => $entry['username'],
            'avatar' => getAvatarUrl($entry['user_id'], null),
            'tier' => ucfirst($entry['current_tier'] ?? 'observer'),
            'value' => (int)$entry['value'],
            'label' => $label
        ];
    }

    apiSuccess([
        'type' => $type,
        'period' => $period,
        'updated_at' => date('c'),
        'entries' => $formattedEntries
    ]);

} catch (PDOException $e) {
    error_log("API /leaderboards error: " . $e->getMessage());
    apiError('Database error', 500);
}
