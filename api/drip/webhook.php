<?php
/**
 * OD9 Drip System - Webhook Endpoint
 * 
 * Receives events from Discord bot:
 * - tier_change: Member tier upgraded/downgraded
 * - achievement: Member earned an achievement
 * - streak: Member hit a streak milestone
 * - patreon: Patreon-related events
 * 
 * @author Orion
 * @version 1.0.0
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CORS and headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Configuration
define('OD9_WEBHOOK_SECRET', getenv('OD9_WEBHOOK_SECRET') ?: 'od9_drip_secret_2026');

// Database config (matches production)
$isLocal = strpos(__DIR__, 'xampp') !== false;
if ($isLocal) {
    define('DRIP_DB_HOST', 'localhost');
    define('DRIP_DB_NAME', 'od9_tickets');
    define('DRIP_DB_USER', 'root');
    define('DRIP_DB_PASS', '');
} else {
    define('DRIP_DB_HOST', 'localhost');
    define('DRIP_DB_NAME', 'offda9_od9_tickets');
    define('DRIP_DB_USER', 'offda9_od9admin');
    define('DRIP_DB_PASS', 'Xk9mPvR3nWq7sYd');
}

/**
 * Get database connection
 */
function getDripDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DRIP_DB_HOST, DRIP_DB_NAME);
        $pdo = new PDO($dsn, DRIP_DB_USER, DRIP_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    return $pdo;
}

/**
 * Auto-migrate: Create tables if they don't exist
 */
