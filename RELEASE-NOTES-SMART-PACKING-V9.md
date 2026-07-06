# Voyasee Smart Packing Generator 9.0 — Bug fixes + accuracy engine + multi-city

Full rewrite of the tool's script following a line-by-line audit of v8 (see prior
session's artifact report). No paid or rate-limited third-party API was added —
Open-Meteo was deliberately left out since its free tier is non-commercial-only and
this tool carries affiliate links/ads.

## Bugs fixed

- **Ticked/worn items no longer reset on regenerate.** Item ids used to come from a
  global auto-increment counter, so editing the trip and clicking Generate again
  produced brand-new ids and silently wiped every checkmark. Ids are now derived from
  `categoryId + item name` (with any leading quantity stripped), so the same
  conceptual item keeps its id — and its checked/worn state — across a regenerate.
- **Saved progress is actually restored.** `persist()` wrote checked/worn state to
  `localStorage` but `restore()` never read it back. It now does, and a returning
  visitor sees a clickable "Welcome back — resume your trip (N items packed)" status
  instead of losing all progress on reload.
- **Share links now carry the full trip.** Previously only 7 of ~18 config fields
  were encoded (destination, month, duration, traveller, luggage, route, activities)
  — stay type, laundry, health, work mode, focus and all six toggles were silently
  dropped. The link now encodes the complete config plus any extra multi-city legs.
- **Custom items keep their category.** Adding an item under, say, Footwear used to
  get re-filed under "Destination Extras" on the next regenerate because only the
  raw text was stored. Custom items now remember which category they were added to.
- **Misc:** the Budget Calculator suggestion was gated on an unrelated "carry-on
  only" setting — re-keyed to party size/trip length/route; VisaHQ is now suggested
  for international/long-haul trips (the list already generates a visa document item
  for these); missing plug/voltage/currency/language now reads "Not listed — confirm
  locally" instead of literally the word "verify"; capsule-wardrobe math is computed
  from the actual build quantities instead of regex-parsing rendered item text (which
  broke silently on any copy edit); fixed a `toLocaleDateString(undefined, …)` call
  that would have rendered dates inconsistently per visitor locale (the same class of
  bug documented in `voyasee-bagfit`'s 7.8.2 release notes).

## Accuracy engine (free, in-house — no external API)

Live weather only ever covered trips starting within ~14 days — the majority of real
bookings are further out, and previously fell back to a single fixed climate label
per destination that never varied with the travel month (e.g. Zagreb showed the same
advice in January and July). Replaced that flat fallback with a hemisphere + month
seasonal model, computed entirely from data already in the destination record
(latitude) — no network call, no third-party dependency, no ToS conflict with
monetization. A "cold" destination in the local summer now reads as "variable"; in
local winter, "freezing" — for every one of the 517 curated destinations, and for any
typed city the Weather Bridge can geocode, at any distance in the future.

## New features

- **Multi-city trips.** Add extra stops in Step 1 (destination + nights each).
  Trip-wide categories (documents, toiletries, health, tech, bags, personal-item,
  transit comfort) generate once; climate/activity-dependent categories (clothing,
  footwear, stay setup, destination extras, activity gear) generate per leg and are
  labelled with that leg's city, using each leg's own seasonal estimate.
- **"Explain my score."** The readiness ring now has a 5-row breakdown (packed
  overall, documents, medicine, cabin-critical, bag weight) instead of one opaque
  number.
- **Drag-to-reorder** items within a category (native HTML5 drag-and-drop).
- **Real "heaviest cuts" list** — the bag optimiser now names your actual 3 heaviest
  unworn items instead of reusing the same four generic bullet sentences for every
  trip.
- **Conditional accuracy cards** — the airport/cabin pre-flight panel only shows
  confidence statements for tags that actually appear in your generated list (e.g. no
  "medicine import" card if health = none and no medicine items exist).
- **Literal forecast dates** on the weather card when live data is used, plus a
  compact "other stops" weather summary for multi-city trips.
- **Improved print/PDF layout** — single-column, print-safe checkbox contrast, hides
  score breakdown/ring/lower side cards, adds a small footer credit line.
- **Best-effort pack reminder** via the Notification API — explicitly labelled
  best-effort in its own copy, since true push notifications need a service worker
  and backend that a single embedded HTML snippet can't provide.

## Known scope limits (not implemented, and why)

- **True installable PWA / offline service worker** — needs a manifest + service
  worker registered at the site's root scope, which isn't achievable from body-only
  content in a WordPress Custom HTML block. Would need a small site-level addition
  outside this file.
- **Aggregated "N travellers packing for X" social proof** — needs a backend
  (WordPress REST + DB) to aggregate anonymized stats; out of reach for a
  client-only HTML file.
- **Open-Meteo integration** — deliberately not added; its free tier is
  non-commercial-only and this site carries affiliate links/ads.

## Verification

- `node --check` clean on the extracted script.
- Headless-Chromium (Playwright) end-to-end pass: full wizard flow, stable-id
  persistence across regenerate, reload-and-resume with checked state intact, share
  link round trip, multi-city leg categories, custom-item category retention,
  synthetic drag-and-drop reorder, print action, and a dedicated Notification-API
  smoke test — zero page/JS errors (only expected network 404s from the WordPress
  Weather Bridge endpoints, which don't exist in this local test environment and are
  already handled by existing `.catch()` fallbacks).
- Not verified: live Open-Meteo-free — n/a (not used); actual WordPress-hosted Weather
  Bridge network calls (this sandbox's network policy blocks outbound reach to test
  further than the local static-file server) — recommend a smoke test on the live
  site before wide rollout.
