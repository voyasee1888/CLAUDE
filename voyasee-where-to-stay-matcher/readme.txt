=== Voyasee Where to Stay Matcher ===
Contributors: voyasee
Tags: travel, hotels, neighborhoods, quiz
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 3.2.0
License: GPLv2 or later

A single all-in-one plugin: the destination/neighborhood dataset, the
matching engine, and the interactive "where should I stay" quiz --
everything operated through one shortcode.

== What changed in 2.0.0 ==

Versions 1.x shipped as two cooperating plugins (a data layer plus a
matcher). 2.0.0 merges everything into this one plugin. If you have the
old two-plugin setup installed:

1. Deactivate and delete "Voyasee Neighborhood Intelligence" and the old
   "Voyasee Where to Stay Matcher" (1.x).
2. Install and activate this plugin instead.
3. Re-import your destination/neighborhood data (or the bundled sample
   data) -- this plugin creates its own copy of the database tables, so
   data from the old setup does not carry over automatically.

== Description ==

Embed the tool anywhere with:

    [voyasee_where_to_stay]

Or pre-fill a destination on a landing page:

    [voyasee_where_to_stay destination="tokyo"]

== Setup ==

1. Activate the plugin. A starter dataset of 156 destinations and 478
   neighborhoods loads automatically the first time it activates --
   there is no manual CSV import step. (Import CSV still exists in the
   admin menu, but only as an optional way to add more destinations
   later or bulk-edit -- you don't need to touch it to get started.)
   If you're upgrading in-place from an earlier version without
   deleting the plugin first, the new 50 destinations won't auto-seed,
   but you don't need to reinstall -- go to Voyasee Where to Stay >
   Import CSV and click "Add any new starter destinations" (safe to run
   anytime; existing data is never overwritten).
2. Go to Voyasee Where to Stay > Sync Data and run the OpenStreetMap
   POI sync once, to confirm the connection works from your server.
   Optionally also run the boundary sync there (real neighborhood
   shapes for the map) and, once a Pexels key is set, the photo sync.
3. Go to Voyasee Where to Stay > Settings & Footer and fill in:
   - Your real Booking.com affiliate URL (must be your dpbolvw.net link
     -- the button label "Booking.com" is fixed in code, not editable,
     per the affiliate registry). Pre-filled with real registry
     defaults for Booking.com and 7 other affiliate partners.
   - URLs for your other Voyasee tools (pre-filled with real
     voyasee.com links), to cross-promote in the permanent footer.
   - A free Pexels API key, if you want real neighborhood photos.
   - A free GeoNames username, if you want to bulk-add destinations by
     name instead of one at a time.
   - Your About blurb.
   Any field left blank simply won't appear -- nothing is guessed.
4. Add the shortcode to a page.

== Design ==

* Original animated illustration background (a flight-route network of
  dashed arcs between city nodes with traveling light pulses, a faint
  globe grid, small floating travel-icon glyphs, and a compass-rose
  watermark) behind glass-effect cards -- no external images, so
  there's no licensing risk and it stays crisp at any resolution.
  Respects prefers-reduced-motion.
* Match cards styled as boarding-pass/luggage-tag tickets with a
  perforated seam and a stamped Match Score badge.
* Match Score badge, 7-dimension radar chart, an infographic-style
  horizontal-bar comparison scorecard, a "Trip Reality" gauge strip for
  the top match, a relative-position "compass" diagram per
  neighborhood, and a city overview map plotted from real coordinates
  (drawing real neighborhood boundary polygons where OpenStreetMap has
  them mapped).
* A permanent, always-visible footer (it lives outside the quiz step
  container, so re-rendering steps never removes it) with five columns:
  About, Plan the Trip, Safety & Documents, Travel Partners, and Good
  to Know.

== How the Match Score works ==

Every neighborhood is scored across 7 transparent dimensions -- budget
fit (adjusted for how expensive the destination is overall), vibe fit,
attraction-interest fit, walkability, airport proximity, traveler-type
suitability, and safety comfort -- each weighted and explained in the
UI. Trip length adjusts the weighting (short trips favor airport ease,
long trips favor walkability). Deliberately rule-based, not a
black-box model, so every score can be explained to the traveler
(including the signature "Wrong Area Warning" -- the honest trade-off
line for whichever dimension scores weakest, and for 8+ night trips, a
split-stay suggestion when two matches are genuinely different and
close in score).

== Data sources and licensing ==

* OpenStreetMap (Overpass API) -- ODbL license, commercial use
  permitted with attribution (handled in the footer). Used only via
  daily cron batches, never on a live visitor request. Powers both the
  POI-density scores and (new in 3.0.0) real neighborhood boundary
  shapes where mapped.
* GeoNames -- CC-BY license, free, commercial use permitted,
  attribution required. Used for optional bulk destination seeding
  (name, coordinates, timezone). Requires a free username.
* Pexels API -- used for optional real neighborhood photos. Commercial
  use permitted, free, no attribution legally required by Pexels'
  license -- Voyasee credits the photographer anyway as good practice.
* All editorial fields (why_fits, why_caution, local_tip, best_for) are
  meant to be written in your own words -- never paste text from
  Wikivoyage, Wikipedia, or any blog.
* The bundled starter dataset auto-loads on activation: 36 destinations
  have real, named neighborhoods written from general well-documented
  travel knowledge (Tier 1). The remaining 120 destinations use honest
  generic zones ("City Center", "Business District", "Quiet
  Residential Area", or "Beachfront" for coastal towns) rather than
  invented neighborhood names for places we don't have confident
  street-level detail on (Tier 2) -- this is flagged to the traveler in
  the result page and in each zone's caution text. Expand any
  destination from Tier 2 to Tier 1 over time via the admin screens or
  CSV import once you have real local detail to add.

== What's deliberately simplified in this version (documented upgrade path) ==

* Real neighborhood boundary polygons (new in 3.0.0) depend on
  OpenStreetMap coverage, which varies a lot by city. Where no boundary
  is mapped, the overview map falls back to the existing dot marker --
  documented and expected, not a bug.
* The relation-to-polygon boundary sync takes the first clean outer
  ring from an OSM relation; genuinely complex multi-way boundaries are
  skipped rather than rendered incorrectly (falls back to the dot
  marker for that neighborhood).
* The Gutenberg block has no block.json/editor UI yet -- it renders
  correctly on the frontend; the shortcode is the recommended
  integration point for now.

== Roadmap ideas for a future version ==

Not built yet, in rough order of likely value:

* Travel Passport profile pre-fill -- if a visitor already has a
  Travel Passport trip profile, pre-filling this quiz from it would be
  a real integration advantage, but needs confirmation of that tool's
  actual data storage format before it can be built safely.
* Expanding more of the 120 generic-zone (Tier 2) destinations into
  fully named neighborhoods as real local detail becomes available.
* A lightweight visitor-facing "suggest a correction" link on each
  neighborhood, so locals/readers can flag outdated info.
* Caching Pexels photo results checks-ins (re-check for a fresher photo
  every few months) rather than fetching once and keeping forever.
* Optional multi-currency price-band display instead of a flat $-$$$$$
  scale.
* A proper block.json/editor UI for the Gutenberg block (currently
  frontend-only; the shortcode is the recommended integration point).
* A richer accessibility signal once a real, free, commercial-use-safe
  data source is identified (none was found during research -- OSM's
  wheelchair=* tags exist but coverage is too sparse to build a
  reliable score on yet).

== Changelog ==

= 3.2.0 =
* Fixed: a destination-relative long-trip (8+ nights) weighting bug where
  the walkability/airport weight shift didn't net to zero, so the Match
  Score could exceed 100 and walkability was over-weighted versus the
  documented design. Trip-length weighting now always sums to exactly 1.0.
* Fixed: neighborhoods that have never had an OpenStreetMap POI sync run
  were silently scored using the database's neutral default (50) as if it
  were real walkability/nightlife data -- both in the overall Match Score
  and in the auto-generated "why this fits" / Wrong Area Warning copy,
  which could describe an unsynced area as having "lower walkability"
  purely by coincidence of the default value. The matching engine now
  tracks per-dimension data confidence explicitly and every score surface
  (match card, radar chart, comparison scorecard, Trip Reality strip, and
  the dynamic copy) discloses an estimate consistently -- previously only
  2 of 5 surfaces did.
* Fixed: the "Not synced" label in the comparison scorecard rendered as
  broken, wrapped text fragments because it was squeezed into a ~42px
  column sized for a 2-3 digit score. Pending rows now use their own
  layout with room for the full label.
* Fixed: a partial OpenStreetMap sync failure (some but not all
  POI-category requests succeeding) could save a confidently-wrong
  walkability score of 0 while marking the neighborhood as "synced" --
  worse than staying unsynced. A partial failure is now treated as a full
  failure and retried on the next sync batch instead.
* Fixed: forcing a photo re-sync ordered neighborhoods by an unrelated
  OpenStreetMap sync timestamp instead of their own photo-sync history. A
  new `photo_last_synced` column now tracks this correctly.
* Fixed: the destination-wide data-tier disclaimer was read from whichever
  neighborhood happened to sort first alphabetically instead of the
  destination's own tier.
* Fixed: the overview map could silently drop neighborhood pins with
  missing coordinates (now shown with a small caption) and the legend
  could list an archetype with no matching pin on the map above it (both
  now share one filtered list, and overlapping/duplicate coordinates now
  fan out instead of hiding each other).
* Fixed: a document-level click listener was re-added every time the
  destination step re-rendered (Back / Start Over / Try Another
  Destination), leaking listeners on repeated navigation.
* Fixed: a neighborhood photo URL was interpolated into an inline style
  attribute without full escaping.
* Fixed: a second shortcode/block instance on the same page vanished with
  no indication why; editors/admins now see a small notice explaining
  only one instance renders per page.
* Improved: match cards now reveal with a staggered fade-in and the Match
  Score ring/number animate in on first view (skipped under
  prefers-reduced-motion); an unsynced match gets a visible "est." marker
  on its score badge and a dashed confidence ring instead of no
  indication at all.
* Improved: card depth (layered shadows, refined hover lift), fluid
  heading sizing, and tabular number alignment throughout the results
  page.

= 3.1.0 =
* Switched real neighborhood photos from Unsplash to Pexels, per
  request -- simpler API key setup (no OAuth-style Client-ID, key shown
  instantly on signup), a more generous free-tier rate limit better
  suited to a large destination count, and no required per-photo
  "download" tracking call. The old Unsplash Access Key setting is
  replaced by a Pexels API Key setting; existing photo URLs already
  fetched are untouched.
* Fixed: a destination pre-filled via the shortcode's destination
  attribute (e.g. a landing page using
  [voyasee_where_to_stay destination="tokyo"]) never actually enabled
  the Continue button on Step 1, because it only unlocked when a
  display name was also known, and the display name was never being
  looked up. The destination name is now resolved server-side, and the
  button now correctly enables whenever a valid slug is present even in
  the rare case a name can't be found.
* Fixed: a match card's photo now keeps its archetype color as a
  background fallback even when a photo URL is set, so a broken or
  slow-loading image no longer leaves a blank card -- it falls back to
  the right color instantly instead of a generic dark placeholder.
* Fixed: the comparison scorecard and the Trip Reality strip previously
  showed Walkability/Nightlife as a specific-looking number (50) for
  any neighborhood that has never had an OpenStreetMap POI sync run --
  indistinguishable from a real "average" score. They now honestly show
  "Not synced" instead, with a visibly different striped bar, so it's
  never mistaken for real data.
* Added: an "Add any new starter destinations" button (Import CSV
  screen) that safely tops up your database with any destinations from
  a plugin update that didn't auto-seed, without touching or
  overwriting anything you've already added or edited yourself --
  removes the need to fully delete and reinstall just to pick up new
  starter destinations after an update.
* Improved: the city overview map is significantly more visually rich
  -- a subtle coordinate grid, a vignette, a compass "N" marker, and a
  soft pulsing glow on each pin, rather than a mostly-empty dark box
  with plain dots.

= 3.0.0 =
* Dataset expanded from 106 to 156 destinations (478 neighborhoods total,
  up from 328) -- 50 new destinations added: 12 with real named
  neighborhoods (Munich, Lyon, Frankfurt, Fukuoka, Busan, Chengdu,
  Phuket, Da Nang, George Town/Penang, Beirut, Tbilisi, Medellín) and 38
  with honest generic zones for global breadth (spanning Europe, Asia,
  the Middle East, Africa, the Americas, and Oceania).
* NEW: destination-relative budget matching. Each destination now has a
  1-5 "cost index" (editable per destination, defaults to average) so a
  neighborhood's price band is read relative to how expensive its city
  is overall -- "$$$" means something different in an expensive
  destination than a cheap one, and the Match Score now accounts for that.
* NEW: trip length now genuinely affects the Match Score. Short trips
  (1-2 nights) weight airport ease higher; long trips (8+ nights) weight
  walkability higher. Previously this was collected but had zero effect.
* NEW: split-stay suggestions. For trips of 8+ nights where the top two
  matches are genuinely different in character and close in score, the
  results page now suggests splitting the stay across both instead of
  compromising on one -- a first for this category of tool.
* NEW: shareable result links. Every match now updates the page URL, so
  a result can be bookmarked or sent to a travel partner and reopens
  directly to the same match without retaking the quiz. A "Copy share
  link" button was added to the results header.
* NEW: downloadable match summary card (PNG), generated client-side, for
  saving or sharing a specific match outside the browser.
* NEW: a 4th comparison slot -- any "explored" neighborhood can now be
  promoted into the comparison scorecard alongside the top 3.
* NEW: data-freshness badge -- each neighborhood's last-reviewed date
  (already stored, previously never shown) now appears in its detail
  panel.
* NEW: real neighborhood boundary shapes. A new OpenStreetMap-based sync
  job (Voyasee Where to Stay > Sync Data) looks up each neighborhood's
  actual boundary polygon where OSM has one mapped, and the overview map
  now draws that real shape instead of always using a plain dot.
  Coverage varies by city (openly documented); neighborhoods without a
  match keep the existing dot marker.
* NEW: GeoNames bulk destination import (Voyasee Where to Stay > Import
  CSV). Paste a list of destination names and each is looked up and
  added automatically with real coordinates and timezone -- free,
  CC-BY licensed, commercial use permitted. Requires a free GeoNames
  username (Settings & Footer).
* NEW: an honest fix for the accessibility question -- rather than
  silently collecting an answer with no effect, checking "I need
  step-free access" now adds a transparent note that verified
  accessibility data isn't available yet and recommends checking listings
  directly, since inventing a fake accessibility score would be worse
  than admitting the gap.
* Added a "Relative cost index" field to the destination admin edit
  screen and to the destinations CSV format (cost_index column).

= 2.2.0 =
* Researched voyasee.com's actual live tool pages directly (not just
  memory) to confirm the established brand direction: an "ultra-premium
  dark navy" system with a glowing route/map motif and gold display
  type is already the deliberate, consistent identity across every
  Voyasee tool -- this plugin's navy/gold approach was validated, then
  pushed further to match that bar rather than replaced.
* Added a branded hero header above the quiz: eyebrow, tool wordmark
  title, tagline, and a live trust-badge row (destination/neighborhood
  count, matching methodology, data source), mirroring the badge-row
  pattern already used on voyasee.com's other tool pages.
* Added small floating travel-icon glyphs (document, pin, luggage tag)
  drifting through the background alongside the flight-route network,
  and increased the route-pulse glow intensity for a more luminous feel.
* Added a "Trip Reality" summary strip on the results page -- a gauge
  ring plus four stat chips for the top match, reusing terminology
  already established across Voyasee's other tools.
* All 15 Voyasee tool links and 8 affiliate links are now pre-filled
  with real, verified live URLs (confirmed via voyasee.com's own
  navigation and the affiliate registry) as working defaults -- no
  blank fields, no manual setup required to see a fully populated
  footer. Includes the Schengen Calculator and Airline Carry-On Size
  Checker tools. Every field can still be overridden from Settings.
* Footer reorganized into 5 columns (About, Plan the Trip, Safety &
  Documents, Travel Partners, Good to Know), matching the grouping
  convention already used in voyasee.com's own site-wide footer.

= 2.1.0 =
* Fixed: neighborhood names on match cards were invisible (a WordPress
  theme heading-color rule was occasionally beating the plugin's own
  color depending on cache/load order). All headings in the tool now use
  a hardened, specificity-safe color rule so this class of bug cannot
  recur even if new headings are added later.
* Fixed: the radar chart in the expanded match detail panel rendered as
  a blank box. The chart container now has a fixed, positioned size so
  Chart.js can reliably measure and draw into it; chart failures are now
  also logged to the browser console for diagnosis.
* Fixed: four buttons (Back, Show My Matches, Start Over, Refine my
  match) had a stray-quote markup bug that silently prevented their
  click handlers from attaching. All button bindings are now defensive
  (won't throw if a button is unexpectedly missing) and fetch/render
  failures are logged to the console instead of failing silently.
* Redesigned: match cards are now styled as boarding-pass/luggage-tag
  tickets -- a dashed perforation seam with punch-hole notches separates
  the photo/name from a "stub" containing a rotated ink-stamp Match
  Score badge, price band, and a decorative barcode strip.
* Redesigned: the animated hero background is now a flight-route network
  (dashed great-circle-style arcs between city nodes with a soft light
  traveling along each route, like a flight-tracker map) plus a faint
  globe-grid texture and a large low-opacity compass-rose watermark,
  replacing the earlier cartoon-cloud animation. Still pure CSS/SVG (no
  external images), still respects prefers-reduced-motion.
* Added: real neighborhood photos via the Unsplash API. Configure a free
  Unsplash Access Key under Settings & Footer, then fetch photos from
  Voyasee Where to Stay > Sync Data. Runs in small batches (never on a
  live visitor request), stores compliant photographer + Unsplash
  attribution with each photo, and fires Unsplash's required
  download-tracking ping once per photo at selection time. Neighborhoods
  without a configured key or without a fetched photo keep the existing
  solid archetype-color card background -- nothing breaks either way.

= 2.0.1 =
* Fixed four buttons whose click handlers failed to attach due to a
  markup typo, and bumped the version to force browsers/CDN caches to
  fetch the corrected files.

= 2.0.0 =
* Merged the data layer and the matcher into a single plugin, single
  shortcode, single REST namespace (voyasee-wtsm/v1).
* Full visual redesign: animated illustration background, glass cards,
  infographic comparison scorecard, permanent multi-column footer.
* Expanded starter dataset from 3 to 10 destinations / 40 neighborhoods.
* Expanded Settings screen: 6 affiliate-partner fields, 10 tool-link
  fields, About text.

= 1.0.0 =
* Initial two-plugin release (superseded).
