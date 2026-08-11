<?php
/**
 * ASCEND Guide Video Player
 *
 * URL: offda9.com/guide.php?key={trigger_key}
 * Videos served from /guide-videos/{filename}
 *
 * Trigger keys are sent as Discord DM button links by utils/guide_videos.py.
 * Each key maps to a character, title, description, and video file.
 */

// ── Video registry (single source of truth; shared with guide-gallery.php) ──────
require __DIR__ . '/guide-registry.php';

// ── Input validation ──────────────────────────────────────────────────────────
$raw_key = $_GET['key'] ?? '';
$key = preg_replace('/[^a-z0-9_]/', '', strtolower($raw_key));

if (!$key || !isset($VIDEO_MAP[$key])) {
    http_response_code(404);
    $page_title    = 'Guide Not Found';
    $character     = null;
    $title         = 'Guide Not Found';
    $description   = 'This guide video does not exist or the link may be outdated.';
    $video_url     = null;
    $accent        = '#00FFF7';
} else {
    [$filename, $character, $title, $description] = $VIDEO_MAP[$key];
    // Cache-bust on the served file's mtime so a re-deployed clip (same filename)
    // bypasses Cloudflare's ~4h edge cache — mirrors guide-gallery.php. Covers the
    // embed-mode player, the main player, and the download fallback (all use $video_url).
    $video_mtime = @filemtime(__DIR__ . '/guide-videos/' . $filename) ?: '1';
    $video_url   = '/guide-videos/' . $filename . '?v=' . $video_mtime;
    $accent      = $CHARACTER_COLORS[$character] ?? '#00FFF7';
    $page_title = $title . ' | ASCEND Guide';
    // Post-video CTA: guide videos are DM'd to MEMBERS — "Join OD9" is wrong (they
    // already joined). The 'arrived' onboarding video continues into the welcome
    // board (-> first-arrival welcome + tour); every other clip points back to HQ.
    if ($key === 'arrived') {
        $cta_heading = "You're in. Welcome to OD9.";
        $cta_text    = "Step into your Dashboard — that's where it all begins.";
        $cta_url     = '/dashboard/welcome.php';
        $cta_label   = 'Enter Your Dashboard';
    } else {
        $cta_heading = 'Keep climbing.';
        $cta_text    = 'Head back to your Dashboard to continue your progression.';
        $cta_url     = '/dashboard/';
        $cta_label   = 'Your Dashboard';
    }
}

// Embed mode (?embed=1): the in-dashboard notification modal iframes this — render
// JUST the player (no nav/hero/CTA/footer), so it sits cleanly inside the modal.
if (!empty($_GET['embed'])) {
    header('X-Frame-Options: SAMEORIGIN');
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title, ENT_QUOTES) ?></title>
<style>*{margin:0;padding:0}html,body{background:#000;height:100%}video{width:100%;height:100%;object-fit:contain;display:block}.nf{color:#999;font-family:sans-serif;padding:2rem;text-align:center}</style>
</head><body>
<?php if ($video_url): ?>
<video controls playsinline preload="metadata"><source src="<?= htmlspecialchars($video_url, ENT_QUOTES) ?>" type="video/mp4">Your browser doesn't support HTML5 video.</video>
<?php else: ?><p class="nf">This guide video isn't available.</p><?php endif; ?>
</body></html><?php
    exit;
}

$page_description = $description;
$page_slug        = 'guide.php';
$page_robots      = 'noindex';   // per-key, DM-driven video player — keep out of search
$current_page     = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#050505;color:#e0e0e0;font-family:'Exo 2',sans-serif;min-height:100vh;padding-top:var(--nav-height,80px)}

