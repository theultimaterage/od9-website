<?php
/**
 * OD9 API v1 - /me/credits Endpoint
 *
 * GET /api/v1/me/credits - Get credit balance and transaction history
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$discordId = requireAuth();
$guildId = OD9_GUILD_ID ?? '1146833684952006769';

checkRateLimit("credits_{$discordId}", 30, 60);

// Query params
$limit = min((int)($_GET['limit'] ?? 50), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);
$type = $_GET['type'] ?? 'all'; // earned, spent, gifted, all

$db = getBotDatabase();

try {
    // Get user balance
    $stmt = $db->prepare("SELECT total_credits FROM users WHERE user_id = ? AND guild_id = ?");
    $stmt->execute([$discordId, $guildId]);
    $balance = (int)($stmt->fetchColumn() ?: 0);

    // Get lifetime stats
    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) as lifetime_earned,
            COALESCE(SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END), 0) as lifetime_spent
        FROM credit_transactions
        WHERE user_id = ? AND guild_id = ?
    ");
    $stmt->execute([$discordId, $guildId]);
    $stats = $stmt->fetch();

    // Build transaction query based on type filter
    $whereClause = "WHERE user_id = ? AND guild_id = ?";
    $params = [$discordId, $guildId];

    if ($type === 'earned') {
        $whereClause .= " AND amount > 0";
    } elseif ($type === 'spent') {
        $whereClause .= " AND amount < 0";
    } elseif ($type === 'gifted') {
        $whereClause .= " AND (reason LIKE '%gift%' OR reason LIKE '%transfer%')";
    }

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM credit_transactions $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Get transactions
    $stmt = $db->prepare("
        SELECT transaction_id, amount, reason, timestamp, balance_after
        FROM credit_transactions
        $whereClause
        ORDER BY timestamp DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Format transactions
    $formattedTransactions = array_map(function($tx) {
        $amount = (int)$tx['amount'];
        return [
            'id' => (int)$tx['transaction_id'],
            'type' => $amount > 0 ? 'earned' : 'spent',
            'amount' => $amount,
            'reason' => $tx['reason'] ?? 'Unknown',
            'timestamp' => formatTimestamp($tx['timestamp']),
            'balance_after' => isset($tx['balance_after']) ? (int)$tx['balance_after'] : null
        ];
    }, $transactions);

    apiSuccess([
        'balance' => $balance,
        'lifetime_earned' => (int)($stats['lifetime_earned'] ?? 0),
        'lifetime_spent' => (int)($stats['lifetime_spent'] ?? 0),
        'transactions' => $formattedTransactions,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);

} catch (PDOException $e) {
    error_log("API /me/credits error: " . $e->getMessage());
    apiError('Database error', 500);
}
