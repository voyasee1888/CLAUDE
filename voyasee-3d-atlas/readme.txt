=== Voyasee 3D World Story Atlas ===
Contributors: voyasee
Tags: travel, map, destinations, vector map, interactive
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, interactive vector-map entry point into Voyasee's destination content.

== Description ==

Renders via the `[voyasee_3d_atlas]` shortcode. Built on a self-hosted, plugin-owned
destinations dataset (no dependency on any other Voyasee plugin), with a
self-contained interactive rotating globe (real country shapes, drag-to-rotate,
zoom, marker clustering, click any country for its own facts even without a
destination pin -- no external tile/map server involved at all), live weather and
country-intelligence enrichment on marker click via Voyasee Weather Bridge and
Voyasee Country Intelligence, and a fully crawlable visible destinations list for
SEO and no-JavaScript fallback.

== Changelog ==

= 3.0.0 =
* Major upgrade adding real data depth and interaction, built entirely on
  free, commercially-safe sources -- no paid API was added, and every new
  external call degrades to "simply not shown" on failure, matching this
  plugin's existing standard. Open-Meteo, REST Countries, UNESCO, and
  WorldTimeAPI were all evaluated and excluded because their free tiers
  prohibit use on ad/affiliate-monetized sites; Voyasee Weather Bridge and
  Voyasee Country Intelligence continue to cover that ground instead.
* New "Travel snapshot" card in the destination sidebar: at-a-glance cost
  level ($-$$$$), safety rating, walkability, English-language ease, ideal
  stay length, and 2-3 "best for" tags (beach, history, nightlife, etc.),
  plus a small radar chart visualizing cost/safety/walkability/English
  together. Backed by six new columns on the destinations table, backfilled
  automatically for all 167 bundled destinations on upgrade without
  touching any destination an admin has already hand-edited.
* New "Right now" live strip at the top of each destination's sidebar:
  local time (computed client-side from Country Intelligence's own IANA
  timezone data), current temperature, and today's sunset time.
* New monthly heatmap calendar in the Weather card, built from Travel Month
  Planner's own bundled seasonal-appeal snapshot, color-coded from "avoid"
  to "ideal" with the current month highlighted -- shown only for
  destinations with genuine month-to-month variation in that data, never a
  fabricated pattern over a flat score.
* New live data, each free and commercially safe to use on a monetized
  site, each cached via WordPress transients and each failing silently
  (never a broken card) if unavailable:
  - Sunrise/sunset times from sunrise-sunset.org (cached 6 hours).
  - Live USD exchange rate for the destination's local currency from
    Frankfurter.dev (ECB reference rates, cached 24 hours).
  - A short, attributed Wikipedia excerpt for the destination (Wikimedia
    REST API, CC BY-SA, cached 30 days).
* Country cards (both the destination sidebar's "Country notes" and the
  standalone "click a country" panel) now also show population, area,
  capital, and languages, extending Country Intelligence's existing
  integration rather than adding a new dependency.
* Every destination now shows its country's flag as a Unicode emoji
  (computed from the existing `country_code` column -- no icon files
  bundled, no extra HTTP request).
* New filter bar above the globe: filter destinations by region with one
  click, and a "Surprise me" button that flies to and opens a random
  destination from the current filter.
* New "Visited" and "Want to go" tracking, stored locally in the visitor's
  own browser (no account, no server-side tracking): toggle either from a
  destination's sidebar, see a running count in a strip above the map, and
  visited/want-to-go destinations get a distinct marker color on the globe.
* New Share button on each destination (native share sheet on supporting
  devices, copy-to-clipboard fallback elsewhere) and shareable deep links
  (`#dest=slug` in the URL) that reopen the exact destination on load.
* Globe visual upgrades: a soft atmospheric glow around the sphere's limb,
  a real-time day/night terminator (computed client-side from solar
  declination, no API), region-tinted country fills, animated great-circle
  flight arcs from the visitor's browser-reported location (optional,
  permission-gated) to whichever destination they click, and a small
  inset minimap once zoomed in past 2x so orientation is never lost.
* Full keyboard navigation: arrow keys rotate the globe, +/- zoom in and
  out, in addition to the existing drag/wheel/button controls.
