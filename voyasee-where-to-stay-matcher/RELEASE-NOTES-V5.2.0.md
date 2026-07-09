# Voyasee Best Area to Stay Finder 5.2.0 — every placeholder replaced with a real neighborhood

## What was requested

"Upgrade them all" — convert the remaining generic placeholder neighborhoods
(the "<City> City Center / Business District / Quiet Residential Area" style
auto-generated entries) into real, genuinely existing named areas, and finish
anything else still pending. This closes out the "real neighborhoods only, no
fake profiles" goal for the whole starter dataset.

## The problem this fixes

A dataset audit found that **313 of the ~489 starter neighborhoods (over half)
were generic placeholders** — auto-generated names like "Edinburgh Quiet
Residential Area", "Havana City Center", "Osaka Business District", "Colombo
Beachfront". They existed only so every destination had *something*, but they
are exactly the invented/placeholder profiles the project is meant to avoid,
and they skewed the matcher (every city looked like it had the same three
archetypes).

## What shipped

**All 313 placeholders removed; 312 real, named neighborhoods added; net dataset
now 156 destinations / 488 neighborhoods, 100% real.**

Cities were rebuilt region by region, each with 2–4 genuine areas of good
archetype spread. A sample of what replaced the placeholders:

- **Western/Northern Europe** — Edinburgh (Old Town, New Town, Leith),
  Copenhagen (Indre By, Vesterbro, Nørrebro), Munich (Altstadt, Schwabing,
  Maxvorstadt), Reykjavik (Miðborg, Old Harbour, Vesturbær).
- **Southern Europe** — Florence (Centro Storico, Oltrarno, San Lorenzo),
  Naples (Centro Storico, Chiaia, Vomero), Porto (Ribeira, Baixa, Cedofeita).
- **Eastern Europe/Balkans** — Kraków (Stare Miasto, Kazimierz, Podgórze),
  Tallinn (Vanalinn, Kalamaja, Rotermann), Belgrade (Stari Grad, Dorćol, Vračar).
- **Asia** — Osaka (Namba, Umeda, Shinsekai), Chiang Mai (Old City, Nimman,
  Riverside), Kathmandu (Thamel, Durbar Marg, Boudha), Jaipur (Pink City,
  C-Scheme, Bani Park).
- **Middle East/Africa** — Amman (Al-Balad, Jabal Amman, Abdoun), Tel Aviv
  (Jaffa, Florentin, Neve Tzedek), Zanzibar City (Stone Town, Shangani,
  Michenzani), Marrakech-area and Cairo already real.
- **Americas/Oceania** — Havana (Habana Vieja, Vedado, Centro Habana), Cartagena
  (Centro Histórico, Getsemaní, Bocagrande), Montreal (Vieux-Montréal, Plateau,
  Downtown), Melbourne (CBD, Fitzroy, St Kilda), plus Malé (Malé City,
  Hulhumalé, Villingili).

## How the data was authored

- Every area is a real, existing neighborhood — no invented names.
- Coordinates are correct for each area; distance-to-centre and
  distance-to-airport are recomputed by haversine from each destination's own
  coordinates, exactly as the rest of the dataset is built.
- price_band, safety_tier, family/solo suitability set realistically per area.
- best_for / why_fits / why_caution / local_tip are genuine, specific,
  travel-useful copy — the fields visitors actually read — not filler.
- Two areas that *looked* like placeholders but are genuinely named that way —
  **Dubai Marina** and **Amsterdam Zuid** — were deliberately kept.

## Safety of the migration

The replacement script ran a dry-run first and refused to proceed if any
destination would be left with zero neighborhoods or if any add collided with
an existing real area. Result: **0 empty destinations, 0 duplicate collisions.**

## Verification performed

- CSV re-parsed with a strict reader: **488 data rows, all 21 fields, zero
  malformed rows, zero duplicate (slug, name) pairs.**
- Independent placeholder sweep: **zero generic placeholders remain** (the only
  names ending in "Old Town" are the real Edinburgh and Dubrovnik Old Towns).
- Every one of the 156 destinations still has at least two neighborhoods.
- Archetype distribution now realistic: historic 112, nightlife 89, business
  77, residential_quiet 68, luxury 50, budget_backpacker 45, beach 34,
  family_suburban 13 (previously dominated by the three placeholder archetypes).
- `php -l` clean on the touched PHP file; readme "Data sources" section updated
  to reflect that the dataset is now fully real.

## Result

Combined with 5.0.0 (accuracy fixes + Area DNA + POI facts + infographic result
page) and 5.1.0 (top-city expansion), the tool now runs on a fully real,
genuinely useful neighborhood dataset end to end — no placeholders, no invented
profiles, everywhere in the world it covers.
