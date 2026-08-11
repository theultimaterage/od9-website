<?php
/**
 * State of the Filter — canonical scorecard data (single source of truth).
 *
 * The mission layer above the OD9 roadmap: outcome-framed civilizational metrics a
 * society optimized to pass the Great Filter would be clearing. OUTCOMES live here;
 * the MECHANISMS (how to get there — UBI vs jobs guarantee, single-payer vs hybrid…)
 * are deliberately NOT on this list: they go to the community debate floor.
 *
 * INTEGRITY RULE (non-negotiable): every number on this page was verified against its
 * PRIMARY source on the verified date below — never from memory, never extrapolated.
 * When updating: re-check the source, update value + year + verified together. A stale
 * number with an honest date beats a fresh-looking guess. Statuses:
 *   failing    — the outcome is not met and the gap is large
 *   at_risk    — partial/fragile progress, could go either way
 *   unmeasured — humanity does not reliably measure this AT ALL (the gap IS the finding)
 * Trend: worsening | improving | stalled | mixed | unknown  (direction of the OUTCOME).
 */

$SOF_VERIFIED = '2026-07-04';

// Headline strip values (rendered even when the scorecard is collapsed).
// Consumed by BOTH surfaces: the public progress.php strip and the ASCEND board's
// World State panel (dashboard/board.php) — one source, two renderers, no drift.
$SOF_CLOCK = '85 seconds to midnight';           // Bulletin of the Atomic Scientists, Jan 27 2026 — closest ever
$SOF_KARDASHEV = 'K ≈ 0.73';                     // Sagan interpolation over ~592 EJ/yr (Energy Institute, 2025)
$SOF_CO2_PPM = '430';                            // NOAA GML: seasonal peak first crossed 430 ppm (May 2025: 430.5)

