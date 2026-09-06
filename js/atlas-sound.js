/* The Atlas — sound layer (the Quartermaster's supplies along the way).
 *
 * Opt in only: nothing is created until the visitor presses the sound
 * button (browsers require a gesture; so does courtesy). Two sources, one
 * contract:
 *   audio/atlas/bed.mp3            the ambient bed (Flow Music, looped with
 *                                  a crossfade so the seam never shows)
 *   audio/atlas/cue-<family>.mp3   one signature per object family, played
 *                                  when a chapter resolves under the camera
 *   audio/atlas/arrival.mp3        the fly-in swell / audio/atlas/reveal.mp3
 *                                  the pull-back chord (the load sequence)
 * Every file is optional. A missing file falls back to a Web Audio synth of
 * the same gesture, so the layer works the day it ships and gets better the
 * day the Flow Music takes land (docs/FLOW-PROMPTS-2026-08.md, Lane 8).
 *
 * Public surface, called by atlas.js:
 *   AtlasSound.cue(family)   AtlasSound.arrival()   AtlasSound.reveal()
 *   AtlasSound.enabled()     AtlasSound.toggle()
 */
(function () {
  "use strict";
  var KEY = "atlas.sound";
  var BASE = "audio/atlas/";
  var BED_GAIN = 0.16, CUE_GAIN = 0.28, DUCK = 0.55;
  var ctx = null, master = null, bedGain = null, bedNodes = [], bedTimer = null;
  var buffers = {}, missing = {}, on = false, started = false;
  /* The page carries the list of files that exist (atlas.php globs audio/atlas/
     at render time: data-audio + data-audio-v on the stage), so a family with
     no take yet costs nothing — no fetch, no 404. Not a manifest.json: this
     site's Cloudflare answers browser fetches of *.json with its block page. */
  var stage = document.getElementById("atlas-stage");
  var VERS = {};                              /* "bed.mp3" -> its own mtime version */
  ((stage && stage.getAttribute("data-audio")) || "").split(",").filter(Boolean).forEach(function (e) {
    var i = e.indexOf(":"); VERS[i === -1 ? e : e.slice(0, i)] = i === -1 ? "1" : e.slice(i + 1);
  });
  var FILES = Object.keys(VERS);
  function haveFile(name) { return Promise.resolve(FILES.indexOf(name + ".mp3") !== -1); }
  /* arrival.mp3, arrival-b.mp3, arrival-c.mp3 … are takes of the same
     gesture; each play picks one at random (the founder: "use both"). */
  function variant(base) {
    var opts = FILES.filter(function (f) { return f === base + ".mp3" || /^(.+)-[a-z]\.mp3$/.test(f) && f.slice(0, base.length + 1) === base + "-"; })
      .map(function (f) { return f.replace(/\.mp3$/, ""); });
    return opts.length ? opts[Math.floor(Math.random() * opts.length)] : base;
  }
  var btn = document.getElementById("atlas-sound");

  /* Object family for a sprite key + state: the signature each one plays. */
  var FAMILY = {
    "black-hole-star": "blackhole", "horizon-star": "blackhole",
    "pulsar": "pulsar", "neutron-star": "pulsar", "cepheid": "pulsar",
    "quasar": "quasar", "einstein-ring": "quasar", "laser-guide-star": "quasar",
    "gold-star": "canon", "protostar": "raw", "ignition": "forge",
    "stellar-nursery": "nebula", "planetary-nebula": "nebula", "cosmic-web": "nebula",
    "pleiades": "cluster", "globular-cluster": "cluster", "dissolving-cluster": "cluster",
    "probe": "probe", "telescope": "probe", "telescope-array": "probe"
  };
  function familyFor(key, state) {
    if (state === "beyond") return "beyond";
    if (key && FAMILY[key]) return FAMILY[key];
    if (state === "canon") return "canon";
    if (state === "forge") return "forge";
    return "star";
  }

  function ensure() {
    if (ctx) return ctx;
    var AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return null;
    ctx = new AC();
    master = ctx.createGain(); master.gain.value = 1; master.connect(ctx.destination);
    bedGain = ctx.createGain(); bedGain.gain.value = 0; bedGain.connect(master);
    return ctx;
  }

  /* ---- files (optional) ------------------------------------------------ */
  function load(name) {
    if (buffers[name]) return Promise.resolve(buffers[name]);
    if (missing[name]) return Promise.resolve(null);
    return haveFile(name).then(function (ok) {
      if (!ok) { missing[name] = true; return null; }
      return fetch(BASE + name + ".mp3?v=" + (VERS[name + ".mp3"] || "1"), { cache: "force-cache" });
    }).then(function (r) {
      if (r === null) return null;
      if (!r.ok) throw new Error(String(r.status));
      return r.arrayBuffer();
    }).then(function (ab) {
      return ab === null ? null : ctx.decodeAudioData(ab);
    }).then(function (buf) { if (buf) buffers[name] = buf; return buf; })
      .catch(function () { missing[name] = true; return null; });
  }

  /* ---- the bed --------------------------------------------------------- */
  function stopBed() {
    if (bedTimer) { clearTimeout(bedTimer); bedTimer = null; }
    bedNodes.forEach(function (n) { try { n.stop ? n.stop() : n.disconnect(); } catch (e) { /* already gone */ } });
    bedNodes = [];
  }
  function playBedFile(buf) {
    /* crossfaded loop: each pass starts FADE seconds before the previous
       one ends, both ramping, so the seam is never audible */
    var FADE = 2.5, len = buf.duration;
    function pass() {
      if (!on) return;
      var src = ctx.createBufferSource(); src.buffer = buf;
      var g = ctx.createGain(); g.gain.setValueAtTime(0, ctx.currentTime);
      g.gain.linearRampToValueAtTime(1, ctx.currentTime + FADE);
      g.gain.setValueAtTime(1, ctx.currentTime + len - FADE);
      g.gain.linearRampToValueAtTime(0, ctx.currentTime + len);
      src.connect(g); g.connect(bedGain); src.start();
      bedNodes.push(src);
      src.onended = function () { var i = bedNodes.indexOf(src); if (i >= 0) bedNodes.splice(i, 1); };
      bedTimer = setTimeout(pass, (len - FADE) * 1000);
    }
    pass();
  }
  function playBedSynth() {
    /* a deep-space drone: two detuned sines an octave apart under a slowly
       breathing low-pass on pink-ish noise — the sketch the Flow bed replaces */
    var o1 = ctx.createOscillator(), o2 = ctx.createOscillator(), o3 = ctx.createOscillator();
    o1.type = "sine"; o1.frequency.value = 55; o2.type = "sine"; o2.frequency.value = 82.5; o2.detune.value = 6;
    o3.type = "triangle"; o3.frequency.value = 110; o3.detune.value = -4;
    var og = ctx.createGain(); og.gain.value = 0.35;
    o1.connect(og); o2.connect(og); o3.connect(og);
    var lp = ctx.createBiquadFilter(); lp.type = "lowpass"; lp.frequency.value = 180; lp.Q.value = 0.7;
    og.connect(lp); lp.connect(bedGain);
    var noise = ctx.createBufferSource(); noise.buffer = noiseBuffer(); noise.loop = true;
    var nf = ctx.createBiquadFilter(); nf.type = "lowpass"; nf.frequency.value = 420; nf.Q.value = 0.5;
    var ng = ctx.createGain(); ng.gain.value = 0.05;
    noise.connect(nf); nf.connect(ng); ng.connect(bedGain);
    var lfo = ctx.createOscillator(); lfo.frequency.value = 0.045;
    var lg = ctx.createGain(); lg.gain.value = 220; lfo.connect(lg); lg.connect(nf.frequency);
    [o1, o2, o3, noise, lfo].forEach(function (n) { n.start(); bedNodes.push(n); });
  }
  var _noise = null;
  function noiseBuffer() {
    if (_noise) return _noise;
    var len = ctx.sampleRate * 2, buf = ctx.createBuffer(1, len, ctx.sampleRate), d = buf.getChannelData(0);
    var b0 = 0, b1 = 0, b2 = 0;
    for (var i = 0; i < len; i++) {           /* cheap pink: three one-pole stages */
      var w = Math.random() * 2 - 1;
      b0 = 0.997 * b0 + 0.029591 * w; b1 = 0.985 * b1 + 0.032534 * w; b2 = 0.95 * b2 + 0.048056 * w;
      d[i] = (b0 + b1 + b2 + w * 0.05) * 0.6;
    }
    _noise = buf; return buf;
  }
  function startBed() {
    stopBed();
    load("bed").then(function (buf) {
      if (!on) return;
      if (buf) playBedFile(buf); else playBedSynth();
      bedGain.gain.cancelScheduledValues(ctx.currentTime);
      bedGain.gain.setValueAtTime(0, ctx.currentTime);
      bedGain.gain.linearRampToValueAtTime(BED_GAIN, ctx.currentTime + 3);
    });
  }

  /* ---- cues ------------------------------------------------------------ */
  function env(g, t0, a, hold, r, peak) {
    g.gain.setValueAtTime(0.0001, t0);
    g.gain.exponentialRampToValueAtTime(peak, t0 + a);
    g.gain.setValueAtTime(peak, t0 + a + hold);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + a + hold + r);
  }
  function tone(type, freq, t0, a, hold, r, peak, detune, dest) {
    var o = ctx.createOscillator(), g = ctx.createGain();
    o.type = type; o.frequency.value = freq; if (detune) o.detune.value = detune;
    env(g, t0, a, hold, r, peak);
    o.connect(g); g.connect(dest || master);
    o.start(t0); o.stop(t0 + a + hold + r + 0.05);
  }
  function noiseBurst(t0, a, hold, r, peak, lpHz, dest) {
    var s = ctx.createBufferSource(); s.buffer = noiseBuffer();
    var f = ctx.createBiquadFilter(); f.type = "lowpass"; f.frequency.value = lpHz || 800;
    var g = ctx.createGain(); env(g, t0, a, hold, r, peak);
    s.connect(f); f.connect(g); g.connect(dest || master);
    s.start(t0); s.stop(t0 + a + hold + r + 0.05);
  }
  var SYNTH = {
    blackhole: function (t) {                        /* a low hum that leans in */
      tone("sine", 41, t, 0.6, 1.2, 1.4, 0.55); tone("sine", 82, t, 0.8, 1.0, 1.2, 0.18, 5);
      noiseBurst(t, 1.2, 0.6, 1.4, 0.06, 160);
    },
    pulsar: function (t) {                           /* eight ticks, the lighthouse */
      for (var i = 0; i < 8; i++) { tone("square", 1760, t + i * 0.135, 0.005, 0.02, 0.06, 0.09); noiseBurst(t + i * 0.135, 0.004, 0.01, 0.05, 0.05, 6000); }
    },
    quasar: function (t) {                           /* a shimmer that climbs */
      var o = ctx.createOscillator(), o2 = ctx.createOscillator(), g = ctx.createGain();
      o.type = "sine"; o2.type = "sine"; o.frequency.setValueAtTime(880, t); o.frequency.exponentialRampToValueAtTime(1760, t + 1.4);
      o2.frequency.setValueAtTime(884, t); o2.frequency.exponentialRampToValueAtTime(1768, t + 1.4);
      env(g, t, 0.15, 0.6, 0.9, 0.16); o.connect(g); o2.connect(g); g.connect(master);
      o.start(t); o2.start(t); o.stop(t + 1.8); o2.stop(t + 1.8);
    },
    canon: function (t) {                            /* a gold bell: the chapter you can read */
      [523.25, 659.25, 783.99, 1046.5].forEach(function (f, i) { tone("sine", f, t + i * 0.02, 0.01, 0.1, 1.9 - i * 0.2, 0.17 - i * 0.03); });
    },
    raw: function (t) { tone("triangle", 220, t, 0.25, 0.3, 0.8, 0.14); tone("sine", 330, t, 0.3, 0.2, 0.7, 0.06); },
    forge: function (t) {                            /* the crackle of work being done */
      tone("sawtooth", 110, t, 0.05, 0.5, 0.6, 0.12);
      for (var i = 0; i < 6; i++) noiseBurst(t + 0.05 + i * 0.16 + Math.random() * 0.05, 0.004, 0.02, 0.08, 0.12, 2400);
    },
    nebula: function (t) { tone("sine", 165, t, 0.9, 0.5, 1.6, 0.14); tone("sine", 247.5, t, 1.1, 0.4, 1.4, 0.08, 4); },
    cluster: function (t) { [660, 880, 990, 1320, 1485].forEach(function (f, i) { tone("sine", f, t + i * 0.09, 0.01, 0.05, 0.7, 0.08); }); },
    probe: function (t) { tone("sine", 1320, t, 0.005, 0.05, 0.25, 0.1); tone("sine", 1980, t + 0.18, 0.005, 0.05, 0.3, 0.07); },
    beyond: function (t) {                           /* detuned, uneasy, not of this catalogue */
      tone("sine", 330, t, 0.4, 0.8, 1.2, 0.12); tone("sine", 333.5, t, 0.4, 0.8, 1.2, 0.12); tone("triangle", 82, t, 0.6, 0.6, 1.0, 0.08);
    },
    star: function (t) { tone("sine", 660, t, 0.01, 0.08, 0.6, 0.12); },
    arrival: function (t) {                          /* the fly-in: a swell that rises for ~2.4 s */
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.type = "sine"; o.frequency.setValueAtTime(110, t); o.frequency.exponentialRampToValueAtTime(220, t + 2.4);
      env(g, t, 2.0, 0.4, 1.0, 0.2); o.connect(g); g.connect(master); o.start(t); o.stop(t + 3.6);
      var s = ctx.createBufferSource(); s.buffer = noiseBuffer(); s.loop = true;
      var f = ctx.createBiquadFilter(); f.type = "bandpass"; f.Q.value = 0.8;
      f.frequency.setValueAtTime(200, t); f.frequency.exponentialRampToValueAtTime(2400, t + 2.4);
      var ng = ctx.createGain(); env(ng, t, 2.2, 0.2, 0.8, 0.09);
      s.connect(f); f.connect(ng); ng.connect(master); s.start(t); s.stop(t + 3.4);
    },
    reveal: function (t) {                           /* the pull-back: a wide chord that settles */
      [130.81, 196, 261.63, 392, 523.25].forEach(function (f, i) { tone("sine", f, t + i * 0.03, 0.4, 1.2, 2.4, 0.16 - i * 0.02); });
    }
  };
  function duck(seconds) {
    if (!bedGain) return;
    var t = ctx.currentTime;
    bedGain.gain.cancelScheduledValues(t);
    bedGain.gain.setValueAtTime(bedGain.gain.value, t);
    bedGain.gain.linearRampToValueAtTime(BED_GAIN * DUCK, t + 0.15);
    bedGain.gain.linearRampToValueAtTime(BED_GAIN, t + seconds);
  }
  function play(name, synthKey, seconds) {
    if (!on || !ctx) return;
    duck(seconds || 2);
    load(variant(name)).then(function (buf) {
      if (!on) return;
      var t = ctx.currentTime + 0.02;
      if (buf) {
        var src = ctx.createBufferSource(); src.buffer = buf;
        var g = ctx.createGain(); g.gain.value = CUE_GAIN; src.connect(g); g.connect(master); src.start(t);
      } else if (SYNTH[synthKey]) {
        SYNTH[synthKey](t);
      }
    });
  }

  /* ---- switch ---------------------------------------------------------- */
  function paint() {
    if (!btn) return;
    btn.setAttribute("aria-pressed", on ? "true" : "false");
    btn.textContent = on ? "♪" : "♪";
    btn.title = on ? "Sound on — click to mute" : "Sound off — click for the ambient bed and object tones";
    btn.classList.toggle("on", on);
  }
  function start() {
    if (!ensure()) return false;
    if (ctx.state === "suspended") ctx.resume();
    on = true; started = true; paint(); startBed();
    try { localStorage.setItem(KEY, "on"); } catch (e) { /* private mode */ }
    return true;
  }
  function stop() {
    on = false; paint();
    try { localStorage.setItem(KEY, "off"); } catch (e) { /* private mode */ }
    if (bedGain) { bedGain.gain.cancelScheduledValues(ctx.currentTime); bedGain.gain.linearRampToValueAtTime(0, ctx.currentTime + 0.6); }
    setTimeout(stopBed, 700);
  }
  function toggle() {
    var r = on ? (stop(), false) : start();
    /* one sender for the whole map (js/atlas-beacon.js) — this used to hand-roll
       the same POST, which meant two places to change the endpoint or the shape */
    if (window.AtlasBeacon) window.AtlasBeacon.ping("sound", { on: !!r, files: FILES.length });
    return r;
  }
  if (btn) btn.addEventListener("click", toggle);

  /* A visitor who left it on last time gets it back on their first gesture —
     the button reads "armed" until then, because the browser will not let a
     page make a sound before the visitor touches it. */
  var pref = null;
  try { pref = localStorage.getItem(KEY); } catch (e) { /* private mode */ }
  if (pref === "on" && btn) {
    btn.classList.add("armed");
    var arm = function () {
      document.removeEventListener("pointerdown", arm); document.removeEventListener("keydown", arm);
      btn.classList.remove("armed");
      if (!started) start();
    };
    document.addEventListener("pointerdown", arm); document.addEventListener("keydown", arm);
  }
  paint();

  window.AtlasSound = {
    enabled: function () { return on; },
    toggle: toggle,
    cue: function (spriteKey, state) { var fam = familyFor(spriteKey, state); play("cue-" + fam, fam, 2.2); },
    arrival: function () { play("arrival", "arrival", 3.4); },
    reveal: function () { play("reveal", "reveal", 3.2); }
  };
})();
