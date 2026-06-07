<?php
/**
 * Discord OAuth Callback Handler
 * OD9 ASCEND Dashboard
 *
 * Handles the OAuth2 callback from Discord, exchanges code for token,
 * fetches user info, and verifies membership in bot database.
 */

// Secure session configuration
session_set_cookie_params([
    'lifetime' => 604800, // 7 days
    'path' => '/',
    'secure' => strpos(__DIR__, 'xampp') === false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../includes/config.php';

// Discord OAuth Configuration
$clientId = getenv('DISCORD_CLIENT_ID');
$clientSecret = getenv('DISCORD_CLIENT_SECRET');
$redirectUri = getenv('DISCORD_REDIRECT_URI');

// Verify OAuth is configured
if (!$clientId || !$clientSecret) {
    header('Location: ' . DASHBOARD_BASE_URL);
    exit;
}

// Verify state token (CSRF protection)
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    showError('Invalid state parameter. Please try again.', 'security');
}
unset($_SESSION['oauth_state']);

// Check for errors from Discord
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
    showError("Discord authorization failed: $error", 'discord');
}

// Must have authorization code
if (!isset($_GET['code'])) {
    showError('Missing authorization code.', 'missing_code');
}

$code = $_GET['code'];

// Exchange code for access token
$tokenUrl = 'https://discord.com/api/oauth2/token';
$tokenData = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri,
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT => 10,
]);
$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Discord token exchange failed: HTTP $httpCode - $tokenResponse");
    showError('Failed to exchange authorization code. Please try again.', 'token_exchange');
}

$tokenJson = json_decode($tokenResponse, true);
$accessToken = $tokenJson['access_token'] ?? null;

if (!$accessToken) {
    showError('No access token received from Discord.', 'no_token');
}

// Fetch user info from Discord
$userUrl = 'https://discord.com/api/users/@me';
$ch = curl_init($userUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
    CURLOPT_TIMEOUT => 10,
]);
$userResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    showError('Failed to fetch user info from Discord.', 'user_fetch');
}

$discordUser = json_decode($userResponse, true);
$discordId = $discordUser['id'] ?? null;
$username = $discordUser['username'] ?? 'Unknown';
$discriminator = $discordUser['discriminator'] ?? '0';
$avatar = $discordUser['avatar'] ?? null;
$globalName = $discordUser['global_name'] ?? $username;

if (!$discordId) {
    showError('Failed to get Discord user ID.', 'no_id');
}

// Build avatar URL (handle animated avatars)
$avatarExt = $avatar && str_starts_with($avatar, 'a_') ? 'gif' : 'png';
$avatarUrl = $avatar
    ? "https://cdn.discordapp.com/avatars/{$discordId}/{$avatar}.{$avatarExt}"
    : "https://cdn.discordapp.com/embed/avatars/" . (intval($discordId) % 5) . ".png";

