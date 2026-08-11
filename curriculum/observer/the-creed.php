<?php
/**
 * THE CODEX — Observer canon lesson: The OD9 Creed (Vol 1, Ch 1 §II).
 * Data + the shared renderer (../_codex.php). Canon text is VERBATIM; the
 * glosses + study layer are the teaching layer. Do NOT edit the verbatim text.
 */
declare(strict_types=1);

/* The nine principles — VERBATIM. [name, verbatim text, gloss(teaching)]. */
$P = [
  ["Conscious Evolution",
   "Evolution need not be left to chance. Through cooperation and systematic understanding, we deliberately guide our species beyond its current biological and cultural limits.",
   "You're not waiting for the universe, or luck, to improve us. We choose to steer human development&mdash;<b>on purpose, together</b>."],
  ["Evidence-Based Progress",
   "We rely on demonstrable results, not abstract theory, measuring verified capability over aspiration. We ground our methods in empirical science and treat contemplative practice as a source of hypotheses to be tested, never as proof in itself.",
   "No vibes, no dogma. If it's real, you can <b>show it</b>&mdash;and a practice that can't be tested is a <em>question</em>, not an answer. (It's why your value is earned, not granted.)"],
  ["Collective Achievement",
   "Civilization advances through cooperation, not zero-sum competition. We build frameworks for genuine collaboration toward shared goals, because a coordinated group out-thinks any lone genius.",
   "We win together or not at all. The system rewards <b>lifting others</b>, not stepping on them&mdash;because a coordinated group out-thinks any lone genius."],
  ["Theseus Methodology",
   "We transform civilization the way the Ship of Theseus is renewed&mdash;replacing failing components one at a time, never destroying what still works. Neither utopian rupture nor mere tinkering, but fundamental change without collapse.",
   "Not burning it all down, and not just tinkering: like the Ship of Theseus, swap each failing plank one at a time and the ship never sinks. This is <b>the</b> method&mdash;you go deep on it in the very next lesson, <b>The Thesean Method</b>."],
  ["Universal Accessibility",
   "Every person can contribute. We design systems anyone can join regardless of starting capability or circumstance, so the benefits reach all of humanity rather than a privileged few.",
   "Where you start doesn't cap where you go. Broke, busy, or self-taught&mdash;<b>the ladder is built so anyone can climb it</b>."],
  ["Cosmic Perspective",
   "We act aware of our place in the universe&mdash;a young species whose choices may decide whether consciousness endures in this corner of the cosmos. This is reason for sober stewardship, not grandiosity.",
   "Zoom out. We're a young species deciding whether consciousness survives in this corner of the galaxy. <b>Act like it matters</b>&mdash;because it does."],
  ["Technological Democracy",
   "We build technology that expands human agency rather than replacing it, widening participation in decision-making instead of concentrating control in a powerful few.",
   "Tech should give <b>you</b> more agency&mdash;not hand control to a powerful few. We use the tools to widen participation, not narrow it."],
  ["Scientific Stewardship",
   "We make evidence foundational to governance, not merely advisory&mdash;while holding that knowledge is strengthened by open criticism and broad participation, not expert authority alone. Where evidence and democratic will diverge, both must be reckoned with.",
   "Science stops being the advisor in the corner and becomes <b>how decisions actually get made</b>&mdash;but when the evidence and the crowd clash, neither just steamrolls the other."],
  ["Cultural Evolution",
   "We cultivate the culture that makes the rest endure&mdash;long-term thinking, global awareness, and our cosmic context&mdash;while respecting beneficial diversity in human expression. Culture is the carrier; without it, principles do not hold.",
   "Culture is the carrier. We build the stories, habits, and <b>long-term thinking</b> that make everything above actually stick."],
];

$canon = [
  ["preamble" => "&ldquo;In recognition of our cosmic context and evolutionary responsibility, we declare these nine fundamental principles for conscious evolution toward Type&nbsp;I civilization capabilities.&rdquo;"],
  ["affirm"   => "We, committed to humanity's conscious evolution, affirm these principles:"],
];
foreach ($P as $i => $p) { $canon[] = ["principle" => [$i + 1, $p[0], $p[1], $p[2]]]; }

$lesson = [
  "title"     => "The OD9 Creed",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 1",
  "subtitle"  => "Nine principles for consciously steering human evolution toward a Type&nbsp;I civilization. This is who we are&mdash;and how we operate.",
  "sigil"     => "I",
  "cover"     => "codex-vol1.jpg",
  "archivist" => "&ldquo;They handed you commandments and called it truth. This is different. Nine principles&mdash;and every one earns its place by being <em>demonstrable</em>. Read them as a contract you're choosing to sign, not a faith you're told to swallow.&rdquo;",
  "canon"     => $canon,
  "study"     => [
    ["label" => "The Archivist's read"],
    ["h3" => "What the Creed is"],
    ["p" => "The Creed is OD9's <b>founding declaration</b>&mdash;the bedrock the entire movement is built on. Every tier you'll climb, every feature of this system, every decision OD9 makes traces back to these nine lines. It deliberately fuses two traditions most people keep apart: <b>Western</b> systems-thinking and enhancement ethics, and <b>Eastern</b> contemplative practice. Rigor <em>and</em> inner work&mdash;though when the two meet, evidence is what settles the question."],
    ["h3" => "Why it comes first"],
    ["p" => "Soon you'll face the hard stuff&mdash;the Kardashev scale, the Great Filter, the coordination failures that could end us. But you can't understand the <em>stakes</em> until you know the <em>stance</em> we take toward them. The Creed is that stance: clear-eyed about the danger, refusing both despair and naive utopia."],
    ["p" => "Read one way, the nine split cleanly into two halves:"],
    ["ul" => [
      "<b>The goal</b> &mdash; why we're here: Conscious Evolution (1), Collective Achievement (3), Cosmic Perspective (6), Cultural Evolution (9).",
      "<b>The method</b> &mdash; how we get there without breaking what works: Evidence-Based Progress (2), Theseus Methodology (4), Universal Accessibility (5), Technological Democracy (7), Scientific Stewardship (8).",
    ]],
    ["callout" => ["&#9670; You're already living it", "This isn't abstract. <b>Principle&nbsp;2</b> is why your value is <em>verified</em>, not handed out for showing up. <b>Principle&nbsp;3</b> is why the system rewards lifting others. <b>Principle&nbsp;5</b> is why the ladder starts wherever <em>you</em> start. The Creed isn't on a wall&mdash;it's the rules of the game you just entered."]],
    ["h3" => "Hold onto principle 4"],
    ["p" => "<b>Theseus Methodology</b> is the one to carry forward. It answers a question that stops most movements cold: <em>how do you change a whole civilization without collapsing it?</em> The next canon record&mdash;<b>The Thesean Method</b>&mdash;is entirely about that. The Creed names it; the next lesson shows you how it works."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;1 §II: <em>The OD9 Creed (Formal Declaration)</em>. The nine principles and preamble are reproduced verbatim; the &ldquo;what this means for you&rdquo; notes and the Archivist's read are the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
