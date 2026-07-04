# Vendored libraries

## maplibre-gl.js / maplibre-gl.css

- Source: https://www.npmjs.com/package/maplibre-gl
- Version: 4.7.1
- License: BSD-3-Clause (see MAPLIBRE-LICENSE.txt)
- Unmodified UMD build output (`dist/maplibre-gl.js`, `dist/maplibre-gl.css`) from the
  published npm package. Self-hosted per the build brief's hard constraint (no CDN
  runtime dependency). No official ESM build is published, so it's loaded as a
  classic script (attaches the `maplibregl` global) rather than a script module.
- Replaced the vendored COBE 3D globe (removed) as of v2.0.0: COBE's dot-matrix
  rendering had no reliable way to hit-test clicks at high marker density (167
  destinations, 40+ clustered in Europe alone) without a full rewrite of its
  internals. MapLibre GL JS gives native, battle-tested click/hover picking on
  real GeoJSON features plus built-in marker clustering, at the cost of switching
  from a 3D sphere look to a flat, pannable/zoomable vector map.
- Map tiles: uses OpenFreeMap (https://openfreemap.org)'s free, no-API-key-required
  hosted vector tiles/style (self-hostable later if ever needed) -- no paid API or
  signup involved, consistent with this plugin's zero-paid-API constraint.
