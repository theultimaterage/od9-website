<?php
/**
 * THE CODEX — observer canon lesson: The System in Crisis (Vol 1, Ch 5).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto (no fabrication, no paraphrase-as-quote); glosses + the Archivist's
 * read are the editable teaching layer. Do NOT edit the verbatim canon text.
 *
 * RE-QUOTED 2026-09-14. The 2026-09-09 consolidation cut ch5 from nine sections
 * to three. Three of the five canon passages survived that cut; two (both §2),
 * the affirm and one study-layer quote did not — they were text the manifesto no
 * longer contained. The whole canon array was regenerated from source rather than
 * patched, so the three survivors are re-verified verbatim rather than assumed,
 * and every string was lifted mechanically, never transcribed. Passages are
 * selected from the source by a distinctive PHRASE, never by paragraph index:
 * index selection silently picked the wrong paragraph once during this repair.
 * The source line's old §6/§8/§9 citations went with the cut sections.
 *
 * Anti-woo: Ch5 is clean hard-scientism — every claim is sourced (Hanson 1998
 * Great Filter, Meadows systems theory, Grinspoon's anthropocene bottleneck), and
 * §2 now argues with its own evidence rather than deferring to it: it flags the
 * Gilens & Page oligarchy result as "unproven by this method rather than
 * disproven", and corrects a common inversion of Ashby's law. Keep it that way:
 * do NOT add invented precision, fabricated institutions, or doom-mongering.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "The System in Crisis",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 5",
  "subtitle"  => "The crises aren&rsquo;t separate emergencies &mdash; they&rsquo;re one coordination failure wearing many masks, and from the inside it looks exactly like a civilization meeting its Great Filter.",
  "sigil"     => "V",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;They hand you the crises one at a time &mdash; climate here, politics there, your own splintered attention somewhere else &mdash; so each one looks survivable alone. The manifesto&rsquo;s diagnosis is colder and far more useful: these are not separate problems. They&rsquo;re one failure wearing many masks &mdash; a species handed godlike tools while still running on obsolete wiring. Name it correctly and a hundred hopeless fights collapse into one solvable problem.&rdquo;",
  "canon" => [
    ["sec" => 1, "p" => "Let us begin by considering what a civilization on the brink of either transcendence or catastrophe might look like. If we were approaching a Great Filter&mdash;that evolutionary hurdle proposed by economist Robin Hanson (1998) that may have ended countless civilizations before they could make their presence known in the cosmos&mdash;what signs would we expect to see?", "lead" => true],

    ["sec" => 1, "p" => "We would expect to see exactly what we see around us today: a convergence of existential threats coupled with systems seemingly incapable of addressing them. A civilization reaching for the stars while simultaneously undermining the very foundations of its existence. A species gaining godlike technological powers while remaining trapped in obsolete modes of thought and organization. As systems theorist Donella Meadows (2008) observed, this represents a fundamental mismatch between our problem-solving capabilities and the complexity of challenges we face."],

    ["sec" => 2, "p" => "Five domains are failing at once, and the reason this chapter treats them together rather than in sequence is that their failures are coupled. Each one degrades the capacity that the others would need in order to be repaired. That coupling &mdash; not the severity of any single domain &mdash; is what makes the present moment a bottleneck rather than a bad decade."],

    ["sec" => 2, "p" => "Taken separately, each domain has a literature, a set of proposed reforms, and a constituency arguing that its own crisis is the master one. Taken together they form a structure with a specific property: <strong>each domain's failure degrades the capacity required to repair the others.</strong> The biophysical crisis demands long horizons that the political crisis has destroyed. The political crisis requires the shared perception the information crisis prevents. The information crisis requires the trust the social crisis has drained. The social crisis is driven substantially by the economic one. And the economic crisis cannot be corrected without political capacity."],

    ["sec" => 3, "p" => "If other technological civilizations throughout the cosmos have faced similar convergence of environmental, technological, political, and social challenges during their development, many may have failed to navigate this turbulent passage. Astrobiologist David Grinspoon (2016) terms this the &quot;anthropocene bottleneck&quot;&mdash;the challenge of transitioning from unconscious planetary impact to conscious planetary stewardship. As he observes, &quot;The transition to planetary intelligence is a major evolutionary threshold which, once crossed, changes the very nature of how a biosphere works and what it means to be an intelligent entity.&quot;"],

    ["affirm" => "A society can be persuaded that a solution would work and still be unable to adopt it, because adoption requires believing that others will hold up their end."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "One failure, many masks"],
    ["p"  => "The instinct is to treat each headline as its own emergency. The chapter&rsquo;s claim is that they are <em>&ldquo;not isolated issues but interconnected symptoms of deeper systemic failures&rdquo;</em> &mdash; environmental strain feeds economic inequality, which corrupts governance, which fragments the information commons, which atomizes us socially, which then blocks any collective response. That loop is why piecemeal fixes keep failing: the interconnection isn&rsquo;t a side effect of the problem, it <em>is</em> the problem. You can&rsquo;t patch one node while the web around it pulls it back apart."],

    ["h3" => "The power&ndash;wisdom gap"],
    ["p"  => "This is the line to carry out of the lesson: <b>godlike tools, obsolete wiring.</b> The danger isn&rsquo;t that our technology is too weak &mdash; it&rsquo;s that our capacity to coordinate, decide, and see clearly hasn&rsquo;t kept pace with our capacity to act. That gap is what a Great Filter looks like from the inside: not a meteor, but a competence mismatch. And information sits close to the keystone, because the chain the chapter traces runs through it: <em>&ldquo;The political crisis requires the shared perception the information crisis prevents.&rdquo;</em> A society that cannot see straight cannot decide straight, and every repair downstream of that inherits the error."],

    ["callout" => ["&#9670; This is a map, not a verdict", "The diagnosis reads like doom until you notice its shape. The chapter names the coupling as the thing that makes this <em>&ldquo;a bottleneck rather than a bad decade&rdquo;</em> &mdash; but a bottleneck is a passage, and it ends by asking whether we are alone because few civilizations navigate it, or whether we join them. Its last word is not a forecast: <em>&ldquo;The choice&mdash;and the challenge&mdash;is ours.&rdquo;</em> OD9 is the attempt to organize that choice."]],

    ["h3" => "How it connects"],
    ["p"  => "This lesson is the <em>why</em> underneath the others. The <b>Evidence Standard</b> is what lets you trust this diagnosis rather than dismiss it as alarmism &mdash; every claim here is sourced (Hanson, Meadows, Wiener, Grinspoon), not asserted. The <b>Great Awakening</b> is what you&rsquo;re waking up <em>to</em>. And the <b>Creed</b> and <b>Love as Infrastructure</b> are the answer in miniature: if the disease is coordination failure, the cure is built coordination capacity. The manifesto&rsquo;s prescription &mdash; transformation across many domains <em>simultaneously</em> &mdash; isn&rsquo;t a slogan. It&rsquo;s the climb you&rsquo;re on, one verified contribution at a time."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;5 (<em>The System in Crisis</em>) &mdash; &sect;1 <em>Introduction: Systemic Failure as Evolutionary Bottleneck</em>, &sect;2 <em>The Five Failure Domains</em>, and &sect;3 <em>Analyzing the Great Filter Potential</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
