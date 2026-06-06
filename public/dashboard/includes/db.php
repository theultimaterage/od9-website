<?php
/**
 * OD9 Dashboard DB bootstrap.
 *
 * Loads the env-aware database config (config/database.php), resolving the
 * local-vs-prod path (config sits at a different depth on prod — the
 * config-inside-docroot quirk). profile.php + settings.php require this to get
 * getDatabaseConnection().
 *
 * HISTORY: this file used to hold the dashboard_* sync read/write helpers — the
 * orphan "second sync" (see migrations/001_dashboard_tables.sql + the dead
 * public/dashboard/api/sync.php). That sync was never a live data source: the
 * dashboard and profile.php read the bot data directly, and profile visibility
 * lives in od9_profile_visibility (migrations/003). The dashboard_* helpers had
 * no remaining caller after the 2026-06 consolidation and were removed with the
 * sync endpoint.
 */

$configPath = __DIR__ . '/../../../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../../config/database.php';
}
require_once $configPath;
