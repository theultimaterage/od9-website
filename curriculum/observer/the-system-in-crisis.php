<?php
/**
 * THE CODEX — observer canon lesson: The System in Crisis (Vol 1, Ch 5).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (no fabrication, no paraphrase-as-quote); glosses + the Archivist's
 * read are the editable teaching layer. Do NOT edit the verbatim canon text.
 *
 * Anti-woo: Ch5 is clean hard-scientism — every claim is sourced (Hanson 1998
 * Great Filter, Meadows systems theory, Holling/Homer-Dixon rigidity traps,
 * Wiener/Shannon cybernetics, Grinspoon's anthropocene bottleneck). Keep it that
 * way: do NOT add invented precision, fabricated institutions, or doom-mongering.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "The System in Crisis",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 5",
  "subtitle"  => "The crises aren&rsquo;t separate emergencies &mdash; they&rsquo;re one coordination failure wearing many masks, and from the inside it looks exactly like a civilization meeting its Great Filter.",
  "sigil"     => "V",
  "cover"     => "codex-vol1.jpg",
  "archivist" => "&ldquo;They hand you the crises one at a time &mdash; climate here, politics there, your own splintered attention somewhere else &mdash; so each one looks survivable alone. The manifesto&rsquo;s diagnosis is colder and far more useful: these are not separate problems. They&rsquo;re one failure wearing many masks &mdash; a species handed godlike tools while still running on obsolete wiring. Name it correctly and a hundred hopeless fights collapse into one solvable problem.&rdquo;",
  "canon" => [
    ["p" => "Let us begin by considering what a civilization on the brink of either transcendence or catastrophe might look like. If we were approaching a Great Filter&mdash;that evolutionary hurdle proposed by economist Robin Hanson (1998) that may have ended countless civilizations before they could make their presence known in the cosmos&mdash;what signs would we expect to see?", "lead" => true],

    ["p" => "We would expect to see exactly what we see around us today: a convergence of existential threats coupled with systems seemingly incapable of addressing them. A civilization reaching for the stars while simultaneously undermining the very foundations of its existence. A species gaining godlike technological powers while remaining trapped in obsolete modes of thought and organization. As systems theorist Donella Meadows (2008) observed, this represents a fundamental mismatch between our problem-solving capabilities and the complexity of challenges we face."],

    ["p" => "Information failure undermines all other systems by corrupting the feedback mechanisms necessary for adaptive function. As cyberneticist Norbert Wiener (1948) observed in his foundational work, accurate information flow is essential for system regulation and adaptation. When information systems become corrupted, all connected systems lose navigational capacity&mdash;like a ship whose instruments provide false readings."],

    ["p" => "If other technological civilizations throughout the cosmos have faced similar convergence of environmental, technological, political, and social challenges during their development, many may have failed to navigate this turbulent passage. Astrobiologist David Grinspoon (2016) terms this the &ldquo;anthropocene bottleneck&rdquo;&mdash;the challenge of transitioning from unconscious planetary impact to conscious planetary stewardship."],

    ["p" => "Understanding these compound interactions prevents both unwarranted optimism and fatalistic pessimism. While the interactions create dangers exceeding the sum of individual crises, they also reveal potential leverage points where positive interventions might similarly cascade through multiple systems."],

    ["affirm" => "The same interconnections that enable devastating collapse can also facilitate rapid positive transformation when properly engaged."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "One failure, many masks"],
    ["p"  => "The instinct is to treat each headline as its own emergency. The chapter&rsquo;s claim is that they are <em>&ldquo;not isolated issues but interconnected symptoms of deeper systemic failures&rdquo;</em> &mdash; environmental strain feeds economic inequality, which corrupts governance, which fragments the information commons, which atomizes us socially, which then blocks any collective response. That loop is why piecemeal fixes keep failing: the interconnection isn&rsquo;t a side effect of the problem, it <em>is</em> the problem. You can&rsquo;t patch one node while the web around it pulls it back apart."],

    ["h3" => "The power&ndash;wisdom gap"],
    ["p"  => "This is the line to carry out of the lesson: <b>godlike tools, obsolete wiring.</b> The danger isn&rsquo;t that our technology is too weak &mdash; it&rsquo;s that our capacity to coordinate, decide, and see clearly hasn&rsquo;t kept pace with our capacity to act. That gap is what a Great Filter looks like from the inside: not a meteor, but a competence mismatch. And its keystone is information &mdash; when the signals a society steers by are corrupted, every other system flies blind, &ldquo;like a ship whose instruments provide false readings.&rdquo; Fix the instruments first, or nothing else you fix will hold."],

    ["callout" => ["&#9670; This is a map, not a verdict", "The diagnosis reads like doom until you notice its shape. The crises are <em>interconnected</em> &mdash; and interconnection runs both ways. The same linkages that let collapse cascade let <em>repair</em> cascade too. That&rsquo;s why the chapter ends on leverage points instead of despair: a coordinated push at the right place ripples through the whole system. OD9 is the attempt to organize that push."]],

    ["h3" => "How it connects"],
    ["p"  => "This lesson is the <em>why</em> underneath the others. The <b>Evidence Standard</b> is what lets you trust this diagnosis rather than dismiss it as alarmism &mdash; every claim here is sourced (Hanson, Meadows, Wiener, Grinspoon), not asserted. The <b>Great Awakening</b> is what you&rsquo;re waking up <em>to</em>. And the <b>Creed</b> and <b>Love as Infrastructure</b> are the answer in miniature: if the disease is coordination failure, the cure is built coordination capacity. The manifesto&rsquo;s prescription &mdash; transformation across many domains <em>simultaneously</em> &mdash; isn&rsquo;t a slogan. It&rsquo;s the climb you&rsquo;re on, one verified contribution at a time."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;5 (<em>The System in Crisis</em>) &mdash; &sect;1 <em>Systemic Failure as Evolutionary Bottleneck</em>, &sect;6 <em>Media and Information Crisis</em>, &sect;8 <em>Compounding Effects and System Interactions</em>, and &sect;9 <em>Analyzing the Great Filter Potential</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
