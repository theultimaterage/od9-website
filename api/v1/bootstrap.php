<?php
/**
 * OD9 API v1 Bootstrap
 * 
 * Core initialization for the Member Dashboard API.
 * Provides SQLite connection to bot database and API utilities.
 */

// Error reporting (disable in production)
$isLocal = strpos(__DIR__, 'xampp') !== false;
if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Load dashboard config.
//
// The old path was dirname(__DIR__, 2) . '/public/dashboard/includes/config.php',
// a pre-restructure leftover: back when public/ WAS the docroot that resolved
// correctly, but after public/* moved to the repo root the file lives at
// dashboard/includes/config.php. On prod the public/ path does not exist, so
// this require fataled and EVERY api/v1 endpoint returned 500 — which is what
// /api/v1/me.php was doing. Every other file in the repo already resolved it
// correctly (see curriculum/view-beacon.php); this was the only straggler.
//
// The legacy path is kept as a fallback so the endpoints still work from a
// checkout that predates the restructure.
$_od9Config = dirname(__DIR__, 2) . '/dashboard/includes/config.php';
if (!is_file($_od9Config)) {
    $_od9Config = dirname(__DIR__, 2) . '/public/dashboard/includes/config.php';
}
if (!is_file($_od9Config)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'code' => 'CONFIG_MISSING',
                      'message' => 'API config not found']);
    exit;
}
require_once $_od9Config;

// Constants the endpoints read. config.php defines NEITHER, and in PHP 8 an
// undefined constant is a fatal Error that `??` does NOT rescue — verified:
// `UNDEFINED ?? 'x'` throws "Error: Undefined constant". Nine endpoint files
// write `OD9_GUILD_ID ?? '...'` expecting a graceful fallback, so defining
// them here once fixes all of them rather than patching nine call sites.
if (!defined('OD9_GUILD_ID'))    { define('OD9_GUILD_ID', '1146833684952006769'); }
if (!defined('SESSION_LIFETIME')) { define('SESSION_LIFETIME', 604800); } // 7 days

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$allowedOrigins = [
    'http://localhost',
    'http://localhost:3000',
    'https://offda9.com',
    'https://www.offda9.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Get SQLite connection to bot database (read-only)
 */
function getBotDatabase(): PDO {
    static $db = null;
    
    if ($db === null) {
        $dbPath = defined('OD9_BOT_DB_PATH') ? OD9_BOT_DB_PATH : 'C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/od9.db';
        
        if (!file_exists($dbPath)) {
            apiError('Bot database not found', 500);
        }
        
        try {
            $db = new PDO("sqlite:$dbPath", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Open in read-only mode
            $db->exec('PRAGMA query_only = ON');
        } catch (PDOException $e) {
            error_log("Bot DB connection error: " . $e->getMessage());
            apiError('Database connection failed', 500);
        }
    }
    
    return $db;
}

/**
 * Get writeable SQLite connection (for user_settings only)
 */
function getBotDatabaseWriteable(): PDO {
    static $db = null;
    
    if ($db === null) {
        $dbPath = defined('OD9_BOT_DB_PATH') ? OD9_BOT_DB_PATH : 'C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/od9.db';
        
        try {
            $db = new PDO("sqlite:$dbPath", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Bot DB write connection error: " . $e->getMessage());
            apiError('Database connection failed', 500);
        }
    }
    
    return $db;
}

/**
 * Send JSON success response
 */
function apiSuccess(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send JSON error response
 */
function apiError(string $message, int $code = 400, ?string $errorCode = null): never {
    http_response_code($code);
    echo json_encode([
        'error' => true,
        'code' => $errorCode ?? match($code) {
            400 => 'INVALID_PARAM',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            429 => 'RATE_LIMITED',
            500 => 'INTERNAL_ERROR',
            default => 'ERROR'
        },
        'message' => $message
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Get request body as JSON
 */
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        apiError('Invalid JSON body', 400);
    }
    return $data;
}

/**
 * Get Authorization header (handles Apache quirks)
 */
function getAuthorizationHeader(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? null;
    
    if (empty($header) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    }
    
    return $header;
}

/**
 * Extract Bearer token from Authorization header
 */
function getBearerToken(): ?string {
    $header = getAuthorizationHeader();
    if ($header && preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Start secure session and verify authentication
 * Returns the Discord user ID if authenticated
 */
function requireAuth(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME ?? 604800,
            'path' => '/',
            'secure' => !str_contains(__DIR__, 'xampp'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
    
    // Check session-based auth
    if (!empty($_SESSION['discord_id'])) {
        return $_SESSION['discord_id'];
    }
    
    // Check Bearer token (for API calls)
    $token = getBearerToken();
    if ($token) {
        // Validate token against sessions table (if we implement token-based auth)
        // For now, we only support session-based auth
    }
    
    apiError('Authentication required', 401);
}

/**
 * Verify user is a member of OD9 guild
 */
function verifyGuildMembership(string $discordId): bool {
    $db = getBotDatabase();
    $guildId = OD9_GUILD_ID ?? '1146833684952006769';
    
    $stmt = $db->prepare("SELECT 1 FROM users WHERE user_id = ? AND guild_id = ? LIMIT 1");
    $stmt->execute([$discordId, $guildId]);
    
    return $stmt->fetchColumn() !== false;
}

/**
 * Rate limiting check
 */
function checkRateLimit(string $key, int $maxRequests = 100, int $windowSeconds = 60): void {
    // Simple in-memory rate limiting (consider Redis for production)
    $cacheFile = sys_get_temp_dir() . '/od9_ratelimit_' . md5($key) . '.json';
    
    $data = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : ['count' => 0, 'window_start' => time()];
    
    if (time() - $data['window_start'] > $windowSeconds) {
        $data = ['count' => 0, 'window_start' => time()];
    }
    
    $data['count']++;
    
    if ($data['count'] > $maxRequests) {
        apiError('Rate limit exceeded. Try again later.', 429, 'RATE_LIMITED');
    }
    
    file_put_contents($cacheFile, json_encode($data));
}

/**
 * Get Discord avatar URL
 */
function getAvatarUrl(string $discordId, ?string $avatarHash): string {
    if ($avatarHash) {
        $ext = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';
        return "https://cdn.discordapp.com/avatars/{$discordId}/{$avatarHash}.{$ext}";
    }
    // Default avatar
    return "https://cdn.discordapp.com/embed/avatars/" . (intval($discordId) % 5) . ".png";
}

/**
 * Format timestamp for API response
 */
function formatTimestamp(?string $timestamp): ?string {
    if (!$timestamp) return null;
    $dt = new DateTime($timestamp);
    return $dt->format('c'); // ISO 8601
}

/**
 * Tier ID to name mapping
 */
function getTierName(int $tierId): string {
    return match($tierId) {
        1 => 'Observer',
        2 => 'Theorist', 
        3 => 'Architect',
        4 => 'Pioneer',
        5 => 'Benefactor',
        default => 'Observer'
    };
}

/**
 * Tier name to color mapping
 */
function getTierColor(string $tierName): string {
    return match(strtolower($tierName)) {
        'observer' => '#808080',
        'theorist' => '#4169E1',
        'architect' => '#32CD32',
        'pioneer' => '#D4AF37',
        'benefactor' => '#9400D3',
        default => '#808080'
    };
}
