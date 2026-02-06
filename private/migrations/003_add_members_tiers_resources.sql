-- Migration: Add Members, Tiers, and Resources tables
-- Date: 2026-01-29

-- ============================================
-- TIERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS od9_tiers (
    tier_id INT PRIMARY KEY AUTO_INCREMENT,
    tier_name VARCHAR(50) NOT NULL UNIQUE,
    tier_slug VARCHAR(50) NOT NULL UNIQUE,
    tier_order INT NOT NULL,
    discord_role_id VARCHAR(50),
    credit_requirement INT DEFAULT 0,
    color_hex VARCHAR(7) DEFAULT '#00BFFF',
    description TEXT,
    benefits JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default OD9 tiers
INSERT INTO od9_tiers (tier_name, tier_slug, tier_order, discord_role_id, credit_requirement, color_hex, description, benefits) VALUES
('Observer', 'observer', 1, NULL, 0, '#00BFFF', 'Entry level - Learn the fundamentals', '["Access to Discord", "Basic resources", "Community discussions"]'),
('Theorist', 'theorist', 2, NULL, 150, '#00D4FF', 'Intermediate - Deepen your understanding', '["All Observer benefits", "Advanced resources", "Study groups"]'),
('Architect', 'architect', 3, NULL, 250, '#00FFF0', 'Advanced - Apply knowledge', '["All Theorist benefits", "Project access", "Mentorship opportunities"]'),
('Pioneer', 'pioneer', 4, NULL, 400, '#00FFAA', 'Expert - Lead initiatives', '["All Architect benefits", "Leadership roles", "Direct collaboration"]'),
('Benefactor', 'benefactor', 5, NULL, 0, '#FFD700', 'Patron tier - Support the mission', '["All tiers unlocked", "Exclusive Patreon benefits", "Private channels"]');

-- ============================================
-- MEMBERS TABLE (Syncs with Discord Bot)
-- ============================================
CREATE TABLE IF NOT EXISTS od9_members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    discord_user_id VARCHAR(50) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL,
    current_tier_id INT,
    total_credits INT DEFAULT 0,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observer_completed BOOLEAN DEFAULT FALSE,
    theorist_completed BOOLEAN DEFAULT FALSE,
    architect_completed BOOLEAN DEFAULT FALSE,
    is_patreon_supporter BOOLEAN DEFAULT FALSE,
    patreon_tier VARCHAR(50),
    patreon_email VARCHAR(255),
    referral_code VARCHAR(20) UNIQUE,
    referred_by VARCHAR(20),
    last_active TIMESTAMP NULL,
    sync_status ENUM('synced', 'pending', 'error') DEFAULT 'pending',
    sync_last_attempted TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (current_tier_id) REFERENCES od9_tiers(tier_id),
    INDEX idx_discord_user_id (discord_user_id),
    INDEX idx_current_tier (current_tier_id),
    INDEX idx_patreon (is_patreon_supporter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RESOURCES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS od9_resources (
    resource_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    url VARCHAR(500) NOT NULL,
    category VARCHAR(100) NOT NULL,
    tags JSON,
    resource_type ENUM('article', 'video', 'course', 'book', 'study', 'tool', 'website') DEFAULT 'article',
    tier_requirement VARCHAR(50),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
    is_critical BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    icon_class VARCHAR(50) DEFAULT 'fas fa-external-link-alt',
    added_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES admin_users(admin_id),
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_tier (tier_requirement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RESOURCE CATEGORIES
-- ============================================
CREATE TABLE IF NOT EXISTS od9_resource_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO od9_resource_categories (category_name, category_slug, description, display_order) VALUES
('Global Challenges', 'global-challenges', 'Current state of global challenges facing humanity', 1),
('Kardashev Scale', 'kardashev-scale', 'Civilization advancement and the Kardashev Scale', 2),
('STEAM Optimization', 'steam', 'Science, Technology, Engineering, Arts, and Mathematics education', 3),
('Transhumanism', 'transhumanism', 'Human enhancement and technological transcendence', 4),
('AI Tools', 'ai-tools', 'Artificial intelligence tools and assistants', 5),
('OD9 Content', 'od9-content', 'Official OD9 content and community resources', 6);

-- ============================================
-- DISCORD BOT INTEGRATION
-- ============================================
CREATE TABLE IF NOT EXISTS od9_discord_bot_config (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    bot_name VARCHAR(100) DEFAULT 'OD9 Bot',
    bot_status ENUM('online', 'offline', 'maintenance') DEFAULT 'offline',
    sqlite_db_path VARCHAR(500),
    last_sync TIMESTAMP NULL,
    guild_id VARCHAR(50),
    celebrations_channel_id VARCHAR(50),
    announcements_channel_id VARCHAR(50),
    config_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default bot config
INSERT INTO od9_discord_bot_config (bot_name, sqlite_db_path) VALUES 
('OD9 Bot', 'C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/data/od9.db');
