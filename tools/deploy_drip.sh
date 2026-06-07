#!/usr/bin/env bash
# Ship the three drip/newsletter senders to prod. They live in api/drip/ in the
# repo (repo-root, not under public/), but map to public_html/api/drip/ on prod,
# so deploy.py can't handle them — same SSH path as the mailer. Idempotent.
set -euo pipefail

KEY="$HOME/.ssh/freshthaband_key"
HOST="ultimaterage@72.167.54.213"
SRC="/c/xampp/htdocs/od9/api/drip"
DEST="/home/offda9/public_html/api/drip"

echo ">> uploading to /tmp ..."
scp -i "$KEY" "$SRC/processor.php"              "$HOST:/tmp/od9_drip_processor.php"
scp -i "$KEY" "$SRC/sender.php"                 "$HOST:/tmp/od9_drip_sender.php"
scp -i "$KEY" "$SRC/weekly_lovelogic_sender.php" "$HOST:/tmp/od9_drip_weekly.php"

echo ">> installing into $DEST ..."
ssh -i "$KEY" "$HOST" "
  set -e
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_drip_processor.php $DEST/processor.php
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_drip_sender.php    $DEST/sender.php
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_drip_weekly.php    $DEST/weekly_lovelogic_sender.php
  rm -f /tmp/od9_drip_processor.php /tmp/od9_drip_sender.php /tmp/od9_drip_weekly.php
  ls -l $DEST/processor.php $DEST/sender.php $DEST/weekly_lovelogic_sender.php
"
echo "DRIP-DEPLOYED-OK"
