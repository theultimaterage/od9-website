/* atlas-beacon.js — the Atlas's own measurement (2026-09-05, split out 2026-09-06).
 *
 * The spec names four success measures — live-link opens, deep-link arrivals
 * from Shorts and Discord, card-to-codex click-through, and whether the first
 * frame lands — and a #chNN arrival never reaches the server log, so none of
 * them could be counted from access logs. Small events go to
 * api/v1/atlas-ping.php: one JSON line per event, appended outside the docroot.
 * tools/atlas_stats.py in the bot repo reads them.
 *
 * Privacy by construction: no cookie, no IP, no user agent beyond phone or
 * desktop. The session id is a random string this tab keeps for its lifetime.
 *
 * It owns no map state and reads none, which is why it is its own file: every
 * other layer sends through it, and it depends on nothing but the browser.
 * Sending is fire-and-forget and can never break the map — a failed send is a
 * lost measurement, never a broken page.
 *
 * window.AtlasBeacon = { ping(event, props), sid(), vp() }
 */
(function () {
  "use strict";
  var ENDPOINT = "api/v1/atlas-ping.php";

  var sid = "";
  try {
    sid = sessionStorage.getItem("atlas.sid") || "";
    if (!sid) {
      sid = Math.random().toString(36).slice(2, 10) + Math.random().toString(36).slice(2, 6);
      sessionStorage.setItem("atlas.sid", sid);
    }
  } catch (e) { sid = "anon"; }          /* private mode: still counted, never named */

  var vp = (window.innerWidth < 700) ? "phone" : "desktop";

  function ping(event, props) {
    try {
      var body = JSON.stringify({ sid: sid, event: event, vp: vp, props: props || {} });
      if (navigator.sendBeacon) {
        navigator.sendBeacon(ENDPOINT, new Blob([body], { type: "application/json" }));
      } else {
        fetch(ENDPOINT, {
          method: "POST", body: body, keepalive: true,
          headers: { "Content-Type": "application/json" }
        }).catch(function () {});
      }
    } catch (e) { /* never the map's problem */ }
  }

  window.AtlasBeacon = {
    ping: ping,
    sid: function () { return sid; },
    vp: function () { return vp; }
  };

  /* Page errors from real browsers land here too — this is how the pinch-release
     freeze was found (87 of these from one phone session, 2026-09-06), a fault
     no visitor would ever have described as an error. */
  window.addEventListener("error", function (e) {
    ping("error", {
      msg: String(e.message || "").slice(0, 160),
      src: String(e.filename || "").split("/").pop() + ":" + (e.lineno || 0)
    });
  });

  /* The arrival, sent before anything else can fail: where this visitor came
     from is the one measure that is gone forever if the page throws first. */
  var from = "direct";
  if (/[?&]live=1/.test(location.search)) from = "live";
  else if (/[?&]tour=/.test(location.search)) from = "tour";
  else if (location.hash.length > 1) from = "hash";
  var ref = "";
  try { ref = document.referrer ? new URL(document.referrer).hostname : ""; } catch (e) { ref = ""; }
  ping("arrive", {
    from: from, hash: location.hash.slice(1, 40), ref: ref,
    w: window.innerWidth, h: window.innerHeight
  });
})();
