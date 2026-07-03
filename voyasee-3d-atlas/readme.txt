=== Voyasee 3D World Story Atlas ===
Contributors: voyasee
Tags: travel, globe, 3d, map, destinations
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, ambient, auto-rotating 3D globe entry point into Voyasee's destination content.

== Description ==

Renders via the `[voyasee_3d_atlas]` shortcode. Built on a self-hosted, plugin-owned
destinations dataset (no dependency on any other Voyasee plugin), with a COBE-based
dot-matrix globe, live weather and country-intelligence enrichment on marker click via
Voyasee Weather Bridge and Voyasee Country Intelligence, and a fully crawlable
visible destinations list for SEO and no-WebGL fallback.

== Changelog ==

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
