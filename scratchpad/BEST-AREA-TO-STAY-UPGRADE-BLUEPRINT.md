# Voyasee Best Area to Stay Finder — Upgrade Blueprint

**Status:** PLAN ONLY. No code changed. This is the pre-work audit + phased plan for your approval.
**Scope reviewed:** every PHP class, the matching engine, the data (156 destinations / 463 neighborhoods), the REST payload, the result-page renderer (matcher.js), and all 6 data-sync/intelligence layers.

---

## PART A — Audit: what works, and where the gaps are

### A1. What already works well (keep, don't touch)

- **The scoring engine is honest and explainable.** 7 weighted dimensions (budget, vibe, attractions, walkability, airport, suitability, safety), weights always sum to 1.0, every score is narratable. This is the plugin's soul — the plan builds on it, never replaces it.
- **The data is real, not fake.** Spot-checked the big cities: Tokyo → Shibuya/Asakusa/Shinjuku/Ginza; Paris → Le Marais/Saint-Germain/Montmartre/La Défense; London → Covent Garden/Notting Hill/Shoreditch. These are genuine, well-known areas. No invented neighborhoods found.
- **The confidence/"Not synced" system is genuinely trustworthy.** It refuses to praise a dimension whose data hasn't been synced, and says so in plain language instead of faking a number. Rare and valuable — keep it.
- **Every data source is already free AND commercially licensed** (this is important for your ad/affiliate constraint):
  - OpenStreetMap Overpass — ODbL, commercial OK with attribution → POI density → walkability/nightlife/transit/convenience.
  - Wikipedia GeoSearch — titles/metadata only, commercial OK → nearby landmarks.
  - OSM boundary polygons — ODbL → real map shapes.
  - Frankfurter (ECB rates) — free, no key, commercial OK → live currency.
  - NASA POWER (via Weather Bridge) — free, keyless → climate normals.
  - Pexels — commercial OK (needs a key) → photos.
  - There is **zero** paid-API or license-violating dependency in the stack today.

### A2. Weaknesses & bugs found (prioritized)

**Data-accuracy bugs (fix first — these make the result page wrong):**

1. **Currency nightly-price ignores destination cost.** The budget *score* adjusts each area's price band by the destination's `cost_index` (an expensive city reads pricier), but `WTSM_Currency::estimate_nightly_range()` uses flat global USD anchors. Result: a "$$$" area in Tokyo and a "$$$" area in Hanoi show the *same* converted nightly range. That's a visibly wrong number on the result page. → feed `cost_index` into the estimate.
2. **"Museums/culture/historic" attraction fit is proxied by `walkability_score`.** (engine line ~474). Walkability is not a culture signal. Meanwhile `poi_attraction_count` — the actual museum/landmark density we already fetch — is stored but never used in scoring. → score culture interest off the real attraction POI count.
3. **Photos can misrepresent the area.** Pexels is searched with `"{neighborhood} {destination} street"`, which frequently returns generic stock that isn't actually that place. A wrong photo is worse than an honest colored block. → prefer real geolocated photos (Wikimedia Commons geosearch, CC-licensed) and clearly label any generic photo as "representative."

**Coverage limitations (the biggest opportunity):**

4. **Only ~3 neighborhoods per city (avg 2.97).** Even Tokyo has just 4 areas; real "where should I stay in Tokyo" needs Ueno, Roppongi, Ikebukuro, Shimokitazawa, Odaiba, etc. This is the #1 thing holding the tool back from being genuinely useful.
5. **Families & backpackers are under-served in the data.** `family_suburban` archetype = **1** neighborhood in the entire dataset; `budget_backpacker` = 9. The engine handles these personas well, but there's almost no data for them to match against.
6. **Safety barely moves the ranking.** `safety_tier` is compressed to 4–5 for ~80% of areas, the score returns a flat 100 whenever actual ≥ desired, and the weight is only 0.05. So "safety comfort" is effectively cosmetic. Either differentiate the data or be honest about what it means.
7. **Out-of-the-box, every hero image is empty** (all 463) and POI scores default to a neutral 50 until the cron syncs run. A fresh install therefore looks half-populated ("Not synced" everywhere, no photos) until batches complete — reads as "broken" to a new user.

