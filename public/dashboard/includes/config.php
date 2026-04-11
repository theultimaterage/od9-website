<?php
/**
 * OD9 Dashboard Configuration
 * 
 * Discord OAuth2 + Bot Database Settings
 */

// Discord OAuth2 Application
putenv('DISCORD_CLIENT_ID=1395923907818688552');
putenv('DISCORD_CLIENT_SECRET=Du8m05duLD0jeFWYs2gU_KdVCZfin2gk');

// Callback URLs (environment-aware)
$isLocal = ($_SERVER['SERVER_NAME'] ?? 'localhost') === 'localhost' 
        || strpos(__DIR__, 'xampp') !== false;

if ($isLocal) {
    putenv('DISCORD_REDIRECT_URI=http://localhost/od9/public/dashboard/auth/callback.php');
    define('DASHBOARD_BASE_URL', 'http://localhost/od9/public/dashboard');
    define('API_BASE_URL', 'http://localhost/od9/api/v1');
} else {
    putenv('DISCORD_REDIRECT_URI=https://offda9.com/dashboard/auth/callback.php');
    define('DASHBOARD_BASE_URL', 'https://offda9.com/dashboard');
    define('API_BASE_URL', 'https://offda9.com/api/v1');
}

// OD9 Guild ID (for member verification)
define('OD9_GUILD_ID', '1309609816934559785');

// Bot SQLite Database Path (data/ subfolder)
define('OD9_BOT_DB_PATH', 'C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/data/od9.db');

// Session configuration
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// Legacy sync secret (keeping for backwards compatibility)
putenv('OD9_WEB_SYNC_SECRET=wr5fOF7VugK1NGk6eUAB0CWYRHiET8Jz');