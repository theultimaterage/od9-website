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
/* LIVE AS AN EVENT (2026-09-04, the spectacle brief's move 6): the same
   endpoint also carries what the box already knows about tonight —
   the call-in door's state (published by the bot to callin/state.json),
   the show kit's words for today (headline + DM COURT's pinned question,
   shipped by tools/build_show_kit.py beside the staged thumbnail), and the
   most recent go-live notification (the VOD link after the show). Every
   piece fails open on its own. ATLAS_LIVE_FIXTURES=<dir> (env) reads
   state.json / words.json from that dir instead — the local test seam. */
const DOOR_STATE  = '/home/offda9/public_html/callin/state.json';
const KIT_DIR     = '/home/ultimaterage/od9-discord-bot/assets/thumbnails';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');

$fixtures = getenv('ATLAS_LIVE_FIXTURES') ?: '';
$out = ['designated' => null, 'door' => null, 'show' => null, 'last_live' => null];

function atlas_live_json(string $path): ?array
{
    if (!is_readable($path)) return null;
    $j = json_decode((string)@file_get_contents($path), true);
    return is_array($j) ? $j : null;
}

try {
    if ($fixtures === '' && is_readable(SQLITE_PATH)) {
        $db = new SQLite3(SQLITE_PATH, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(2000);
        $row = $db->querySingle('SELECT lesson_content_id FROM tt_lesson_state LIMIT 1');
        if ($row !== null && $row !== false) {
            $out['designated'] = (int)$row;
        }
        $last = $db->querySingle(
            "SELECT title, url, notified_at FROM content_notifications "
            . "WHERE platform IN ('stream_start','youtube_live') ORDER BY notified_at DESC LIMIT 1", true);
        if (is_array($last) && !empty($last['url'])) {
            $out['last_live'] = ['title' => (string)$last['title'], 'url' => (string)$last['url'],
                                 'at' => (string)$last['notified_at']];
        }
        $db->close();
    }
} catch (Throwable $e) {
    /* fail-open: beacon dark */
}

try {
    $door = atlas_live_json($fixtures !== '' ? $fixtures . '/state.json' : DOOR_STATE);
    if ($door !== null) {
        $out['door'] = [
            'open'         => !empty($door['open']),
            'reason'       => (string)($door['reason'] ?? ''),
            'video_id'     => $door['video_id'] ?? null,
            'watch_url'    => $door['watch_url'] ?? null,
            'title'        => $door['title'] ?? null,
            'opened_at'    => $door['opened_at'] ?? null,
            'next_show_utc'=> $door['next_show_utc'] ?? null,
            'next_show_ct' => $door['next_show_ct'] ?? null,
        ];
    }
} catch (Throwable $e) {
    /* fail-open: no door */
}

try {
    $today = (new DateTime('now', new DateTimeZone('America/Chicago')))->format('Y-m-d');
    if ($fixtures !== '') {
        $words = atlas_live_json($fixtures . '/words.json');
    } else {
        $hits  = glob(KIT_DIR . '/ncz-' . $today . '-*.words.json') ?: [];
        $words = $hits ? atlas_live_json($hits[count($hits) - 1]) : null;
    }
    if ($words !== null && !empty($words['headline'])) {
        $out['show'] = [
            'day'      => (string)($words['day'] ?? $today),
            'headline' => (string)$words['headline'],
            'pin'      => (string)($words['pin'] ?? ''),
            'tags'     => array_values(array_filter((array)($words['tags'] ?? []), 'is_string')),
            'receipt'  => (string)($words['receipt'] ?? ''),
        ];
    }
} catch (Throwable $e) {
    /* fail-open: no show words */
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
