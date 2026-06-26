# Voyasee BagFit 7.5.0 — Deep-Verified Tier Expansion (50 → 70)

## What changed

20 airlines promoted from Tier 2 (core_source_linked) to Tier 1 (deep_verified), closing real geographic gaps: this was the first time any African carrier reached deep-verified status, and Chinese, Southeast Asian, South American, and Middle Eastern coverage all thickened meaningfully.

**The 20 promoted:** China Eastern Airlines, Air China, AirAsia, Thai Airways, Vietnam Airlines, Philippine Airlines, Cebu Pacific, Ethiopian Airlines, EgyptAir, Kenya Airways, Royal Air Maroc, Avianca, GOL Linhas Aéreas, Azul Brazilian Airlines, Copa Airlines, flydubai, Oman Air, Air New Zealand, Norwegian, SAS.

**Deep-verified tier is now 70 airlines** (28% of the dataset). Tier counts: 70 deep-verified, 89 core source-linked, 91 directory.

## What verification found

**6 of 20 were already perfectly accurate** (Air China, Kenya Airways, Azul, Copa Airlines, SAS, and Avianca's cabin bag) — confirming the original data collection held up under scrutiny. **14 needed corrections**, several of them substantive:

| Airline | Issue | Fix |
|---|---|---|
| EgyptAir | Cabin dimensions (58×45×25cm) didn't match any corroborated source | Corrected to 55×40×23cm, the well-sourced figure |
| Royal Air Maroc | Cabin height wrong (20cm vs official 25cm) | Corrected to 55×40×25cm per royalairmaroc.com directly |
| GOL Linhas Aéreas | Cabin width wrong (40cm vs official 35cm) | Corrected to 55×35×25cm per voegol.com.br directly |
| China Eastern | Stored the domestic-route figure, not the more broadly relevant international one | Switched to 56×45×25cm with 115cm linear cap |
| Oman Air | Had an unverified fixed-box guess | Replaced with the airline's actual published rule: 115cm total linear, no fixed box |
| flydubai | Personal item didn't match the airline's own page | Corrected to 33×25×20cm |
| **AirAsia, Philippine Airlines, Cebu Pacific, Air New Zealand, Norwegian, Vietnam Airlines** | **Combined-weight modeling bug** | Same pattern caught in JAL/ANA/Korean Air/BA/Air France/KLM previously — these airlines share one weight limit across the cabin bag and personal item, but it was stored as a per-bag limit. Fixed using the engine's `combined_weight_g` mechanism. |

That brings the total count of airlines using the combined-weight fix to **13** across this and the prior release — confirmed as a genuinely common pattern worth checking on every future promotion, not a one-off.

Smaller additions: published personal-item weights/dimensions were added where the official source gives them but the data didn't yet (Ethiopian Airlines' 5kg personal-item cap, Thai Airways' 37.5×25×12.5cm/1.5kg spec, Royal Air Maroc's 40×30×15cm/2kg accessory, Philippine Airlines' 45×35×20cm personal item, China Eastern's 35×32×18cm underseat spec — this last one came directly off ceair.com itself).

## Verification method

Every figure was checked against the airline's own official baggage page where one exists, cross-referenced against 3+ independent secondary sources before accepting a number, and never invented where the airline doesn't publish one (Copa Airlines' personal item and Ethiopian's personal-item dimensions remain unset for exactly this reason — the airlines don't publish them).

The combined-weight fixes were specifically verified by running the real engine against the real data: an AirAsia cabin+personal split totaling 8kg correctly fails the 7kg combined cap while 6kg passes; Vietnam Airlines' 13kg correctly fails its 12kg cap while 10kg passes; the same pattern confirmed for Philippine Airlines, Cebu Pacific, Air New Zealand, and Norwegian.

## Full verification performed before release

- PHP 8.3 `php -l` clean on all files
- `node --check` clean on app.js
- JSON structure valid, 250 airlines, schema intact
- 8 targeted combined-weight test cases, all passing with expected fail/pass results
- Full stress test: all 250 airlines × 3 bag types (750 combinations) run through the real engine with **0 errors**
- Confirmed exactly 70 airlines now carry `coverage_tier: deep_verified` with matching `confidence: high`

## Not touched in this release

No airlines outside this batch of 20 were modified. No checked-baggage data changed. The remaining 89 core_source_linked and 91 directory-tier airlines are unaffected.
