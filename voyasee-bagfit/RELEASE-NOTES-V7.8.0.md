# Voyasee BagFit 7.8.0 — Roadmap Complete: Confidence Badges, Fare-Class Expansion, Progressive Disclosure

This release finishes the three remaining items from the deep audit roadmap, completing the full sequence that started with the family-bug fix and country-gap expansion in 7.7.0.

## 1. Confidence-tier visibility on the result itself

Previously, the deep-verified / source-linked / directory distinction only showed up on the *input form*, before checking. A user could get a PASS on British Airways (individually verified) and a PASS on a small regional carrier (never independently re-confirmed), and both looked identical on the result page.

Now every result shows a confidence badge in two places:
- Next to the strictest airline in the main verdict summary
- On **each individual flight leg** in the "Every Flight" breakdown — important for multi-airline itineraries, since one leg can be deep-verified while a connecting leg on a smaller carrier is only source-linked

Badge labels: **Deep verified** (checked directly against the airline's current page), **Source linked** (sourced from the airline, not yet individually re-confirmed), **Needs confirmation** (no verified number stored), **No longer operating** (for the one archived airline, Spirit). Each badge has a hover tooltip explaining exactly what the label means, so it's informative without needing a separate legend.

## 2. Fare-class data: 4 airlines → 11

The original 4 (American, Delta, United, Southwest) were all US carriers. Added 7 more, prioritizing airlines where Basic/cheapest fare **completely excludes** the carry-on bag rather than just capping its weight — the more dangerous and less obvious trap:

- **KLM** and **Air France** — both confirmed directly from official sources: Basic fare includes only a personal item (40×30×15cm); the full-size carry-on isn't included at all and must be purchased separately.
- **Brussels Airlines** — Economy Basic on short/medium-haul: personal item only.
- **Eurowings** — BASIC fare: only the small under-seat item is free; the cabin trolley needs a fare upgrade or paid add-on.
- **Wizz Air** — without WIZZ Priority, only the small under-seat bag is included; the overhead trolley requires the paid add-on.
- **Volaris** — Zero fare: personal item only, carry-on excluded.
- **JetBlue** — Blue Basic: personal item only.

Verified by running the actual engine: selecting "basic" fare on each of these 7 now surfaces the specific warning; selecting "standard" produces no warning, confirming the logic doesn't over-fire.

## 3. Progressive disclosure on the result page

The enforcement badge, fee estimate, and fare-class warning — useful context, but secondary to the headline verdict — are now grouped behind a single "More about this result" expandable section instead of all three displaying unconditionally below the main status tiles.

The open/closed default is verdict-aware: it opens automatically when a bag fails or needs confirmation (more context is genuinely useful right then), and stays collapsed by default on a clean pass (the user mostly just needs the checkmark, with detail one tap away if wanted). The headline verdict, the specific failing measurement, and the Size/Weight/Booking breakdown are unaffected — those stay immediately visible, since hiding the actual reason for a verdict would hurt trust rather than help it.

## Bug caught and fixed during this work

While adding the new CSS for the progressive-disclosure block, a `str_replace` edit accidentally consumed the opening selector of an unrelated, pre-existing rule (`.vsb7-bag-result, .vsb7-flight-row`), leaving its body and closing brace orphaned. Caught immediately by the routine brace-balance check (473 expected vs. 472 found), traced to the exact line with a depth-tracking scan, and fixed before it ever reached a release. Re-verified full balance and re-ran the complete stress test afterward.

## Verification performed

- PHP 8.3 `php -l` clean on all files
- `node --check` clean on app.js
- CSS brace balance verified via depth-tracking, not just a raw count (473/473, zero negative-depth points)
- Functional test confirming `coverage_tier` and `last_verified` flow correctly through to both the strictest-leg summary and the per-leg flight matrix, across deep-verified, source-linked, and directory-tier airlines
- Functional test confirming all 7 new fare-class entries trigger correctly on "basic" and stay silent on "standard"
- Full stress test: 249 airlines × 3 bag types × 3 fare classes × 2 travellers = **2,241 combinations, 0 errors**

## Where this leaves the plugin

Every item from the deep function-by-function audit is now complete: the family/group booking bug is fixed, deep-verified coverage has grown from 69 to 84 with real country diversity, fare-class trap coverage has nearly tripled, and the result page now shows its own confidence level and discloses secondary detail progressively rather than all at once.
