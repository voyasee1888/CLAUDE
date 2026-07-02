# Voyasee Where to Stay Matcher 4.2.0 — destination quality, air quality, corrections, and comparing destinations

## What was requested

After confirming the v4.1.2 fix worked, the ask was a deep, honest look at what the
*next* upgrade should be — more destination/neighborhood coverage (especially for
the world's most-searched cities), more useful data on the results page, and
anything else worth building next — followed by "upgrade all": implement
everything recommended, safely, using only already-integrated free/commercial-safe
sources, with the same no-fabrication discipline as every prior release.

## What shipped

### 1. Fifteen Tier 2 → Tier 1 destination upgrades

A count of the starter dataset's countries showed real geographic gaps (Central
America and most of the Caribbean entirely missing, most of Africa and Central
Asia down to a single city), but a bigger discovery: a lot of the *already-listed*
Tier 2 ("generic zone") destinations are some of the most globally searched cities
in the world -- Los Angeles, San Francisco, Chicago, Miami, Las Vegas, Rio de
Janeiro, Buenos Aires, Cape Town, Bali (Denpasar), Kuala Lumpur, Cancun, Delhi,
Kyoto, Shanghai, and Beijing were all still showing "City Center" / "Beachfront"
placeholder zones rather than real neighborhoods. Upgrading these to genuine,
named neighborhoods (2 each, 30 total) is higher-impact than adding obscure new
countries, since these are exactly the destinations most visitors are likely to
search for.

- `sample-data/sample-destinations.csv`: these 15 destinations flipped from
  `tier=2` to `tier=1`.
- `sample-data/sample-neighborhoods.csv`: the 45 old generic-zone rows for these
  destinations were removed and replaced with 30 real neighborhoods (Hollywood/
  Santa Monica for LA, Fisherman's Wharf/Mission District for SF, Copacabana/
  Ipanema for Rio, and so on) -- each with real archetype, honest why_fits/
  why_caution, and a local tip, in the same style and rigor as the original 36
  Tier 1 destinations. Distances to center/airport were computed via haversine
  from each neighborhood's actual coordinates rather than guessed.
- **New: `WTSM_Tier1_Upgrade`** -- a self-running, one-time migration
  (`includes/class-wtsm-tier1-upgrade.php`). A CSV re-import can only ever add
  rows, never remove ones a site already has, so a site that already seeded the
  old generic zones for these 15 destinations needed an explicit cleanup step.
  This migration deletes only the old generic-zone rows whose why_caution still
  contains the literal, distinctive auto-generated disclaimer text -- if a site
  owner has since hand-edited one of those rows (even just to fix a typo), it no
  longer matches and is left completely untouched, so nothing anyone has
  customized can ever be silently replaced. It then re-runs the exact same
  starter-dataset import the existing "Add any new starter destinations" admin
  button already uses. Runs once automatically on `plugins_loaded` after
  updating, gated by its own option flag -- no manual step needed.
- Destination/neighborhood totals: 156 destinations unchanged; 51 are now Tier 1
  (was 36), 105 remain Tier 2 (was 120); total neighborhoods 463 (was 478 -- net
  down because 3 generic zones per destination became 2 real ones, which is the
  right tradeoff for genuine detail over placeholder count).

### 2. Air Quality fact

The Voyasee Weather Bridge sibling plugin exposes an air-quality lookup that was
already available but never actually called anywhere in this plugin. Added
`voyasee_ni_maybe_get_air_quality()` (function_exists-gated, same pattern as the
existing weather/country-intel integration points) and wired it into the `/match`
REST response and a new `buildAirQualityFact()` chip in the Trip Facts strip.
Since this plugin doesn't own or fully know Weather Bridge's exact index scale,
the fact shows the source's own number and category label as-is rather than
inventing a good/moderate/unhealthy banding from the raw number -- the same
"don't guess what we can't confirm" handling already used for Country
Intelligence's currency data.

### 3. "Suggest a correction"

Each neighborhood's detail panel now has a small "Notice something outdated?
Suggest a correction" link that opens an inline form (message + optional email).
Submissions go to a new rate-limited (5/hour/IP) REST endpoint
(`POST /wtsm/v1/report-issue`) which emails the site admin via `wp_mail()` --
nothing is ever written to the database or auto-applied from an anonymous
submission; a human always decides what to do with it, the same trust model as
every other editorial field in this plugin.

### 4. Compare with another destination

The results page now has a "Compare with another destination" section: pick a
second city (same autocomplete search used in Step 1) and see its top match
side-by-side with the current destination's top match, using the exact same
quiz answers already given. Built entirely on the client side against the
existing `/match` endpoint -- no new backend route, no matching-engine or
scoring changes, and no risk to the weight-sum invariant since nothing in
`class-wtsm-matching-engine.php` was touched this release.

## What was deliberately not done

- No new paid API keys, and no data invented where a real source doesn't exist
  (still no cost-of-living breakdowns, no invented accessibility scores, no
  GTFS transit feeds) -- consistent with every prior release's discipline.
- Didn't chase the raw geographic gaps (Central America, Caribbean, Central Asia)
  this pass -- upgrading already-popular Tier 2 destinations to Tier 1 was judged
  higher-impact for "the world's top destinations" than adding new, less-searched
  countries. Worth a future pass on its own.

## Verification

- `php -l` clean on every PHP file (full sweep).
- `node --check` clean on `matcher.js`.
- CSS brace balance verified (349 open / 349 close).
- Matching engine (`class-wtsm-matching-engine.php`) was not touched this
  release -- the weight-sum invariant test was not re-run since there is nothing
  in this release that could affect it.
- Both edited starter CSVs re-parsed and validated column-count-consistent after
  editing; confirmed each of the 15 upgraded destinations ends up with exactly 2
  real neighborhoods and zero leftover generic-zone rows.
- Traced the `WTSM_Tier1_Upgrade` deletion query against the exact schema/marker
  text used by the original generic-zone seeding to confirm it only ever matches
  still-default rows.
- No live WordPress install was available in this environment to visually
  confirm the rendered air-quality chip, correction form, or destination-compare
  UI, or to exercise the migration against a real already-seeded database --
  recommend spot-checking those three after updating.
