# Voyasee Where to Stay Matcher 4.0.0 — the full blueprint, implemented

## What was requested

Implement the entire v4.0 upgrade blueprint in one pass: every feature from the "confirmed safe" priority list, using only free, keyless, or already-integrated public data sources — no paid APIs, nothing that would jeopardize running affiliate links/ads. Two sibling plugins (Voyasee Weather Bridge v1.3.1, Voyasee Country Intelligence v2.0.0) were provided and analyzed first; their real integration points replaced several items originally planned around calling external APIs directly.

## What shipped

### Bug fix
- `voyasee_ni_maybe_get_weather()` called `voyasee_wb_get_forecast()` — a function that never existed in Weather Bridge. Fixed to call the plugin's real functions (`voyasee_weather_get_forecast()` for near-term dates, `voyasee_weather_get_climate_normals()` — NASA POWER, free/keyless — for dates further out or no date at all).

### New data on the results page
1. **Trip Facts strip** — jet-lag-at-a-glance (pure client-side `Intl` time-zone math, zero API), a weather snapshot (Weather Bridge), and a public-holiday overlap warning (Country Intelligence's pre-compiled local calendars, no external HTTP call).
2. **Practical Facts panel** — driving side, plug type, tipping guidance, and emergency numbers, sourced from Country Intelligence and read defensively (the plugin's exact nested field names weren't fully confirmed during analysis, so every field is looked up via several plausible key paths and simply omitted — never shown blank — if none match).
3. **Currency-aware pricing** — an estimated nightly range in the destination's local currency (Country Intelligence's currency code + Frankfurter's free ECB-rate API, no key, unrestricted commercial use), alongside the existing $-$$$$$ band.
4. **Nearby named landmarks** — a new daily-batched sync against Wikipedia's GeoSearch API (titles + coordinates only, never article text, matching this plugin's existing "never paste external editorial text" rule), shown as chips on match cards.
5. **Daily-convenience stat** — supermarket/pharmacy/cafe/park counts added to the existing OpenStreetMap POI sync (same connection, same license, no new external dependency), shown as an informational walking-distance stat. Deliberately *not* folded into the Match Score weighting, to avoid disturbing the already-verified scoring engine.
6. **Similar neighborhoods elsewhere** — a same-archetype cross-destination suggestion, computed entirely from data already in the plugin's own database.

### Destination/neighborhood dataset growth
- **OSM-assisted Neighborhood Discovery** (Voyasee Where to Stay > Neighborhood Discovery): queries OpenStreetMap for a destination's named `place=suburb/neighbourhood/quarter` areas and creates **draft** rows — never auto-published, never quiz-visible (`get_neighborhoods_for_destination()` now filters to `discovery_status = 'published'`). An admin reviews each draft, sets a real archetype, and writes why_fits/why_caution by hand before publishing, identical to adding a neighborhood any other way. Deliberately does not attempt to auto-guess an archetype from a secondary POI query, to keep the discovery step itself fast and avoid multiplying Overpass load per destination.
- A destination now carries an ISO 3166-1 alpha-2 `country_code`, auto-backfilled on activation from the existing free-text `country` field via a bundled, public-domain name→code table. Never overwrites a code that's already set; a CSV re-import that omits the column no longer wipes an existing code.

## Architecture notes

- Both sibling-plugin integrations follow the exact `function_exists()` soft-dependency pattern already established in this codebase (the pre-existing, previously-broken weather stub) — nothing errors or looks broken if Weather Bridge/Country Intelligence aren't installed, or if a destination has no country code.
- New sync jobs (`WTSM_Landmark_Sync`) mirror the existing `WTSM_Boundary_Sync`/`WTSM_Photo_Sync` pattern exactly: small daily batches, never called on a live visitor request, a documented User-Agent, admin-triggerable.
- `WTSM_Neighborhood_Discovery` is admin-triggered only (not cron-scheduled) — an occasional "expand this destination" action, not routine maintenance.
- New DB columns (`country_code`, `poi_supermarket_count`/`poi_pharmacy_count`/`poi_cafe_count`/`poi_park_count`/`convenience_score`, `nearby_landmarks`/`landmarks_last_synced`, `discovery_status`/`discovery_source`) all ship via the existing `dbDelta()` version-bump upgrade path.

## Verification

- `php -l` clean on all PHP files, `node --check` clean on `matcher.js`, CSS brace balance verified.
- A standalone PHP harness (mocked WP functions) exercised the country-code lookup table, the forecast-vs-climate-normals date-branching logic, and the holiday-date-range overlap math — all passed.
- A standalone Node harness exercised the client-side jet-lag UTC-offset computation against five real IANA time zones (including a half-hour-offset zone, India) and an invalid zone — all correct.
- No live WordPress install, and neither sibling plugin installed/activated, was available in this environment — the exact nested JSON field names inside Country Intelligence's `travel`/`safety` objects were reconstructed from a prior code-reading pass, not verified against a live response, which is why the Practical Facts panel reads those fields defensively (multiple candidate paths, silent omission on a miss) rather than assuming one exact shape.

## Known limitation to flag

The Practical Facts panel's plug-type/tipping-guidance lines depend on guessed nested field names for Country Intelligence's `travel.*` object and may not render even when Country Intelligence is active and has the data, if the real key names differ from what was guessed. Driving side, currency, and emergency numbers use field names the original analysis quoted with higher confidence and are more likely to work correctly out of the box. If any Practical Facts line doesn't appear, the underlying data is very likely still there — it's a key-name mismatch, not a functionality gap — worth a quick console check against a real Country Intelligence response if it matters.
