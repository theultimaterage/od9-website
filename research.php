<?php
/**
 * OD9 Research — public, partner-facing page for the coordination-infrastructure
 * research program: the instrument, the eight falsifiable hypotheses (H1–H7),
 * the plainly-stated limitations, partnership categories, and the four asks.
 *
 * Content mirrors docs/RESEARCH_STATEMENT.md v0.5 (2026-08-08), the canonical
 * registry. Update from there — never edit numbers/statuses here independently.
 *
 * Mounted at https://offda9.com/research.php
 */
$current_page = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Research | OD9 — Coordination-Infrastructure Research Program</title>
<meta name="description" content="OD9 operates a live, fully logged coordination-research instrument: a five-tier Discord progression system, AI-assisted evaluation, and multi-channel telemetry. Seeking academic, institutional, and grant-funded research partners.">
<link rel="canonical" href="https://offda9.com/research.php">
<meta name="theme-color" content="#00BFFF">
<meta property="og:title" content="OD9 — Coordination-Infrastructure Research Program">
<meta property="og:description" content="A live, instrumented community-research platform with eight falsifiable hypotheses, honestly reported limitations, and four standing asks for research partners.">
<meta property="og:url" content="https://offda9.com/research.php">
<meta property="og:image" content="https://offda9.com/images/logos/od9-logo.png">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<?php /* css/od9.css owns tokens + reset + nav/topbar/email-popup CSS (site contract since 2026-06-05).
         Base-path-aware so the link resolves at / on prod and /od9/ on local XAMPP. */
