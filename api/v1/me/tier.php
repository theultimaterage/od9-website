<?php
/**
 * OD9 API v1 - /me/tier Endpoint
 *
 * GET /api/v1/me/tier - Get tier status and history
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$discordId = requireAuth();
$guildId = OD9_GUILD_ID ?? '1146833684952006769';

checkRateLimit("tier_{$discordId}", 30, 60);

$db = getBotDatabase();

try {
    // Get current tier
    $stmt = $db->prepare("
        SELECT current_tier, total_credits, join_date, observer_completed
        FROM users
        WHERE user_id = ? AND guild_id = ?
        LIMIT 1
    ");
    $stmt->execute([$discordId, $guildId]);
    $user = $stmt->fetch();

    if (!$user) {
        apiError('User not found', 404);
    }

    $tierName = ucfirst($user['current_tier'] ?? 'observer');
    $tierRank = match(strtolower($tierName)) {
        'observer' => 1,
        'theorist' => 2,
        'architect' => 3,
        'pioneer' => 4,
        'benefactor' => 5,
        default => 1
    };

    // Tier benefits mapping
    $tierBenefits = [
        'Observer' => [
            'Access to general channels',
            'Facilitated Discussion Think Tank format',
            '50 credits per Think Tank session',
            'Basic progression tracking'
        ],
        'Theorist' => [
            'Access to Theorist-only channels',
            'Collaborative Analysis Think Tank format',
            '50 credits per Think Tank session',
            'Participation in discussions',
            'Recommendation access'
        ],
        'Architect' => [
            'Access to Architect-only channels',
            'Design Workshop Think Tank format',
            '60 credits per Think Tank session',
            'Create learning pathways',
            'Mentorship eligibility'
        ],
        'Pioneer' => [
            'Access to Pioneer-only channels',
            'Strategic Planning Think Tank format',
            '75 credits per Think Tank session',
            'Priority mentorship matching',
            'Suggest QotD questions'
        ],
        'Benefactor' => [
            'Access to all channels',
            'Multi-Year Planning Think Tank format',
            '100 credits per Think Tank session',
            'Create Think Tank sessions',
            'Full governance rights'
        ]
    ];

    // Next tier info
    $nextTierMap = [
        1 => ['id' => 2, 'name' => 'Theorist', 'requirements' => 'Complete Observer onboarding and consistent participation'],
        2 => ['id' => 3, 'name' => 'Architect', 'requirements' => 'Demonstrate theoretical understanding and active discussion participation'],
        3 => ['id' => 4, 'name' => 'Pioneer', 'requirements' => 'Contribute to community architecture and pathway creation'],
        4 => ['id' => 5, 'name' => 'Benefactor', 'requirements' => 'Patreon subscription or significant community contribution'],
        5 => null
    ];

    $current = [
        'id' => $tierRank,
        'name' => $tierName,
        'color' => getTierColor($tierName),
        'rank' => $tierRank,
        'achieved_at' => formatTimestamp($user['join_date']), // TODO: track actual tier change dates
        'benefits' => $tierBenefits[$tierName] ?? []
    ];

    $nextTier = $nextTierMap[$tierRank] ?? null;

    // Build tier history (simplified - ideally would have a tier_history table)
    $history = [
        [
            'tier_name' => $tierName,
            'action' => 'current',
            'timestamp' => formatTimestamp($user['join_date'])
        ]
    ];

    // Add Observer join if not current tier
    if ($tierRank > 1) {
        $history[] = [
            'tier_name' => 'Observer',
            'action' => 'joined',
            'timestamp' => formatTimestamp($user['join_date'])
        ];
    }

    apiSuccess([
        'current' => $current,
        'next_tier' => $nextTier,
        'history' => $history
    ]);

} catch (PDOException $e) {
    error_log("API /me/tier error: " . $e->getMessage());
    apiError('Database error', 500);
}
