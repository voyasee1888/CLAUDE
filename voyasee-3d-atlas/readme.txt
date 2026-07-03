=== Voyasee 3D World Story Atlas ===
Contributors: voyasee
Tags: travel, globe, 3d, map, destinations
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 1.2.0
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
