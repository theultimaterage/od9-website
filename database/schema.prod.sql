-- OD9 production schema — STRUCTURE ONLY, generated from prod.
--
-- Ground truth for schema-usage-lint (.claude/schema-usage-lint-config.json).
-- Before this file existed od9 ran NO schema gate at all, while carrying the
-- fleet's least-gated database code (a dashboard, Discord OAuth, a drip mailer).
-- The same gate found two live user-facing bugs on FTB the first time it ran
-- there, which is why od9 was the obvious next target.
--
-- Regenerate:  mysqldump --no-data --skip-comments --skip-dump-date
--                --no-tablespaces <db> | sed -E 's/ AUTO_INCREMENT=[0-9]+//'
--
-- NOTE: do NOT pass --skip-ssl here. This server runs with
-- require_secure_transport=ON and the dump fails with error 3159; FTB's copy of
-- this header carries --skip-ssl and that is specific to FTB's connection.
-- AUTO_INCREMENT counters are stripped so the file does not churn on every dump,
-- and view DEFINER account names are replaced with CURRENT_USER.
-- Contains no data and no credentials.
/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL DEFAULT 1,
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `icon` varchar(50) NOT NULL DEFAULT 'fas fa-trophy',
  `tier` enum('bronze','silver','gold','platinum') NOT NULL DEFAULT 'bronze',
  `requires_peer_validation` tinyint(1) NOT NULL DEFAULT 0,
  `peer_validations_needed` int(11) NOT NULL DEFAULT 0,
  `auto_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`auto_criteria`)),
  `points_reward` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_active` (`site_id`,`is_active`),
  KEY `idx_site_tier` (`site_id`,`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_validations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_validations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_achievement_id` int(11) NOT NULL,
  `validator_id` int(11) NOT NULL,
  `validator_type` enum('admin','customer') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_validation` (`user_achievement_id`,`validator_id`,`validator_type`),
  CONSTRAINT `achievement_validations_ibfk_1` FOREIGN KEY (`user_achievement_id`) REFERENCES `user_achievements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aco_agent_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aco_agent_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL DEFAULT 'shared-platform',
  `endpoint` varchar(100) NOT NULL COMMENT 'llms.txt, api/agent, api/v1/agent, schema-jsonld',
  `method` varchar(10) NOT NULL DEFAULT 'GET',
  `user_agent` text DEFAULT NULL,
  `agent_type` varchar(50) DEFAULT NULL COMMENT 'chatgpt, perplexity, gemini, claude, googlebot, unknown',
  `ip_address` varchar(45) DEFAULT NULL,
  `response_code` smallint(5) unsigned NOT NULL DEFAULT 200,
  `response_time_ms` int(10) unsigned DEFAULT NULL,
  `request_params` text DEFAULT NULL COMMENT 'JSON of query params or action',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_endpoint` (`site_id`,`endpoint`),
  KEY `idx_created` (`created_at`),
  KEY `idx_agent_type` (`agent_type`),
  KEY `idx_site_date` (`site_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aco_protocol_check_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aco_protocol_check_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `protocol_key` varchar(20) NOT NULL,
  `current_version` varchar(50) NOT NULL COMMENT 'Version found during check',
  `previous_version` varchar(50) DEFAULT NULL COMMENT 'Version before this check',
  `has_changed` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL COMMENT 'Migration notes or breaking change details',
  `checked_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_protocol` (`protocol_key`),
  KEY `idx_checked` (`checked_at`),
  KEY `idx_changes` (`has_changed`,`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aco_protocol_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aco_protocol_versions` (
  `protocol_key` varchar(20) NOT NULL COMMENT 'ucp, acp, webmcp, a2a, mcp',
  `version` varchar(50) NOT NULL COMMENT 'Current known version (e.g., 1.0.0, 2025-03-26)',
  `last_checked` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When we last verified the version',
  `last_changed` datetime DEFAULT NULL COMMENT 'When the version last changed',
  `changelog_url` varchar(500) DEFAULT NULL COMMENT 'URL to changelog or release notes',
  PRIMARY KEY (`protocol_key`),
  KEY `idx_last_changed` (`last_changed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aco_validation_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aco_validation_scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `layer` varchar(20) NOT NULL COMMENT 'jsonld, llms_txt, agent_profile, webmcp, ucp',
  `score` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0-100',
  `issues` text DEFAULT NULL COMMENT 'JSON array of validation issues',
  `checked_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_layer_latest` (`site_id`,`layer`,`checked_at`),
  KEY `idx_site_layer` (`site_id`,`layer`),
  KEY `idx_checked` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `action` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_user` (`site_id`,`user_id`,`user_type`),
  KEY `idx_site_action` (`site_id`,`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_asset_generation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_asset_generation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `feature` enum('design_studio','video_generation') NOT NULL,
  `prompt_text` text NOT NULL,
  `output_url` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `width` smallint(5) unsigned DEFAULT NULL,
  `height` smallint(5) unsigned DEFAULT NULL,
  `status` enum('pending','complete','failed') NOT NULL DEFAULT 'pending',
  `usage_log_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_feature` (`site_id`,`feature`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_content_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_content_suggestions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `content_type` enum('blog_idea','social_caption','email_subject','hashtags') NOT NULL,
  `prompt_context` text DEFAULT NULL COMMENT 'JSON-serialized context used to generate (event name, genre, etc.)',
  `generated_text` text NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `accepted_by` int(10) unsigned DEFAULT NULL COMMENT 'admin user_id who accepted',
  `usage_log_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_type` (`site_id`,`content_type`),
  KEY `idx_status` (`site_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_copilot_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_copilot_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `admin_user_id` int(10) unsigned NOT NULL,
  `context_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Serialized messages array [{role,content,timestamp},...]' CHECK (json_valid(`context_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_site_admin` (`site_id`,`admin_user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_provider_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_provider_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `provider` enum('openai','anthropic','gemini','custom') NOT NULL DEFAULT 'openai',
  `model_default` varchar(100) NOT NULL DEFAULT 'gpt-4o-mini',
  `api_key_enc` text DEFAULT NULL COMMENT 'AES-256 encrypted tenant key. NULL = use platform key from .env.',
  `max_tokens` int(10) unsigned NOT NULL DEFAULT 2000,
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.70,
  `system_prompt` text DEFAULT NULL COMMENT 'Custom system prompt override. NULL = use platform default.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_provider` (`site_id`,`provider`),
  KEY `idx_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_quotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_quotas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `feature` varchar(100) NOT NULL COMMENT 'e.g. ai_copilot, ai_content_suggestions',
  `period_month` char(7) NOT NULL COMMENT 'YYYY-MM format',
  `tokens_used` bigint(20) unsigned NOT NULL DEFAULT 0,
  `requests_used` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_generations_used` int(10) unsigned DEFAULT 0,
  `image_generations_limit` int(10) unsigned DEFAULT 100,
  `content_generations_used` int(10) unsigned DEFAULT 0,
  `content_generations_limit` int(10) unsigned DEFAULT 500,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_feature_month` (`site_id`,`feature`,`period_month`),
  KEY `idx_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_soul_filter_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_soul_filter_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `usage_log_id` bigint(20) unsigned DEFAULT NULL,
  `feature` varchar(100) NOT NULL,
  `content_hash` char(64) NOT NULL COMMENT 'SHA-256 of flagged content - no raw text stored',
  `violation_type` enum('hate','violence','exploitation','divisive','vulgarity','custom') NOT NULL,
  `severity` enum('warn','block') NOT NULL DEFAULT 'block',
  `detection_method` enum('openai_moderation','pattern_match','manual') NOT NULL,
  `resolution` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_resolution` (`resolution`),
  KEY `idx_created` (`created_at`),
  KEY `fk_soul_usage` (`usage_log_id`),
  CONSTRAINT `fk_soul_usage` FOREIGN KEY (`usage_log_id`) REFERENCES `ai_usage_log` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_usage_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_usage_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `feature` varchar(100) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_type` enum('admin','customer') DEFAULT NULL,
  `provider` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `prompt_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `completion_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `total_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `cost_usd` decimal(10,6) DEFAULT NULL,
  `latency_ms` int(10) unsigned DEFAULT NULL,
  `status` enum('success','error','filtered') NOT NULL DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `soul_filtered` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_feature` (`site_id`,`feature`),
  KEY `idx_site_month` (`site_id`,`created_at`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `event_type` varchar(50) DEFAULT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aria_content_generations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aria_content_generations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `gen_type` enum('bio','caption','script','dm_flow','carousel','product_idea','product_outline','product_full') NOT NULL,
  `input_prompt` text NOT NULL,
  `input_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`input_context`)),
  `output_content` text NOT NULL,
  `output_format` enum('text','json','markdown','html') NOT NULL DEFAULT 'text',
  `target_platform` varchar(50) DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `was_used` tinyint(1) DEFAULT NULL,
  `ai_provider` varchar(50) DEFAULT NULL,
  `ai_model` varchar(100) DEFAULT NULL,
  `tokens_used` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_gen_type` (`gen_type`),
  KEY `idx_target_platform` (`target_platform`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aria_dm_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aria_dm_flows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_type` enum('new_follower','story_reply','dm_keyword','manual') NOT NULL DEFAULT 'manual',
  `trigger_keyword` varchar(100) DEFAULT NULL COMMENT 'Keyword that triggers flow',
  `messages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '[{delay_hours: 0, content: "...", has_link: true}, ...]' CHECK (json_valid(`messages`)),
  `times_triggered` int(10) unsigned NOT NULL DEFAULT 0,
  `conversion_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_trigger_type` (`trigger_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aria_suggestion_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aria_suggestion_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_key` varchar(50) NOT NULL,
  `suggestion_type` enum('brand_improvement','content_idea','upsell_hint','milestone','warning','celebration') NOT NULL,
  `title_template` varchar(200) NOT NULL COMMENT 'Supports {placeholders}',
  `message_template` text NOT NULL COMMENT 'Supports {placeholders}',
  `cta_template` varchar(100) DEFAULT NULL,
  `default_priority` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `min_tier` enum('FREE','STARTER','PRO','BUSINESS','ENTERPRISE','ULTIMATE') NOT NULL DEFAULT 'FREE',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `cooldown_hours` int(10) unsigned DEFAULT 24 COMMENT 'Min hours between same suggestion to same site',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aria_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aria_suggestions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `suggestion_type` enum('brand_improvement','content_idea','upsell_hint','milestone','warning','celebration') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `cta_text` varchar(100) DEFAULT NULL COMMENT 'Call-to-action button text',
  `cta_url` varchar(500) DEFAULT NULL COMMENT 'Where CTA links to',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 5 COMMENT '1=highest, 10=lowest',
  `context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Data used to generate (brand score, field, etc.)' CHECK (json_valid(`context_data`)),
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `is_acted_upon` tinyint(1) NOT NULL DEFAULT 0,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `acted_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Auto-expire stale suggestions',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_active` (`site_id`,`is_dismissed`,`expires_at`),
  KEY `idx_site_type` (`site_id`,`suggestion_type`),
  KEY `idx_priority` (`site_id`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `achievement_key` varchar(50) NOT NULL,
  `achievement_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `earned_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_achievement` (`site_id`,`achievement_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_report_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_report_metrics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `metric_date` date NOT NULL,
  `spotify_monthly_listeners` int(10) unsigned DEFAULT NULL,
  `spotify_followers` int(10) unsigned DEFAULT NULL,
  `apple_music_listeners` int(10) unsigned DEFAULT NULL,
  `instagram_followers` int(10) unsigned DEFAULT NULL,
  `instagram_posts_this_week` tinyint(3) unsigned DEFAULT NULL,
  `instagram_avg_engagement` decimal(5,2) DEFAULT NULL,
  `tiktok_followers` int(10) unsigned DEFAULT NULL,
  `tiktok_avg_views` int(10) unsigned DEFAULT NULL,
  `youtube_subscribers` int(10) unsigned DEFAULT NULL,
  `platform_revenue_cents` int(10) unsigned DEFAULT NULL,
  `digital_product_sales` int(10) unsigned DEFAULT NULL,
  `brand_score` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_date` (`site_id`,`metric_date`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_metric_date` (`metric_date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `report_type` enum('weekly','biweekly','monthly','daily_pulse') NOT NULL DEFAULT 'weekly',
  `report_period_start` date NOT NULL,
  `report_period_end` date NOT NULL,
  `highlights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Top-line numbers and milestones' CHECK (json_valid(`highlights`)),
  `content_performance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Best/worst content, consistency score' CHECK (json_valid(`content_performance`)),
  `audience_pulse` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'New followers, demographics, sentiment' CHECK (json_valid(`audience_pulse`)),
  `aria_recommendations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Top 3 actionable tasks' CHECK (json_valid(`aria_recommendations`)),
  `brand_score_update` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Score change + breakdown' CHECK (json_valid(`brand_score_update`)),
  `upcoming_calendar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Releases, shows, deadlines' CHECK (json_valid(`upcoming_calendar`)),
  `pdf_path` varchar(500) DEFAULT NULL COMMENT 'Path to generated PDF report',
  `pdf_generated_at` timestamp NULL DEFAULT NULL,
  `delivered_to_email` tinyint(1) NOT NULL DEFAULT 0,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `status` enum('generating','ready','delivered','failed') NOT NULL DEFAULT 'generating',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_period_start` (`report_period_start`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_score_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_score_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `score` int(10) unsigned NOT NULL DEFAULT 0,
  `breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`breakdown`)),
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_score` (`site_id`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_score_thresholds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_score_thresholds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `threshold_score` int(10) unsigned NOT NULL,
  `action_type` enum('email','notification','unlock_feature','badge') NOT NULL,
  `action_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_data`)),
  `triggered_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_threshold` (`site_id`,`threshold_score`,`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bundle_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `item_type` enum('ticket','merch','download','access','credit') NOT NULL,
  `item_id` int(11) DEFAULT NULL COMMENT 'FK to tickets/products/etc. NULL for generic',
  `item_name` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_value` decimal(10,2) DEFAULT 0.00 COMMENT 'Individual item value for comparison pricing',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_package` (`package_id`),
  CONSTRAINT `bundle_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `vip_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_roi_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_roi_models` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `ad_spend_cents` int(10) unsigned NOT NULL COMMENT 'Total ad budget in cents',
  `platform` enum('meta','tiktok','youtube','spotify') NOT NULL,
  `duration_days` tinyint(3) unsigned NOT NULL DEFAULT 30,
  `projected_new_listeners_min` int(10) unsigned NOT NULL DEFAULT 0,
  `projected_new_listeners_max` int(10) unsigned NOT NULL DEFAULT 0,
  `projected_revenue_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Projected downstream revenue in cents',
  `projected_revenue_max` int(10) unsigned NOT NULL DEFAULT 0,
  `actual_new_listeners` int(10) unsigned DEFAULT NULL,
  `actual_revenue_cents` int(10) unsigned DEFAULT NULL,
  `modeled_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_modeled_at` (`modeled_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cascade_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cascade_proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_type` enum('cascade_accelerate','cascade_delay','cascade_universal','cascade_freeze') NOT NULL COMMENT 'accelerate=speed up, delay=slow down, universal=all tiers now, freeze=stop cascade',
  `feature_key` varchar(100) NOT NULL COMMENT 'The features.json key this proposal targets',
  `from_tier` enum('FREE','STARTER','PRO','BUSINESS','ENTERPRISE','ULTIMATE') DEFAULT NULL COMMENT 'Source tier (where the feature currently lives)',
  `to_tier` enum('FREE','STARTER','PRO','BUSINESS','ENTERPRISE','ULTIMATE') DEFAULT NULL COMMENT 'Target tier for cascade (NULL for universal proposals)',
  `proposed_cascade_days` int(11) DEFAULT NULL COMMENT 'New cascade delay (overrides features.json default)',
  `proposed_effective_date` date DEFAULT NULL COMMENT 'When the override should take effect',
  `rationale` text NOT NULL COMMENT 'Why this cascade change is being proposed',
  `proposed_by` int(11) NOT NULL COMMENT 'FK to governance_members.id',
  `status` enum('draft','open','passed','failed','executed','expired','vetoed') NOT NULL DEFAULT 'draft',
  `required_approval_pct` decimal(5,2) NOT NULL DEFAULT 60.00 COMMENT 'Weighted approval percentage needed. 60% standard, 75% for cascade_universal',
  `quorum_count` int(11) NOT NULL DEFAULT 5 COMMENT 'Minimum number of unique voters required',
  `voting_opens_at` timestamp NULL DEFAULT NULL,
  `voting_closes_at` timestamp NULL DEFAULT NULL,
  `cooldown_until` timestamp NULL DEFAULT NULL COMMENT 'Same feature cannot have another proposal until this date (30-day cooldown)',
  `total_votes` int(11) DEFAULT 0,
  `weighted_yes` decimal(8,2) DEFAULT 0.00,
  `weighted_no` decimal(8,2) DEFAULT 0.00,
  `weighted_abstain` decimal(8,2) DEFAULT 0.00,
  `final_approval_pct` decimal(5,2) DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `executed_by` varchar(100) DEFAULT NULL COMMENT 'system/cron or admin who applied the override',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_proposal_proposer` (`proposed_by`),
  KEY `idx_proposal_feature` (`feature_key`,`status`),
  KEY `idx_proposal_status` (`status`,`voting_closes_at`),
  KEY `idx_proposal_cooldown` (`feature_key`,`cooldown_until`),
  CONSTRAINT `fk_proposal_proposer` FOREIGN KEY (`proposed_by`) REFERENCES `governance_members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cascade_vote_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cascade_vote_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `vote` enum('yes','no','abstain') NOT NULL,
  `weight_at_vote` decimal(4,2) NOT NULL COMMENT 'Snapshot of member voting_weight at time of vote (immutable)',
  `reason` text DEFAULT NULL COMMENT 'Optional: why they voted this way (transparency)',
  `voted_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proposal_member` (`proposal_id`,`member_id`),
  KEY `fk_vote_member` (`member_id`),
  KEY `idx_vote_proposal` (`proposal_id`,`vote`),
  CONSTRAINT `fk_vote_member` FOREIGN KEY (`member_id`) REFERENCES `governance_members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vote_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `cascade_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collab_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collab_matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `matched_site_id` varchar(50) NOT NULL,
  `match_score` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0-100 compatibility score',
  `match_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'genre, audience_overlap, demographics, geography, tier, brand_score, content_style' CHECK (json_valid(`match_factors`)),
  `aria_suggestions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'ARIA-generated collab ideas' CHECK (json_valid(`aria_suggestions`)),
  `status` enum('pending','interested','declined','connected') NOT NULL DEFAULT 'pending',
  `is_blurred` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'FREE tier: details hidden',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_match` (`site_id`,`matched_site_id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_matched_site` (`matched_site_id`),
  KEY `idx_status` (`status`),
  KEY `idx_score` (`match_score` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collab_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collab_projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array of participating site IDs' CHECK (json_valid(`site_ids`)),
  `project_name` varchar(255) NOT NULL,
  `project_type` enum('single','ep','live','content','playlist') NOT NULL DEFAULT 'single',
  `status` enum('planning','active','completed') NOT NULL DEFAULT 'planning',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collab_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collab_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_site_id` varchar(50) NOT NULL,
  `to_site_id` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_from_to` (`from_site_id`,`to_site_id`),
  KEY `idx_from` (`from_site_id`),
  KEY `idx_to` (`to_site_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competitor_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitor_alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `watchlist_id` int(10) unsigned NOT NULL,
  `site_id` varchar(50) NOT NULL COMMENT 'Denormalized for fast lookup',
  `alert_type` enum('new_release','viral_post','playlist_add','milestone','tour_announce','collab','content_trend') NOT NULL,
  `severity` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `source_url` varchar(500) DEFAULT NULL,
  `aria_recommendation` text DEFAULT NULL COMMENT 'What ARIA suggests doing about this',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `detected_at` timestamp NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_watchlist_id` (`watchlist_id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_detected_at` (`detected_at` DESC),
  CONSTRAINT `competitor_alerts_ibfk_1` FOREIGN KEY (`watchlist_id`) REFERENCES `competitor_watchlist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competitor_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitor_snapshots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `watchlist_id` int(10) unsigned NOT NULL,
  `spotify_monthly_listeners` int(10) unsigned DEFAULT NULL,
  `spotify_followers` int(10) unsigned DEFAULT NULL,
  `spotify_popularity_score` tinyint(3) unsigned DEFAULT NULL COMMENT '0-100',
  `instagram_followers` int(10) unsigned DEFAULT NULL,
  `instagram_engagement_rate` decimal(5,2) DEFAULT NULL COMMENT 'Percentage',
  `tiktok_followers` int(10) unsigned DEFAULT NULL,
  `tiktok_avg_views` int(10) unsigned DEFAULT NULL,
  `youtube_subscribers` int(10) unsigned DEFAULT NULL,
  `youtube_avg_views` int(10) unsigned DEFAULT NULL,
  `snapshot_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_watchlist_date` (`watchlist_id`,`snapshot_date`),
  KEY `idx_watchlist_id` (`watchlist_id`),
  KEY `idx_snapshot_date` (`snapshot_date`),
  CONSTRAINT `competitor_snapshots_ibfk_1` FOREIGN KEY (`watchlist_id`) REFERENCES `competitor_watchlist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competitor_watchlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitor_watchlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `artist_name` varchar(200) NOT NULL,
  `spotify_id` varchar(100) DEFAULT NULL,
  `spotify_url` varchar(500) DEFAULT NULL,
  `instagram_handle` varchar(100) DEFAULT NULL,
  `tiktok_handle` varchar(100) DEFAULT NULL,
  `youtube_channel_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Why tracking this artist',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `last_checked_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_artist` (`site_id`,`artist_name`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_checked` (`last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content_gap_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_gap_analysis` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `gap_type` enum('content_format','posting_frequency','platform_presence','topic_coverage','engagement_style') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `competitors_doing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of competitor names doing this' CHECK (json_valid(`competitors_doing`)),
  `competitors_not_doing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array not doing this (opportunity)' CHECK (json_valid(`competitors_not_doing`)),
  `aria_recommendation` text NOT NULL,
  `estimated_impact` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `is_acted_on` tinyint(1) NOT NULL DEFAULT 0,
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Insights go stale',
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_gap_type` (`gap_type`),
  KEY `idx_estimated_impact` (`estimated_impact`),
  KEY `idx_generated_at` (`generated_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content_generation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_generation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `content_type` enum('image_resize','logo_placement','ad_variant','social_post','bio','press_release','email_copy') NOT NULL,
  `input_params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Original request parameters' CHECK (json_valid(`input_params`)),
  `output_url` varchar(500) DEFAULT NULL COMMENT 'URL/path to generated asset',
  `output_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional output metadata' CHECK (json_valid(`output_data`)),
  `provider` enum('local','openai','anthropic','stability','replicate') DEFAULT 'local',
  `model_version` varchar(100) DEFAULT NULL,
  `tokens_used` int(10) unsigned DEFAULT 0,
  `generation_time_ms` int(10) unsigned DEFAULT 0,
  `status` enum('pending','processing','completed','failed','expired') DEFAULT 'completed',
  `error_message` text DEFAULT NULL,
  `quota_category` enum('image','text','premium') DEFAULT 'image',
  `credits_consumed` decimal(10,2) DEFAULT 0.00,
  `user_rating` tinyint(4) DEFAULT NULL COMMENT '1-5 star rating from user',
  `was_used` tinyint(1) DEFAULT 0 COMMENT 'Did user actually use this output?',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When generated asset expires (cleanup)',
  PRIMARY KEY (`id`),
  KEY `idx_site_type` (`site_id`,`content_type`),
  KEY `idx_site_created` (`site_id`,`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_recommendations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `user_id` int(10) unsigned NOT NULL,
  `content_type` enum('pathway','resource','event','product','article') NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `score` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `is_clicked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_user` (`site_id`,`user_type`,`user_id`,`is_dismissed`),
  KEY `idx_content` (`content_type`,`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reward_key` varchar(50) NOT NULL,
  `reward_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `credit_cost` decimal(10,2) NOT NULL,
  `reward_type` enum('feature_unlock','billing_discount','priority_support','early_access') NOT NULL,
  `reward_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Type-specific: {"feature":"wishlist","days":30} or {"discount_percent":10}' CHECK (json_valid(`reward_value`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reward_key` (`reward_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `credits` decimal(10,2) NOT NULL COMMENT 'Positive=earn, negative=spend',
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `source_type` varchar(50) NOT NULL COMMENT 'tenant_referral, milestone, onboarding, feedback, renewal, admin_adjustment',
  `source_id` int(10) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_source` (`source_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cta_stops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cta_stops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stop_id` varchar(20) NOT NULL COMMENT 'CTA stop identifier from GTFS',
  `stop_name` varchar(255) NOT NULL,
  `stop_desc` varchar(500) DEFAULT NULL,
  `stop_lat` decimal(10,7) NOT NULL,
  `stop_lon` decimal(10,7) NOT NULL,
  `stop_type` enum('bus','train','both') NOT NULL DEFAULT 'bus',
  `parent_station` varchar(20) DEFAULT NULL,
  `wheelchair_accessible` tinyint(1) NOT NULL DEFAULT 0,
  `routes_served` varchar(500) DEFAULT NULL COMMENT 'Comma-separated route IDs serving this stop',
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stop_id` (`stop_id`),
  KEY `idx_lat_lon` (`stop_lat`,`stop_lon`),
  KEY `idx_type` (`stop_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL COMMENT 'FK to customers.id in tenant DB',
  `site_id` varchar(50) NOT NULL COMMENT 'Which tenant this asset belongs to',
  `asset_type` enum('TICKET','DOWNLOAD','MEDIA','MERCH','SUBSCRIPTION','LICENSE','CREDIT') NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_description` text DEFAULT NULL,
  `asset_reference_id` varchar(100) DEFAULT NULL COMMENT 'External ID (order_item_id, download_token, etc)',
  `asset_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Type-specific payload (ticket_code, download_url, file_path, etc)' CHECK (json_valid(`asset_data_json`)),
  `status` enum('ACTIVE','REDEEMED','EXPIRED','REVOKED','PENDING') NOT NULL DEFAULT 'ACTIVE',
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `redeemed_at` datetime DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to orders.id in tenant DB',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_site` (`customer_id`,`site_id`),
  KEY `idx_type_status` (`asset_type`,`status`),
  KEY `idx_reference` (`asset_reference_id`),
  KEY `idx_valid_until` (`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL COMMENT 'FK to sites.site_id',
  `plan_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'Platform-level customer who owns this subscription',
  `square_customer_id` varchar(64) DEFAULT NULL COMMENT 'Square Customer ID',
  `square_subscription_id` varchar(64) DEFAULT NULL COMMENT 'Square Subscription ID',
  `square_card_id` varchar(64) DEFAULT NULL COMMENT 'Square Card ID on file',
  `status` enum('ACTIVE','PENDING','PAUSED','CANCELED','DEACTIVATED','EXPIRED','TRIAL') NOT NULL DEFAULT 'PENDING',
  `billing_cycle` enum('MONTHLY','ANNUAL') NOT NULL DEFAULT 'MONTHLY',
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `last_payment_at` datetime DEFAULT NULL,
  `last_payment_amount` int(10) unsigned DEFAULT NULL COMMENT 'Last payment in cents',
  `failed_payment_count` int(10) unsigned NOT NULL DEFAULT 0,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Extra data from Square' CHECK (json_valid(`metadata_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_site_id` (`site_id`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_square_sub` (`square_subscription_id`),
  KEY `idx_square_customer` (`square_customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_period_end` (`current_period_end`),
  CONSTRAINT `fk_cs_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_wishlists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `notify_restock` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto-create restock_notifications when out of stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_product` (`site_id`,`customer_id`,`product_id`,`variant_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Row created by a sandbox checkout; set on INSERT only, never on UPDATE',
  `password_hash` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'US',
  `square_customer_id` varchar(255) DEFAULT NULL,
  `email_opt_in` tinyint(1) DEFAULT 0,
  `sms_opt_in` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customers_is_test` (`is_test`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `digital_product_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_product_purchases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `buyer_site_id` varchar(50) DEFAULT NULL COMMENT 'If buyer is a tenant',
  `buyer_email` varchar(255) NOT NULL,
  `buyer_name` varchar(200) DEFAULT NULL,
  `price_cents` int(11) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL COMMENT 'Square/Stripe payment ID',
  `payment_status` enum('pending','completed','refunded','failed') NOT NULL DEFAULT 'pending',
  `download_token` varchar(64) NOT NULL COMMENT 'Unique token for download link',
  `download_count` int(10) unsigned NOT NULL DEFAULT 0,
  `download_limit` int(10) unsigned NOT NULL DEFAULT 5,
  `expires_at` timestamp NULL DEFAULT NULL,
  `purchased_at` timestamp NULL DEFAULT current_timestamp(),
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_download_token` (`download_token`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_buyer_email` (`buyer_email`),
  KEY `idx_payment_status` (`payment_status`),
  CONSTRAINT `digital_product_purchases_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `digital_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `digital_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price_cents` int(11) NOT NULL DEFAULT 0,
  `product_type` enum('preset-pack','sample-pack','tutorial','guide','template','course','ebook','other') NOT NULL DEFAULT 'other',
  `file_path` varchar(500) NOT NULL COMMENT 'Path to downloadable file',
  `preview_path` varchar(500) DEFAULT NULL COMMENT 'Preview image or sample',
  `thumbnail` varchar(500) DEFAULT NULL,
  `aria_generated` tinyint(1) NOT NULL DEFAULT 0,
  `aria_prompt` text DEFAULT NULL COMMENT 'Original prompt if AI-generated',
  `download_count` int(10) unsigned NOT NULL DEFAULT 0,
  `revenue_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_slug` (`site_id`,`slug`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_product_type` (`product_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `download_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `download_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `download_token_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `format` varchar(20) NOT NULL,
  `file_size_bytes` bigint(20) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `download_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `download_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `order_item_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `format` varchar(20) DEFAULT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `max_downloads` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `first_downloaded_at` timestamp NULL DEFAULT NULL,
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `last_download_ip` varchar(45) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_campaign_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaign_recipients` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tracking_id` varchar(64) NOT NULL,
  `status` enum('pending','queued','sent','failed','bounced','opened','clicked') DEFAULT 'pending',
  `queued_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_status` (`campaign_id`,`status`),
  KEY `idx_tracking` (`tracking_id`),
  KEY `idx_subscriber` (`subscriber_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` longtext DEFAULT NULL,
  `status` enum('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
  `total_recipients` int(11) DEFAULT 0,
  `unsubscribe_count` int(11) DEFAULT 0,
  `total_sent` int(11) DEFAULT 0,
  `total_opened` int(11) DEFAULT 0,
  `total_clicked` int(11) DEFAULT 0,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `content` longtext DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `recipient_type` enum('all_subscribers','customers_only','ticket_buyers','custom') DEFAULT 'all_subscribers',
  `recipient_count` int(10) unsigned DEFAULT 0,
  `sent_count` int(10) unsigned DEFAULT 0,
  `failed_count` int(10) unsigned DEFAULT 0,
  `open_count` int(10) unsigned DEFAULT 0,
  `click_count` int(10) unsigned DEFAULT 0,
  `bounce_count` int(10) unsigned DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_list_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_list_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_list_subscriber` (`list_id`,`subscriber_id`),
  KEY `idx_subscriber` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `subscriber_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT NULL COMMENT 'Which site this email belongs to; NULL = predates the column',
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `status` enum('queued','sent','failed','bounced') NOT NULL DEFAULT 'queued',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `campaign` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_campaign_recipient` (`campaign`,`recipient`),
  KEY `idx_email_log_site_sent` (`site_id`,`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL COMMENT 'NULL for transactional/sequence emails',
  `sequence_enrollment_id` int(11) DEFAULT NULL COMMENT 'NULL for campaign/transactional emails',
  `subscriber_id` int(11) DEFAULT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(200) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `tracking_id` varchar(64) NOT NULL COMMENT 'Unique ID for open/click tracking',
  `status` enum('queued','sending','sent','failed','cancelled') DEFAULT 'queued',
  `priority` tinyint(4) DEFAULT 5 COMMENT '1=highest (transactional), 5=normal (marketing), 9=lowest (bulk)',
  `scheduled_at` datetime DEFAULT NULL COMMENT 'NULL = send immediately',
  `sent_at` datetime DEFAULT NULL,
  `attempts` tinyint(4) DEFAULT 0,
  `max_attempts` tinyint(4) DEFAULT 3,
  `last_error` text DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_scheduled` (`status`,`scheduled_at`),
  KEY `idx_site_campaign` (`site_id`,`campaign_id`),
  KEY `idx_tracking` (`tracking_id`),
  KEY `idx_priority_status` (`priority`,`status`,`scheduled_at`),
  KEY `idx_site_status` (`site_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_sequence_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_sequence_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `current_step` int(11) DEFAULT 0,
  `status` enum('active','completed','cancelled','paused') DEFAULT 'active',
  `enrolled_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `next_send_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sequence_subscriber` (`sequence_id`,`subscriber_id`),
  KEY `idx_next_send` (`status`,`next_send_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_sequence_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_sequence_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_id` int(11) NOT NULL,
  `step_order` tinyint(4) NOT NULL,
  `template_id` int(11) NOT NULL,
  `delay_days` int(11) NOT NULL DEFAULT 0 COMMENT '0=immediate, 1=next day, etc.',
  `delay_hours` int(11) NOT NULL DEFAULT 0,
  `subject_override` varchar(255) DEFAULT NULL COMMENT 'Override template subject',
  `is_active` tinyint(1) DEFAULT 1,
  `sent_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_sequence_order` (`sequence_id`,`step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_sequences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `trigger_type` enum('list_subscribe','purchase','signup','manual') NOT NULL,
  `trigger_list_id` int(11) DEFAULT NULL COMMENT 'For list_subscribe trigger',
  `is_active` tinyint(1) DEFAULT 0,
  `subscriber_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_trigger` (`site_id`,`trigger_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_signups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_signups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'website',
  `status` enum('active','unsubscribed','bounced') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `signup_source` varchar(100) DEFAULT 'website',
  `ip_address` varchar(45) DEFAULT NULL,
  `referrer_url` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `email_opt_in` tinyint(1) DEFAULT 0,
  `discord_user_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_discord_user_id` (`discord_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `status` enum('active','unsubscribed','bounced','complained','pending') DEFAULT 'active',
  `source` enum('signup','import','api','manual','purchase') DEFAULT 'manual',
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `subscribed_at` datetime DEFAULT current_timestamp(),
  `unsubscribed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_email` (`site_id`,`email`),
  KEY `idx_site_status` (`site_id`,`status`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content_html` mediumtext NOT NULL,
  `content_text` mediumtext DEFAULT NULL,
  `category` enum('transactional','marketing','automated') DEFAULT 'marketing',
  `variables_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Expected variables: [{"name":"customer_name","required":true}]' CHECK (json_valid(`variables_schema`)),
  `is_active` tinyint(1) DEFAULT 1,
  `version` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_slug` (`site_id`,`slug`),
  KEY `idx_site_category` (`site_id`,`category`),
  KEY `idx_site_active` (`site_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_tracking_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_tracking_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `tracking_id` varchar(64) NOT NULL,
  `event_type` enum('open','click','bounce','complaint','unsubscribe') NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'click: {url}, bounce: {type,reason}, etc.' CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tracking` (`tracking_id`),
  KEY `idx_site_campaign` (`site_id`,`campaign_id`),
  KEY `idx_event_type` (`event_type`,`created_at`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_unsubscribes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_unsubscribes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `list_id` int(11) DEFAULT NULL COMMENT 'NULL = unsubscribed from all',
  `reason` varchar(500) DEFAULT NULL,
  `method` enum('one-click','preference-center','manual','api') DEFAULT 'one-click',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_subscriber` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `epk_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `epk_profiles` (
  `site_id` varchar(50) NOT NULL,
  `content_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content_json`)),
  `ai_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_json`)),
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_change_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `change_type` enum('emergency_edit','standard_edit','status_change') NOT NULL,
  `is_emergency` tinyint(1) DEFAULT 0,
  `reason_category` enum('venue_emergency','weather_disaster','artist_issue','error_correction','legal_safety','other') DEFAULT NULL,
  `reason_text` text DEFAULT NULL,
  `fields_changed` longtext NOT NULL,
  `old_values` longtext NOT NULL,
  `new_values` longtext NOT NULL,
  `affected_customers` int(11) DEFAULT 0,
  `notification_sent` tinyint(1) DEFAULT 0,
  `notification_sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `name` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `timezone` varchar(10) DEFAULT 'CT',
  `venue` varchar(255) DEFAULT NULL,
  `venue_address` varchar(500) DEFAULT NULL,
  `venue_lat` decimal(10,7) DEFAULT NULL,
  `venue_lon` decimal(10,7) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','published','cancelled','completed') DEFAULT 'draft',
  `ticket_price` decimal(10,2) DEFAULT 0.00,
  `capacity` int(11) DEFAULT 0,
  `tickets_sold` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slug` varchar(255) DEFAULT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `venue_city` varchar(100) DEFAULT NULL,
  `venue_state` varchar(50) DEFAULT NULL,
  `venue_zip` varchar(20) DEFAULT NULL,
  `venue_capacity` int(10) unsigned DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `doors_open_time` time DEFAULT NULL,
  `show_start_time` time DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `promo_image` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `thumbnail_image` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `show_ticket_availability` tinyint(1) DEFAULT 1,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(500) DEFAULT NULL,
  `ticket_template_id` varchar(100) DEFAULT NULL,
  `custom_ticket_design` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_platform_featured` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_venue_geo` (`venue_lat`,`venue_lon`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fan_personas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fan_personas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `persona_name` varchar(100) NOT NULL COMMENT 'e.g. "Late Night Lydia"',
  `persona_emoji` varchar(10) DEFAULT NULL COMMENT 'e.g. ?',
  `age_range_min` tinyint(3) unsigned DEFAULT NULL,
  `age_range_max` tinyint(3) unsigned DEFAULT NULL,
  `gender_skew` enum('mostly_female','mostly_male','balanced','unknown') DEFAULT 'unknown',
  `primary_locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["Houston, TX", "Atlanta, GA"]' CHECK (json_valid(`primary_locations`)),
  `listening_times` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["9pm-1am", "morning commute"]' CHECK (json_valid(`listening_times`)),
  `listening_device` enum('headphones_mobile','desktop','smart_speaker','car','mixed') DEFAULT 'mixed',
  `discovery_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["Release Radar", "Friend recs", "IG Reels"]' CHECK (json_valid(`discovery_channels`)),
  `also_listens_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Similar artist names' CHECK (json_valid(`also_listens_to`)),
  `content_engages_with` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Content types they like' CHECK (json_valid(`content_engages_with`)),
  `content_scrolls_past` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Content types they ignore' CHECK (json_valid(`content_scrolls_past`)),
  `sharing_style` enum('public_shares','private_dm','saves_only','no_sharing') DEFAULT 'saves_only',
  `buys_merch` tinyint(1) DEFAULT NULL,
  `merch_price_ceiling_cents` int(10) unsigned DEFAULT NULL,
  `merch_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["oversized", "comfort items"]' CHECK (json_valid(`merch_preferences`)),
  `digital_product_interest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["sample packs", "guides"]' CHECK (json_valid(`digital_product_interest`)),
  `digital_product_price_ceiling_cents` int(10) unsigned DEFAULT NULL,
  `what_they_need` text DEFAULT NULL COMMENT 'What they seek from this artist',
  `best_reach_method` text DEFAULT NULL COMMENT 'How/when to reach them',
  `confidence_score` tinyint(3) unsigned DEFAULT NULL COMMENT '0-100, how confident ARIA is',
  `data_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Which data sources fed this persona' CHECK (json_valid(`data_sources`)),
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is this the main persona?',
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_is_primary` (`is_primary`),
  KEY `idx_generated_at` (`generated_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_identifier` varchar(100) NOT NULL COMMENT 'Discord user ID, email, or platform-specific identifier',
  `display_name` varchar(100) DEFAULT NULL,
  `ascend_tier` enum('Observer','Seeker','Initiate','Architect','Visionary','Transcendent') NOT NULL DEFAULT 'Observer',
  `voting_weight` decimal(4,2) NOT NULL DEFAULT 1.00 COMMENT 'Computed from ASCEND tier. Observer=1.0, Seeker=1.5, Initiate=2.0, Architect=3.0, Visionary=4.0, Transcendent=5.0',
  `engagement_score` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Running engagement metric that feeds tier advancement',
  `site_id` varchar(64) DEFAULT NULL COMMENT 'Tenant affiliation (NULL = platform-wide member)',
  `is_eligible_proposer` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Only Initiate+ can create proposals',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_identifier` (`member_identifier`),
  KEY `idx_member_tier` (`ascend_tier`,`is_active`),
  KEY `idx_member_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `learning_pathways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_pathways` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `difficulty` enum('beginner','intermediate','advanced','expert') NOT NULL DEFAULT 'beginner',
  `estimated_hours` decimal(5,1) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_published` (`site_id`,`is_published`),
  KEY `idx_site_category` (`site_id`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `points_per_dollar` decimal(5,2) NOT NULL DEFAULT 1.00,
  `signup_bonus` int(11) NOT NULL DEFAULT 50,
  `review_bonus` int(11) NOT NULL DEFAULT 25,
  `referral_bonus` int(11) NOT NULL DEFAULT 100,
  `birthday_bonus` int(11) NOT NULL DEFAULT 50,
  `tier_multipliers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"standard":1.0,"silver":1.25,"gold":1.5,"platinum":2.0}' CHECK (json_valid(`tier_multipliers`)),
  `loyalty_tier_thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"silver":500,"gold":2000,"platinum":5000}' CHECK (json_valid(`loyalty_tier_thresholds`)),
  `points_expiry_days` int(11) NOT NULL DEFAULT 365 COMMENT '0 = never expire',
  `min_redemption_points` int(11) NOT NULL DEFAULT 100,
  `points_to_dollar_ratio` int(11) NOT NULL DEFAULT 100 COMMENT '100 points = $1 discount',
  `program_name` varchar(100) NOT NULL DEFAULT 'Loyalty Rewards',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_redemptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `reward_id` int(10) unsigned NOT NULL,
  `points_spent` int(11) NOT NULL,
  `status` enum('pending','applied','expired','reversed') NOT NULL DEFAULT 'pending',
  `promo_code_id` int(10) unsigned DEFAULT NULL COMMENT 'Generated single-use promo code',
  `order_id` int(10) unsigned DEFAULT NULL COMMENT 'Order where reward was applied',
  `expires_at` datetime DEFAULT NULL COMMENT 'Redemption expires if not used by this date',
  `applied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_customer` (`site_id`,`customer_id`),
  KEY `idx_status` (`status`),
  KEY `fk_redemption_reward` (`reward_id`),
  CONSTRAINT `fk_redemption_reward` FOREIGN KEY (`reward_id`) REFERENCES `loyalty_rewards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_rewards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `points_cost` int(11) NOT NULL,
  `reward_type` enum('discount_fixed','discount_percent','free_product','free_shipping','vip_upgrade','custom') NOT NULL,
  `reward_value` decimal(10,2) DEFAULT NULL COMMENT 'Dollar amount for fixed, percentage for percent, NULL for custom',
  `reward_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Type-specific data: product_id for free_product, etc.' CHECK (json_valid(`reward_metadata`)),
  `max_redemptions` int(10) unsigned DEFAULT NULL COMMENT 'NULL = unlimited',
  `total_redeemed` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_active` (`site_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marketplace_creator_earnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_creator_earnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL COMMENT 'Creator site',
  `total_sales` int(11) NOT NULL DEFAULT 0,
  `total_earnings_cents` int(11) NOT NULL DEFAULT 0,
  `pending_payout_cents` int(11) NOT NULL DEFAULT 0,
  `lifetime_paid_cents` int(11) NOT NULL DEFAULT 0,
  `payout_method` enum('bank','paypal','platform_credit') DEFAULT 'platform_credit',
  `payout_email` varchar(255) DEFAULT NULL,
  `payout_threshold_cents` int(11) NOT NULL DEFAULT 5000 COMMENT 'Min $50 for payout',
  `last_payout_at` timestamp NULL DEFAULT NULL,
  `last_payout_amount_cents` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marketplace_featured`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_featured` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `feature_type` enum('hero','trending','new','staff-pick','seasonal') NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_feature_type` (`feature_type`),
  KEY `idx_position` (`position`),
  KEY `idx_dates` (`starts_at`,`ends_at`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `marketplace_featured_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `marketplace_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marketplace_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL COMMENT 'Buying site',
  `template_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who purchased (if applicable)',
  `purchase_type` enum('paid','tier-included','free','promotional') NOT NULL,
  `price_cents` int(11) NOT NULL DEFAULT 0,
  `platform_fee_cents` int(11) NOT NULL DEFAULT 0 COMMENT '30% to F.R.E.S.H.',
  `creator_earnings_cents` int(11) NOT NULL DEFAULT 0 COMMENT '70% to creator',
  `payment_id` varchar(100) DEFAULT NULL COMMENT 'Square/Stripe payment ID',
  `payment_status` enum('pending','completed','refunded','failed') NOT NULL DEFAULT 'pending',
  `purchased_at` timestamp NULL DEFAULT current_timestamp(),
  `downloaded_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_template` (`site_id`,`template_id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_purchase_type` (`purchase_type`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_purchased_at` (`purchased_at`),
  CONSTRAINT `marketplace_purchases_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `marketplace_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marketplace_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `site_id` varchar(50) NOT NULL COMMENT 'Reviewer site',
  `user_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `status` enum('pending','approved','flagged','removed') NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`template_id`,`site_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_rating` (`rating`),
  KEY `idx_status` (`status`),
  CONSTRAINT `marketplace_reviews_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `marketplace_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marketplace_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL COMMENT 'Creator site_id or "platform" for first-party',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('one-sheet','press-kit','contract','invoice','social','flyer','rider','split-sheet','other') NOT NULL DEFAULT 'other',
  `template_type` enum('first-party','creator','ai-generated') NOT NULL DEFAULT 'first-party',
  `quality_tier` enum('good','better','best') NOT NULL DEFAULT 'good' COMMENT 'Good/Better/Best versioning',
  `price_cents` int(11) NOT NULL DEFAULT 0 COMMENT '0 = free/tier-included',
  `is_tier_included` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Included in subscriber tier',
  `min_tier_required` enum('FREE','STARTER','PRO','BUSINESS','ENTERPRISE','ULTIMATE') DEFAULT NULL COMMENT 'Tier-exclusive templates',
  `file_path` varchar(500) NOT NULL COMMENT 'Path to template file',
  `preview_image` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["contract", "legal", "music"]' CHECK (json_valid(`tags`)),
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Fillable field definitions' CHECK (json_valid(`fields`)),
  `downloads` int(11) NOT NULL DEFAULT 0,
  `rating_avg` decimal(3,2) DEFAULT NULL,
  `rating_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','pending_review','approved','rejected','archived') NOT NULL DEFAULT 'draft',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_category` (`category`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_status` (`status`),
  KEY `idx_quality_tier` (`quality_tier`),
  KEY `idx_min_tier` (`min_tier_required`),
  KEY `idx_price` (`price_cents`),
  KEY `idx_rating` (`rating_avg` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#00BFFF',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `file_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `file_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mentorship_pairs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentorship_pairs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `mentor_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `mentor_id` int(10) unsigned NOT NULL,
  `mentee_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `mentee_id` int(10) unsigned NOT NULL,
  `status` enum('pending','active','paused','completed','cancelled') NOT NULL DEFAULT 'pending',
  `focus_area` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `paired_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_status` (`site_id`,`status`),
  KEY `idx_mentor` (`site_id`,`mentor_type`,`mentor_id`),
  KEY `idx_mentee` (`site_id`,`mentee_type`,`mentee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mentorship_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentorship_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pair_id` int(10) unsigned NOT NULL,
  `site_id` varchar(50) NOT NULL,
  `session_date` datetime NOT NULL,
  `duration_minutes` int(10) unsigned DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pair` (`pair_id`),
  KEY `idx_site_date` (`site_id`,`session_date`),
  CONSTRAINT `fk_session_pair` FOREIGN KEY (`pair_id`) REFERENCES `mentorship_pairs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `milestone_tracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `milestone_tracker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `milestone_type` enum('listeners','followers','revenue','streams','playlist') NOT NULL,
  `target_value` bigint(20) unsigned NOT NULL COMMENT 'The number to hit',
  `achieved_value` bigint(20) unsigned DEFAULT NULL COMMENT 'Value when achieved',
  `achieved_at` timestamp NULL DEFAULT NULL COMMENT 'When milestone was actually hit',
  `projected_date` date DEFAULT NULL COMMENT 'ARIA projected completion date',
  `status` enum('pending','achieved','missed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_status` (`status`),
  KEY `idx_milestone_type` (`milestone_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `icon` varchar(50) NOT NULL DEFAULT 'fas fa-star',
  `trigger_type` enum('activity_count','streak_length','points_total','manual') NOT NULL,
  `trigger_action` varchar(100) DEFAULT NULL,
  `trigger_threshold` int(11) NOT NULL DEFAULT 1,
  `points_reward` int(11) NOT NULL DEFAULT 0,
  `badge_color` varchar(20) NOT NULL DEFAULT '#FFD700',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_active` (`site_id`,`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `music_api_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `music_api_token` (
  `platform` varchar(20) NOT NULL,
  `access_token` text NOT NULL,
  `expires_at` datetime NOT NULL,
  `obtained_at` datetime NOT NULL,
  PRIMARY KEY (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `music_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `music_catalog` (
  `platform` varchar(20) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `title` varchar(512) DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `permalink_url` text DEFAULT NULL,
  `artwork_url` text DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `genre` varchar(128) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `src_created_at` datetime DEFAULT NULL,
  `first_seen_at` datetime NOT NULL,
  `last_synced_at` datetime NOT NULL,
  PRIMARY KEY (`platform`,`track_id`),
  KEY `idx_first_seen` (`platform`,`first_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_discord_bot_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_discord_bot_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_name` varchar(100) DEFAULT 'R-ZERO',
  `bot_status` enum('online','offline','maintenance') DEFAULT 'offline',
  `guild_id` varchar(20) DEFAULT NULL,
  `celebrations_channel_id` varchar(20) DEFAULT NULL,
  `announcements_channel_id` varchar(20) DEFAULT NULL,
  `sqlite_db_path` varchar(500) DEFAULT NULL,
  `config_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_data`)),
  `last_sync` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_drip_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_drip_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_user_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sequence_name` varchar(100) NOT NULL DEFAULT 'welcome',
  `current_step` int(11) NOT NULL DEFAULT 0,
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_sent_at` datetime DEFAULT NULL,
  `next_send_at` datetime DEFAULT NULL,
  `status` enum('active','paused','completed','unsubscribed') NOT NULL DEFAULT 'active',
  `source` varchar(50) DEFAULT NULL COMMENT 'signup, tier_change, achievement, manual',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_user_sequence` (`discord_user_id`,`sequence_name`),
  KEY `idx_status_next_send` (`status`,`next_send_at`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_drip_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_drip_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `discord_user_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sequence_name` varchar(100) NOT NULL,
  `step_number` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed','bounced','opened','clicked') NOT NULL DEFAULT 'sent',
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_enrollment` (`enrollment_id`),
  KEY `idx_user` (`discord_user_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_drip_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_drip_sequences` (
  `sequence_id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_type` enum('signup','tier_change','achievement','manual','webhook') NOT NULL DEFAULT 'signup',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sequence_id`),
  UNIQUE KEY `sequence_name` (`sequence_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_drip_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_drip_steps` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `delay_hours` int(11) NOT NULL DEFAULT 24 COMMENT 'Hours after previous step',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`step_id`),
  UNIQUE KEY `unique_sequence_step` (`sequence_id`,`step_number`),
  KEY `idx_sequence` (`sequence_id`),
  CONSTRAINT `od9_drip_steps_ibfk_1` FOREIGN KEY (`sequence_id`) REFERENCES `od9_drip_sequences` (`sequence_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_user_id` varchar(50) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `current_tier_id` int(11) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 0,
  `join_date` datetime DEFAULT NULL,
  `observer_completed` tinyint(1) DEFAULT 0,
  `theorist_completed` tinyint(1) DEFAULT 0,
  `architect_completed` tinyint(1) DEFAULT 0,
  `pioneer_completed` tinyint(1) DEFAULT 0,
  `is_patreon_supporter` tinyint(1) DEFAULT 0,
  `patreon_tier` varchar(50) DEFAULT NULL,
  `patreon_email` varchar(255) DEFAULT NULL,
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` varchar(20) DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `sync_status` varchar(20) DEFAULT 'pending',
  `sync_last_attempted` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `discord_user_id` (`discord_user_id`),
  UNIQUE KEY `referral_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_milestone_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_milestone_log` (
  `milestone_id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_user_id` varchar(50) NOT NULL,
  `milestone_type` enum('tier_change','achievement','streak','referral','patreon','anniversary') NOT NULL,
  `milestone_name` varchar(100) NOT NULL,
  `previous_value` varchar(100) DEFAULT NULL,
  `new_value` varchar(100) NOT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent_at` datetime DEFAULT NULL,
  `achieved_at` datetime NOT NULL DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`milestone_id`),
  KEY `idx_user` (`discord_user_id`),
  KEY `idx_type` (`milestone_type`),
  KEY `idx_email_sent` (`email_sent`),
  KEY `idx_achieved_at` (`achieved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_profile_visibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_profile_visibility` (
  `discord_id` varchar(20) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `presence` varchar(10) NOT NULL DEFAULT 'hidden',
  PRIMARY KEY (`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_remember_tokens` (
  `selector` char(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `discord_id` varchar(20) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`selector`),
  KEY `idx_discord` (`discord_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_resource_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_resource_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`),
  UNIQUE KEY `category_slug` (`category_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `title` varchar(255) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT 'website',
  `icon_class` varchar(100) DEFAULT 'fas fa-link',
  `difficulty_level` varchar(50) DEFAULT 'all',
  `credit_value` int(11) DEFAULT 10,
  `tier_level` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `is_critical` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `tags` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `tier_requirement` varchar(50) DEFAULT NULL,
  `added_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_tiers` (
  `tier_id` int(11) NOT NULL AUTO_INCREMENT,
  `tier_name` varchar(50) NOT NULL,
  `tier_order` int(11) DEFAULT 0,
  `credit_requirement` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `color_hex` varchar(20) DEFAULT '#00BFFF',
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `discord_role_id` varchar(50) DEFAULT NULL,
  `tier_slug` varchar(50) DEFAULT NULL,
  `benefits` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_tour_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_tour_state` (
  `discord_id` varchar(32) NOT NULL,
  `chapters` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `od9_webhook_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `od9_webhook_log` (
  `webhook_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT 'tier_change, achievement, streak, join, etc.',
  `discord_user_id` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` datetime DEFAULT NULL,
  `result` text DEFAULT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`webhook_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user` (`discord_user_id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `item_type` enum('ticket','merch','digital') DEFAULT 'ticket',
  `ticket_type_id` int(10) unsigned DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `ticket_code` varchar(100) DEFAULT NULL,
  `qr_code_hash` varchar(64) DEFAULT NULL,
  `validation_status` enum('pending','valid','used','cancelled','flagged') DEFAULT 'pending',
  `scanned_at` timestamp NULL DEFAULT NULL,
  `scanned_by` varchar(100) DEFAULT NULL,
  `scan_location` varchar(255) DEFAULT NULL,
  `scan_count` int(11) DEFAULT 0,
  `last_scan_ip` varchar(45) DEFAULT NULL,
  `flagged_reason` text DEFAULT NULL,
  `transferred_to_email` varchar(255) DEFAULT NULL,
  `transferred_at` timestamp NULL DEFAULT NULL,
  `transfer_accepted` tinyint(1) DEFAULT 0,
  `product_options` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `order_number` varchar(50) DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Sandbox order placed through SimulatedPayment; never real money',
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','completed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `promo_code_id` int(10) unsigned DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_type` enum('tickets','merchandise','mixed') DEFAULT 'tickets',
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `promo_code_used` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `square_payment_id` varchar(255) DEFAULT NULL,
  `square_order_id` varchar(255) DEFAULT NULL,
  `square_receipt_url` varchar(500) DEFAULT NULL,
  `order_status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `fulfillment_status` enum('unfulfilled','processing','shipped','delivered') DEFAULT 'unfulfilled',
  `tracking_number` varchar(255) DEFAULT NULL,
  `shipping_carrier` varchar(50) DEFAULT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `label_url` varchar(500) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `shipping_address` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(50) DEFAULT NULL,
  `shipping_zip` varchar(20) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_is_test_created` (`is_test`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `package_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_email` varchar(255) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `status` enum('completed','refunded','pending') NOT NULL DEFAULT 'completed',
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_package` (`package_id`),
  KEY `idx_customer` (`customer_email`),
  CONSTRAINT `package_purchases_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `vip_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pathway_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pathway_steps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pathway_id` int(10) unsigned NOT NULL,
  `site_id` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('text','video','quiz','resource','external_link') NOT NULL DEFAULT 'text',
  `content_data` longtext DEFAULT NULL,
  `step_order` int(10) unsigned NOT NULL DEFAULT 0,
  `estimated_minutes` int(10) unsigned DEFAULT NULL,
  `points_reward` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pathway` (`pathway_id`,`step_order`),
  KEY `idx_site` (`site_id`),
  CONSTRAINT `fk_step_pathway` FOREIGN KEY (`pathway_id`) REFERENCES `learning_pathways` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pdf_annotation_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdf_annotation_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `annotation_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_annotation` (`annotation_id`),
  CONSTRAINT `pdf_annotation_comments_ibfk_1` FOREIGN KEY (`annotation_id`) REFERENCES `pdf_annotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pdf_annotation_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdf_annotation_shares` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `annotation_id` int(10) unsigned NOT NULL,
  `shared_with_user_id` int(10) unsigned NOT NULL,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_annotation_user` (`annotation_id`,`shared_with_user_id`),
  CONSTRAINT `fk_annotation_share` FOREIGN KEY (`annotation_id`) REFERENCES `pdf_annotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pdf_annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdf_annotations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(10) unsigned NOT NULL,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `page_number` int(10) unsigned NOT NULL DEFAULT 1,
  `annotation_type` enum('highlight','underline','strikethrough','note','drawing','text','stamp') NOT NULL,
  `x_percent` decimal(8,4) NOT NULL,
  `y_percent` decimal(8,4) NOT NULL,
  `width_percent` decimal(8,4) DEFAULT NULL,
  `height_percent` decimal(8,4) DEFAULT NULL,
  `content` text DEFAULT NULL COMMENT 'Note text or drawing SVG path data',
  `color` varchar(7) DEFAULT '#FFFF00' COMMENT 'Hex color for highlight/drawing',
  `opacity` decimal(3,2) DEFAULT 0.50,
  `is_private` tinyint(1) DEFAULT 1 COMMENT 'Private to user or shared with team',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_document` (`document_id`),
  KEY `idx_site_user` (`site_id`,`user_id`),
  KEY `idx_page` (`document_id`,`page_number`),
  CONSTRAINT `pdf_annotations_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `pdf_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pdf_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdf_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) unsigned DEFAULT 0,
  `doc_type` enum('press-kit','one-sheet','contract','rider','invoice','split-sheet','other') DEFAULT 'other',
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_site_type` (`site_id`,`doc_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pdf_template_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdf_template_library` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `doc_type` enum('press-kit','one-sheet','contract','rider','invoice','split-sheet','other') NOT NULL,
  `min_tier` enum('FREE','STARTER','PRO','BUSINESS','ENTERPRISE','ULTIMATE') DEFAULT 'FREE',
  `file_path` varchar(255) NOT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `field_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Maps form field names to brand profile fields' CHECK (json_valid(`field_map`)),
  `is_fillable` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `download_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_doc_type` (`doc_type`),
  KEY `idx_min_tier` (`min_tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `persistent_carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `persistent_carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `cart_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cart_data`)),
  `status` enum('active','recovered','expired') DEFAULT 'active',
  `recovery_token` varchar(64) DEFAULT NULL,
  `reminder_count` int(11) DEFAULT 0,
  `last_reminder_at` datetime DEFAULT NULL,
  `recovered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_email` (`site_id`,`email`),
  KEY `idx_status` (`status`),
  KEY `idx_recovery` (`recovery_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `points_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `points_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `points` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL DEFAULT 0,
  `source_type` varchar(50) NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_user` (`site_id`,`user_id`,`user_type`),
  KEY `idx_site_user_latest` (`site_id`,`user_id`,`user_type`,`id` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `sku_suffix` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `slug` varchar(255) DEFAULT NULL,
  `product_type` enum('physical','digital','bundle') DEFAULT 'physical',
  `sku` varchar(100) DEFAULT NULL,
  `quantity_in_stock` int(11) DEFAULT 0,
  `is_unlimited_stock` tinyint(1) DEFAULT 0,
  `track_variant_stock` tinyint(1) DEFAULT 0,
  `max_per_order` int(11) DEFAULT 10,
  `low_stock_threshold` int(11) DEFAULT 5,
  `requires_shipping` tinyint(1) DEFAULT 1,
  `tax_code` varchar(20) DEFAULT NULL,
  `digital_file_path` varchar(500) DEFAULT NULL,
  `download_formats` longtext DEFAULT NULL,
  `preview_file_path` varchar(500) DEFAULT NULL,
  `download_limit` int(11) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `use_main_as_thumbnail` tinyint(1) DEFAULT 1,
  `options` longtext DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promo_code_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_code_usage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `promo_code_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_email` varchar(255) DEFAULT NULL,
  `discount_applied` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_promo_id` (`promo_code_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(10,2) DEFAULT NULL,
  `max_uses` int(11) DEFAULT 0,
  `max_uses_per_customer` int(11) DEFAULT 1,
  `uses` int(11) DEFAULT 0,
  `times_used` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `applies_to` enum('all','tickets','merch') DEFAULT 'all',
  `specific_event_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referral_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','paused','expired') NOT NULL DEFAULT 'active',
  `referrer_reward_type` enum('percentage','fixed_amount') NOT NULL,
  `referrer_reward_value` decimal(10,2) NOT NULL,
  `referee_reward_type` enum('percentage','fixed_amount') NOT NULL,
  `referee_reward_value` decimal(10,2) NOT NULL,
  `max_referrals_per_customer` int(10) unsigned DEFAULT NULL,
  `code_prefix` varchar(10) NOT NULL DEFAULT 'REF',
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `applies_to` enum('all','tickets','merchandise') NOT NULL DEFAULT 'all',
  `total_conversions` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_status` (`site_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referral_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `total_referrals` int(10) unsigned NOT NULL DEFAULT 0,
  `total_earnings` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  UNIQUE KEY `uk_campaign_customer` (`campaign_id`,`customer_id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_customer` (`customer_id`),
  CONSTRAINT `fk_rc_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `referral_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referral_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_conversions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `referral_code_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `referrer_customer_id` int(10) unsigned NOT NULL,
  `referee_customer_id` int(10) unsigned DEFAULT NULL,
  `referee_email` varchar(255) NOT NULL,
  `referee_order_id` int(10) unsigned DEFAULT NULL,
  `referrer_promo_code_id` int(10) unsigned DEFAULT NULL,
  `referee_promo_code_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','completed','reversed') NOT NULL DEFAULT 'pending',
  `referrer_reward_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `referee_reward_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `converted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_campaign` (`campaign_id`),
  KEY `idx_referrer` (`referrer_customer_id`),
  KEY `idx_referee_email` (`referee_email`),
  KEY `fk_conv_code` (`referral_code_id`),
  CONSTRAINT `fk_conv_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `referral_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_code` FOREIGN KEY (`referral_code_id`) REFERENCES `referral_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `square_refund_id` varchar(255) DEFAULT NULL,
  `processed_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `remember_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `restock_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `restock_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `variant_size` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `notified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `revenue_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `revenue_projections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `projection_type` enum('growth','revenue','roi','milestone') NOT NULL COMMENT 'growth=listener/follower, revenue=income streams, roi=campaign, milestone=target timeline',
  `period_months` tinyint(3) unsigned NOT NULL DEFAULT 3 COMMENT '3, 6, or 12 month projection window',
  `scenario` enum('conservative','base','optimistic') NOT NULL DEFAULT 'base',
  `projected_min` decimal(15,2) NOT NULL COMMENT 'Lower bound of projection',
  `projected_max` decimal(15,2) NOT NULL COMMENT 'Upper bound of projection',
  `confidence_pct` tinyint(3) unsigned NOT NULL DEFAULT 50 COMMENT 'Confidence percentage 0-100',
  `inputs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot of inputs used to generate this projection' CHECK (json_valid(`inputs`)),
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_projection_type` (`projection_type`),
  KEY `idx_generated_at` (`generated_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `signature_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `signature_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(10) unsigned NOT NULL,
  `site_id` varchar(50) NOT NULL,
  `signer_email` varchar(255) NOT NULL,
  `signer_name` varchar(200) DEFAULT NULL,
  `status` enum('pending','viewed','signed','declined','expired') NOT NULL DEFAULT 'pending',
  `signed_at` timestamp NULL DEFAULT NULL,
  `signer_ip` varchar(45) DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL COMMENT 'Base64 canvas signature or external provider ID',
  `external_request_id` varchar(100) DEFAULT NULL COMMENT 'HelloSign/Dropbox Sign request ID',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`document_id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_signature_document` FOREIGN KEY (`document_id`) REFERENCES `pdf_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_key` (`site_id`,`setting_key`),
  KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `platform` enum('facebook','instagram','x') NOT NULL,
  `platform_id` varchar(100) NOT NULL COMMENT 'Platform user/page/account ID',
  `account_name` varchar(255) NOT NULL COMMENT 'Display name (page name, @handle)',
  `account_type` enum('page','business','personal') NOT NULL DEFAULT 'business',
  `profile_url` varchar(500) DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `access_token_enc` text NOT NULL COMMENT 'AES-256-CBC encrypted OAuth access token',
  `refresh_token_enc` text DEFAULT NULL COMMENT 'AES-256-CBC encrypted refresh token (X only)',
  `token_expires_at` timestamp NULL DEFAULT NULL COMMENT 'NULL = never expires (Meta BISUAT)',
  `scopes` text DEFAULT NULL COMMENT 'JSON array of granted scopes',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `connected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_platform_id` (`site_id`,`platform`,`platform_id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_token_expires` (`token_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_analytics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to social_post_targets (NULL = account-level)',
  `platform` enum('facebook','instagram','x') NOT NULL,
  `metric_date` date NOT NULL,
  `impressions` int(10) unsigned NOT NULL DEFAULT 0,
  `reach` int(10) unsigned NOT NULL DEFAULT 0,
  `engagement` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'likes + comments + shares + clicks',
  `likes` int(10) unsigned NOT NULL DEFAULT 0,
  `comments` int(10) unsigned NOT NULL DEFAULT 0,
  `shares` int(10) unsigned NOT NULL DEFAULT 0,
  `clicks` int(10) unsigned NOT NULL DEFAULT 0,
  `followers` int(10) unsigned DEFAULT NULL COMMENT 'Account-level only',
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Full API response for platform-specific metrics' CHECK (json_valid(`raw_data`)),
  `fetched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_target_date` (`target_id`,`platform`,`metric_date`),
  KEY `idx_site_date` (`site_id`,`metric_date`),
  KEY `idx_account_date` (`account_id`,`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_post_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_post_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned NOT NULL COMMENT 'FK to social_post_targets',
  `scheduled_at` timestamp NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scheduled` (`status`,`scheduled_at`),
  KEY `idx_post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_post_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_post_targets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL COMMENT 'FK to social_accounts',
  `platform` enum('facebook','instagram','x') NOT NULL,
  `platform_post_id` varchar(100) DEFAULT NULL COMMENT 'Platform post/tweet ID after publishing',
  `status` enum('pending','publishing','published','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `created_by` int(10) unsigned NOT NULL COMMENT 'Admin user who created the post',
  `content_text` text DEFAULT NULL COMMENT 'Post body text/caption',
  `media_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of local media file references' CHECK (json_valid(`media_ids`)),
  `hashtags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of hashtag strings' CHECK (json_valid(`hashtags`)),
  `link_url` varchar(2048) DEFAULT NULL COMMENT 'Optional link attachment',
  `post_type` enum('text','image','video','carousel','link') NOT NULL DEFAULT 'text',
  `status` enum('draft','scheduled','publishing','published','failed','cancelled') NOT NULL DEFAULT 'draft',
  `scheduled_at` timestamp NULL DEFAULT NULL COMMENT 'When to publish (NULL = immediate)',
  `published_at` timestamp NULL DEFAULT NULL,
  `ai_suggestion_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to ai_content_suggestions if AI-generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_status` (`site_id`,`status`),
  KEY `idx_site_scheduled` (`site_id`,`scheduled_at`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `song_of_week`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `song_of_week` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `week_start` date NOT NULL,
  `platform` varchar(20) NOT NULL DEFAULT 'soundcloud',
  `track_id` varchar(64) NOT NULL,
  `note` mediumtext DEFAULT NULL,
  `reaction` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_week` (`week_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `square_webhook_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `square_webhook_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL COMMENT 'e.g. subscription.updated, payment.completed',
  `event_id` varchar(100) NOT NULL COMMENT 'Square event ID (for deduplication)',
  `square_id` varchar(100) DEFAULT NULL COMMENT 'Related Square object ID',
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`)),
  `processing_status` enum('RECEIVED','PROCESSED','FAILED','SKIPPED') NOT NULL DEFAULT 'RECEIVED',
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_event_id` (`event_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_status` (`processing_status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `step_completions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `step_completions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `user_id` int(10) unsigned NOT NULL,
  `step_id` int(10) unsigned NOT NULL,
  `pathway_id` int(10) unsigned NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_step` (`site_id`,`user_type`,`user_id`,`step_id`),
  KEY `idx_pathway` (`pathway_id`),
  KEY `fk_completion_step` (`step_id`),
  CONSTRAINT `fk_completion_pathway` FOREIGN KEY (`pathway_id`) REFERENCES `learning_pathways` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_completion_step` FOREIGN KEY (`step_id`) REFERENCES `pathway_steps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `transaction_type` enum('purchase','restock','adjustment','return','initial') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` int(10) unsigned NOT NULL,
  `square_payment_id` varchar(64) DEFAULT NULL COMMENT 'Square Payment ID',
  `square_invoice_id` varchar(64) DEFAULT NULL COMMENT 'Square Invoice ID',
  `amount_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('PAID','PENDING','FAILED','REFUNDED','VOID') NOT NULL DEFAULT 'PENDING',
  `billing_period_start` datetime DEFAULT NULL,
  `billing_period_end` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `failure_reason` varchar(500) DEFAULT NULL,
  `receipt_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sub` (`subscription_id`),
  KEY `idx_status` (`status`),
  KEY `idx_square_payment` (`square_payment_id`),
  CONSTRAINT `fk_si_sub` FOREIGN KEY (`subscription_id`) REFERENCES `customer_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plan_key` varchar(50) NOT NULL COMMENT 'Internal key: free, starter, pro, business, enterprise, ultimate',
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `tier` enum('FREE','STARTER','PRO','BUSINESS','ENTERPRISE','ULTIMATE') NOT NULL,
  `price_cents` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Monthly price in cents (0 = free)',
  `annual_price_cents` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Annual price in cents (0 = no annual option)',
  `square_plan_variation_id` varchar(64) DEFAULT NULL COMMENT 'Square Catalog plan variation ID',
  `features_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Feature overrides for this plan (merged with features.json defaults)' CHECK (json_valid(`features_json`)),
  `max_sites` int(10) unsigned NOT NULL DEFAULT 1,
  `max_storage_mb` int(10) unsigned NOT NULL DEFAULT 500,
  `max_emails_month` int(10) unsigned NOT NULL DEFAULT 1000,
  `max_products` int(10) unsigned NOT NULL DEFAULT 50,
  `max_events` int(10) unsigned NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_key` (`plan_key`),
  KEY `idx_tier` (`tier`),
  KEY `idx_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_type` enum('refund','order_issue','ticket_issue','general') NOT NULL DEFAULT 'general',
  `order_number` varchar(50) DEFAULT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','in_progress','resolved','closed') DEFAULT 'new',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `admin_notes` text DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`request_type`),
  KEY `idx_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `name` varchar(100) DEFAULT NULL,
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_data`)),
  `preview` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `ticket_code` varchar(100) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(200) DEFAULT NULL,
  `transfer_reason` varchar(500) DEFAULT NULL,
  `status` enum('pending','completed','cancelled','expired') NOT NULL DEFAULT 'pending',
  `transfer_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `initiated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL COMMENT 'Auto-cancel if not accepted',
  `transfer_token` varchar(64) DEFAULT NULL COMMENT 'Unique token for acceptance link',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_token` (`transfer_token`),
  KEY `idx_site_status` (`site_id`,`status`),
  KEY `idx_ticket` (`ticket_code`),
  KEY `idx_from` (`from_email`),
  KEY `idx_to` (`to_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `quantity_total` int(11) NOT NULL,
  `quantity_sold` int(11) DEFAULT 0,
  `min_per_order` int(11) DEFAULT 1,
  `max_per_order` int(11) DEFAULT 10,
  `quantity_reserved` int(11) DEFAULT 0,
  `min_purchase` int(10) unsigned DEFAULT 1,
  `max_purchase` int(10) unsigned DEFAULT 10,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','disabled','sold_out') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_preorder` tinyint(1) NOT NULL DEFAULT 0,
  `preorder_starts` datetime DEFAULT NULL,
  `preorder_ends` datetime DEFAULT NULL,
  `preorder_deposit` decimal(10,2) DEFAULT NULL COMMENT 'Partial payment amount; NULL = full price',
  `preorder_max` int(11) DEFAULT NULL COMMENT 'Max pre-orders; NULL = unlimited',
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) DEFAULT 'od9',
  `event_id` int(11) DEFAULT NULL,
  `ticket_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `ticket_type` varchar(50) DEFAULT 'general',
  `price` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','used','cancelled','refunded') DEFAULT 'active',
  `qr_code_path` varchar(500) DEFAULT NULL,
  `scanned_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `achievement_type_id` int(11) NOT NULL,
  `status` enum('pending_validation','granted','validated','revoked') NOT NULL DEFAULT 'granted',
  `granted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_achievement` (`site_id`,`user_id`,`user_type`,`achievement_type_id`),
  KEY `achievement_type_id` (`achievement_type_id`),
  KEY `idx_site_status` (`site_id`,`status`),
  CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`achievement_type_id`) REFERENCES `achievement_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `milestone_id` int(11) NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_milestone` (`site_id`,`user_id`,`user_type`,`milestone_id`),
  KEY `milestone_id` (`milestone_id`),
  CONSTRAINT `user_milestones_ibfk_1` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_pathway_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_pathway_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `user_id` int(10) unsigned NOT NULL,
  `pathway_id` int(10) unsigned NOT NULL,
  `current_step_id` int(10) unsigned DEFAULT NULL,
  `status` enum('enrolled','in_progress','completed','abandoned') NOT NULL DEFAULT 'enrolled',
  `progress_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_pathway` (`site_id`,`user_type`,`user_id`,`pathway_id`),
  KEY `idx_site_status` (`site_id`,`status`),
  KEY `fk_progress_pathway` (`pathway_id`),
  CONSTRAINT `fk_progress_pathway` FOREIGN KEY (`pathway_id`) REFERENCES `learning_pathways` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_streaks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_streaks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `streak_type` varchar(50) NOT NULL DEFAULT 'daily_login',
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `longest_streak` int(11) NOT NULL DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `streak_started_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_streak` (`site_id`,`user_id`,`user_type`,`streak_type`),
  KEY `idx_site_streak` (`site_id`,`streak_type`,`current_streak` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vip_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vip_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `package_type` enum('vip','bundle','upgrade') NOT NULL DEFAULT 'bundle',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compare_price` decimal(10,2) DEFAULT NULL COMMENT 'Original total before discount',
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `max_quantity` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `quantity_sold` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL COMMENT 'NULL = all events',
  `badge_text` varchar(50) DEFAULT NULL COMMENT 'e.g., VIP, EARLY BIRD, BUNDLE',
  `badge_color` varchar(7) DEFAULT '#FFD700',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_site_slug` (`site_id`,`slug`),
  KEY `idx_site_active` (`site_id`,`is_active`),
  KEY `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visitor_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `page_url` varchar(500) NOT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `is_bot` tinyint(1) DEFAULT 0,
  `country_code` varchar(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visitor_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` varchar(50) NOT NULL,
  `stat_date` date NOT NULL,
  `unique_visitors` int(11) DEFAULT 0,
  `total_page_views` int(11) DEFAULT 0,
  `unique_ips` int(11) DEFAULT 0,
  `bot_visits` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

