<?php
/**
 * download.php — auth-gated PDF proxy for the OD9 Pedagogical Library.
 *
 * Observer + Theorist tiers are public and served directly by Apache. This
 * proxy is the ONLY path to a GATED tier's PDF (Architect / Pioneer /
 * Benefactor), whose directories deny direct HTTP access in
 * public/library/.htaccess. It verifies the visitor's tier via patron_gate
 * BEFORE streaming any bytes — so the exclusivity is real, not a hidden link.
 *
 *   download.php?tier=architect&file=Architect-01-Coordination-Mechanism-Design.pdf
 *
 * Added 2026-06-05 to close the hole where gated PDFs sat at static, guessable
 * paths (and were enumerated by the public Master Table of Contents).
 */
declare(strict_types=1);

// includes/ is a repo-root sibling of the docroot locally but lives INSIDE it
// on prod (public_html/includes/), so resolve patron_gate.php from either.
$gatePath = null;
foreach ([__DIR__ . '/includes/patron_gate.php', __DIR__ . '/../includes/patron_gate.php'] as $cand) {
    if (is_file($cand)) { $gatePath = $cand; break; }
}
if ($gatePath === null) {
    // Fail CLOSED — never serve gated content if the gate can't be loaded.
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Library temporarily unavailable.\n");
}
require_once $gatePath;

const LIBRARY_DIR  = __DIR__ . '/library';
const PUBLIC_TIERS = ['observer', 'theorist'];

$tier = strtolower(trim((string)($_GET['tier'] ?? '')));
$file = (string)($_GET['file'] ?? '');

// Validate: tier must be a known key; file must be a bare *.pdf with no path parts.
if (!isset(TIER_ORDER[$tier])
    || basename($file) !== $file
    || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.pdf$/', $file)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Bad request.\n");
}

// Resolve and confirm the file is genuinely inside LIBRARY_DIR (anti-traversal).
$real = realpath(LIBRARY_DIR . '/' . $tier . '/' . $file);
$base = realpath(LIBRARY_DIR);
if ($real === false || $base === false
    || strncmp($real, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Not found.\n");
}

// Authorize: public tiers pass; gated tiers require that tier or higher.
// require_min_tier() renders the styled "log in / upgrade" gate page + exit on failure.
if (!in_array($tier, PUBLIC_TIERS, true)) {
    require_min_tier($tier, 'This document is gated to ' . ucfirst($tier) . '+ supporters.');
}

// Authorized — stream the PDF inline.
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($real));
header('Content-Disposition: inline; filename="' . basename($real) . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');          // gated docs must never enter a search index
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($real);
exit;
