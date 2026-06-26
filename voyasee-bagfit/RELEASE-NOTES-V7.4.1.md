# Voyasee BagFit 7.4.1 — Two bugs found from live screenshots

Found by reviewing your live screenshots of an Air France checked-bag result, then reproduced exactly in the test harness before fixing.

## Bug 1: "Copy" button text was nearly invisible (the one you spotted)

The CSS rule `.vsb7-airport-script span` was written to style the small "SHOW THIS TO AIRPORT OR CHECK-IN STAFF" label — but `span` is a generic selector, so it also caught the "Copy" text nested inside the copy button. That rule set the text color to `#5be3da` (a mint/cyan), which sat directly on the button's own `#33dfdc` (cyan) background — cyan text on a cyan button, virtually unreadable.

**Fix:** scoped the label styling to `.vsb7-airport-script>div>span` so it only hits the intended label, and added an explicit `.vsb7-airport-script button span { color: #07355c }` rule as a defensive backstop so this can't silently break again from some other generic span rule. "Copy" now renders in dark navy on cyan — clearly readable.

## Bug 2: Enforcement note showed cabin-specific info for a checked-bag result

Your screenshot showed a **checked bag** (45×20×15cm, 23kg, 0.5cm over Air France's checked-bag size limit) — but the "GATE ENFORCEMENT" panel below it said *"Air France checks combined weight (12 kg cabin + personal) at CDG and other hubs."* That sentence is entirely about cabin/personal carry-on weight, which has nothing to do with the checked-bag size problem actually being shown. It was confusing because it sounded like it was explaining the failure, but it wasn't related to it at all.

**Root cause:** the enforcement badge was written to display for the strictest leg regardless of bag type, but the underlying "enforcement level" concept (how strictly gate staff check carry-on at the airport) was only ever researched and written for cabin/personal items. Checked baggage is weighed and measured at the check-in counter as standard procedure at essentially every airline — there isn't a meaningful "strict vs. lenient" variability to report there the way there is for carry-on.

**Fix:** the enforcement badge now checks the strictest bag's type and suppresses itself entirely when that bag is a checked bag, rather than showing an airline-level note that doesn't apply to what's actually being evaluated.

## How this was verified

Reproduced your exact scenario in the test harness — Air France, checked bag, 45×20×15cm, 23kg — and confirmed:
- `size_status: fail`, `weight_status: pass`, `verdict_code: FAIL_SIZE` (matches your screenshot exactly)
- The enforcement note text in the data is exactly what appeared in your screenshot, confirming the root cause
- With the fix applied, the badge correctly suppresses itself for this checked-bag scenario
- The airport script text generated also matches your screenshot exactly, confirming this is the same code path

CSS brace balance re-verified (456/456), JS syntax re-verified clean, all PHP files re-linted clean.

## Not changed in this patch

The fee-estimate panel and fare-class warning were left as-is — both are still relevant information regardless of bag type, unlike the enforcement note. No data values were changed, only the two display bugs above.
