<?php
/**
 * THE CODEX — Observer canon lesson: The Thesean Method (Preface §V, w/ §I framing).
 * Data + the shared renderer (../_codex.php). Canon prose is VERBATIM; the study
 * layer is the teaching. Do NOT edit the verbatim paragraphs.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "The Thesean Method",
  "eyebrow"   => "Preface &middot; The Paradox of Transformation",
  "subtitle"  => "How do you change an entire civilization&mdash;without collapsing it? OD9's answer, and the method behind everything we build.",
  "sigil"     => "↻",  /* renewal — the ship rebuilt plank by plank */
  "cover"     => "codex-thesean.jpg",
  "archivist" => "&ldquo;Every revolution you were taught to admire ate its own children. Every reform too timid left the rot exactly where it was. There's a third way&mdash;older than both&mdash;and it's how we actually pull this off. Watch.&rdquo;",
  "canon" => [
    ["p" => "The philosophical foundations and consciousness-based perspective explored in the preceding sections converge toward a practical methodology for civilizational transformation&mdash;what we call the Thesean approach. This approach systematically replaces components of our social systems while maintaining functional continuity throughout the process.", "lead" => true],
    ["p" => "At its core, the Thesean approach recognizes that transformation need not mean destruction and replacement of existing structures. Just as the Athenians preserved their sacred vessel by methodically replacing each plank as it decayed, we can transform our civilization by systematically upgrading each component&mdash;from economic models to governance structures, from educational systems to consciousness itself&mdash;while ensuring that essential functions remain operational throughout the transition."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],
    ["h3" => "The ship that's still the ship"],
    ["p" => "The name comes from a 2,000-year-old puzzle (Plutarch's <em>Life of Theseus</em>): the Athenians preserved Theseus's ship by replacing each plank as it rotted. After enough time, not one original plank remained. Was it still the same ship? OD9 turns the riddle into a <b>method</b>&mdash;yes, if you replace the planks one at a time while it keeps sailing, the ship never sinks and never stops being itself."],
    ["h3" => "The third way"],
    ["p" => "Most theories of change come in two broken flavors. <b>Destructive revolution</b> tears the system down and promises to rebuild&mdash;but the demolition causes so much chaos and suffering that, as history keeps showing, the revolution devours its own children. <b>Timid incrementalism</b> avoids the chaos but leaves the underlying injustices fully intact. The Thesean Method is the third way: <b>fundamental</b> change&mdash;every plank, eventually&mdash;delivered <b>gradually</b> enough that the lights stay on."],
    ["h3" => "Sequencing is the whole game"],
    ["p" => "If you're replacing components one at a time, <em>which</em> one&mdash;and <em>in what order</em>&mdash;is everything. OD9 leans on systems theory (Donella Meadows' &ldquo;leverage points&rdquo;) to target the upgrades that move the most with the least disruption, and to keep the shaky in-between periods as short and survivable as possible."],
    ["callout" => ["&#9670; You're standing on it", "This isn't a metaphor to admire from a distance. <b>OD9 itself is the Thesean Method running.</b> The manifesto, this progression system, the community around you&mdash;they're the new planks, built and swapped in while the old world keeps running. Every tier you climb upgrades one more component of the machine that coordinates fixing everything else."]],
    ["h3" => "Carry this with you"],
    ["p" => "When the rest of the curriculum shows you how broken things are&mdash;and it will&mdash;the Thesean Method is your antidote to despair. We're not waiting for collapse, and we're not pretending tweaks are enough. We're rebuilding the ship while it sails. <b>Plank by plank.</b>"],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Preface (The Paradox of Transformation), §V: <em>The Thesean Approach</em>, with the Ship of Theseus framing from §I. The two indented paragraphs are reproduced verbatim; the Archivist's read is the study layer. See also the Creed's Principle&nbsp;6, <em>Theseus Methodology</em>.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
