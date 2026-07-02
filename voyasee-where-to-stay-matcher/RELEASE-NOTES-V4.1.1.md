# Voyasee Where to Stay Matcher 4.1.1 — live-site bug fixes

## What was reported

Live screenshots of the production v4.1.0 site surfaced two front-end bugs and one
data-freshness complaint that read as "broken" even though the underlying
mechanism was working as designed:

1. Typing into the "Destination" field on Step 1 showed nothing — the text only
   became visible after clicking an autocomplete suggestion.
2. The calendar icon on the "Travel start date" field wasn't visible at all.
3. Match card photos only showed a real image for Japan/Tokyo; every other
   destination showed a solid archetype-color block, and it wasn't obvious the
   photo sync was even running automatically.

## Root causes

1. **Destination field invisible while typing.** `.vwtsm-input:focus` only set
   an outline; it never re-asserted the dark background / cream text that the
   base `.vwtsm-input` rule sets with `!important`. Some themes ship their own
   `input:focus` rule (commonly a plain white "active field" background) with
   enough specificity to win specifically during the focused state. The moment
   the field lost focus — e.g. right after clicking a suggestion — the base
   rule took over again and the text reappeared. This is the same class of bug
   fixed for the unfocused state in v3.4.0, just in a state that fix didn't
   cover.
2. **Calendar icon invisible.** No CSS at all targeted the native date-input
   picker glyph, so the browser drew it in its own default near-black ink —
   invisible against this tool's dark navy fields.
3. **Photos "only working for Tokyo."** The automatic daily Pexels sync cron
   was real and correctly wired up (not a manual-only process), but it only
   processed 20 neighborhoods per run. Across a catalog of a few hundred
   neighborhoods, the very first full pass took over two weeks, during which
   every destination not yet reached looked identical to "photos are broken,"
   rather than "still catching up." Whichever destination happened to be
   earliest in sync order (Tokyo) simply finished first.

## What shipped

- `.vwtsm-root .vwtsm-input:focus` / `.vwtsm-select:focus` now re-assert the
  same background/text-color as the base rule, so no theme's own focus style
  can repaint the field while a visitor is actively typing.
- Added `::-webkit-calendar-picker-indicator` styling (inverted + brightened)
  so the date field's calendar icon is visible against the dark theme.
- `WTSM_Photo_Sync`: batch size raised from 20 → 50 neighborhoods per run, and
  the cron schedule raised from daily → hourly. Pexels is a dedicated,
  generously-quota'd commercial key (200 req/hour, 20,000/month) rather than a
  shared community resource like the OpenStreetMap Overpass or Wikipedia
  GeoSearch syncs used elsewhere in this plugin — those stay on their existing
  conservative daily/small-batch schedule deliberately, out of respect for
  their fair-use policies as shared public resources. Pexels had no such
  constraint, so there was no reason for it to be this slow.
  `register_cron()` now detects and migrates an already-scheduled daily event
  from a prior install to the new hourly one automatically — no deactivate/
  reactivate needed on the live site.
- Admin → Sync page copy and button label updated to reflect the new
  batch size/frequency.

## What did *not* change (and why)

- The OpenStreetMap POI sync (walkability/nightlife) and the Wikipedia
  landmark sync keep their existing small daily batches. Both explicitly
  document that they're deliberately conservative out of respect for shared
  public API fair-use policies (Overpass in particular warns against
  "heavy use" from any single consumer) — unlike Pexels, increasing their
  throughput risks the plugin's own IP getting rate-limited or blocked
  across every site using it, which is a materially different kind of risk
  than "photos take a few extra days."
- WP-Cron itself still depends on the site receiving page-load traffic to
  fire (it isn't a true system-level cron unless the host is configured with
  one) — this is inherent to how WordPress scheduling works everywhere, not
  specific to this plugin, and wasn't something to "fix" here.

## Verification

- `php -l` clean on every PHP file in the plugin (full sweep, not just
  changed files).
- CSS brace balance verified (330 open / 330 close).
- Manually traced the focus-state cascade fix against the exact symptom
  reported (visible after blur, invisible while focused) to confirm the
  root-cause theory before writing the fix, rather than guessing.
- No live WordPress install was available in this environment to visually
  re-confirm the rendered fix; recommend spot-checking the destination field
  while typing and the date picker icon on the live site after updating.
