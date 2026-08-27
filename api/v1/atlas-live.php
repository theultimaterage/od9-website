<?php
/**
 * atlas-live.php — the Atlas's live beacon.
 *
 * Returns the currently DESIGNATED lesson's content_id so the Atlas can light
 * the "LIVE LESSON" node (the same designation the go-live announce and the
 * Think Tank use — bot table tt_lesson_state, read live per the standard
 * web→bot-SQLite pattern, see api/v1/pulse.php).
 *
 * PUBLIC + read-only by design (Atlas decision 2026-08-20: structure public;
 * the designated lesson is announced publicly on every go-live anyway).
 * Fail-open: any error returns {"designated":null} — the map just shows no
 * beacon. 60s cache keeps this endpoint invisible to the box.
 */
declare(strict_types=1);

const SQLITE_PATH = '/home/ultimaterage/od9-discord-bot/data/od9.db';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');

$out = ['designated' => null];
try {
    if (is_readable(SQLITE_PATH)) {
        $db = new SQLite3(SQLITE_PATH, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(2000);
        $row = $db->querySingle('SELECT lesson_content_id FROM tt_lesson_state LIMIT 1');
        if ($row !== null && $row !== false) {
            $out['designated'] = (int)$row;
        }
        $db->close();
    }
} catch (Throwable $e) {
    /* fail-open: beacon dark */
}
echo json_encode($out);
