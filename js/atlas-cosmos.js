/* atlas-cosmos.js — the sky the map is drawn on (split out of atlas.js 2026-09-06).
 *
 * Founder direction, 2026-08-21: constellation vibes — stars, not dots;
 * nebulae, not boxes. Everything here is procedural and SEEDED, so the sky is
 * identical on every visit: the layout is the map's identity, and an identity
 * that reshuffles is not one. The one exception is ambient motion (a meteor
 * every 15-40 seconds), which is ephemeral and allowed to be random.
 *
 * This is the map's only layer with no knowledge of chapters, focus, or the
 * camera's meaning — it is given a drawing context and a viewport and paints
 * the background. That is why it separates cleanly while the tour, the
 * timeline and the card do not: they all read and write the same focus.
 *
 * window.AtlasCosmos.init({ ctx, view, W, H, C, rgba, volumes, platesVersion,
 *                           reducedMotion, extraPlates })
 * extraPlates: [{key, file}] for regions that are not volumes — the beyond
 * field is the only one, and it is a region without being a volume.
 *   -> { field, nebulae, plates, starGlow, flare, meteor }
 * `view` and the returned `plates` are LIVE references: the viewport is mutated
 * on resize, and plates arrive asynchronously as their images load.
 */
(function () {
  "use strict";

  function mulberry32(a) {
    return function () {
      a |= 0; a = a + 0x6D2B79F5 | 0;
      var t = Math.imul(a ^ a >>> 15, 1 | a);
      t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }

  /* ONE COSMOS (2026-09-04): a plate's edges fade to nothing over the outer
     ~14% of each side, once, at load — so at the corpus view the six volumes
     read as one field of nebulae, not six framed tiles (the first frame's
     weakest habit, and the frame the Short sends people to). */
  function featherPlate(img) {
    try {
      var c = document.createElement("canvas");
      c.width = img.naturalWidth || img.width; c.height = img.naturalHeight || img.height;
      var x = c.getContext("2d");
      x.drawImage(img, 0, 0);
      var fx = 0.14, fy = 0.14;
      x.globalCompositeOperation = "destination-in";
      var gh = x.createLinearGradient(0, 0, c.width, 0);
      gh.addColorStop(0, "rgba(0,0,0,0)"); gh.addColorStop(fx, "rgba(0,0,0,1)");
      gh.addColorStop(1 - fx, "rgba(0,0,0,1)"); gh.addColorStop(1, "rgba(0,0,0,0)");
      x.fillStyle = gh; x.fillRect(0, 0, c.width, c.height);
      var gv = x.createLinearGradient(0, 0, 0, c.height);
      gv.addColorStop(0, "rgba(0,0,0,0)"); gv.addColorStop(fy, "rgba(0,0,0,1)");
      gv.addColorStop(1 - fy, "rgba(0,0,0,1)"); gv.addColorStop(1, "rgba(0,0,0,0)");
      x.fillStyle = gv; x.fillRect(0, 0, c.width, c.height);
      return c;
    } catch (e) { return img; }               /* a tainted canvas still draws the plate */
  }

  function init(o) {
    var ctx = o.ctx, view = o.view, C = o.C, rgba = o.rgba;
    var W = o.W, H = o.H, volumes = o.volumes || [];
    var reducedMotion = !!o.reducedMotion;

    var FIELD = [];
    (function seedField() {
      var rnd = mulberry32(3369); /* OD9's sky, same every visit */
      var layers = [
        { n: 130, p: 0.18, rMax: 1.1, aMax: 0.5 },
        { n: 90, p: 0.42, rMax: 1.5, aMax: 0.7 },
        { n: 45, p: 0.75, rMax: 2.1, aMax: 0.95 }
      ];
      layers.forEach(function (L) {
        for (var i = 0; i < L.n; i++) {
          var tint = rnd();
          FIELD.push({
            x: rnd() * (W + 800) - 400, y: rnd() * (H + 800) - 400,
            p: L.p, r: 0.4 + rnd() * L.rMax, a: 0.12 + rnd() * L.aMax,
            ph: rnd() * 6.28, sp: 0.4 + rnd() * 1.2,
            c: tint < 0.72 ? "#FFFFFF" : (tint < 0.88 ? C.cyan : C.violet)
          });
        }
      });
    })();

    var NEBULAE = [];
    (function seedNebulae() {
      var rnd = mulberry32(909);
      volumes.forEach(function (v) {
        var r = v.region;
        var blobs = 2 + Math.round(rnd());
        for (var i = 0; i < blobs; i++) {
          NEBULAE.push({
            vol: v.vol,
            x: r[0] + r[2] * (0.2 + rnd() * 0.6),
            y: r[1] + r[3] * (0.2 + rnd() * 0.6),
            rad: Math.max(r[2], r[3]) * (0.3 + rnd() * 0.35),
            c: rnd() < 0.7 ? C.violet : C.cyan,
            a: 0.05 + rnd() * 0.05,
            /* ambient-motion phase (2026-08-27): seeded, so each wash breathes
               and drifts on its own slow cycle but identically every visit */
            ph: rnd() * 6.28
          });
        }
      });
    })();

    /* founder-generated nebula plates (Flow, 2026-08-21) — one per volume,
       pre-feathered to alpha by tools/build_atlas_plates.py. Fail-open: until a
       plate loads, that volume keeps its procedural nebulae. */
    var PLATES = {};
    (function loadPlates() {
      var pv = o.platesVersion || "1";
      var keys = { 0: "preface", 1: "vol1", 2: "vol2", 3: "vol3", 4: "vol4", 5: "vol5", 6: "vol6" };
      volumes.forEach(function (v) {
        var img = new Image();
        img.onload = function () { PLATES[v.vol] = featherPlate(img); };
        img.src = "images/atlas/plates/" + keys[v.vol] + ".webp?v=" + pv;
      });
      (o.extraPlates || []).forEach(function (p) {
        var img = new Image();
        img.onload = function () { PLATES[p.key] = featherPlate(img); };
        img.src = "images/atlas/plates/" + p.file + ".webp?v=" + pv;
      });
    })();

    function starGlow(x, y, r, color, coreAlpha) {
      var g = ctx.createRadialGradient(x, y, 0, x, y, r);
      g.addColorStop(0, rgba("#FFFFFF", coreAlpha));
      g.addColorStop(0.25, rgba(color, coreAlpha * 0.85));
      g.addColorStop(1, rgba(color, 0));
      ctx.fillStyle = g;
      ctx.beginPath(); ctx.arc(x, y, r, 0, 7); ctx.fill();
    }
    function flare(x, y, len, color, alpha) {
      ctx.strokeStyle = rgba(color, alpha);
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(x - len, y); ctx.lineTo(x + len, y);
      ctx.moveTo(x, y - len); ctx.lineTo(x, y + len);
      ctx.stroke();
    }

    /* ---- ambient motion: shooting stars (2026-08-27) ------------------------
       The LAYOUT of the sky is deterministic (seeded) — that's the identity.
       Ephemeral events are allowed to be random: a meteor every ~15-40s, one at
       a time, screen-space (atmosphere, not geography), gone in under a second.
       Disabled entirely under prefers-reduced-motion. rAF pausing in hidden tabs
       means no meteor debt accumulates while the tab is backgrounded.
       ?meteorfast — debug cadence so the layer is DETERMINISTICALLY testable
       against the real file (a 0.7s streak every ~25s defeats statistical
       screenshot sampling; an untestable ambient feature would be trusted on
       faith forever). Production cadence is the default. */
    var meteorFast = /[?&]meteorfast/.test(location.search);
    var current = null;
    var nextAt = Date.now() + (meteorFast ? 700 : 6000 + Math.random() * 10000);
    function gap() {
      return meteorFast ? 1500 + Math.random() * 1500
                        : 15000 + Math.random() * 25000;
    }
    function spawn(nowAbs) {
      var edge = Math.random();
      var x0 = view.w * (0.1 + Math.random() * 0.8);
      var y0 = view.h * (edge < 0.5 ? 0.05 + Math.random() * 0.2 : 0.25 + Math.random() * 0.3);
      var ang = (35 + Math.random() * 25) * Math.PI / 180 * (Math.random() < 0.5 ? 1 : -1);
      current = { x: x0, y: y0, dx: Math.cos(ang), dy: Math.abs(Math.sin(ang)),
                  start: nowAbs, life: 650 + Math.random() * 350,
                  speed: 0.55 + Math.random() * 0.35 };
    }
    function meteor(nowAbs) {
      if (reducedMotion) return;
      if (!current) {
        if (nowAbs >= nextAt) spawn(nowAbs);
        return;
      }
      var t = (nowAbs - current.start) / current.life;
      if (t >= 1) {
        current = null;
        nextAt = nowAbs + gap();
        return;
      }
      var dist = current.speed * (nowAbs - current.start);
      var hx = current.x + current.dx * dist, hy = current.y + current.dy * dist;
      var tail = 110 * (1 - t * 0.35);
      var fade = t < 0.15 ? t / 0.15 : (1 - t);
      /* ADDITIVE blending + a glowing head (2026-08-27): the first cut was a
         1.4px source-over gradient line — console probes proved it drew every
         frame while pixel diffs proved nobody could see it: white at 0.85α
         composited over the bright Flow plates is a near-zero delta. 'lighter'
         ADDS light, so the streak reads over the void AND over a gold nebula. */
      ctx.globalCompositeOperation = "lighter";
      var g = ctx.createLinearGradient(hx, hy, hx - current.dx * tail, hy - current.dy * tail);
      g.addColorStop(0, rgba("#FFFFFF", 0.9 * fade));
      g.addColorStop(0.35, rgba(C.cyan, 0.5 * fade));
      g.addColorStop(1, rgba(C.cyan, 0));
      ctx.strokeStyle = g;
      ctx.lineWidth = 2.1;
      ctx.beginPath();
      ctx.moveTo(hx, hy);
      ctx.lineTo(hx - current.dx * tail, hy - current.dy * tail);
      ctx.stroke();
      starGlow(hx, hy, 7, C.cyan, 0.9 * fade);
      ctx.globalCompositeOperation = "source-over";
    }

    return { field: FIELD, nebulae: NEBULAE, plates: PLATES,
             starGlow: starGlow, flare: flare, meteor: meteor };
  }

  window.AtlasCosmos = { init: init };
})();
