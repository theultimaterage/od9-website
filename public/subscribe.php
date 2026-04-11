<?php
/**
 * OD9 Email Subscribe Handler
 * 
 * Handles email signups from:
 * - Homepage popup form
 * - Join page form  
 * - Future /wake-up landing page
 * 
 * Inserts into OD9 local email_signups AND shared-platform email_subscribers
 * so the Phase 11 drip sequence engine can pick up new subscribers.
 * 
 * Accepts both regular form POST and AJAX (JSON response).
 * No CSRF required - this is a public, sessionless endpoint.
 * Rate limited by IP to prevent abuse.
 */

// Load OD9 database config (handles both local /public/ and production /root/ structures)
$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config/database.php';
}
require_once $configPath;

header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Rate limiting: max 5 signups per IP per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = sys_get_temp_dir() . '/od9_subscribe_' . md5($ip) . '.json';
$now = time();

if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true);
    // Clean entries older than 1 hour
    $rateData = array_filter($rateData, fn($ts) => ($now - $ts) < 3600);
    if (count($rateData) >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
        exit;
    }
} else {
    $rateData = [];
}

// Parse input - support both form POST and JSON body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

// Extract and sanitize fields
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$source = trim($input['source'] ?? 'website');
$referrer = trim($input['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
$context = trim($input['context'] ?? ''); // "What brought you here?" from join page

// Validate email
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// Normalize email
$email = strtolower($email);

// Split name into first/last
$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';

// Sanitize source
$allowedSources = ['website', 'homepage-popup', 'join-page', 'wake-up', 'ncz', 'discord'];
if (!in_array($source, $allowedSources)) {
    $source = 'website';
}

try {
    $pdo = getDatabaseConnection();
    
    // ========================================
    // 1. Insert into OD9 local email_signups
    // ========================================
    
    // Check if already subscribed
    $checkStmt = $pdo->prepare("SELECT id, email_opt_in FROM email_signups WHERE email = ?");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        if ($existing['email_opt_in']) {
            // Already subscribed and active
            echo json_encode([
                'success' => true, 
                'message' => "You're already on the list. We see you.",
                'already_subscribed' => true
            ]);
            exit;
        } else {
            // Re-subscribe (was opted out)
            $updateStmt = $pdo->prepare("
                UPDATE email_signups 
                SET email_opt_in = 1, 
                    first_name = COALESCE(NULLIF(?, ''), first_name),
                    signup_source = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$firstName, $source, $existing['id']]);
        }
    } else {
        // New subscriber
        $insertStmt = $pdo->prepare("
            INSERT INTO email_signups (email, first_name, last_name, signup_source, ip_address, referrer_url, email_opt_in, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $insertStmt->execute([$email, $firstName, $lastName, $source, $ip, $referrer]);
    }
    
    // ========================================
    // 2. Insert into shared-platform email_subscribers
    //    (for Phase 11 drip sequence engine)
    // ========================================
    
    try {
        // Load shared-platform credentials
        // Dev: same DB user (root), Prod: separate config file
        $spConfigPath = __DIR__ . '/../config/sp-credentials.php';
        if (!file_exists($spConfigPath)) {
            $spConfigPath = __DIR__ . '/config/sp-credentials.php';
        }
        
        if (file_exists($spConfigPath)) {
            $spCreds = require $spConfigPath;
            $spDsn = $spCreds['dsn'];
            $spUser = $spCreds['user'];
            $spPass = $spCreds['pass'];
        } else {
            // Dev fallback: same DB server, root user
            $spDsn = 'mysql:host=localhost;dbname=shared_platform;charset=utf8mb4';
            $spUser = DB_USER;
            $spPass = DB_PASS;
        }
        
        $spPdo = new PDO($spDsn, $spUser, $spPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Check if already in shared-platform
        $spCheck = $spPdo->prepare("SELECT id, status FROM email_subscribers WHERE site_id = 'od9' AND email = ?");
        $spCheck->execute([$email]);
        $spExisting = $spCheck->fetch();
        
        if ($spExisting) {
            if ($spExisting['status'] !== 'active') {
                // Reactivate
                $spUpdate = $spPdo->prepare("UPDATE email_subscribers SET status = 'active', first_name = COALESCE(NULLIF(?, ''), first_name) WHERE id = ?");
                $spUpdate->execute([$firstName, $spExisting['id']]);
            }
        } else {
            // Custom fields can store the "what brought you here" context
            $customFields = !empty($context) ? json_encode(['context' => $context]) : null;
            
            $spInsert = $spPdo->prepare("
                INSERT INTO email_subscribers (site_id, email, first_name, last_name, status, source, custom_fields, subscribed_at)
                VALUES ('od9', ?, ?, ?, 'active', 'signup', ?, NOW())
            ");
            $spInsert->execute([$email, $firstName, $lastName, $customFields]);
            
            $newSubscriberId = $spPdo->lastInsertId();
            
            // Auto-enroll in ALL OD9 lists (default + Dispatch + any future lists)
            $allLists = $spPdo->prepare("SELECT id FROM email_lists WHERE site_id = 'od9'");
            $allLists->execute();
            
            foreach ($allLists->fetchAll() as $listRow) {
                $addToList = $spPdo->prepare("INSERT IGNORE INTO email_list_members (list_id, subscriber_id, added_at) VALUES (?, ?, NOW())");
                $addToList->execute([$listRow['id'], $newSubscriberId]);
            }
            
            // Auto-enroll in active sequences triggered by list_subscribe
            $activeSeqs = $spPdo->prepare("
                SELECT id FROM email_sequences 
                WHERE site_id = 'od9' AND trigger_type = 'list_subscribe' AND is_active = 1
            ");
            $activeSeqs->execute();
            
            foreach ($activeSeqs->fetchAll() as $seq) {
                $enrollStmt = $spPdo->prepare("
                    INSERT IGNORE INTO email_sequence_enrollments (sequence_id, subscriber_id, current_step, status, enrolled_at, next_send_at)
                    VALUES (?, ?, 0, 'active', NOW(), NOW())
                ");
                $enrollStmt->execute([$seq['id'], $newSubscriberId]);
            }
        }
    } catch (PDOException $e) {
        // Log the error but don't fail the signup - local insert already succeeded
        error_log("OD9 subscribe: shared-platform sync failed: " . $e->getMessage());
    }
    
    // Record rate limit hit
    $rateData[] = $now;
    file_put_contents($rateLimitFile, json_encode($rateData));
    
    // Success
    echo json_encode([
        'success' => true,
        'message' => "You're in. Welcome to the movement.",
        'already_subscribed' => false
    ]);
    
} catch (PDOException $e) {
    error_log("OD9 subscribe error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
