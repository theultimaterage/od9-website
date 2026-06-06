<?php
/**
 * OD9 Pedagogical Library
 * Public-facing library page for the 5-tier OD9 progression system.
 * 44 PDFs grouped by tier, each linked to its hosted PDF + cover thumbnail.
 *
 * GATING (added 2026-05-01):
 *   Observer  - public, no login
 *   Theorist  - public (the $5 Patreon-shortcut tier; no gate so cold visitors
 *               can sample the diagnostic layer and convert to support)
 *   Architect - login + Architect-tier OR earned-Architect required
 *   Pioneer   - login + Pioneer-tier OR earned-Pioneer required
 *   Benefactor- login + Benefactor-tier OR earned-Benefactor required
 *
 * Tier check pulls from $_SESSION['bot_user']['current_tier'] populated by
 * dashboard/auth/callback.php after Discord OAuth. Locked-tier sections still
 * render their card grid, but the cards link to /support.php with a
 * lock icon instead of the PDF. This preserves SEO + scannability while
 * gating the actual content.
 */

$gatePath = null;
foreach ([__DIR__ . '/includes/patron_gate.php', __DIR__ . '/../includes/patron_gate.php'] as $cand) {
    if (is_file($cand)) { $gatePath = $cand; break; }
}
if ($gatePath !== null) {
    require_once $gatePath;
} else {
    // local-only fallback so the page still renders if includes/ is missing
    function tier_at_least(string $r): bool { return false; }
    function current_user(): ?array { return null; }
    if (!defined('TIER_ORDER')) {
        define('TIER_ORDER', ['observer'=>0,'theorist'=>1,'architect'=>2,'pioneer'=>3,'benefactor'=>4,'founding'=>5]);
    }
}

// Tiers below this index are public; tiers >= this index require login + tier match
const FIRST_GATED_TIER = 'architect';

