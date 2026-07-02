# Voyasee Where to Stay Matcher 3.3.0 — a real map

## What was reported

New screenshots of the live v3.2.0 site confirmed the earlier fixes are working correctly in production (real Pexels photos loading, the "Not synced yet" label rendering cleanly instead of the previous broken/wrapped text, "EST." confidence badges, the confidence summary note). The one remaining issue: the "Where these areas sit in {destination}" overview map didn't look like an actual map — just colored dots on a plain dark grid with no real geography, streets, or city context.

## Root cause

The overview map was deliberately built as an abstract, hand-drawn SVG/CSS diagram (`buildOverviewMap` in `matcher.js`) rather than a real map, specifically to avoid embedding a real basemap tile service — a reasonable licensing precaution at the time, but the result reads as "not a real map" to a visitor, which is exactly what was reported.

## Fix

- Added Leaflet.js (MIT license, ~40KB, same CDN-enqueue pattern already used for Chart.js in this plugin) and a free CARTO "Dark Matter" basemap (OSM data, CC-BY 4.0 / BSD-3-Clause, no API key required, free for any use with attribution — see https://carto.com/basemaps). We deliberately avoided calling `tile.openstreetmap.org`'s raster tiles directly: that service's tile usage policy forbids "heavy use," which a plugin redistributed to many WordPress sites would risk triggering. CARTO's basemap CDN is built for exactly this kind of embedding and happens to be dark-themed already, matching the tool's navy design with no extra styling hacks needed.
- The map now plots every neighborhood at its true latitude/longitude, draws real OpenStreetMap-sourced neighbourhood boundary polygons (already fetched by the existing boundary sync job, previously wasted on a non-geographic projection) in their true geographic shape, and supports pan/zoom.
- Scroll-wheel zoom is off by default (enables on click) so the map doesn't hijack page scrolling as a visitor scrolls past it.
- Defensive fallback: if Leaflet or the CARTO tiles fail to load (ad-blocker, restrictive CSP, offline admin preview, network issue), `initOverviewMap()` catches the failure and automatically renders the previous relative-position diagram instead — the same defensive pattern already used for Chart.js (`renderRadarChart`) elsewhere in this file, so nothing ever looks broken.
- Added Leaflet's own attribution requirement alongside the existing OpenStreetMap credit already in the footer.

## Files changed

- `includes/class-wtsm-shortcode.php` — enqueue Leaflet CSS/JS.
- `assets/js/matcher.js` — `buildOverviewMap` now returns a placeholder; new `initOverviewMap`, `renderOverviewMapLeaflet`, `renderOverviewMapFallback`; Leaflet map instance is torn down (`.remove()`) before each new result render to avoid leaking a window resize listener across repeated searches.
- `assets/css/matcher.css` — real fixed-height map container, Leaflet dark-theme control/tooltip overrides; the previous grid-background/compass decoration now only applies in the `.is-fallback` state.
- `templates/quiz-container.php` — footer credits CARTO alongside OpenStreetMap.

## Verification

- `php -l` clean on all PHP files, `node --check` clean on `matcher.js`, CSS brace balance verified.
- Manually traced the bounds/projection math for both the new Leaflet path (single-point `setView` vs multi-point `fitBounds`) and the still-used fallback path (duplicate-coordinate spiral offset) with a standalone Node harness — no exceptions, sane output.
- No live WordPress install was available in this environment to visually confirm the rendered tiles; the CDN URLs, licensing terms, and Leaflet API usage were verified via web search against current (2026) OpenStreetMap and CARTO policy pages before implementation.
