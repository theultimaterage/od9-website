/* atlas-guides.js — four presences at the map's edge (split out 2026-09-06).
 *
 * The founder's connective tissue, 2026-09-05: each guide owns one layer —
 * the Navigator the tour, the Archivist the timeline, the Forgemaster the
 * forge, the Quartermaster the sound. Hover gives a line in their voice; tap
 * gives the line AND the thing they own, so a guide is a door, not a label.
 * Portraits are the canonical reference stills, cropped to medallions by
 * tools/build_atlas_guides.py in the bot repo.
 *
 * This module is an orchestrator: it holds no state of the map, and every
 * action it takes belongs to another layer. So it is handed those layers
 * rather than reaching for them, which is what keeps it a leaf.
 *
 * window.AtlasGuides.init({ nodes, esc, ping, reducedMotion, focusNode,
 *                           beforeAct, sound, tour, timeline })
 *   tour     = { openMenu() }
 *   timeline = { counts(), isPlaying(), play(), bar }
 *   -> { speak(key, sticky), line(key) }
 */
(function () {
  "use strict";

  function init(o) {
    var say = document.getElementById("atlas-guide-say"), timer = null;
    var esc = o.esc, ping = o.ping || function () {};
    var tour = o.tour || {}, timeline = o.timeline || {};

    function forgeNode() {
      return (o.nodes || []).filter(function (n) { return n.forge; })[0] || null;
    }

    var GUIDES = {
      navigator: {
        who: "The Navigator", go: "Take the tour",
        line: function () { return "Four routes are preached. Say the word and I fly you down one."; },
        act: function () { if (tour.openMenu) tour.openMenu(); }
      },
      archivist: {
        who: "The Archivist", go: "Replay the history",
        line: function () {
          var c = timeline.counts ? timeline.counts() : { canon: 0 };
          return (timeline.isPlaying && timeline.isPlaying())
            ? "Watch the book get written: every chapter lights up on the day it was preached, first publish to now, in eight seconds."
            : "Every star has a date. I keep them. " + c.canon + " chapters are canon; drag the bar and watch the book get written.";
        },
        act: function () {
          if (timeline.bar) timeline.bar.scrollIntoView({ behavior: o.reducedMotion ? "auto" : "smooth", block: "center" });
          if (timeline.play && !(timeline.isPlaying && timeline.isPlaying())) timeline.play();
        }
      },
      forgemaster: {
        who: "The Forgemaster", go: "Go to the forge",
        line: function () {
          var f = forgeNode();
          return f ? "Chapter " + f.num + " is on the anvil — " + f.title + ". Sunday it gets struck."
                   : "The anvil's cold this week. Come back Sunday.";
        },
        act: function () { var f = forgeNode(); if (f) o.focusNode(f.id); }
      },
      quartermaster: {
        who: "The Quartermaster", go: "Toggle sound",
        line: function () {
          return (o.sound && o.sound.enabled()) ? "Sound's on. You're welcome."
               : "Sound's my department. Tap the note. Or don't — I'm not your mama.";
        },
        act: function () { var b = document.getElementById("atlas-sound"); if (b) b.click(); }
      }
    };

    function speak(key, sticky) {
      var g = GUIDES[key];
      if (!g || !say) return;
      if (timer) { clearTimeout(timer); timer = null; }
      say.innerHTML = '<div class="who">' + esc(g.who) + "</div>" + esc(g.line()) +
        '<br><button type="button" class="go" id="atlas-guide-go">' + esc(g.go) + " →</button>";
      say.classList.add("on");
      var goBtn = document.getElementById("atlas-guide-go");
      if (goBtn) goBtn.addEventListener("click", function () { g.act(); speak(key, true); });
      timer = setTimeout(function () { say.classList.remove("on"); }, sticky ? 6000 : 3500);
    }

    Array.prototype.forEach.call(document.querySelectorAll(".atlas-guide"), function (btn) {
      var key = btn.getAttribute("data-guide");
      if (key === "forgemaster" && forgeNode()) btn.classList.add("forge");
      btn.addEventListener("mouseenter", function () { speak(key, false); });
      btn.addEventListener("focus", function () { speak(key, false); });
      btn.addEventListener("click", function (ev) {
        ev.stopPropagation();                  /* the document's outside-click closer must not eat the tour menu the Navigator just opened */
        if (o.beforeAct) o.beforeAct();
        ping("guide", { who: key });
        GUIDES[key].act();
        speak(key, true);
        if (o.sound) o.sound.cue("probe", "star");
      });
    });

    return {
      speak: speak,
      line: function (k) { return GUIDES[k] ? GUIDES[k].line() : null; }
    };
  }

  window.AtlasGuides = { init: init };
})();
