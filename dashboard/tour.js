/* OD9 guided tour — four-surface chaptered journey (Board → HQ → Bunker → Settings).
 * Spec: docs/GUIDED_TOUR_SPEC.md. Successor to onboarding-tour.js (retired same
 * change). External file on purpose: the origin HTML minifier only touches PHP
 * responses, so this is never collapsed.
 *
 * Each toured page declares itself via <body data-tour="board|dashboard|bunker|settings">
 * (+ data-tour-csrf for the server-state POST) and includes /js/lib/driver.{css,js}
 * (vendored — the jsdelivr CDN was an adblocker-silent kill-switch; /vendor/ paths
 * are WAF-blocked, hence js/lib) + this file + css/tour.css.
 *
 * State: localStorage "od9_tour" {v:2, chapters:{page:"done"}, chain:page|null},
 * legacy "od9_tour_seen" migrates to {board:"done"} (existing members are never
 * re-welcomed). Server mirror: tour-state.php (web MySQL, fail-open, union-of-done
 * merge — never demotes). Chain: the "continue the tour" links inside final
 * popovers are normal a.ztrans-link anchors, so the existing ztrans door clips
 * carry the member between surfaces; the target page sees state.chain and
 * auto-starts its own chapter.
 */
(function () {
  "use strict";

  var PAGE = document.body ? (document.body.getAttribute("data-tour") || "") : "";
  var CSRF = document.body ? (document.body.getAttribute("data-tour-csrf") || "") : "";
  var LS_KEY = "od9_tour";

  /* Continue-link factory: a real ztrans anchor — the document-level ztrans click
     handler plays the door clip and navigates; our own click handler (below)
     records the chain + chapter completion before the page unloads. */
  function cont(page, clip, href, label) {
    return '<div class="od9-tour-next"><a class="ztrans-link tour-continue" data-ztrans="' + clip +
      '" data-tour-next="' + page + '" href="' + href + '">' + label + " &rarr;</a></div>";
  }

  var CHAPTERS = {
    board: {
      steps: [
        /* .worldstate and .value steps were removed 2026-08-16: P2 of the board
           redesign moved that content to progress.php and index.php, so both
           selectors matched nothing. The filter in startTour() was silently
           dropping them, which is graceful but leaves a tour that skips
           straight past the things that replaced them. */
        { element: ".viewport", popover: { title: "Where you are",
          description: "Your zone. Five zones, one per tier — you climb through them from The Wake to The Horizon." } },
        { element: ".zone-nav", popover: { title: "The world map",
          description: "Every zone is open to <b>look at</b>, even ones you haven't reached yet — that's how you follow along when a live lesson comes from a higher tier. You can only <b>earn</b> in your own zone." } },
        { element: ".chapters", popover: { title: "This tier, in chapters",
          description: "Your tier's curriculum, grouped. Click a chapter to see its lessons — the gold-filled one is what you're looking at, the outlined one is where you actually are." } },
        { element: ".rail", popover: { title: "Your path",
          description: "Every stop is one lesson. Filled = done, gold = your move right now, hollow = still ahead. Click any of them to open it." } },
        { element: ".dockwrap", popover: { title: "Your moves this turn",
          description: "Your daily to-do. Start with today's question — it's your fastest first win, and it's right here." } },
        { element: ".gate", popover: { title: "The Gate",
          description: "What it takes to reach the next tier: credits, value dimensions, and a capstone. Think of it as the boss at the end of the zone." } },
        { element: ".guide", popover: { title: "Your Guide",
          description: "Stuck? Your guide is here the whole climb. You're never doing this alone." } },
        { element: "header.hud", popover: { title: "Getting around",
          description: "Your full Dashboard, the Bunker (rewards), and Settings all live up here. Want the full walkthrough?" +
            cont("dashboard", "hq-door", "index.php", "Continue the tour — OD9 HQ") } }
      ],
      doneBtnText: "Make my first move →",
      onComplete: firstMove
    },
    dashboard: {
      steps: [
        { element: ".dashboard-header", popover: { title: "OD9 HQ",
          description: "The board is the game; this is the war room. Everything you've earned, measured and traceable." } },
        { element: "#card-tier", popover: { title: "Tier + credits",
          description: "Your tier, credits, and distance to the next gate — the same numbers the protocol enforces." } },
        { element: "#card-tier", popover: { title: "One gate you can't grind",
          description: "Higher tiers need <b>mentorship</b> — one member a rung ahead getting one member " +
            "unstuck, on a 90-day clock. Reaching Pioneer requires having taken someone through it. " +
            "Raise your hand with <b>/mentor request</b> in Discord, or read the whole thing with " +
            "<b>/mentor guide</b>. Nobody gets rejected — if you're not picked you stay in the pool." } },
        { element: "#card-dimensions", popover: { title: "The five dimensions",
          description: "These only move when your work passes evaluation. That's deliberate — value here is verified, never vibes." } },
        { element: "#card-achievements", popover: { title: "Achievements",
          description: "Milestones you've unlocked. Flex them in Discord with <b>/achievements showcase</b>." } },
        { element: "#card-activity", popover: { title: "Your verified feed",
          description: "Every credit traces to a real action. This is your receipt trail." } },
        { element: ".journey-cta-row", popover: { title: "Fast routes",
          description: "Your current curriculum and The Map — the two doors you'll use most." +
            cont("bunker", "hatch", "bunker.php", "Continue the tour — The Bunker") } }
      ],
      doneBtnText: "Finish →"
    },
    bunker: {
      steps: [
        { element: "header.hud", popover: { title: "The Bunker",
          description: "The vault. Members-only drops live down here — and it is <b>earned</b>, not asked for. " +
            "Entry takes two keys: credits for showing up, and Knowledge for the work. Presence buys the first and " +
            "never the second. See exactly what you're short with <b>/store view</b>." } },
        { element: ".vault-exclusive", popover: { title: "Exclusive drops",
          description: "Releases from the collective you won't find on public platforms — streams, tracks, early cuts." } },
        { element: ".lyrics", popover: { title: "The lyric vault",
          description: "Read along with the catalog — then bring your take to the Song of the Week for credit." } },
        { element: "header.hud .exit.ztrans-link", popover: { title: "The way out",
          description: "Doors back to The Map and HQ whenever you're done digging." +
            cont("settings", "toolbox", "settings.php", "Continue the tour — Settings") } }
      ],
      doneBtnText: "Finish →"
    },
    settings: {
      steps: [
        { element: ".settings-card", popover: { title: "Your public face",
          description: "Control what the world sees of your progression. Private by default — you choose what to show." } },
        { element: ".url-box", popover: { title: "Your profile link",
          description: "Your shareable page — recruit with receipts." } },
        { element: ".save-btn", popover: { title: "Lock it in",
          description: "Changes only stick once you save." } },
        { element: ".back-link", popover: { title: "Full circuit complete",
          description: "That's every surface of the protocol. Now go play." +
            cont("", "gate", "board.php", "Back to your Board") } }
      ],
      doneBtnText: "Finish →"
    }
  };

  /* ---- state ---- */
  function load() {
    var s = null;
    try { s = JSON.parse(localStorage.getItem(LS_KEY) || "null"); } catch (e) {}
    if (!s || typeof s !== "object" || !s.chapters) { s = { v: 2, chapters: {}, chain: null }; }
    try { /* legacy single-page flag → board done, never re-welcome */
      if (localStorage.getItem("od9_tour_seen") === "1") { s.chapters.board = "done"; }
    } catch (e) {}
    return s;
  }
  function save(s) { try { localStorage.setItem(LS_KEY, JSON.stringify(s)); } catch (e) {} }

  function markDone(page) {
    var s = load();
    if (s.chapters[page] === "done") { syncBeacon(s); return; }
    s.chapters[page] = "done";
    save(s);
    syncBeacon(s);
    push(page);
  }

  /* ---- server mirror (fail-open; union-of-done — never demotes) ---- */
  function push(page) {
    if (!CSRF || !window.fetch) { return; }
    try {
      fetch("tour-state.php", { method: "POST", credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "csrf_token=" + encodeURIComponent(CSRF) + "&chapter=" + encodeURIComponent(page)
      }).catch(function () {});
    } catch (e) {}
  }
  function pull() {
    if (!CSRF || !window.fetch) { return; }
    try {
      fetch("tour-state.php", { credentials: "same-origin" }).then(function (r) {
        return r.ok ? r.json() : null;
      }).then(function (j) {
        if (!j || !j.chapters) { return; }
        var s = load(), changed = false, k;
        for (k in j.chapters) { if (j.chapters[k] === "done" && s.chapters[k] !== "done") { s.chapters[k] = "done"; changed = true; } }
        if (changed) { save(s); }
        syncBeacon(s);
        /* cross-device: server says this member already toured the board —
           retract a welcome the sync path may have just shown */
        if (PAGE === "board" && s.chapters.board === "done") {
          var ov = document.getElementById("od9-welcome");
          if (ov) { ov.setAttribute("hidden", ""); }
        }
      }).catch(function () {});
    } catch (e) {}
  }

  /* ---- beacon (▶ TOUR pill until this page's chapter is done, then ⓘ Tour) ---- */
  function syncBeacon(s) {
    var b = document.getElementById("od9-tour-replay");
    if (!b) { return; }
    b.classList.toggle("seen", (s || load()).chapters[PAGE] === "done");
  }

  /* ---- board finale: the first-move bridge (QOTD → focused quest → nothing) ---- */
  function firstMove() {
    var q = document.querySelector(".questdock.qotd");
    if (q) {
      q.setAttribute("open", "");
      q.scrollIntoView({ behavior: "smooth", block: "center" });
      var t = q.querySelector("textarea");
      if (t) { setTimeout(function () { try { t.focus(); } catch (e) {} }, 450); }
      return;
    }
    var r = document.querySelector(".questdock .read-link");
    if (r) { r.scrollIntoView({ behavior: "smooth", block: "center" }); }
  }

  /* ---- the tour itself ---- */
  function startTour() {
    var ch = CHAPTERS[PAGE];
    if (!ch) { return; }
    if (!(window.driver && window.driver.js && window.driver.js.driver)) { return; }
    /* present AND visible — e.g. settings' .url-box hides while the profile is
       private; highlighting an invisible element strands the tour */
    var steps = ch.steps.filter(function (st) {
      var el = document.querySelector(st.element);
      return el && el.offsetParent !== null;
    });
    if (!steps.length) { return; }
    var completed = false;
    var d = window.driver.js.driver({
      showProgress: true,
      allowClose: true,
      nextBtnText: "Next →",
      prevBtnText: "← Back",
      doneBtnText: ch.doneBtnText || "Finish →",
      steps: steps,
      onNextClick: function () {
        if (d.isLastStep()) { completed = true; }
        d.moveNext();
      },
      onDestroyed: function () {
        markDone(PAGE); /* any close = seen; never nag (anti-overwhelm) */
        if (completed && ch.onComplete) { ch.onComplete(); }
      }
    });
    d.drive();
  }

  /* board chapter opens with the full-screen 'tour' clip (fail-open); other
     chapters start dry — the door clip already played on the way in */
  function launchTour() {
    /* driver engine missing (blocked/broken)? mark done so the welcome modal
       can never loop a member who physically can't receive the tour */
    if (!(window.driver && window.driver.js && window.driver.js.driver)) { markDone(PAGE); return; }
    if (PAGE === "board" && typeof window.odZtransPlay === "function") {
      window.odZtransPlay("tour", startTour);
    } else {
      startTour();
    }
  }

  /* ---- first-visit welcome (board only) ---- */
  function maybeWelcome(s) {
    if (PAGE !== "board") { return; }
    var ov = document.getElementById("od9-welcome");
    if (!ov) { return; }
    var params = new URLSearchParams(window.location.search);
    if (s.chapters.board !== "done" && !params.has("tier")) { ov.removeAttribute("hidden"); }
  }

  function init() {
    if (!PAGE || !CHAPTERS[PAGE]) { return; }
    var s = load();
    save(s); /* persist any legacy migration */
    syncBeacon(s);

    var replay = document.getElementById("od9-tour-replay");
    if (replay) { replay.addEventListener("click", function (e) { e.preventDefault(); launchTour(); }); }

    /* continue-links inside popovers: record the handoff before ztrans navigates */
    document.addEventListener("click", function (e) {
      var a = e.target && e.target.closest ? e.target.closest("a.tour-continue") : null;
      if (!a) { return; }
      var st = load();
      st.chapters[PAGE] = "done";
      st.chain = a.getAttribute("data-tour-next") || null;
      save(st);
      push(PAGE);
    });

    var ov = document.getElementById("od9-welcome");
    if (ov) {
      var go = document.getElementById("od9-welcome-go");
      var skip = document.getElementById("od9-welcome-skip");
      if (go) { go.addEventListener("click", function () { ov.setAttribute("hidden", ""); launchTour(); }); }
      if (skip) { skip.addEventListener("click", function () { ov.setAttribute("hidden", ""); markDone(PAGE); }); }
    }

    /* arriving on a chain handoff? auto-start this page's chapter */
    if (s.chain === PAGE) {
      s.chain = null;
      save(s);
      startTour();
    } else {
      maybeWelcome(s);
    }

    pull(); /* server merge (may re-hide nothing — welcome only ever ADDS on top) */
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
