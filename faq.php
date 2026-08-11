<?php
/**
 * OD9 - FAQ
 * The eight questions newcomers actually ask: is this a cult, do I have to be an
 * atheist, what's the manifesto, what do I do here, is it free, what's Type 1,
 * is this a research project + my data, who's behind this.
 * Copy source: docs/MISSION_CLARITY.md Module F (clean public voice, verbatim).
 */
$page_title = 'FAQ | OD9 - Off Da Nine';
$page_description = 'Honest answers to the real questions about OD9: is it a cult, do you have to be an atheist, what is the manifesto, what do you actually do, is it free, and who is behind it.';
$page_slug = 'faq.php';
$page_og_description = 'Is this a cult? Do I have to be an atheist? What do I actually do here? Straight answers about OD9 - the anti-church building toward Kardashev Type 1.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is this a cult?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Fair question - bold movement, a manifesto, levels, a founder. So here's the test: a cult runs on faith, punishes questions, hides its books, and exists to serve the guy at the top. OD9 is the exact opposite. Question everything - bring a real argument and we'll show you the evidence or change our minds. Our numbers are public - members, supporters, revenue, right on the site. We publish our failures, not just our wins. And the founder climbs the same ladder as everyone else, from the bottom, no shortcuts - there's no pope here. A cult wants your obedience. We want your judgment."
      }
    },
    {
      "@type": "Question",
      "name": "Do I have to be an atheist?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No oath, no purity test. But straight up: OD9 is evidence-first, and we think the evidence against the supernatural is overwhelming - we don't hide that, it's half the reason for the name. What we ask isn't that you already agree. It's that you're willing to follow evidence wherever it leads, even when it's uncomfortable. If you can question your own beliefs, you belong here. If you need them protected from questions, you won't be happy."
      }
    },
    {
      "@type": "Question",
      "name": "What's the manifesto? Do I have to read the whole thing?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The OD9 Manifesto is the full framework - six volumes, the whole worldview, sourced and dated. And no - you don't sit down and read a brick. The entire point of the progression is that you absorb it as you climb: the right ideas at the right time, made clear, talked through with your crew. The complete manifesto is what you unlock at the very top."
      }
    },
    {
      "@type": "Question",
      "name": "What do I actually do here?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Day to day: hang out and talk in Discord, answer the daily question, jump in the Live, work through your tier's material with your cohort, and ship the occasional capstone to level up. Over time: you go from learning the ideas to building with them - real projects, real contributions to the mission."
      }
    },
    {
      "@type": "Question",
      "name": "Is it free? How does OD9 make money?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Free to join and climb. We're funded by Patreon supporters who want to push the mission faster, and long-term by research partnerships and grants. We publish our real numbers - members, supporters, revenue - right on the site. No ads. We never sell your data."
      }
    },
    {
      "@type": "Question",
      "name": "What's \"Kardashev Type 1\"?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "A scale for how advanced a civilization is, measured by how well it harnesses energy and coordinates at scale. Type 1 = a species that's mastered its own planet: clean energy, no manufactured scarcity, governance that actually works. We're at about 0.73. Reaching 1 is the near-term mission - not sci-fi, just the minimum for making it through this century in one piece."
      }
    },
    {
      "@type": "Question",
      "name": "Is this a research project? What happens to my data?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Both things are true: OD9 is a real community and an honest experiment in whether humans and AI can coordinate better together. We're upfront about that - radical transparency is the whole brand. We don't sell or hand off your data; anything used to study the system is anonymized; and nothing becomes a formal study without your say-so. Want out at any point? We honor it."
      }
    },
    {
      "@type": "Question",
      "name": "Who's behind this?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "OD9 is built by The Ultimate Rage - a Chicago artist and founder - in deep collaboration with AI. The whole thing - the community, the system, the manifesto - was built from scratch by one person and a machine, which is kind of the point: small groups coordinating well can do what giant institutions can't."
      }
    }
  ]
}
</script>
<style>
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;padding-top:80px;min-height:100vh;line-height:1.7}
h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff;line-height:1.25}