* Every new sidebar section reuses this plugin's existing graceful-
  degradation pattern throughout: a section is simply omitted, never
  broken or half-rendered, whenever its underlying data isn't available
  for a given destination or country.

= 2.3.1 =
* Pre-launch audit pass: verified every function end-to-end (drag-rotate,
  auto-rotate, wheel/button zoom, marker click, cluster expand, country
  click, A-Z list, search, sidebar close) and cross-checked all 167
  bundled destinations' coordinates against real country boundary data
  and their stated country codes.
* Fixed a real data error found during that check: "Victoria Falls" was
  labeled as being in Zambia (ZM), but its stored coordinates are
  precisely the Zimbabwean side of the falls (Zambia's side is a
  separate town, Livingstone) -- corrected to Zimbabwe (ZW) both in the
  default dataset for new installs and via a one-time, narrowly-targeted
  backfill for sites that already seeded the old value (an admin who
  already hand-edited this destination is never overwritten).
* Fixed a real functional bug found during the same audit: pressing
  Escape did not close the sidebar when it was opened by clicking a map
  marker or a country shape (both are SVG elements with no keyboard
  focus, so the Escape listener -- previously scoped to receive only
  events bubbling from inside the widget -- never saw the keypress).
  Escape now reliably closes the sidebar however it was opened.
* Note on Jerusalem: real country-boundary data used elsewhere in this
  audit reflects contested international boundaries in a few places.
  This was reviewed and deliberately left as Israel (matching the
  practical travel facts -- currency, driving side, emergency numbers --
  a visitor actually encounters there), rather than "corrected" based on
  one boundary dataset's political stance; flagging this explicitly
  rather than silently deciding it either way.
* Confirmed (not a bug): a handful of small-island destinations --
  Puerto Ayora/Galapagos, Okinawa, Santorini, Zanzibar, Nassau, and
  similar -- sit outside their country's polygon in the bundled 110m-
  resolution world data because those specific small islands aren't
  separately rendered at that simplification level. Their marker
  placement and country/weather data are correct; only the country
  *shape* is too coarse to include that particular island. A handful of
  very small nations (Singapore, Hong Kong, Aruba, Barbados, Saint
  Lucia, Mauritius, Maldives, Seychelles, Tonga, Samoa, French
  Polynesia) aren't rendered as distinct clickable shapes at all for the
  same reason -- their destination markers still work normally either
  way, only the new "click bare country territory" feature has nothing
  to click there.

