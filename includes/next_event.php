<?php
/**
 * OD9 - next scheduled event partial (belonging-first funnel).
 * The single shared source of "what's the next room" for every entry surface
 * (homepage hero, verify.php, dashboard header, footer). Reads the SAME
 * community_events table the bot's board + events.php use.
 *
 * od9_next_event(): ?array        - next dated event row, or null.
 * od9_next_event_label(): string  - "Sat Aug 8 - Game Night", or the standing
 *                                   daily ritual fallback. NEVER empty: every
 *                                   surface can always point at a room.
 *
 * Degrades silently (error_log only) - the belonging door must never 500 a page.
 */

function od9_next_event(): ?array {
    static $cached = false, $event = null;
    if ($cached) {
        return $event;
    }
    $cached = true;
    try {
        require_once __DIR__ . '/od9_sqlite.php';
        [$botDb] = od9_member_source();
        if ($botDb) {
            $stmt = $botDb->query(
                "SELECT event_date, title, event_type FROM community_events
                 WHERE event_date >= date('now')
                 ORDER BY event_date ASC, event_id ASC LIMIT 1"
            );
            $rows = $stmt ? $stmt->fetchAll() : [];
            $event = $rows ? $rows[0] : null;
        }
    } catch (Throwable $e) {
        error_log('[next_event] read failed: ' . $e->getMessage());
        $event = null;
    }
    return $event;
}

function od9_next_event_label(): string {
    $ev = od9_next_event();
    if ($ev) {
        $d = DateTime::createFromFormat('Y-m-d', (string)$ev['event_date']);
        $when = $d ? $d->format('D M j') : (string)$ev['event_date'];
        return $when . ' — ' . (string)$ev['title'];
    }
    // The standing daily room - true every day of the week (events.php rhythm).
    return 'Think Tank — tonight, 6 PM CT';
}
