=== Voyasee Where to Stay Matcher ===
Contributors: voyasee
Tags: travel, hotels, neighborhoods, quiz
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 4.2.0
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

1. Activate the plugin. A starter dataset of 156 destinations and 463
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
5. Optional (new in 4.0.0): if you also run Voyasee Weather Bridge and/or
   Voyasee Country Intelligence, this plugin detects and uses them
   automatically -- no settings to configure here. Weather Bridge powers
   the weather snapshot; Country Intelligence powers the "Good to know"
   panel, currency-aware pricing, and public-holiday overlap warning.
   Neither is required -- everything else works exactly the same without
   them. Each destination needs a 2-letter country code for these to
   activate (Voyasee Where to Stay > Destinations > edit a destination);
   it's auto-filled from the existing Country field where recognized.

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
* The bundled starter dataset auto-loads on activation: 51 destinations
  have real, named neighborhoods written from general well-documented
  travel knowledge (Tier 1) -- as of 4.2.0 this includes 15 of the
  world's most globally searched destinations (Los Angeles, San
  Francisco, Chicago, Miami, Las Vegas, Rio de Janeiro, Buenos Aires,
  Cape Town, Bali (Denpasar), Kuala Lumpur, Cancun, Delhi, Kyoto,
  Shanghai, and Beijing), upgraded from generic zones to real named
  neighborhoods. The remaining 105 destinations use honest generic zones
  ("City Center", "Business District", "Quiet Residential Area", or
  "Beachfront" for coastal towns) rather than invented neighborhood
  names for places we don't have confident street-level detail on
  (Tier 2) -- this is flagged to the traveler in the result page and in
  each zone's caution text. Expand any destination from Tier 2 to
  Tier 1 over time via the admin screens or CSV import once you have
  real local detail to add. A site that already seeded the old generic
  zones for those 15 destinations migrates automatically the first time
  it loads after updating to 4.2.0 -- see WTSM_Tier1_Upgrade.

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
* Expanding more of the 105 remaining generic-zone (Tier 2) destinations
  into fully named neighborhoods as real local detail becomes available.
* Caching Pexels photo results checks-ins (re-check for a fresher photo
  every few months) rather than fetching once and keeping forever.
* A proper block.json/editor UI for the Gutenberg block (currently
  frontend-only; the shortcode is the recommended integration point).
* A richer accessibility signal once a real, free, commercial-use-safe
  data source is identified (none was found during research -- OSM's
  wheelchair=* tags exist but coverage is too sparse to build a
  reliable score on yet).

== Changelog ==

= 4.2.0 =
* Added: 15 of the bundled starter dataset's most globally-searched Tier 2
  destinations upgraded to Tier 1, gaining real, named neighborhoods
  instead of generic zones -- Los Angeles (Hollywood, Santa Monica), San
  Francisco (Fisherman's Wharf, Mission District), Chicago (The Loop,
  Wicker Park), Miami (South Beach, Brickell), Las Vegas (The Strip,
  Downtown/Fremont Street), Rio de Janeiro (Copacabana, Ipanema), Buenos
  Aires (Palermo, Recoleta), Cape Town (City Bowl, Camps Bay), Bali/
  Denpasar (Seminyak, Kuta), Kuala Lumpur (Bukit Bintang, KLCC), Cancun
  (Zona Hotelera, El Centro), Delhi (Connaught Place, Hauz Khas), Kyoto
  (Gion, Arashiyama), Shanghai (The Bund, French Concession), and Beijing
  (Wangfujing, Sanlitun). Sites that already seeded the old generic zones
  for these destinations are migrated automatically and safely the first
  time the site loads after updating -- any of these rows a site owner
  has since hand-edited are left untouched rather than being replaced.
* Added: Air Quality now shown in the Trip Facts strip, via the Voyasee
  Weather Bridge sibling plugin's existing air-quality lookup (already
  integrated for weather, but never actually called anywhere until now).
  Shown as the source's own index number and category label, with no
  good/moderate/unhealthy banding invented on this end, since the exact
  index scale isn't a value this plugin can confirm.
* Added: a "Suggest a correction" link on each neighborhood's detail
  panel. A visitor's note is emailed straight to the site admin (rate-
  limited per connection) -- nothing is ever auto-applied to the data
  from an anonymous submission, the same human-in-the-loop trust model as
  every other editorial field in this plugin.
