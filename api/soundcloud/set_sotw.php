<?php
/**
 * set_sotw.php — set the Bunker's Song of the Week (CLI-only, idempotent per week).
 *
 * Run on prod (your action — clears the prod-write gate):
 *   sudo -u offda9 /opt/cpanel/ea-php82/root/usr/bin/php \
 *     /home/offda9/public_html/api/soundcloud/set_sotw.php [--dry-run]
 *
 * Owns the song_of_week schema (CREATE IF NOT EXISTS) — the table previously had
 * NO DDL in the repo, only bunker.php's read (created ad-hoc in Slice A). Until the
 * weekly picker cron (Slice C), change the pick by editing $TITLE/$NOTE/$REACTION
 * below and re-running. Re-run is safe: it upserts this week's row.
 */
declare(strict_types=1);

// Defense-in-depth: CLI-only, matching the two fetch.php scripts. No user input
// here (fixed pick), but the guard is free and keeps this off the HTTP surface.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }

$DRY = in_array('--dry-run', $argv, true);

foreach (['/home/offda9/config/database.php',
          __DIR__ . '/../../../config/database.php',
          __DIR__ . '/../../config/database.php'] as $c) {
    if (is_file($c)) { require_once $c; break; }
}
if (!function_exists('getDatabaseConnection')) {
    fwrite(STDERR, "set_sotw: no database config found\n"); exit(1);
}

// ---- the pick (edit to change the SotW; Slice C will automate this) ----
$PLATFORM = 'soundcloud';  // 'soundcloud' or 'spotify' — which catalog to pick from
$TITLE    = 'Traffic';  // substring matched against music_catalog.title
$NOTE     = "Here's what most people miss: the \"traffic\" is the system, not the street. "
          . "It's gridlock by design \u{2014} the loan they told you to take for a degree that ends up on a shelf, "
          . "the crash that left you fending for yourself. He calls patience \"a lie we were all told,\" and "
          . "he's not wrong \u{2014} you don't wait for the lane to clear, you fly over it. Catch the flip, too: "
          . "the system traffics dreams; he traffics the truth, straight to half the planet. And those Morpheus "
          . "bars \u{2014} \"more than meets the eyes\" \u{2014} that's an Observer waking up. This one's the whole "
          . "mission, just set to a beat. You didn't hear it from me.";
$REACTION = "Don't tell him I said this, but this one's going in the permanent stash.";
$WEEK     = date('Y-m-d', strtotime('monday this week'));

$db = getDatabaseConnection();

$db->exec("CREATE TABLE IF NOT EXISTS song_of_week (
    id INT AUTO_INCREMENT PRIMARY KEY,
    track_id VARCHAR(64) NOT NULL,
    note TEXT,
    reaction VARCHAR(512),
    week_start DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_week (week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$st = $db->prepare("SELECT track_id, title FROM music_catalog
                    WHERE platform = ? AND title LIKE ? ORDER BY title LIMIT 1");
$st->execute([$PLATFORM, '%' . $TITLE . '%']);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    fwrite(STDERR, "set_sotw: no $PLATFORM track matching '$TITLE' — run that platform's fetch first\n");
    exit(2);
}

if ($DRY) {
    echo "[dry-run] would set Song of the Week:\n";
    echo "  track:    {$row['title']} ({$row['track_id']})\n";
    echo "  week:     {$WEEK}\n";
    echo "  reaction: {$REACTION}\n";
    echo "  note:     " . substr($NOTE, 0, 80) . "...\n";
    echo "  --- song_of_week columns (live schema) ---\n";
    foreach ($db->query("DESCRIBE song_of_week") as $col) {
        echo "    {$col['Field']}  {$col['Type']}  null={$col['Null']}  default=" . var_export($col['Default'], true) . "\n";
    }
    exit(0);
}

// Constraint-agnostic upsert (delete-then-insert) so it works whether or not the
// prod table carries the uk_week unique key.
$db->prepare("DELETE FROM song_of_week WHERE week_start = ?")->execute([$WEEK]);
// created_at set explicitly: the pre-existing Slice A table has it NOT NULL with no
// default (NOW() is type-agnostic across TIMESTAMP/DATETIME; harmless on a fresh CREATE).
$db->prepare("INSERT INTO song_of_week (track_id, note, reaction, week_start, created_at) VALUES (?,?,?,?,NOW())")
   ->execute([$row['track_id'], $NOTE, $REACTION, $WEEK]);

echo "Song of the Week set: {$row['title']} (track {$row['track_id']}) for week {$WEEK}\n";
