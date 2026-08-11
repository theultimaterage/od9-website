<?php
/**
 * OD9 — The Books (transparency ledger).
 *
 * The funding design's core promise (founder-settled 2026-07-04): radical allocation
 * transparency — where money comes from, where it goes, and what the founder takes,
 * all in the open. INTEGRITY RULE (same as the State of the Filter): every figure on
 * this page is either LIVE from the database, MEASURED from logs, or a stated POLICY —
 * never estimated to look better. Quiet surface for now (linked from support + progress,
 * not the nav) — the values are loud, the ask stays soft until traction.
 */
declare(strict_types=1);

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config/database.php';
}
@require_once $configPath;

const TIER_PRICES_CENTS = [
    'theorist' => 500, 'architect' => 1500, 'pioneer' => 3000,
    'benefactor' => 5000, 'founding' => 10000,
];

$mrr_dollars = 0; $supporters = 0;
// SOURCE OF TRUTH: the bot's SQLite patreon_supporters (written directly by the
// Patreon webhook; tier_amount is stored in dollars). The MySQL od9_members mirror
// under-reports (its patreon flags don't sync — it read $0 while the bot knew $10,
// caught 2026-07-04); it remains only a fallback.
try {
    require_once __DIR__ . '/includes/od9_sqlite.php';
    $sq = getOd9SqliteConnection();
    if ($sq) {
        $row = $sq->query(
            "SELECT COUNT(*) AS n, IFNULL(SUM(tier_amount),0) AS mrr
             FROM patreon_supporters WHERE status = 'active'"
        )->fetch();
        $supporters = (int)($row['n'] ?? 0);
        $mrr_dollars = (int)floor((float)($row['mrr'] ?? 0));
    }
} catch (Throwable $e) {
    if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') error_log('[funding.php] sqlite read failed: ' . $e->getMessage());
}
if ($supporters === 0) {
    try {
        if (function_exists('getDatabaseConnection')) {
            $pdo = getDatabaseConnection();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query(
                "SELECT LOWER(patreon_tier) AS tier, COUNT(*) AS n FROM od9_members
                 WHERE is_patreon_supporter = 1 AND patreon_tier IS NOT NULL AND patreon_tier != ''
                 GROUP BY LOWER(patreon_tier)"
            );
            $cents = 0;
            foreach ($stmt as $row) {
                $cents += (TIER_PRICES_CENTS[$row['tier']] ?? 0) * (int)$row['n'];
                $supporters += (int)$row['n'];
            }
            $mrr_dollars = (int)floor($cents / 100);
        }
    } catch (Throwable $e) {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') error_log('[funding.php] DB read failed: ' . $e->getMessage());
    }
}
$verified = '2026-07-04';

