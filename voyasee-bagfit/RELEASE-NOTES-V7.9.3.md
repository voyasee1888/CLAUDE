# Voyasee BagFit v7.9.3 — Release Notes

Release date: 2026-07-10

## Bug fixes

1. **Admin edits now appear instantly on the front end.** Saving an airline in
   the admin editor now clears the `vsb_public_airlines` object cache, so the
   updated rule is served immediately instead of after the 5-minute cache TTL.
2. **Data-health counters fixed for the v7.0 schema.** `data_health()` read the
   removed v6 `personal`/`cabin` `dimensions_mm` paths and therefore always
   reported every airline as "missing dimensions." It now inspects the
   `allowance_options` arrays that the v7.0 schema actually uses.
3. **Expired report links are never cached.** The printable-report handler now
   calls `nocache_headers()` before every error `wp_die()` path, so an expired
   or invalid share link cannot be cached and served to another visitor.
4. **Bag passport label matches the preview.** The "Length" measurement field is
   now labelled "Height (longest side)," matching the value shown as the tall
   dimension in the bag-passport mini-case preview.
5. **Removed the external Google Fonts request.** The front end no longer loads
   the Inter web font from `fonts.googleapis.com`; the stylesheet already falls
   back to the system font stack. This removes a third-party request (a GDPR
   consideration) with no visual change on modern systems.

## Result page

- **New journey summary strip.** Multi-flight results now open with an
  at-a-glance, flight-by-flight strip: one node per flight showing a
  pass / confirm / conditional / over-limit status with the airline name, so
  travellers can immediately see which leg is the problem before scrolling.

## Airline data

- **20 airlines promoted to the deep-verified tier** (now 124, up from 104)
  after cross-referencing current published cabin-baggage rules across multiple
  authoritative sources: Air Europa, Binter Canarias, Bulgaria Air, Caribbean
  Airlines, Cyprus Airways, Fiji Airways, flynas, Hainan Airlines, Jetstar
  Japan, Peach Aviation, RwandAir, Smartwings, Spring Airlines, Sun Country,
  SunExpress, TAROM, TUI Airways, Viva Aerobus, XiamenAir and ZIPAIR.
- Data corrections: Hainan economy cabin weight corrected to 5 kg, RwandAir set
  to 10 kg, Viva Aerobus free personal item corrected to 45×35×20 cm, and Cyprus
  Airways personal-item allowance populated.
- Added fare/aircraft condition notes (ATR exceptions, two-piece combined
  limits, LCC paid-carry-on and fare-dependent personal-item rules).

The issued ticket and the operating airline's official page remain
authoritative; BagFit is a planning assistant.