* Added: "Compare with another destination" on the results page -- pick
  a second city and see its top match side by side with your current
  top match, re-using the exact same answers you already gave. Built
  entirely from the existing /match endpoint (no matching-engine or
  scoring changes at all).

= 4.1.2 =
* Fixed: the 4.1.1 fix for the invisible destination-field text while typing
  wasn't strong enough on this site -- a class-based CSS rule
  (.vwtsm-root .vwtsm-input:focus) can still lose a specificity contest
  against certain theme/page-builder styling (Elementor in particular
  scopes its own CSS to auto-generated container IDs, which outrank plain
  class selectors). Re-anchored the fix directly to each field's own
  #id (#vwtsm-destination, #vwtsm-nights, #vwtsm-travel-date), which beats
  virtually any class-based competitor regardless of where it comes from.

= 4.1.1 =
* Fixed: the destination field could go invisible (cream text on a
  white background) while actively being typed into, on themes that ship
  their own input:focus style. The field's normal (correct, dark) styling
  only reasserted itself once the field lost focus -- e.g. right after
  clicking an autocomplete suggestion -- which read as "my typing doesn't
  show up, but the picked result does."
* Fixed: the date field's native calendar icon had no styling at all, so
  browsers drew it in their default near-black ink -- invisible against
  this tool's dark fields.
* Changed: the Pexels photo sync now runs hourly (was daily) and fetches
  50 neighborhoods per run (was 20). Pexels is a dedicated, generously-
  quota'd commercial key (200 req/hour, 20,000/month) rather than a
  shared community resource like the OpenStreetMap/Wikipedia syncs, so
  there was no reason for it to be this conservative -- at the old pace a
  full destination catalog's first photo pass took weeks, which looked
  like "photos are broken everywhere except the one destination that
  happened to sync first" rather than "still catching up." Existing
  installs are migrated to the faster schedule automatically, no action
  needed.

= 4.1.0 =
* Added: a plain-language "why this topped your matches" narrative on the
  results page -- one sentence built entirely from the traveler's own
  answers and the same dimension scores already shown in the radar chart,
  naming the single strongest reason a neighborhood matched. Not a
  generic description; it changes with the answers and the neighborhood.
* Added: a qualitative match-confidence label (Excellent/Strong/Good/Fair
  fit) plus a count of genuinely strong, confirmed-data dimensions,
  shown next to the Match Score.
* Added: a "good for a late-night arrival" badge, derived from a
  neighborhood's existing airport-time and safety data -- no new
  data source, just an honest recombination of numbers already scored.
* Added: an optional per-destination "seasonal note" field (admin-written,
  e.g. cherry-blossom crowds or typhoon season) shown alongside the
  weather snapshot when set; the weather snapshot's own wording now also
  flags a "notably rainy" or "typically dry" month using the real
  rain-day estimate already fetched, rather than a bare number.
* Added: a walking-distance ring (~10 min) around each matched
  neighborhood on the overview map, and an airport marker when the
  destination has one on record -- both using coordinates already stored,
  no new data source.
* Added two more trust badges to the hero header (no login required,
  free/no account needed).
