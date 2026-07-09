# Voyasee Best Area to Stay Finder 5.0.0 — accuracy, intelligence & design upgrade

## What was requested

After the tool was added to the Voyasee tools hub page, the ask was a full
A-to-Z upgrade: first find and fix every bug and weakness that made the result
page inaccurate, then build on top of that — more real destinations and
neighborhoods (genuine areas only, never invented profiles), free and
commercially-permitted intelligence layers (no paid APIs, no ad/affiliate
policy violations), and a more informative, infographic-style result page.

The work was planned first as a 5-phase blueprint (Phase 0 correctness → Phase
4 design), the plan was approved in full, and this release implements all of
it. There are no breaking changes to the shortcode or the database schema.

## Phase 0 — Correctness fixes (the results are now genuinely more accurate)

These are the fixes that change which areas win a match, so they matter most.

- **Nightly price now respects how expensive the destination is.**
  `WTSM_Currency::estimate_nightly_range()` previously applied one global set of
  USD band anchors regardless of city, so a "$$" area in Tokyo and a "$$" area
  in Hanoi showed the identical range. It now takes the destination's 1–5
  `cost_index` and scales the anchors by `1.0 + ((cost_index - 3) * 0.25)`
  before converting to local currency — an expensive city reads higher than a
  cheap one at the same price band. The REST caller was updated to pass the
  destination's cost index through.

- **Culture/history trips now score against real attraction density.**
  For travelers who selected "museums & culture" or "history", the attractions
  score was blended against a walkability proxy instead of the
  `poi_attraction_count` already stored per neighborhood. A new
  `attraction_count_to_score()` log curve (5 attractions ≈ 50, 20 ≈ 80, 40+ ≈
  95) now feeds those interests, so culture-rich areas rank correctly for
  culture-focused trips.

- **Safety is no longer cosmetic.** `score_safety()` returned a flat perfect
  score the moment an area met the traveler's comfort floor, and it only
  carried a 0.05 weight — so it almost never affected the ranking. It now uses a
  graduated curve (`min(100, 85 + surplus*8)` when safe,
  `max(0, 60 - gap*25)` when below the floor) and its weight was doubled to
  0.10 (redistributed from vibe −0.02, attractions −0.02, walkability −0.01).
  The full default weight set still sums to exactly 1.0, re-verified across
  every traveler-type / interest / toggle combination.

## Phase 3 — New intelligence layers (all from data already on file)

No new external services and no paid APIs were added. Every new signal is
derived from POI and scoring data the plugin already collects, so each one is
fully explainable rather than a black box.

- **Area DNA tags.** Short, plain-language badges on each area — up to five of:
  late-night-friendly, car-free-ready, foodie-area, essentials-nearby,
  green-space, family-ready, airport-close, central, high-safety — built by
  `build_area_dna()` purely from existing counts and scores.

- **"Within ~800m" POI facts panel.** The area detail view now surfaces the
  concrete counts behind the scores: restaurants, cafés, bars, attractions,
  transit stops, supermarkets, pharmacies and parks, attributed to
  OpenStreetMap. Only shown when POI data is actually synced for that area
  (never fabricated).

- **Top-reason chips.** Each match card shows the two or three strongest,
  high-confidence dimensions (confirmed data, score ≥ 75) at a glance.

## Phase 1 — Data depth

The bundled starter dataset was expanded with real, well-known neighborhoods,
deliberately filling previously thin archetypes (backpacker, beach, nightlife,
quiet-residential) so those quiz answers have somewhere to land:

- Los Angeles (Hollywood, Santa Monica), San Francisco (Fisherman's Wharf,
  Mission District), Chicago (The Loop, Wicker Park), Miami (South Beach,
  Brickell), Las Vegas (The Strip, Fremont Street), Rio de Janeiro (Copacabana,
  Ipanema), Buenos Aires (Palermo, Recoleta), Cape Town (City Bowl, Camps Bay),
  Bali (Seminyak, Kuta), Cancún (Zona Hotelera, El Centro), Delhi (Connaught
  Place, Hauz Khas), Kyoto (Gion, Arashiyama), Shanghai (The Bund, French
  Concession), Beijing (Wangfujing, Sanlitun), and Kuala Lumpur (Bukit Bintang,
  KLCC).

Every added area is a genuine, existing neighborhood with realistic
coordinates, price band, safety tier and editorial copy — no invented profiles.

## Phase 4 — Presentation

- **Infographic trip-facts tiles.** The flat text "fact chips" (jet lag,
  weather, air quality, seasonal note, holiday overlap) were rebuilt as a tile
  grid, each tile with an icon, label, value and supporting note, under a
  "Trip intelligence" heading.
- **Bolder Wrong Area Warning.** The signature caution box now has a gold accent
  bar and a clearer warning heading so it's harder to miss.
- **Housekeeping.** Removed now-unused legacy `.vwtsm-fact-chip` CSS left over
  from the old chip layout (kept `.vwtsm-fact-chip-icon`, which the practical
  facts panel still uses).

## Verification performed

- `php -l` clean on all four modified PHP files.
- `node --check` clean on `matcher.js`.
- CSS brace balance verified (368/368) after the cleanup edit.
- Weight-sum invariant re-run across every traveler-type × interest × toggle
  combination — every effective-weight set sums to 1.0.

## Upgrade note

The shortcode and database schema are unchanged, so existing installs keep
working as-is. To pull in the new starter neighborhoods on an in-place upgrade
(without deleting the plugin), go to **Best Area to Stay Finder → Import CSV**
and click **"Add any new starter destinations"** — it's safe to run anytime and
never overwrites existing data.