$asset_base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\'); ?>
<link rel="stylesheet" href="<?= $asset_base ?>/css/od9.css?v=<?= @filemtime(__DIR__ . '/css/od9.css') ?: '1' ?>">
<style>
/* Page-specific styles only — css/od9.css owns design tokens, the reset, and nav/topbar/email-popup CSS. */
:root{--green:#00E89A;--orange:#FF8A3D;--purple:#A55FFF}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;line-height:1.7}

/* ── Hero ── */
.research-hero{background:linear-gradient(135deg,#050505 0%,#0a0a12 60%,#050505 100%);border-bottom:1px solid #111;padding:4rem 1.5rem 3rem;text-align:center}
.hero-chip{display:inline-block;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:0.8rem;letter-spacing:3px;text-transform:uppercase;color:var(--b);border:1px solid rgba(0,191,255,0.35);padding:0.35rem 1.1rem;border-radius:2px;margin-bottom:1.25rem;text-shadow:0 0 12px rgba(0,191,255,0.4)}
.research-hero h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.7rem,4.5vw,2.7rem);font-weight:900;color:#fff;letter-spacing:2px;line-height:1.25;text-shadow:0 0 30px rgba(0,191,255,0.27);margin-bottom:1.25rem}
.hero-lede{max-width:780px;margin:0 auto;color:#aaa;font-size:1.05rem;line-height:1.75}
.hero-lede strong{color:var(--c)}
.hero-pi{margin-top:1.25rem;color:#666;font-size:0.85rem;font-family:'Rajdhani',sans-serif;letter-spacing:1.5px;text-transform:uppercase}

/* ── Layout ── */
.wrap{max-width:980px;margin:0 auto;padding:3.5rem 1.5rem 4rem}
.section{margin-bottom:3.5rem}
.kicker{font-family:'Rajdhani',sans-serif;color:var(--b);letter-spacing:4px;font-size:0.8rem;text-transform:uppercase;margin-bottom:0.4rem}
.section h2{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.35rem;letter-spacing:1px;margin-bottom:0.75rem}
.section-intro{color:#999;font-size:0.95rem;max-width:760px;margin-bottom:1.5rem}

/* ── Status chips ── */
.chip{display:inline-block;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.72rem;letter-spacing:1.5px;text-transform:uppercase;padding:0.15rem 0.6rem;border-radius:3px;white-space:nowrap}
.chip-green{color:var(--green);border:1px solid rgba(0,232,154,0.45);background:rgba(0,232,154,0.08)}
.chip-gold{color:var(--gold);border:1px solid rgba(255,215,0,0.45);background:rgba(255,215,0,0.08)}
.chip-orange{color:var(--orange);border:1px solid rgba(255,138,61,0.45);background:rgba(255,138,61,0.08)}
.chip-purple{color:var(--purple);border:1px solid rgba(165,95,255,0.45);background:rgba(165,95,255,0.08)}

/* ── Instrument cards ── */
.inst-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem}
.inst{position:relative;overflow:hidden;background:linear-gradient(135deg,#0d0d0d,#0a0a12);border:1px solid #1a1a2e;border-radius:8px;padding:1.5rem;transition:transform 0.2s ease}
.inst:hover{transform:translateY(-3px)}
.inst::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--b)}
.inst h3{font-family:'Orbitron',sans-serif;font-size:0.95rem;color:#fff;letter-spacing:1px;line-height:1.4;margin-bottom:0.6rem}
.inst h3 i{color:var(--b);margin-right:0.5rem}
.inst p{color:#999;font-size:0.9rem;line-height:1.65;margin-top:0.75rem}
.inst-surface{margin-top:0.75rem;color:#666;font-size:0.78rem;font-family:'Rajdhani',sans-serif;font-weight:600;letter-spacing:1.5px;text-transform:uppercase}

/* ── Hypotheses ── */
.hyp-list{display:flex;flex-direction:column;gap:0.8rem}
.hyp{display:flex;align-items:flex-start;gap:1rem;flex-wrap:wrap;background:#0d0d0d;border:1px solid #1a1a2e;border-radius:8px;padding:1.1rem 1.3rem}
.hyp-id{font-family:'Orbitron',sans-serif;font-weight:700;font-size:0.85rem;color:var(--b);min-width:3em;padding-top:0.1rem}
.hyp p{flex:1;min-width:240px;color:#bbb;font-size:0.93rem;line-height:1.6}
.hyp .chip{margin-top:0.15rem}

/* ── Honesty block (centerpiece) ── */
.plain{background:linear-gradient(135deg,#0d0d0d,#0a0a12);border:1px solid #1a1a2e;border-left:4px solid var(--gold);border-radius:6px;padding:2rem 2.2rem}
.plain .kicker{color:var(--gold)}
.plain-quote{font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:600;color:#fff;line-height:1.5;margin-bottom:1.4rem}
.plain ul{list-style:none}
.plain li{position:relative;padding-left:1.4rem;color:#aaa;font-size:0.95rem;line-height:1.65;margin-bottom:0.9rem}
.plain li:last-child{margin-bottom:0}
.plain li::before{content:'\2014';position:absolute;left:0;color:var(--gold)}
.plain li strong{color:var(--c)}

/* ── Partnership categories + asks ── */
.row-list{display:flex;flex-direction:column;gap:0.7rem}
.row-item{display:flex;align-items:flex-start;gap:1rem;background:#0d0d0d;border:1px solid #1a1a2e;border-radius:8px;padding:1rem 1.3rem}
.row-marker{font-family:'Orbitron',sans-serif;font-weight:700;font-size:0.9rem;color:var(--b);min-width:1.6em;padding-top:0.1rem;text-align:center}
.row-item p{flex:1;color:#bbb;font-size:0.93rem;line-height:1.6}
.row-item p strong{color:#fff}

/* ── Contact CTA ── */
.contact-cta{text-align:center;background:linear-gradient(135deg,rgba(0,191,255,0.08),rgba(0,191,255,0.02));border:1px solid #1a1a2e;border-radius:12px;padding:2.5rem 2rem}
.contact-cta h2{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.3rem;letter-spacing:1px;margin-bottom:0.6rem}
.contact-cta>p{color:#999;font-size:0.95rem;max-width:620px;margin:0 auto 1.5rem}
.btn-mail{display:inline-flex;align-items:center;gap:0.6rem;background:var(--b);color:#000;font-family:'Orbitron',sans-serif;font-size:0.8rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:0.8rem 1.7rem;border-radius:3px;text-decoration:none;transition:opacity 0.2s,box-shadow 0.2s;white-space:nowrap}
.btn-mail:hover{opacity:0.85;box-shadow:0 0 20px rgba(0,191,255,0.35)}
.cta-note{margin-top:1.2rem;color:#777;font-size:0.88rem}

/* ── Bottom links ── */
.bottom-links{text-align:center;margin-top:3rem}
.bottom-links a{color:#666;text-decoration:none;font-size:0.85rem;margin:0 0.75rem}
.bottom-links a:hover{color:var(--b)}
</style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="research-hero">
    <div class="hero-chip">Instrumented &middot; Falsifiable &middot; Honestly Reported</div>
    <h1>Coordination-Infrastructure Research</h1>
    <p class="hero-lede">
        OD9 is a coordination-infrastructure research program investigating whether deliberate
        <strong>human-and-AI collaboration in instrumented community contexts</strong> can produce
        measurable improvements in collective deliberation, decision quality, and goal execution —
        and whether those interventions generalize to larger-scale coordination problems.
        The failure pattern we study repeats across healthcare, education, governance, climate
        response, and AI safety: large claimed coordinating capacity, large resource inputs,
        weak measured output. We operate a live, fully logged instrument to test interventions
        against it, and we are seeking academic, institutional, and grant-funded research partners.
    </p>
    <p class="hero-pi">Principal investigator: The Ultimate Rage &middot; OD9 LLC, Chicago</p>
</section>

<main class="wrap">

    <!-- ── 1. The instrument ── -->
    <section class="section">
        <p class="kicker">Deployed infrastructure</p>
        <h2>The instrument</h2>
        <p class="section-intro">
            Not a survey, not a simulation — a deployed system generating structured behavioral
            data continuously, with complete version-control history and telemetry available to partners.
        </p>
        <div class="inst-grid">
            <div class="inst">
                <h3><i class="fas fa-layer-group"></i>Discord progression bot</h3>
                <span class="chip chip-green">Live &middot; 96 instrumented accounts</span>
                <p>
                    The five-tier ASCEND progression system: dimensional scoring, review workflow,
                    daily tier scheduler. Every credit-earning action, review decision, and tier
                    transition is event-logged. Repository since July 2025; progression phases
                    deployed February&ndash;June 2026.
                </p>
                <div class="inst-surface">Surface: H1 &middot; H2 &middot; H5</div>
            </div>
            <div class="inst">
                <h3><i class="fas fa-scale-balanced"></i>AI-assisted evaluation layer</h3>
                <span class="chip chip-green">Deployed June 2026 &middot; content-grounded</span>
                <p>
                    Claude-based rubric evaluation of discussions and reflections, graded against
                    the actual source text, with a human audit queue; capstone parts stay
                    human-approved — AI operating inside the coordination instrument, not
                    merely advising its operator.
                </p>
                <div class="inst-surface">Surface: H1 &middot; H5</div>
            </div>
            <div class="inst">
                <h3><i class="fas fa-comments"></i>Daily Think Tank cadence</h3>
                <span class="chip chip-green">Live &middot; daily 6pm Central since May 2026</span>
                <p>
                    Structured group deliberation every day: automated scheduling, attendance
                    tracking, and facilitator workflow around a facilitated voice-channel agenda.
                </p>
                <div class="inst-surface">Surface: H2</div>
            </div>
            <div class="inst">
                <h3><i class="fas fa-envelope-open-text"></i>Email engagement pipeline</h3>
                <span class="chip chip-green">Live since May 2026</span>
                <p>
                    Subscribe &rarr; double-opt-in verify &rarr; tier-triggered drip &rarr; weekly
                    broadcast on a 33-week calendar, with send-log and open/click telemetry.
                    The Sunday Discord question is mechanically pinned to the email week.
                </p>
                <div class="inst-surface">Surface: H1 &middot; H2 &middot; H6a</div>
            </div>
            <div class="inst">
                <h3><i class="fas fa-tower-broadcast"></i>F.R.E.S.H. Stream Center</h3>
                <span class="chip chip-green">Live in production for daily streams</span>
                <p>
                    OBS plugin powering the daily streams: multi-stream broadcasting, chat
                    aggregation, and audio entrainment with per-session logging via
                    <code style="font-family:inherit">EntrainmentLogger</code>.
                </p>
                <div class="inst-surface">Surface: H3</div>
            </div>
            <div class="inst">
                <h3><i class="fas fa-brain"></i>Synaptiq</h3>
                <span class="chip chip-orange">In development</span>
                <p>
                    Audio/cognitive-science toolkit with three layers: VST commercial,
                    cognitive-state research, and quantum-cognition speculative — the research
                    surface for audio-mediated cognitive-state intervention.
                </p>
                <div class="inst-surface">Surface: H3 &middot; H4 &middot; frontier H5</div>
            </div>
        </div>
    </section>

    <!-- ── 2. The hypotheses ── -->
    <section class="section">
        <p class="kicker">Falsifiable by design</p>
        <h2>The hypotheses</h2>
        <p class="section-intro">
            Eight hypotheses across seven families, each with named measurement infrastructure and
            an honest status. Compressed here — the canonical registry with full constraints,
            design notes, and corrections is the research statement (v0.5).
        </p>
        <div class="hyp-list">
            <div class="hyp">
                <span class="hyp-id">H1</span>
                <p>Tier-gated progression with reviewed written reflections improves argument
                   quality and engagement durability, assessed against within-community baselines.</p>
                <span class="chip chip-green">Accumulating data</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H2</span>
                <p>Daily structured group deliberation — the 6pm Central Think Tank cadence —
                   increases member-reported goal clarity and member-initiated collaborative
                   projects over baseline.</p>
                <span class="chip chip-gold">Partially instrumented</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H3</span>
                <p>Defined audio interventions (40&nbsp;Hz gamma stimulation, HRV-paced breathing,
                   binaural protocols, pink/brown noise) produce measurable changes in attention,
                   working memory, and self-reported cognitive state versus silent control.</p>
                <span class="chip chip-orange">In development</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H4</span>
                <p>Targeted cognitive-state interventions can partially counteract attention and
                   cognitive-function decrements associated with environmental neurotoxin exposure
                   — designs restricted to exposures with live biomarkers.</p>
                <span class="chip chip-purple">Partnership-gated</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H5</span>
                <p>Structured iterative collaboration between human operators and large language
                   models compounds decision quality and execution velocity across iteration count
                   — the production AI-evaluation layer is the current exhibit.</p>
                <span class="chip chip-green">Accumulating data</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H6a</span>
                <p>Members reached across coordinated channels — the weekly email broadcast and the
                   Discord question-of-the-day — show longer activity tails and higher subsequent
                   response rates than single-channel members.</p>
                <span class="chip chip-green">Accumulating data</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H6b</span>
                <p>Coordinated same-chapter exposure across both channels improves concept
                   retention, scored on question responses against matched control chapter pairs.</p>
                <span class="chip chip-purple">Partnership-gated</span>
            </div>
            <div class="hyp">
                <span class="hyp-id">H7</span>
                <p>Sustained exposure to explicit calibration rituals — verified reflections,
                   post-game review huddles, institutionalized debiasing checks — shifts member
                   discourse from moral-essentialist attribution toward mechanism-structural
                   attribution, measured within-member against pre-exposure baselines.</p>
                <span class="chip chip-orange">Registered — instrument in development</span>
            </div>
        </div>
    </section>

    <!-- ── 3. What we say plainly (centerpiece) ── -->
    <section class="section">
        <div class="plain">
            <p class="kicker">What we say plainly</p>
            <p class="plain-quote">We would rather under-claim and be checked than over-claim and be caught.</p>
            <ul>
                <li><strong>96 member accounts, honestly broken down.</strong> 93 Observer, 1 Theorist,
                    2 Benefactor (July 2026). The Benefactor accounts are founder/supporter grants;
                    the Theorist advancement (June 29, 2026) is our first non-founder progression —
                    recorded under a documented beta-tester exception to the full content gate, so
                    we count it as activation evidence, not yet a clean organic advancement.</li>
                <li><strong>A documented null result — and its first break.</strong> Through May 2026 the
                    instrument recorded essentially zero organic tier advancement. We treat that as
                    data: passive onboarding into an instrumented progression system produced
                    near-zero activation. Countermeasures deployed June&ndash;July 2026 produced the
                    first advancement; curriculum actions are still a small share of logged
                    activity, so the problem is narrowed, not solved. Documented, not buried.</li>
                <li><strong>We audit our own instrument and say so.</strong> The evaluation layer's first
                    version scored reflections without the source text and discarded rejected
                    attempts; fixed June 30, 2026, and disclosed — pre-fix scores are flagged in any
                    analysis. A June 2026 recalibration of the dimension economy (passive presence
                    no longer mints advancement value) came with a one-time reset; pre- and
                    post-recalibration telemetry are treated as separate measurement regimes.</li>
                <li><strong>Unpowered, and we say so.</strong> At current scale no between-group
                    hypothesis is adequately powered. Claims are gated on declared power thresholds
                    — within-subject analyses at roughly 300 weekly-active members, between-cohort
                    designs at roughly 1,000+ — and scale with the data, not ahead of it.</li>
                <li><strong>No IRB relationship yet.</strong> Telemetry is platform-operations data;
                    no member has been enrolled as a human research subject. H4 and H6b are
                    explicitly gated on partner-institution IRB supervision.</li>
                <li><strong>Pre-registered before inspection.</strong> From June 2026, analyses are
                    pre-registered in a git-timestamped registry before any data inspection.</li>
            </ul>
        </div>
    </section>

    <!-- ── 4. Partnership categories ── -->
    <section class="section">
        <p class="kicker">Where we partner</p>
        <h2>Partnership categories</h2>
        <div class="row-list">
            <div class="row-item">
                <span class="row-marker">1</span>
                <p><strong>Coordination science &amp; complex systems</strong> — the progression
                   instrument as a small-scale testbed where coordination interventions are
                   deployed and measured against live telemetry.</p>
            </div>
            <div class="row-item">
                <span class="row-marker">2</span>
                <p><strong>Cognitive neuroscience &amp; audio intervention</strong> — Synaptiq as a
                   research surface for audio-mediated cognitive-state intervention, with
                   hypotheses tied to the existing literature.</p>
            </div>
            <div class="row-item">
                <span class="row-marker">3</span>
                <p><strong>Quantum biology &amp; cognition (speculative frontier)</strong> — presented
                   as bookmark-worthy futurism, not established science, and carrying no near-term
                   claims.</p>
            </div>
            <div class="row-item">
                <span class="row-marker">4</span>
                <p><strong>AI safety &amp; human-AI coordination</strong> — a live small-scale
                   instance of the human-AI coordination question, including an AI evaluation
                   layer running inside the production instrument.</p>
            </div>
            <div class="row-item">
                <span class="row-marker">5</span>
                <p><strong>Civilizational-scale planning &amp; Type I infrastructure</strong> —
                   coordination instruments scaled toward planetary problems on the long-arc
                   Kardashev Type I target.</p>
            </div>
        </div>
    </section>

    <!-- ── 5. What we're asking for ── -->
    <section class="section">
        <p class="kicker">The four asks</p>
        <h2>What we&rsquo;re asking for</h2>
        <div class="row-list">
            <div class="row-item">
                <span class="row-marker"><i class="fas fa-flask"></i></span>
                <p><strong>Research collaboration</strong> — co-designed measurement protocols on the
                   live instrument, joint telemetry analysis, co-authored peer-reviewed publication.</p>
            </div>
            <div class="row-item">
                <span class="row-marker"><i class="fas fa-file-signature"></i></span>
                <p><strong>Grant co-application</strong> — OD9 LLC as operator of the live instrument;
                   partner institutions provide academic credentialing, IRB processes, and
                   methodological rigor.</p>
            </div>
            <div class="row-item">
                <span class="row-marker"><i class="fas fa-user-graduate"></i></span>
                <p><strong>Visiting researcher access</strong> — participant-observer engagement with
                   the live community and anonymized telemetry under appropriate ethical and
                   data-protection arrangements.</p>
            </div>
            <div class="row-item">
                <span class="row-marker"><i class="fas fa-compass"></i></span>
                <p><strong>Methodological consultation</strong> — research-methodology, IRB-process,
                   and publication-norm expertise to accelerate the transition from operational
                   instrument to peer-reviewed output.</p>
            </div>
        </div>
    </section>

    <!-- ── 6. Contact CTA ── -->
    <section class="section">
        <div class="contact-cta">
            <h2>Start the conversation</h2>
            <p>
                Academic and partnership inquiries, methodological critiques, and corrections
                are all welcome — critique is a contribution here, not a threat.
            </p>
            <a href="mailto:research@offda9.com" class="btn-mail">
                <i class="fas fa-envelope"></i> research@offda9.com
            </a>
            <p class="cta-note">
                Full research statement (v0.4) and complete telemetry/schema inventory available on request.
            </p>
        </div>
    </section>

    <div class="bottom-links">
        <a href="index.php">Home</a>
        <a href="ecosystem.php">Ecosystem</a>
        <a href="why-patreon.php">Why Patreon</a>
        <a href="progress.php">Live progress</a>
        <a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord</a>
    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
