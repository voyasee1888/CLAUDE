# Voyasee BagFit 7.6.0 — All 70 Deep-Verified Airlines Now Personally Re-Checked

## What this release is

You asked for all 70 deep-verified airlines to be personally checked against current official pages, with no exceptions. This release closes that gap: the 22 airlines that were carried forward from an earlier session (not personally re-checked in this conversation) have now been verified the same way as the other 48.

## The most important finding: one airline doesn't exist anymore

**Spirit Airlines ceased all operations permanently on May 2, 2026** — confirmed via NPR, CNN, CBS News, and CNBC. It filed for Chapter 7 liquidation after a failed $500 million federal bailout. All flights were cancelled, customer service shut down, and 17,000 people lost their jobs. This is not a baggage-data issue — there is no current Spirit flight to check a bag against, anywhere, ever again.

**Fix applied:** Spirit Airlines has been set to `status: archived`. This is the plugin's existing mechanism for hiding an airline — both `public_airlines()` (the dropdown list) and `airline_by_slug()` (direct lookup) filter on `status = 'published'`, so Spirit now:
- Does not appear in the airline selection dropdown
- Cannot be looked up even via a direct API call or an old saved/shared link
- Returns a clean "Airline not found" error rather than ever producing a baggage result for a flight that doesn't exist

Verified by actually running the engine against it: `airline_by_slug('spirit-airlines')` returns `null`, and attempting to check a bag against it returns a proper error, not a stale result.

**This means the honest count is 69 active deep-verified airlines, not 70.** Tier counts are now: 69 deep_verified, 89 core_source_linked, 91 directory, 1 discontinued (Spirit, hidden from all public surfaces). 249 airlines remain selectable; Spirit is excluded.

## Data corrections found across the other 21

**14 of 21 were already perfectly accurate** — Austrian Airlines, Eurowings, TAP Air Portugal, Iberia, Vueling, Icelandair, Hawaiian Airlines, Frontier Airlines, Wizz Air, LOT Polish Airlines, Allegiant Air, Etihad Airways, Finnair, Akasa Air. **7 needed corrections:**

| Airline | Issue | Fix |
|---|---|---|
| SWISS | Personal item height wrong (15cm vs official 10cm) | Corrected to 40×30×10cm |
| Brussels Airlines | Same issue | Corrected to 40×30×10cm |
| Pegasus Airlines | Cabin height wrong (23cm vs official 20cm) | Corrected to 55×40×20cm |
| Jet2.com | Personal item height wrong (20cm vs official 15cm) | Corrected to 40×30×15cm |
| **airBaltic** | Personal item height wrong, **and the same combined-weight bug found 14 times now across this project** | Corrected dims; added combined 8kg cap across cabin+personal (official: "the total weight is 8kg for all pieces") |
| **Transavia** | Combined-weight bug | Added combined 10kg cap (dims/weight values were already individually correct, just not linked as a shared pool) |
| Aer Lingus | Personal item dimensions were missing entirely | Added 40×30×20cm, based on the stronger-corroborated of two conflicting claims found |

That brings the running total of airlines fixed for the combined-weight pattern to **15** across this and the two prior releases.

## Verification method (unchanged from prior rounds)

Every figure checked against the airline's own official page where fetchable, cross-referenced against 3+ independent secondary sources, corrections applied only where evidence clearly supported them, and nothing invented where an airline doesn't publish a number. Where two sources genuinely conflicted (Aer Lingus's personal item), the conclusion is flagged as lower-confidence rather than presented as certain.

## Full verification performed

- PHP 8.3 `php -l` clean on all files
- `node --check` clean on app.js
- JSON valid, 250 airlines total, tier counts correct
- **Functional Spirit-exclusion test**: confirmed `airline_by_slug()` returns null, confirmed Spirit is absent from the public dropdown list, confirmed attempting to check a bag against it returns a clean error instead of a result
- Combined-weight tests for airBaltic and Transavia, both passing with correct fail/pass behavior at the boundary
- Full stress test: all 249 published airlines × 3 bag types (747 combinations) through the real engine, **0 errors**

## Where this leaves you

**Every one of the 69 currently active deep-verified airlines has now been personally checked by this process against current official sources, with corrections applied where found.** That is a materially different and stronger claim than what could honestly be said before this release. The 89 core_source_linked and 91 directory-tier airlines are unchanged and still carry their existing, lower confidence labels — which the result page already surfaces to users honestly rather than presenting everything as equally certain.
