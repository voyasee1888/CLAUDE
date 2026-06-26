# Voyasee BagFit 7.2.1 — Bug-fix patch

Found by reviewing your live screenshots, then verified with an actual functional test harness that ran the real engine and real data file (not just code review).

## Bugs fixed

### 1. Hero graphic label collision (visual bug, confirmed in your screenshots)

The "55 cm" dimension label and the "MULTI-FLIGHT / Strictest leg" callout box were both positioned on the right side of the hero illustration and overlapped at tablet/narrow-laptop widths — exactly what your screenshots showed. Fixed by moving the height label to the left side, creating a clean four-quadrant layout (height label upper-left, "BAG CHECK" lower-left, depth label lower-right, "MULTI-FLIGHT" upper-right) with no shared space.

### 2. Scan-frame overflow at tablet width (latent bug, found while fixing #1)

The hero's animated suitcase frame is a fixed 230×270px box. At the 850px tablet breakpoint, its container shrinks to 260px tall — 10px shorter than the frame itself, causing a silent vertical overflow that likely showed as slight clipping. Added a proper `scale(.88)` to fit the frame inside its container at this breakpoint, matching the existing approach already used at mobile width.

### 3. Fare-class warning firing on the wrong condition (accuracy bug — the serious one)

This is the one that matters most. The new fare-class warning (added in 7.2.0) was checking `cabin_class === 'economy'` instead of checking the actual fare-type selector. The practical effect: **anyone who selected Standard or Flexible fare, as long as their cabin was Economy, would have wrongly been shown Basic Economy restrictions that don't apply to their ticket** — e.g. a United passenger with a Standard fare would have seen "no carry-on allowed" even though that's only true for Basic Economy.

Fixed to check the actual `fare_class` field. Verified with a functional test running the real engine against real United Airlines data:
- Basic Economy selected → warning shown (correct)
- Standard fare selected → no warning (now correct; previously incorrectly showed the warning)
- Flexible fare selected → no warning (correct)
- Fare type not specified → a separate, generic "select your fare type for an exact result" reminder instead of a specific false claim

### 4. Broken apostrophe character (found while testing fix #3)

The generic fare-type reminder notice added during the fix above used `\u2019` for an apostrophe, which is not valid PHP unicode-escape syntax without curly braces (`\u{2019}`). It would have rendered literally as `airline\u2019s` on the live page instead of "airline's". Confirmed via test, then fixed to a plain apostrophe. Scanned the rest of the codebase for the same mistake — no other instances found.

## How this was verified

Built a standalone PHP test harness that loads the actual plugin engine and the actual `airlines.json` data file (not mocked), and:
- Ran all four fare-class scenarios against real United Airlines data and printed the actual warning text produced
- Ran a soft-bag-compression scenario on Ryanair (high enforcement) and confirmed leniency correctly does NOT apply
- Ran the same scenario on Aer Lingus (low enforcement) and confirmed leniency correctly DOES apply, with the dimension-usage percentage recalculated consistently
- Cross-checked fee estimates for Spirit, Frontier, Southwest and Ryanair against the populated data
- Sanity-checked failed_checks/status consistency across a sample of airlines

All passed after the fixes above. Full PHP lint (`php -l`, PHP 8.3), JS syntax check, and CSS brace balance also re-verified clean.

## Not changed in this patch

No new features. No data values altered beyond the two bug fixes above. No design changes beyond the hero-graphic repositioning needed to fix the overlap.
