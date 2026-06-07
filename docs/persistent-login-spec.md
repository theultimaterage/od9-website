# Spec: Persistent Dashboard Login ("Remember Me")

**Status:** Draft for review · **Author:** Claude (with The Ultimate Rage) · **Date:** 2026-06-07
**Task:** #7 — stop forcing a full Discord OAuth round-trip on every dashboard visit.

---

## 1. Problem (root cause, verified in code)

The dashboard authenticates with **Discord OAuth** (`/dashboard/auth/discord.php` → `callback.php`) and stores identity in a **PHP session** (`$_SESSION['discord_id']` + cached bot data).

`index.php` sets the session **cookie** to a 7-day lifetime:

```php
session_set_cookie_params(['lifetime'=>604800,'secure'=>od9_cookie_secure(),'httponly'=>true,'samesite'=>'Lax']);
```

But the **server-side session data** is governed by `session.gc_maxlifetime` (PHP default **1440s ≈ 24 min**) and lives in the shared `session.save_path`. On GoDaddy shared hosting the global session GC reaps those files well before the 7-day cookie expires. So on any return after ~24 min idle (or a browser restart), the cookie is still sent but **the session it points at is gone** → `$_SESSION['discord_id']` is empty → the user is bounced back through the entire Discord OAuth flow. That re-auth-every-visit friction is task #7.

**A longer cookie alone cannot fix this** — the gap is server-side session lifetime, which we can't reliably control on shared hosting. The correct fix is an independent **remember-me persistent token** that can *rebuild* a session after the PHP session has died.

## 2. Goals / non-goals

**Goals**
- A returning member stays logged in for up to **30 days** of inactivity without re-running OAuth.
- Secure by construction: DB compromise yields no usable cookies; stolen-cookie reuse is detectable and time-bounded.
- Works across multiple devices/browsers independently.
- Centralize the dashboard's session bootstrap (currently duplicated inline in `index.php`).