// Single source of truth for library content
$LIBRARY = [
    'observer' => [
        'name' => 'OBSERVER',
        'theme' => 'The Awakening',
        'zone' => 'The Wake',
        'color' => '#C0C0C0',  // silver
        'description' => 'Recognize the stakes. Learn the framework. See what most people never see.',
        'docs' => [
            ['Observer-01-Welcome-to-OD9.pdf', '01', 'Welcome to OD9'],
            ['Observer-02-The-Nature-of-Observation.pdf', '02', 'The Nature of Observation'],
            ['Observer-03-Core-Vocabulary.pdf', '03', 'Core Vocabulary'],
            ['Observer-04-The-Four-Pillars-Overview.pdf', '04', 'The Four Pillars Overview'],
            ['Observer-05-How-to-Use-This-System.pdf', '05', 'How to Use This System'],
            ['Observer-06-You-Are-Here.pdf', '06', 'You Are Here'],
            ['Observer-07-The-Kardashev-Framework.pdf', '07', 'The Kardashev Framework'],
            ['Observer-08-The-Great-Filter.pdf', '08', 'The Great Filter'],
            ['Observer-09-Coordination-The-Bottleneck.pdf', '09', 'Coordination - The Bottleneck'],
            ['Observer-10-The-Stakes.pdf', '10', 'The Stakes'],
            ['Observer-11-The-Window.pdf', '11', 'The Window'],
            ['Observer-12-What-You-Can-Do.pdf', '12', 'What You Can Do'],
            ['Observer-13-Emotional-Landing.pdf', '13', 'Emotional Landing'],
            ['Observer-Capstone-Part1-The-Four-Barriers.pdf', 'C1', 'Capstone Part 1: The Four Barriers'],
            ['Observer-Capstone-Part2A-Theological-Apocalyptic.pdf', 'C2A', 'Capstone Part 2A: Theological-Apocalyptic'],
            ['Observer-Capstone-Part2B-Economic-Exploitation.pdf', 'C2B', 'Capstone Part 2B: Economic Exploitation'],
            ['Observer-Capstone-Part2C-Information-Control.pdf', 'C2C', 'Capstone Part 2C: Information Control'],
            ['Observer-Capstone-Part2D-Cognitive-Impairment.pdf', 'C2D', 'Capstone Part 2D: Cognitive Impairment'],
            ['Observer-Capstone-Part3-Synthesis-Discussion.pdf', 'C3', 'Capstone Part 3: Synthesis Discussion'],
        ],
    ],
    'theorist' => [
        'name' => 'THEORIST',
        'theme' => 'The Pattern Seeker',
        'zone' => 'The Diagnostic',
        'color' => '#00BFFF',  // sky blue
        'description' => 'Understand the machinery. Diagnose why coordination keeps failing across history.',
        'docs' => [
            ['Theorist-01-Welcome.pdf', '01', 'Welcome to Theorist'],
            ['Theorist-02-Strategic-Pessimism.pdf', '02', 'Strategic Pessimism'],
            ['Theorist-03-Four-Barriers-Overview.pdf', '03', 'The Four Barriers Overview'],
            ['Theorist-04-The-Case-Against-God.pdf', '04', 'The Case Against God'],
            ['Theorist-05-Economic-Exploitation.pdf', '05', 'Economic Exploitation Systems'],
            ['Theorist-06-Information-Control.pdf', '06', 'Information Control'],
            ['Theorist-07-Cognitive-Impairment.pdf', '07', 'Cognitive & Neurological Impairment'],
            ['Theorist-08-Synergy-of-Barriers.pdf', '08', 'Synergy of Barriers'],
            ['Theorist-09-What-Theorists-Do.pdf', '09', 'What Theorists Do'],
            ['Theorist-10-Capstone.pdf', '10', 'Capstone: Emotional Landing'],
        ],
    ],
    'architect' => [
        'name' => 'ARCHITECT',
        'theme' => 'The Builder',
        'zone' => 'The Forge',
        'color' => '#FF00FF',  // magenta
        'description' => 'Build systems that make coordination possible. Translate diagnosis into design.',
        'docs' => [
            ['Architect-01-Coordination-Mechanism-Design.pdf', '01', 'Coordination Mechanism Design'],
            ['Architect-02-Information-Resilience.pdf', '02', 'Information Resilience'],
            ['Architect-03-Metaworld-Theory.pdf', '03', 'Metaworld Theory'],
            ['Architect-04-Succession-Planning.pdf', '04', 'Succession Planning'],
            ['Architect-05-Capstone.pdf', '05', "Capstone: The Builder's Threshold"],
        ],
    ],
    'pioneer' => [
        'name' => 'PIONEER',
        'theme' => 'The Trailblazer',
        'zone' => 'The Bridge',
        'color' => '#FFD700',  // gold
        'description' => 'Govern what gets built. Coordinate the coordinators. Decide which problems get solved.',
        'docs' => [
            ['Pioneer-01-Welcome.pdf', '01', 'Welcome to Pioneer'],
            ['Pioneer-02-Governance-as-Research.pdf', '02', 'Governance as Research'],
            ['Pioneer-03-Pioneer-Roles.pdf', '03', 'The Five Pioneer Roles'],
            ['Pioneer-04-Strategic-Stewardship.pdf', '04', 'Strategic Stewardship'],
            ['Pioneer-05-Capstone.pdf', '05', "Capstone: The Steward's Vow"],
        ],
    ],
    'benefactor' => [
        'name' => 'BENEFACTOR',
        'theme' => 'The Transcendent',
        'zone' => 'The Horizon',
        'color' => '#DC143C',  // crimson
        'description' => 'Steward across generations. Build cathedrals you will not live to see completed.',
        'docs' => [
            ['Benefactor-01-Institutional-Immortality.pdf', '01', 'Institutional Immortality'],
            ['Benefactor-02-Legacy-Systems.pdf', '02', 'Legacy Systems'],
            ['Benefactor-03-Sustainability-Workshop.pdf', '03', '5-Year Sustainability Model Workshop'],
            ['Benefactor-04-Capstone.pdf', '04', "Capstone: The Steward's Vow"],
        ],
    ],
];