// Verify user exists in OD9 MySQL database (od9_members table)
try {
    // DB credentials — single source of truth: config/database.php, so a
    // password rotation touches that one file, never this OAuth handler.
    $_cfg = __DIR__ . '/../../../config/database.php';
    if (!file_exists($_cfg)) $_cfg = __DIR__ . '/../../config/database.php';
    require_once $_cfg;
    
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Check if user exists in od9_members table
    $stmt = $db->prepare("SELECT discord_user_id as user_id, username, current_tier_id, total_credits, join_date FROM od9_members WHERE discord_user_id = ? LIMIT 1");
    $stmt->execute([$discordId]);
    $botUser = $stmt->fetch();
    
    // Map tier_id to tier name if needed
    if ($botUser && $botUser['current_tier_id']) {
        $tierStmt = $db->prepare("SELECT tier_name FROM od9_tiers WHERE tier_id = ?");
        $tierStmt->execute([$botUser['current_tier_id']]);
        $tier = $tierStmt->fetchColumn();
        $botUser['current_tier'] = $tier ?: 'observer';
    } elseif ($botUser) {
        $botUser['current_tier'] = 'observer';
    }
    
    if (!$botUser) {
        // User not found in bot database - not an OD9 member
        showError(
            "You're not a member of the OD9 Discord server yet!<br><br>" .
            "Join us first, then come back to view your progression.",
            'not_member',
            'https://discord.gg/spgmrXVMWq'
        );
    }
    
    // User is verified OD9 member - set session
    $_SESSION['discord_id'] = $discordId;
    $_SESSION['discord_username'] = $username;
    $_SESSION['discord_global_name'] = $globalName;
    $_SESSION['discord_avatar'] = $avatarUrl;
    $_SESSION['discord_access_token'] = $accessToken; // For future API calls
    $_SESSION['bot_user'] = $botUser; // Cache basic bot data
    $_SESSION['auth_time'] = time();
    
    // Redirect to dashboard
    $redirectTo = $_SESSION['auth_redirect'] ?? DASHBOARD_BASE_URL;
    unset($_SESSION['auth_redirect']);
    
    header('Location: ' . $redirectTo);
    exit;

} catch (PDOException $e) {
    error_log("Dashboard OAuth DB error: " . $e->getMessage());
    showError('Database error during login. Please try again.', 'db_error');
}

/**
 * Show styled error page
 */
function showError(string $message, string $errorCode, ?string $actionUrl = null): never {
    $discordInvite = 'https://discord.gg/spgmrXVMWq';
    $dashboardUrl = defined('DASHBOARD_BASE_URL') ? DASHBOARD_BASE_URL : '/dashboard/';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Error | OD9</title>
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            :root { --primary-blue: #00bfff; --deep-space: #0a0a0f; --gold: #D4AF37; --error-red: #ff4757; }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Rajdhani', sans-serif; 
                background: var(--deep-space); 
                color: #fff; 
                min-height: 100vh; 
                display: flex; 
                align-items: center; 
                justify-content: center;
            }
            .container { 
                text-align: center; 
                padding: 3rem; 
                max-width: 500px;
                background: rgba(255,255,255,0.03);
                border-radius: 16px;
                border: 1px solid rgba(255,255,255,0.1);
            }
            .icon { font-size: 4rem; color: var(--error-red); margin-bottom: 1.5rem; }
            .icon.warning { color: var(--gold); }
            h1 { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; margin-bottom: 1rem; }
            p { font-size: 1.1rem; color: #aaa; line-height: 1.6; margin-bottom: 1.5rem; }
            .btn { 
                display: inline-block; 
                padding: 12px 28px; 
                border-radius: 8px; 
                text-decoration: none; 
                font-weight: 600; 
                margin: 0.5rem;
                transition: all 0.3s;
            }
            .btn-primary { background: var(--primary-blue); color: #000; }
            .btn-primary:hover { background: #33ccff; transform: translateY(-2px); }
            .btn-discord { background: #5865F2; color: #fff; }
            .btn-discord:hover { background: #6d79ff; transform: translateY(-2px); }
            .btn-secondary { background: transparent; color: var(--primary-blue); border: 1px solid var(--primary-blue); }
            .error-code { font-size: 0.8rem; color: #888; margin-top: 2rem; }
        </style>
    </head>
    <body>
        <div class="container">
            <?php if ($errorCode === 'not_member'): ?>
                <i class="fab fa-discord icon warning"></i>
                <h1>Join OD9 First!</h1>
            <?php else: ?>
                <i class="fas fa-exclamation-triangle icon"></i>
                <h1>Login Error</h1>
            <?php endif; ?>
            
            <p><?= $message ?></p>
            
            <?php if ($actionUrl): ?>
                <a href="<?= htmlspecialchars($actionUrl) ?>" class="btn btn-discord" target="_blank">
                    <i class="fab fa-discord"></i> Join OD9 Discord
                </a>
            <?php endif; ?>
            
            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            
            <p class="error-code">Error: <?= htmlspecialchars($errorCode) ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
