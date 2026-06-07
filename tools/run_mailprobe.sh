#!/usr/bin/env bash
# Definitive prod check: does offda9.com's outbound SMTP actually deliver via
# Brevo? Runs the diagnostic as the offda9 user against the live prod mail.php,
# prints the driver + boolean result, and dumps any [od9_send_mail] SMTP error.
# Self-cleans. Safe to re-run.
set -euo pipefail

KEY="$HOME/.ssh/freshthaband_key"
HOST="ultimaterage@72.167.54.213"
TO="${1:-theultimaterage+brevoprod@gmail.com}"

scp -i "$KEY" /c/xampp/htdocs/od9/tools/_mailprobe.php "$HOST:/tmp/_mailprobe.php"
ssh -i "$KEY" "$HOST" "
  PHPBIN=\$(command -v php || echo /usr/local/bin/php)
  echo \"php=\$PHPBIN\"
  sudo -u offda9 \$PHPBIN /tmp/_mailprobe.php '$TO'
  echo '---- error log ----'
  cat /tmp/od9_mailprobe_err.log 2>/dev/null || echo '(no error log written)'
  rm -f /tmp/_mailprobe.php /tmp/od9_mailprobe_err.log
"
