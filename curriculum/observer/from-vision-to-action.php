<?php
/**
 * THE CODEX — observer canon lesson: From Vision to Action (Vol 1, Ch 7 §VIII + §X).
 * The closing chapter of Volume I — the call to action. Data array + the shared
 * renderer (../_codex.php). Canon text is VERBATIM manifesto; glosses + the
 * Archivist's read are the editable teaching layer. Do NOT edit verbatim canon.
 *
 * RE-AUTHORED 2026-09-14. This lesson was built on Chapter 8, which the
 * 2026-09-09 Volume 1 consolidation folded into Chapter 7 ("ch8's implementation
 * folded into the sections it implements"). All four canon passages, the affirm
 * and two study-layer quotes were text the manifesto no longer contained — the
 * lesson was quoting a book that had stopped existing. The knowing-doing material
 * survives in ch7 §VIII (Development Pathways), and the Volume I hand-off in
 * §X, so the lesson keeps its title, slug, order and credits and re-quotes from
 * there. Every canon string was lifted from the source mechanically and
 * round-trip verified, never transcribed.
 *
 * Anti-woo: §VIII is clean — Pfeffer & Sutton, Lewin, Rogers, Kegan & Lahey,
 * Ackoff, Drucker, all cited, no mysticism. The "Daily practice" passage is the
 * founder's own tightened prose and says plainly what the framework does not do.
 * Keep it grounded; no invented precision or fabrication.
 */
declare(strict_types=1);

$lesson = [
  "title"     => "From Vision to Action",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 7",
  "subtitle"  => "The hardest gap isn&rsquo;t understanding &mdash; it&rsquo;s doing. Volume&nbsp;I ends here, where theory becomes practice and you stop reading and start climbing.",
  "sigil"     => "VIII",
  "cover"     => "atlas-vol1.jpg",
  "archivist" => "&ldquo;Everything before this was the map &mdash; the creed, the method, the diagnosis, the solution. A map you only read is just paper; this chapter is where it becomes a journey. The knowing&ndash;doing gap is the real bottleneck: nearly everyone <em>knows</em>; almost no one <em>acts</em>. You don&rsquo;t cross it by understanding more. You cross it by moving &mdash; now, imperfectly, from exactly where you stand.&rdquo;",
  "canon" => [
    ["sec" => 8, "p" => "The gap between transformative vision and practical implementation represents what organizational theorist Jeffrey Pfeffer and management scholar Robert Sutton (2000) called &quot;the knowing-doing gap&quot;&mdash;how understanding frequently fails to translate into action. This gap creates what psychologist Kurt Lewin (1947) termed &quot;the unfreezing problem&quot;&mdash;the difficulty of initiating change in stable systems with powerful homeostatic mechanisms. The ASCEND Development Pathways component addresses these challenges through what implementation scientist Everett Rogers (2003) called &quot;the diffusion of innovations&quot;&mdash;systematic approaches to spreading beneficial practices across individuals, communities, and institutions.", "lead" => true],

    ["sec" => 8, "p" => "Structured transformation methodologies for individuals implement what psychologists Robert Kegan and Lisa Lahey (2009) called &quot;immunity to change&quot;&mdash;frameworks addressing the sophisticated resistance mechanisms that prevent development despite conscious intentions. Unlike conventional self-help approaches focused primarily on motivation or information, ASCEND implements what Kegan and Lahey (2016) termed &quot;deliberately developmental organization&quot;&mdash;environments specifically designed to overcome psychological barriers to transformation. This approach aligns with what adult development researcher William Torbert (2004) calls &quot;action inquiry&quot;&mdash;conscious practice that simultaneously produces action and inquiry as part of the same process."],

    ["sec" => 8, "p" => "<strong>Daily practice.</strong> Individual development is where every larger claim in this book either becomes real or stays theoretical, and it happens in the unglamorous units of a day. The framework's commitment is to protocols specific enough to follow without a teacher present: a defined practice with a stated duration, a way of noticing whether it is working, and an honest account of what it does not do. The measurement question matters more than it appears &mdash; self-report on one's own development is exactly the instrument most distorted by the development being claimed, which is why the evidence standard in Appendix A applies to individual practice as strictly as to anything else. Practice that cannot be checked is faith with better branding."],

    ["sec" => 10, "p" => "<strong>What Volume 2 does with this.</strong> Volume 1 has argued that conscious evolution is possible, described what would have to be built, and stated the constraints binding it. It has not established the case that the current arrangement is failing badly enough to justify the effort &mdash; that is Volume 2's work, and it proceeds domain by domain: the media and information crisis, mental health, economic dysfunction, environmental degradation, religious institutions and the rest of the diagnostic spine. The order is deliberate. A diagnosis read before any alternative exists produces despair; read after, it produces specification. Volume 2 is where the argument stops describing what we want and starts documenting what we are actually up against."],

    ["affirm" => "Individual development is where every larger claim in this book either becomes real or stays theoretical, and it happens in the unglamorous units of a day."],
  ],
  "study" => [
    ["label" => "The Archivist&rsquo;s read"],

    ["h3" => "You&rsquo;ve read the map. Now move."],
    ["p"  => "Volume&nbsp;I built the whole picture &mdash; the Creed&rsquo;s principles, the Thesean Method, the Evidence Standard, love as infrastructure, the awakening, the crisis, the guardrails, the architecture. This section names why that isn&rsquo;t enough on its own. The manifesto calls it <em>&ldquo;the knowing-doing gap&rdquo;</em>: understanding fails to become action, reliably, in almost everyone. The bottleneck isn&rsquo;t information. You already have more than you are using."],

    ["h3" => "Why knowing isn&rsquo;t enough"],
    ["p"  => "The reason is not laziness. Stable systems defend themselves &mdash; what the chapter calls <em>&ldquo;the unfreezing problem&rdquo;</em> &mdash; and so do people, through <em>&ldquo;immunity to change&rdquo;</em>: the resistance that runs on real but unexamined commitments pulling the other way. That is why motivation alone keeps failing you. You are not fighting a shortage of willpower; you are fighting a structure that is working exactly as designed, and structures are changed by other structures, not by resolutions."],

    ["callout" => ["&#9670; Your first move is already in front of you", "The manifesto isn&rsquo;t a book to finish &mdash; it&rsquo;s a system to <em>enter</em>, and you&rsquo;re already inside it. Pick the smallest thing you can actually do today, in the &ldquo;unglamorous units of a day,&rdquo; and do that. A practice you keep beats a plan you admire."]],

    ["h3" => "Where Volume I ends"],
    ["p"  => "The chapter is honest about the limit of what it has earned: Volume&nbsp;I argued that conscious evolution is possible, described what would have to be built, and stated the constraints binding it &mdash; and it has <em>not</em> yet made the case that the current arrangement is failing badly enough to justify the effort. That is Volume&nbsp;2&rsquo;s work. The distinction matters, because a framework that skipped it would be asking for belief. This one asks you to keep reading, and to start moving while you do."]
  ],
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;7 (<em>The Solution Framework</em>), &sect;VIII <em>Development Pathways: From Theory to Practice</em> and &sect;X <em>From ASCEND to Type I Civilization</em> &mdash; the closing chapter of Volume&nbsp;I. Featured passages are reproduced verbatim; the Archivist&rsquo;s read is the study layer.",
];

require __DIR__ . '/../_codex.php';
codex_render($lesson);
