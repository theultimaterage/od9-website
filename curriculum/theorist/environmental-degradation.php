<?php
/**
 * THE CODEX — theorist canon lesson: Environmental Degradation (Vol 2, Ch 21).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (grep-verified against the source 2026-07-01); glosses + the
 * Archivist's read are the editable teaching layer. Do NOT edit verbatim canon.
 *
 * Anti-woo handling (recon 2026-07-01): §§1-2 and 4 of the source are the
 * IPCC-class core (Armstrong McKay 2022, Ceballos 2020, planetary boundaries —
 * real, verifiable); §§10-11 are the fab-stats swamp (dead-author "Ostrom
 * 2022," batch-dated constructed authorities, invented correlations). Canon
 * draws ONLY from the evidence-solid sections; no doomer exaggeration — the
 * claims here are the well-evidenced ones, which are heavy enough.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Environmental Degradation",
  "eyebrow"   => "Volume II &middot; Diagnostic Analysis &nbsp;&bull;&nbsp; Chapter 21",
  "subtitle"  => "The stakes pillar of the diagnosis: the barriers aren&rsquo;t failing us in the abstract &mdash; they&rsquo;re failing us against a clock, on the one infrastructure no civilization gets to replace.",
  "sigil"     => "XXI",
  "cover"     => "codex-vol2.jpg",
  "archivist" => "&ldquo;As an Observer you learned the window is closing. This chapter is the gear-work behind that sentence &mdash; carbon arithmetic, tipping cascades, extinction&rsquo;s one-way door. Read it as an engineer, not a doomer: every number here is the well-evidenced kind, and the difference matters, because false alarm and false comfort are the same failure. The environment is not one issue on the list. It is the table the list is sitting on.&rdquo;",
  "canon" => [
    ["p" => "This transgression represents not merely an environmental crisis but a fundamental constraint on civilizational potential. As Lenton (2020) argues, the stability of Earth systems provides the necessary conditions for complex society to exist and evolve. When these systems destabilize, they create cascading disruptions across human systems from agriculture to economics to governance. Thus, environmental integrity is not an optional component of advancement but a prerequisite condition.", "lead" => true],

    ["p" => "The carbon budget framework reveals the stark mathematical reality of climate constraints: either rapid systematic transformation of energy systems must occur, or temperature thresholds will be exceeded with consequences that persist for centuries to millennia."],

    ["p" => "Many tipping points become apparent only after they have been crossed, creating what Armstrong McKay et al. (2022) term an &ldquo;early warning gap.&rdquo; By the time empirical evidence conclusively demonstrates a tipping point has been crossed, intervention options have already diminished or disappeared."],

    ["p" => "The collective evidence indicates not merely incremental biodiversity loss but systematic collapse across multiple biological systems&mdash;a pattern consistent with previous mass extinction events in Earth&rsquo;s history but occurring at an unprecedented rate. As Ceballos et al. (2020) conclude: &ldquo;The ongoing sixth mass extinction may be the most serious environmental threat to the persistence of civilization, because it is irreversible.&rdquo;"],

    ["affirm" => "Instead, evidence suggests that ecological regeneration must occur simultaneously with technological advancement rather than following it sequentially."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "Infrastructure, not issue"],
    ["p"  => "The lead passage performs the reframe the whole lesson rests on: the environment is not a policy domain competing with the economy for attention &mdash; it is the <b>physical substrate</b> the economy, the state, and every other system run on. The planetary-boundaries framework (Rockstr&ouml;m and colleagues&rsquo; real, peer-reviewed work) says several of Earth&rsquo;s nine operating limits are already transgressed. Ch 16 gave you the punchline early: the economy is a wholly owned subsidiary of the environment. This chapter is the parent company&rsquo;s audited books."],

    ["h3" => "The early-warning gap"],
    ["p"  => "The tipping-point passage carries the chapter&rsquo;s most important decision-theory lesson: <b>for irreversible systems, waiting for certainty is itself the failure mode.</b> By the time the evidence is conclusive, the option to act has expired. This inverts the burden of proof most institutions run on &mdash; and connect it to the barriers you&rsquo;ve studied: an information system that manufactures doubt (Ch 9) plus a political system designed not to act (Ch 19) plus an eschatology that reads collapse as prophecy (Ch 22) is precisely the wrong civilization to be standing inside an early-warning gap. That convergence &mdash; not any single emission curve &mdash; is the diagnosis of Volume II."],

    ["callout" => ["&#9670; Evidence-heavy, not doom-heavy", "Notice which claims this lesson stands on: the carbon-budget arithmetic (IPCC-class accounting), the tipping-elements research (Armstrong McKay et al., Science, 2022 &mdash; real), and Ceballos&rsquo; extinction work (PNAS, 2020 &mdash; real). No mystic Gaia, no fabricated precision, no &ldquo;ten years to save the world&rdquo; theatrics. The honest numbers are heavy enough &mdash; and honesty is the strategic asset: a community that never cries wolf is the one still credible when the wolf is real."]],

    ["h3" => "How it connects"],
    ["p"  => "This is where the volume&rsquo;s stakes stop being abstract. The Great Filter question you met as an Observer &mdash; and will meet again in Bostrom&rsquo;s paper just after this lesson &mdash; asks why the galaxy is silent. Here is one concrete candidate mechanism: intelligent species whose coordination systems fail slower than their planets do. And the affirmation is the anti-despair move the manifesto keeps making: the fix is not &ldquo;grow now, clean up later&rdquo; (that curve never bends on its own) but regeneration <em>concurrent</em> with advancement &mdash; which is exactly why OD9 stakes everything on building coordination capacity now, not after the singularity arrives to save us. The clock is the reason the climb exists."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;2 &middot; Chapter&nbsp;21 (<em>Environmental Degradation</em>) &mdash; &sect;1 <em>Introduction: Environmental Systems as Civilizational Foundation</em>, &sect;2 <em>Climate Crisis Acceleration and Tipping Points</em>, and &sect;4 <em>Ecosystem Collapse and Biodiversity Loss</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
