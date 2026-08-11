<?php
/**
 * THE CODEX — theorist canon lesson: Economic Dysfunction (Vol 2, Ch 16).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (grep-verified against the source 2026-07-01, no fabrication, no
 * paraphrase-as-quote); glosses + the Archivist's read are the editable
 * teaching layer. Do NOT edit the verbatim canon text.
 *
 * Anti-woo handling (recon 2026-07-01): the chapter's mechanisms and famous-
 * economist quotations are canon-grade; its house statistics are NOT — the
 * source carries the fab-stats class (the "0.07-0.13 success probability,"
 * a 37-52% band recycled across unrelated claims, re-dated citations, one
 * corrupted figure in §9). No numeric claims are ported into this lesson.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Economic Dysfunction",
  "eyebrow"   => "Volume II &middot; Diagnostic Analysis &nbsp;&bull;&nbsp; Chapter 16",
  "subtitle"  => "The second barrier under the hood: an economy that rewards capturing value over creating it, manufactures scarcity amid technical abundance, and measures success with an instrument that can&rsquo;t see most of what matters.",
  "sigil"     => "XVI",
  "cover"     => "codex-vol2.jpg",
  "archivist" => "&ldquo;Your study doc showed you the extraction machine in plain language &mdash; the cured patient as lost revenue, the annuity logic. This chapter is the formal audit behind that anger. Read it the way an engineer reads a failure report: not <em>who is evil</em>, but <em>which incentive, pointed which direction, produces this wreckage every time</em>. Machines can be redesigned. That&rsquo;s the whole point of diagnosing one.&rdquo;",
  "canon" => [
    ["p" => "As economist Amartya Sen&rsquo;s capabilities approach (Development as Freedom, 1999) argues, the ultimate test of an economic system is not the goods produced but the lives people are able to lead. By this metric, current wealth concentration patterns fail not merely distributionally but functionally&mdash;limiting collective human potential below what is both possible and necessary for addressing planetary-scale challenges.", "lead" => true],

    ["p" => "Research by economic theorists Mazzucato (The Value of Everything, 2018) and Lazonick (2014) distinguishes between value creation&mdash;activities that generate new goods, services, or capabilities that enhance individual or collective welfare&mdash;and value extraction&mdash;activities that capture existing value through positional advantage without generating new welfare-enhancing outputs."],

    ["p" => "The relationship between the financial system and the real economy now resembles that between a Formula 1 racing car and the driver&rsquo;s champagne celebration&mdash;the champagne is necessary for the event but represents a tiny fraction of resources dedicated to it."],

    ["p" => "As economist Kenneth Boulding famously observed (1973): &ldquo;Anyone who believes exponential growth can go on forever in a finite world is either a madman or an economist.&rdquo; This insight applies not merely to material throughput but to the underlying economic coordination mechanisms themselves&mdash;systems designed for one phase of civilization development that become fundamentally inadequate for subsequent phases requiring different capabilities."],

    ["p" => "As physicist and systems thinker Buckminster Fuller observed: &ldquo;We should do away with the absolutely specious notion that everybody has to earn a living. It is a fact today that one in ten thousand of us can make a technological breakthrough capable of supporting all the rest... The true business of people should be to go back to school and think about whatever it was they were thinking about before somebody came along and told them they had to earn a living.&rdquo;"],

    ["affirm" => "The analysis presented throughout this chapter establishes economic dysfunction not merely as a set of problems to be solved but as an evolutionary crisis point containing transformative potential."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "Creation vs. capture"],
    ["p"  => "The one distinction to carry out of this chapter: <b>creating value</b> (making something that didn&rsquo;t exist &mdash; a good, a service, a capability) versus <b>capturing value</b> (using position &mdash; a patent wall, a toll booth, a lobbying seat &mdash; to skim value someone else made). Both show up identically in GDP. Both pay salaries. But one grows the pool and the other drains it, and the chapter&rsquo;s core claim is that our incentive structures increasingly reward the drain. The chapter&rsquo;s Formula&nbsp;1 image is the picture to keep: the champagne (finance) was supposed to celebrate the race (the real economy) &mdash; instead we&rsquo;ve started running the race for the champagne."],

    ["h3" => "Scarcity as a product"],
    ["p"  => "The chapter&rsquo;s sharpest reframe is that much of our scarcity is <em>manufactured</em> &mdash; intellectual-property walls around ideas that cost nothing to copy, planned obsolescence engineered into products, enclosure of things that used to be commons. Not scarcity found in nature; scarcity produced, on purpose, because scarcity is what makes capture profitable. That&rsquo;s why Fuller&rsquo;s line lands: the &ldquo;everybody must earn a living&rdquo; premise was written for an age of genuine scarcity, and we keep enforcing it in an age where the bottleneck is coordination, not capacity."],

    ["callout" => ["&#9670; The instrument problem", "You steer by what you measure, and GDP is an instrument that counts a forest as worthless until it&rsquo;s lumber, counts care work as zero, and counts a cured disease as lost revenue &mdash; your study doc&rsquo;s annuity line is the measurement failure wearing a lab coat. The chapter treats this as a <em>navigation</em> problem: a civilization steering by the wrong gauge doesn&rsquo;t just misprice things, it drives toward the wrong destination with great precision. The verification-first economy you&rsquo;re earning credits in right now is OD9&rsquo;s small-scale answer: measure verified value, not raw activity."]],

    ["h3" => "How it connects"],
    ["p"  => "This barrier interlocks with the other three. <b>Religious institutions</b> pioneered tax-free extraction on unverifiable claims; <b>information control</b> keeps the extraction invisible (who profits from your attention?); <b>cognitive impairment</b> keeps you too depleted to object. And Boulding&rsquo;s madman-or-economist line is the bridge to the whole Kardashev arc you started as an Observer: a Type&nbsp;1 civilization is, before anything else, an economy that can coordinate planetary resources &mdash; something a capture-rewarding, growth-addicted system structurally cannot do. Diagnosis first. The redesign is Volume&nbsp;3&rsquo;s work, and the Architect tier is where you&rsquo;ll take it on."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;2 &middot; Chapter&nbsp;16 (<em>Economic Dysfunction</em>) &mdash; &sect;3 <em>Wealth Concentration and Inequality Systems</em>, &sect;4 <em>Artificial Scarcity Mechanisms</em>, &sect;5 <em>Financial System Failures</em>, &sect;7 <em>Financial Extraction Mechanisms</em>, &sect;10 <em>Type 1 Civilization Requirements</em>, and &sect;11 <em>Transformation Pathways</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
