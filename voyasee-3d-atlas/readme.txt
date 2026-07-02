=== Voyasee 3D World Story Atlas ===
Contributors: voyasee
Tags: travel, globe, 3d, map, destinations
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 1.0.1
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