/* Hero */
.faq-hero{background:linear-gradient(135deg,#050505 0%,#0a0a12 55%,#050505 100%);border-bottom:1px solid #1a1a2e;padding:4.2rem 1.5rem 3rem;text-align:center}
.faq-hero .badge{display:inline-block;font-family:'Orbitron',sans-serif;font-size:.72rem;letter-spacing:3px;text-transform:uppercase;color:var(--b);border:1px solid rgba(0,191,255,.34);padding:.35rem 1rem;border-radius:2px;text-shadow:0 0 12px rgba(0,191,255,.4);margin-bottom:1.3rem}
.faq-hero h1{font-size:clamp(2rem,6vw,3.2rem);font-weight:900;text-shadow:0 0 30px rgba(0,191,255,.27);margin-bottom:.9rem}
.faq-hero p{max-width:600px;margin:0 auto;color:#aaa;font-size:1.08rem}

/* Accordion list */
.faq-main{max-width:820px;margin:0 auto;padding:2.8rem 1.5rem 1rem}
.faq-item{background:linear-gradient(135deg,#0d0d0d,#0a0a12);border:1px solid #1a1a2e;border-left:3px solid rgba(0,191,255,.45);border-radius:6px;margin-bottom:1rem;overflow:hidden;transition:border-color .25s}
.faq-item[open]{border-left-color:var(--b);box-shadow:0 0 24px rgba(0,191,255,.08)}
.faq-item summary{list-style:none;cursor:pointer;padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:clamp(1.05rem,2.6vw,1.25rem);color:#fff}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary:hover{color:var(--b)}
.faq-item summary .chev{flex:0 0 auto;color:var(--b);font-size:.95rem;transition:transform .25s}
.faq-item[open] summary .chev{transform:rotate(180deg)}
.faq-item .answer{padding:0 1.5rem 1.4rem;color:#c4c7cb;font-size:1.02rem}
.faq-item .answer p{margin:0 0 .9rem}
.faq-item .answer p:last-child{margin-bottom:0}
.faq-item .answer strong{color:#fff}
.faq-item .answer .term{font-family:'Orbitron',sans-serif;color:var(--b);text-shadow:0 0 12px rgba(0,191,255,.35)}
.faq-item .answer a{color:var(--b);text-decoration:none;border-bottom:1px solid rgba(0,191,255,.35)}
.faq-item .answer a:hover{border-bottom-color:var(--b)}

/* Bottom CTA */
.faq-cta{max-width:820px;margin:1.5rem auto 4rem;padding:0 1.5rem}
.faq-cta-inner{background:linear-gradient(135deg,rgba(0,191,255,.1),rgba(0,0,0,.6));border:1px solid rgba(0,191,255,.3);border-radius:16px;padding:2.4rem 2rem;text-align:center;box-shadow:var(--g)}
.faq-cta-inner h2{font-size:clamp(1.4rem,4vw,2rem);margin-bottom:.9rem}
.faq-cta-inner p{max-width:560px;margin:0 auto 1.6rem;color:#c4c7cb}
.faq-cta-buttons{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:.5rem;font-family:'Orbitron',sans-serif;font-size:.78rem;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;padding:.8rem 1.5rem;border-radius:4px;text-decoration:none;transition:transform .25s,box-shadow .25s}
.btn-primary{background:linear-gradient(135deg,var(--b),var(--eb));color:#000;box-shadow:var(--g)}
.btn-primary:hover{transform:translateY(-2px)}
.btn-ghost{background:transparent;color:var(--b);border:1px solid rgba(0,191,255,.5)}
.btn-ghost:hover{background:rgba(0,191,255,.08);transform:translateY(-2px)}

@media(max-width:600px){
  .faq-hero{padding:3.2rem 1.2rem 2.4rem}
  .faq-item summary{padding:1.05rem 1.15rem}
  .faq-item .answer{padding:0 1.15rem 1.2rem}
}
</style>
</head>
<body>
<?php $current_page = 'faq'; include('includes/nav.php'); ?>

<header class="faq-hero">
  <span class="badge">No make-believe</span>
  <h1>FAQ</h1>
  <p>The real questions, answered straight. Bring more &mdash; we'll show you the evidence or change our minds.</p>
</header>

<main class="faq-main">

  <details class="faq-item">
    <summary>Is this a cult? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>Fair question &mdash; bold movement, a manifesto, levels, a founder. So here's the test: a cult runs on <em>faith</em>, punishes questions, hides its books, and exists to serve the guy at the top. OD9 is the exact opposite. Question everything &mdash; bring a real argument and we'll show you the evidence or change our minds. Our numbers are public &mdash; members, supporters, revenue, right on the site. We publish our <em>failures</em>, not just our wins. And the founder climbs the same ladder as everyone else, from the bottom, no shortcuts &mdash; <strong>there's no pope here.</strong> A cult wants your obedience. <strong>We want your judgment.</strong></p>
    </div>
  </details>

  <details class="faq-item">
    <summary>Do I have to be an atheist? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>No oath, no purity test. But straight up: OD9 is evidence-first, and we think the evidence against the supernatural is overwhelming &mdash; we don't hide that, it's half the reason for the name. What we ask isn't that you already agree. It's that you're willing to <strong>follow evidence wherever it leads</strong>, even when it's uncomfortable. If you can question your own beliefs, you belong here. If you need them protected <em>from</em> questions, you won't be happy.</p>
    </div>
  </details>

  <details class="faq-item">
    <summary>What's the manifesto? Do I have to read the whole thing? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>The OD9 Manifesto is the full framework &mdash; six volumes, the whole worldview, sourced and dated. And no &mdash; you don't sit down and read a brick. The entire point of the progression is that you <em>absorb</em> it as you climb: the right ideas at the right time, made clear, talked through with your crew. The complete manifesto is what you unlock at the very top. <em>(Real talk: it's so thorough that even the founder hasn't read every word front to back &mdash; the game is how you actually learn it.)</em></p>
    </div>
  </details>

  <details class="faq-item">
    <summary>What do I actually do here? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>Day to day: hang out and talk in Discord, answer the daily question, jump in the Live, work through your tier's material with your cohort, and ship the occasional capstone to level up. Over time: you go from <em>learning</em> the ideas to <em>building</em> with them &mdash; real projects, real contributions to the mission. <a href="about.php">See how the progression works &rarr;</a></p>
    </div>
  </details>

  <details class="faq-item">
    <summary>Is it free? How does OD9 make money? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p><strong>Free to join and climb.</strong> We're funded by Patreon supporters who want to push the mission faster, and long-term by research partnerships and grants. We publish our real numbers &mdash; members, supporters, revenue &mdash; right on the site. No ads. <strong>We never sell your data.</strong></p>
    </div>
  </details>

  <details class="faq-item">
    <summary>What's "Kardashev Type 1"? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>A scale for how advanced a civilization is, measured by how well it harnesses energy and coordinates at scale. <span class="term">Type 1</span> = a species that's mastered its own planet: clean energy, no manufactured scarcity, governance that actually works. We're at about <strong>0.73</strong>. Reaching 1 is the near-term mission &mdash; not sci-fi, just the minimum for making it through this century in one piece.</p>
    </div>
  </details>

  <details class="faq-item">
    <summary>Is this a research project? What happens to my data? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>Both things are true: OD9 is a real community <em>and</em> an honest experiment in whether humans and AI can coordinate better together. We're upfront about that &mdash; radical transparency is the whole brand. We don't sell or hand off your data; anything used to study the system is anonymized; and nothing becomes a formal study without your say-so. Want out at any point? <strong>We honor it.</strong></p>
    </div>
  </details>

  <details class="faq-item">
    <summary>Who's behind this? <span class="chev"><i class="fas fa-chevron-down"></i></span></summary>
    <div class="answer">
      <p>OD9 is built by <strong>The Ultimate Rage</strong> &mdash; a Chicago artist and founder &mdash; in deep collaboration with AI. The whole thing &mdash; the community, the system, the manifesto &mdash; was built from scratch by one person and a machine, which is kind of the point: <strong>small groups coordinating well can do what giant institutions can't.</strong></p>
    </div>
  </details>

</main>

<section class="faq-cta">
  <div class="faq-cta-inner">
    <h2>Still curious? Come question us in person.</h2>
    <p>About a hundred people are already on the ground floor. You don't need a degree or a dime &mdash; just curiosity and the guts to question everything, including us.</p>
    <div class="faq-cta-buttons">
      <a class="btn btn-primary" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener"><i class="fab fa-discord"></i> Join OD9 on Discord</a>
      <a class="btn btn-ghost" href="about.php">What OD9 is &rarr;</a>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>
