#!/usr/bin/env bash
# Cross-repo sync guard. Reports, for every known repo: current branch, whether
# an upstream is set, how many commits are UNPUSHED (and how old the oldest is),
# and any uncommitted/untracked files. Exits non-zero if ANYTHING is unpushed or
# dirty — so it works both as a session-start check and as a close-out gate.
#
# WHY THIS EXISTS: on 2026-06-06 we found 32 bot commits (incl. a 62 MB video
# from 4 days earlier) stranded unpushed on one machine. The bot commits to a
# classifier-gated `main`, so pushes need manual approval and silently pile up.
# This guard surfaces that backlog every session instead of discovering it by
# accident during a slow push.
set -uo pipefail

REPOS=(
  "/c/xampp/htdocs/od9"
  "/c/Users/Rage/IdeaProjects/OD9-Discord-Bot"
)

problems=0
now=$(date +%s)

for repo in "${REPOS[@]}"; do
  [ -d "$repo/.git" ] || { echo "!! $repo — not a git repo (skipped)"; continue; }
  cd "$repo" || continue
  branch=$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
  echo "==== $repo  [$branch] ===="

  if git rev-parse --abbrev-ref --symbolic-full-name @{u} >/dev/null 2>&1; then
    ahead=$(git rev-list --count @{u}..HEAD 2>/dev/null || echo 0)
    if [ "${ahead:-0}" -gt 0 ]; then
      oldest_ts=$(git log @{u}..HEAD --reverse --format=%ct 2>/dev/null | head -1)
      age_h="?"
      [ -n "${oldest_ts:-}" ] && age_h=$(( (now - oldest_ts) / 3600 ))
      echo "  ✗ UNPUSHED: $ahead commit(s); oldest is ${age_h}h old"
      problems=$((problems+1))
    else
      echo "  ✓ pushed (0 ahead)"
    fi
  else
    echo "  ✗ NO UPSTREAM set for '$branch' — work here cannot be pushed/tracked"
    problems=$((problems+1))
  fi

  dirty=$(git status --porcelain 2>/dev/null)
  if [ -n "$dirty" ]; then
    n=$(printf '%s\n' "$dirty" | grep -c .)
    echo "  ! $n uncommitted/untracked path(s):"
    printf '%s\n' "$dirty" | sed 's/^/      /' | head -15
    problems=$((problems+1))
  else
    echo "  ✓ working tree clean"
  fi
  echo
done

if [ "$problems" -gt 0 ]; then
  echo ">>> $problems issue group(s). Flush before moving on (commit + push)."
  exit 1
fi
echo ">>> all repos clean + pushed."
