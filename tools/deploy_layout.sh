#!/usr/bin/env bash
# Deploy ONLY the email chrome (includes/email_layout.php) to prod.
#
# Why this exists: includes/email_layout.php lives inside public_html on prod but
# outside public/ in the repo, so deploy.py can't ship it. The other includes/
# deploy tool, deploy_mailer.sh, bundles the SECRET config/mail.config.php (Brevo
# creds) into the same push — overkill and risky for a chrome-only change. This
# tool touches exactly one non-secret file and verifies the bytes landed via a
# sha256 compare. It will never write to config/ or mail.php.
set -euo pipefail

KEY="$HOME/.ssh/freshthaband_key"
HOST="ultimaterage@72.167.54.213"
SRC="/c/xampp/htdocs/od9/includes/email_layout.php"
DEST="/home/offda9/public_html/includes/email_layout.php"

[ -f "$SRC" ] || { echo "!! missing $SRC" >&2; exit 1; }
php -l "$SRC" >/dev/null || { echo "!! php -l failed" >&2; exit 1; }

local_sha=$(sha256sum "$SRC" | awk '{print $1}')
echo ">> local  sha256: $local_sha"

echo ">> uploading ..."
scp -i "$KEY" "$SRC" "$HOST:/tmp/od9_inc_layout.php"

echo ">> installing (email_layout.php ONLY — no config, no secrets) ..."
remote_sha=$(ssh -i "$KEY" "$HOST" '
  set -e
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_inc_layout.php '"$DEST"'
  rm -f /tmp/od9_inc_layout.php
  sha256sum '"$DEST"' | awk "{print \$1}"
')
echo ">> remote sha256: $remote_sha"

if [ "$local_sha" = "$remote_sha" ]; then
  echo "DEPLOYED-OK (bytes verified identical)"
else
  echo "!! MISMATCH — prod bytes differ from local" >&2
  exit 1
fi
