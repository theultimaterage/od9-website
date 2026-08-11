<?php
/**
 * THE CODEX — observer canon lesson: The Solution Framework (Vol 1, Ch 7).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto; glosses + the Archivist's read are the editable teaching layer.
 * Do NOT edit the verbatim canon text.
 *
 * Anti-woo: Ch7 is clean (recon-verified) — ASCEND grounded in Csikszentmihalyi,
 * Dunbar, Holland, Varela neurophenomenology, Rogers; the consciousness-first
 * passage explicitly disclaims mysticism ("neither mystical substance nor mere
 * byproduct", Chalmers' hard problem). The study layer reinforces meaning-vs-
 * mysticism. Do NOT add quantum-consciousness, invented precision, or fabricated
 * institutions; the backronym + the speculative Strawson/Seth lines are omitted.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "The Solution Framework",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 7",
  "subtitle"  => "Diagnosis and caution aren&rsquo;t a plan. ASCEND is the plan &mdash; a coordination system you upgrade while it runs, built around meaning instead of metrics.",
  "sigil"     => "VII",
  "cover"     => "codex-vol1.jpg",
  "archivist" => "&ldquo;You&rsquo;ve seen the disease and read the warning label. Now the prescription &mdash; and it isn&rsquo;t a manifesto to admire, it&rsquo;s a <em>system to run</em>. ASCEND doesn&rsquo;t fight the broken world or wait for permission to rebuild it; it upgrades the pieces while they&rsquo;re still load-bearing, and it aligns what&rsquo;s good for you with what&rsquo;s good for everyone. You&rsquo;re not reading about it &mdash; you&rsquo;re standing inside the prototype.&rdquo;",
  "canon" => [
    ["p" => "The ASCEND framework represents our comprehensive approach to this transformation challenge. Drawing on decades of research across multiple disciplines, ASCEND integrates technological innovation with consciousness development, creating practical pathways toward Type I civilization capability.", "lead" => true],

    ["p" => "Unlike revolutionary approaches that seek to demolish existing systems before building new ones (often creating catastrophic disruption), and unlike reformist approaches that make superficial changes while preserving fundamental dysfunctions, ASCEND implements what we call &ldquo;creative transformation&rdquo;&mdash;the systematic upgrade of civilization components while maintaining functional continuity throughout the process."],

    ["p" => "Consciousness-first design methodology represents our most fundamental departure from conventional system architecture. While traditional systems treat consciousness as epiphenomenal&mdash;a secondary product of physical processes&mdash;ASCEND recognizes what philosopher David Chalmers (1995) calls &ldquo;the hard problem of consciousness&rdquo;: the fact that subjective experience constitutes the foundation of all human values and meaning."],

    ["p" => "These systems fundamentally misalign individual advancement with collective flourishing, creating what game theorist Thomas Schelling (1978) called &ldquo;micromotives and macrobehavior&rdquo;&mdash;situations where individually rational choices produce collectively irrational outcomes. The ASCEND Achievement System fundamentally reimagines these structures to create what psychologist Mihaly Csikszentmihalyi (1990) terms &ldquo;autotelic activity&rdquo;&mdash;intrinsically meaningful action that simultaneously develops individual capability and contributes to collective advancement."],

    ["affirm" => "You never change things by fighting the existing reality. To change something, build a new model that makes the existing model obsolete."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "The five moving parts"],
    ["p"  => "ASCEND is an acronym, but the part that matters is the architecture &mdash; five components designed to work as one:"],
    ["ul" => [
      "<b>Achievement</b> &mdash; mechanics that channel motivation into real growth (Csikszentmihalyi&rsquo;s &ldquo;flow&rdquo;), not hollow points.",
      "<b>Community</b> &mdash; resonance-based connection that scales past the tribe (Dunbar&rsquo;s limits on stable relationships).",
      "<b>Evolutionary algorithms</b> &mdash; the system learns and improves from collective experience (Holland&rsquo;s complex adaptive systems).",
      "<b>Neural development</b> &mdash; disciplined attention to inner experience (Varela&rsquo;s neurophenomenology), not mysticism.",
      "<b>Development pathways</b> &mdash; concrete routes for individuals, groups, and institutions (Rogers&rsquo; diffusion of innovations).",
    ]],
    ["p"  => "The progression you&rsquo;re climbing is the first parts made real: <b>Achievement</b> (the tiers + capstones), <b>Community</b> (cohorts + the daily Live), and <b>Development Pathways</b> (this curriculum)."],

    ["h3" => "Meaning before metrics"],
    ["p"  => "&ldquo;Consciousness-first&rdquo; sounds mystical; it&rsquo;s the opposite. The manifesto is explicit &mdash; treat consciousness <em>&ldquo;neither as mystical substance nor as mere byproduct.&rdquo;</em> It means the system is designed around what people actually <em>value and experience</em>, not around whatever is easiest to measure. That&rsquo;s the deep difference between OD9&rsquo;s progression and a Skinner-box app: an engagement-maximizer optimizes <em>your</em> attention for <em>its</em> numbers; ASCEND&rsquo;s achievement system is built to align the two &mdash; to solve Schelling&rsquo;s trap, where everyone acting rationally still produces a collectively irrational mess (which is exactly the crisis of Chapter&nbsp;5)."],

    ["callout" => ["&#9670; Build the new model", "Buckminster Fuller&rsquo;s line is the whole strategy: don&rsquo;t fight the broken system &mdash; build a working alternative that makes it obsolete. That&rsquo;s what you&rsquo;re inside of. OD9 isn&rsquo;t a protest; it&rsquo;s a <b>prototype</b> &mdash; a small, real instance of the coordination system the manifesto argues civilization needs."]],

    ["h3" => "How it connects"],
    ["p"  => "Three chapters, one argument: Chapter&nbsp;5 named the disease (coordination failure), Chapter&nbsp;6 set the guardrails (capability isn&rsquo;t wisdom; upgrade, don&rsquo;t demolish), and this is the answer that fits inside them. <b>Creative transformation</b> is the Thesean Method at civilizational scale &mdash; replacing the planks while the ship sails &mdash; precisely the responsible path Chapter&nbsp;6 demanded. What remains is turning the framework into a roadmap: that&rsquo;s the next chapter."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;7 (<em>The Solution Framework</em>) &mdash; &sect;1 <em>Introduction: Beyond Critique to Creation</em>, &sect;2 <em>Architectural Principles of the ASCEND System</em>, and &sect;3 <em>Achievement System: Capability Through Conscious Action</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
