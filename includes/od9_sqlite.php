<?php
declare(strict_types=1);
/**
 * od9_sqlite.php — read the bot's LIVE SQLite directly (same cPanel box),
 * replacing the 15-min `od9_bot_*` MySQL mirror for member-facing reads.
 *
 * WHY
 * ---
 * The bot writes progression data (users, user_dimensions, ...) to
 * /home/ultimaterage/od9-discord-bot/data/od9.db, which is world-readable. offda9's
 * PHP can open it directly — proven in production by api/v1/pulse.php. Reading it
 * live removes the 15-minute staleness of the sync_to_mysql.py mirror, which has
 * caused tier-gating bugs (a just-promoted member seeing the old gate, e.g. the
 * 2026-06-13 "Benefactor sees only Architect" incident).
 *
 * The od9_bot_* MySQL mirror (sync_to_mysql.py + the od9_bot_* tables) was RETIRED
 * 2026-06-28 — this live read replaced it. od9_member_source() is now SQLite-only:
 * if the live DB is unreachable it returns a null connection and callers fail safe
 * (a gate denies, a display shows "unavailable") rather than serving stale data.
 *
 * The SQLite path needs NO database credentials (it's a local file), so it also
 * sidesteps the MySQL password-rotation drift the mirror path was exposed to.
 */

if (!defined('OD9_SQLITE_PATH')) {
    define('OD9_SQLITE_PATH', '/home/ultimaterage/od9-discord-bot/data/od9.db');
}

/**
 * A shared read PDO to the bot's SQLite, or null if the DB isn't on this host
 * (e.g. local XAMPP, where the path doesn't exist) so callers can fall back.
 * Matches the proven api/v1/pulse.php connection. Writes are impossible anyway —
 * offda9 has read-only access to the file.
 */
function getOd9SqliteConnection(): ?PDO {
    static $pdo = null;
    static $tried = false;
    if ($tried) {
        return $pdo;
    }
    $tried = true;
    if (!is_file(OD9_SQLITE_PATH)) {
        return $pdo = null;   // not on this host — caller uses the MySQL fallback
    }
    try {
        $pdo = new PDO('sqlite:' . OD9_SQLITE_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Wait up to 5s for a lock instead of failing instantly with SQLITE_BUSY
        // if the bot is mid-write. Matches the bot's own busy_timeout=5000. Once
        // the MySQL mirror fallback is retired, this is what keeps a write-burst
        // from turning a member read into an error.
        $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
    } catch (Throwable $e) {
        error_log('[od9_sqlite] connect failed: ' . $e->getMessage());
        $pdo = null;
    }
    return $pdo;
}

/**
 * Pick the member-data source. Returns [PDO|null $conn, 'users', 'user_dimensions'].
 * The od9_bot_* MySQL mirror was RETIRED 2026-06-28 — the bot's live SQLite is now
 * the only source. $conn is null only if the live DB is unreachable/unreadable, in
 * which case callers fail safe (a gate denies; a display shows "unavailable").
 * Never throws.
 */
function od9_member_source(): array {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $sqlite = getOd9SqliteConnection();
    if ($sqlite) {
        try {
            $sqlite->query('SELECT 1 FROM users LIMIT 1');   // probe: actually readable?
            return $cached = [$sqlite, 'users', 'user_dimensions'];
        } catch (Throwable $e) {
            error_log('[od9_sqlite] live read failed: ' . $e->getMessage());
        }
    }
    return $cached = [null, 'users', 'user_dimensions'];
}
