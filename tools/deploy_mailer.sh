#!/usr/bin/env bash
# One-shot: ship the unified mailer + Brevo config to prod (files that live
# INSIDE public_html on prod but outside it in the repo, so deploy.py can't).
# Order-critical: this MUST run before the new subscribe.php is deployed, or
# subscribe.php's new od9_send_mail($opts) signature hits the OLD prod mail.php.
set -euo pipefail

KEY="$HOME/.ssh/freshthaband_key"
HOST="ultimaterage@72.167.54.213"
SRC="/c/xampp/htdocs/od9"

echo ">> uploading to /tmp ..."
scp -i "$KEY" "$SRC/includes/mail.php"          "$HOST:/tmp/od9_inc_mail.php"
scp -i "$KEY" "$SRC/includes/email_layout.php"  "$HOST:/tmp/od9_inc_layout.php"
scp -i "$KEY" "$SRC/config/mail.php"            "$HOST:/tmp/od9_cfg_mail.php"
scp -i "$KEY" "$SRC/config/mail.config.php"     "$HOST:/tmp/od9_cfg_mc.php"

echo ">> installing into public_html ..."
ssh -i "$KEY" "$HOST" '
  set -e
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_inc_mail.php   /home/offda9/public_html/includes/mail.php
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_inc_layout.php /home/offda9/public_html/includes/email_layout.php
  sudo install -o offda9 -g offda9 -m 644 /tmp/od9_cfg_mail.php   /home/offda9/public_html/config/mail.php
  sudo install -o offda9 -g offda9 -m 600 /tmp/od9_cfg_mc.php     /home/offda9/public_html/config/mail.config.php
  rm -f /tmp/od9_inc_mail.php /tmp/od9_inc_layout.php /tmp/od9_cfg_mail.php /tmp/od9_cfg_mc.php
  echo "   modes:"
  ls -l /home/offda9/public_html/includes/mail.php /home/offda9/public_html/includes/email_layout.php /home/offda9/public_html/config/mail.php /home/offda9/public_html/config/mail.config.php
'
echo "DEPLOYED-OK"
