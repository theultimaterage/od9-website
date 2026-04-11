<?php
/**
 * OD9 API v1 - /me/achievements Endpoint
 *
 * GET /api/v1/me/achievements - Get achievements
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$discordId = requireAuth();
$guildId = OD9_GUILD_ID ?? '1309609816934559785';

checkRateLimit("achievements_{$discordId}", 30, 60);

$status = $_GET['status'] ?? 'all'; // earned, in_progress, locked, all
$category = $_GET['category'] ?? null;

$db = getBotDatabase();

try {
    // Get earned achievements
    $stmt = $db->prepare("
        SELECT ua.achievement_id, ua.earned_at, ua.progress, ua.target,
               a.name, a.description, a.category, a.points, a.rarity, a.icon
        FROM user_achievements ua
        LEFT JOIN achievements a ON ua.achievement_id = a.achievement_id
        WHERE ua.user_id = ? AND ua.guild_id = ?
        ORDER BY ua.earned_at DESC
    ");
    $stmt->execute([$discordId, $guildId]);
    $userAchievements = $stmt->fetchAll();

    // Get all achievements for comparison
    $stmt = $db->prepare("SELECT * FROM achievements WHERE guild_id = ? OR guild_id = '0'");
    $stmt->execute([$guildId]);
    $allAchievements = $stmt->fetchAll();

    // Build achievement map
    $earnedMap = [];
    foreach ($userAchievements as $ua) {
        $earnedMap[$ua['achievement_id']] = $ua;
    }

    // Calculate total points
    $totalPoints = 0;
    $earnedCount = 0;
    $achievements = [];

    foreach ($allAchievements as $a) {
        $id = $a['achievement_id'];
        $earned = isset($earnedMap[$id]) && $earnedMap[$id]['earned_at'];
        $inProgress = isset($earnedMap[$id]) && !$earnedMap[$id]['earned_at'] && ($earnedMap[$id]['progress'] ?? 0) > 0;

        // Filter by status
        if ($status === 'earned' && !$earned) continue;
        if ($status === 'in_progress' && !$inProgress) continue;
        if ($status === 'locked' && ($earned || $inProgress)) continue;

        // Filter by category
        if ($category && strtolower($a['category'] ?? '') !== strtolower($category)) continue;

        $achievementData = [
            'id' => $id,
            'name' => $a['name'] ?? 'Unknown Achievement',
            'description' => $a['description'] ?? '',
            'category' => $a['category'] ?? 'general',
            'points' => (int)($a['points'] ?? 10),
            'rarity' => $a['rarity'] ?? 'common',
            'icon' => $a['icon'] ?? '🏆'
        ];

        if ($earned) {
            $achievementData['status'] = 'earned';
            $achievementData['earned_at'] = formatTimestamp($earnedMap[$id]['earned_at']);
            $totalPoints += (int)($a['points'] ?? 10);
            $earnedCount++;
        } elseif ($inProgress) {
            $achievementData['status'] = 'in_progress';
            $achievementData['progress'] = [
                'current' => (int)($earnedMap[$id]['progress'] ?? 0),
                'target' => (int)($earnedMap[$id]['target'] ?? $a['target'] ?? 1)
            ];
            $achievementData['earned_at'] = null;
        } else {
            $achievementData['status'] = 'locked';
            $achievementData['earned_at'] = null;
        }

        $achievements[] = $achievementData;
    }

    apiSuccess([
        'total_points' => $totalPoints,
        'earned_count' => $earnedCount,
        'total_count' => count($allAchievements),
        'achievements' => $achievements
    ]);

} catch (PDOException $e) {
    error_log("API /me/achievements error: " . $e->getMessage());
    apiError('Database error', 500);
}
