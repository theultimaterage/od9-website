/* od9-errors.js — every page reports its own faults (2026-09-08).
 *
 * Loaded from includes/head.php, so it covers the whole site rather than the
 * one page somebody remembered to instrument. The Atlas has had this since
 * 2026-09-05 and it paid for itself in a day: 87 identical TypeErrors from one
 * phone session exposed a pinch gesture that froze the map — a fault a visitor
 * would have experienced as a stutter and never reported.
 *
 * Three rules keep it from becoming the problem it reports on:
 *   - CAPPED. At most 5 reports per page load. The pinch bug fired 87 times in
 *     one session; a beacon that faithfully relays a loop is a denial of
 *     service you built yourself.
 *   - DEDUPED. The same message from the same line is sent once.
 *   - SILENT. Every failure is swallowed. A page must never break because its
 *     error reporter did, which would be the funniest possible bug.
 */
(function () {
  "use strict";
  /* The endpoint comes from the script tag, not a hardcoded absolute path.
     includes/head.php already computes a base path because this site is served
     from the domain root in production and from /od9/ locally — a leading-slash
     URL is correct on prod and silently 404s everywhere else, which is the
     worst kind of wrong: it tests clean locally by never being exercised.
     Read by id rather than document.currentScript, which is not dependable for
     a DEFERRED script — and this must stay deferred, or every page on the site
     would block on an error reporter that usually has nothing to say. */
  var tag = document.getElementById("od9-err");
  var ENDPOINT = (tag && tag.getAttribute("data-endpoint")) || "/api/v1/error-beacon.php";

  var MAX = 5;
  var sent = 0, seen = {};

  var sid = "";
  try {
    sid = sessionStorage.getItem("od9.sid") || "";
    if (!sid) {
      sid = Math.random().toString(36).slice(2, 10) + Math.random().toString(36).slice(2, 6);
      sessionStorage.setItem("od9.sid", sid);
    }
  } catch (e) { sid = "anon"; }

  var vp = (window.innerWidth < 700) ? "phone" : "desktop";

  function report(kind, msg, src, line, col) {
    try {
      if (sent >= MAX) return;
      msg = String(msg || "").slice(0, 300);
      if (!msg) return;
      var key = kind + "|" + msg + "|" + (line || 0);
      if (seen[key]) return;
      seen[key] = 1;
      sent++;
      var body = JSON.stringify({
        sid: sid, vp: vp, kind: kind, msg: msg,
        /* the path only — a query string can carry identifiers, and the page is
           what makes an error actionable, not who was looking at it */
        page: location.pathname.slice(0, 160),
        src: String(src || "").split("/").pop().slice(0, 160),
        line: line || 0, col: col || 0
      });
      if (navigator.sendBeacon) {
        navigator.sendBeacon(ENDPOINT, new Blob([body], { type: "application/json" }));
      } else {
        fetch(ENDPOINT, { method: "POST", body: body, keepalive: true,
                          headers: { "Content-Type": "application/json" } }).catch(function () {});
      }
    } catch (e) { /* never the page's problem */ }
  }

  window.addEventListener("error", function (e) {
    report("error", e.message, e.filename, e.lineno, e.colno);
  });
  window.addEventListener("unhandledrejection", function (e) {
    var r = e && e.reason;
    report("rejection", (r && (r.message || r)) || "unhandled rejection",
           (r && r.fileName) || "", (r && r.lineNumber) || 0, 0);
  });
})();
