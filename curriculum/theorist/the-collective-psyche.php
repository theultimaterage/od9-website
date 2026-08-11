<?php
/**
 * THE CODEX — theorist canon lesson: Mental Health and Collective Psyche (Vol 2, Ch 10).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (grep-verified against the source 2026-07-01, no fabrication, no
 * paraphrase-as-quote); glosses + the Archivist's read are the editable
 * teaching layer. Do NOT edit the verbatim canon text.
 *
 * Anti-woo handling (recon 2026-07-01, HIGHEST-scrutiny chapter of wave 1):
 * "collective consciousness/psyche" is framed in the study layer as an
 * aggregate + network phenomenon (Christakis/Fowler contagion research),
 * explicitly NOT a group mind — the §5 "measurable collective consciousness"
 * escalation is not ported. The debunked goldfish-attention-span stat, the
 * effect-size tables, psychedelic remission rates, "consciousness engineering"
 * promises, and all house percentages stay out. Arguments only, no numbers.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Mental Health and Collective Psyche",
  "eyebrow"   => "Volume II &middot; Diagnostic Analysis &nbsp;&bull;&nbsp; Chapter 10",
  "subtitle"  => "The fourth barrier lives between your ears: a civilization&rsquo;s exterior power racing ahead of its interior development &mdash; minds systematically depleted, distracted, and stalled by the very systems the other three barriers built.",
  "sigil"     => "X",
  "cover"     => "codex-vol2.jpg",
  "archivist" => "&ldquo;The other barriers are out there &mdash; institutions, markets, feeds. This one is in here. The chapter&rsquo;s claim is uncomfortable and precise: psychological capacity is <em>infrastructure</em>, as load-bearing as any power grid, and ours is failing under engineered load. Read it without flinching and without mysticism &mdash; the &lsquo;collective psyche&rsquo; is not a spirit hovering over us; it is what millions of individual minds, wired together, measurably do to each other.&rdquo;",
  "canon" => [
    ["p" => "The implications for civilizational development are profound. A civilization cannot progress beyond the psychological capacity of its collective consciousness: the exterior development of a society cannot long exceed its interior development without creating severe social pathologies. This principle establishes mental health as prerequisite infrastructure for higher civilization capabilities.", "lead" => true],

    ["p" => "Beyond the direct manifestations of psychological disorder lies a more subtle yet perhaps more consequential dimension of mental health crisis: the systematic restriction of consciousness development through sophisticated psychological manipulation. This dimension represents not accidental dysfunction but deliberate limitation&mdash;creating perhaps the most profound evolutionary bottleneck preventing civilization advancement."],

    ["p" => "This evidence suggests that addiction functions less as individual pathology and more as adaptive response to social contexts increasingly engineered for disconnection&mdash;revealing how modern social structures create vulnerabilities that addiction industries systematically exploit."],

    ["p" => "Research across disciplines converges on a fundamental conclusion: Type 1 civilization capabilities cannot emerge or persist with predominantly Type 0 psychology."],

    ["affirm" => "While the challenges identified are severe, research across multiple domains indicates specific approaches with significant development potential."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "Interior infrastructure"],
    ["p"  => "The lead passage reframes mental health from a private medical matter into <b>civilizational infrastructure</b>. The logic: every collective capability &mdash; coordinating, deliberating, planning past the next quarter &mdash; runs on individual minds, and minds are networked. Social-neuroscience research (Christakis &amp; Fowler&rsquo;s network studies) shows emotional states and behaviors spreading through social ties in contagion-like patterns; depletion propagates, and so does resilience. A society whose exterior power &mdash; nukes, AI, planetary-scale industry &mdash; races ahead of its interior development is running heavy machinery on a cracked foundation. That gap between capability and psychological capacity is the same capability&ndash;wisdom gap you&rsquo;ve now met in every barrier."],

    ["h3" => "Engineered, not accidental"],
    ["p"  => "The chapter&rsquo;s hardest claim repeats the pattern your <b>Cognitive &amp; Neurological Impairment</b> study doc taught: the depletion is <em>load-bearing for someone</em>. Attention is fragmented because fragmentation monetizes. Addiction is reframed here as an <b>adaptive response to engineered disconnection</b> &mdash; the system manufactures the loneliness, then sells the compulsive relief, then bills the treatment. Blame-the-victim framing dissolves once you see the supply chain. That&rsquo;s not an excuse to surrender agency; it&rsquo;s the map of exactly where agency has to push back."],

    ["callout" => ["&#9670; &ldquo;Collective psyche&rdquo; is a measurement, not a mind", "Hold the Evidence Standard here, because this is where sloppy readers drift into mysticism. When the manifesto says <em>collective consciousness</em>, it means the aggregate, networked properties of many individual minds &mdash; moods and behaviors that measurably propagate through social ties &mdash; not a group mind, a field, or any substance floating above the crowd. The network effects are real, replicated science. The spooky version is not. OD9 asserts the first and refuses the second, and a Theorist should be able to explain the difference cold."]],

    ["h3" => "How it connects"],
    ["p"  => "This closes the circuit of the Four Barriers: <b>religious institutions</b> install the epistemology, <b>economic dysfunction</b> sets the extraction incentives, <b>information systems</b> aim the machinery at your attention &mdash; and this chapter is the bill: what all three do to the minds that would otherwise resist them. The &ldquo;Type 0 psychology&rdquo; line is the volume&rsquo;s thesis in miniature: you cannot bolt Type&nbsp;1 tools onto Type&nbsp;0 minds and expect survival. Which is why OD9&rsquo;s progression tracks a <em>consciousness practice</em> dimension at all &mdash; and why the transformation menu here (evidence-based therapy at scale, prevention, institutional redesign; active research, not settled magic) is diagnosis handing you a workbench. <b>Synergy of Barriers</b> is next: the four machines, running as one."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;2 &middot; Chapter&nbsp;10 (<em>Mental Health and Collective Psyche</em>) &mdash; &sect;1 <em>Introduction: Mental Health as Civilizational Foundation</em>, &sect;7 <em>Addiction Systems and Their Neurological Impacts</em>, &sect;8 <em>Consciousness Restriction Through Psychological Manipulation</em>, &sect;10 <em>Type 1 Civilization Requirements</em>, and &sect;11 <em>Transformation Pathways</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
