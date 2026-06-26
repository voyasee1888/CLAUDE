# Voyasee BagFit 7.7.0 — Family Bug Fix + 15 New Countries in Deep-Verified

This release executes the first two items from the deep function-by-function audit: the confirmed family/group booking bug, and closing the biggest country gaps in the deep-verified tier.

## Bug fix: families and groups were getting the wrong verdict

Found by testing every mode of the engine directly, not by inspection. A family of 4, each bringing their own cabin bag, with `travellers: 4` correctly set, produced this result before the fix: bag 1 passes, bags 2, 3, and 4 are all wrongly marked **NOT_INCLUDED** — even though 4 separate people are each entitled to their own cabin-bag allowance under the airline's actual rule.

**Root cause:** the engine checks how many bags of a type exist against the airline's per-person `piece_count`, but the `travellers` count, while captured and validated, was never multiplied into that check anywhere in the codebase.

**Fix:** `apply_piece_and_combined_weight_rules()` now multiplies the per-person allowance by the traveller count before flagging extra bags as not-included. Verified directly:

| Scenario | Before | After |
|---|---|---|
| Family of 4, 4 cabin bags, travellers=4 | 3 of 4 bags wrongly rejected | All 4 correctly pass |
| Solo traveller, 3 cabin bags entered by mistake | bags 2–3 rejected | Unchanged — still correctly rejected |
| Family of 2, 3 cabin bags (genuinely one too many) | — | Bag 3 correctly rejected, bags 1–2 pass |
| Group of 6 (max bag count), travellers=6 | — | All 6 correctly pass |

The fix does not weaken the check — a solo traveller who enters more bags than their fare allows still gets correctly flagged. It only correctly scales the allowance for parties of more than one. Re-ran the full 2,241-combination stress test (249 airlines × 3 bag types × 3 traveller counts) afterward: 0 errors, and confirmed the existing combined-weight feature (built in earlier releases) is unaffected by this change.

## Deep-verified tier: 69 → 84, closing real country gaps

Rather than adding more airlines from countries already well covered, this batch specifically targeted countries with **zero** prior deep-verified representation — picked from an audit of every country currently represented (44 countries across the prior 69 airlines).

**The 15 added:** Garuda Indonesia, Lion Air (Indonesia), Aeromexico, Volaris (Mexico), ITA Airways (Italy), Aegean Airlines (Greece), Aerolíneas Argentinas (Argentina), South African Airways (South Africa), El Al (Israel), Pakistan International Airlines (Pakistan), Air Peace (Nigeria), SriLankan Airlines (Sri Lanka), Biman Bangladesh Airlines (Bangladesh), SpiceJet, Air India Express (closing two remaining Indian-carrier gaps).

### What the research found

**8 of 15 were already accurate** (Lion Air's unusually small cabin allowance — 40×30×20cm — looked like a possible data error and was specifically double-checked; it's genuinely correct, confirmed by the airline's own Condition of Carriage document). **The rest needed real fixes:**

- **El Al and Air Peace had zero data at all** (directory tier, name and link only). Both now carry real, sourced numbers: El Al 56×45×25cm/8kg (official source), Air Peace 45×33×20cm/6kg (official flyairpeace.com).
- **SpiceJet's stored cabin dimensions were wrong** (55×40×20cm vs. the official 55×35×25cm), and it has the same combined-weight pattern as 16 other airlines fixed in prior releases — its 7kg limit covers the cabin bag *and* personal item together, not the cabin bag alone.
- **ITA Airways'** width and height were each off by a few centimeters against the airline's own published spec.
- **Volaris** also has the combined-weight pattern (15kg shared across cabin + personal on its Basic fare).
- South African Airways and Pakistan International Airlines were missing personal-item data the official sources do publish; both now added.

That brings the running count of airlines with the combined cabin+personal weight pattern to roughly 18 across all releases — strong enough now to treat as an expected check on every future airline, not an edge case.

## Verification performed

- PHP 8.3 `php -l` clean on all files; `node --check` clean on app.js
- JSON valid: 250 airlines, 84 deep-verified, 89 directory, 76 core, 1 discontinued (Spirit, unchanged from last release)
- Functional tests for the family-bug fix at multiple party sizes, all passing expected behavior
- Functional combined-weight tests for Volaris and SpiceJet at the exact kg boundary, both passing
- Full stress test: every published airline × 3 bag types, **0 errors**

## Still pending from the roadmap

Confidence-tier visibility on the result card, fare-class data expansion beyond the current 4 airlines, and the progressive-disclosure layout pass are not yet started.
