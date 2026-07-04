function supportsWebGL() {
  try {
    const canvas = document.createElement("canvas");
    return !!(window.WebGLRenderingContext && (canvas.getContext("webgl") || canvas.getContext("webgl2")));
  } catch (err) {
    return false;
  }
}

function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (undefined !== text && null !== text) node.textContent = text;
  return node;
}

/**
 * Owns the destination detail sidebar: fetching, rendering, open/close.
 * Deliberately independent of the map/WebGL code below -- clicking a
 * destination (from the map, the A-Z list, or the search box) must show
 * that destination's own weather/country/fact data even on a device or
 * browser where the WebGL map itself can't render. This intentionally
 * never links out to blog posts or a site-search results page: every
 * destination's own particular data (weather, country notes, "did you
 * know") is shown directly in this sidebar, with no dependency on whether
 * any article has been written about that place yet.
 *
 * @param {(marker: object) => void} [onOpen] Optional callback fired with
 *   the marker every time the sidebar opens, regardless of what triggered
 *   it (map marker click, A-Z list, search box, or a "you might also
 *   like" chip) -- used by the map to fly the camera to that destination
 *   even when the sidebar was opened from outside the map itself.
 */
function initSidebar(root, markers, strings, restBase, onOpen) {
  const byslug = {};
  markers.forEach(function (m) { byslug[m.slug] = m; });

  const sidebar = root.querySelector("[data-v3datlas-sidebar]");
  const sidebarBody = root.querySelector("[data-v3datlas-sidebar-body]");
  const sidebarClose = root.querySelector("[data-v3datlas-sidebar-close]");
  if (!sidebar || !sidebarBody) {
    return { openSidebar: function () {}, byslug: byslug };
  }

  const reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);

  const ro = new ResizeObserver(function (entries) {
    for (const entry of entries) {
      root.toggleAttribute("data-v3datlas-narrow", entry.contentRect.width < 400);
    }
  });
  ro.observe(root);

  sidebarClose && sidebarClose.addEventListener("click", closeSidebar);
  root.addEventListener("keydown", function (e) {
    if ("Escape" === e.key) closeSidebar();
  });

  function openSidebar(marker) {
    if (!marker) return;
    if (onOpen) onOpen(marker);
    sidebar.hidden = false;
    requestAnimationFrame(function () { sidebar.classList.add("is-open"); });
    sidebarBody.innerHTML = "";
    sidebarBody.appendChild(el("p", "v3datlas-sidebar-loading", strings.loading || "Loading…"));

    fetch(restBase + "destinations/" + encodeURIComponent(marker.slug), { headers: { Accept: "application/json" } })
      .then(function (r) {
        return r.json().then(function (data) { return { ok: r.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok || !result.data || false === result.data.ok) throw new Error("load failed");
        renderSidebar(result.data);
      })
      .catch(function () {
        sidebarBody.innerHTML = "";
        sidebarBody.appendChild(el("p", "v3datlas-sidebar-error", strings.loadError || "This destination could not be loaded."));
      });
  }

  function closeSidebar() {
    sidebar.classList.remove("is-open");
    setTimeout(function () { sidebar.hidden = true; }, reduceMotion ? 0 : 300);
  }

  function renderSidebar(data) {
    const dest = data.destination || {};
    sidebarBody.innerHTML = "";

    if (dest.hero_image_url) {
      const img = document.createElement("img");
      img.className = "v3datlas-sidebar-hero";
      img.src = dest.hero_image_url;
      img.alt = "";
      sidebarBody.appendChild(img);
    }

    sidebarBody.appendChild(el("h3", "v3datlas-sidebar-name", dest.name || ""));
    sidebarBody.appendChild(el("p", "v3datlas-sidebar-country", dest.country || ""));

    if (dest.signature_line) {
      sidebarBody.appendChild(el("p", "v3datlas-sidebar-signature", dest.signature_line));
    }

    sidebarBody.appendChild(renderWeather(data.weather, data.bestTime));
    sidebarBody.appendChild(renderCountry(data.country, data.upcomingHoliday));

    if (dest.did_you_know) {
      const fact = el("div", "v3datlas-sidebar-section v3datlas-fact");
      fact.appendChild(el("h4", null, "Did you know?"));
      fact.appendChild(el("p", null, dest.did_you_know));
      sidebarBody.appendChild(fact);
    }

    sidebarBody.appendChild(renderRelatedDestinations(data.nearby, data.sameCountry));
  }

  function renderWeather(weather, bestTime) {
    const section = el("div", "v3datlas-sidebar-section v3datlas-weather");
    section.appendChild(el("h4", null, "Weather"));
    if (!weather) {
      section.appendChild(el("p", "v3datlas-muted", strings.weatherUnavailable || "Weather data is temporarily unavailable."));
    } else if ("current" === weather.type) {
      const row = el("p", "v3datlas-weather-now");
      if (null !== weather.tempC && undefined !== weather.tempC) {
        row.appendChild(el("strong", null, Math.round(weather.tempC) + "°C"));
      }
      if (weather.conditionText) row.appendChild(document.createTextNode(" " + weather.conditionText));
      section.appendChild(row);
    } else if ("climate_normals" === weather.type) {
      const row = el("p", "v3datlas-weather-normals");
      const label = weather.month ? weather.month + " avg: " : "Typical this month: ";
      row.appendChild(document.createTextNode(label));
      if (null !== weather.tempMeanC && undefined !== weather.tempMeanC) {
        row.appendChild(el("strong", null, Math.round(weather.tempMeanC) + "°C"));
      }
      section.appendChild(row);
    }
    if (bestTime && bestTime.months && bestTime.months.length) {
      const row = el("p", "v3datlas-best-time");
      row.appendChild(document.createTextNode("Best time to visit: "));
      row.appendChild(el("strong", null, bestTime.months.join(" & ")));
      if (bestTime.highlight) row.appendChild(document.createTextNode(" (" + bestTime.highlight + ")"));
      section.appendChild(row);
    }
    return section;
  }

  function renderCountry(country, upcomingHoliday) {
    const section = el("div", "v3datlas-sidebar-section v3datlas-country");
    section.appendChild(el("h4", null, "Country notes"));
    if (!country) {
      section.appendChild(el("p", "v3datlas-muted", strings.countryUnavailable || "Country details are temporarily unavailable."));
      return section;
    }
    const list = el("ul", "v3datlas-country-facts");
    if (country.currencyName) {
      list.appendChild(el("li", null, "Currency: " + country.currencyName + (country.currencyCode ? " (" + country.currencyCode + ")" : "")));
    }
    if (country.drivingSide) list.appendChild(el("li", null, "Drives on the " + country.drivingSide));
    if (country.electricalPlugTypes && country.electricalPlugTypes.length) {
      list.appendChild(el("li", null, "Power plugs: Type " + country.electricalPlugTypes.join(", ")));
    }
    if (country.emergencyPolice) list.appendChild(el("li", null, "Police: " + country.emergencyPolice));
    if (country.tippingGuidance) list.appendChild(el("li", null, country.tippingGuidance));
    if (upcomingHoliday && upcomingHoliday.name) {
      list.appendChild(el("li", null, upcomingHoliday.name + " is coming up (" + upcomingHoliday.date + ")"));
    }
    section.appendChild(list);
    return section;
  }

  function renderRelatedDestinations(nearby, sameCountry) {
    const section = el("div", "v3datlas-sidebar-section v3datlas-related");
    const combined = [];
    const seen = {};
    (nearby || []).forEach(function (item) {
      if (seen[item.slug]) return;
      seen[item.slug] = true;
      combined.push(item);
    });
    (sameCountry || []).forEach(function (item) {
      if (seen[item.slug]) return;
      seen[item.slug] = true;
      combined.push(item);
    });
    if (!combined.length) return el("div");

    section.appendChild(el("h4", null, "You might also like"));
    const list = el("div", "v3datlas-related-chips");
    combined.slice(0, 5).forEach(function (item) {
      const chip = document.createElement("button");
      chip.type = "button";
      chip.className = "v3datlas-related-chip";
      chip.textContent = item.name;
      chip.addEventListener("click", function () {
        const marker = byslug[item.slug];
        if (marker) openSidebar(marker);
      });
      list.appendChild(chip);
    });
    section.appendChild(list);
    return section;
  }

  return { openSidebar: openSidebar, byslug: byslug };
}

/**
 * Wires the server-rendered A-Z destination list (and, by extension, its
 * search-filtered subset) so clicking any destination name opens the same
 * sidebar a map marker click would, instead of navigating to a category
 * archive or a site-search results page. This is the one click handler for
 * every non-map entry point into a destination's data.
 */
function initDestinationList(root, openSidebar, byslug) {
  const list = root.querySelector("[data-v3datlas-destination-list]");
  if (!list) return;
  list.addEventListener("click", function (e) {
    const btn = e.target.closest("[data-v3datlas-open-slug]");
    if (!btn) return;
    e.preventDefault();
    const marker = byslug[btn.getAttribute("data-v3datlas-open-slug")];
    if (marker) openSidebar(marker);
  });
}

/**
 * Type-to-filter search over the server-rendered A-Z destination list.
 * Deliberately independent of the map/WebGL init below -- this list is
 * plain server-rendered HTML and must keep working (including for
 * accessibility/no-WebGL visitors) whether or not the map boots at all.
 */
function initListSearch(root) {
  const input = root.querySelector("[data-v3datlas-list-search]");
  if (!input) return;
  const regions = root.querySelectorAll("[data-v3datlas-region]");

  input.addEventListener("input", function () {
    const q = input.value.trim().toLowerCase();
    regions.forEach(function (region) {
      let anyVisible = false;
      region.querySelectorAll("[data-v3datlas-region-item]").forEach(function (item) {
        const match = !q || (item.getAttribute("data-name") || "").indexOf(q) !== -1;
        item.toggleAttribute("data-v3datlas-hidden", !match);
        if (match) anyVisible = true;
      });
      region.toggleAttribute("data-v3datlas-hidden", !anyVisible);
      if (q) region.open = anyVisible;
    });
  });
}

/** Standard geographic-to-unit-vector conversion, used only for great-
 *  circle interpolation of the featured-route lines below (independent of
 *  the map's own flat Web Mercator projection, which MapLibre owns). */
function latLngToVec3(lat, lng) {
  const latRad = (lat * Math.PI) / 180;
  const lngRad = (lng * Math.PI) / 180;
  const cosLat = Math.cos(latRad);
  return [cosLat * Math.cos(lngRad), cosLat * Math.sin(lngRad), Math.sin(latRad)];
}

function vec3ToLatLng(v) {
  return [(Math.asin(v[2]) * 180) / Math.PI, (Math.atan2(v[1], v[0]) * 180) / Math.PI];
}

/** Spherical linear interpolation between two points on a great circle --
 *  this is what makes a route between, say, Tokyo and Paris draw as a
 *  real curved flight path over the pole/high latitudes on the flat map,
 *  rather than a straight line cutting through the earth. */
function slerp(a, b, t) {
  let dot = a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
  dot = Math.max(-1, Math.min(1, dot));
  const omega = Math.acos(dot);
  if (omega < 1e-6) return a;
  const sinOmega = Math.sin(omega);
  const s0 = Math.sin((1 - t) * omega) / sinOmega;
  const s1 = Math.sin(t * omega) / sinOmega;
  return [a[0] * s0 + b[0] * s1, a[1] * s0 + b[1] * s1, a[2] * s0 + b[2] * s1];
}

/** Builds a great-circle route as a GeoJSON LineString of [lng,lat] pairs.
 *  Splits at the antimeridian if the route crosses it, since MapLibre (like
 *  any Mercator-based renderer) draws a LineString as literal straight
 *  segments between consecutive coordinates -- a route that crosses +-180
 *  degrees longitude needs two separate line pieces to avoid a spurious
 *  line drawn all the way across the map. */
function greatCircleLine(from, to, steps) {
  const a = latLngToVec3(from.lat, from.lng);
  const b = latLngToVec3(to.lat, to.lng);
  const points = [];
  for (let i = 0; i <= steps; i++) {
    const [lat, lng] = vec3ToLatLng(slerp(a, b, i / steps));
    points.push([lng, lat]);
  }
  const lines = [[]];
  for (let i = 0; i < points.length; i++) {
    const cur = points[i];
    const prev = points[i - 1];
    if (prev && Math.abs(cur[0] - prev[0]) > 180) {
      lines.push([]);
    }
    lines[lines.length - 1].push(cur);
  }
  return lines.filter(function (line) { return line.length > 1; });
}

/** Free, no-API-key vector tile styles (https://openfreemap.org) -- tries
 *  the dark style first for a look matching this Atlas's existing emerald/
 *  gold theme, and falls back to their flagship default style if that
 *  particular style name doesn't exist or fails to load, so the map still
 *  renders correctly either way rather than showing nothing. */
const STYLE_CANDIDATES = [
  "https://tiles.openfreemap.org/styles/dark",
  "https://tiles.openfreemap.org/styles/liberty",
];

const DEFAULT_FEATURED_ARC_PAIRS = [
  ["new-york-city", "london"],
  ["paris", "tokyo"],
  ["dubai", "sydney"],
  ["cape-town", "rio-de-janeiro"],
  ["singapore", "los-angeles"],
];

function initMap(root, markers, config, openSidebar) {
  const mount = root.querySelector("[data-v3datlas-map-mount]");
  if (!mount || !markers.length) return { flyToMarker: function () {} };

  const strings = config.strings || {};
  if (!supportsWebGL() || !window.maplibregl) {
    mount.innerHTML = "";
    const msg = el("p", "v3datlas-map-unavailable", strings.mapUnavailable || "");
    mount.appendChild(msg);
    return { flyToMarker: function () {} };
  }

  const reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  const byslug = {};
  markers.forEach(function (m) { byslug[m.slug] = m; });

  const container = document.createElement("div");
  container.className = "v3datlas-map-canvas";
  mount.innerHTML = "";
  mount.appendChild(container);

  const map = new maplibregl.Map({
    container: container,
    style: STYLE_CANDIDATES[0],
    center: [10, 25],
    zoom: 1.2,
    minZoom: 0.6,
    maxZoom: 12,
    attributionControl: { compact: true },
    dragRotate: false,
    pitchWithRotate: false,
    touchPitch: false,
  });

  // Only ever fall back to the second style candidate if the FIRST style
  // never finished loading at all. Once the map has successfully loaded
  // once, later 'error' events are usually benign, unrelated style-spec
  // warnings (e.g. a symbol layer's text-field needing a glyphs URL the
  // base style didn't define) -- reacting to those by calling setStyle()
  // would destructively tear down and replace the entire style, wiping
  // out every layer this code has already added, for no good reason.
  let styleLoaded = false;
  let styleFallbackTried = false;
  map.on("error", function (e) {
    if (styleLoaded || styleFallbackTried) return;
    const isStyleFailure = e && e.error && /style|tile|fetch/i.test(String(e.error.message || ""));
    if (isStyleFailure) {
      styleFallbackTried = true;
      map.setStyle(STYLE_CANDIDATES[1]);
    }
  });

  map.addControl(new maplibregl.NavigationControl({ showCompass: false }), "top-right");

  function markersToGeoJSON() {
    return {
      type: "FeatureCollection",
      features: markers.map(function (m) {
        return {
          type: "Feature",
          geometry: { type: "Point", coordinates: [m.lng, m.lat] },
          properties: { slug: m.slug, name: m.name, weight: m.weight || 0 },
        };
      }),
    };
  }

  function addMarkerLayers() {
    map.addSource("v3da-destinations", {
      type: "geojson",
      data: markersToGeoJSON(),
      cluster: true,
      clusterMaxZoom: 6,
      clusterRadius: 46,
    });

    map.addLayer({
      id: "v3da-cluster-glow",
      type: "circle",
      source: "v3da-destinations",
      filter: ["has", "point_count"],
      paint: {
        "circle-radius": ["step", ["get", "point_count"], 22, 10, 28, 30, 36],
        "circle-color": "#c9a24b",
        "circle-opacity": 0.18,
        "circle-blur": 1,
      },
    });

    map.addLayer({
      id: "v3da-clusters",
      type: "circle",
      source: "v3da-destinations",
      filter: ["has", "point_count"],
      paint: {
        "circle-radius": ["step", ["get", "point_count"], 14, 10, 18, 30, 22],
        "circle-color": "#0f3d2e",
        "circle-stroke-width": 2,
        "circle-stroke-color": "#e8cf86",
      },
    });

    map.addLayer({
      id: "v3da-cluster-count",
      type: "symbol",
      source: "v3da-destinations",
      filter: ["has", "point_count"],
      layout: {
        "text-field": "{point_count_abbreviated}",
        "text-font": ["Noto Sans Bold"],
        "text-size": 12,
        "text-allow-overlap": true,
      },
      paint: { "text-color": "#f6f1e6" },
    });

    map.addLayer({
      id: "v3da-point-glow",
      type: "circle",
      source: "v3da-destinations",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-radius": ["interpolate", ["linear"], ["get", "weight"], 0, 9, 1, 14],
        "circle-color": "#c9a24b",
        "circle-opacity": 0.22,
        "circle-blur": 1,
      },
    });

    map.addLayer({
      id: "v3da-points",
      type: "circle",
      source: "v3da-destinations",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-radius": ["interpolate", ["linear"], ["get", "weight"], 0, 4.5, 1, 7],
        "circle-color": "#e8cf86",
        "circle-stroke-width": 1.5,
        "circle-stroke-color": "#0a201a",
      },
    });

    ["v3da-clusters", "v3da-points"].forEach(function (layerId) {
      map.on("mouseenter", layerId, function () { map.getCanvas().style.cursor = "pointer"; });
      map.on("mouseleave", layerId, function () { map.getCanvas().style.cursor = ""; });
    });

    map.on("click", "v3da-clusters", function (e) {
      const feature = e.features && e.features[0];
      if (!feature) return;
      const clusterId = feature.properties.cluster_id;
      map.getSource("v3da-destinations").getClusterExpansionZoom(clusterId).then(function (zoom) {
        map.flyTo({ center: feature.geometry.coordinates, zoom: zoom, essential: true });
      }).catch(function () { /* noop -- worst case the cluster just doesn't expand on click */ });
    });

    map.on("click", "v3da-points", function (e) {
      const feature = e.features && e.features[0];
      if (!feature) return;
      const marker = byslug[feature.properties.slug];
      if (marker) openSidebar(marker);
    });
  }

  function addFeaturedArcs() {
    const arcPairs = Array.isArray(config.arcs) && config.arcs.length ? config.arcs : DEFAULT_FEATURED_ARC_PAIRS;
    const featuredArcs = arcPairs
      .map(function (pair) {
        const from = byslug[pair[0]];
        const to = byslug[pair[1]];
        return from && to ? { from: from, to: to } : null;
      })
      .filter(Boolean);
    if (!featuredArcs.length) return;

    const features = [];
    featuredArcs.forEach(function (arc) {
      greatCircleLine(arc.from, arc.to, 48).forEach(function (line) {
        features.push({ type: "Feature", geometry: { type: "LineString", coordinates: line }, properties: {} });
      });
    });

    map.addSource("v3da-routes", { type: "geojson", data: { type: "FeatureCollection", features: features } });
    map.addLayer(
      {
        id: "v3da-routes",
        type: "line",
        source: "v3da-routes",
        layout: { "line-cap": "round", "line-join": "round" },
        paint: { "line-color": "#e8cf86", "line-width": 1.5, "line-opacity": 0 },
      },
      "v3da-cluster-glow"
    );

    if (reduceMotion) {
      map.setPaintProperty("v3da-routes", "line-opacity", 0.45);
      return;
    }
    // Simple one-time fade-in rather than a per-frame animated "draw" --
    // deliberately less elaborate than possible, to keep this new map
    // layer robust on the first pass; a scripted flyTo tour can be added
    // back later the same way the previous globe's intro tour worked.
    let opacity = 0;
    const fade = setInterval(function () {
      if (!map.getLayer("v3da-routes")) {
        clearInterval(fade);
        return;
      }
      opacity = Math.min(0.45, opacity + 0.02);
      map.setPaintProperty("v3da-routes", "line-opacity", opacity);
      if (opacity >= 0.45) clearInterval(fade);
    }, 40);
  }

  map.on("load", function () {
    styleLoaded = true;
    addMarkerLayers();
    addFeaturedArcs();
  });

  function flyToMarker(marker) {
    if (!marker) return;
    map.flyTo({
      center: [marker.lng, marker.lat],
      zoom: Math.max(map.getZoom(), 5),
      essential: true,
      duration: reduceMotion ? 0 : 1400,
    });
  }

  return { flyToMarker: flyToMarker };
}

