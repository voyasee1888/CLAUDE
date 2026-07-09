# Voyasee Best Area to Stay Finder 5.3.0 — 200 destinations, all real

## What was requested

Grow the dataset to around 180–200 places, with 3–4 real neighborhoods per
place, researched properly so the additions are good, well-known destinations
and genuinely existing neighborhoods — then hand back the updated totals.

## What shipped

**The dataset went from 156 to 200 destinations and from 488 to 641
neighborhoods**, and every destination now has at least three real
neighborhoods (previously 16 had only two).

### 1. 44 new real destinations (→ 200 total)

Each new city was given 3–4 real, named neighborhoods with correct
coordinates, realistic price/safety tiers and genuine editorial copy. By
region:

- **Europe (13):** Bruges, Salzburg, Split, Granada, Bologna, Gdańsk, Vilnius,
  Bergen, Gothenburg, Antwerp, Bordeaux, Nuremberg, Palermo.
- **Asia (13):** Jakarta, Hoi An, Yogyakarta, Bengaluru, Varanasi, Udaipur,
  Phnom Penh, Luang Prabang, Pokhara, Kota Kinabalu, Hiroshima, Nara, Kandy.
- **Middle East (3):** Jerusalem, Riyadh, Jeddah.
- **Africa (5):** Fez, Johannesburg, Luxor, Dar es Salaam, Mombasa.
- **Americas (8):** Boston, Washington DC, San Diego, Austin, Nashville, Panama
  City, Oaxaca, Arequipa.
- **Oceania (2):** Adelaide, Cairns.

Examples of the real neighborhoods added: Jerusalem (Old City, City Center,
German Colony); Bruges (Historic Centre, 't Zand, Langerei); Hiroshima (Peace
Memorial Park, Hondori, Ujina); Boston (Back Bay, Beacon Hill, North End,
Cambridge); Jakarta (Kota Tua, Menteng, Kemang, Sudirman/SCBD).

### 2. Topped up 16 thin cities to a minimum of three

The 16 destinations that had only two neighborhoods each got one more real
area, so no destination is below three:

- Los Angeles (+Downtown LA), San Francisco (+Union Square), Chicago (+Lincoln
  Park), Miami (+Wynwood), Las Vegas (+Summerlin), Rio de Janeiro (+Santa
  Teresa), Buenos Aires (+San Telmo), Cape Town (+V&A Waterfront), Bali
  (+Sanur), Cancún (+Puerto Cancún), Delhi (+Paharganj), Kuala Lumpur
  (+Chinatown/Petaling Street), Kyoto (+Kyoto Station), Shanghai (+Lujiazui),
  Beijing (+Chaoyang), Phuket (+Phuket Old Town).

## How the data was authored

- Every area is a real, existing neighborhood — no invented names.
- Coordinates are correct per area; distance-to-centre and distance-to-airport
  are computed by haversine from each destination's own coordinates, exactly as
  the rest of the dataset is built.
- price_band, safety_tier and family/solo suitability set realistically.
- best_for / why_fits / why_caution / local_tip are genuine, specific,
  travel-useful copy — not filler.

## Safety of the migration

The build script ran a dry-run first and refused to proceed on any duplicate
destination slug or duplicate (destination, neighborhood) pair. Result: **0
duplicate destinations, 0 duplicate neighborhoods, 0 problems.**

## Verification performed

- Destinations CSV: **200 rows, 11 fields each, no malformed rows, no duplicate
  slugs.**
- Neighborhoods CSV: **641 rows, 21 fields each, no malformed rows, no duplicate
  (slug, name) pairs.**
- Every one of the 200 destinations has neighborhoods; **minimum 3, maximum 6**
  per destination; none below three.
- Archetype spread stays realistic: historic 150, nightlife 109, business 100,
  residential_quiet 99, luxury 64, budget_backpacker 59, beach 41,
  family_suburban 19.
- `php -l` clean on the touched PHP file; readme setup text, "Data sources"
  section, and count references all updated to 200 / 641.

## Final totals

| | Count |
|---|---|
| Destinations (places) | **200** |
| Neighborhoods (areas) | **641** |
| Countries | 85 |
| Neighborhoods per place | min 3, max 6 |

All real — no placeholders, no invented profiles, anywhere in the dataset.
