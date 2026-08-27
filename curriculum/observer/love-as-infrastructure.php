<?php
/**
 * THE CODEX — Observer canon lesson: Love as Infrastructure (Vol 1, Ch 3 §III + §IX).
 * Data + the shared renderer (../_codex.php). Canon text is VERBATIM manifesto;
 * glosses + the Archivist's read are the teaching layer. Do NOT edit verbatim text.
 *
 * Anti-woo discipline: the manifesto Ch3 was purged of oxytocin/vasopressin,
 * mirror-neuron, and positivity-resonance claims. This lesson rides the
 * cooperation-science spine only (Wilson multilevel selection, Tomasello shared
 * intentionality, Singer's expanding circle, Tania Singer empathy vs compassion).
 * Do NOT reintroduce neurochemical-bonding or metaphysical framing.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Love as Infrastructure",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 3",
  "subtitle"  => "Not a feeling you wait for &mdash; the coordination layer civilization runs on, and a skill you can train.",
  "sigil"     => "III",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;They told you love was soft &mdash; the opposite of serious work. The manifesto&rsquo;s claim is harder, and it&rsquo;s testable: love is <em>infrastructure</em>. It&rsquo;s the load-bearing system that lets separate minds coordinate at all. Pull it out, and the most advanced technology ever built still can&rsquo;t carry us through the Filter.&rdquo;",
  "canon" => [
    ["preamble" => "Beyond sentimental notions or romantic idealization, love represents a fundamental evolutionary force&mdash;a powerful driver of both individual development and collective advancement."],

    ["p" => "The evolutionary significance of love appears through what evolutionary biologist David Sloan Wilson (2015) calls &ldquo;multilevel selection&rdquo;&mdash;mechanisms operating at both individual and group levels simultaneously. As Wilson explains, &ldquo;Selfishness beats altruism within groups. Altruistic groups beat selfish groups. Everything else is commentary.&rdquo;", "lead" => true],

    ["p" => "This process depends on what developmental psychologist Michael Tomasello (2014) terms &ldquo;shared intentionality&rdquo;&mdash;the ability to form collaborative representations of goals and intentions. As Tomasello writes, &ldquo;Human beings are adapted for acting and thinking cooperatively in cultural groups, and so their most impressive cognitive achievements are not products of individuals acting alone but of individuals interacting in cultural contexts.&rdquo;"],

    ["p" => "The expanding circle of moral concern throughout human history follows patterns identified by philosopher Peter Singer (2011) in his analysis of ethical progress. As Singer documents, &ldquo;The circle of altruism has broadened from the family and tribe to the nation and race, and we are beginning to recognize that our obligations extend to all human beings and even beyond to some nonhuman animals.&rdquo;"],

    ["p" => "Contemporary research by neuroscientist Tania Singer (2013) distinguishes between empathy (sharing others&rsquo; emotional states) and compassion (concern motivating helpful action), revealing how love-based training enhances resilience while empathy alone can lead to burnout. As Singer explains, &ldquo;Empathy has the capacity to lead to empathic distress and withdrawal, but compassion does not. On the contrary, compassion seems to be associated with resilience and the motivation to help others.&rdquo;"],

    ["preamble" => "Love represents neither sentimental luxury nor distraction from serious evolutionary work, but rather the very foundation that makes conscious evolution possible."],

    ["affirm" => "The path to Type&nbsp;1 civilization runs not around but through the full development of our capacity to love."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "Why &lsquo;infrastructure&rsquo;?"],
    ["p"  => "Love here isn&rsquo;t decoration on the system &mdash; it&rsquo;s the load-bearing layer underneath it. The manifesto&rsquo;s claim is concrete: love is &ldquo;a measurable function in human cooperation&mdash;one that shapes how individuals and groups coordinate, build trust, and sustain commitment over time.&rdquo; Strip it out and coordination collapses, no matter how advanced the technology stacked on top. That is what <em>infrastructure</em> means: the thing everything else runs on."],

    ["h3" => "Empathy is not the goal. Compassion is."],
    ["p"  => "This is the sharpest distinction in the chapter, and it&rsquo;s the one most people miss. <b>Empathy</b> is feeling what another feels &mdash; and on its own it <em>burns out</em> (Tania Singer&rsquo;s &ldquo;empathic distress and withdrawal&rdquo;). <b>Compassion</b> is caring enough to <em>act</em> &mdash; and it&rsquo;s associated with resilience instead of exhaustion. The difference isn&rsquo;t poetic; it shows up in measured behavior. OD9 trains the second one. That&rsquo;s why this is a discipline, not a mood."],

    ["callout" => ["&#9670; You can train this", "Love&mdash;as OD9 means it&mdash;is a <b>learnable skill</b>, not a fixed trait or a feeling you sit around waiting for. That&rsquo;s the whole reason it belongs in a progression system: a skill has a signature you can observe, and it improves with practice. It also fits the verification-first rule you met in the Creed&mdash;compassion is <em>demonstrated in action</em>, not declared."]],

    ["h3" => "How it connects"],
    ["p"  => "Remember the Creed&rsquo;s third principle, <b>Collective Achievement</b>: a coordinated group out-thinks any lone genius. This lesson is the <em>why underneath it</em>. Cooperation isn&rsquo;t a nice-to-have bolted onto smart individuals &mdash; D.S. Wilson&rsquo;s multilevel selection shows it&rsquo;s the evolutionary engine that built us, and Tomasello&rsquo;s &ldquo;shared intentionality&rdquo; shows it&rsquo;s how our largest achievements actually happen. Love is the substrate that makes coordination possible at all &mdash; which is exactly why the path to a Type&nbsp;1 civilization runs <em>through</em> it, not around it."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;3 (<em>A Labor of Love</em>), §III: <em>Love as Evolutionary Force</em> and §IX: <em>Conclusion &mdash; The Heart of Transformation</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