**Non-goals**
- No password auth (Discord OAuth stays the only identity source).
- No change to what data the dashboard shows (#8 progression UI is separate).
- Not building "active sessions management UI" now (deferred; schema will support it).

## 3. Design — selector/validator split-token remember-me

The industry-standard secure pattern (Paragonie / Barry Jaspan), chosen over a single-token cookie because it allows an **indexed O(1) lookup** *and* a **constant-time secret comparison** with no timing leak.

### Cookie
`od9_remember = "<selector>:<validator>"`, where
- `selector` = 12 random bytes, hex (24 chars) — the DB lookup key (not secret).
- `validator` = 32 random bytes, hex (64 chars) — the secret; **only ever exists in the cookie**, never stored raw.

Cookie flags: `Secure` (prod, via `od9_cookie_secure()`), `HttpOnly` (blocks JS/XSS theft), `SameSite=Lax` (CSRF mitigation; Lax still allows top-level navigation to the dashboard), `Path=/dashboard/`, `Max-Age = 30 days`.

### DB table — `migrations/005_remember_tokens.sql`
```sql
CREATE TABLE IF NOT EXISTS od9_remember_tokens (
    selector       CHAR(24)    NOT NULL PRIMARY KEY,         -- 12 bytes hex, lookup key
    validator_hash CHAR(64)    NOT NULL,                     -- sha256(validator) hex
    discord_id     VARCHAR(20) NOT NULL,
    expires_at     DATETIME    NOT NULL,
    created_at     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at   DATETIME    NULL,
    user_agent     VARCHAR(255) NULL,                        -- audit only, NOT enforced
    INDEX idx_discord (discord_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Web-owned, same MySQL DB as `od9_profile_visibility` (migration 003), reached via `getDatabaseConnection()`.

> **Why `sha256`, not bcrypt/argon2:** slow hashes defend *low-entropy* secrets (passwords). The validator is 256 bits of CSPRNG output — brute force is infeasible regardless — so a fast hash is the correct, recommended choice and keeps per-request auth cheap.

### Issue (on successful OAuth, in `callback.php`)
After the existing membership check + `$_SESSION` set, and **after `session_regenerate_id(true)`** (fixation defense):
1. `selector = bin2hex(random_bytes(12)); validator = bin2hex(random_bytes(32));`
2. `INSERT` row: selector, `hash('sha256',$validator)`, discord_id, `NOW()+30d`, UA.
3. Set the `od9_remember` cookie to `selector:validator`.

### Consume (when a request has a remember cookie but no live session)
In the shared bootstrap (`od9_dashboard_boot()`), if `empty($_SESSION['discord_id'])` and the cookie is present:
1. Split into `selector`/`validator`; bail to logged-out if malformed.
2. `SELECT ... WHERE selector = ? AND expires_at > NOW()`.
3. If a row exists, `hash_equals($row['validator_hash'], hash('sha256',$validator))` — **constant time**.
4. **On match:**
   - **Re-verify authorization**: confirm the `discord_id` is still in `od9_members` (they may have left the server). If not → invalidate token, stay logged out.
   - `session_regenerate_id(true)`, set `$_SESSION['discord_id']` + re-hydrate cached bot fields exactly as `callback.php` does.
   - **Rotate**: issue a new validator (and selector), `UPDATE` the row, reset `expires_at = NOW()+30d` (sliding), update `last_used_at`, and re-set the cookie.
5. **On selector-hit but validator-MISMATCH** → treat as theft/forgery: `DELETE FROM od9_remember_tokens WHERE discord_id = ?` (nuke every token for that user), clear the cookie, force fresh login.
6. **On no row / expired** → clear the cookie, stay logged out.

> Rotation only fires when the session is absent (≈ once per session revival, not per page load — once revived, the session carries subsequent requests). So the classic remember-me rotation race window is tiny for this low-traffic dashboard; noted, not mitigated further.

### Logout (`logout.php`)
- Delete the current token row by its cookie selector, clear the `od9_remember` cookie, then the existing session teardown.
- (Stretch) a `?everywhere=1` variant → `DELETE ... WHERE discord_id = ?` to drop all devices. Also: make logout **POST-only** (or CSRF-token it) so a forged GET can't silently log users out.

### Expired-token GC
Opportunistic `DELETE ... WHERE expires_at < NOW()` on each consume attempt, plus a tiny weekly cron sweep (the box already runs the OD9 crontab) so abandoned rows don't accumulate.

## 4. Files

| File | Change |
|---|---|
| `migrations/005_remember_tokens.sql` | **NEW** — the table above |
| `public/dashboard/includes/auth.php` | **NEW** — `od9_dashboard_boot()` (centralized secure session params + `session_start` + remember-cookie consume/rotate), `od9_issue_remember_token($id)`, `od9_clear_remember_token()`, internal rotate/verify helpers |
| `public/dashboard/auth/callback.php` | Add `session_regenerate_id(true)` + `od9_issue_remember_token($discordId)` after the session is set |
| `public/dashboard/index.php` | Replace the inline `session_set_cookie_params` + `session_start` with `require includes/auth.php; od9_dashboard_boot();` |
| `public/dashboard/profile.php`, `settings.php` | Same `od9_dashboard_boot()` bootstrap (so token re-auth applies everywhere, not just index) |
| `public/dashboard/auth/logout.php` | Delete current token + clear cookie; POST-only/CSRF |

No new secrets (uses the existing DB + `random_bytes` CSPRNG). Reuses `od9_cookie_secure()` / `od9_is_local()` from `public/includes/env.php`.

## 5. Security analysis (threats → mitigations)

- **DB read compromise** → only `sha256(validator)` is stored; cannot reconstruct the cookie. ✓
- **Cookie theft via XSS** → `HttpOnly` blocks JS access. ✓ (and rotation bounds the reuse window)
- **Cookie theft via network** → `Secure` (HTTPS-only) on prod. ✓
- **Replay of a stolen cookie** → rotation on each use + theft detection (validator mismatch nukes all the user's tokens). ✓
- **Timing attack on lookup** → indexed selector (no secret in the WHERE), `hash_equals` for the validator. ✓
- **Session fixation** → `session_regenerate_id(true)` on both OAuth login and token revival. ✓
- **CSRF** → `SameSite=Lax` on both session + remember cookies; logout hardened to POST/CSRF. ✓
- **Stale authorization** (user left Discord) → membership re-checked against `od9_members` on every token revival, not trusted from the cookie. ✓
- **Token fixation / forced-login** → selector/validator are server-generated CSPRNG; an attacker can't pre-plant a valid pair. ✓
- **Shared-computer risk** → see open decision A (checkbox vs always-on).

## 6. Edge cases
- **Multi-device**: one row per login → independent remember on each device. ✓
- **Cookie valid, row gone** (logged out elsewhere / GC'd): no match → clear cookie, show login.
- **UA stored but NOT enforced**: hard-binding to user-agent breaks legit users on browser updates; we keep UA for audit only.
- **Clock**: all expiry math uses server `NOW()` / `time()`.
- **`callback.php` builds its own PDO; `db.php` exposes `getDatabaseConnection()`** — `auth.php` will standardize on `getDatabaseConnection()`; a follow-up can converge callback too.

## 7. Test plan
- **Unit** (`tests/`): selector/validator format + entropy; `sha256` + `hash_equals` match/mismatch; rotation produces a new validator; theft path deletes all rows.
- **Integration (local XAMPP)**:
  1. Login → assert `od9_remember` cookie set + one DB row (hashed).
  2. Destroy the PHP session (delete the session file) → reload → asserts logged-in **without** OAuth + the row's validator/selector rotated + `expires_at` extended.
  3. Tamper a cookie byte → asserts logged out + **all** rows for that user deleted.
  4. Expire the row (`expires_at` in the past) → asserts logged out + cookie cleared.
  5. Logout → asserts row deleted + cookie cleared.
  6. Membership revoked (delete the `od9_members` row) → token revival refused.
- **Idempotency**: re-running the migration is a no-op (`IF NOT EXISTS`).

## 8. Deploy
1. Apply `migrations/005_remember_tokens.sql` to the prod MySQL DB (same DB as 003). *(Confirm how migrations are applied on prod — manual `mysql <` vs a runner.)*
2. `deploy.py --files dashboard/includes/auth.php dashboard/index.php dashboard/profile.php dashboard/settings.php dashboard/auth/callback.php dashboard/auth/logout.php`.
3. Verify on prod: login → cookie present (DevTools shows HttpOnly+Secure) → wait past session GC (or clear the session cookie only) → revisit → still logged in, no Discord round-trip.

## 9. Decisions (RESOLVED 2026-06-07)
- **A. Always-on** (no checkbox). Every Discord login issues the token. Low-sensitivity data behind OAuth; directly satisfies "stop re-registering."
- **B. 30-day sliding** expiry (extends on each use), no hard cap initially.
- **C. Cookie scope `Path=/dashboard/`.**
- **D. Migrations are applied MANUALLY** — no runner, no `schema_migrations` table (migration 002's header: "Applied locally to XAMPP `od9_tickets` and on prod to `offda9_od9_tickets`"). Plan: I apply `005` to the local XAMPP DB for testing; **the user runs the same SQL on prod** (cPanel phpMyAdmin) — it's `CREATE TABLE IF NOT EXISTS`, idempotent. Code ships via `deploy.py`.
```
