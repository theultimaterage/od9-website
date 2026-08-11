<?php
/**
 * THE CODEX — theorist canon lesson: Media and Information Crisis (Vol 2, Ch 9).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (grep-verified against the source 2026-07-01, no fabrication, no
 * paraphrase-as-quote); glosses + the Archivist's read are the editable
 * teaching layer. Do NOT edit the verbatim canon text.
 *
 * Anti-woo handling (recon 2026-07-01): conceptual passages only — the source's
 * house statistics (bot-network counts, "advancement gap" percentage tables,
 * constructed-authority reports) are NOT ported; the "Sagan developmental gap"
 * attribution in §10 looks apocryphal and is omitted entirely. §2 uses a spaced
 * en-dash (–) where other sections use em-dashes; preserved verbatim below.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Media and Information Crisis",
  "eyebrow"   => "Volume II &middot; Diagnostic Analysis &nbsp;&bull;&nbsp; Chapter 9",
  "subtitle"  => "The third barrier is the one you&rsquo;re soaking in: an information system that stopped describing reality and started manufacturing it &mdash; optimized to harvest your attention, not to help you see.",
  "sigil"     => "IX",
  "cover"     => "codex-vol2.jpg",
  "archivist" => "&ldquo;A civilization is an information-processing system before it is anything else. Its media are its senses. This chapter documents what happens when the senses stop reporting the world and start selling it &mdash; and why that failure outranks every other, because a society that cannot see clearly cannot fix <em>anything</em>, including its sight. Read this one slowly. The machinery it describes is running on the device you&rsquo;re reading it with.&rdquo;",
  "canon" => [
    ["p" => "This evolution from information transmission to reality construction represents the foundation of our media and information crisis. Contemporary media systems no longer primarily inform audiences about external reality but actively construct the perceived reality within which those audiences operate &ndash; often with incentives fundamentally misaligned with truth, understanding development, or civilization advancement potential.", "lead" => true],

    ["p" => "These metrics paint a clear picture: our information systems are not merely failing to support advancement toward Type 1 civilization status but actively undermining the prerequisites for such advancement through systematic erosion of shared reality, collective trust, quality information propagation, and appropriate attention allocation."],

    ["p" => "When attention becomes comprehensively dominated by systems optimized for extraction rather than development, civilization loses the very cognitive foundation necessary for addressing any other challenge. This creates what systems theorists term a &ldquo;trap archetype&rdquo;&mdash;a self-reinforcing cycle where the exploitation of a resource prevents developing the capabilities necessary to address that exploitation."],

    ["p" => "When populations operate in fundamentally different perceived realities, even optimal policy becomes unimplementable due to legitimacy failures regardless of its technical merits."],

    ["affirm" => "These missed opportunities reveal that current information system dysfunction represents a contingent outcome of specific historical choices rather than an inevitable technological pathway."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "From window to projector"],
    ["p"  => "The lead passage marks the hinge of the whole chapter: media used to be a <em>window</em> &mdash; imperfect, smudged, but pointed at the world. Somewhere in the last few decades it became a <em>projector</em>, constructing the reality its audiences live inside, with the projectionist paid by the second of your gaze. Your <b>Information Control</b> study doc gave you the blunt version: <em>&ldquo;We don&rsquo;t have an information ecosystem. We have an information extraction system.&rdquo;</em> This chapter is the formal proof behind that line &mdash; ownership concentration builds the projector, engagement optimization aims it, and algorithmic amplification splits the audience into incompatible movies."],

    ["h3" => "The trap archetype"],
    ["p"  => "Hold on to the trap passage &mdash; it explains why this barrier outranks the others. Attention is the resource every repair effort runs on: noticing a problem, understanding it, coordinating a response. A system that strip-mines attention doesn&rsquo;t just cause harm; it <b>consumes the capacity to respond to harm</b>. That&rsquo;s the self-reinforcing cycle: the exploitation of the resource prevents developing the capabilities needed to end the exploitation. It&rsquo;s the same shape as the coordination traps you&rsquo;ll meet in <b>Meditations on Moloch</b> later in this tier &mdash; nobody has to want this outcome for the system to keep producing it."],

    ["callout" => ["&#9670; Why fragmentation kills policy", "The fourth passage deserves a beat of silence. It says even a <em>perfect</em> policy &mdash; technically flawless, fully funded &mdash; fails if the population can&rsquo;t agree it addresses a real problem. Shared reality isn&rsquo;t a nice-to-have; it&rsquo;s the substrate legitimacy runs on. This is the mechanism connecting your feed&rsquo;s outrage bait to the planet&rsquo;s inability to act on risks everyone can measure."]],

    ["h3" => "How it connects"],
    ["p"  => "Notice the affirmation: this dysfunction is <em>contingent</em> &mdash; the product of specific choices at specific junctures, not an iron law of technology. That&rsquo;s the most hopeful sentence in the chapter, because chosen systems can be re-chosen. It also interlocks with the other barriers: <b>religious institutions</b> ran reality-construction for millennia before algorithms industrialized it; <b>economic dysfunction</b> explains who profits from the projector; and the next chapter, <b>the collective psyche</b>, shows what the machinery does to the minds inside it. OD9&rsquo;s own bet &mdash; verified value, evidence-gated claims, a community that checks its sources &mdash; is a working prototype of the alternative information architecture this chapter says must be built."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;2 &middot; Chapter&nbsp;9 (<em>Media and Information Crisis</em>) &mdash; &sect;1 <em>Introduction: Information Systems as Civilizational Foundation</em>, &sect;2 <em>Historical Development of Media Dysfunction</em>, &sect;4 <em>Attention Extraction and Engagement Optimization</em>, and &sect;5 <em>Algorithmic Amplification and Reality Fragmentation</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