$SOF_METRICS = [
    [
        'id' => 'xrisk',
        'optimal' => 'a standing registry',
        'title' => 'Existential-Risk Accounting',
        'status' => 'unmeasured',
        'trend' => 'unknown',
        'headline' => 'No registry exists',
        'detail' => 'No international body maintains a formal registry of existential threats with mitigation ownership. The nearest thing humanity has is a civil-society clock: the Doomsday Clock sits at 85 seconds to midnight (Jan 2026) — the closest to catastrophe it has ever been.',
        'passing' => 'A standing international existential-risk registry: every known threat tracked, owned, and resourced.',
        'source' => 'Bulletin of the Atomic Scientists (2026)',
        'source_url' => 'https://thebulletin.org/doomsday-clock/',
    ],
    [
        'id' => 'nuclear',
        'optimal' => 'binding limits, declining',
        'title' => 'Nuclear Escalation Control',
        'status' => 'failing',
        'trend' => 'worsening',
        'headline' => '12,187 warheads · zero treaty limits',
        'detail' => 'New START expired 5 Feb 2026 with no successor and no negotiations even planned — the first time in over 50 years the US and Russian strategic arsenals are under no binding limits. All nine nuclear states are modernizing.',
        'passing' => 'Binding, verified arms-control limits in force and stockpiles declining.',
        'source' => 'SIPRI Yearbook 2026 · UN Secretary-General (Feb 2026)',
        'source_url' => 'https://www.sipri.org/media/press-release/2026/increasing-focus-nuclear-weapons-amid-heightened-escalation-risks-new-sipri-yearbook-out-now',
    ],
    [
        'id' => 'climate',
        'optimal' => '&le;350 ppm / Paris held',
        'title' => 'Climate Trajectory',
        'status' => 'failing',
        'trend' => 'worsening',
        'headline' => 'First 3-year period above 1.5°C',
        'detail' => '2023–2025 averaged 1.48 ± 0.13°C above pre-industrial — the first three-year span over the Paris threshold; the 11 warmest years on record are the last 11. Atmospheric CO₂ crossed a 430 ppm seasonal peak in May 2025, the highest in over 2 million years.',
        'passing' => 'Emissions falling fast enough to hold the Paris range; CO₂ concentration peaking, then declining.',
        'source' => 'WMO State of the Global Climate 2025 · NOAA GML',
        'source_url' => 'https://wmo.int/news/media-centre/wmo-confirms-2025-was-one-of-warmest-years-record',
    ],
    [
        'id' => 'pandemic',
        'optimal' => 'ratified + ready',
        'title' => 'Pandemic Preparedness',
        'status' => 'at_risk',
        'trend' => 'mixed',
        'headline' => 'Avg readiness 38.9/100 · treaty stalled',
        'detail' => 'Average national preparedness scored 38.9/100 in the last full Global Health Security Index (2021) — no country in the top tier. The WHO Pandemic Agreement was adopted in May 2025, but it cannot even open for ratification until its pathogen-access annex is finished — now pushed to May 2027.',
        'passing' => 'A ratified, funded global pandemic framework and preparedness scores rising across all regions.',
        'source' => 'GHS Index 2021 · WHO (May 2025 / May 2026)',
        'source_url' => 'https://www.who.int/news/item/01-05-2026-who-member-states-agree-to-extend-negotiations-on-pathogen-access-and-benefit-sharing-annex',
    ],
    [
        'id' => 'hunger',
        'optimal' => 'zero preventable',
        'title' => 'Hunger',
        'status' => 'failing',
        'trend' => 'improving',
        'headline' => '673 million people hungry',
        'detail' => '8.3% of humanity faced hunger in 2024 — down 15 million from 2023, but still above pre-pandemic levels and rising in Africa and western Asia. 2.3 billion people experienced moderate or severe food insecurity.',
        'passing' => 'Zero deaths and zero chronic undernourishment from preventable hunger. The food exists; the coordination does not.',
        'source' => 'FAO SOFI 2025',
        'source_url' => 'https://www.fao.org/newsroom/detail/global-hunger-declines--but-rises-in-africa-and-western-asia--un-report/en',
    ],
    [
        'id' => 'poverty',
        'optimal' => 'universal floor',
        'title' => 'Extreme Poverty → Basic Assets',
        'status' => 'failing',
        'trend' => 'improving',
        'headline' => '808 million below $3.00/day',
        'detail' => '9.9% of humanity — 1 in 10 people — lives in extreme poverty (2025, at the World Bank\'s updated $3.00/day line). The multi-decade trend is real improvement; the remaining gap is a coordination choice, not a resource shortage.',
        'passing' => 'A universal floor of basic assets — nobody below survival level in a civilization this rich.',
        'source' => 'World Bank (June 2025 poverty-line update)',
        'source_url' => 'https://www.worldbank.org/en/news/factsheet/2025/06/05/june-2025-update-to-global-poverty-lines',
    ],
    [
        'id' => 'housing',
        'optimal' => 'measured + housed',
        'title' => 'Housing & Homelessness',
        'status' => 'unmeasured',
        'trend' => 'mixed',
        'headline' => '~300M homeless · last global count: 2005',
        'detail' => 'The UN last attempted a global homelessness count in 2005. Current estimates run ~300 million homeless and ~2.8 billion lacking adequate housing — a range so wide it proves the point: a civilization serious about this would measure it. Where measurement exists it can move: US homelessness fell 3.4% in 2025, the first decline in nearly a decade, after a record 771,480 in 2024.',
        'passing' => 'Housing security measured everywhere, and unsheltered homelessness trending to zero where measured.',
        'source' => 'UN DESA (2025) · HUD AHAR (May 2026)',
        'source_url' => 'https://social.desa.un.org/world-summit-2025/blog/300million-people-homeless-worldwide',
    ],
    [
        'id' => 'health',
        'optimal' => 'universal coverage',
        'title' => 'Universal Healthcare',
        'status' => 'failing',
        'trend' => 'stalled',
        'headline' => '4.6 billion not fully covered',
        'detail' => 'More than half of humanity was not fully covered by essential health services in 2023 — and coverage progress has stalled since 2015. In 2022, 2.1 billion people faced financial hardship from out-of-pocket health costs; 1.6 billion were pushed into or deeper into poverty by them.',
        'passing' => 'Everyone covered for essential care without financial ruin — the outcome; the mechanism is the debate.',
        'source' => 'WHO–World Bank UHC Global Monitoring Report (Dec 2025)',
        'source_url' => 'https://www.who.int/news/item/06-12-2025-most-countries-make-progress-towards-universal-health-coverage-but-major-challenges-remain-who-world-bank-report-finds',
    ],
    [
        'id' => 'education',
        'optimal' => 'zero out of school',
        'title' => 'Education',
        'status' => 'failing',
        'trend' => 'worsening',
        'headline' => '272 million children out of school',
        'detail' => 'UNESCO\'s 2025 revision moved the out-of-school count UP by 21 million, to 272 million — with less than 1% improvement since the world set the 2030 education goal in 2015. In low-income countries a third of school-age children are out of school, versus 3% in high-income ones.',
        'passing' => 'Universal schooling with actual learning — an education system built for the century we\'re in.',
        'source' => 'UNESCO GEM Report (2025 revision)',
        'source_url' => 'https://www.unesco.org/en/articles/251m-children-and-youth-still-out-school-despite-decades-progress-unesco-report',
    ],
    [
        'id' => 'energy',
        'optimal' => 'K&rarr;1.0 on clean supply',
        'title' => 'Energy Transition (Kardashev trajectory)',
        'status' => 'at_risk',
        'trend' => 'improving',
        'headline' => 'K ≈ 0.73 · hydrocarbons still ~87%',
        'detail' => 'Humanity runs on ~592 exajoules a year — Kardashev ≈ 0.73 on Sagan\'s interpolation — but hydrocarbons still supply ~87% of it. The Type I threshold isn\'t just an energy number: it\'s reached by a civilization coordinated enough to harness planetary energy CLEANLY without destroying itself first.',
        'passing' => 'K climbing toward 1.0 on clean supply — the energy curve and the survival curve bending together.',
        'source' => 'Energy Institute Statistical Review (2025) · Sagan interpolation',
        'source_url' => 'https://www.energyinst.org/statistical-review',
    ],
];

// Composite: how many metrics currently PASS their bar. (Spoiler: none. That's the point.)
$SOF_PASSING = 0;
$SOF_TOTAL = count($SOF_METRICS);
