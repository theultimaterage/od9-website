<?php
/**
 * THE CODEX — observer canon lesson: The Twin Singularities (Vol 1, Ch 4 §IV, +§IX).
 * Data + the shared renderer (../_codex.php). Canon prose is VERBATIM; the study layer
 * is the teaching. The "consciousness singularity" half is framed honestly (real
 * evidence + clearly-labeled speculation) per the founder's anti-woo standard —
 * NO Maharishi Effect / parapsychology / the §IX Wigner misquote.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "The Twin Singularities",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 4",
  "subtitle"  => "We just gained the power to end ourselves. The real question is whether our <em>wisdom</em> can grow fast enough to match it.",
  "sigil"     => "IV",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;You&rsquo;ve seen where we sit on the ladder, and what tends to kill civilizations at exactly this rung. Here&rsquo;s the part most people miss: the danger isn&rsquo;t the power. It&rsquo;s the <em>gap</em> between the power and the wisdom to wield it. Mind the gap.&rdquo;",
  "canon" => [
    ["p" => "The necessity of integrating technological and consciousness evolution creates what we term the Twin Singularities&mdash;complementary developmental trajectories that must converge for successful navigation of our evolutionary bottleneck.", "lead" => true],
    ["p" => "Recent analysis by existential risk researchers suggests that the period between achieving advanced technology and developing commensurate wisdom represents a narrow evolutionary bottleneck that may explain the Great Silence (Bostrom, 2002; Ord, 2020)."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],
    ["h3" => "Two transformations, not one"],
    ["p" => "You&rsquo;ve already met the <b>technological singularity</b> in the wild&mdash;exponential technology, AI that may soon improve itself. That&rsquo;s one curve climbing fast. OD9&rsquo;s claim is that there&rsquo;s a <em>second</em> curve that has to climb with it: the growth of human <b>wisdom, awareness, and coordination</b>&mdash;what the manifesto calls the <b>consciousness singularity</b>. Power is the easy half. Wisdom is the half we keep underinvesting in."],
    ["h3" => "Why they have to converge"],
    ["p" => "Technological power without matching wisdom isn&rsquo;t progress&mdash;it&rsquo;s a loaded gun in the hands of a species that still can&rsquo;t reliably cooperate. The pattern behind every barrier OD9 fights is the same one: <b>capability outran coordination</b>. The Twin Singularities thesis is simply that we close that gap <em>on purpose</em>&mdash;let the wisdom curve catch the power curve before the gap catches us."],
    ["callout" => ["&#9670; This is the Great Filter, restated", "Remember the cosmic silence? Here&rsquo;s the unsettling version: the gap between <em>getting</em> powerful and <em>getting wise enough to survive it</em> may be exactly the bottleneck that ends most civilizations&mdash;and we may be standing in it right now. That&rsquo;s not doom. It&rsquo;s a job description."]],
    ["h3" => "An honest line about the &ldquo;consciousness&rdquo; half"],
    ["p" => "Be clear-eyed here, because OD9 runs on evidence, not vibes. The <em>technological</em> singularity is well-grounded (real AI-safety research&mdash;the alignment and control problems). The <em>consciousness</em> side has solid science behind parts of it&mdash;the measurable neuroplasticity of sustained contemplative practice, developmental psychology&mdash;but in some tellings it gets stretched toward claims that aren&rsquo;t established. We keep the defensible parts and <b>label the speculation as speculation</b>. The core argument&mdash;that wisdom has to scale with power&mdash;stands on its own. It doesn&rsquo;t need the woo."],
    ["h3" => "Where you come in"],
    ["p" => "This is the whole reason OD9 exists. A system that rewards <em>verified</em> contribution, mentorship, and coordination is a machine for growing the wisdom curve&mdash;deliberately, together, fast. Every tier you climb adds one more unit of the second singularity. <b>You&rsquo;re not just learning the stakes&mdash;you&rsquo;re closing the gap.</b>"],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;4 (<em>The Great Awakening</em>), §IV: <em>The Twin Singularities</em>, with the bottleneck synthesis from §IX: <em>The Cosmic Stakes</em>. The two indented passages are reproduced verbatim; the Archivist's read is the study layer. The stakes it builds on&mdash;the Kardashev transition (~Type&nbsp;0.7), the Great Filter, the narrowing window&mdash;are covered in the earlier Observer readings.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
