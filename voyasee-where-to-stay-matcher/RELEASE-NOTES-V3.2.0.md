# Voyasee Where to Stay Matcher 3.2.0 — Accuracy fixes, data-confidence system, results-page polish

## What was reported

A live site screenshot showed the comparison scorecard rendering "Not synced" as broken, stacked text fragments ("Not" / "synce" / "d"), match cards showing flat archetype colors instead of photos, and a general request to audit the whole plugin for bugs, verify result accuracy, and improve the results page's design.

## Root causes found (beyond the reported symptom)

Reading the full matching engine, sync jobs, REST API, and frontend renderer surfaced that the visible "Not synced" bug was a symptom of a deeper issue: **the plugin's honesty mechanism for unsynced OpenStreetMap data was implemented in only 2 of 5 places it needed to be**, and the score computation itself had two real accuracy bugs.

1. **Match Score could exceed 100 and over-weight walkability on 8+ night trips.** `effective_weights()`'s long-trip adjustment added 0.08 to the walkability weight but only subtracted (a floored) 0.06 from airport — every other modifier in that function nets to zero; this one didn't, silently breaking the "weights sum to 1.0" invariant documented in the code.
2. **Unsynced neighborhoods' walkability/nightlife scores (a DB default of 50) were scored, blended, and described as if real**, with no disclosure, in the two most prominent places a visitor looks: the match-card score badge and the radar chart. The auto-generated "why this fits" / Wrong Area Warning copy could describe an unsynced area as having "lower walkability" purely by coincidence of the neutral default landing below the warning threshold — an honesty bug, not just a missing badge.
3. **A partial OpenStreetMap sync failure could get silently recorded as confirmed data.** If 3 of 4 Overpass category requests succeeded and one failed, the failing category was zeroed out and the neighborhood still got stamped as "synced" — a confidently-wrong 0 is worse than staying unsynced and retrying.
4. The destination-wide "data tier" disclaimer was read from whichever neighborhood happened to sort first alphabetically, not the destination's own tier.
5. Forcing a photo re-sync ordered candidates by an unrelated OpenStreetMap sync timestamp (no `photo_last_synced` column existed at all).
6. The overview map could silently drop pins for neighborhoods missing coordinates, and its legend was built from an unfiltered list — so the legend could show an archetype with no matching pin, reading as a rendering glitch.
7. A document-level click listener was re-added on every Back/Start Over navigation and never removed (a real memory leak on repeated use).
8. A neighborhood photo URL was interpolated into an inline `style` attribute with only ad hoc quote-stripping instead of full escaping.

## Fix approach

- **Backend**: `WTSM_Matching_Engine` now computes an explicit per-dimension `confidence` array (plus a convenience `any_unsynced` flag) once, server-side, mirroring the exact conditions used in scoring — this is now the single source of truth every UI surface reads, instead of each frontend function independently re-deriving it from a raw timestamp. The long-trip weight shift is now computed as `min(0.08, airport_weight - floor)` so it always nets to exactly zero. The OSM sync now aborts (and retries next batch) on any partial category failure rather than saving a mix of real and zeroed counts. `data_tier` now reads `destination.tier` directly. A new `photo_last_synced` column tracks photo-sync history correctly.
- **Frontend**: all five score surfaces (match-card score badge, radar chart, comparison scorecard, Trip Reality strip, and the dynamic why-fits/why-caution copy) now consistently disclose an estimate using the same backend flag and the same visual language — a dashed ring + "est." badge on the score, hollow chart points, and a pending-pill in the comparison table. The comparison table's pending row now uses its own 2-column layout instead of squeezing a 10-character label into a ~42px column sized for a numeric score, which was the direct cause of the reported text-wrap bug. The overview map and its legend now share one filtered coordinate list, missing-coordinate neighborhoods get a small caption instead of vanishing silently, and overlapping/duplicate coordinates fan out with a small spiral offset instead of fully occluding each other. The outside-click listener is now bound once instead of on every render.
- **Visual polish**: match cards reveal with a staggered fade-in and the score ring/number count up on first view (both skip straight to the end state under `prefers-reduced-motion`, and default to fully visible so a JS error can't leave cards permanently hidden); refined card depth via layered shadows, fluid heading sizes via `clamp()`, and tabular number alignment throughout.

## Verification

- `php -l` clean on all 25 PHP files.
- `node --check` clean on `matcher.js`; CSS brace balance verified (262/262).
- A standalone Reflection-based harness swept `nights` × `early_departure`/`luggage_amount`/`walkability_importance` toggle combinations (63 total) confirming `effective_weights()` always sums to exactly 1.0 with no negative weights, and separately verified `dimension_confidence()`'s branching against synced/unsynced × POI-dependent/independent interest combinations.
- An end-to-end harness ran `WTSM_Matching_Engine::match()` against a 3-neighborhood mock destination (one fully synced, two never-synced) across a normal trip and a 21-night trip, confirming: match scores stay within 0-100, `data_tier` reads from the destination, `confidence`/`any_unsynced` flags match each neighborhood's actual sync state, and an unsynced weakest-dimension caution uses the honest "not synced" substitute rather than a fabricated weakness claim. All checks passed.
- Manual code review confirmed no other file references the removed dead `travel_date`/weather code path, and no other CSS/JS references the removed unused `.vwtsm-compare-row` class.

No live WordPress install was available in this environment; the schema change (`photo_last_synced`) relies on the existing version-bump → `dbDelta()` upgrade path already used for every prior schema change in this plugin, which is idempotent and safe to apply on top of an existing install.
