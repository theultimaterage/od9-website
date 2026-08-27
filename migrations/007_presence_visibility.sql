-- 007_presence_visibility.sql
--
-- World presence consent (PROGRESSION_WORLD_SPEC §5): whether a member's token
-- shows on the zone map, and how. THREE states, not a boolean, because
-- anonymity is a legitimate middle ground on an anti-surveillance product:
--   'hidden'  — no token, ever (DEFAULT: privacy is the baseline)
--   'anon'    — an unnamed token ("someone is on this step")
--   'visible' — named token (username initial + name on hover)
--
-- Lives on od9_profile_visibility (003) — one consent home, web-owned, the bot
-- never reads it. Apply MANUALLY (no runner): local XAMPP `od9_tickets` and
-- prod `offda9_od9_tickets`. Idempotent (MariaDB IF NOT EXISTS).
--
-- The backfill honors consent already given: welcome.php's onboarding checkbox
-- has ALWAYS read "Your token appears on the zone map so others can see
-- they're climbing alongside you" — but until presence existed it could only
-- write is_public. Members who checked it consented to exactly this feature,
-- so is_public=1 seeds presence='visible'. (welcome.php now writes presence
-- directly; is_public stays the profile-PAGE flag, settings-owned.)

ALTER TABLE od9_profile_visibility
    ADD COLUMN IF NOT EXISTS presence VARCHAR(10) NOT NULL DEFAULT 'hidden';

UPDATE od9_profile_visibility
   SET presence = 'visible'
 WHERE is_public = 1 AND presence = 'hidden';
