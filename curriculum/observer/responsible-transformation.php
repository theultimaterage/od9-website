<?php
/**
 * THE CODEX — observer canon lesson: Responsible Transformation (Vol 1, Ch 6).
 * Data array + the shared renderer (../_codex.php). Canon text is VERBATIM
 * manifesto; glosses + the Archivist's read are the editable teaching layer.
 * Do NOT edit the verbatim canon text.
 *
 * Anti-woo: Ch6 is clean — every claim is sourced (Meadows, Bostrom orthogonality
 * + differential development, Russell, Ord, Jonas, Maxwell). The chapter's §6
 * Eastern-wisdom is explicitly antitheist (wu-wei/dharma as operational philosophy,
 * no supernatural claims); this lesson stays on the risk-science/ethics spine.
 * Do NOT add invented precision, fabricated institutions, or metaphysical framing.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "Responsible Transformation",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 6",
  "subtitle"  => "Godlike tools don&rsquo;t make a wise civilization &mdash; new power without new wisdom magnifies the crisis. This is the discipline of changing things without making them worse.",
  "sigil"     => "VI",
  "cover"     => "codex-vol1.jpg",
  "archivist" => "&ldquo;The diagnosis is in: a species with godlike tools and obsolete wiring. The reflex is to reach for bigger tools &mdash; and that reflex is the trap. New capability without new wisdom doesn&rsquo;t close the gap; it widens it, faster. This chapter is the part nobody romanticizes: how to change a civilization without recreating the very failures you set out to fix.&rdquo;",
  "canon" => [
    ["p" => "Why must responsible frameworks precede implementation? The answer lies in what systems theorist Donella Meadows (2008) called &ldquo;the trap of technology as savior&rdquo;&mdash;the mistaken belief that technical solutions alone can resolve complex systemic problems. As our previous analysis of interconnected crises demonstrates, technological advancement without corresponding wisdom magnifies rather than resolves systemic dysfunction.", "lead" => true],

    ["p" => "The integration of ethics and effectiveness in transformation design recognizes what philosopher Nick Bostrom (2012) calls &ldquo;the orthogonality thesis&rdquo;&mdash;the principle that intelligence and moral values represent independent variables. Advanced capabilities do not automatically generate beneficial outcomes, but require explicit alignment with human flourishing. This alignment cannot be retrofitted after capabilities develop but must be integrated from inception. As computer scientist Stuart Russell (2019) observes regarding artificial intelligence, &ldquo;You can&rsquo;t fetch the coffee if you&rsquo;re dead&rdquo;&mdash;highlighting how certain basic values (like human survival) necessarily precede and enable all other objectives."],

    ["p" => "When facing potentially irreversible catastrophes, the precautionary principle advises restraint. Yet philosopher Toby Ord (2020) counters with what he calls &ldquo;the paralysis problem&rdquo;&mdash;the danger that excessive caution might itself constitute a moral failure by preventing the development of solutions to existing suffering. Navigating between these poles requires what philosopher Hans Jonas (1984) called &ldquo;the heuristics of fear&rdquo;&mdash;using realistic threat assessment to inform wise action rather than paralysis."],

    ["p" => "Differential technology development, proposed by philosopher Nick Bostrom (2014), offers strategic guidance for navigating these vulnerabilities. This approach prioritizes technologies that enhance safety and resilience over those that increase risk, creating what Bostrom calls &ldquo;the technological completion conjecture&rdquo;&mdash;the principle that civilizations reaching certain technological thresholds will either self-destruct or develop effective safety mechanisms."],

    ["p" => "Wisdom as ethical superintelligence represents what philosopher Nicholas Maxwell (2007) calls &ldquo;wisdom-inquiry&rdquo;&mdash;approaches that integrate factual knowledge with evaluative wisdom to guide technological development."],

    ["affirm" => "Responsible frameworks serve as navigational tools, ensuring that our increasing powers enhance rather than diminish human flourishing."],
  ],
  "study" => [
    ["label" => "The Archivist's read"],

    ["h3" => "The trap of technology as savior"],
    ["p"  => "Coming straight off the diagnosis, the obvious move is to reach for bigger tools. That reflex is the trap Meadows named. Chapter&nbsp;5 already showed its shape: <em>&ldquo;technological advancement without corresponding wisdom magnifies rather than resolves systemic dysfunction.&rdquo;</em> So responsible frameworks aren&rsquo;t brakes on progress &mdash; they&rsquo;re the <b>steering</b>. Without them, more capability just drives the same failures faster."],

    ["h3" => "Capability is not wisdom"],
    ["p"  => "This is the cornerstone, and it&rsquo;s why the chapter has to exist: the <b>orthogonality thesis</b> &mdash; intelligence and good values are <em>independent variables</em>. A more powerful system is not automatically a better one; alignment has to be built in from the start, never bolted on after. Stuart Russell&rsquo;s line is the whole point in eight words &mdash; <em>&ldquo;You can&rsquo;t fetch the coffee if you&rsquo;re dead&rdquo;</em> &mdash; some values come <em>before</em> every goal. It&rsquo;s also why OD9 gates the climb on demonstrated wisdom (verified contribution, the value dimensions), not raw capability or credits alone."],

    ["callout" => ["&#9670; Neither reckless nor frozen", "Two opposite failures, equally fatal: charge ahead &mdash; the hubris that magnifies the crisis &mdash; or freeze up, Ord&rsquo;s &ldquo;paralysis problem,&rdquo; where excessive caution lets present suffering continue. The discipline between them is Jonas&rsquo;s &ldquo;heuristics of fear&rdquo;: use a clear-eyed read of real risk to act <em>wisely</em>, not to stall. In practice that means <b>safety before capability</b> &mdash; sequenced, defended, reversible."]],

    ["h3" => "How it connects"],
    ["p"  => "Chapter&nbsp;5 named the disease; this is the Hippocratic oath for the cure &mdash; <em>first, don&rsquo;t make it worse.</em> It&rsquo;s the bridge to the Solution Framework that follows: the principles and guardrails live here, the concrete architecture comes next. And it&rsquo;s the logic running under the whole progression you&rsquo;re climbing &mdash; the <b>Evidence Standard</b> and the dimension gates are this chapter operationalized: a system that advances people on demonstrated wisdom, not merely on how much they can do."],
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;6 (<em>Responsible Transformation Frameworks</em>) &mdash; &sect;1 <em>Introduction: The Ethics of Conscious Evolution</em>, &sect;3 <em>Safety Frameworks for Technological Development</em>, and &sect;5 <em>Ethical Frameworks for Transformation</em>. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
