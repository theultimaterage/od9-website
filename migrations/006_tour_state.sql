-- 006: guided-tour chapter state (docs/GUIDED_TOUR_SPEC.md §2).
-- Canonical schema record; tour-state.php also runs this CREATE TABLE IF NOT
-- EXISTS on first use, so the table self-heals and no manual prod step exists.
CREATE TABLE IF NOT EXISTS od9_tour_state (
    discord_id VARCHAR(32) NOT NULL PRIMARY KEY,
    chapters TEXT NOT NULL,                -- JSON: {"board":"done","dashboard":"done",...}
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
