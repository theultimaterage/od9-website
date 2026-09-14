<?php
/**
 * THE CODEX — observer canon lesson: Responsible Transformation (Vol 1, Ch 7 §II).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto; glosses + the Archivist's read are the editable teaching layer.
 * Do NOT edit verbatim canon.
 *
 * RE-AUTHORED 2026-09-14. This lesson was built on Chapter 6, which the
 * 2026-09-09 Volume 1 consolidation folded into Chapter 7 as §II — "ethics and
 * safety before the architecture it binds". All five canon passages, the affirm
 * and every study-layer quote but one ("the orthogonality thesis") were text the
 * manifesto no longer contained: the lesson quoted a chapter that had stopped
 * existing. Every canon string here was lifted from the source mechanically and
 * round-trip verified, never transcribed.
 *
 * The ARGUMENT moved with the text and is stronger for it. The old lesson framed
 * responsibility through AI-safety borrowings (orthogonality, differential
 * technological development); §II frames it through the problem that is actually
 * OD9's — a consciousness-transforming technology has no outside vantage point
 * from which to evaluate it, so an error propagates into the faculty that would
 * detect the error. Same discipline, on-thesis sourcing.
 *
 * Anti-woo: §II is clean — Churchland, Habermas, Kass, Ostrom, Toffler, all
 * cited and argued with rather than deferred to; it states plainly that the
 * framework is antitheist and that Kass's "wisdom of repugnance" is NOT accepted.
 * Keep it grounded; no invented precision or fabrication.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Responsible Transformation",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 7",
  "subtitle"  => "Godlike tools don&rsquo;t make a wise civilization &mdash; new power without new wisdom magnifies the crisis. This is the discipline of changing things without making them worse.",
  "sigil"     => "II",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;The diagnosis is in: a species with godlike tools and obsolete wiring. The reflex is to reach for bigger tools &mdash; and that reflex is the trap. New capability without new wisdom doesn&rsquo;t close the gap; it widens it, faster. This is the part nobody romanticizes: how to change a civilization without recreating the very failures you set out to fix.&rdquo;",
  "canon" => [
    ["sec" => 2, "p" => "The previous section described what we intend to build. This one comes before the description of how, deliberately, because a proposal to modify consciousness at civilizational scale that leads with its architecture and appends its safety case has already told you what it values. The commitments below are constraints on everything that follows, not caveats attached to it. If the system described in the rest of this chapter cannot satisfy them, the system is wrong and not the constraints.", "lead" => true],

    ["sec" => 2, "p" => "Conventional technologies alter the world outside the person using them. Their effects can be observed from a position the technology has not touched. Consciousness-transforming technologies have no such outside: they modify the apparatus we would use to evaluate them. Patricia Churchland's (2013) point about the self-referential character of awareness is the whole problem in one line &mdash; consciousness is the foundation on which every other human capability and value rests, so an error there propagates into the faculty that would detect the error."],

    ["sec" => 2, "p" => "Two further risks deserve naming because they are less obvious. The first is partial development: expanded capability without corresponding ethical development, which produces someone more effective and no better. The countermeasure is coupling capability enhancement to ethical development structurally, so the two cannot be separated by a user who would prefer only the first. The second is what Habermas (2003) calls the instrumentalization of the self &mdash; treating consciousness as a resource to be optimized rather than as the thing that has value. Against that: genuine informed consent about what a transformation implies, reversibility wherever the intervention permits it, agency preserved throughout rather than surrendered at the start, and enough continuity between the person before and after that the word &quot;recovery&quot; still means something."],

    ["sec" => 2, "p" => "The architecture described through the rest of this chapter is bound by the following, and each is checkable rather than aspirational. Transformation is paced to integration capacity. Capability enhancement is coupled to ethical development rather than sold separately. Access is designed for baseline universality, and a version that cannot achieve it is a failure of the design rather than an acceptable compromise. Governance is polycentric, with overlapping decision centres and graduated rather than binary enforcement. Interventions are reversible where the technology permits and honestly labelled where it does not. And the strongest objections above are answered in the design, not in the marketing."],

    ["affirm" => "If the system described in the rest of this chapter cannot satisfy them, the system is wrong and not the constraints."],
  ],
  "study" => [
    ["label" => "The Archivist&rsquo;s read"],

    ["h3" => "Why this comes before the architecture"],
    ["p"  => "Notice what the manifesto does with the running order. The safety case is placed <em>before</em> the description of the system, not appended after it, and the text says why: a proposal that leads with its architecture has already told you what it values. These are <em>&ldquo;constraints on everything that follows, not caveats attached to it&rdquo;</em> &mdash; and if the system can&rsquo;t satisfy them, it is the system that&rsquo;s wrong. That is a falsifiable commitment, which is the only kind worth making."],

    ["h3" => "The technology with no outside"],
    ["p"  => "Here is the part that makes this harder than ordinary engineering. A bridge can be judged from the riverbank. Consciousness-transforming technologies <em>&ldquo;modify the apparatus we would use to evaluate them&rdquo;</em> &mdash; an error introduced there propagates into the faculty that would catch it. This is why the framework refuses to treat safety as a later step. There is no later vantage point to check from."],

    ["callout" => ["&#9670; More effective, and no better", "The risk the manifesto names as partial development: capability that grows while ethics doesn&rsquo;t, producing someone <em>&ldquo;more effective and no better.&rdquo;</em> The countermeasure isn&rsquo;t a warning label &mdash; it&rsquo;s structural, coupling the two so a user cannot take only the first."]],

    ["h3" => "Checkable, not aspirational"],
    ["p"  => "The section closes with commitments it insists are <em>&ldquo;checkable rather than aspirational&rdquo;</em>: pacing to integration capacity, enhancement coupled to ethical development, access designed for baseline universality, polycentric governance, reversibility where the technology permits and honest labelling where it doesn&rsquo;t. Then the line to hold the whole framework to &mdash; the strongest objections are <em>&ldquo;answered in the design, not in the marketing.&rdquo;</em> Read the rest of Chapter&nbsp;7 against that standard, and hold this one to it too."]
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;7 (<em>The Solution Framework</em>), &sect;II <em>Ethics and Safety Before Architecture</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
