#!/usr/bin/env bash
# Ship the email-templates/*.html (drip fragments + one-shot campaigns) to prod.
# They live at repo-root email-templates/ but map to public_html/email-templates/
# on prod, so deploy.py (public/ only) can't handle them. Idempotent.
# NOTE: after the chrome migration the *.html are inner-content FRAGMENTS — they
# only render correctly when wrapped by od9_email_layout() in the senders, so
# deploy this together with the senders (deploy_drip.sh).
set -euo pipefail

KEY="$HOME/.ssh/freshthaband_key"
HOST="ultimaterage@72.167.54.213"
SRC="/c/xampp/htdocs/od9/email-templates"
DEST="/home/offda9/public_html/email-templates"

echo ">> staging templates on prod ..."
ssh -i "$KEY" "$HOST" 'rm -rf /tmp/od9tpl && mkdir -p /tmp/od9tpl'
scp -i "$KEY" "$SRC"/*.html "$HOST:/tmp/od9tpl/"

echo ">> installing into $DEST ..."
ssh -i "$KEY" "$HOST" '
  set -e
  for f in /tmp/od9tpl/*.html; do
    sudo install -o offda9 -g offda9 -m 644 "$f" "'"$DEST"'/$(basename "$f")"
  done
  echo "   installed *.html count:"; ls -1 '"$DEST"'/*.html | wc -l
  rm -rf /tmp/od9tpl
'
echo "TEMPLATES-DEPLOYED-OK"
