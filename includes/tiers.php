<?php
/**
 * Canonical web tier colors — the ICED design-system ladder (founder decision
 * 2026-08-13: "flip the tier colors to the iced ladder everywhere" — the
 * one-place change this file's original header anticipated).
 *
 * Single source of truth for tier colors across the OD9 web surface:
 *   me.php / dashboard/profile.php / library.php / tiers.php / framework.php
 *   / index.php (tiers-preview).
 *
 * MIRROR-SET (change all in the same commit or drift returns):
 *   - design-system/tokens/colors.css  (--t-* — the MASTER, locked 2026-06-16)
 *   - scripts/web/css/od9.css          (--t-* site tokens)
 *   - THIS FILE                        (PHP-side values for od9_tier_color())
 *
 * The ladder climbs to gold at Pioneer — the "iced out" tier — deliberately
 * NOT the saturated #F1C40F. The bot's config.TIER_COLORS (Discord role
 * colors) now INTENTIONALLY differs: web went iced by founder decision;
 * Discord keeps its role colors until that surface is separately flipped.
 *
 * Guarded so multiple includes in one request are safe (same convention as
 * includes/env.php), since some pages already require several includes.
 */

if (!defined('OD9_TIER_COLORS_LOADED')) {
    define('OD9_TIER_COLORS_LOADED', true);

    /** Lowercase tier slug => canonical hex (the iced design-system ladder). */
    $GLOBALS['OD9_TIER_COLORS'] = [
        'observer'   => '#8A9499',  // Gray
        'theorist'   => '#2E8BFF',  // Blue
        'architect'  => '#9B5CFF',  // Violet (soft)
        'pioneer'    => '#E5B53A',  // Iced gold — the ladder's arrival
        'benefactor' => '#E5483A',  // Crimson
    ];

    /** Progression order (mirror of config.TIER_ORDER). */
    $GLOBALS['OD9_TIER_ORDER'] = ['observer', 'theorist', 'architect', 'pioneer', 'benefactor'];

    /** Display labels, title-cased. */
    $GLOBALS['OD9_TIER_LABELS'] = [
        'observer'   => 'Observer',
        'theorist'   => 'Theorist',
        'architect'  => 'Architect',
        'pioneer'    => 'Pioneer',
        'benefactor' => 'Benefactor',
    ];
}

if (!function_exists('od9_tier_color')) {
    /**
     * Canonical color for a tier slug (case-insensitive), with a safe fallback
     * to the Observer gray for any unknown/empty slug. Use this instead of
     * indexing OD9_TIER_COLORS directly so callers never emit an empty string.
     */
    function od9_tier_color(?string $slug): string
    {
        $key = strtolower(trim((string)$slug));
        return $GLOBALS['OD9_TIER_COLORS'][$key] ?? $GLOBALS['OD9_TIER_COLORS']['observer'];
    }
}

if (!function_exists('od9_tier_css_vars')) {
    /**
     * Emit the canonical tier colors as `--tier-<slug>` CSS custom properties,
     * for inline <style> blocks to reference via var(--tier-benefactor) etc.
     * Returns a string of `--tier-observer:#8A9499;...` (no selector wrapper) so
     * the caller controls the scope (e.g. inside `:root{ ... }`).
     */
    function od9_tier_css_vars(): string
    {
        $out = '';
        foreach ($GLOBALS['OD9_TIER_COLORS'] as $slug => $hex) {
            $out .= '--tier-' . $slug . ':' . $hex . ';';
        }
        return $out;
    }
}

if (!function_exists('od9_tier_order')) {
    /**
     * THE canonical progression order for the web surface.
     *
     * This literal was written out in SIX places — world_consts.php,
     * board-action.php twice, dashboard/index.php, dashboard/presence.php and
     * here — every one of them a hand-kept mirror of the bot's
     * config.TIER_ORDER, and the project rule is explicit that tier order is
     * never hardcoded. They all agreed when measured on 2026-09-14, which is
     * exactly the state the Atlas's canon set was in that same morning.
     *
     * NOT the same list as includes/patron_gate.php's TIER_ORDER, and they must
     * not be merged: that one is the PATREON gating ladder and legitimately
     * carries a sixth rank, `founding` ($100/mo Founding Patron), which is not a
     * progression tier and is not in the bot's config.TIER_ORDER. Two
     * vocabularies that share five names are still two vocabularies.
     */
    function od9_tier_order(): array
    {
        return $GLOBALS['OD9_TIER_ORDER'];
    }

    /** Is this one of the five progression tiers? Replaces in_array(..., [literal], true). */
    function od9_is_tier(?string $slug): bool
    {
        return in_array(strtolower(trim((string)$slug)), $GLOBALS['OD9_TIER_ORDER'], true);
    }
}

// ---------------------------------------------------------------------------
// Selftest: the web's order must equal the BOT's, and the bot publishes it.
// tier_gate_requirements carries a `position` column that the bot rewrites from
// its loaded config on every startup, so the authority is live rather than
// remembered — the same projection the board already reads its gates from.
// Runtime stays pure (this file is included by public pages with no DB), so the
// comparison happens only here, under CLI.
// ---------------------------------------------------------------------------
if (PHP_SAPI === 'cli'
    && in_array('--selftest', $argv ?? [], true)
    && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $fail = 0;
    $web  = od9_tier_order();
    printf("  web order : %s\n", implode(' < ', $web));

    if (count($web) !== count(array_unique($web)) || $web !== array_values(array_filter($web, 'is_string'))) {
        echo "  FAIL the web order is not a clean list of unique strings\n"; $fail++;
    }
    foreach (['observer' => true, 'founding' => false] as $slug => $want) {
        if (od9_is_tier($slug) !== $want) {
            printf("  FAIL od9_is_tier('%s') should be %s\n", $slug, var_export($want, true));
            $fail++;
        }
    }

    $cfg = __DIR__ . '/../dashboard/includes/config.php';
    $db  = null;
    if (is_readable($cfg)) { require_once $cfg; $db = defined('OD9_BOT_DB_PATH') ? OD9_BOT_DB_PATH : null; }
    if ($db === null || !is_readable((string)$db)) {
        printf("  SKIP bot comparison: bot DB not readable (%s) — UNCHECKED, not clean\n", $db ?? 'no config');
    } else {
        $pdo = new PDO('sqlite:' . $db, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $bot = $pdo->query("SELECT target_tier FROM tier_gate_requirements ORDER BY position")
                   ->fetchAll(PDO::FETCH_COLUMN);
        printf("  bot order : %s  (%s)\n", implode(' < ', $bot), basename((string)$db));
        if (!$bot) {
            echo "  FAIL tier_gate_requirements is empty — the bot has not published an order\n"; $fail++;
        } elseif ($bot !== $web) {
            echo "  FAIL the web order does NOT match the bot's published order\n"; $fail++;
        } else {
            echo "  ok   the web order matches the bot's live projection exactly\n";
        }
    }
    printf("\ntiers selftest: %s\n", $fail ? "$fail FAILURE(S)" : 'PASS');
    exit($fail ? 1 : 0);
}
