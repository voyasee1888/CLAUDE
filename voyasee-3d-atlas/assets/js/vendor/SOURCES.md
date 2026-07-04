# Vendored libraries

## d3.min.js

- Source: https://www.npmjs.com/package/d3
- Version: 7.9.0
- License: ISC (see D3-LICENSE.txt)
- Unmodified minified UMD build (`dist/d3.min.js`). Self-hosted per the
  build brief's hard constraint (no CDN runtime dependency). Attaches the
  `d3` global. Used for: geographic projection + path generation
  (`d3.geoEqualEarth`, `d3.geoPath`) and pan/zoom behavior (`d3.zoom`).

## supercluster.min.js

- Source: https://www.npmjs.com/package/supercluster
- Version: 8.0.1
- License: ISC (see SUPERCLUSTER-LICENSE.txt)
- Unmodified minified UMD build. Attaches the `Supercluster` constructor.
  Used to group nearby destination markers into numbered clusters at low
  zoom levels (recomputed as the map's zoom/pan transform changes).

## topojson-client.min.js

- Source: https://www.npmjs.com/package/topojson-client
- Version: 3.1.0
- License: ISC (see TOPOJSON-CLIENT-LICENSE.txt)
- Unmodified minified UMD build. Attaches the `topojson` global. Used
  once on load to expand ../data/world-countries-110m.topo.json (a much
  more compact encoding than plain GeoJSON) into real country boundary
  GeoJSON features for rendering.

## iso-country-codes.js

- Source: https://www.npmjs.com/package/i18n-iso-countries
- Version: 7.14.0 (data extracted at build time, package itself not vendored)
- License: MIT (see ISO-COUNTRY-CODES-LICENSE.txt)
- Not a vendored library -- a static data table (ISO 3166-1 numeric ->
  alpha-2 code) extracted from this package's `getNumericCodes()` via a
  one-off Node script and written out as a plain object literal attached
  to `window.V3DA_ISO_NUMERIC_ALPHA2`. Needed because the bundled world
  topology (../data/world-countries-110m.topo.json) identifies each
  country by its ISO numeric code, but Voyasee Country Intelligence (the
  optional bridge plugin queried for the "click a country" info panel,
  see class-v3da-rest.php) is keyed by ISO alpha-2 code, the same format
  already used for every destination's `country_code` column.

## Replaced approach (v2.0.0 -> v2.1.0)

v2.0.0 briefly used MapLibre GL JS with third-party-hosted vector tiles
(OpenFreeMap) for the base map. That tile host could not be reached from
this project's own development/verification environment, and the live
result did not render real country shapes acceptably. v2.1.0 replaces it
with a fully self-contained approach: real country boundary polygons
(bundled in ../data/, see its own SOURCES note) rendered directly as SVG
paths via D3's geographic projection, with no external map/tile server
involved at runtime at all -- removing that entire class of external-
dependency risk. Marker click/hover handling is native, real SVG DOM
event handling (not custom hit-testing), for the same reliability reason
the interactive-map direction was chosen over the earlier COBE 3D globe.

## Previously vendored (removed)

- cobe.esm.js (COBE 2.0.1) -- removed in v2.0.0, replaced by an
  interactive map per explicit user decision after marker-click
  reliability problems on the 3D globe (see readme.txt changelog).
- maplibre-gl.js / maplibre-gl.css (MapLibre GL JS 4.7.1) -- removed in
  v2.1.0, replaced by the self-contained D3/SVG approach above.
