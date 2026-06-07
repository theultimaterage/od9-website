#!/usr/bin/env bash
# Make the weekly email fire at 9 AM CT YEAR-ROUND (DST-proof).
#
# The crontab runs in the server's UTC clock, so "0 14 * * 1" is 9 AM only
# during CDT; after DST ends it drifts to 8 AM CST. Fix: declare
# CRON_TZ=America/Chicago (affects all lines below it) and change the weekly
# line to "0 9 * * 1". No other cron line depends on a UTC wall-clock — only
# overnight maintenance jobs, which simply shift to CT (still fine windows).
# Heartbeats keep logging UTC (date -u), unaffected.
#
# Backs the live crontab up first. Idempotent: re-runs are no-ops.
set -euo pipefail

KEY="$HOME/.ssh/freshthaband_key"
HOST="ultimaterage@72.167.54.213"

# The remote worker (runs in bash on prod; needs process substitution for diff).
TMP="$(mktemp)"
cat > "$TMP" <<'REMOTE'
set -euo pipefail
TS=$(date +%Y%m%d-%H%M%S)
BK="$HOME/crontab-backup-$TS.txt"
crontab -l > "$BK"
echo "backup -> $BK"

cur="$(crontab -l)"
new="$cur"

if printf '%s\n' "$cur" | grep -q '^CRON_TZ='; then
  echo "CRON_TZ already present — leaving env line as is"
else
  new="$(printf 'CRON_TZ=America/Chicago\n%s\n' "$cur")"
  echo "added CRON_TZ=America/Chicago"
fi

# Flip ONLY the weekly_lovelogic line's schedule from 0 14 -> 0 9.
new="$(printf '%s\n' "$new" | sed -E '/weekly_lovelogic_sender\.php/ s/^0 14 \* \* 1 /0 9 * * 1 /')"

echo "=== diff (old -> new) ==="
diff <(printf '%s\n' "$cur") <(printf '%s\n' "$new") || true

printf '%s\n' "$new" | crontab -
echo "=== installed; relevant lines ==="
crontab -l | grep -E '^CRON_TZ=|weekly_lovelogic_sender' || true
REMOTE

scp -i "$KEY" "$TMP" "$HOST:/tmp/od9_cron_patch.sh"
rm -f "$TMP"
ssh -i "$KEY" "$HOST" 'bash /tmp/od9_cron_patch.sh; rm -f /tmp/od9_cron_patch.sh'
echo "CRON-DST-FIX-OK"
