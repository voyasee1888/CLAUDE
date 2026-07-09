# Voyasee Best Area to Stay Finder 5.1.0 — focused data expansion (Phase 1)

## What was requested

After v5.0.0 shipped the code upgrades, the honest gap was that Phase 1 of the
approved blueprint — actually adding more real destinations and neighborhoods —
had never been applied (the background agent assigned to it failed). The scope
chosen for this release was a **focused** expansion: bring the top ten cities up
to six real areas each, and expand the three flagged additional cities (Athens,
Marrakech, Hanoi).

The overriding rule, as always: every neighborhood must be a real, genuinely
existing area — no invented profiles.

## What shipped

**29 real neighborhoods added; 3 generic placeholders removed; net +26.**
The starter dataset goes from 156 destinations / 463 neighborhoods to
**156 destinations / 489 neighborhoods.**

Top ten cities, each brought to six real areas (added areas in brackets):

- **Tokyo** — [Ueno, Kichijoji]
- **Paris** — [Latin Quarter, Batignolles]
- **London** — [South Kensington, Greenwich]
- **New York** — [Upper West Side, Financial District]
- **Bangkok** — [Khao San Road, Ari]
- **Barcelona** — [Gràcia, Poble Sec]
- **Rome** — [Via Veneto, Parioli]
- **Istanbul** — [Şişli, Üsküdar]
- **Dubai** — [Jumeirah, Bur Dubai]
- **Singapore** — [Little India, Sentosa]

Additional cities:

- **Athens** — [Koukaki, Exarchia, Glyfada] (now 6 real areas)
- **Marrakech** — [Hivernage, Kasbah] (now 5 real areas)
- **Hanoi** — three generic placeholder rows ("Hanoi City Center", "Hanoi
  Business District", "Hanoi Quiet Residential Area") replaced with four real
  neighborhoods: [Old Quarter, Hoàn Kiếm, Tây Hồ / West Lake, Ba Đình].

## Archetype gaps deliberately filled

The two thinnest archetypes — which meant certain quiz answers had almost
nowhere real to land — are now much healthier:

- **family_suburban: 1 → 8** (Kichijoji, Batignolles, Greenwich, Upper West
  Side, plus earlier additions)
- **budget_backpacker: 9 → 16** (Ueno, Latin Quarter, Khao San Road, Poble Sec,
  Little India, Exarchia, Old Quarter, and more)

## How the data was authored

- Coordinates are real for each named area; distances to the city centre and
  airport are computed by haversine from the destination's own coordinates
  (the same method used across the existing dataset), not hand-guessed.
- `best_for`, `why_fits`, `why_caution` and `local_tip` are genuine,
  area-specific editorial copy — the fields travelers actually read — not
  generic filler.
- `hero_image_url` / `hero_image_credit` left empty (filled by the Pexels photo
  sync), `data_tier` = 1, `last_reviewed` = 2026-07-09.

## Verification performed

- CSV re-parsed with a strict reader: **489 data rows, all 21 fields, zero
  malformed rows, zero duplicate (slug, name) pairs.**
- Per-city counts confirmed (top ten all at 6; Athens 6, Marrakech 5, Hanoi 4).
- `php -l` clean on the two touched PHP files.

## Known follow-up (surfaced, not yet done)

A dataset audit during this work found that **242 of the neighborhoods (about
half) across smaller cities are still generic placeholder profiles** of the form
"<City> City Center / Business District / Quiet Residential Area" — e.g.
Edinburgh, Dublin, Copenhagen, Zurich, Munich, Florence, Kraków. These are not
real named areas. Hanoi's were converted here as a first step; converting the
rest to real neighborhoods is the natural next phase and is flagged for a
decision on scope.

## Upgrade note

Schema and shortcode unchanged. On an in-place upgrade (without deleting the
plugin), pull in the new neighborhoods from **Best Area to Stay Finder → Import
CSV → "Add any new starter destinations"** — safe to run anytime; existing data
is never overwritten.