document.querySelectorAll("[data-v3datlas-root]").forEach(function (root) {
  if (root.hasAttribute("data-v3datlas-ready")) return;
  root.setAttribute("data-v3datlas-ready", "1");

  let config = {};
  try {
    config = JSON.parse(root.getAttribute("data-v3datlas-config") || "{}");
  } catch (err) {
    config = {};
  }
  const markers = Array.isArray(config.markers) ? config.markers : [];
  const strings = config.strings || {};
  const restBase = config.restBase || "";

  // The sidebar and the A-Z list click handler are wired up regardless of
  // WebGL support, so every destination -- whether clicked on the map, in
  // the search-filtered A-Z list, or via a "you might also like" chip --
  // shows the same in-page detail view. Only the map rendering itself
  // needs WebGL. mapApi is created after sidebarApi but referenced by it
  // (via the onOpen callback) since both need each other; the callback
  // indirection avoids a circular construction order.
  let mapApi = { flyToMarker: function () {} };
  const sidebarApi = initSidebar(root, markers, strings, restBase, function (marker) {
    mapApi.flyToMarker(marker);
  });
  initListSearch(root);
  initDestinationList(root, sidebarApi.openSidebar, sidebarApi.byslug);
  mapApi = initMap(root, markers, config, sidebarApi.openSidebar);
});
