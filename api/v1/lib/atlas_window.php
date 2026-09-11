<?php
/**
 * Atlas beacon events in a UTC window — the pure half of api/v1/atlas-window.php.
 *
 * atlas-ping.php appends one JSON line per event to <dir>/YYYY-MM-DD.jsonl
 * ({ts, sid, event, vp, props}). This reads only the day files the window can
 * touch, filters on each event's own `ts` (files are named by UTC day, a window
 * is not), skips probe events (props.probe), and counts distinct sessions.
 * Testable without a server: tests/test_atlas_window.php.
 */
declare(strict_types=1);

const ATLAS_WINDOW_MAX_DAYS = 14;

function atlas_window_summary(string $dir, string $since, string $until): array
{
    $s = strtotime($since);
    $u = strtotime($until);
    if ($s === false || $u === false) {
        throw new InvalidArgumentException('since/until must be ISO-8601 timestamps');
    }
    if ($u < $s) {
        throw new InvalidArgumentException('until precedes since');
    }
    if ($u - $s > ATLAS_WINDOW_MAX_DAYS * 86400) {
        throw new InvalidArgumentException('window longer than ' . ATLAS_WINDOW_MAX_DAYS . ' days');
    }

    $files = 0;
    $events = 0;
    $arrivals = 0;
    $sids = [];
    $byEvent = [];
    for ($d = strtotime(gmdate('Y-m-d', $s) . 'T00:00:00Z'); $d <= $u; $d += 86400) {
        $path = $dir . '/' . gmdate('Y-m-d', $d) . '.jsonl';
        if (!is_file($path)) {
            continue;
        }
        $fh = fopen($path, 'r');
        if ($fh === false) {
            throw new RuntimeException("cannot open $path");
        }
        $files++;
        while (($line = fgets($fh)) !== false) {
            $j = json_decode($line, true);
            if (!is_array($j) || empty($j['ts'])) {
                continue;
            }
            $t = strtotime((string)$j['ts']);
            if ($t === false || $t < $s || $t > $u) {
                continue;
            }
            if (!empty($j['props']['probe'])) {
                continue;
            }
            $events++;
            $ev = (string)($j['event'] ?? '');
            $byEvent[$ev] = ($byEvent[$ev] ?? 0) + 1;
            $sid = (string)($j['sid'] ?? '');
            if ($sid !== '') {
                $sids[$sid] = true;
            }
            if ($ev === 'arrive') {
                $arrivals++;
            }
        }
        fclose($fh);
    }
    ksort($byEvent);
    return [
        'since'      => gmdate('Y-m-d\TH:i:s\Z', $s),
        'until'      => gmdate('Y-m-d\TH:i:s\Z', $u),
        'files_read' => $files,
        'events'     => $events,
        'sessions'   => count($sids),
        'arrivals'   => $arrivals,
        'by_event'   => $byEvent,
    ];
}
