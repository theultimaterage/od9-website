<?php
/**
 * THE CODEX — theorist canon lesson: Scientific Institution Analysis (Vol 2, Ch 13).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (grep-verified against the source 2026-07-01); glosses + the
 * Archivist's read are the editable teaching layer. Do NOT edit verbatim canon.
 *
 * Anti-woo handling (recon 2026-07-01): the source carries the fab-stats class
 * (re-dated Baker/Freedman/Bik citations, decimal-precision retraction splits,
 * literal generation artifacts like "A78%"). None ported. GUARDRAIL: this
 * lesson must NEVER read as anti-science — the chapter's own thesis is that
 * the METHOD works and the INSTITUTIONS around it are failing; every study
 * block reinforces that split. The real replication-crisis anchors (Open
 * Science Collaboration 2015 ~36%) are corroborated mainstream findings.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Scientific Institution Analysis",
  "eyebrow"   => "Volume II &middot; Diagnostic Analysis &nbsp;&bull;&nbsp; Chapter 13",
  "subtitle"  => "The hardest audit in the volume, because it points at our own side: the method that built the modern world is being run through institutions that reward publishing over truth &mdash; and loving science means fixing its plumbing.",
  "sigil"     => "XIII",
  "cover"     => "codex-vol2.jpg",
  "archivist" => "&ldquo;Careful with this one. Every crank who ever lost an argument to evidence loves to quote science&rsquo;s problems back at it. That is not what this chapter is. The method &mdash; conjecture, test, correction &mdash; remains the only reliable path to knowledge our species has found. The audit here is of the <em>buildings around the method</em>: the journals, the funding bodies, the career ladders. We critique them the way an engineer critiques a refinery leaking its own fuel &mdash; because the fuel is precious, not because it isn&rsquo;t.&rdquo;",
  "canon" => [
    ["p" => "This growing gap between scientific potential and implementation reveals fundamental limitations in current scientific institutions that constrain civilizational development. The dysfunction is not primarily in scientists&rsquo; ability to discover or innovate, but in the institutional structures that govern how scientific knowledge is produced, validated, disseminated, and implemented.", "lead" => true],

    ["p" => "Science fundamentally depends on reliability&mdash;the expectation that reported findings reflect genuine phenomena that can be independently verified. This reliability forms the essential foundation that distinguishes scientific knowledge from other knowledge systems and enables the cumulative advancement that defines scientific progress. The past decade, however, has revealed substantial evidence that this foundation contains critical structural weaknesses across multiple scientific disciplines."],

    ["p" => "The analysis reveals that current reliability problems stem not primarily from individual researcher misconduct but from systemic dysfunction created by misaligned incentives, inadequate quality control, insufficient methodological training, and institutional resistance to correction. This systemic nature of the problem demands systemic rather than individualized solutions focused on transforming the underlying structures that shape scientific practice."],

    ["p" => "These patterns reveal how publication pressure creates progressive incentivization of corner-cutting and misconduct&mdash;not through individual moral failing but through predictable responses to structural reward systems that prioritize career advancement metrics over scientific integrity."],

    ["p" => "The evolution of scientific institutions, like all human systems, represents a series of choices rather than an inevitable trajectory&mdash;creating both responsibility for current limitations and agency in developing systems better aligned with civilization advancement requirements."],

    ["affirm" => "These evidence-based approaches demonstrate that scientific institution reform does not require waiting for undefined cultural change but can proceed through structured interventions with demonstrated effectiveness."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "The method is not on trial"],
    ["p"  => "Read the lead passage twice, because everything else hangs on its distinction: <b>the dysfunction is not in scientists&rsquo; ability to discover</b> &mdash; it&rsquo;s in the institutional plumbing around discovery. This is the same move the Evidence Standard taught you as an Observer, applied inward. Science earns its authority from self-correction, and self-correction is exactly what the chapter finds under-maintained: the replication crisis (the field&rsquo;s own psychologists found only about a third of landmark findings reproduced when the Open Science Collaboration retested them in 2015 &mdash; a finding science produced <em>about itself</em>, which is the system working) is a symptom of incentives, not of a broken method."],

    ["h3" => "Incentives, not villains"],
    ["p"  => "The causal engine is the same one you saw in economics and media: <b>structural reward systems produce predictable behavior.</b> Publish-or-perish rewards novelty over verification; nobody builds a career replicating someone else&rsquo;s result; journals prize surprising findings, and surprising findings are disproportionately wrong. No conspiracy required &mdash; just a gradient, descended by ordinary people. This is the Theorist skill the whole tier trains: when you see systemic bad output, look for the reward structure before you look for the villain."],

    ["callout" => ["&#9670; The crank-proof framing", "Hold this line for the inevitable bad-faith encounter: <em>the replication crisis is evidence FOR science, not against it.</em> No church audits its miracles and publishes the failure rate. Science found its own reliability problem, measured it, named it, and built fixes &mdash; registered reports, preregistration, open data &mdash; that measurably work. An institution that catches itself being wrong is doing the one thing that separates knowledge from doctrine. The people who quote science&rsquo;s problems to excuse believing nothing &mdash; or anything &mdash; are asking you to trade a leaky refinery for no fuel at all."]],

    ["h3" => "How it connects"],
    ["p"  => "This chapter is the epistemics pillar of the synthesis: if the barriers corrupt what a society believes, scientific institutions are the <b>repair shop for belief itself</b> &mdash; which is why their capture matters more than their size suggests. <b>Commercial capture</b> (Ch 16&rsquo;s incentives reaching into the lab) and <b>political capture</b> (Ch 19&rsquo;s think-tank pipeline manufacturing &ldquo;expertise&rdquo;) both route through here. And the affirmation is concrete: the fixes exist, are cheap, and are already deployed by early adopters. A civilization serious about Type&nbsp;1 doesn&rsquo;t wait for culture to shift &mdash; it changes the reward structure and lets ordinary people descend a better gradient. OD9&rsquo;s verification-first economy is that same bet, made small."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;2 &middot; Chapter&nbsp;13 (<em>Scientific Institution Analysis</em>) &mdash; &sect;1 <em>Introduction: Scientific Institutions as Civilizational Foundation</em>, &sect;2 <em>Historical Development</em>, &sect;5 <em>Replication Crisis and Methodological Limitations</em>, &sect;6 <em>Career Incentive Misalignment</em>, and &sect;11 <em>Transformation Pathways</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
