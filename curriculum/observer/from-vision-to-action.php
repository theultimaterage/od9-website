<?php
/**
 * THE CODEX — observer canon lesson: From Vision to Action (Vol 1, Ch 8).
 * The closing chapter of Volume I — the call to action. Data array + the shared
 * renderer (../_codex.php). Canon text is VERBATIM manifesto; glosses + the
 * Archivist's read are the editable teaching layer. Do NOT edit verbatim canon.
 *
 * Anti-woo: Ch8 §1 is clean — knowing-doing gap (Pfeffer & Sutton), Snowden,
 * Meadows "dancing with systems", James, Nietzsche, Heidegger — all cited, no
 * mysticism. (The Fuller "build a new model" quote also appears in Ch8 but is
 * already this lesson-set's affirm in Ch7 The Solution Framework, so it's not
 * reused here.) Keep it grounded; no invented precision or fabrication.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "From Vision to Action",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 8",
  "subtitle"  => "The hardest gap isn&rsquo;t understanding &mdash; it&rsquo;s doing. Volume&nbsp;I ends here, where theory becomes practice and you stop reading and start climbing.",
  "sigil"     => "VIII",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;Everything before this was the map &mdash; the creed, the method, the diagnosis, the solution. A map you only read is just paper; this chapter is where it becomes a journey. The knowing&ndash;doing gap is the real bottleneck: nearly everyone <em>knows</em>; almost no one <em>acts</em>. You don&rsquo;t cross it by understanding more. You cross it by moving &mdash; now, imperfectly, from exactly where you stand.&rdquo;",
  "canon" => [
    ["p" => "As we stand at the threshold between vision and manifestation, between theoretical frameworks and practical transformation, a crucial truth emerges: understanding without implementation remains mere intellectual exercise. The preceding chapters have established comprehensive foundations for conscious evolution&mdash;from the OD9 Creed&rsquo;s core principles to theoretical methodologies, from diagnostic analysis to solution frameworks. Yet the most sophisticated understanding proves meaningless without translation into lived reality.", "lead" => true],

    ["p" => "The gap between knowledge and action represents perhaps the most significant evolutionary bottleneck facing humanity. As Pfeffer and Sutton (2000) observe in their research on organizational change, &ldquo;The knowing-doing gap&mdash;the challenge of turning knowledge about how to enhance organizational performance into actions consistent with that knowledge&mdash;is one of the most important and vexing barriers to organizational success.&rdquo;"],

    ["p" => "Implementation need not&mdash;indeed, must not&mdash;await theoretical perfection. As complexity theorist Snowden notes, &ldquo;We know more than we can tell, and we can tell more than we can prove.&rdquo; In complex adaptive systems such as human consciousness and civilization, perfect foreknowledge proves impossible. Action itself becomes an essential knowledge-generating process&mdash;what systems thinker Meadows (2008) called &ldquo;dancing with systems&rdquo; rather than attempting to control them through complete theoretical models."],

    ["p" => "The imperative of implementation calls us beyond comfortable theorizing into the messy, challenging work of manifestation. As philosopher Nietzsche (1883-1885/1954) admonished, &ldquo;He who cannot obey himself will be commanded. That is the nature of living creatures.&rdquo;"],

    ["affirm" => "Either we consciously implement our highest understanding, or we remain unconsciously implemented by existing systems."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "You&rsquo;ve read the map. Now move."],
    ["p"  => "Volume&nbsp;I built the whole picture &mdash; the Creed&rsquo;s principles, the Thesean Method, the Evidence Standard, love as infrastructure, the awakening, the crisis, the guardrails, the solution. This chapter&rsquo;s job is the one nobody finds easy: turning all of it into action. And it names the obstacle bluntly &mdash; the <b>knowing&ndash;doing gap</b>. Almost everyone <em>knows</em> things should change; almost no one acts. The gap isn&rsquo;t a shortage of understanding; it&rsquo;s the step from understanding to doing &mdash; and that step is the bottleneck this whole system exists to help you cross."],

    ["h3" => "Start imperfect, on purpose"],
    ["p"  => "The permission here is precise: implementation <em>&ldquo;must not await theoretical perfection.&rdquo;</em> You don&rsquo;t wait until you understand everything &mdash; in a complex world you never will, and the waiting is itself the failure. Action <em>generates</em> the understanding (Meadows&rsquo; &ldquo;dancing with systems&rdquo;). The first move doesn&rsquo;t have to be big or right; it has to be <em>real</em>. That&rsquo;s Chapter&nbsp;6&rsquo;s &ldquo;heuristics of fear&rdquo; turned on yourself &mdash; act wisely, don&rsquo;t freeze."],

    ["callout" => ["&#9670; Your first move is already in front of you", "The manifesto isn&rsquo;t a book to finish &mdash; it&rsquo;s a system to <em>enter</em>, and you&rsquo;re already inside it. The smallest real action beats the most elegant intention: answer today&rsquo;s question, ship your first reflection, show up to the Live. That&rsquo;s &ldquo;from vision to action&rdquo; &mdash; not a metaphor, the next click."]],

    ["h3" => "Where Volume I ends"],
    ["p"  => "Nietzsche sets the real stakes: <em>&ldquo;He who cannot obey himself will be commanded.&rdquo;</em> You are being shaped by existing systems whether you choose it or not &mdash; the only question is whether you implement your own understanding instead of being implemented by theirs. That is what the climb is <em>for</em>. Volume&nbsp;I was the why and the what; everything from here is the doing &mdash; and you&rsquo;ve already taken the first step by being here."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;8 (<em>From Vision to Action</em>), &sect;1 <em>The Imperative of Implementation</em> &mdash; the closing chapter of Volume&nbsp;I. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
