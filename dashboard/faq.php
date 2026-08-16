<?php
/**
 * Progression FAQ — the member reference for HOW the ASCEND progression system
 * works: the five tiers, the advancement gates, credits + dimensions, the capstone,
 * the slash commands, and The Firm. Member-gated + noindex — this is NOT the public
 * "what is OD9" FAQ (that's about.php). It lives inside the progression system, for
 * people who are already in.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ztrans.php';
od9_dashboard_boot();

if (empty($_SESSION['discord_id'])) {
    header('Location: ' . DASHBOARD_BASE_URL . '/index.php');
    exit;
}
$discordId = $_SESSION['discord_id'];

$tier = 'observer';
try {
    $bot = new PDO('sqlite:' . OD9_BOT_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $st = $bot->prepare("SELECT current_tier FROM users WHERE user_id = ? LIMIT 1");
    $st->execute([$discordId]);
    $tier = strtolower((string)($st->fetch()['current_tier'] ?? 'observer')) ?: 'observer';
} catch (Throwable $e) {
    error_log('[faq] bot DB read failed: ' . $e->getMessage());
}

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$cssv = fn($p) => @filemtime(__DIR__ . '/../css/' . $p) ?: '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Progression FAQ · OD9</title>
<link rel="stylesheet" href="/css/board.css?v=<?= $cssv('board.css') ?>">
<style>
.faq-wrap{max-width:860px;margin:0 auto;padding:1.5rem 1.25rem 4rem}
.faq-wrap h1{font-family:'Orbitron',sans-serif;letter-spacing:2px;color:#00FFF7;margin:.5rem 0 .25rem}
.faq-lead{color:#9aa;line-height:1.6;margin:0 0 1.25rem}
.faq-back{display:inline-flex;align-items:center;gap:.5rem;background:linear-gradient(135deg,#00FFF7,#00A0FF);color:#04121a;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.95rem;text-transform:uppercase;letter-spacing:1px;padding:.65rem 1.3rem;border-radius:8px;text-decoration:none;box-shadow:0 0 16px rgba(0,255,247,.3);transition:filter .2s}
.faq-back:hover{filter:brightness(1.12)}
.faq-back.bottom{margin-top:2.25rem}
.faq-q{background:#121317;border:1px solid #23252b;border-radius:12px;margin:0 0 .9rem;overflow:hidden}
.faq-q>summary{cursor:pointer;list-style:none;padding:1rem 1.2rem;font-family:'Rajdhani',sans-serif;font-weight:700;
  font-size:1.05rem;letter-spacing:.5px;color:#e8e8ef;display:flex;justify-content:space-between;align-items:center}
.faq-q>summary::-webkit-details-marker{display:none}
.faq-q>summary::after{content:'+';color:#00FFF7;font-size:1.3rem;line-height:1}
.faq-q[open]>summary::after{content:'\2212'}
.faq-q[open]>summary{border-bottom:1px solid #23252b}
.faq-a{padding:1rem 1.2rem;color:#c7c9d2;line-height:1.7}
.faq-a code{background:#0a0a0a;color:#00FFF7;padding:.12rem .45rem;border-radius:5px;font-family:'Courier New',monospace;font-size:.92em}
.faq-a ul{margin:.5rem 0 .5rem 1.1rem;padding:0}
.faq-a li{margin:.35rem 0}
.faq-a strong{color:#fff}
.tier-row{color:#b388ff}
.cmd-table{width:100%;border-collapse:collapse;margin:.5rem 0}
.cmd-table td{padding:.45rem .6rem;border-bottom:1px solid #1d1f25;vertical-align:top}
.cmd-table td:first-child{white-space:nowrap;color:#00FFF7;font-family:'Courier New',monospace;font-size:.88rem}
</style>
<?php od9_ztrans_head(); ?>
</head>
<body>
<?php od9_ztrans_body(); ?>
<div class="board">
  <header class="hud">
    <div class="brand"><b>OD9 // PROGRESSION FAQ</b></div>
    <span class="zone">// How the system works</span>
    <div class="spacer"></div>
    <div class="tier-badge"><span class="k">Current Tier</span><span class="v"><?= $h(ucfirst($tier)) ?></span></div>
    <a class="exit ztrans-link" data-ztrans="hatch" href="<?= DASHBOARD_BASE_URL ?>/index.php">Dashboard &rsaquo;</a>
  </header>

  <div class="faq-wrap">
    <h1>Progression FAQ</h1>
    <p class="faq-lead">How the ASCEND progression system actually works — the tiers, how you advance, and the
      commands you run in Discord. New here? Start with <code>/help</code> and <code>/whats_next</code> in the OD9 server.</p>
    <a class="faq-back" href="<?= DASHBOARD_BASE_URL ?>/index.php">&larr; Back to Dashboard</a>

    <details class="faq-q" open><summary>How does the progression system work?</summary>
      <div class="faq-a">You climb <strong>five tiers</strong> by demonstrating real understanding and contribution —
        not by showing up. It's <strong>verification-first</strong>: ambient presence earns small engagement credits,
        but curriculum value (content, discussions, QOTD) only counts <em>after</em> it's evaluated (AI at cold-start,
        peers as the community grows). The point is honest growth you can prove, not a participation trophy.</div></details>

    <details class="faq-q"><summary>What are the five tiers?</summary>
      <div class="faq-a"><ul>
        <li class="tier-row"><strong>Observer</strong> — learning the fundamentals.</li>
        <li class="tier-row"><strong>Theorist</strong> — synthesizing the concepts.</li>
        <li class="tier-row"><strong>Architect</strong> — building solutions.</li>
        <li class="tier-row"><strong>Pioneer</strong> — leading initiatives.</li>
        <li class="tier-row"><strong>Benefactor</strong> — sustaining + expanding the mission.</li>
      </ul></div></details>

    <details class="faq-q"><summary>How do I advance to the next tier?</summary>
      <div class="faq-a">Every transition has <strong>three gates, and you must clear all of them</strong>:
        <ul>
          <li><strong>Credits</strong> — a threshold of earned ASCEND credits.</li>
          <li><strong>Dimensions</strong> — minimum scores across the five growth dimensions (so advancement is
            well-rounded, not one-track).</li>
          <li><strong>Demonstrations</strong> — the tier's <strong>Capstone</strong>, plus, at higher tiers, shipped
            work (a contribution project, facilitating a Think Tank, mentoring someone, a governance proposal).</li>
        </ul>
        Run <code>/whats_next</code> for the exact gate that's blocking you right now, and <code>/my_demonstrations</code>
        to see what you've shipped.</div></details>

    <details class="faq-q"><summary>How do I earn credits?</summary>
      <div class="faq-a">Through <strong>verified activity</strong>:
        <ul>
          <li><code>/content engage</code> then <code>/content comprehension</code> — read a lesson + reflect on it.</li>
          <li><code>/qotd respond</code> — answer the Question of the Day.</li>
          <li><code>/discussion discuss</code> — post a graded analysis.</li>
          <li>Plus tier-gated activities: missions, Think Tanks, research cells, projects, governance.</li>
        </ul>
        Just being present earns a little; the <strong>curriculum</strong> is what actually moves you up.</div></details>

    <details class="faq-q"><summary>What are the five dimensions?</summary>
      <div class="faq-a"><strong>Knowledge, Resource, Community, Consciousness, System.</strong> Each is capped per day,
        so you can't farm one in a single sitting. The dimension gate is why advancement reflects honest, balanced
        growth instead of grinding a single number. (Resource + System unlock meaningfully at Theorist+ — a low score
        there early is by design, not a gap.)</div></details>

    <details class="faq-q"><summary>What can I actually spend credits on?</summary>
      <div class="faq-a">There's a store &mdash; <code>/store view</code> in Discord shows what your credits can buy,
        and <code>/store unlock</code> buys it.
        <br><br>
        <strong>Spending never costs you tier progress.</strong> Your lifetime earned total is what advancement reads,
        and it is never reduced. Buying something increases a separate <em>spent</em> counter, so what you can spend is
        <em>earned &minus; spent</em> while the number the ladder cares about only ever goes up. You never have to
        choose between climbing and unlocking.
        <br><br>
        Every unlock needs <strong>two keys, not one</strong>: enough credits, <em>and</em> a minimum in the relevant
        dimension. Credits set the price; the dimension is the proof. Showing up every day earns credits, but only
        verified work &mdash; lessons, reflections, graded posts &mdash; moves a dimension. So there is no path where
        you idle your way to an unlock, and no path where you buy your way past the curriculum.
        <br><br>
        Prices are meant to <strong>tighten over time</strong>, not loosen. As the community grows the bar goes up.
        Anything you have already unlocked stays yours.</div></details>

    <details class="faq-q"><summary>What's The Bunker?</summary>
      <div class="faq-a"><strong>Unreleased music.</strong> Tracks that aren't out anywhere, in a room you unlock once
        and keep &mdash; and <strong>every track added later is yours too</strong>, with nothing further to pay.
        <br><br>
        The keys are <strong>250 credits + 25 Knowledge</strong>. The Knowledge minimum is the deliberate part: the
        Bunker is not a paywall and it isn't a reward for hanging around, it's for people actually doing the work.
        Unlock it with <code>/store unlock</code>, then <code>/bunker enter</code>.
        <br><br>
        It's the first thing in the store, not the only thing. More gets added as the room fills.</div></details>

    <details class="faq-q"><summary>How does mentorship work?</summary>
      <div class="faq-a">One member a rung ahead helps one member get unstuck, on a <strong>90-day clock</strong>.
        Not a guru, not a grader &mdash; you're not smarter than your mentee, you're <em>earlier</em>.
        It's the load-bearing relationship in the ladder: reaching <strong>Pioneer</strong> requires having
        taken someone through it, and there's no way around that gate.
        <ul>
          <li><strong>Days 1&ndash;30 &mdash; Land.</strong> One completed lesson. Not five. One &mdash; it's the
            hardest thing in the system, and everything after it is easier.</li>
          <li><strong>Days 31&ndash;60 &mdash; Practice.</strong> They say something in public: a QOTD answer,
            a discussion post, a take in the Think Tank.</li>
          <li><strong>Days 61&ndash;90 &mdash; Ship and close.</strong> Something small exists that didn't before,
            and the pair ends <em>on purpose</em> rather than fading out.</li>
        </ul>
        <strong>Want one?</strong> <code>/mentor request</code> raises your hand, and a mentor picks from everyone
        who has. <strong>Nobody gets rejected</strong> &mdash; if you're not picked this round you stay in the pool
        and are never told. <strong>Want to be one?</strong> <code>/mentor volunteer</code> once you're eligible;
        you'll see who raised their hand and <em>you</em> choose.
        Full explainer in Discord: <code>/mentor guide</code>.</div></details>

    <details class="faq-q"><summary>Should I volunteer to mentor?</summary>
      <div class="faq-a">Only if all three are true: roughly <strong>30 minutes a week for 90 days</strong>,
        you're around most weeks, and you actually want this person to get somewhere.
        <strong>A mentor who was talked into it is worse than no mentor</strong>, so this screen is yours to run on
        yourself &mdash; nobody else checks it. Cap is 3 active mentees; at three, the right answer to a fourth is no.
        <br><br>
        Ending a pair early is a legitimate outcome and isn't a failure. A bad pair that runs the full 90 days
        <em>is</em> the failure. And don't do it for the credits &mdash; store unlocks need a dimension minimum too,
        which only verified work produces, so there's no mentoring your way around the curriculum.</div></details>

    <details class="faq-q"><summary>What's the Capstone?</summary>
      <div class="faq-a">A <strong>hard gate on every tier transition</strong> — the proof you can synthesize, not just
        accumulate. You complete the tier's Capstone parts as reflections (<code>/content comprehension</code> on the
        Capstone docs), and they're <strong>human-reviewed</strong> by an elder via <code>/review content</code>.
        Capstone parts never auto-approve — they always wait for a real review.</div></details>

    <details class="faq-q"><summary>What are the key slash commands?</summary>
      <div class="faq-a"><table class="cmd-table">
        <tr><td>/help</td><td>Tier-aware menu — what's open to you now + what unlocks next.</td></tr>
        <tr><td>/whats_next</td><td>Your exact remaining gate to the next tier.</td></tr>
        <tr><td>/status · /profile</td><td>Your tier, credits, dimensions, demonstrations.</td></tr>
        <tr><td>/content engage · /content comprehension</td><td>Read a lesson, then reflect for credit.</td></tr>
        <tr><td>/qotd respond · /qotd streak</td><td>Answer the Question of the Day; track your streak.</td></tr>
        <tr><td>/discussion discuss</td><td>Post an analysis for peer/AI evaluation.</td></tr>
        <tr><td>/library · /recs get</td><td>Browse content; get a personal recommendation.</td></tr>
        <tr><td>/cohort view</td><td>Your crew + their Volume I progress.</td></tr>
        <tr><td>/achievements view · showcase</td><td>Your badges; pin one to your profile.</td></tr>
        <tr><td>/firm roster · /firm reveal</td><td>The founding circle; claim a sealed Firm badge.</td></tr>
        <tr><td>/link_email</td><td>Sync your progress to the website + the drip.</td></tr>
      </table>
      Higher tiers unlock more: <code>/thinktank</code>, <code>/cell_checkin</code>, <code>/mentor</code>,
      <code>/mission</code>, <code>/pathway</code>, <code>/debate</code>, <code>/governance</code>.</div></details>

    <details class="faq-q"><summary>What is The Firm?</summary>
      <div class="faq-a">The <strong>founding circle</strong> — the first members to complete the Observer tier. The
        inaugural <strong>5 take a Seal</strong> (the inner circle, with the Firm role); the next <strong>10 are OG
        Members</strong>. First-come, capped at 15. See who holds a seat with <code>/firm roster</code>; if you've been
        inducted, claim your sealed badge with <code>/firm reveal</code>.</div></details>

    <details class="faq-q"><summary>The live lesson is from a tier I haven't reached. Can I follow along?</summary>
      <div class="faq-a"><strong>Yes.</strong> Every zone on the board is open to <strong>look at</strong>, whatever
        tier you're on. If the 4 PM live is preaching a Theorist lesson and you joined yesterday, you can open it,
        read it, and follow the whole thing.
        <ul>
          <li>Click the zone in the row at the top of your board — the ones ahead of you are marked <em>ahead</em>.</li>
          <li>Pick the chapter, then click the lesson on the path. It opens right there.</li>
          <li>Reading ahead <strong>costs you nothing and earns you nothing</strong>. Credit, reflections and the Gate
            only count in your own zone — so you can explore freely without breaking your climb.</li>
          <li>When you're done, hit <strong>Back to &lt;your zone&gt;</strong> and make your move for the day.</li>
        </ul>
        There's a <strong>Following a sermon?</strong> button on your board with these same steps.</div></details>

    <details class="faq-q"><summary>What's the Live Roadmap?</summary>
      <div class="faq-a">OD9's future, in public — as <strong>conditional moves, not promises</strong>.
        You'll find it at <a href="/roadmap.php">the roadmap</a> (linked from your board). Every trigger
        says: <em>when X benchmark is hit, here's what becomes possible, what it requires, and what it unlocks.</em>
        How to read one:
        <ul>
          <li><strong>Approved</strong> — in the queue, waiting on its condition or its turn.</li>
          <li><strong>Active</strong> — what we're currently working toward.</li>
          <li><strong>Activated</strong> — condition met, action shipped, with a retrospective of what actually happened.</li>
        </ul>
        Triggers span six domains — <strong>Community, Revenue, Infrastructure, Research, Content, Credibility</strong> —
        and the <strong>measurable ones watch live signals</strong> (member count, supporters, MRR): the system detects
        when a condition is met and a human confirms the action was actually done before anything is marked Activated.
        Nothing gets celebrated that didn't happen. Your board shows the Active moves; the full registry lives at
        <a href="https://offda9.com/roadmap.php" style="color:#00FFF7">offda9.com/roadmap.php</a>.</div></details>

    <details class="faq-q"><summary>What's the State of the Filter?</summary>
      <div class="faq-a">The mission layer. Becoming a Type I civilization isn't a tech metric — it's a
        <strong>coordination metric</strong>: <a href="/progress.php">the scorecard</a> tracks ten outcomes a society optimized
        to pass the Great Filter would be clearing (existential-risk accounting, hunger, healthcare, climate, nuclear
        control, and more). Three things make it different from doomscroll content:
        <ul>
          <li><strong>Every number is verified against its primary source</strong> — dated, linked, never vibes. Catch a
            stale or wrong one and call it out — that's the system working, not failing.</li>
          <li>It tracks <strong>outcomes, not mechanisms</strong>. <em>What</em> a surviving civilization achieves is on
            the map; <em>how</em> humanity gets there (the policy fights) is for the community to debate.</li>
          <li>It's honest: right now the score is <strong>0/10 passing</strong>. That's the gap we're closing — and the
            reason the curriculum, the roadmap, and your climb exist.</li>
        </ul></div></details>

    <details class="faq-q"><summary>How do I help shape the roadmap?</summary>
      <div class="faq-a">The roadmap is <strong>community-governed by earned standing</strong> — influence comes from
        demonstrated understanding, and it can't be bought:
        <ul>
          <li><strong>Now, at any tier:</strong> read the triggers, pressure-test them in Discord, and challenge any
            State of the Filter number you think is off. That's real input — it shapes what gets proposed.</li>
          <li><strong>Theorist+:</strong> when proposals go to a vote, you vote — ballots are
            <strong>consciousness-weighted</strong>, so votes carry the weight of your verified growth.</li>
          <li><strong>Architect+:</strong> you can <strong>propose new triggers</strong> (<code>/roadmap propose</code>) —
            they run a public deliberation window, then a vote, and passed proposals join the registry with your name on them.</li>
        </ul>
        Nobody's reached Architect yet — the first members to earn it will be the first to put their own moves on
        OD9's roadmap. And as the resource layer comes online, members will help steer <strong>how pooled resources get
        allocated</strong> — debated and voted, in the open, the same way.</div></details>

    <details class="faq-q"><summary>Where do I track my progress?</summary>
      <div class="faq-a">Right here on the <a href="<?= DASHBOARD_BASE_URL ?>/index.php" style="color:#00FFF7">dashboard</a>,
        plus <code>/status</code> / <code>/profile</code> in Discord. Run <code>/me_link</code> for a personal web link
        to your progress page anytime.</div></details>

    <a class="faq-back bottom" href="<?= DASHBOARD_BASE_URL ?>/index.php">&larr; Back to Dashboard</a>
  </div>
</div>
</body>
</html>
