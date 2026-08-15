<?php
/**
 * Progress-rail labels + chapters — the data behind BOARD_REDESIGN_SPEC §6.1.
 *
 * WHY THIS FILE EXISTS. The rail replaces the helix, so every stop needs a
 * label short enough to render without shrinking the marks. Module titles are
 * written for a reading surface, not a rail: "Kurzgesagt: Why Are You Alive –
 * Life, Energy & ATP" is 49 characters, and naive truncation yields
 * "Kurzgesagt: Why Ar…", which names the publisher instead of the lesson.
 * So labels are CURATED here, with a total algorithmic fallback so a newly
 * seeded module can never render an overflowing or empty stop.
 *
 * AUTHORING RULES (apply in order; keep new labels consistent with these):
 *   1. Ceiling is RAIL_LABEL_MAX chars, wrapping to at most two lines.
 *   2. Strip the publisher/source prefix — "Kurzgesagt:", "PBS Space Time:",
 *      "The Long Now:". The source is not the lesson.
 *   3. Strip "Capstone Part N:" scaffolding; the part number carries it.
 *   4. Drop a leading "The" unless the phrase is iconic without it
 *      ("The Creed", "The Great Filter", "The Stakes", "The Window").
 *   5. Keep the distinctive noun phrase — a member scanning the rail must be
 *      able to tell two stops apart at a glance.
 *
 * SIZING NOTE (spec §6.1 corrected 2026-08-15): the original spec assumed
 * 6-8 stops per tier and an 18-char cap. The live curriculum is Observer 33,
 * Theorist 20, Architect 5, Pioneer 5, Benefactor 4 — so the rail is
 * two-level (chapters, then the current chapter's stops) and the cap is 22,
 * which is what a ~94px stop fits over two lines at --fs-label.
 *
 * Self-test:  php scripts/web/dashboard/includes/rail-labels.php --selftest
 */

const RAIL_LABEL_MAX = 22;

/** module_id => rail label. Curated; see the authoring rules above. */
const RAIL_LABELS = [
    // --- Observer: Arrival ---
    'observer.watch.arrived' => 'Arrival',
    // --- Observer: Foundations ---
    'observer.read.1'        => 'Welcome to OD9',
    'observer.read.44'       => 'The Creed',
    'observer.read.45'       => 'Thesean Method',
    'observer.read.47'       => 'Evidence Standard',
    'observer.read.48'       => 'Love as Infrastructure',
    'observer.read.2'        => 'Nature of Observation',
    'observer.read.3'        => 'Core Vocabulary',
    'observer.read.4'        => 'The Four Pillars',
    'observer.read.5'        => 'Using This System',
    'observer.read.6'        => 'You Are Here',
    // --- Observer: The Cosmic Frame ---
    'observer.read.7'        => 'Kardashev Framework',
    'observer.read.53'       => 'Kardashev Scale',
    'observer.read.54'       => 'Why Are You Alive',
    'observer.read.8'        => 'The Great Filter',
    'observer.read.55'       => 'Fermi Paradox',
    'observer.read.9'        => 'The Bottleneck',
    'observer.read.10'       => 'The Stakes',
    'observer.read.11'       => 'The Window',
    'observer.read.56'       => '10,000-Year Clock',
    'observer.read.46'       => 'Twin Singularities',
    // --- Observer: Crisis & Response ---
    'observer.read.49'       => 'System in Crisis',
    'observer.read.50'       => 'Responsible Change',
    'observer.read.51'       => 'Solution Framework',
    'observer.read.52'       => 'Vision to Action',
    'observer.read.12'       => 'What You Can Do',
    'observer.read.13'       => 'Emotional Landing',
    // --- Observer: Capstone (human-reviewed; the hard gate) ---
    'observer.capstone.14'   => '1 · Four Barriers',
    'observer.capstone.15'   => '2A · Apocalyptic',
    'observer.capstone.16'   => '2B · Exploitation',
    'observer.capstone.17'   => '2C · Info Control',
    'observer.capstone.18'   => '2D · Impairment',
    'observer.capstone.19'   => '3 · Synthesis',
];

/**
 * Chapters per zone: [label, first_position, last_position].
 * A zone with no entry renders a FLAT rail (correct for Architect/Pioneer/
 * Benefactor, which have 4-5 required modules). A zone with more stops than
 * the flat budget gets chapters so the rail never shrinks its marks.
 */
const RAIL_CHAPTERS = [
    'observer' => [
        ['Arrival',           0,  0],
        ['Foundations',       1, 10],
        ['The Cosmic Frame', 11, 20],
        ['Crisis & Response', 21, 26],
        ['Capstone',         27, 32],
    ],
];

/** Above this many required stops, a zone needs chapters (spec §6.1). */
const RAIL_FLAT_MAX = 8;

/** Stops shown at the broadcast breakpoint before the rail windows (spec §8).
 *  Measured, not guessed: at x1.5 type a full chapter runs under the chyron. */
