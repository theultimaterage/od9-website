/* atlas-find.js — chapters, objects, ideas, sections (split out 2026-09-06).
 *
 * "/" focuses it, arrows move, Enter flies. A hit is a whole chapter, or one
 * of its sections — the map's third zoom level is searchable, which is how a
 * reader who half-remembers a phrase gets to the right satellite rather than
 * the right chapter.
 *
 * A leaf: nothing in the map calls into find, and find only reaches out to fly
 * somewhere. That is why it separates cleanly while the tour does not.
 *
 * window.AtlasFind.init({ allNodes, objectInfo, esc, ping, focusNode,
 *                         beforeFly, isReaderOpen })
 */
(function () {
  "use strict";

  function init(o) {
    var input = document.getElementById("atlas-find");
    var list = document.getElementById("atlas-find-results");
    if (!input) return { focus: function () {} };

    var esc = o.esc, ping = o.ping || function () {};
    var objectInfo = o.objectInfo || function () { return {}; };
    var hits = [], idx = -1;

    function indexText(n) {
      var info = objectInfo(n) || {};
      return [n.num, n.title, (n.bullets || []).join(" "), info.name || "", info.kind || "", n.note || ""]
        .join(" ").toLowerCase();
    }

    /* a hit is {n} for a chapter or {n, si} for one of its sections */
    function matches(q) {
      var out = [];
      var m = /^(?:ch|chapter)?\s*(\d+)$/.exec(q);
      o.allNodes().forEach(function (n) {
        if (out.length >= 8) return;
        if (m ? String(n.num) === m[1] : indexText(n).indexOf(q) !== -1) { out.push({ n: n }); return; }
        if (!m && n.sections) {
          for (var i = 0; i < n.sections.length && out.length < 8; i++) {
            if (n.sections[i].toLowerCase().indexOf(q) !== -1) out.push({ n: n, si: i });
          }
        }
      });
      return out;
    }

    function render() {
      if (!list) return;
      list.innerHTML = "";
      hits.forEach(function (hit, i) {
        var n = hit.n;
        var li = document.createElement("li");
        li.setAttribute("role", "option");
        var info = objectInfo(n) || {};
        if (hit.si !== undefined) {
          li.innerHTML = "<b>" + esc(n.num || "P") + "." + (hit.si + 1) + "</b><span>" + esc(n.sections[hit.si]) + "</span>" +
            "<small>" + esc(n.title) + "</small>";
        } else {
          li.innerHTML = "<b>" + esc(n.beyond ? "∞" : (n.num || "P")) + "</b><span>" + esc(n.title) + "</span>" +
            (info.name ? "<small>" + esc(info.name) + "</small>" : "");
        }
        if (i === idx) li.className = "active";
        li.addEventListener("mousedown", function (ev) { ev.preventDefault(); pick(i); });
        list.appendChild(li);
      });
      list.classList.toggle("open", hits.length > 0);
    }

    function pick(i) {
      var hit = hits[i];
      if (!hit) return;
      list.classList.remove("open"); hits = []; idx = -1;
      input.blur();
      if (o.beforeFly) o.beforeFly();
      ping("find", { id: hit.n.id, section: hit.si === undefined ? -1 : hit.si, qlen: (input.value || "").length });
      o.focusNode(hit.n.id, true, hit.si === undefined ? null : hit.si);
    }

    input.addEventListener("input", function () {
      var q = input.value.trim().toLowerCase();
      hits = []; idx = -1;
      if (q.length >= 2) {
        hits = matches(q);
        if (hits.length) idx = 0;
      }
      render();
    });
    input.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown" && hits.length) { idx = (idx + 1) % hits.length; render(); e.preventDefault(); }
      else if (e.key === "ArrowUp" && hits.length) { idx = (idx - 1 + hits.length) % hits.length; render(); e.preventDefault(); }
      else if (e.key === "Enter" && idx >= 0) { pick(idx); e.preventDefault(); }
      else if (e.key === "Escape") { hits = []; render(); input.blur(); }
      e.stopPropagation();                       /* typing never skips the arrival or closes the card */
    });
    input.addEventListener("blur", function () { setTimeout(function () { hits = []; render(); }, 150); });
    document.addEventListener("keydown", function (e) {
      if (e.key === "/" && document.activeElement !== input && !(o.isReaderOpen && o.isReaderOpen())) {
        e.preventDefault(); input.focus();
      }
    });

    return { focus: function () { input.focus(); } };
  }

  window.AtlasFind = { init: init };
})();