* Considered and deliberately skipped: granular fabricated cost estimates
  (average taxi/coffee/metro prices) -- no free, reliable, per-city
  source for this exists (the same conclusion reached researching a
  cost-of-living index for v4.0), and inventing plausible-sounding numbers
  would undermine the tool's core "never guess where data doesn't exist"
  principle. The existing currency-aware nightly price *range* (from the
  destination's own price_band + a real live exchange rate) is a
  defensible estimate; a fabricated daily-expense breakdown is not.

= 4.0.0 =
* Fixed: the optional Weather Bridge integration stub called a function
  name (voyasee_wb_get_forecast) that never existed in that plugin, so it
  had silently returned nothing since it was written. Now calls Weather
  Bridge's real functions -- a near-term (up to 15-day) forecast when a
  travel date is close, or NASA POWER climate normals (free, keyless,
  never fails for lack of an API key) for "typical weather this month"
  when the trip is further out.
* Added: an optional "Travel start date" field on Step 1. Powers a new
  Trip Facts strip on the results page -- a weather snapshot, a
  jet-lag-at-a-glance readout (pure client-side time-zone math, no API),
  and a public-holiday overlap warning for your dates.
* Added: optional integration with Voyasee Country Intelligence, if
  active -- a "Good to know" panel with driving side, plug type, tipping
  guidance, and emergency numbers for the destination's country, plus a
  currency-aware price estimate (via Country Intelligence's currency data
  + Frankfurter's free, no-key exchange rates) alongside the existing
  $-$$$$$ price band. A destination now stores an ISO country code
  (auto-filled from its existing country name where recognized) to power
  this.
* Added: real named landmarks near each neighborhood (a new daily-batched
  sync against Wikipedia's GeoSearch API -- titles and coordinates only,
  never article text) shown as small chips on match cards.
* Added: a "daily convenience" signal -- supermarket/pharmacy/cafe/park
  counts folded into the existing OpenStreetMap POI sync, shown as a
  walking-distance stat in each match's detail panel. Informational only,
  not folded into the Match Score weighting.
* Added: "Similar neighborhoods elsewhere" -- a cross-destination
  suggestion (same archetype as your top match, in a different city),
  computed entirely from data already in your own database.
* Added: OSM-assisted Neighborhood Discovery (Voyasee Where to Stay >
  Neighborhood Discovery) -- finds candidate neighborhood names and
  coordinates for a destination from OpenStreetMap and adds them as
  review-queue drafts. A draft is never visible to visitors and never
  auto-publishes; an admin still sets the real archetype and writes
  why_fits/why_caution by hand before publishing, exactly like adding a
  neighborhood any other way. Meant to remove the slowest step in growing
  destination coverage (finding out what a city's neighborhoods are even
  called), not the editorial judgment.
* All of the above are optional, soft-dependency integrations
  (function_exists-gated) -- nothing above requires a paid API, and
  nothing breaks or looks broken if Weather Bridge/Country Intelligence
  aren't installed, or if a destination has no country code set yet.

= 3.4.0 =
* Fixed: the Step 1 destination and "how many nights" fields could render
  as a plain unstyled white box with default browser text instead of the
  tool's dark glass-style input -- the same class of bug already fixed for
  headings in 2.1.0 (a WordPress theme's own input styling, or Chrome's
  autofill layer, silently beating the plugin's styling), just never
  hardened for form fields until now. Input background/border/text color
  are now defensively protected the same way headings already are, plus a
  fix for Chrome's autofill repainting the field white after a suggestion
  is picked.
* Improved: the results page is now a genuine multi-color infographic
  instead of a single-hue navy/gold scorecard. Each scoring dimension
  (Budget, Walkability, Nightlife, Airport, Safety) now has its own fixed
  identity color and icon -- validated colorblind-safe against this
  plugin's navy surface -- used consistently in the Trip Reality strip and
  the comparison scorecard, so the same metric reads as the same color
  everywhere it appears. The Price & Logistics row is now a row of colored
  icon pills instead of a single plain text string. Match Score stays gold
  everywhere, matching its "hero number" role.
* Improved: the footer is more visually distinct -- a brand-gold gradient
  seam across the top, a subtle grid/glow texture, a compass emblem next
  to the Voyasee wordmark, a small icon on every column heading, dashed
  vertical seams between columns (echoing the match card's perforated
  ticket seam), and an animated arrow on link hover.

= 3.3.0 =
* Replaced the "Where these areas sit" overview map's abstract dot-grid
  diagram with a real, geographically accurate interactive map (Leaflet +
  a free CARTO "Dark Matter" basemap -- no API key required, CC-BY/BSD-3
  licensed, free for any use with attribution, styled to match the tool's
  dark navy design). Real neighbourhood boundary polygons (already fetched
  by the boundary sync job) now draw in their true geographic position
  instead of being projected onto a non-geographic grid, pins sit at their
  true coordinates, and you can pan/zoom/scroll the map itself. We
  deliberately did not call tile.openstreetmap.org's raster tiles directly
  -- that service's usage policy forbids "heavy use" from a redistributed
  plugin, which this basemap choice avoids. If the map CDN can't load (an
  ad-blocker, restrictive CSP, offline preview), the tool automatically
  falls back to the previous relative-position diagram -- nothing breaks
  either way.

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
