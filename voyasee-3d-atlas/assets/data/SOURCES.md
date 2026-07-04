# Bundled data

## world-countries-110m.topo.json

- Source: https://www.npmjs.com/package/world-atlas (`countries-110m.json`)
- Version: 2.0.2
- License: ISC (see WORLD-ATLAS-LICENSE.txt)
- Real country boundary shapes derived from Natural Earth's public domain
  1:110m cultural vector data, encoded as TopoJSON (much more compact than
  plain GeoJSON, since shared borders between neighboring countries are
  only stored once). Expanded into GeoJSON features client-side, once on
  load, by the vendored topojson-client library -- see
  ../js/vendor/SOURCES.md.
- 110m resolution (rather than the same package's 50m/10m variants) was
  chosen deliberately: this map's zoom range never goes past city-level
  destination markers, so a finer coastline resolution would add real
  page weight (the 50m variant is roughly 7x larger) without a visible
  benefit at the zoom levels this Atlas actually uses.