function ensureTables(): void {
    $pdo = getDripDB();
    
    // od9_drip_sequences
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_drip_sequences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        trigger_event VARCHAR(50) NOT NULL,
        trigger_value VARCHAR(100) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_trigger (trigger_event, trigger_value)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // od9_drip_steps
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_drip_steps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sequence_id INT NOT NULL,
        step_number INT NOT NULL,
        template_name VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        delay_hours INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sequence_id) REFERENCES od9_drip_sequences(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_seq_step (sequence_id, step_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // od9_drip_enrollments
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_drip_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_user_id VARCHAR(20) NOT NULL,
        sequence_id INT NOT NULL,
        current_step INT DEFAULT 1,
        status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
        next_send_at DATETIME DEFAULT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        FOREIGN KEY (sequence_id) REFERENCES od9_drip_sequences(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_user_seq (discord_user_id, sequence_id),
        INDEX idx_next_send (status, next_send_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // od9_drip_log
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_drip_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL,
        step_id INT NOT NULL,
        email_to VARCHAR(255) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        status ENUM('sent', 'failed', 'skipped') NOT NULL,
        error_message TEXT DEFAULT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (enrollment_id) REFERENCES od9_drip_enrollments(id) ON DELETE CASCADE,
        FOREIGN KEY (step_id) REFERENCES od9_drip_steps(id) ON DELETE CASCADE,
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // od9_webhook_log
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_webhook_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        event_data JSON NOT NULL,
        discord_user_id VARCHAR(20) DEFAULT NULL,
        processing_status ENUM('received', 'processed', 'failed') DEFAULT 'received',
        error_message TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at DATETIME DEFAULT NULL,
        INDEX idx_event (event_type),
        INDEX idx_user (discord_user_id),
        INDEX idx_status (processing_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // od9_milestone_log
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_milestone_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_user_id VARCHAR(20) NOT NULL,
        milestone_type VARCHAR(50) NOT NULL,
        milestone_value VARCHAR(100) NOT NULL,
        achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_type (discord_user_id, milestone_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Seed default sequences if empty
    seedSequences($pdo);
}

/**
 * Seed default drip sequences
 */
function seedSequences(PDO $pdo): void {
    $count = $pdo->query("SELECT COUNT(*) FROM od9_drip_sequences")->fetchColumn();
    if ($count > 0) return;
    
    $sequences = [
        ['welcome', 'Welcome Series', 'New member onboarding', 'tier_change', 'observer'],
        ['tier-theorist', 'Theorist Welcome', 'Theorist tier onboarding', 'tier_change', 'theorist'],
        ['tier-architect', 'Architect Welcome', 'Architect tier onboarding', 'tier_change', 'architect'],
        ['tier-pioneer', 'Pioneer Welcome', 'Pioneer tier exclusive', 'tier_change', 'pioneer'],
        ['tier-benefactor', 'Benefactor Welcome', 'Benefactor VIP treatment', 'tier_change', 'benefactor'],
        ['achievement-first', 'First Achievement', 'Celebrate first achievement', 'achievement', 'first'],
        ['streak-7day', '7-Day Streak', 'Weekly streak celebration', 'streak', '7'],
        ['patreon-new', 'Patreon Welcome', 'New Patreon supporter', 'patreon', 'new']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO od9_drip_sequences (slug, name, description, trigger_event, trigger_value) VALUES (?, ?, ?, ?, ?)");
    foreach ($sequences as $seq) {
        $stmt->execute($seq);
    }
    
    // Seed steps for each sequence
    $steps = [
        // Welcome series (sequence 1)
        [1, 1, 'welcome-day1.html', 'Welcome to The OD9 Movement!', 0],
        [1, 2, 'welcome-day3.html', 'Your OD9 Journey: What\'s Next?', 72],
        [1, 3, 'welcome-day7.html', 'Week 1 Check-in: How\'s Your Journey?', 168],
        // Theorist (sequence 2)
        [2, 1, 'tier-theorist-welcome.html', 'Welcome to Theorist Tier!', 0],
        [2, 2, 'tier-theorist-resources.html', 'Exclusive Theorist Resources Inside', 48],
        // Architect (sequence 3)
        [3, 1, 'tier-architect-welcome.html', 'Welcome to Architect Tier!', 0],
        [3, 2, 'tier-architect-tools.html', 'Your Architect Tools Unlocked', 24],
        // Pioneer (sequence 4)
        [4, 1, 'tier-pioneer-welcome.html', 'Welcome to Pioneer Tier!', 0],
        [4, 2, 'tier-pioneer-exclusive.html', 'Pioneer-Only: Behind the Curtain', 48],
        // Benefactor (sequence 5)
        [5, 1, 'tier-benefactor-welcome.html', 'Welcome to the Inner Circle, Benefactor', 0],
        [5, 2, 'tier-benefactor-vip.html', 'Your VIP Benefactor Experience', 24],
        // First achievement (sequence 6)
        [6, 1, 'achievement-first.html', 'Congrats on Your First OD9 Achievement!', 0],
        // 7-day streak (sequence 7)
        [7, 1, 'streak-7day.html', '7-Day Streak! You\'re on Fire!', 0],
        // Patreon (sequence 8)
        [8, 1, 'patreon-welcome.html', 'Thank You for Supporting OD9 on Patreon!', 0],
        [8, 2, 'patreon-perks.html', 'Your Patreon Perks Explained', 72]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO od9_drip_steps (sequence_id, step_number, template_name, subject, delay_hours) VALUES (?, ?, ?, ?, ?)");
    foreach ($steps as $step) {
        $stmt->execute($step);
    }
}

/**
 * Validate webhook signature
 */
function validateSignature(string $payload, string $signature): bool {
    $expected = hash_hmac('sha256', $payload, OD9_WEBHOOK_SECRET);
    return hash_equals($expected, $signature);
}

/**
 * Log webhook event
 */
function logWebhookEvent(string $eventType, array $data, ?string $userId, string $status, ?string $error = null): int {
    $pdo = getDripDB();
    $stmt = $pdo->prepare("INSERT INTO od9_webhook_log 
        (event_type, event_data, discord_user_id, processing_status, error_message, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $eventType,
        json_encode($data),
        $userId,
        $status,
        $error,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Update webhook log status
 */
function updateWebhookStatus(int $logId, string $status, ?string $error = null): void {
    $pdo = getDripDB();
    $stmt = $pdo->prepare("UPDATE od9_webhook_log SET processing_status = ?, error_message = ?, processed_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $error, $logId]);
}

/**
 * Enroll user in a drip sequence
 */
function enrollInSequence(string $discordUserId, string $triggerEvent, ?string $triggerValue): ?array {
    $pdo = getDripDB();
    
    // Find matching sequence
    $stmt = $pdo->prepare("SELECT id, slug, name FROM od9_drip_sequences 
        WHERE trigger_event = ? AND (trigger_value = ? OR trigger_value IS NULL) AND is_active = 1
        ORDER BY trigger_value DESC LIMIT 1");
    $stmt->execute([$triggerEvent, $triggerValue]);
    $sequence = $stmt->fetch();
    
    if (!$sequence) {
        return null;
    }
    
    // Get first step delay
    $stmt = $pdo->prepare("SELECT delay_hours FROM od9_drip_steps WHERE sequence_id = ? AND step_number = 1");
    $stmt->execute([$sequence['id']]);
    $firstStep = $stmt->fetch();
    $delayHours = $firstStep ? $firstStep['delay_hours'] : 0;
    
    // Check if already enrolled
    $stmt = $pdo->prepare("SELECT id, status FROM od9_drip_enrollments WHERE discord_user_id = ? AND sequence_id = ?");
    $stmt->execute([$discordUserId, $sequence['id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // If completed/cancelled, re-enroll
        if (in_array($existing['status'], ['completed', 'cancelled'])) {
            $stmt = $pdo->prepare("UPDATE od9_drip_enrollments 
                SET status = 'active', current_step = 1, next_send_at = DATE_ADD(NOW(), INTERVAL ? HOUR), enrolled_at = NOW(), completed_at = NULL 
                WHERE id = ?");
            $stmt->execute([$delayHours, $existing['id']]);
            return ['action' => 're-enrolled', 'sequence' => $sequence['slug'], 'enrollment_id' => $existing['id']];
        }
        return ['action' => 'already_enrolled', 'sequence' => $sequence['slug'], 'enrollment_id' => $existing['id']];
    }
    
    // Create new enrollment
    $stmt = $pdo->prepare("INSERT INTO od9_drip_enrollments (discord_user_id, sequence_id, next_send_at) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))");
    $stmt->execute([$discordUserId, $sequence['id'], $delayHours]);
    
    return ['action' => 'enrolled', 'sequence' => $sequence['slug'], 'enrollment_id' => (int) $pdo->lastInsertId()];
}

/**
 * Log milestone
 */
function logMilestone(string $discordUserId, string $type, string $value): void {
    $pdo = getDripDB();
    $stmt = $pdo->prepare("INSERT INTO od9_milestone_log (discord_user_id, milestone_type, milestone_value) VALUES (?, ?, ?)");
    $stmt->execute([$discordUserId, $type, $value]);
}

/**
 * Handle tier_change event
 */
function handleTierChange(array $data): array {
    $userId = $data['discord_user_id'] ?? null;
    $newTier = strtolower($data['new_tier'] ?? '');
    $oldTier = strtolower($data['old_tier'] ?? '');
    
    if (!$userId || !$newTier) {
        return ['success' => false, 'error' => 'Missing discord_user_id or new_tier'];
    }
    
    // Log milestone
    logMilestone($userId, 'tier_change', $newTier);
    
    // Enroll in tier-specific sequence
    $result = enrollInSequence($userId, 'tier_change', $newTier);
    
    return ['success' => true, 'enrollment' => $result, 'tier' => $newTier];
}

/**
 * Handle achievement event
 */
function handleAchievement(array $data): array {
    $userId = $data['discord_user_id'] ?? null;
    $achievementId = $data['achievement_id'] ?? null;
    $achievementName = $data['achievement_name'] ?? 'Unknown';
    $isFirst = $data['is_first'] ?? false;
    
    if (!$userId) {
        return ['success' => false, 'error' => 'Missing discord_user_id'];
    }
    
    // Log milestone
    logMilestone($userId, 'achievement', $achievementId ?? $achievementName);
    
    // Enroll in first achievement sequence if applicable
    $result = null;
    if ($isFirst) {
        $result = enrollInSequence($userId, 'achievement', 'first');
    }
    
    return ['success' => true, 'enrollment' => $result, 'achievement' => $achievementName];
}

/**
 * Handle streak event
 */
function handleStreak(array $data): array {
    $userId = $data['discord_user_id'] ?? null;
    $streakDays = (int) ($data['streak_days'] ?? 0);
    
    if (!$userId || $streakDays < 1) {
        return ['success' => false, 'error' => 'Missing discord_user_id or invalid streak_days'];
    }
    
    // Log milestone
    logMilestone($userId, 'streak', (string) $streakDays);
    
    // Check for streak milestones (7, 30, 100, etc.)
    $milestones = [7, 14, 30, 60, 100, 365];
    $result = null;
    
    if (in_array($streakDays, $milestones)) {
        $result = enrollInSequence($userId, 'streak', (string) $streakDays);
    }
    
    return ['success' => true, 'enrollment' => $result, 'streak_days' => $streakDays];
}

/**
 * Handle patreon event
 */
function handlePatreon(array $data): array {
    $userId = $data['discord_user_id'] ?? null;
    $action = $data['action'] ?? 'new'; // new, cancelled, upgraded, downgraded
    $tierName = $data['tier_name'] ?? null;
    
    if (!$userId) {
        return ['success' => false, 'error' => 'Missing discord_user_id'];
    }
    
    // Log milestone
    logMilestone($userId, 'patreon', $action);
    
    // Enroll in patreon sequence
    $result = null;
    if ($action === 'new') {
        $result = enrollInSequence($userId, 'patreon', 'new');
    }
    
    return ['success' => true, 'enrollment' => $result, 'action' => $action];
}

// ============================================================
// MAIN ENTRY POINT
// ============================================================

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Ensure tables exist
    ensureTables();
    
    // Get raw payload
    $rawPayload = file_get_contents('php://input');
    if (empty($rawPayload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Validate signature (optional in dev, required in prod)
    $signature = $_SERVER['HTTP_X_OD9_SIGNATURE'] ?? '';
    if (!$isLocal && !empty(OD9_WEBHOOK_SECRET)) {
        if (!validateSignature($rawPayload, $signature)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }
    
    // Parse payload
    $payload = json_decode($rawPayload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $eventType = $payload['event'] ?? null;
    $eventData = $payload['data'] ?? [];
    $discordUserId = $eventData['discord_user_id'] ?? null;
    
    if (!$eventType) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event type']);
        exit;
    }
    
    // Log incoming webhook
    $logId = logWebhookEvent($eventType, $eventData, $discordUserId, 'received');
    
    // Process event
    $result = match($eventType) {
        'tier_change' => handleTierChange($eventData),
        'achievement' => handleAchievement($eventData),
        'streak' => handleStreak($eventData),
        'patreon' => handlePatreon($eventData),
        default => ['success' => false, 'error' => 'Unknown event type: ' . $eventType]
    };
    
    // Update log status
    if ($result['success'] ?? false) {
        updateWebhookStatus($logId, 'processed');
        http_response_code(200);
    } else {
        updateWebhookStatus($logId, 'failed', $result['error'] ?? 'Unknown error');
        http_response_code(422);
    }
    
    echo json_encode([
        'webhook_log_id' => $logId,
        'event' => $eventType,
        'result' => $result
    ]);
    
} catch (PDOException $e) {
    error_log("OD9 Webhook DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $isLocal ? $e->getMessage() : 'Internal server error']);
} catch (Exception $e) {
    error_log("OD9 Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $isLocal ? $e->getMessage() : 'Internal server error']);
}
