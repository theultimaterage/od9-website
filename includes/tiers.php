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