**Smaller gaps:** only 156 destinations; no first-class "nearest metro station" distance shown (we have the POI data but don't surface it); no neighborhood-level seasonal/event awareness.

---

## PART B — The upgrade blueprint (phased)

### Phase 0 — Correctness pass (do first, low risk)
- Fix the currency/cost-index bug (#1).
- Fix culture attraction scoring to use `poi_attraction_count` (#2).
- Make the safety dimension actually differentiate (spread the data, or relabel honestly) (#6).
- Verify weight-sum invariant still holds (96-combination test) after any engine change.

### Phase 1 — Data depth: more real neighborhoods & destinations (biggest win)
- **Target ~6–10 real areas for the top ~60 cities**, 3–5 for the rest. Grow 463 → ~900–1,100 neighborhoods, all genuine.
- Use the **existing OSM discovery tool** (already built) to pull real candidate area names, then fill editorial fields (archetype, why_fits, why_caution, price/safety) — the honest, human-reviewed way the plugin already works. No auto-publishing.
- **Deliberately seed the thin personas:** add real `family_suburban` areas (e.g. residential family districts) and `budget_backpacker` areas (real hostel districts) so families and backpackers get true matches.
- Add ~40–60 more destinations where OSM boundary/POI coverage is strong.

### Phase 2 — Result-page accuracy & informativeness
- **Surface the real POI data we already fetch** as concrete facts: "42 restaurants + 9 bars within 800m," "nearest metro ~350m," "supermarket + pharmacy on-site" — turns the abstract 0–100 scores into things a traveler can verify.
- **"Getting around" mini-panel** per area from transit POIs (real station proximity), replacing a bare walkability number.
- Keep every new fact under the same confidence rule: only show it when the sync behind it actually ran.

### Phase 3 — New intelligence layer (own + free third-party, all license-clean)
All optional, all free, all commercial-use-OK, none conflict with ads/affiliates:
- **Wikimedia Commons geosearch** → real geolocated, CC-licensed photos of the actual area (accuracy fix for #3).
- **Extend OSM Overpass** (already wired) to fetch: nearest metro/rail station, hospital/24h-pharmacy proximity, lodging (hotel/hostel) density as an availability signal.
- **Our own "Area DNA" synthesis** — no new source, just smarter use of data we already have: human-readable fingerprint tags ("late-night friendly," "quiet after dark," "great for car-free trips") derived transparently from the real POI ratios. This is the own-intelligence system you invited, and it stays 100% explainable.
- **GeoNames** (helper already present, CC BY) for population/admin context on new destinations.

### Phase 4 — Design, infographic & footer
- Rework the match card into a cleaner infographic: score ring + top-3 reason chips + the new concrete POI facts + a mini "fit vs. your answers" bar, instead of leaning only on the radar chart.
- Turn the Trip Facts strip into proper infographic tiles (weather, currency, holiday, air quality) with icons and confidence dots.
- A clearer "why NOT here" treatment for the Wrong Area Warning (the signature feature) so it stands out as a benefit, not a footnote.
- Footer: apply the same icon-card system already shipped in v4.2.3, tidied and consistent with the new result-page visual language.

### Phase 5 — Ship discipline (every change)
`php -l` + `node --check` + CSS brace balance + weight-sum invariant test + version bump + changelog + release notes + zip rebuild + commit + push. Same as every prior release.

---

## Suggested sequencing for your decision
1. **Phase 0 + the Phase 2 POI-facts surfacing** give the fastest visible accuracy/quality jump for the least risk.
2. **Phase 1 data depth** is the highest-value but most time-intensive (real editorial work per area).
3. **Phase 3/4** are enhancement layers once the data is solid.

Nothing here is built yet. Tell me which phases to green-light (and in what order) and I'll start with a detailed spec for that phase before writing code.
