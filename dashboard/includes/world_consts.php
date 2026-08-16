<?php
/**
 * Shared progression-world constants — the ONE definition of the zone table,
 * tier order, and gate requirements on the web side.
 *
 * Consumers: board.php (the zone map / the Gate) + world.php (the world map).
 * Extracted from board.php 2026-08-12 when world.php landed, so the two pages
 * can never drift from each other.
 *
 * Canon: docs/PROGRESSION_WORLD_SPEC.md §2 (zones as level-maps).
 * GATE / NEXT_TIER mirror the bot's config.TIER_REQUIREMENTS /
 * TIER_DIMENSION_REQUIREMENTS / CAPSTONE_PARTS — keep in sync if those change.
 */
declare(strict_types=1);

const TIER_ORDER = ['observer', 'theorist', 'architect', 'pioneer', 'benefactor'];

// ---- Per-tier zone + guide (the world). Observer -> The Wake -> Archivist. ----
// 'focus' = the plate's vertical focal point, used as CSS object-position on the
// board viewport. The viewport is a BAND (up to 6.7:1 at broadcast), so most of
// each plate is cropped away and a single global value cannot serve five
// different compositions: The Wake's figures and The Diagnostic's lone engineer
// sit low in frame and vanished entirely at the old shared 38%. Per-plate, tuned
// against the tightest crop. See docs/ZONE_PLATE_BRIEF.md §2.
const ZONES = [
    'observer'   => ['focus' => '56%', 'zone' => 'The Wake',       'img' => 'wake.jpg',       'guide' => 'The Archivist',  'guide_img' => 'archivist.jpg',  'vo' => "You're awake now. Good. Let me show you what they kept from you."],
    'theorist'   => ['focus' => '62%', 'zone' => 'The Diagnostic',  'img' => 'diagnostic.jpg', 'guide' => 'The Archivist',  'guide_img' => 'archivist.jpg',  'vo' => "Now we map the machine. Every failure has a mechanism."],
    'architect'  => ['focus' => '38%', 'zone' => 'The Forge',       'img' => 'forge.jpg',      'guide' => 'The Forgemaster','guide_img' => 'forgemaster.jpg','vo' => "Enough theory. Build something that holds weight."],
    'pioneer'    => ['focus' => '38%', 'zone' => 'The Bridge',       'img' => 'bridge.jpg',     'guide' => 'The Navigator',  'guide_img' => 'navigator.jpg',  'vo' => "You don't climb alone anymore. Lead."],
    'benefactor' => ['focus' => '38%', 'zone' => 'The Horizon',      'img' => 'horizon.jpg',    'guide' => 'The Watcher',    'guide_img' => 'watcher.jpg',    'vo' => "What you sustain outlives you. That is the point."],
];

// Gate requirements per transition — read LIVE from the bot's SQLite
// (tier_gate_requirements), which the bot rewrites from its loaded config on
// every startup. The hand-copied GATE/NEXT_TIER constants that used to live
// here are DEAD (chunk 4, 2026-08-13): the web now shows exactly what the bot
// process enforces, and a config change can no longer silently drift.
//
// Returns the same shape the old const had: [current_tier => ['credits'=>int,
// 'dims'=>[dim=>need], 'capstone'=>Label, 'capstone_parts'=>int]] keyed by the
// tier you're IN (requirements of the NEXT tier), plus a next-tier label map.
// Fail-open: an unreadable bot DB yields [] and callers blank their own gate
// widgets, never the page (house safe-read pattern).
function od9_gate_tables(?PDO $bot): array {
    $gate = [];
    $next = array_fill_keys(TIER_ORDER, null);
    if ($bot) {
        try {
            $rows = $bot->query(
                'SELECT target_tier, position, credits_required, dims_json, capstone_parts
                   FROM tier_gate_requirements ORDER BY position'
            )->fetchAll();
            $byPos = [];
            foreach ($rows as $r) { $byPos[(int)$r['position']] = $r; }
            foreach ($byPos as $pos => $r) {
                $tgt = strtolower((string)$r['target_tier']);
                if (!isset($byPos[$pos + 1])) continue;         /* summit: no gate above */
                $up = $byPos[$pos + 1];
                $dims = json_decode((string)($up['dims_json'] ?? '{}'), true);
                $gate[$tgt] = [
                    'credits'        => (int)$up['credits_required'],
                    'dims'           => is_array($dims) ? array_map('intval', $dims) : [],
                    'capstone'       => ucfirst($tgt),
                    'capstone_parts' => (int)$r['capstone_parts'],
                ];
                $next[$tgt] = ucfirst(strtolower((string)$up['target_tier']));
            }
        } catch (Throwable $e) {
            error_log('[world_consts] gate table read failed: ' . $e->getMessage());
        }
    }
    return [$gate, $next];
}
// The five value dimensions, member-facing. Order = relevance (the two Observer-
// gate dims first; consciousness flagged scarce). `sub` says what verifiably earns
// each — the honest framing that this is value, not XP: it comes only from
// evaluated contributions, never presence (Manifesto Vol.5 Ch.49 verification-first).
const DIMS = [
    'knowledge'     => ['label' => 'Knowledge',     'sub' => 'evaluated learning',  'scarce' => false, 'desc' => 'Your grasp of the OD9 frameworks — from content you read and discussions that pass evaluation.'],
    'consciousness' => ['label' => 'Consciousness', 'sub' => 'verified reflection',  'scarce' => true,  'desc' => 'Depth of reflection and growth. The scarcest dimension — earned only from evaluated reflections, taught sessions, and capstones.'],
    'community'     => ['label' => 'Community',      'sub' => 'lifting others',       'scarce' => false, 'desc' => 'How you lift the collective — think tanks, research cells, mentoring, and referrals.'],
    'resource'      => ['label' => 'Resource',       'sub' => 'material support',  'scarce' => false, 'emerge' => 'live for supporters — opens wider soon', 'desc' => 'Materially resourcing the mission — supporter contributions build it. Deliberately adds ZERO governance vote weight: support is recognized, influence is earned.'],
    'system'        => ['label' => 'System',         'sub' => 'upkeep + review',      'scarce' => false, 'emerge' => 'unlocks at Theorist',        'desc' => 'Maintaining and improving the protocol — reviewing work and shipping projects. Opens at Theorist and above.'],
];