$page_title = 'The Books — How OD9 Is Funded';
$page_description = 'The OD9 transparency ledger: where the money comes from, where every dollar goes, and what the founder takes — all in the open. Radical allocation transparency.';
$page_slug = 'funding.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
:root{--b:#00BFFF;--gold:#FFD700;--green:#2ECC71;--d:#0A0A0A;--dd:#111;--c:#C0C0C0}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;padding:calc(var(--nav-height) + 1rem) 1rem 4rem}
.wrap{max-width:840px;margin:0 auto}
header.hd{text-align:center;margin-bottom:2.5rem}
.tag{font-family:'Rajdhani',sans-serif;color:var(--b);letter-spacing:4px;font-size:.95rem;text-transform:uppercase;margin-bottom:.75rem}
h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.8rem,5vw,2.6rem);font-weight:900;color:#fff;letter-spacing:3px;margin-bottom:.5rem;text-shadow:0 0 20px rgba(0,191,255,.45)}
.sub{color:#888;font-size:1rem;line-height:1.7;max-width:600px;margin:0 auto}
.sec{background:var(--dd);border:1px solid #222;border-radius:12px;padding:1.8rem 1.6rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
.sec::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--b),transparent)}
.sec.gold::before{background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.sec h2{font-family:'Orbitron',sans-serif;font-size:1.15rem;color:#fff;letter-spacing:1px;margin-bottom:1.1rem}
.line{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;padding:.8rem 0;border-bottom:1px solid #1c1c1c;flex-wrap:wrap}
.line:last-child{border-bottom:none}
.line .k{color:#ddd;font-weight:600}
.line .k small{display:block;color:#777;font-weight:400;font-size:.85rem;margin-top:.2rem;line-height:1.5;max-width:52ch}
.line .v{font-family:'Orbitron',sans-serif;color:var(--b);font-size:1.05rem;white-space:nowrap}
.line .v.gold{color:var(--gold)} .line .v.green{color:var(--green)} .line .v.dim{color:#666;font-size:.85rem}
.policy{list-style:none;counter-reset:p}
.policy li{counter-increment:p;padding:.7rem 0 .7rem 2.4rem;position:relative;border-bottom:1px solid #1c1c1c;color:#bbb;line-height:1.65}
.policy li:last-child{border-bottom:none}
.policy li::before{content:counter(p);position:absolute;left:0;top:.75rem;width:1.6rem;height:1.6rem;border:1px solid var(--b);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:.8rem;color:var(--b)}
.policy strong{color:#fff}
.note{text-align:center;color:#777;font-size:.85rem;line-height:1.7;margin-top:1.5rem}
.note a{color:var(--b);text-decoration:none}
.cta-row{display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin-top:1.8rem}
.btn{padding:.85rem 1.8rem;border-radius:6px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.95rem;text-transform:uppercase;letter-spacing:2px;text-decoration:none;transition:.3s}
.btn-primary{background:linear-gradient(135deg,var(--b),#00A0FF);color:var(--d)}
.btn-secondary{background:transparent;color:var(--b);border:1px solid var(--b)}
.btn:hover{transform:translateY(-2px)}
</style>
</head>
<body>
<?php $current_page = ''; include('includes/nav.php'); ?>
<div class="wrap">
  <header class="hd">
    <p class="tag">Radical allocation transparency</p>
    <h1>The Books</h1>
    <p class="sub">Where OD9's money comes from, where every dollar goes, and what the founder
      takes — in the open, always. Most organizations hide this page. We think hiding it is
      exactly how everything got this broken.</p>
  </header>

  <section class="sec">
    <h2>Money In <span style="color:#666;font-size:.75rem;font-family:'Rajdhani',sans-serif;letter-spacing:1px">— LIVE FROM THE DATABASE</span></h2>
    <div class="line"><span class="k">Monthly recurring (Patreon)<small><?= $supporters ?> active supporter<?= $supporters === 1 ? '' : 's' ?> — live count, refreshes every page load</small></span><span class="v gold">$<?= number_format($mrr_dollars) ?>/mo</span></div>
    <div class="line"><span class="k">One-time direct support<small>Cash App / PayPal / Zelle gifts (see <a href="support.php" style="color:var(--b)">support</a>) — not centrally tracked yet; a gift ledger is on the build list</small></span><span class="v dim">untracked</span></div>
  </section>

  <section class="sec">
    <h2>Money Out <span style="color:#666;font-size:.75rem;font-family:'Rajdhani',sans-serif;letter-spacing:1px">— MEASURED, NOT ESTIMATED</span></h2>
    <div class="line"><span class="k">AI evaluation (the grading engine)<small>Every reflection, discussion, and capstone is AI-evaluated; every call is logged. Measured spend since logging began (Jun 18): roughly one dollar. Routine evaluations moved to a ~4&times; cheaper model on <?= $verified ?> — capstones stay on the strongest model.</small></span><span class="v">~$1 to date</span></div>
    <div class="line"><span class="k">Server (VPS)<small>Gifted by family — the whole stack runs on a box the founder's uncle bought. Zero monthly cost, full transparency about why.</small></span><span class="v green">$0/mo</span></div>
    <div class="line"><span class="k">Domains, tools, everything else<small>Absorbed out of pocket by a founder with zero income. That's not a flex — it's the current reality, and it's why the support page exists.</small></span><span class="v dim">founder-absorbed</span></div>
    <div class="line"><span class="k">Founder compensation<small>Yes, the founder intends to get paid — in the open, on a public ladder, never skimmed: a modest stipend when MRR sustains $1,000/mo, approaching livable at $2,500/mo. Those thresholds are public triggers on the <a href="roadmap.php" style="color:var(--b)">roadmap</a> (T-REV-1000, T-REV-2500). Until then:</small></span><span class="v">$0</span></div>
  </section>

  <section class="sec gold">
    <h2>Where Every Next Dollar Goes</h2>
    <ul class="policy">
      <li><strong>Keep the lights on</strong> — hosting, domains, the pipeline that runs the whole stack.</li>
      <li><strong>Cover members' learning</strong> — evaluations cost money and members never pay for them. At current rates, <strong>$5 covers roughly 1,000 member evaluations</strong> — your support directly funds someone else's climb.</li>
      <li><strong>Founder stipend per the public ladder</strong> — visible fair compensation instead of hidden extraction is the whole point.</li>
      <li><strong>Production &amp; growth</strong> — music, the science series, research partnerships, the mission.</li>
    </ul>
    <p style="color:#999;font-size:.92rem;line-height:1.7;margin-top:1.1rem">And as members earn standing, <strong style="color:var(--gold)">allocation itself goes to community vote</strong> — supporting OD9 builds your Resource dimension, and by design it adds <strong style="color:var(--gold)">zero</strong> governance vote weight. Support is recognized; influence is earned. Money can't buy power here — that's structural, not a promise.</p>
  </section>

  <div class="cta-row">
    <a class="btn btn-primary" href="support.php">Support the Mission</a>
    <a class="btn btn-secondary" href="progress.php">Live Counters</a>
    <a class="btn btn-secondary" href="roadmap.php">The Roadmap</a>
  </div>

  <p class="note">Figures verified <?= $verified ?> · live numbers refresh per page load ·
    think something's off or missing? Call it out in the
    <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener">Discord</a> —
    catching us is the system working.</p>
</div>
<?php include('includes/footer.php'); ?>
</body>
</html>
