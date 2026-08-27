<?php
/**
 * THE CODEX — observer canon lesson: The Evidence Standard (Vol 1, Ch 2 §III).
 * Data + the shared renderer (../_codex.php). Canon = VERBATIM §III; study = teaching.
 * Deliberately EXCLUDES Ch 2 §IV "co-equal verification" framing and ALL of §VI
 * (bhumis/past-life/subtle-energy) per the founder's anti-woo standard; uses only the
 * woo-clean evidence-hierarchy + responsible-speculation spine. Does NOT reproduce the
 * §III "Manifesto of Theseus" internal working-title sentence.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "The Evidence Standard",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 2",
  "subtitle"  => "How OD9 weighs every claim&mdash;including its own. The rule that separates us from both dogma and woo.",
  "sigil"     => "II",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;You know who we are, and how we mean to change things. Here&rsquo;s the rule that keeps us honest while we do it&mdash;the cleanest line there is between a movement and a cult: we say out loud how sure we&rsquo;re allowed to be, on every claim, <em>including our own</em>.&rdquo;",
  "canon" => [
    ["p" => "The challenge lies not in seeking absolute certainty&mdash;which philosopher Karl Popper (1963) demonstrated to be both impossible and unnecessary for scientific progress&mdash;but in developing sophisticated frameworks for assessing and utilizing knowledge across a spectrum of certainty levels.", "lead" => true],
    ["p" => "We propose a comprehensive classification system that maintains intellectual integrity while enabling transformative vision:"],
    ["principle" => [1, "Established Facts", "represent claims supported by extensive empirical evidence, replicated findings, and scientific consensus. These include laws of physics like thermodynamics, well-documented historical events, and consistently replicated experimental results.", "Bet your life on these. Thermodynamics, the speed of light, the moon landing. Revisable in principle&mdash;but functionally, bedrock."]],
    ["principle" => [2, "Strong Theories", "encompass frameworks supported by substantial empirical research but subject to ongoing refinement. Examples include evolutionary theory, quantum mechanics, and general relativity&mdash;theories with overwhelming evidential support that nonetheless continue evolving in their details and applications.", "Overwhelming evidence, still refined at the edges. Evolution. Relativity. You build on these&mdash;you don&rsquo;t dismiss them because they&rsquo;re not &lsquo;100% final.&rsquo;"]],
    ["principle" => [3, "Emerging Concepts", "include ideas with preliminary empirical support and theoretical coherence but requiring further validation. These represent promising directions that merit exploration while maintaining appropriate epistemic humility. Examples might include certain theories in consciousness studies, early-stage experimental technologies, or preliminary models in complexity science.", "Promising, partial evidence. Worth exploring&mdash;not yet load-bearing. Hold them with interest <em>and</em> humility."]],
    ["principle" => [4, "Reasoned Speculation", "encompasses logical extensions beyond current evidence that maintain plausibility and internal coherence. These enable exploration of possibilities beyond current knowledge while clearly distinguishing from established understanding.", "Logical &lsquo;what-ifs&rsquo; past the evidence. <b>Allowed</b>&mdash;a project aimed at a future that doesn&rsquo;t exist yet needs them&mdash;but ALWAYS labeled as speculation, never smuggled in as fact. <b>This is the line.</b>"]],
  ],
  "study" => [
    ["label" => "The Archivist's read"],
    ["h3" => "Why this is the line"],
    ["p" => "You&rsquo;ve met the Creed (who we are) and the Thesean Method (how we change things). This is the rule that keeps both honest: <b>OD9 ranks how confident it&rsquo;s entitled to be&mdash;out loud, on every claim.</b> An Established Fact and a Reasoned Speculation never get to wear the same clothes. That single habit is the cleanest line there is between a movement and a cult."],
    ["h3" => "Speculation is allowed &mdash; lying about it isn&rsquo;t"],
    ["p" => "A movement aiming at a future that doesn&rsquo;t exist yet <em>has</em> to speculate. OD9 doesn&rsquo;t ban that&mdash;it <b>labels</b> it. The whole point of the four tiers is to let you, in the manifesto&rsquo;s own words, &ldquo;navigate between domains of greater and lesser certainty without falling into either dogmatism or relativism.&rdquo; Dogmatism = false certainty. Relativism = pretending nothing is more true than anything else. The tiers refuse both."],
    ["h3" => "Say it like you mean it &mdash; only as much as you mean it"],
    ["p" => "So OD9 phrases claims with their real confidence baked in: not &ldquo;this is true,&rdquo; but &ldquo;the evidence suggests,&rdquo; &ldquo;roughly 70% confidence,&rdquo; &ldquo;this <em>might</em>.&rdquo; It&rsquo;s a guard against what Daniel Kahneman called the illusion of validity&mdash;mistaking a good story for solid data. As he put it: <em>&ldquo;Confidence is not a good guide to accuracy.&rdquo;</em>"],
    ["callout" => ["&#9670; The line vs. dogma AND woo", "Dogma claims false certainty. Woo dresses unfalsifiable claims as fact. The evidence standard refuses both at once: every claim wears its real confidence level. If something can&rsquo;t be tested or shown, it doesn&rsquo;t get to cosplay as an Established Fact&mdash;no matter how good the story sounds. <b>Hold OD9 itself to this.</b>"]],
    ["h3" => "You&rsquo;re already living it"],
    ["p" => "This is why your <em>value</em> on the board is verified, not vibes&mdash;and why OD9 says &ldquo;evidence over ideology&rdquo; even when the comfortable answer would convert better. The standard points at us too. When we cross from fact into speculation, you should be able to <em>see</em> the label. If you ever can&rsquo;t&mdash;call it out."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;2 (<em>Theoretical Foundations &amp; Methodology</em>), §III: <em>Systems of Evidence &mdash; From Established Fact to Reasoned Speculation</em> (the four tiers + the Popper passage are reproduced verbatim; Kahneman&rsquo;s line is from §III.B). The Archivist's read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
