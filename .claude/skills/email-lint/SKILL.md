---
name: email-lint
description: Guardrail that fails when any OD9 email surface drifts from the branded, CAN-SPAM-compliant shared chrome (includes/email_layout.php). Catches emails that bypass od9_email_layout(), a layout missing its logo/unsubscribe/postal address, drip templates that aren't fragments or carry doc chrome, <img> without alt, and — most importantly — broken merge tokens that ship literal ({{tier}}, {{credits}}, {{achievement_name}}, or a {{first_name}} vs {{first_name|Hey}} mismatch). Run before any email commit and inside deploy_templates.sh.
---

# email-lint

OD9 email mirrors the website's shared-chrome model: one layout
(`includes/email_layout.php` → `od9_email_layout()`) supplies the logo header +
unsubscribe/postal footer, **every** sender routes through it, and each drip
template is an inner-content **fragment**. This guardrail keeps that true. Run it:

```bash
python tools/email_lint.py             # scan every email surface, exit 1 on drift
python tools/email_lint.py --list-ok   # also list compliant surfaces
```

It reports a per-surface worklist:
- **LAYOUT** — `includes/email_layout.php` is missing the logo `<img>` (absolute `offda9.com/images/email/` src + `alt`), the unsubscribe link, a physical postal address (ZIP — CAN-SPAM), or `od9_email_button()`.
- **SENDER** — a sender (`public/subscribe.php`, `api/drip/sender.php`, `api/drip/weekly_lovelogic_sender.php`) does **not** call `od9_email_layout(` → its mail bypasses the branded chrome.
- **NOT_FRAGMENT** — a drip template carries `<!DOCTYPE>/<html>/<head>/<body>`; the layout supplies those, so templates must be inner-content fragments only.
- **IMG_NO_ALT** — an `<img>` without `alt=`.
- **BROKEN_TOKEN** — a `{{token}}` the surface's sender does **not** resolve, so it ships literal. The drip allowlist is the set `api/drip/sender.php`'s `personalize()` replaces (`{{username}}`, `{{first_name}}`, `{{EMAIL}}`, `{{unsubscribe_url}}`, …). Standalone one-shot emails (founding-patron) are checked against their own sender's set. **This is the high-value check** — it's why `{{achievement_name}}`/`{{tier}}`/`{{credits}}` stopped shipping as literal text.
- **DOC** — a registered standalone full-doc email is missing an unsubscribe link or postal address.

## Adding a new email
- A new **drip template** must be an inner-content fragment (no doc chrome) using only allowlisted tokens; `api/drip/sender.php` wraps it via `od9_email_layout()`.
- A new **standalone** full-doc email: register it (filename → its sender's resolved tokens) in `STANDALONE` in `tools/email_lint.py`, and give it an unsubscribe link + postal address.
- A new **token**: add it to `DRIP_TOKENS` (or the standalone's set) only once a sender actually resolves it.

**Wire it in** (so drift cannot ship): it runs as a preflight at the top of
`tools/deploy_templates.sh` (template deploys abort on drift); also run it before
any email commit. Exit 0 = clean, 1 = drift. Companion to **`web-template-lint`**
(the website equivalent).