$total_docs = array_sum(array_map(fn($t) => count($t['docs']), $LIBRARY));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OD9 Pedagogical Library | The Complete Progression System</title>
<link rel="canonical" href="https://offda9.com/library.php">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<meta name="description" content="The complete OD9 Pedagogical Library - 44 PDFs across 5 tiers (Observer, Theorist, Architect, Pioneer, Benefactor) covering the full progression from awareness to civilizational stewardship.">
<meta name="robots" content="index, follow">
<style>
:root{--b:#00BFFF;--d:#0D0D0D;--dd:#1A1A1A;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}body{background:var(--d);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--c);font-family:'Exo 2',sans-serif;padding-top:80px}
.od9-nav{position:fixed;top:0;left:0;width:100%;height:80px;background:linear-gradient(180deg,rgba(13,13,13,.98),rgba(26,26,26,.95));backdrop-filter:blur(20px);border-bottom:2px solid var(--b);box-shadow:var(--g);z-index:9999}.nav-container{max-width:1200px;margin:0 auto;padding:0 2rem;height:100%;display:flex;justify-content:space-between;align-items:center}.nav-logo{display:flex;align-items:center;text-decoration:none}.nav-logo img{height:50px;margin-right:.75rem;filter:drop-shadow(var(--g))}.nav-logo-text{font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:700;color:var(--b);letter-spacing:3px;text-shadow:var(--g)}.nav-menu{display:flex;list-style:none;gap:1.2rem;align-items:center}.nav-link{color:var(--c);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;transition:.3s;padding:.5rem 0;position:relative}.nav-link::after{content:'';position:absolute;bottom:0;left:50%;width:0;height:2px;background:var(--b);transition:.3s;transform:translateX(-50%)}.nav-link:hover,.nav-link.active{color:var(--b)}.nav-link:hover::after,.nav-link.active::after{width:100%}.nav-btn{background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.6rem 1.2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;display:flex;align-items:center;gap:.5rem;box-shadow:var(--g)}.mobile-toggle{display:none;background:none;border:none;cursor:pointer;padding:.5rem;z-index:10001}.mobile-toggle span{display:block;width:25px;height:3px;background:var(--b);margin:5px 0;border-radius:2px;transition:.3s}.mobile-toggle.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}.mobile-toggle.active span:nth-child(2){opacity:0}.mobile-toggle.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px)}.mobile-menu{display:none;position:fixed;top:80px;left:0;width:100%;background:linear-gradient(180deg,rgba(13,13,13,.98),rgba(26,26,26,.98));padding:1rem 0;border-bottom:2px solid var(--b);box-shadow:var(--g);z-index:9998}.mobile-menu.active{display:block}.mobile-menu a{display:block;color:var(--c);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:1rem 2rem;border-bottom:1px solid #222}.mobile-menu a.active,.mobile-menu a:hover{color:var(--b);background:rgba(0,191,255,.1)}.mobile-menu .mobile-discord{background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;margin:1rem;border-radius:4px;text-align:center;border-bottom:none}@media(max-width:900px){.nav-menu{display:none}.mobile-toggle{display:block}}
.container{max-width:1200px;margin:0 auto;padding:2rem}
.hero{background:linear-gradient(135deg,rgba(0,191,255,.13),rgba(0,0,0,.72));border:1px solid #2a2a2a;border-radius:16px;padding:3rem 2rem;margin-bottom:1.5rem;box-shadow:var(--g);text-align:center}
.hero h1{font-family:'Orbitron',sans-serif;color:#fff;font-size:3rem;margin-bottom:1rem;letter-spacing:3px;line-height:1.1}
.hero p{line-height:1.8;color:#bcbcbc;max-width:780px;margin:0 auto 1.5rem;font-size:1.05rem}
.hero-stats{display:flex;justify-content:center;gap:2.5rem;flex-wrap:wrap;margin-top:1rem}
.hero-stat{text-align:center}
.hero-stat-num{font-family:'Orbitron',sans-serif;font-size:2.5rem;color:var(--b);font-weight:900;display:block;text-shadow:var(--g)}
.hero-stat-label{font-family:'Rajdhani',sans-serif;font-size:0.85rem;color:#888;letter-spacing:2px;text-transform:uppercase}
.toc-banner{background:#121212;border:2px solid #00BFFF;border-radius:14px;padding:1.5rem 2rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;box-shadow:var(--g)}
.toc-banner-text h3{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:.4rem;font-size:1.1rem}
.toc-banner-text p{color:#999;font-size:.9rem;margin:0}
.toc-banner .btn-toc{background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.85rem 1.6rem;border-radius:6px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;font-size:.9rem;display:inline-flex;align-items:center;gap:.5rem;white-space:nowrap}
.toc-banner .btn-toc:hover{transform:translateY(-2px);box-shadow:var(--g)}
.tier-section{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin-bottom:1.5rem;border-left:6px solid var(--tier-color)}
.tier-header{margin-bottom:1.5rem;border-bottom:1px solid #2a2a2a;padding-bottom:1rem}
.tier-header h2{font-family:'Orbitron',sans-serif;color:var(--tier-color);font-size:2rem;letter-spacing:4px;margin-bottom:.4rem;text-shadow:0 0 12px rgba(255,255,255,.1)}
.tier-meta{display:flex;gap:1.5rem;color:#888;font-size:.85rem;font-family:'Rajdhani',sans-serif;letter-spacing:1px;text-transform:uppercase;margin-bottom:.75rem;flex-wrap:wrap}
.tier-meta span{display:inline-flex;align-items:center;gap:.4rem}
.tier-desc{color:#bcbcbc;line-height:1.7;font-size:.95rem;max-width:780px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem}
.card{background:#121212;border:1px solid #2a2a2a;border-radius:14px;padding:1rem;display:flex;flex-direction:column;transition:transform .2s, border-color .2s, box-shadow .2s;text-decoration:none;color:inherit}
.card:hover{transform:translateY(-3px);border-color:var(--tier-color);box-shadow:0 0 18px rgba(255,255,255,.06)}
.card-thumb{width:100%;border-radius:10px;margin-bottom:.85rem;border:1px solid rgba(255,255,255,.08);background:#000;display:block}
.card-num{font-family:'Orbitron',sans-serif;color:var(--tier-color);font-size:.75rem;letter-spacing:2px;margin-bottom:.3rem;font-weight:700}
.card-title{font-family:'Rajdhani',sans-serif;color:#fff;font-size:.95rem;font-weight:600;line-height:1.3;margin-bottom:.85rem;flex-grow:1}
.card-btn{display:inline-flex;align-items:center;gap:.4rem;justify-content:center;background:transparent;border:1px solid var(--tier-color);color:var(--tier-color);padding:.55rem .9rem;border-radius:5px;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;text-transform:uppercase;font-size:.78rem;text-decoration:none;transition:.2s;margin-top:auto}
.card:hover .card-btn{background:var(--tier-color);color:#0D0D0D}
.intro-callout{background:linear-gradient(135deg,rgba(0,191,255,.05),rgba(0,0,0,.4));border:1px solid #1f3a4a;border-radius:14px;padding:1.5rem 2rem;margin-bottom:2rem}
.intro-callout h3{font-family:'Orbitron',sans-serif;color:#fff;font-size:1rem;letter-spacing:2px;margin-bottom:.6rem}
.intro-callout p{color:#aaa;line-height:1.7;font-size:.92rem;margin:0}
@media(max-width:600px){.hero h1{font-size:2rem}.hero-stats{gap:1.5rem}.hero-stat-num{font-size:1.8rem}.tier-header h2{font-size:1.5rem;letter-spacing:2px}}
</style>
</head>
<body>
<?php $current_page = 'library'; include('includes/nav.php'); ?>

<div class="container">

<section class="hero">
<h1>THE LIBRARY</h1>
<p>The complete OD9 Pedagogical Library. Five tiers, <?= $total_docs ?> documents, one trajectory: from recognizing the stakes to stewarding civilization across generations.</p>
<div class="hero-stats">
<div class="hero-stat"><span class="hero-stat-num"><?= $total_docs ?></span><span class="hero-stat-label">Documents</span></div>
<div class="hero-stat"><span class="hero-stat-num">5</span><span class="hero-stat-label">Tiers</span></div>
<div class="hero-stat"><span class="hero-stat-num">5</span><span class="hero-stat-label">Capstones</span></div>
<div class="hero-stat"><span class="hero-stat-num">2</span><span class="hero-stat-label">Free Tiers</span></div>
</div>
</section>

<section class="toc-banner">
<div class="toc-banner-text">
<h3>Master Table of Contents</h3>
<p>One PDF that links to everything. Hyperlinked, page-numbered, sealed.</p>
</div>
<a href="library/OD9-Master-Table-of-Contents.pdf" target="_blank" class="btn-toc"><i class="fas fa-bookmark"></i> Open Master TOC</a>
</section>

<section class="intro-callout">
<h3>HOW TO USE THIS LIBRARY</h3>
<p>Each tier builds on the previous one. Most readers should progress in order: Observer first, then Theorist, then Architect, then Pioneer, finally Benefactor. Each tier ends with a Capstone document - an emotional landing that prepares you for what comes next. The work itself is the destination - tiers are scaffolding, not a hierarchy. Many contributors find their lasting home at Theorist, Architect, or Pioneer. That's not failure - that's matching contribution to capability.</p>
</section>

<?php
$first_gated_idx = TIER_ORDER[FIRST_GATED_TIER] ?? 2;
foreach ($LIBRARY as $tier_key => $tier):
    $tier_idx = TIER_ORDER[$tier_key] ?? 0;
    $needs_gate = $tier_idx >= $first_gated_idx;
    $unlocked = !$needs_gate || tier_at_least($tier_key);
?>
<section class="tier-section" style="--tier-color: <?= $tier['color'] ?>">
<div class="tier-header">
<h2><?= htmlspecialchars($tier['name']) ?>
<?php if ($needs_gate && !$unlocked): ?>
    <span style="font-size:0.6em;color:#888;font-family:'Rajdhani',sans-serif;letter-spacing:2px;margin-left:0.75rem"><i class="fas fa-lock" style="margin-right:0.3rem"></i>SUPPORTER ACCESS</span>
<?php endif; ?>
</h2>
<div class="tier-meta">
<span><i class="fas fa-compass"></i> Zone: <?= htmlspecialchars($tier['zone']) ?></span>
<span><i class="fas fa-user-astronaut"></i> <?= htmlspecialchars($tier['theme']) ?></span>
<span><i class="fas fa-file-pdf"></i> <?= count($tier['docs']) ?> documents</span>
</div>
<p class="tier-desc"><?= htmlspecialchars($tier['description']) ?></p>
<?php if ($needs_gate && !$unlocked): ?>
<div style="margin-top:1rem;padding:0.85rem 1rem;background:rgba(0,191,255,0.06);border:1px solid rgba(0,191,255,0.25);border-radius:8px;font-size:0.9rem;color:#aaa">
    <i class="fas fa-info-circle" style="color:var(--b);margin-right:0.4rem"></i>
    <strong style="color:#fff"><?= htmlspecialchars($tier['name']) ?>+</strong> docs require either earning the <?= htmlspecialchars($tier['name']) ?> tier through ASCEND progression OR supporting OD9 at the matching Patreon tier.
    <a href="support.php" style="color:var(--b);margin-left:0.4rem">Why Patreon →</a>
    <a href="/dashboard/auth/discord.php" style="color:var(--b);margin-left:0.6rem"><i class="fab fa-discord"></i> Log in to verify tier</a>
</div>
<?php endif; ?>
</div>
<div class="grid">
<?php foreach ($tier['docs'] as [$filename, $num, $title]): ?>
<?php if ($unlocked): ?>
<?php $doc_href = $needs_gate
    ? 'download.php?tier=' . rawurlencode($tier_key) . '&amp;file=' . rawurlencode($filename)
    : 'library/' . rawurlencode($tier_key) . '/' . rawurlencode($filename); ?>
<a href="<?= $doc_href ?>" target="_blank" rel="noopener" class="card">
<img class="card-thumb" src="images/library-thumbs/<?= htmlspecialchars(str_replace('.pdf','.jpg',$filename)) ?>" alt="<?= htmlspecialchars($title) ?> cover" loading="lazy">
<div class="card-num"><?= htmlspecialchars($tier['name']) ?> <?= htmlspecialchars($num) ?></div>
<div class="card-title"><?= htmlspecialchars($title) ?></div>
<span class="card-btn"><i class="fas fa-book-open"></i> Open PDF</span>
</a>
<?php else: ?>
<a href="support.php" class="card" style="opacity:0.7;position:relative">
<img class="card-thumb" src="images/library-thumbs/<?= htmlspecialchars(str_replace('.pdf','.jpg',$filename)) ?>" alt="<?= htmlspecialchars($title) ?> cover (locked)" loading="lazy" style="filter:grayscale(0.65) brightness(0.7)">
<div style="position:absolute;top:1rem;right:1rem;background:rgba(0,0,0,0.85);border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border:1px solid var(--tier-color)"><i class="fas fa-lock" style="color:var(--tier-color)"></i></div>
<div class="card-num"><?= htmlspecialchars($tier['name']) ?> <?= htmlspecialchars($num) ?></div>
<div class="card-title"><?= htmlspecialchars($title) ?></div>
<span class="card-btn"><i class="fas fa-key"></i> Unlock</span>
</a>
<?php endif; ?>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html>