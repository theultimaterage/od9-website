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

// ── Video registry ────────────────────────────────────────────────────────────
// [filename, character, title, description]
$VIDEO_MAP = [
    // Onboarding
    'arrived' => [
        'archivist-welcome.mp4', 'The Archivist',
        'Welcome to The Wake',
        'You have arrived. The Archivist welcomes you to your journey of understanding reality.',
    ],

    // Tier Entry
    'tier_theorist' => [
        'archivist-theorist-entry.mp4', 'The Archivist',
        'Welcome to The Diagnostic',
        'You have passed through The Wake. The Archivist acknowledges your growth into a Theorist.',
    ],
    'tier_approaching_theorist' => [
        'archivist-approaching-theorist.mp4', 'The Archivist',
        'A Shift Approaches',
        'The Archivist senses your progress. You are nearing the threshold of the Theorist tier.',
    ],
    'tier_benefactor' => [
        'benefactor-entry-montage-final.mp4', 'All Guides',
        'Welcome to The Horizon',
        'All four guides gather to mark your arrival at the highest tier. The Horizon awaits.',
    ],

    // First Activities
    'first_content'          => ['activity-first-content-archivist.mp4',      'The Archivist',   'First Knowledge Absorbed',    'The Archivist notes your first completed content. The library opens to you.'],
    'first_discussion'       => ['activity-first-discussion-archivist.mp4',   'The Archivist',   'First Voice Raised',          'You have spoken in the halls. The Archivist records your first discussion contribution.'],
    'first_qotd'             => ['activity-first-qotd-archivist.mp4',         'The Archivist',   'Thinking Together',           'Your voice joins the collective inquiry. The Archivist honors your first Question of the Day response.'],
    'first_reflection'       => ['activity-first-reflection-archivist.mp4',   'The Archivist',   'Reflection Crystallizes',     'The Archivist witnesses your insight take form. Reflection transforms knowledge into wisdom.'],
    'first_evaluation'       => ['activity-first-evaluation-archivist.mp4',   'The Archivist',   'First Judgment Cast',         "You have evaluated another's work. The Archivist acknowledges your discernment."],
    'first_governance'       => ['activity-first-governance-navigator.mp4',   'The Navigator',   'First Civic Act',             'The Navigator welcomes you to governance. Your vote shapes the path forward.'],
    'first_mentorship'       => ['activity-first-mentorship-navigator.mp4',   'The Navigator',   'First Connection Forged',     'The Navigator celebrates your entry into mentorship. Growth flows both ways.'],
    'first_milestone'        => ['activity-first-milestone-forgemaster.mp4',  'The Forgemaster', 'First Milestone Reached',     'The Forgemaster marks your first project milestone. The forge burns brighter.'],
    'first_project'          => ['activity-first-project-forgemaster.mp4',    'The Forgemaster', 'First Creation Begun',        'The Forgemaster acknowledges your first project. Build what has never existed.'],
    'first_research_checkin' => ['activity-research-checkin-forgemaster.mp4', 'The Forgemaster', 'Research Cell Active',        'The Forgemaster notes your research cell check-in. Inquiry drives innovation.'],
    'first_pathway_complete' => ['activity-pathway-completion-archivist.mp4', 'The Archivist',   'Pathway Completed',           'The Archivist records your completed learning pathway. Knowledge crystallized.'],
    'mentorship_90day'       => ['activity-mentorship-90day-navigator.mp4',   'The Navigator',   '90-Day Mentorship Milestone', 'The Navigator honors 90 days of dedicated mentorship. A lasting bond forged.'],
    'pessimism_training'     => ['activity-pessimism-training-archivist.mp4', 'The Archivist',   'Pessimism Training Complete', 'The Archivist guides you through pessimism as a diagnostic tool, not a destination.'],

    // Streaks
    'streak_3'      => ['streak-3day-archivist.mp4',    'The Archivist',   '3-Day Streak!',       'The Archivist sees your consistency. Three days of dedicated engagement.'],
    'streak_7'      => ['streak-7day-archivist.mp4',    'The Archivist',   'Weekly Warrior',      'Seven days strong. The Archivist marks your first full week of dedication.'],
    'streak_14'     => ['streak-14day-forgemaster.mp4', 'The Forgemaster', 'Fortnight Focus',     'The Forgemaster acknowledges two weeks of unbroken commitment.'],
    'streak_30'     => ['streak-30day-forgemaster.mp4', 'The Forgemaster', 'Monthly Devotion',    'Thirty days. The Forgemaster salutes your relentless dedication to the cause.'],
    'streak_60'     => ['streak-60day-navigator.mp4',   'The Navigator',   'Relentless',          'Sixty days of continuous growth. The Navigator charts your extraordinary trajectory.'],
    'streak_100'    => ['streak-100day-navigator.mp4',  'The Navigator',   'Centurion',           'One hundred days. The Navigator declares you a Centurion of commitment.'],
    'streak_shield' => ['streak-shield-archivist.mp4',  'The Archivist',   'Shield Activated',    'Your streak shield protected you. The Archivist reminds you: resilience is not perfection.'],

    // Capstones
    'observer_capstone_complete'  => ['observer-capstone-complete.mp4',  'The Archivist', 'Observer Capstone Complete',  'The Archivist marks your passage through The Diagnosis Preview. You have seen the shape of the problem. Now you learn its texture.'],
    'theorist_capstone_complete'  => ['theorist-capstone-complete.mp4',  'The Archivist', 'Theorist Capstone Complete',  'The Archivist honors your Theorist Capstone. You have demonstrated mastery of diagnostic thinking.'],
];

$CHARACTER_COLORS = [
    'The Archivist'   => '#00FFF7',
    'The Forgemaster' => '#FF6B35',
    'The Navigator'   => '#7A00FF',
    'The Watcher'     => '#FFD700',
    'All Guides'      => '#00FFF7',
];

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
    $video_url  = '/guide-videos/' . $filename;
    $accent     = $CHARACTER_COLORS[$character] ?? '#00FFF7';
    $page_title = $title . ' | ASCEND Guide';
}

$current_page = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> | OD9</title>
<meta name="description" content="<?= htmlspecialchars($description) ?>">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/guide.php'), '/') ?>/css/od9.css">
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
    color:#555;
}
.video-placeholder i{font-size:3rem;margin-bottom:1rem;display:block;color:#333}
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
.guide-cta-text p{color:#666;font-size:0.9rem}
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
.not-found-block i{font-size:3rem;color:#333;display:block;margin-bottom:1rem}
.not-found-block h2{font-family:'Orbitron',sans-serif;color:#555;margin-bottom:0.75rem}
.not-found-block p{color:#444;font-size:0.95rem}
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
            <h3>Ready to ascend?</h3>
            <p>Join the OD9 community and begin your progression through the ASCEND tiers.</p>
        </div>
        <a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn-discord">
            <i class="fab fa-discord"></i> Join OD9
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
