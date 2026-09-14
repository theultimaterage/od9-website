#!/usr/bin/env python3
r"""Where the manifesto checkout is, resolved for whichever OS is asking.

WHY THIS EXISTS (2026-09-14). Four tools carried the same line:

    MANIFESTO = Path(os.environ.get("MANIFESTO_DIR",
                                    r"C:\Users\Rage\Documents\The OD9 Manifesto"))

...and the documented way to deploy this site is `wsl python3 tools/prod-deploy.py`.
Under WSL that Windows path does not exist. So on every real deploy:

  * the Forge work-log gate printed "no manifesto checkout ... cannot verify" and
    checked nothing;
  * the chapter-reference gate did the same, while 2,264 sentences saying
    "Chapter N" went unexamined through a 67->52 consolidation;
  * the citation gate printed "CLEAN - nothing published carries a condemned
    citation" with its ratchet SILENTLY SKIPPED, because two worklists were
    unreadable. That is the dangerous one: a gate reporting clean while partly
    blind is worse than one that fails, because it produces confidence.

Each announced its own blindness in one quiet NOTE, which is how it survived:
a gate that says it is blind is still a gate nobody is being stopped by, and
nobody reads a passing deploy's output. They were only caught because a NEW gate
was added directly beneath them and ran for real in the same output, so the
contrast was visible on one screen.

WSL can read the tree and run git against it (both verified 2026-09-14) - the
path was the only thing missing. So translate it rather than teaching four tools
about WSL, or worse, letting the deploy pass MANIFESTO_DIR and leaving the same
tools broken for anyone who runs them by hand.

    python tools/_manifesto_path.py            # print the resolved path
    python tools/_manifesto_path.py --selftest  # prove the translation both ways
"""
from __future__ import annotations

import os
import re
import sys
from pathlib import Path

DEFAULT_WINDOWS = r"C:\Users\Rage\Documents\The OD9 Manifesto"

_WIN = re.compile(r"^([A-Za-z]):[\\/](.*)$")
_WSL = re.compile(r"^/mnt/([a-zA-Z])/(.*)$")


def windows_to_wsl(raw: str) -> str | None:
    r"""C:\Users\x -> /mnt/c/Users/x. None when it is not a Windows path."""
    m = _WIN.match(raw)
    if not m:
        return None
    return "/mnt/{}/{}".format(m.group(1).lower(), m.group(2).replace("\\", "/"))


def wsl_to_windows(raw: str) -> str | None:
    r"""/mnt/c/Users/x -> C:\Users\x. None when it is not a WSL mount path."""
    m = _WSL.match(raw)
    if not m:
        return None
    return "{}:\\{}".format(m.group(1).upper(), m.group(2).replace("/", "\\"))


def candidates(raw: str) -> list[str]:
    """The path as given, then its translation for the other OS."""
    out = [raw]
    for alt in (windows_to_wsl(raw), wsl_to_windows(raw)):
        if alt and alt not in out:
            out.append(alt)
    return out


def manifesto_dir(default: str = DEFAULT_WINDOWS) -> Path:
    """The manifesto checkout. MANIFESTO_DIR wins; either spelling resolves.

    Returns the first candidate that is a real directory. When none is, returns
    the path AS ASKED FOR unchanged, so each tool's own "no manifesto checkout at
    X" note still names what it actually looked for instead of a translation the
    reader has never seen.
    """
    raw = os.environ.get("MANIFESTO_DIR") or default
    for cand in candidates(raw):
        if Path(cand).is_dir():
            return Path(cand)
    return Path(raw)


def _selftest() -> int:
    """Prove the translation in BOTH directions, and prove it can FAIL.

    A resolver that always handed back the path it was given would look correct
    on the machine that already worked - which is exactly the shape of the bug
    this replaces.
    """
    ok = True

    def case(name: str, passed: bool) -> None:
        nonlocal ok
        ok &= passed
        print(f"  {'OK  ' if passed else 'FAIL'} {name}")

    case("windows -> wsl, drive letter lowercased, separators flipped",
         windows_to_wsl(r"C:\Users\Rage\Documents\The OD9 Manifesto")
         == "/mnt/c/Users/Rage/Documents/The OD9 Manifesto")
    case("windows -> wsl accepts forward slashes too",
         windows_to_wsl("C:/Users/Rage/x") == "/mnt/c/Users/Rage/x")
    case("wsl -> windows, drive letter capitalised, separators flipped",
         wsl_to_windows("/mnt/c/Users/Rage/Documents/The OD9 Manifesto")
         == r"C:\Users\Rage\Documents\The OD9 Manifesto")
    case("a plain posix path is not mistaken for a wsl mount",
         wsl_to_windows("/home/rage/manifesto") is None
         and windows_to_wsl("/home/rage/manifesto") is None)
    case("a relative path is left alone by both translators",
         windows_to_wsl("../manifesto") is None and wsl_to_windows("../manifesto") is None)
    case("spaces survive the round trip (the real path has two)",
         wsl_to_windows(windows_to_wsl(DEFAULT_WINDOWS)) == DEFAULT_WINDOWS)
    case("candidates offers the original first, then exactly one translation",
         candidates(DEFAULT_WINDOWS) == [DEFAULT_WINDOWS,
                                         "/mnt/c/Users/Rage/Documents/The OD9 Manifesto"])
    case("a path that needs no translation yields exactly itself",
         candidates("/home/rage/manifesto") == ["/home/rage/manifesto"])

    # The negative half: a path that exists NOWHERE must come back unchanged, so
    # the caller's error message names what the operator actually configured.
    missing = r"D:\no\such\manifesto"
    os.environ["MANIFESTO_DIR"] = missing
    case("an unresolvable path is returned as asked for, not as a translation",
         str(manifesto_dir()) == missing)

    # ...and an explicit override in EITHER spelling must find the real tree,
    # whichever OS is running this.
    real = manifesto_dir.__defaults__[0]
    found = None
    for spelling in candidates(real):
        os.environ["MANIFESTO_DIR"] = spelling
        got = manifesto_dir()
        if got.is_dir():
            found = got
            break
    del os.environ["MANIFESTO_DIR"]
    if found is None:
        print("  SKIP the manifesto is not on this machine; resolution untested")
    else:
        both = []
        for spelling in candidates(real):
            os.environ["MANIFESTO_DIR"] = spelling
            both.append(manifesto_dir().is_dir())
        del os.environ["MANIFESTO_DIR"]
        case("both spellings of the real checkout resolve to a real directory", all(both))

    print("_manifesto_path selftest:", "PASS" if ok else "FAIL")
    return 0 if ok else 1


if __name__ == "__main__":
    if "--selftest" in sys.argv:
        sys.exit(_selftest())
    print(manifesto_dir())
