<?php
$page_title = 'ASCEND Protocol Progression Tiers - OD9 | Observer to Benefactor';
$page_description = 'The ASCEND Protocol: a five-tier credit-based progression system from Observer to Benefactor. Earn advancement through critical analysis, peer evaluation, think tanks, and contribution projects.';
$page_slug = 'tiers.php';
$page_og_title = 'ASCEND Protocol Tiers | Observer to Benefactor - OD9';
$page_og_description = 'Five tiers of growth through genuine understanding. Earn your way up through analysis, peer evaluation, think tanks, and building real solutions.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "@id": "https://offda9.com/tiers.php",
  "url": "https://offda9.com/tiers.php",
  "name": "ASCEND Protocol Progression Tiers - OD9 | Observer to Benefactor",
  "description": "The ASCEND Protocol: a five-tier credit-based progression system from Observer to Benefactor. Earn advancement through critical analysis, peer evaluation, think tanks, and contribution projects.",
  "isPartOf": {"@id": "https://offda9.com/#website"},
  "mainEntityOfPage": "https://offda9.com/tiers.php"
}
</script>
<style>
body{background:var(--carbon);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--chrome);font-family:'Exo 2',sans-serif;padding-top:var(--nav-height);min-height:100vh}
</style>
<style>
.container{max-width:1000px;margin:0 auto;padding:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:2.5rem;color:#fff;text-align:center;margin-bottom:0.5rem;text-shadow:var(--glow)}
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.2rem;margin-bottom:0.5rem;font-family:'Rajdhani',sans-serif;font-weight:600}
.intro{text-align:center;max-width:750px;margin:0 auto 2.5rem;line-height:1.8;color:#aaa}
.tier-flow{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:0.4rem;margin:2rem 0 3rem;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.9rem}
.tier-flow span{padding:0.5rem 1rem;border-radius:4px}
.tier-flow .arrow{color:#444;font-size:1.1rem}
.tf-observer{background:rgba(128,128,128,0.2);color:#808080}
.tf-theorist{background:rgba(65,105,225,0.2);color:#4169E1}
.tf-architect{background:rgba(153,50,204,0.2);color:#9932CC}
.tf-pioneer{background:rgba(255,215,0,0.2);color:#FFD700}
.tf-benefactor{background:rgba(0,255,136,0.2);color:#00FF88}
.tier-card{background:var(--carbon-dark);border-radius:12px;margin-bottom:2rem;border-left:4px solid;overflow:hidden}
.tier-card.observer{border-color:#808080}.tier-card.theorist{border-color:#4169E1}.tier-card.architect{border-color:#9932CC}.tier-card.pioneer{border-color:#FFD700}.tier-card.benefactor{border-color:#00FF88}
.tier-header{padding:1.5rem 2rem;display:flex;align-items:center;gap:1.25rem;cursor:pointer;transition:background 0.3s}
.tier-header:hover{background:rgba(255,255,255,0.02)}
.tier-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.observer .tier-icon{background:rgba(128,128,128,0.15);color:#808080}
.theorist .tier-icon{background:rgba(65,105,225,0.15);color:#4169E1}
.architect .tier-icon{background:rgba(153,50,204,0.15);color:#9932CC}
.pioneer .tier-icon{background:rgba(255,215,0,0.15);color:#FFD700}
.benefactor .tier-icon{background:rgba(0,255,136,0.15);color:#00FF88}
.tier-meta{flex:1}
.tier-meta h2{font-family:'Orbitron',sans-serif;font-size:1.2rem;color:#fff;margin-bottom:0.2rem}
.tier-meta .tagline{font-family:'Rajdhani',sans-serif;font-size:0.95rem;font-weight:600;margin-bottom:0.3rem}
.observer .tagline{color:#808080}.theorist .tagline{color:#4169E1}.architect .tagline{color:#9932CC}.pioneer .tagline{color:#FFD700}.benefactor .tagline{color:#00FF88}
.tier-meta .credits{font-size:0.8rem;color:#888}
.tier-toggle{color:#888;font-size:1.2rem;transition:transform 0.3s}
.tier-card.open .tier-toggle{transform:rotate(180deg)}
.tier-body{max-height:0;overflow:hidden;transition:max-height 0.5s ease}
.tier-card.open .tier-body{max-height:2000px}
.tier-content{padding:0 2rem 2rem;border-top:1px solid #222}
.tier-content p{line-height:1.8;color:#aaa;margin-bottom:1rem;font-size:0.95rem}
.tier-content p strong{color:#ddd}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin:1.25rem 0}
.detail-box{background:rgba(0,0,0,0.3);border:1px solid #222;border-radius:8px;padding:1.25rem}
.detail-box h4{font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;margin-bottom:0.6rem;display:flex;align-items:center;gap:0.5rem}
.observer .detail-box h4{color:#808080}.theorist .detail-box h4{color:#4169E1}.architect .detail-box h4{color:#9932CC}.pioneer .detail-box h4{color:#FFD700}.benefactor .detail-box h4{color:#00FF88}
.detail-box ul{list-style:none;padding:0}
.detail-box li{font-size:0.9rem;color:#999;line-height:1.7;padding-left:1.2rem;position:relative}
.detail-box li::before{content:'';position:absolute;left:0;top:0.55rem;width:6px;height:6px;border-radius:50%;background:#333}
.psych-note{background:rgba(0,191,255,0.05);border:1px solid rgba(0,191,255,0.15);border-radius:8px;padding:1rem 1.25rem;margin:1rem 0;font-size:0.9rem;color:#888;line-height:1.7}
.psych-note strong{color:var(--primary-blue)}
.credit-section{background:var(--carbon-dark);border-radius:12px;padding:2rem;margin:2.5rem 0;border:1px solid var(--primary-blue)}
.credit-section h2{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:1.5rem;text-align:center;font-size:1.3rem}
.credit-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem}
.credit-item{background:rgba(0,0,0,0.3);border:1px solid #222;border-radius:8px;padding:1.25rem;display:flex;gap:1rem;align-items:flex-start}
.credit-item i{color:var(--primary-blue);font-size:1.5rem;margin-top:0.2rem;flex-shrink:0}
.credit-item h4{font-family:'Rajdhani',sans-serif;color:#fff;font-size:1rem;margin-bottom:0.3rem}
.credit-item p{font-size:0.85rem;color:#888;line-height:1.6}
.credit-item .pts{font-family:'Orbitron',sans-serif;font-size:0.75rem;color:var(--primary-blue);margin-top:0.4rem}
.rubric{display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;margin:1.5rem 0}
.rubric-item{text-align:center;padding:1rem 0.75rem;border-radius:8px;border:1px solid #222;background:rgba(0,0,0,0.2)}
.rubric-item .label{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.85rem;margin-bottom:0.3rem}
.rubric-item .pts{font-family:'Orbitron',sans-serif;font-size:1.1rem;margin-bottom:0.3rem}
.rubric-item p{font-size:0.75rem;color:#888;line-height:1.4}
.r-inadequate{border-color:#444}.r-inadequate .label{color:#888}.r-inadequate .pts{color:#888}
.r-adequate{border-color:#4169E1}.r-adequate .label{color:#4169E1}.r-adequate .pts{color:#4169E1}
.r-strong{border-color:#9932CC}.r-strong .label{color:#9932CC}.r-strong .pts{color:#9932CC}
.r-exceptional{border-color:#FFD700}.r-exceptional .label{color:#FFD700}.r-exceptional .pts{color:#FFD700}
.cta{text-align:center;margin:2.5rem 0;padding:2rem;background:linear-gradient(135deg,rgba(0,191,255,0.1),transparent);border-radius:12px;border:1px solid var(--primary-blue)}
.cta h3{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:0.5rem}
.cta p{color:#888;margin-bottom:1.25rem;font-size:0.95rem}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem 2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;transition:all 0.3s}
.btn:hover{transform:translateY(-3px);box-shadow:var(--glow)}
@media(max-width:768px){.detail-grid,.credit-grid{grid-template-columns:1fr}.rubric{grid-template-columns:repeat(2,1fr)}.tier-flow{flex-direction:column}.tier-flow .arrow{transform:rotate(90deg)}}
</style>
</head>
<body>
<?php $current_page = 'tiers'; include('includes/nav.php'); ?>

<div class="container">
<h1>PROGRESSION TIERS</h1>
<p class="subtitle">The Path from Understanding to Action</p>
<p class="intro">OD9 uses a credit-based progression system designed to build genuine understanding, not just surface engagement. Each tier represents a deeper level of capability in solving coordination problems at civilizational scale.</p>

<div class="tier-flow">
<span class="tf-observer">Observer</span><span class="arrow">&rarr;</span>
<span class="tf-theorist">Theorist</span><span class="arrow">&rarr;</span>
<span class="tf-architect">Architect</span><span class="arrow">&rarr;</span>
<span class="tf-pioneer">Pioneer</span><span class="arrow">&rarr;</span>
<span class="tf-benefactor">Benefactor</span>
</div>

<!-- OBSERVER -->
<div class="tier-card observer open">
<div class="tier-header" onclick="toggleTier(this)">
<div class="tier-icon"><i class="fas fa-eye"></i></div>
<div class="tier-meta"><h2>Observer</h2><div class="tagline">Problem Recognition & Stakes</div><div class="credits">Entry Level &middot; 150 credits to advance &middot; 2-4 weeks</div></div>
<i class="fas fa-chevron-down tier-toggle"></i>
</div>
<div class="tier-body"><div class="tier-content">
<p><strong>"Understanding why we're not Type I yet and what happens if we don't get there."</strong></p>
<p>Observers learn the foundational concepts: the Kardashev Scale, the Great Filter, existential risk, and why coordination failure is the core problem preventing human advancement. This isn't passive consumption. You earn credits by engaging critically with the material and demonstrating real comprehension.</p>
<div class="detail-grid">
<div class="detail-box"><h4><i class="fas fa-book"></i> What You'll Learn</h4><ul>
<li>Kardashev Scale and Type I civilization requirements</li>
<li>The Fermi Paradox and the Great Filter</li>
<li>Why individual intelligence doesn't produce collective capability</li>
<li>Energy as the fundamental metric of civilization</li>
<li>Multi-generational thinking and the Long Now</li>
</ul></div>
<div class="detail-box"><h4><i class="fas fa-tasks"></i> Requirements to Advance</h4><ul>
<li>150 total credits earned</li>
<li>Complete all foundation content (30 credits min)</li>
<li>At least 3 substantial discussion posts</li>
<li>Participate in 1 Think Tank session (50 credits)</li>
<li>Engage with psychological endurance content</li>
</ul></div>
</div>
<div class="psych-note"><strong><i class="fas fa-brain"></i> Endurance Mechanism: Long-Term Thinking</strong> - Required content on the Long Now Foundation and cathedral thinking. If you're building infrastructure for a civilization 200 years from now that you'll never see, how does that change what "success" means?</div>
</div></div>
</div>

<!-- THEORIST -->
<div class="tier-card theorist">
<div class="tier-header" onclick="toggleTier(this)">
<div class="tier-icon"><i class="fas fa-brain"></i></div>
<div class="tier-meta"><h2>Theorist</h2><div class="tagline">Understanding Root Causes</div><div class="credits">150 credits &middot; 300 to advance &middot; 4-8 weeks</div></div>
<i class="fas fa-chevron-down tier-toggle"></i>
</div>
<div class="tier-body"><div class="tier-content">
<p><strong>"Why coordination fails - the systems preventing Type I advancement."</strong></p>
<p>Theorists move beyond recognizing problems to understanding the root systems that maintain them. You'll study four interconnected failure domains and begin teaching what you've learned to Observers, creating a cognitive shift from learning to explaining.</p>
<div class="detail-grid">
<div class="detail-box"><h4><i class="fas fa-microscope"></i> Four Failure Domains</h4><ul>
<li><strong>Theological-Apocalyptic Programming</strong> - how end-times conditioning removes survival instinct</li>
<li><strong>Economic Exploitation</strong> - profit motives that incentivize civilizational harm</li>
<li><strong>Information Control</strong> - institutional capture and surveillance preventing coordination</li>
<li><strong>Cognitive Impairment</strong> - environmental toxins and systems degrading human capability</li>
</ul></div>
<div class="detail-box"><h4><i class="fas fa-tasks"></i> Requirements to Advance</h4><ul>
<li>300 total credits (150 beyond Observer)</li>
<li>Complete all 4 Theorist summaries</li>
<li>At least 5 substantial discussion posts</li>
<li>Evaluate at least 3 Observer discussions</li>
<li>Participate in 2 Think Tank sessions</li>
</ul></div>
</div>
<div class="psych-note"><strong><i class="fas fa-shield-alt"></i> Endurance Mechanism: Strategic Pessimism</strong> - Based on Philip Tetlock's forecasting research. Most civilizational intervention attempts fail (base rate). Entering with "probably will fail but worthwhile anyway" inoculates against disappointment. Setbacks confirm base rates rather than destroying morale.</div>
</div></div>
</div>

<!-- ARCHITECT -->
<div class="tier-card architect">
<div class="tier-header" onclick="toggleTier(this)">
<div class="tier-icon"><i class="fas fa-drafting-compass"></i></div>
<div class="tier-meta"><h2>Architect</h2><div class="tagline">Building Solutions</div><div class="credits">300 credits &middot; 500 to advance &middot; 2-4 months</div></div>
<i class="fas fa-chevron-down tier-toggle"></i>
</div>
<div class="tier-body"><div class="tier-content">
<p><strong>"Translating understanding into functional systems."</strong></p>
<p>Architects stop analyzing and start building. You'll join a Research Cell (3-4 people working on related projects), complete milestone-based contribution projects, and create infrastructure that outlives any individual contributor. All projects require continuation documentation so someone else could take over.</p>
<div class="detail-grid">
<div class="detail-box"><h4><i class="fas fa-project-diagram"></i> Project Categories</h4><ul>
<li><strong>Content Creation</strong> - educational materials, videos, visualizations</li>
<li><strong>Infrastructure</strong> - bot features, analytics, automation</li>
<li><strong>Metaworld</strong> - simulation environments for testing coordination</li>
<li><strong>Research</strong> - case studies, data analysis, literature reviews</li>
</ul></div>
<div class="detail-box"><h4><i class="fas fa-tasks"></i> Requirements to Advance</h4><ul>
<li>500 total credits (200 beyond Theorist)</li>
<li>Complete at least 1 major contribution project</li>
<li>Active participation in a Research Cell</li>
<li>Complete all 4 Architect summaries</li>
<li>Evaluate at least 5 Theorist discussions</li>
</ul></div>
</div>
<div class="psych-note"><strong><i class="fas fa-users"></i> Endurance Mechanism: Research Cells</strong> - Small accountability groups with weekly check-ins. Mutual progress reporting (collaborative, not competitive). If someone goes quiet, cellmates reach out. Creates social obligation without oppression and prevents silent disappearance during low motivation.</div>
</div></div>
</div>

<!-- PIONEER -->
<div class="tier-card pioneer">
<div class="tier-header" onclick="toggleTier(this)">
<div class="tier-icon"><i class="fas fa-star"></i></div>
<div class="tier-meta"><h2>Pioneer</h2><div class="tagline">Leadership & Meta-Coordination</div><div class="credits">500 credits &middot; 750 to advance &middot; 4-6 months</div></div>
<i class="fas fa-chevron-down tier-toggle"></i>
</div>
<div class="tier-body"><div class="tier-content">
<p><strong>"Coordinating the coordinators - building the system that builds solutions."</strong></p>
<p>Pioneer work isn't about personally solving civilizational problems. It's about enabling others to solve them. Success is measured by Architect project completion rates, community health, and sustainable infrastructure. Pioneers rotate through five responsibility modes to prevent single-track burnout.</p>
<div class="detail-grid">
<div class="detail-box"><h4><i class="fas fa-sync-alt"></i> Role Rotation</h4><ul>
<li><strong>Mentorship</strong> - guiding Architects through projects</li>
<li><strong>Evaluation</strong> - maintaining quality standards</li>
<li><strong>Strategic Planning</strong> - partnerships, grants, growth</li>
<li><strong>Community Health</strong> - burnout monitoring, culture</li>
<li><strong>Operations</strong> - infrastructure and process optimization</li>
</ul></div>
<div class="detail-box"><h4><i class="fas fa-tasks"></i> Requirements to Advance</h4><ul>
<li>750+ total credits (250 beyond Architect)</li>
<li>At least 2 major contribution projects completed</li>
<li>Demonstrated mentorship capability</li>
<li>Community recognition (peer nomination)</li>
<li>Completion of all Pioneer summaries</li>
</ul></div>
</div>
<div class="psych-note"><strong><i class="fas fa-door-open"></i> Endurance Mechanism: Normalized Exit/Re-Entry</strong> - Life changes. Pioneers can declare "Inactive" status at any time with zero penalty. When ready to return, simply resume. No re-proving required. Cycling in and out is expected and healthy, not a sign of failure.</div>
</div></div>
</div>

<!-- BENEFACTOR -->
<div class="tier-card benefactor">
<div class="tier-header" onclick="toggleTier(this)">
<div class="tier-icon"><i class="fas fa-gem"></i></div>
<div class="tier-meta"><h2>Benefactor</h2><div class="tagline">Sustaining & Scaling the Mission</div><div class="credits">750+ credits &middot; Sustained commitment &middot; Ongoing</div></div>
<i class="fas fa-chevron-down tier-toggle"></i>
</div>
<div class="tier-body"><div class="tier-content">
<p><strong>"Investing in the long-term infrastructure that makes Type I advancement possible."</strong></p>
<p>Benefactors ensure the entire system has the resources, stability, and institutional resilience to persist across years and decades. Where Pioneers coordinate, Benefactors sustain. This includes financial stewardship, external partnerships, legal and organizational development, and long-term succession planning.</p>
<div class="detail-grid">
<div class="detail-box"><h4><i class="fas fa-landmark"></i> Key Responsibilities</h4><ul>
<li><strong>Resource Stewardship</strong> - funding strategy, grants, operations</li>
<li><strong>Strategic Partnerships</strong> - academic, nonprofit, tech relationships</li>
<li><strong>Institutional Resilience</strong> - succession planning, risk management</li>
<li><strong>Community Investment</strong> - sponsoring projects, retention programs</li>
</ul></div>
<div class="detail-box"><h4><i class="fas fa-tasks"></i> Requirements</h4><ul>
<li>750+ total credits</li>
<li>6+ months active at Pioneer level</li>
<li>Financial or resource contribution to operations</li>
<li>Community nomination and Pioneer council approval</li>
<li>Demonstrated long-term strategic thinking</li>
</ul></div>
</div>
<div class="psych-note"><strong><i class="fas fa-church"></i> Endurance Mechanism: Cathedral Thinking</strong> - Benefactors are explicitly building for successors they may never meet. This isn't about seeing results in your lifetime. It's about ensuring the infrastructure exists for those who come after. Regular impact reports provide concrete feedback on projects funded, members retained, and systems sustained.</div>
</div></div>
</div>

<!-- HOW TO EARN CREDITS -->
<div class="credit-section">
<h2><i class="fas fa-coins"></i> How to Earn Credits</h2>
<div class="credit-grid">
<div class="credit-item"><i class="fas fa-play-circle"></i><div><h4>Engage with Content</h4><p>Watch videos, read papers, and complete foundation materials at your tier level.</p><div class="pts">10-40 pts per item (based on difficulty)</div></div></div>
<div class="credit-item"><i class="fas fa-comments"></i><div><h4>Discussion Posts</h4><p>Write substantive analysis demonstrating real comprehension. Evaluated by higher-tier members.</p><div class="pts">10-50 pts per post (based on quality)</div></div></div>
<div class="credit-item"><i class="fas fa-chalkboard-teacher"></i><div><h4>Think Tank Sessions</h4><p>Collaborative analysis sessions exploring systemic interactions and cross-concept synthesis.</p><div class="pts">50 pts per session</div></div></div>
<div class="credit-item"><i class="fas fa-user-check"></i><div><h4>Evaluate Others</h4><p>Review and provide constructive feedback on discussions from lower tiers.</p><div class="pts">10 pts per evaluation</div></div></div>
<div class="credit-item"><i class="fas fa-tools"></i><div><h4>Contribution Projects</h4><p>Build content, infrastructure, simulations, or research. Milestone-based with independent recognition.</p><div class="pts">50-250 pts (based on scope)</div></div></div>
<div class="credit-item"><i class="fas fa-lightbulb"></i><div><h4>Suggest Content</h4><p>Propose quality educational resources for the community library. Reviewed for alignment and value.</p><div class="pts">10 pts per approved suggestion</div></div></div>
</div>
</div>

<!-- Discussion Quality Rubric -->
<div style="margin:2rem 0">
<h3 style="font-family:'Orbitron',sans-serif;color:#fff;text-align:center;margin-bottom:1rem;font-size:1.1rem">Discussion Quality Rubric</h3>
<p style="text-align:center;color:#888;margin-bottom:1.25rem;font-size:0.9rem">All posts are evaluated by higher-tier members using this standard</p>
<div class="rubric">
<div class="rubric-item r-inadequate"><div class="label">Inadequate</div><div class="pts">0 pts</div><p>Generic, no engagement with actual concepts</p></div>
<div class="rubric-item r-adequate"><div class="label">Adequate</div><div class="pts">10 pts</div><p>References content, basic understanding shown</p></div>
<div class="rubric-item r-strong"><div class="label">Strong</div><div class="pts">25 pts</div><p>Synthesizes sources, original insights</p></div>
<div class="rubric-item r-exceptional"><div class="label">Exceptional</div><div class="pts">50 pts</div><p>Novel connections, mastery-level analysis</p></div>
</div>
</div>

<div class="cta">
<h3>Ready to Begin?</h3>
<p>Join the Discord, start as an Observer, and begin understanding why coordination is humanity's bottleneck.</p>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn"><i class="fab fa-discord"></i> Join Discord</a>
</div>
</div>

<?php include('includes/footer.php'); ?>
<script>function toggleTier(header){header.parentElement.classList.toggle('open')}</script>
</body>
</html>

