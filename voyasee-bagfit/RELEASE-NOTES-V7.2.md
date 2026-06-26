# Voyasee BagFit 7.2.0

## Summary

Major feature release. Adds enforcement-likelihood intelligence, gate-fee estimation, fare-class awareness, and soft-bag compression logic to the engine and result page. Expands the airline dataset to include enforcement data for 42 high-profile airlines. All new features are free, using no paid APIs — they run entirely on the self-hosted airline dataset.

## New features

### 1. Gate enforcement likelihood indicator

Every result now shows an enforcement badge for the strictest airline:

- **Strictly enforced** (21 airlines): Ryanair, Wizz Air, easyJet, Spirit, Frontier, Emirates, Qatar, AirAsia, IndiGo, Cebu Pacific, etc.
- **Selectively enforced** (21 airlines): BA, Lufthansa, United, American, Air France, KLM, Turkish, Singapore, ANA, JAL, etc.
- **Rarely enforced** (remaining ~208 airlines): default for carriers where enforcement data is sparse.

Each badge includes a per-airline explanation (e.g. "Ryanair strictly measures bags at the gate with sizer frames. Weight is checked at check-in."). This is the single most-requested travel insight that no competitor offers.

### 2. Gate-fee estimation

When a bag fails, the result now shows estimated costs the traveler would face:

- Gate fee (typically the most expensive)
- Checked online (prepaid, usually cheapest)
- Checked at counter

19 airlines have fee data populated. The panel highlights in red when the bag actually failed. A disclaimer notes these are estimates that vary by route, date and fare.

### 3. Fare-class selector (Basic Economy awareness)

A new "Fare type" dropdown appears on every flight card: Basic Economy / Standard / Flexible. When Basic Economy is selected on airlines with known restrictions, a prominent warning appears:

- **United**: "Basic Economy on domestic flights: personal item only, no carry-on. $75 gate fee if oversized bag detected."
- **American**: "Basic Economy includes carry-on but boards Group 9 — overhead bin space may be full."
- **Delta**: "Basic Economy includes carry-on. No checked bags included. Boards last group."
- **Southwest**: "Basic fare: carry-on allowed, checked bags now $35 each way (changed 2025)."

### 4. Soft-bag compression (enforcement-aware)

A "Soft-sided" checkbox already existed in the bag form but had no engine effect. Now:

- When a soft bag **fails** on size, and the airline's enforcement is **low**, the engine applies 5mm grace per axis (representing compression).
- If that grace makes the bag pass, the verdict upgrades and a notice explains: "Soft-bag compression may let this bag fit — but only where manual sizer checks are used, not automated scanners."
- On **high-enforcement** airlines (automated scanners), no grace is applied — because scanners don't compress bags.

### 5. All v7.1.0 features (Smart Fix Plan, expanded footer, Inter font) remain

## Data changes

- `airlines.json` schema version bumped to 7.2
- Every airline record now includes `enforcement`, `fee_estimate`, and `fare_class_affects` fields
- 42 airlines have researched enforcement levels; 19 have fee estimates; 4 have fare-class restriction data
- No airline dimensions, weights, or source URLs were altered

## UI changes

- New "Fare type" selector in the flight form (4-column grid on desktop, 2-column on mobile)
- Enforcement badge, fee estimate panel, and fare-class warning render between the focus grid and "Best next action" on the result page
- All new sections are mobile-responsive (single-column stacking at 560px)

## Bug fixes

None new — all v7.1.0 fixes (Smart Fix Plan, footer wiring, Inter font) remain in place.

## Verification

- PHP 8.3 `php -l` passed on all 11 PHP files
- `node --check` passed on app.js
- CSS brace balance verified (452/452)
- JSON dataset validates cleanly (250 airlines, schema 7.2)
- All 14 tool links and 14 affiliate links in footer remain correctly wired