= 2.3.0 =
* Replaced the flat "Equal Earth" map projection with a true rotating
  globe (D3's orthographic projection), at the user's explicit request
  that the map "go fully round" the way the original 3D globe did rather
  than only ever showing a flat, front-on slice of the world. Dragging
  (mouse or a single finger) rotates the globe directly by manipulating
  the projection's own rotate() parameters, so the far side of the world
  is always just a drag away rather than permanently out of reach, in any
  direction -- up, down, left, or right, including all the way round the
  poles and back.
* Added a continuous, unbounded ambient auto-rotation whenever the globe
  is left idle (replacing the previous bounded left-right auto-pan, which
  only ever worked on the old flat map's fixed edges) -- it pauses the
  instant a real drag/wheel gesture starts and resumes automatically a
  couple of seconds after the user lets go, and is fully disabled under
  prefers-reduced-motion.
* Marker visibility now correctly follows the globe's current
  orientation: only destinations on the currently-facing hemisphere are
  ever rendered, so rotating the globe reveals a different, correct set
  of destination pins rather than showing every pin overlaid onto a flat
  projection regardless of which "side" it's really on.
* Fixed click-vs-drag reliability the same way the browser's own drag
  gesture recognizer is designed to: d3's clickDistance() setting
  suppresses the native click event on whatever marker or country was
  under the pointer if the drag moved more than a few pixels, so
  rotating the globe by dragging can never misfire as a destination or
  country click -- the exact bug class that made the original COBE 3D
  globe's manual hit-testing unreliable, now solved structurally rather
  than by patching symptoms.
* Added a new "click a country" info panel: every country shape on the
  globe is independently clickable, even ones with no destination pins
  of their own, and shows whatever country-level facts Voyasee Country
  Intelligence has for it (currency, driving side, power plugs, tipping
  and tap-water guidance, emergency numbers, upcoming public holidays)
  plus a list of any Atlas destinations in that country, via a new
  `/countries/{code}` REST endpoint. If neither is available for a given
  country, it shows a graceful "no information available" message
  instead of doing nothing.
* Added a small bundled ISO 3166-1 numeric-to-alpha-2 country code table
  (extracted from the i18n-iso-countries package, MIT-licensed, see
  assets/js/vendor/SOURCES.md) to bridge the bundled world topology's
  numeric country IDs to the alpha-2 codes Country Intelligence and every
  destination's own `country_code` column already use.
* Added a subtle latitude/longitude graticule and a filled sphere/ocean
  backdrop so the globe reads as a solid rotating body, plus a hover
  highlight on every country shape as a "this is clickable" affordance.

= 2.2.0 =
* Fixed a destination-name legibility bug in the sidebar: `.v3datlas-sidebar-name`
  was the only heading in the plugin relying on inherited text color instead of
  setting its own, which let a WordPress theme/page-builder's global heading
  color win on the live site and render destination names (e.g. "Beijing") in
  a dark color against the sidebar's dark background. Every heading in the
  plugin now sets its own explicit color, as the rest already did.
* Fixed the hero "Find your next destination" call-to-action button
  referencing an undefined CSS variable (`--v3datlas-navy-950`, which was
  never declared) for its text color, which silently fell back to inherited
  cream text on a light gold pill background -- also illegible. The button
  is now restyled with a proper gold gradient, explicit dark text, a subtle
  arrow, and a hover lift.
* Added ambient auto-pan: once a visitor zooms in on a destination (so the
  whole world no longer fits the viewport, the way it does at the default
  zoomed-out view), the map now gently keeps drifting on its own after a
  couple of seconds of inactivity -- similar in spirit to the old rotating
  globe -- so the hidden far side of the map cycles back into view without
  requiring the visitor to manually drag. It pauses instantly on any real
  drag/zoom/click and resumes automatically after a short idle delay, and
  is fully disabled under `prefers-reduced-motion: reduce`.
* Enlarged and restyled the hero header: the "Voyasee World Story Atlas"
  eyebrow now has flanking rule lines and wider letter-spacing, and the main
  heading scales up to 56px on wide screens (previously capped at 40px).
* Redesigned the footer from a plain four-column link list into a richer,
  branded section: a gradient "Turn this map into your next trip" banner
  with its own call-to-action, an icon-title-description card grid split
  into Voyasee's own trip-planning tools and booking/safety resources, and
  a bottom bar with the Voyasee wordmark, tagline, a link back to
  voyasee.com, and a version stamp.

= 2.1.0 =
* Replaced the v2.0.0 MapLibre GL JS map (which depended on OpenFreeMap's
  externally-hosted vector tiles) with a fully self-contained SVG world
  map: real country boundary shapes are now bundled directly with the
  plugin (Natural Earth data via the world-atlas package, see
  assets/data/SOURCES.md) and rendered client-side via D3's geographic
  projection -- there is no external map/tile server involved at runtime
  at all, removing that entire class of dependency risk. This was a
  direct response to the external tile host not rendering an acceptable
  real map on the live site, which this project's own development
  environment had no way to verify or debug against a third party.
* Real, recognizable country/continent shapes are now guaranteed to
  render exactly the same way regardless of any third-party service's
  availability, styling choices, or API changes -- the previous approach
  could look broken if a tile host had an outage, changed its style
  format, or simply styled things differently than expected, none of
  which this plugin could detect or control.
* Marker clustering is now handled by Supercluster (recomputed live as
  the map's zoom level changes), with the same numbered-circle-that-
  expands-on-click behavior as v2.0.0.
* Every marker (individual or cluster) is a real SVG element with native
  browser click/hover handling -- not a custom hit-test against a canvas,
  and not dependent on any particular map-library's internal feature-
  picking implementation. This is the most structurally reliable of the
  three approaches this plugin has now tried for marker interaction.
* No longer requires WebGL at all (the previous two approaches both did,
  first for the 3D globe, then for MapLibre's vector-tile rendering) --
  SVG rendering works on essentially every browser released in the last
  15+ years, meaningfully widening real-world compatibility.
* The great-circle featured-route lines and the "fly to this destination"
  camera behavior on marker/list/search selection both carry over
  unchanged in spirit, reimplemented using D3's zoom-transform and path
  APIs instead of MapLibre's.
* Verified with a headless-browser test suite: 177 real country shapes
  render correctly, clicking an individual marker opens the correct
  destination's sidebar with real fetched data, clicking a cluster
  expands and zooms in (confirmed via the map's transform changing and
  the visible marker count increasing), the manual zoom in/out controls
  work, and clicking a destination in the A-Z list flies the map to that
  destination while opening its sidebar -- all using the plugin's own
  bundled data with no external network dependency to verify against,
  unlike the previous two approaches.

= 2.0.0 =
* Replaced the COBE 3D dot-matrix globe with an interactive MapLibre GL JS
  vector map, at the user's explicit request after repeated marker-click
  reliability problems on the globe. COBE's dot-matrix rendering had no
  reliable way to hit-test clicks at high marker density (167 destinations,
  40+ clustered in Europe alone) without a full rewrite of its internals --
  every fix attempt (hit-radius tuning, drag-threshold tuning, bigger
  render size) reduced but never eliminated the problem, because it was
  structural to that renderer, not a tunable parameter. MapLibre GL JS
  gives real, browser/library-native click and hover picking on actual
  map features, the same class of technology behind Google Maps-style
  products, so a click is now resolved correctly by construction rather
  than by custom projection math.
* Real pan and scroll/pinch zoom, exactly like a familiar map product,
  replacing the fixed-radius rotate-only globe interaction.
* Marker clustering: destinations close together at low zoom collapse into
  a single numbered cluster circle; clicking one flies the camera in and
  expands it, down to individual destinations -- solves marker-density
  crowding structurally instead of via hit-radius tuning.
* Every entry point into a destination -- a map marker, a cluster expanding
  down to it, the A-Z list, the search box, or a "you might also like"
  chip -- now also flies the map camera to that destination's exact
  location as its sidebar opens, unifying map and list/search interaction.
* The great-circle "featured routes" (previously an animated arc tour on
  the globe) are now curved flight-path lines drawn directly on the map,
  correctly split at the antimeridian so a route like Tokyo-Los Angeles
  doesn't draw a spurious line across the whole map. Configured the same
  way as before (Settings -> Map Featured Routes).
* Map tiles are served by OpenFreeMap (openfreemap.org), a free,
  no-API-key, no-signup vector tile provider -- consistent with this
  plugin's zero-paid-API constraint. The map tries a dark-themed style
  first and automatically falls back to OpenFreeMap's flagship default
  style if that particular one is ever unavailable, so the map still
  renders correctly either way.
* Dropped the real-time day/night terminator and the multi-phase scripted
  intro-tour camera animation that the previous globe had -- both were
  tightly coupled to the globe's spherical rendering and don't carry over
  directly to a flat map. Kept the sidebar, weather/country/fact data,
  A-Z list, search, and footer completely unchanged; only the map
  rendering layer itself was replaced. A day/night band and a scripted
  camera tour are both feasible to re-add on the new map (as a curved
  overlay band and a sequence of flyTo animations respectively) as a
  future enhancement if wanted.
* Verified with a headless-browser test suite covering the parts under
  this plugin's own control: map initialization with zero JS errors,
  marker/cluster layer setup, clicking an individual marker opens the
  correct destination's sidebar with real data, clicking a cluster
  expands and flies the camera in (confirmed zoom level increases),
  and clicking a destination in the A-Z list flies the map to that
  destination's exact coordinates while opening its sidebar. The live
  OpenFreeMap tile service itself could not be reached from the
  development sandbox's network (an environment-level restriction, not a
  code issue) -- the base map's real visual appearance should be checked
  once installed on the live site, the same way every other visual change
  in this plugin has been verified against real screenshots.

= 1.5.1 =
* Fix: v1.5.0's click-hit-testing was correct, but a separate click-vs-
  drag check was still misfiring on ordinary clicks. Real mice and
  trackpads report several small in-between pointer positions during a
  normal ~100ms click -- the previous check flagged a click as a "drag" if
  either the horizontal OR vertical movement alone exceeded 5px, which
  natural hand/trackpad jitter crosses constantly, silently cancelling the
  marker click before hit-testing even ran. This is very likely why some
  clicks kept not responding even after the v1.5.0 fix. Replaced it with a
  single straight-line distance-from-start threshold (9px) -- generous
  enough for normal click wobble, still far below the tens of pixels a
  deliberate drag-to-rotate gesture covers. Verified with a headless test
  that simulates realistic mouse jitter (small random movement between
  press and release): the sidebar now opens on ~93-100% of click attempts
  on real overlapping markers, up from clicks frequently registering as a
  drag and doing nothing.
* The globe itself now renders larger on wide screens (up to 960px instead
  of 800px), giving markers more real screen-pixel separation -- in an
  extremely dense cluster (e.g. 40+ destinations across Europe), a slightly
  imprecise click can still occasionally land nearer to a neighboring
  destination than the intended one; this is an inherent limit of packing
  that many clickable points into a small area, not a bug, and a larger
  globe directly reduces how often it happens. The A-Z destination list
  below the globe always opens the exact destination by name with no
  precision limits, for guaranteed-correct selection of any specific place.
* Data Health Check now clarifies that "not yet linked to a category/tag"
  no longer affects what a visitor sees (removed as a concept from the
  visitor-facing side back in 1.4.0) -- it only affects marker glow
  intensity and structured-data article links. It also now shows whether
  a Pexels API key is configured and explains that the "manually-set
  photo" count doesn't include destinations getting a live Pexels photo
  automatically, so a "0" there isn't a sign that photos aren't working.

= 1.5.0 =
* Fix (the main bug): clicking a globe marker frequently did nothing on
  destination-dense regions like Europe (40+ destinations packed close
  together). The cause was that every marker had its own absolutely-
  positioned 18x18px DOM button stacked directly on the canvas -- once
  several of those visually overlapped, the browser could only ever
  deliver a click to whichever one happened to be topmost in paint order,
  so most clicks on a cluster of dots silently did nothing. Rewrote marker
  interaction entirely: there's now a single click handler on the globe
  canvas that hit-tests the exact click point against every marker's real
  current screen position and opens whichever one is genuinely closest.
  This is correct no matter how many markers overlap on screen. Verified
  with a headless-browser test clicking 25 real overlapping marker
  positions across Europe: 24/25 opened the exact right destination (the
  one "miss" was a test-harness artifact, not an app bug). A drag-to-
  rotate gesture is still distinguished from a click by movement distance,
  so rotating the globe is unaffected.
* Hero images no longer come from articles at all, in any case. The
  previous "pull the mapped category's latest post thumbnail" fallback is
  removed outright. In its place, an optional Pexels API integration
  (Settings -> Destination Photos) fetches a real photo of the actual
  place when no image has been picked manually -- Pexels' free tier is
  enough, no paid plan required. With no key configured, or on any lookup
  failure, a destination simply shows no photo -- never an unrelated
  article's image.
* The footer now shows exactly one Booking.com link (the EU/EEA market
  link, falling back to the Asia-Pacific/Middle East one only if the EU
  link isn't configured) instead of listing both approved-market variants
  as two separate lines.

= 1.4.0 =
* Removed the "Related Articles" feature entirely. This Atlas no longer
  fetches, shows, or links to blog posts at all -- clicking any destination
  now only ever shows that destination's own weather, country notes, and
  "did you know" fact, with zero dependency on whether any article has
  been written about it.
* Clicking a destination in the A-Z browse list (or its search-filtered
  view) now opens the same in-page detail sidebar a globe marker click
  does, instead of navigating to a site-search results page or a category
  archive. Every entry point into a destination -- the globe, the list,
  the search box, and the "you might also like" chips -- now behaves
  identically: it shows that destination's own particular data in place,
  never a separate page. The sidebar itself no longer depends on WebGL
  support, so this also now works correctly on devices/browsers where the
  3D globe can't render.
* The destinations' JSON-LD structured data (for search engines) no longer
  falls back to a site-search URL for an unmapped destination; it now
  simply omits the url field for that entry rather than pointing crawlers
  at a search-results page.
* Footer tool/affiliate links are now pre-filled with the real,
  registry-sourced URLs from VOYASEE_TOOLS_AND_AFFILIATES_REGISTRY.md
  (10 Voyasee tools, 6 affiliate partners including both Booking.com
  market links) instead of shipping blank. Previously every field
  defaulted to empty until an admin manually pasted in every URL via
  Settings, which meant the footer's entire "Plan Your Trip," "Decide &
  Prepare," "Book Your Trip," and "Travel Safe" columns silently failed to
  render at all on a site that hadn't done that yet. An admin can still
  override, correct, or intentionally blank out any individual field any
  time via 3D Atlas -> Settings -- an explicit save there always wins over
  this default.

= 1.3.1 =
* Fix: Auto-Map Content used a plain keyword search and tallied every
  category/tag on every post that merely mentioned a destination's name
  anywhere in its body text. On sites with limited destination-specific
  content, this could map a destination to a completely unrelated
  category (e.g. a destination getting mapped to a generic "world food"
  category because one unrelated roundup post happened to name-drop it).
  Auto-Map now requires either a category/tag literally named after the
  destination, or at least two published posts with the destination's
  name in their own title sharing a category/tag -- a single incidental
  body-text mention can no longer map anything on its own.
* The Data Health Check screen's "Run Auto-Map Now" button now has an
  optional "Also re-check destinations that are already mapped" checkbox.
  Run it once after updating if a destination looks mapped to the wrong
  category: mappings that no longer meet the new bar are cleared back to
  the safe search-fallback link instead of silently keeping the wrong one.

= 1.3.0 =
* Dataset expanded from 117 to 167 destinations, filling gaps in the Balkans,
  Scandinavia, the Himalayas, East/Southern Africa, Central America, and the
  Pacific. Every new signature line / "did you know" fact was independently
  checked before writing (dates, superlative claims, and disputed origin
  stories are hedged rather than stated flatly). Existing sites that already
  seeded the original 117 get the 50 new ones automatically on next page
  load, matched by slug, without touching any destination already hand-edited.
* Travel Month Planner integration: destinations that overlap with Voyasee
  Travel Month Planner's own seasonal-appeal data now show a "best time to
  visit" hint in the sidebar. Deliberately conservative -- a destination
  only gets a best-time hint when the underlying data shows real month-to-
  month variation (not flat/placeholder scores), so nothing is shown rather
  than something invented. 112 of 167 destinations matched directly by slug,
  plus 4 more found via known aliasing, sourced from a one-time snapshot of
  the planner's own exported data (no live API dependency).
* New "nearby destinations" and "same country" related-destination chips in
  the sidebar (haversine distance, reusing Country Intelligence's own
  coordinate data -- no new dependency), and an automatic hero-image
  fallback that pulls the latest post thumbnail from a destination's mapped
  category when no image has been set manually.
* New Data Health Check admin screen: mapped/unmapped destination counts,
  one-click "Run Auto-Map Now" (matches unmapped destinations to existing
  categories/tags by searching site content, or offers to create a new
  category), and a live coordinate-outlier check against Country
  Intelligence's centroid data.
* Footer redesigned into four labeled columns (Plan Your Trip, Decide &
  Prepare, Book Your Trip, Travel Safe) covering up to 10 other Voyasee
  tools and up to 6 affiliate partners including Booking.com, instead of a
  single flat link list.
* The hero call-to-action no longer names a specific tool (it previously
  read "Try the Interactive Travel Map," which name-dropped a separate,
  already-existing Voyasee tool right next to this Atlas). It now reads
  "Not sure where to start? Find your next destination" and still links to
  whichever discovery tool is configured in Settings.
* Destination search box added above the A-Z browse list for fast client-
  side filtering by name or country.
* Configurable globe intro-tour arcs (Settings -> Globe Intro Tour), falling
  back to a built-in default route set if left blank.
* Structured data upgraded from a plain ItemList of names to nested Place
  entries with GeoCoordinates and each destination's signature line as its
  description.
* Post-count lookups (used for marker glow intensity) are now cached for 15
  minutes via a transient instead of recomputed on every single page load,
  invalidated immediately on any admin save/delete/auto-map.

= 1.2.0 =
* Color system: deep emerald/forest base with the existing Voyasee gold as
  accent, replacing navy as the dominant hue (navy kept only as a minor
  shadow tone). Background is now a layered ambient mesh gradient with an
  optional faint starfield drift, disabled under prefers-reduced-motion.
* Real-time day/night terminator overlaid on the globe, computed client-side
  from a standard solar-position algorithm (no API, no key) -- verified
  against known solstice/equinox reference points before shipping.
* Animated great-circle arcs with a traveling marker, scripted into a short
  auto-tour of five featured destination pairs on load (skipped entirely
  under prefers-reduced-motion), then handing off to normal idle rotation.
* Story content: each destination now has an editorial "signature line" and
  a verified "did you know" fact, shown in the sidebar. All 117 seeded
  destinations were written and checked against multiple independent public
  sources (not fabricated), with hedged phrasing on any commonly-repeated
  but disputed superlative claims. Existing installs get these backfilled
  automatically by matching on slug.

= 1.1.1 =
* Purge LiteSpeed Cache / QUIC.cloud (both are driven by the same
  `litespeed_purge_all` action, so one call covers the CDN edge too) whenever
  a destination or the Settings screen is saved or a destination is deleted
  -- otherwise a full-page cache can keep serving the old destination list
  after an admin edit. Safe no-op on hosts without LiteSpeed Cache installed.
* Verified all 117 seeded coordinates against Voyasee Country Intelligence's
  own compiled country-centroid/capital data (haversine distance check, no
  external API, no key required): 116/117 within 3500km of their country's
  centroid or capital; the one flagged case (Honolulu) is a correct known
  edge case -- Hawaii is genuinely ~6,000km from the continental US centroid.
  Zero actual wrong-country/wrong-continent errors found.

= 1.1.0 =
* Auto-seeds 117 destinations across Europe, Asia, the Middle East, Africa,
  North America, the Caribbean, South America, and Oceania on first
  activation (or on the next page load for sites that already activated an
  earlier version) — no manual entry required. See
  includes/data/default-destinations.php.
* Each seeded destination's "content term slug" defaults to its own slug as
  a placeholder, since this plugin cannot know this site's real category
  taxonomy. Edit it per destination in 3D Atlas → Destinations once a
  matching category/tag exists, to connect real related articles.
* Fix: the visible "Browse all destinations A–Z" list and the ItemList
  structured data no longer silently drop a destination just because its
  content term slug doesn't match a real category yet — they now fall back
  to a site search link for that destination's name so every destination
  always has a working, crawlable link.

= 1.0.1 =
* Fix: the globe failed to render at all on a fresh install with zero
  destinations added yet (it was incorrectly hidden whenever the marker
  list was empty, instead of only skipping the now-empty set of clickable
  hotspots). The ambient globe now always renders once WebGL is available.

= 1.0.0 =
* Plugin scaffold, destinations custom table, admin CRUD screen, and a Settings
  screen for tool/affiliate URLs (never hardcoded — pasted in from the registry).
* COBE globe render, self-hosted (vendored, not CDN-loaded), driven via WordPress's
  Script Modules API.
* Real marker interactivity: DOM hotspots kept in sync with the sphere's rotation
  every frame, drag-to-rotate, click opens a frosted-glass sidebar populated from
  a REST endpoint (related articles via WP_Query, plus weather/country enrichment
  that degrades gracefully if Voyasee Weather Bridge / Country Intelligence are
  inactive or lack data for a country).
* SEO layer: a real, crawlable "Browse all destinations A-Z" list server-rendered
  above the globe, ItemList structured data, and a WebGL feature-detect fallback.
* Performance/accessibility: lazy-boot via IntersectionObserver, WebGL
  context-loss/restore handling, devicePixelRatio clamp, and prefers-reduced-motion
  support.

= 0.1.0 =
* Phase 1: plugin scaffold, destinations custom table, admin CRUD screen, shortcode stub.