.guide-hero{
    background:linear-gradient(135deg,#050505 0%,#0a0a12 60%,#050505 100%);
    border-bottom:1px solid #111;
    padding:3rem 1.5rem 2rem;
    text-align:center;
    position:relative;
    overflow:hidden;
}
.guide-hero::before{
    content:'';position:absolute;top:-60px;left:50%;transform:translateX(-50%);
    width:600px;height:200px;
    background:radial-gradient(ellipse,<?= $accent ?>22 0%,transparent 70%);
    pointer-events:none;
}
.character-badge{
    display:inline-block;
    font-family:'Orbitron',sans-serif;
    font-size:0.75rem;
    letter-spacing:3px;
    text-transform:uppercase;
    color:<?= $accent ?>;
    border:1px solid <?= $accent ?>55;
    padding:0.3rem 1rem;
    border-radius:2px;
    margin-bottom:1rem;
    text-shadow:0 0 12px <?= $accent ?>66;
}
.guide-title{
    font-family:'Orbitron',sans-serif;
    font-size:clamp(1.4rem,4vw,2.4rem);
    font-weight:900;
    color:#fff;
    text-shadow:0 0 30px <?= $accent ?>44;
    margin-bottom:1rem;
    line-height:1.2;
}
.guide-description{
    max-width:640px;
    margin:0 auto;
    color:#aaa;
    font-size:1rem;
    line-height:1.7;
}

.guide-main{
    max-width:860px;
    margin:0 auto;
    padding:2.5rem 1.5rem 4rem;
}

.video-container{
    position:relative;
    width:100%;
    background:#000;
    border-radius:6px;
    overflow:hidden;
    border:1px solid <?= $accent ?>33;
    box-shadow:0 0 40px <?= $accent ?>11,0 8px 32px rgba(0,0,0,0.6);
    margin-bottom:2rem;
}
.video-container video{
    width:100%;
    display:block;
    max-height:540px;
}

.video-placeholder{
    padding:4rem 2rem;
    text-align:center;
    color:#888;
}
.video-placeholder i{font-size:3rem;margin-bottom:1rem;display:block;color:#949494}
.video-placeholder p{font-size:0.95rem}

.guide-cta{
    background:linear-gradient(135deg,#0d0d0d,#0a0a12);
    border:1px solid #1a1a2e;
    border-left:3px solid <?= $accent ?>;
    border-radius:6px;
    padding:1.8rem 2rem;
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:1rem;
}
.guide-cta-text h3{
    font-family:'Orbitron',sans-serif;
    font-size:0.95rem;
    color:#fff;
    margin-bottom:0.3rem;
    letter-spacing:1px;
}
.guide-cta-text p{color:#888;font-size:0.9rem}
.btn-discord{
    display:inline-flex;
    align-items:center;
    gap:0.5rem;
    background:<?= $accent ?>;
    color:#000;
    font-family:'Orbitron',sans-serif;
    font-size:0.8rem;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    padding:0.7rem 1.5rem;
    border-radius:3px;
    text-decoration:none;
    transition:opacity 0.2s,box-shadow 0.2s;
    white-space:nowrap;
}
.btn-discord:hover{opacity:0.85;box-shadow:0 0 20px <?= $accent ?>55}

.not-found-block{
    text-align:center;
    padding:4rem 2rem;
}
.not-found-block i{font-size:3rem;color:#949494;display:block;margin-bottom:1rem}
.not-found-block h2{font-family:'Orbitron',sans-serif;color:#888;margin-bottom:0.75rem}
.not-found-block p{color:#949494;font-size:0.95rem}
.not-found-block a{color:<?= $accent ?>;text-decoration:none}
.not-found-block a:hover{text-decoration:underline}
</style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="guide-hero">
<?php if ($character): ?>
    <div class="character-badge"><?= htmlspecialchars($character) ?></div>
<?php endif; ?>
    <h1 class="guide-title"><?= htmlspecialchars($title) ?></h1>
    <p class="guide-description"><?= htmlspecialchars($description) ?></p>
</section>

<main class="guide-main">
<?php if ($video_url): ?>

    <div class="video-container">
        <video controls preload="metadata" playsinline>
            <source src="<?= htmlspecialchars($video_url) ?>" type="video/mp4">
            Your browser doesn't support HTML5 video.
            <a href="<?= htmlspecialchars($video_url) ?>">Download the video</a> instead.
        </video>
    </div>

    <div class="guide-cta">
        <div class="guide-cta-text">
            <h3><?= htmlspecialchars($cta_heading) ?></h3>
            <p><?= htmlspecialchars($cta_text) ?></p>
        </div>
        <a href="<?= htmlspecialchars($cta_url) ?>" class="btn-discord">
            <i class="fas fa-arrow-right"></i> <?= htmlspecialchars($cta_label) ?>
        </a>
    </div>

<?php else: ?>

    <div class="not-found-block">
        <i class="fas fa-video-slash"></i>
        <h2>Guide Not Found</h2>
        <p>This link may be outdated. <a href="https://discord.gg/spgmrXVMWq">Rejoin the server</a> to get a fresh one.</p>
    </div>

<?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
