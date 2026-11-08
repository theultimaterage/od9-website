#!/usr/bin/env python3
"""Prove the PHP and Python chapter-macro resolvers agree byte for byte.

Two implementations of one grammar (includes/chapter_macros.php at render time,
tools/chapter_macros.py in the gates) drift the day one of them is edited alone. This
runs a fixture that exercises every macro kind and every num shape, plus every mail
template and every lesson page's eyebrow and Source line, through both, and refuses on
the first byte that differs. php must be on PATH (it is on the workstation and under
WSL, where the deploy gates run); without it the verdict is UNCHECKED (exit 2), never a
pass.

    python tools/chapter_macros_parity.py            # 0 agree, 1 differ, 2 cannot tell
    python tools/chapter_macros_parity.py --selftest # the comparison can fail: a planted
                                                     # difference is caught

Provers: --selftest; this tool runs in prod-deploy's drip references gate
(tools/drip_refs_check.py --check calls it).
"""
from __future__ import annotations

import glob
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile

if hasattr(sys.stdout, "reconfigure"):  # not when a selftest has redirected stdout to a StringIO
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, HERE)
import chapter_macros as cm  # noqa: E402

RENDER_PHP = os.path.join(HERE, "chapter_macros_render.php")
FIXTURE = (
    "[**{{ch:ch4}}**](u) {{chapter:ch4}} {{chapter_nbsp:ch4}} <em>{{chtitle:ch4}}</em>\n"
    "{{ch:ch22}} {{chtitle:ch22}} {{ch:appendix-a}} {{chapter:appendix-a}} {{chapter_nbsp:appendix-a}} "
    "{{chtitle:appendix-a}} {{chapter:preface}} {{chtitle:preface}} {{ch:v3-space}} {{chtitle:v3-mind}}\n"
    "{{CH:ch4}} {{ch:ch 4}} {{bogus:ch4}} Ch.4 stays\n"
)
LESSON_FIELD = re.compile(r'"(?:eyebrow|source)"\s*=>\s*"((?:[^"\\]|\\.)*)"')


def php_exe() -> str | None:
    return shutil.which("php")


def php_resolve(text: str, registry: str | None, php: str) -> tuple[int, str, str]:
    with tempfile.NamedTemporaryFile("w", encoding="utf-8", newline="", suffix=".txt", delete=False) as fh:
        fh.write(text)
        path = fh.name
    try:
        argv = [php, RENDER_PHP, path] + ([registry] if registry else [])
        # bytes, decoded by hand: text mode would turn a CRLF template's "\r\n" into "\n"
        # on the PHP side only and report a difference the resolvers do not have
        p = subprocess.run(argv, capture_output=True, timeout=60)
        return p.returncode, p.stdout.decode("utf-8", "replace"), p.stderr.decode("utf-8", "replace")
    finally:
        os.unlink(path)


def corpus() -> list[tuple[str, str]]:
    """(label, text) for every input the resolvers meet in production."""
    out = [("fixture", FIXTURE)]
    for pat in ("email-templates/*.html", "email-templates/lovelogic-weekly/email-week-*.md"):
        for p in sorted(glob.glob(os.path.join(ROOT, pat))):
            with open(p, encoding="utf-8", newline="") as fh:
                out.append((os.path.relpath(p, ROOT).replace("\\", "/"), fh.read()))
    for p in sorted(glob.glob(os.path.join(ROOT, "curriculum", "*", "*.php"))):
        if os.path.basename(p).startswith("_"):
            continue
        with open(p, encoding="utf-8", errors="replace") as fh:
            fields = LESSON_FIELD.findall(fh.read())
        if fields:
            out.append((os.path.relpath(p, ROOT).replace("\\", "/") + " (eyebrow/source)", "\n".join(fields)))
    return out


