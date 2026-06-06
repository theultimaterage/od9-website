-- Roadmap v0.2 MySQL mirror tables
-- Applied locally to XAMPP `od9_tickets` and on prod to `offda9_od9_tickets`
-- after the bot-side SQLite migration 008_roadmap_v0_2 has run.

CREATE TABLE IF NOT EXISTS od9_roadmap_triggers (
    trigger_id VARCHAR(64) PRIMARY KEY,
    domain VARCHAR(16) NOT NULL,
    title VARCHAR(255) NOT NULL,
    condition_text TEXT NOT NULL,
    prerequisites TEXT,
    action_text TEXT NOT NULL,
    effectiveness_rationale TEXT NOT NULL,
    unlocks TEXT,
    risk_if_skipped TEXT,
    status VARCHAR(32) NOT NULL DEFAULT 'Approved',
    source VARCHAR(32) NOT NULL DEFAULT 'community',
    source_proposal_id INT,
    proposed_by VARCHAR(64),
    proposed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    activated_at TIMESTAMP NULL,
    activated_by VARCHAR(64),
    retro_text TEXT,
    superseded_by VARCHAR(64),
    retired_reason TEXT,
    guild_id VARCHAR(64) NOT NULL DEFAULT '0',
    synced_at TIMESTAMP NULL,
    INDEX idx_roadmap_status (status),
    INDEX idx_roadmap_domain_status (domain, status),
    INDEX idx_roadmap_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
