# Voyasee BagFit 7.4.0 — Pre-Launch Final QA Pass

This is the release to check before going live publicly. It re-verifies the highest-traffic deep-verified airlines against their current official pages, finds and fixes several real accuracy bugs (one of them significant), and stress-tests the entire engine against all 250 airlines plus a battery of edge cases and security probes.

## Part 1 — Re-verification of high-traffic deep-verified airlines

Checked 13 of the highest-traffic airlines in the deep-verified tier against live official sources one more time (Emirates, Qatar Airways, Singapore Airlines, Lufthansa, British Airways, Air France, KLM, Turkish Airlines, Air India), specifically hunting for anything that doesn't match what's currently published on the airline's own site.

### Confirmed already accurate, no change needed
- **Qatar Airways** — 50×37×25cm, 7kg, matches qatarairways.com exactly
- **Singapore Airlines** — correctly modeled as "115cm total, no fixed box" since that's genuinely SQ's real policy (not guessing a box shape like some third-party sites do)
- **Turkish Airlines** — 55×40×23cm/8kg cabin + separately-limited 40×30×15cm/4kg personal item — confirmed these are genuinely independent limits, not combined
- **Air India** — dimensions and personal item confirmed; cabin weight figure (7kg) has minor source disagreement (7 vs 8kg) without a clear resolution, left unchanged rather than guess

### Bugs found and fixed

**1. British Airways — a real weight limit was completely missing (most significant find this round).** BA publishes a 23kg **combined** weight limit across the cabin bag and personal item together. The database had **no weight limit recorded at all** for either bag — meaning a BA result page was reporting weight as "unknown" regardless of how heavy the bags actually were. Fixed using the same combined-weight mechanism built for the JAL/ANA/Korean Air fix last release. Verified: a single 25kg bag now correctly fails; an 18+6kg split now correctly fails; an 18+4kg split correctly passes.

**2. Air France and KLM — same combined-weight bug pattern found in JAL/ANA/Korean Air last release.** Both airlines' 12kg cabin limit is officially the *combined* weight of the hand baggage and the small bag together (confirmed directly on airfrance.us and klm.com). The data had this stored as a per-bag limit. Fixed the same way. Verified: a 10+3kg split now correctly fails; a 9+2kg split correctly passes.

**3. Emirates — cabin bag height was 2cm too tall.** Stored as 55×38×22cm; the correct figure, confirmed by Emirates' own 8-inch height spec and four independent sources with internally consistent cm/inch math, is 55×38×**20**cm. The 22cm figure traced to two sources with an internal arithmetic error (citing "8 inches" but converting it to 22cm instead of the correct ~20cm).

**4. Lufthansa — personal item height was 5cm too tall.** Stored as 40×30×15cm; majority evidence with consistent cm/inch conversion (40×30×**10**cm = 15.7×11.8×3.9in) points to 10cm. Corrected.

### Pattern worth flagging for future audits
Five airlines now use the combined-weight mechanism (JAL, ANA, Korean Air from last release; British Airways, Air France, KLM from this one). All are full-service international carriers with a "cabin bag + personal item, X kg total" structure — worth specifically checking for this pattern on any future airline promoted to deep-verified status, since it's an easy thing to misread as a per-bag limit.

## Part 2 — Full engine stress test (all 250 airlines)

Built and ran a test harness that exercises the **real** engine and advisor code (not mocks) against every one of the 250 airlines in the dataset, for all three bag types (cabin, personal, checked), varying cabin class, fare class, and soft-sided status — 750 total combinations.

**Result: 0 errors.** No crashes, no missing required fields in any result, the Smart Advisor ran cleanly for every combination.

## Part 3 — Edge case and security testing

Ran 13 deliberately hostile or malformed inputs through the engine:

| Input | Result |
|---|---|
| Zero-weight bag | Handled, produces a sensible result |
| Zero dimensions | Rejected with a clear validation message |
| Negative weight | Rejected with a clear validation message |
| Absurdly large bag (1000cm, 9999kg) | Rejected — dimension bounds enforced |
| Nonexistent airline slug | Rejected with "Airline not found" |
| Missing airline_slug field | Rejected gracefully, no crash |
| Empty bags array | Rejected ("Add between one and six bags") |
| Empty flights array | Rejected ("Add between one and ten flights") |
| `<script>` tag in bag name | Accepted as text, confirmed escaped on render (see below) |
| SQL-injection-style airline slug | Treated as plain text lookup, no injection surface — airline data isn't queried via raw SQL by slug |
| Multi-flight (3 legs) + multi-bag (4 bags), full mode, 2 travellers | Resolved correctly |
| Unicode/emoji in bag name | Handled correctly |
| Imperial units (inches/lb) instead of metric | Converted and evaluated correctly |

No uncaught exceptions, no fatal errors, in any case.

**XSS check:** confirmed the JS render layer wraps every user-controlled string (bag names, airline names, etc.) in the existing `esc()` helper before insertion into the DOM, and that `safeUrl()` rejects anything not starting with `http(s)://` before using it as a link target. A `<script>` payload in a bag name renders as inert text, not executable script.

## Part 4 — Security and structure review

- **REST API**: all five mutating endpoints (check, reverse-search, shared-size, parse-ticket, save) require a valid `vsb_public` nonce and are individually rate-limited (100/hr, 30/hr, 40/hr, 100/hr, 30/hr respectively). Read-only endpoints are open by design.
- **Admin pages**: gated behind `current_user_can('manage_options')`.
- **Database**: every query against the custom tables uses `$wpdb->prepare()` with placeholders — no raw string interpolation into SQL.
- **PHP**: all 11 files pass `php -l` on PHP 8.3.
- **JavaScript**: `app.js` passes `node --check`.
- **CSS**: brace count balanced (455/455).
- **JSON**: `airlines.json` (250 airlines, schema 7.4) and `airports.json` (7,883 airports) both parse cleanly.
- **Version consistency**: plugin header, `VSB_VERSION` constant, and readme stable tag all read 7.4.0.

## Final tier counts

- Deep-verified (Tier 1): **50 airlines** — 13 of the highest-traffic ones re-checked again this round, with 4 corrections found and fixed
- Core source-linked (Tier 2): 109 airlines
- Directory only (Tier 3): 91 airlines

## What this does NOT cover

- Visual/CSS rendering inside an actual WordPress + Elementor + caching stack — still requires the staging test described in earlier release notes.
- The remaining 37 deep-verified airlines not specifically re-searched this round (they were verified in earlier sessions; no new contradicting evidence was found for any of them during this pass, but they weren't independently re-fetched against live pages this round).
- Live load/performance testing under real traffic.

## Recommendation

Structurally and functionally, this build is ready. The one item worth a final staging check before going fully live: confirm the British Airways 23kg combined-weight fix and the Air France/KLM equivalents display correctly on the actual result page in a browser, since this was verified at the engine level but not visually re-confirmed in a live render.