def compare(items: list[tuple[str, str]], registry_path: str | None, reg: dict, php: str) -> list[str]:
    problems = []
    for label, text in items:
        py_out, _ = cm.resolve(text, reg)
        rc, php_out, err = php_resolve(text, registry_path, php)
        if rc == 2:
            problems.append(f"{label}: php could not run: {err.strip()}")
            continue
        if php_out != py_out:
            i = next((k for k, (a, b) in enumerate(zip(php_out, py_out)) if a != b), min(len(php_out), len(py_out)))
            problems.append(f"{label}: differ at byte {i}: php {php_out[i:i+40]!r} vs py {py_out[i:i+40]!r}")
    return problems


def run() -> int:
    php = php_exe()
    if not php:
        print("[macro-parity] UNCHECKED: php is not on PATH; the two resolvers were not compared")
        return 2
    reg = cm.load_registry()
    items = corpus()
    # vacuity: the fixture must change under resolution and carry every kind
    fx = cm.resolve(FIXTURE, reg)[0]
    kinds = {k for k, _ in cm.find(FIXTURE)}
    if fx == FIXTURE or kinds != set(cm.KINDS):
        print("[macro-parity] REFUSING: the fixture does not exercise every macro kind")
        return 1
    problems = compare(items, None, reg, php)
    macros = sum(len(cm.find(t)) for _, t in items)
    if problems:
        print(f"[macro-parity] DIFFER: {len(problems)} input(s) resolve differently in PHP and Python:")
        for p in problems:
            print("  - " + p)
        return 1
    print(f"[macro-parity] agree: {len(items)} input(s), {macros} macro(s), PHP == Python byte for byte")
    return 0


def selftest() -> int:
    php = php_exe()
    if not php:
        print("SELFTEST UNCHECKED: php is not on PATH")
        return 2
    fails = []
    tmp = tempfile.mkdtemp(prefix="macro_parity_")
    try:
        reg = {"ch4": {"id": "ch4", "num": "3", "title": "The Great Awakening"},
               "appendix-a": {"id": "appendix-a", "num": "A", "title": "Appendix A — Methods & the Evidence Standard"},
               "preface": {"id": "preface", "num": "P", "title": "The Paradox of Transformation"},
               "ch22": {"id": "ch22", "num": "19", "title": "Religious Institutions & Pseudoscience"},
               "v3-space": {"id": "v3-space", "num": "23", "title": "The Space Frontier"},
               "v3-mind": {"id": "v3-mind", "num": "20", "title": "Engineering the Mind"}}
        rp = os.path.join(tmp, "reg.json")
        with open(rp, "w", encoding="utf-8") as fh:
            json.dump({"nodes": list(reg.values())}, fh)
        if compare([("fixture", FIXTURE)], rp, reg, php):
            fails.append("the two resolvers disagree on the fixture: " + "; ".join(compare([("fixture", FIXTURE)], rp, reg, php)))
        # a planted difference is caught: the Python side sees a registry where ch4 is Chapter 99
        bent = dict(reg)
        bent["ch4"] = {"id": "ch4", "num": "99", "title": "The Great Awakening"}
        if not compare([("fixture", FIXTURE)], rp, bent, php):
            fails.append("VACUITY: a registry that disagrees between the two sides was not caught")
        # an unknown id: both leave it as written, php exits 1 and names it
        rc, out, err = php_resolve("{{ch:ch99}} {{ch:ch4}}", rp, php)
        if rc != 1 or out != "{{ch:ch99}} Ch.3" or "ch99" not in err:
            fails.append(f"php did not leave the unknown id as written and exit 1: rc={rc} out={out!r} err={err.strip()!r}")
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    if fails:
        print("SELFTEST FAIL:")
        for f in fails:
            print("  - " + f)
        return 1
    print("SELFTEST OK: 3 checks; the resolvers agree on the fixture, a planted disagreement is caught, "
          "an unknown id is left as written and reported by PHP.")
    return 0


if __name__ == "__main__":
    raise SystemExit(selftest() if "--selftest" in sys.argv[1:] else run())
