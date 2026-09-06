/* atlas-timeline.js — scrub the ledger (spectacle move 5; split out 2026-09-06).
 *
 * Every chapter carries history [{state, at}]; as of a date its state is the
 * last entry at or before it, and raw before any. Scrubbing rewrites n.state
 * and n.forge IN PLACE, and "now" restores them, so every draw path — colours,
 * rings, the object a chapter resolves into — sees the past for free rather
 * than each one needing a date-aware branch. That in-place rewrite is the whole
 * trick, and the reason this module is handed the live node array.
 *
 * "Replay history" plays the whole ledger in about eight seconds.
 *
 * window.AtlasTimeline.init({ nodes, ledger, ping, sound })
 *   -> { counts, asOf, days, isPlaying, play, stop, bar, onPlay }
 * onPlay(fn) registers a listener for the replay starting — the Archivist
 * narrates it, and a callback keeps this module from having to know that.
 */
(function () {
  "use strict";

  function init(o) {
    var nodes = o.nodes || [], TL = o.ledger || null;
    var ping = o.ping || function () {}, sound = o.sound;

    var bar = document.getElementById("atlas-timeline"), range = document.getElementById("atlas-tl-range");
    var dateEl = document.getElementById("atlas-tl-date"), statEl = document.getElementById("atlas-tl-stat");
    var playBtn = document.getElementById("atlas-tl-play"), nowBtn = document.getElementById("atlas-tl-now");
    var asOf = null, timer = null, days = 0, listeners = [];

    function dayOf(date) { return Math.round((Date.parse(date + "T12:00:00Z") - Date.parse(TL.start + "T12:00:00Z")) / 86400000); }
    function dateOf(day) { return new Date(Date.parse(TL.start + "T12:00:00Z") + day * 86400000).toISOString().slice(0, 10); }

    function stateAt(n, date) {
      var st = "raw", forge = false;
      (n.history || []).forEach(function (h) {
        if (h.at <= date) { if (h.state === "forge") forge = true; else st = h.state; }
      });
      return { state: st, forge: forge };
    }
    function applyAsOf(date) {
      nodes.forEach(function (n) {
        if (n._state0 === undefined) { n._state0 = n.state; n._forge0 = n.forge; }
        if (date) { var s = stateAt(n, date); n.state = s.state; n.forge = s.forge; }
        else { n.state = n._state0; n.forge = n._forge0; }
      });
      asOf = date;
      paint();
    }
    function counts() {
      var c = { canon: 0, forge: 0, preached: 0 };
      nodes.forEach(function (n) { if (n.state === "canon") c.canon++; if (n.state === "preached") c.preached++; if (n.forge) c.forge++; });
      return c;
    }
    function paint() {
      if (!dateEl) return;
      var c = counts();
      dateEl.textContent = asOf ? "as of " + asOf : "now";
      var s = c.canon + " canon";
      if (c.preached) s += " · " + c.preached + " preached";
      s += " · " + c.forge + " in the forge";
      if (!asOf) {
        var weekAgo = dateOf(Math.max(0, days - 7));
        var recent = TL.events.filter(function (e) { return e.at >= weekAgo; });
        s += recent.length ? " · this week: " + recent.length + " change" + (recent.length > 1 ? "s" : "")
                           : " · nothing changed this week";
      }
      statEl.textContent = s;
      bar.classList.toggle("past", !!asOf);
    }
    function stop() {
      if (timer) { clearInterval(timer); timer = null; if (playBtn) playBtn.innerHTML = "&#9654; Replay history"; }
    }
    function play() {
      if (timer) { stop(); return; }
      ping("timeline", { how: "play" });
      var day = 0, lastCanon = -1, step = Math.max(1, Math.round(days / 200));
      if (playBtn) playBtn.innerHTML = "&#10074;&#10074; Replaying";
      listeners.forEach(function (fn) { try { fn(); } catch (e) { /* a narrator must never stop the replay */ } });
      timer = setInterval(function () {
        day += step;
        if (day >= days) {
          range.value = String(days); applyAsOf(null); stop();
          if (sound) sound.reveal();
          return;
        }
        range.value = String(day); applyAsOf(dateOf(day));
        var c = counts().canon;
        if (c > lastCanon && lastCanon >= 0 && sound) sound.cue("gold-star", "canon");
        lastCanon = c;
      }, 40);
    }

    if (TL && TL.events && bar && range) {
      days = Math.max(1, dayOf(TL.end));
      range.max = String(days); range.value = String(days);
      bar.removeAttribute("hidden");
      var pinged = false;
      range.addEventListener("input", function () {
        stop();
        var v = parseInt(range.value, 10);
        applyAsOf(v >= days ? null : dateOf(v));
        if (!pinged) { pinged = true; ping("timeline", { how: "scrub" }); }
      });
      if (nowBtn) nowBtn.addEventListener("click", function () { stop(); range.value = String(days); applyAsOf(null); });
      if (playBtn) playBtn.addEventListener("click", play);
      paint();
    }

    return {
      counts: counts,
      asOf: function () { return asOf; },
      days: function () { return days; },
      isPlaying: function () { return !!timer; },
      play: play, stop: stop, bar: bar,
      onPlay: function (fn) { listeners.push(fn); }
    };
  }

  window.AtlasTimeline = { init: init };
})();