const RAIL_BROADCAST_MAX = 6;

/** Publisher/source prefixes stripped by the fallback (rule 2). */
const RAIL_STRIP_PREFIXES = [
    'Kurzgesagt:', 'PBS Space Time:', 'The Long Now:', 'Space.com:',
    'Astronomy.com:', 'Cool Worlds:', 'Wait But Why:', 'Isaac Arthur:',
    'Carl Sagan:',
];

/**
 * Rail label for a module. TOTAL by construction: always returns a non-empty
 * string of at most RAIL_LABEL_MAX chars, so a module seeded after this file
 * was last curated still renders a usable stop instead of an overflowing or
 * blank one.
 */
function rail_label(string $moduleId, string $title = ''): string
{
    if (isset(RAIL_LABELS[$moduleId])) {
        return RAIL_LABELS[$moduleId];
    }
    $t = trim($title);
    foreach (RAIL_STRIP_PREFIXES as $p) {
        if (stripos($t, $p) === 0) {
            $t = trim(substr($t, strlen($p)));
            break;
        }
    }
    // "Capstone Part 2A: Foo" -> "2A · Foo"  (rule 3)
    if (preg_match('/^Capstone Part\s+([0-9]+[A-Z]?)\s*[:\-]\s*(.+)$/i', $t, $m)) {
        $t = $m[1] . ' · ' . $m[2];
    }
    // an em/en-dash or colon subtitle: keep the head, it is the distinctive part
    $t = preg_split('/\s+[–—:]\s+/u', $t)[0] ?? $t;
    $t = trim($t) !== '' ? trim($t) : trim($title);
    if ($t === '') {
        $t = $moduleId;                      // last resort: never empty
    }
    if (mb_strlen($t) <= RAIL_LABEL_MAX) {
        return $t;
    }
    // truncate on a word boundary, leaving room for the ellipsis
    $cut = mb_substr($t, 0, RAIL_LABEL_MAX - 1);
    $sp  = mb_strrpos($cut, ' ');
    if ($sp !== false && $sp >= 8) {
        $cut = mb_substr($cut, 0, $sp);
    }
    return rtrim($cut, " ,.;:-–—") . '…';
}

/** Chapter index (0-based) containing $position, or null when flat. */
function rail_chapter_for(string $zone, int $position): ?int
{
    foreach ((RAIL_CHAPTERS[$zone] ?? []) as $i => [$label, $from, $to]) {
        if ($position >= $from && $position <= $to) {
            return $i;
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// Self-test. A label file that silently grows an over-long entry is exactly
// the kind of rot the render check cannot see until it ships, so prove BOTH
// halves here: every curated label is within budget, AND the fallback is total
// against the ugliest real titles in the live curriculum.
// ---------------------------------------------------------------------------
if (PHP_SAPI === 'cli' && in_array('--selftest', $argv ?? [], true)) {
    $fail = 0;
    foreach (RAIL_LABELS as $id => $label) {
        $n = mb_strlen($label);
        if ($n > RAIL_LABEL_MAX || $label === '') {
            printf("  FAIL curated %-28s %2d chars  %s\n", $id, $n, $label);
            $fail++;
        }
    }
    // real titles from the live modules table, including the worst offenders
    $adversarial = [
        'Kurzgesagt: Why Are You Alive – Life, Energy & ATP',
        'PBS Space Time: How Can Humanity Become a Kardashev Type 1 Civilization?',
        'Astronomy.com: The Great Filter — A Possible Solution to the Fermi Paradox',
        'Capstone Part 2B: Economic Exploitation',
        'The Evolution of Trust (interactive game theory of cooperation)',
        'Kurzgesagt: A Selfish Argument for Making the World a Better Place (Egoistic Altruism)',
        'Coordination - The Bottleneck',
        '',                                   // empty title must still resolve
        '                ',                   // whitespace-only
        'Supercalifragilisticexpialidocious',  // single unbreakable word
    ];
    foreach ($adversarial as $t) {
        $out = rail_label('zzz.not.curated.' . md5($t), $t);
        $n   = mb_strlen($out);
        $bad = ($n > RAIL_LABEL_MAX || trim($out) === '');
        if ($bad) { $fail++; }
        printf("  %s fallback %2d  %-24s <- %s\n", $bad ? 'FAIL' : ' ok ', $n, $out,
               mb_substr($t, 0, 46));
    }
    // the shortener must actually SHORTEN (a no-op shortener passes vacuously)
    $long = rail_label('zzz.vacuity', 'Kurzgesagt: Why Are You Alive – Life, Energy & ATP');
    if (mb_strlen($long) >= 49) { echo "  FAIL vacuity: shortener did not shorten\n"; $fail++; }

    printf("\nrail-labels selftest: %d curated, %s\n",
           count(RAIL_LABELS), $fail ? "$fail FAILURE(S)" : 'PASS');
    exit($fail ? 1 : 0);
}
