<?php
/**
 * Canonical web tier colors — MIRROR of config.TIER_COLORS (keep in sync).
 *
 * Single source of truth for tier colors across the OD9 web surface:
 *   me.php / dashboard/profile.php / library.php / tiers.php.
 *
 * These hexes MIRROR the bot's `config.TIER_COLORS` (config.py) so the website
 * matches the live Discord role colors. If config.TIER_COLORS changes, update
 * the values here too — same "mirror of config.X — keep in sync" convention
 * used by dashboard/board.php (GATE / NEXT_TIER consts).
 *
 * Canonical values (config.py:163):
 *   observer   #808080  Gray
 *   theorist   #3498DB  Blue
 *   architect  #9B59B6  Purple
 *   pioneer    #F1C40F  Gold
 *   benefactor #E74C3C  Crimson
 *
 * Future: these can later be refined to the iced-out `--t-*` ladder in
 * design-system/tokens/colors.css as a one-place change here — but for now they
 * deliberately use the config values so web == Discord.
 *
 * Guarded so multiple includes in one request are safe (same convention as
 * includes/env.php), since some pages already require several includes.
 */

if (!defined('OD9_TIER_COLORS_LOADED')) {
    define('OD9_TIER_COLORS_LOADED', true);

    /** Lowercase tier slug => canonical hex string (mirror of config.TIER_COLORS). */
    $GLOBALS['OD9_TIER_COLORS'] = [
        'observer'   => '#808080',  // Gray
        'theorist'   => '#3498DB',  // Blue
        'architect'  => '#9B59B6',  // Purple
        'pioneer'    => '#F1C40F',  // Gold
        'benefactor' => '#E74C3C',  // Crimson
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
     * Returns a string of `--tier-observer:#808080;...` (no selector wrapper) so
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
