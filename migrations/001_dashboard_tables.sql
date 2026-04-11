-- OD9 Progression Dashboard - Phase H2-1 Schema
-- Created: 2026-04-10
-- Purpose: Store Discord progression data synced from bot for web dashboard

-- Discord users who have linked their accounts
CREATE TABLE IF NOT EXISTS dashboard_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discord_id VARCHAR(20) NOT NULL UNIQUE,
    discord_username VARCHAR(100),
    discord_discriminator VARCHAR(10),
    discord_avatar VARCHAR(255),
    display_name VARCHAR(100),
    email VARCHAR(255),
    public_profile BOOLEAN DEFAULT FALSE,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_discord_id (discord_id),
    INDEX idx_public (public_profile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Core progression data (synced from Discord bot)
CREATE TABLE IF NOT EXISTS dashboard_progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    guild_id VARCHAR(20) NOT NULL DEFAULT '0',
    total_credits INT DEFAULT 0,
    tier VARCHAR(20) DEFAULT 'OBSERVER',
    tier_progress_pct DECIMAL(5,2) DEFAULT 0.00,
    dim_analytical INT DEFAULT 0,
    dim_creative INT DEFAULT 0,
    dim_collaborative INT DEFAULT 0,
    dim_practical INT DEFAULT 0,
    dim_leadership INT DEFAULT 0,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES dashboard_users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_guild (user_id, guild_id),
    INDEX idx_tier (tier),
    INDEX idx_credits (total_credits)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Achievement tracking
CREATE TABLE IF NOT EXISTS dashboard_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    guild_id VARCHAR(20) NOT NULL DEFAULT '0',
    achievement_id VARCHAR(100) NOT NULL,
    achievement_name VARCHAR(200),
    achievement_desc TEXT,
    rarity VARCHAR(20) DEFAULT 'common',
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES dashboard_users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_achievement (user_id, guild_id, achievement_id),
    INDEX idx_rarity (rarity),
    INDEX idx_earned (earned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recent activity feed (rolling 100 items per user)
CREATE TABLE IF NOT EXISTS dashboard_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    guild_id VARCHAR(20) NOT NULL DEFAULT '0',
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    credits_earned INT DEFAULT 0,
    dimension_affected VARCHAR(20),
    metadata JSON,
    occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES dashboard_users(id) ON DELETE CASCADE,
    INDEX idx_user_occurred (user_id, occurred_at),
    INDEX idx_action (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook sync log (for debugging/auditing)
CREATE TABLE IF NOT EXISTS dashboard_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    discord_id VARCHAR(20),
    payload JSON,
    status ENUM('success', 'error') DEFAULT 'success',
    error_message TEXT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event (event_type),
    INDEX idx_discord (discord_id),
    INDEX idx_processed (processed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuth sessions (Discord OAuth flow)
CREATE TABLE IF NOT EXISTS dashboard_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    discord_access_token VARCHAR(255),
    discord_refresh_token VARCHAR(255),
    token_expires_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES dashboard_users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
