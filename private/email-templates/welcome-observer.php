<?php
/**
 * Welcome Observer - New member welcome to OD9 & ASCEND Protocol
 * 
 * Variables: $member_name, $discord_name, $join_date
 */
include __DIR__ . '/partials/email-header.php';
$name = $member_name ?? $discord_name ?? 'Observer';
?>
<h2>Welcome to OD9, <?= htmlspecialchars($name) ?></h2>

<p>You just took the first step toward something most people never attempt: understanding why humanity is stuck and what it takes to get unstuck.</p>

<p>OD9 isn't another community that rewards passive consumption. We're building a framework for advancing humanity toward Type I civilization, and every member earns their place through genuine understanding.</p>

<div class="card">
<h3 style="margin-top:0">You Are Now: <span class="tier-badge tier-observer">Observer</span></h3>
<p style="margin-bottom:0">Your mission as an Observer is to understand the fundamentals: the Kardashev Scale, the Great Filter, existential risk, and why coordination failure is the core problem preventing human advancement.</p>
</div>

<h3>What Happens Next</h3>

<div class="card">
<p style="margin:0 0 10px"><strong style="color:#fff">1. Engage with Foundation Content</strong></p>
<p style="margin:0;font-size:14px">Start with the resources in the Discord. Each piece of content you engage with earns credits toward advancement. This isn't Netflix - you'll need to demonstrate comprehension.</p>
</div>

<div class="card">
<p style="margin:0 0 10px"><strong style="color:#fff">2. Write Reflections, Not Summaries</strong></p>
<p style="margin:0;font-size:14px">After engaging with content, write a reflection on what you learned and how it connects to the mission. A higher-tier member reviews it before you earn credits. Quality over quantity.</p>
</div>

<div class="card">
<p style="margin:0 0 10px"><strong style="color:#fff">3. Join a Think Tank Session</strong></p>
<p style="margin:0;font-size:14px">Collaborative analysis sessions where members explore systemic interactions. Worth 50 credits and they'll change how you think about civilizational problems.</p>
</div>

<div class="card">
<p style="margin:0 0 10px"><strong style="color:#fff">4. Participate in Discussions</strong></p>
<p style="margin:0;font-size:14px">Submit analysis in the discussion channels. Higher-tier members evaluate your posts using a structured rubric. Strong synthesis and original insights earn the most credits.</p>
</div>

<hr class="divider">

<p style="text-align:center"><strong style="color:#00BFFF">150 credits</strong> to reach <span class="tier-badge tier-theorist">Theorist</span></p>

<p>The path: Observer, Theorist, Architect, Pioneer, Benefactor. Each tier represents a deeper level of capability. You don't just read and click done. You demonstrate understanding at every step.</p>

<p style="text-align:center;margin:25px 0"><a href="https://discord.gg/spgmrXVMWq" class="btn">Open Discord</a></p>

<p style="color:#666;font-size:13px">The Great Filter kills civilizations that can't coordinate. You're here because you think we can do better. Let's prove it.</p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
