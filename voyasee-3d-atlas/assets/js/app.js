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
 *  the map's own D3 geographic projection, which handles rendering the
 *  resulting points/lines separately via d3.geoPath). */
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
 *  Splits at the antimeridian if the route crosses it, since d3.geoPath
 *  draws a LineString as literal straight segments between consecutive
 *  coordinates -- a route that crosses +-180 degrees longitude needs two
 *  separate line pieces to avoid a spurious line drawn all the way across
 *  the map. */
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

const DEFAULT_FEATURED_ARC_PAIRS = [
  ["new-york-city", "london"],
  ["paris", "tokyo"],
  ["dubai", "sydney"],
  ["cape-town", "rio-de-janeiro"],
  ["singapore", "los-angeles"],
];

// Logical SVG coordinate space (a 16:10 canvas, matched by the CSS
// aspect-ratio on the map container) -- D3's projection is fit to this
// fixed box once, and the SVG's viewBox scales it to whatever size the
// container actually renders at, so none of the geometry math below needs
// to know or care about real pixel dimensions or window resizes.
const MAP_WIDTH = 960;
const MAP_HEIGHT = 600;
const MAP_MIN_SCALE = 1;
const MAP_MAX_SCALE = 10;

/**
 * A self-contained SVG world map: real country boundary shapes (bundled
 * with the plugin, no external tile/map server involved at all), rendered
 * via D3's geographic projection, with Supercluster grouping destination
 * markers at low zoom. Every marker is a real SVG element handling its
 * own native click/hover -- there is no custom hit-testing math here to
 * get subtly wrong, which is the whole reason this replaced the earlier
 * COBE 3D globe (manual sphere-projection hit-testing) and, before that,
 * an external-tile-server map this project's own environment couldn't
 * verify was rendering correctly. Needs d3, Supercluster, and topojson
 * (each vendored as a classic global) plus the bundled world country
 * topology; if any of those didn't load for some reason, this fails
 * gracefully to a text message rather than a broken half-rendered map.
 */
function initMap(root, markers, config, openSidebar) {
  const mount = root.querySelector("[data-v3datlas-map-mount]");
  if (!mount || !markers.length) return { flyToMarker: function () {} };

  const strings = config.strings || {};
  if (!window.d3 || !window.Supercluster || !window.topojson || !config.worldDataUrl) {
    mount.innerHTML = "";
    mount.appendChild(el("p", "v3datlas-map-unavailable", strings.mapUnavailable || ""));
    return { flyToMarker: function () {} };
  }

  const reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  const byslug = {};
  markers.forEach(function (m) { byslug[m.slug] = m; });

  mount.innerHTML = "";
  const svg = d3.select(mount)
    .append("svg")
    .attr("class", "v3datlas-map-svg")
    .attr("viewBox", "0 0 " + MAP_WIDTH + " " + MAP_HEIGHT)
    .attr("role", "img")
    .attr("aria-label", strings.mapAriaLabel || "");

  const worldGroup = svg.append("g").attr("class", "v3datlas-map-world");
  const countriesLayer = worldGroup.append("g").attr("class", "v3datlas-map-countries");
  const routesLayer = worldGroup.append("g").attr("class", "v3datlas-map-routes");
  const markersLayer = svg.append("g").attr("class", "v3datlas-map-markers");

  const projection = d3.geoEqualEarth();
  const path = d3.geoPath(projection);

  const zoomControls = document.createElement("div");
  zoomControls.className = "v3datlas-map-zoom-controls";
  zoomControls.innerHTML =
    '<button type="button" class="v3datlas-map-zoom-btn" data-zoom-in aria-label="Zoom in">+</button>' +
    '<button type="button" class="v3datlas-map-zoom-btn" data-zoom-out aria-label="Zoom out">−</button>';
  mount.appendChild(zoomControls);

  const attribution = el("p", "v3datlas-map-attribution", "Map data © Natural Earth");
  mount.appendChild(attribution);

  const index = new Supercluster({ radius: 50, maxZoom: 9 });
  index.load(markers.map(function (m) {
    return {
      type: "Feature",
      properties: { slug: m.slug, name: m.name, weight: m.weight || 0 },
      geometry: { type: "Point", coordinates: [m.lng, m.lat] },
    };
  }));

  const zoom = d3.zoom()
    .scaleExtent([MAP_MIN_SCALE, MAP_MAX_SCALE])
    .on("start", function (event) {
      if (event.sourceEvent) stopAutoPan();
    })
    .on("zoom", function (event) {
      currentTransform = event.transform;
      worldGroup.attr("transform", currentTransform);
      renderMarkers();
    })
    .on("end", function (event) {
      if (event.sourceEvent) scheduleAutoPanResume();
    });
  svg.call(zoom);

  let currentTransform = d3.zoomIdentity;

  /** Ambient "world keeps turning" drift: once a user zooms in on a
   *  destination the whole world no longer fits the viewport, so -- like
   *  the old rotating globe -- the map gently keeps panning on its own
   *  once idle, cycling the hidden far side back into view, and pauses
   *  the instant a real user gesture (drag/wheel/pinch) starts. At the
   *  default fully-zoomed-out view the whole world already fits the
   *  frame (nothing is hidden), so the drift naturally has no distance
   *  to travel and stays still until the user zooms in. */
  let autoPanFrame = null;
  let autoPanActive = false;
  let autoPanResumeTimer = null;
  let autoPanLastTime = null;
  let autoPanDirection = 1;
  const AUTO_PAN_UNITS_PER_SEC = 16;
  const AUTO_PAN_RESUME_DELAY = 2500;

  function stopAutoPan() {
    autoPanActive = false;
    autoPanLastTime = null;
    if (autoPanResumeTimer) {
      clearTimeout(autoPanResumeTimer);
      autoPanResumeTimer = null;
    }
    if (autoPanFrame) {
      cancelAnimationFrame(autoPanFrame);
      autoPanFrame = null;
    }
  }

  function scheduleAutoPanResume() {
    if (reduceMotion) return;
    if (autoPanResumeTimer) clearTimeout(autoPanResumeTimer);
    autoPanResumeTimer = setTimeout(startAutoPan, AUTO_PAN_RESUME_DELAY);
  }

  function startAutoPan() {
    if (reduceMotion || autoPanActive) return;
    autoPanActive = true;
    autoPanLastTime = null;
    autoPanFrame = requestAnimationFrame(stepAutoPan);
  }

  function stepAutoPan(now) {
    if (!autoPanActive) return;
    if (autoPanLastTime === null) autoPanLastTime = now;
    const dt = (now - autoPanLastTime) / 1000;
    autoPanLastTime = now;

    const k = currentTransform.k;
    const minX = MAP_WIDTH * (1 - k);
    const maxX = 0;

    if (minX < maxX) {
      let x = currentTransform.x + autoPanDirection * AUTO_PAN_UNITS_PER_SEC * dt;
      if (x <= minX) {
        x = minX;
        autoPanDirection = 1;
      } else if (x >= maxX) {
        x = maxX;
        autoPanDirection = -1;
      }
      zoom.transform(svg, d3.zoomIdentity.translate(x, currentTransform.y).scale(k));
    }

    autoPanFrame = requestAnimationFrame(stepAutoPan);
  }

  /** Approximate Supercluster "zoom level" for the current D3 scale
   *  factor -- Supercluster's clustering radius is calibrated in web-
   *  mercator-style zoom levels (roughly a doubling of visual scale per
   *  level), which is a close enough match to D3's linear scale factor
   *  for the purpose of deciding how aggressively to group markers. */
  function superclusterZoom() {
    return Math.max(0, Math.min(9, Math.round(Math.log2(currentTransform.k) + 2)));
  }

  function renderMarkers() {
    const clusters = index.getClusters([-180, -85, 180, 85], superclusterZoom());

    const sel = markersLayer.selectAll("g.v3datlas-marker")
      .data(clusters, function (d) { return d.properties.cluster ? "cluster-" + d.id : d.properties.slug; });

    sel.exit().remove();

    const entered = sel.enter().append("g").attr("class", "v3datlas-marker");
    entered.each(function (d) {
      const g = d3.select(this);
      if (d.properties.cluster) {
        g.attr("class", "v3datlas-marker v3datlas-marker-cluster");
        g.append("circle").attr("class", "v3datlas-cluster-glow");
        g.append("circle").attr("class", "v3datlas-cluster-dot");
        g.append("text").attr("class", "v3datlas-cluster-label").attr("text-anchor", "middle").attr("dy", "0.32em");
      } else {
        g.attr("class", "v3datlas-marker v3datlas-marker-point");
        g.append("circle").attr("class", "v3datlas-point-glow");
        g.append("circle").attr("class", "v3datlas-point-dot");
        g.append("title");
      }
      g.style("cursor", "pointer");
      g.on("click", function (event, dd) { handleMarkerClick(dd); });
    });

    const merged = entered.merge(sel);
    merged.each(function (d) {
      const [x, y] = currentTransform.apply(projection(d.geometry.coordinates));
      const g = d3.select(this);
      g.attr("transform", "translate(" + x + "," + y + ")");
      if (d.properties.cluster) {
        const count = d.properties.point_count;
        const r = count < 10 ? 13 : count < 30 ? 17 : 21;
        g.select(".v3datlas-cluster-glow").attr("r", r + 8);
        g.select(".v3datlas-cluster-dot").attr("r", r);
        g.select(".v3datlas-cluster-label").text(d.properties.point_count_abbreviated);
      } else {
        const r = 4.5 + 2.5 * (d.properties.weight || 0);
        g.select(".v3datlas-point-glow").attr("r", r + 6);
        g.select(".v3datlas-point-dot").attr("r", r);
        g.select("title").text(d.properties.name);
      }
    });
  }

  function handleMarkerClick(d) {
    if (d.properties.cluster) {
      const expansionZoom = Math.min(9, index.getClusterExpansionZoom(d.id));
      const targetScale = Math.min(MAP_MAX_SCALE, Math.pow(2, expansionZoom - 2));
      zoomToPoint(d.geometry.coordinates, targetScale);
    } else {
      const marker = byslug[d.properties.slug];
      if (marker) openSidebar(marker);
    }
  }

  function zoomToPoint(lngLat, targetScale) {
    stopAutoPan();
    const [x0, y0] = projection(lngLat);
    const t = d3.zoomIdentity
      .translate(MAP_WIDTH / 2, MAP_HEIGHT / 2)
      .scale(targetScale)
      .translate(-x0, -y0);
    if (reduceMotion) {
      svg.call(zoom.transform, t);
    } else {
      svg.transition().duration(900).call(zoom.transform, t).on("end", scheduleAutoPanResume);
    }
  }

  function flyToMarker(marker) {
    if (!marker) return;
    const targetScale = Math.max(currentTransform.k, 4);
    zoomToPoint([marker.lng, marker.lat], targetScale);
  }

  zoomControls.querySelector("[data-zoom-in]").addEventListener("click", function () {
    stopAutoPan();
    if (reduceMotion) {
      svg.call(zoom.scaleBy, 1.6);
      scheduleAutoPanResume();
    } else {
      svg.transition().duration(300).call(zoom.scaleBy, 1.6).on("end", scheduleAutoPanResume);
    }
  });
  zoomControls.querySelector("[data-zoom-out]").addEventListener("click", function () {
    stopAutoPan();
    if (reduceMotion) {
      svg.call(zoom.scaleBy, 1 / 1.6);
      scheduleAutoPanResume();
    } else {
      svg.transition().duration(300).call(zoom.scaleBy, 1 / 1.6).on("end", scheduleAutoPanResume);
    }
  });

  function addFeaturedRoutes() {
    const arcPairs = Array.isArray(config.arcs) && config.arcs.length ? config.arcs : DEFAULT_FEATURED_ARC_PAIRS;
    const featuredArcs = arcPairs
      .map(function (pair) {
        const from = byslug[pair[0]];
        const to = byslug[pair[1]];
        return from && to ? { from: from, to: to } : null;
      })
      .filter(Boolean);
    if (!featuredArcs.length) return;

    const lines = [];
    featuredArcs.forEach(function (arc) {
      greatCircleLine(arc.from, arc.to, 64).forEach(function (coords) {
        lines.push({ type: "Feature", geometry: { type: "LineString", coordinates: coords }, properties: {} });
      });
    });

    const routeSel = routesLayer.selectAll("path")
      .data(lines)
      .enter()
      .append("path")
      .attr("class", "v3datlas-route")
      .attr("d", path)
      .style("opacity", reduceMotion ? 0.45 : 0);

    if (!reduceMotion) {
      routeSel.transition().duration(1200).style("opacity", 0.45);
    }
  }

  fetch(config.worldDataUrl)
    .then(function (r) { return r.json(); })
    .then(function (topo) {
      const objectName = Object.keys(topo.objects)[0];
      const world = topojson.feature(topo, topo.objects[objectName]);
      projection.fitSize([MAP_WIDTH, MAP_HEIGHT], world);

      countriesLayer.selectAll("path")
        .data(world.features)
        .enter()
        .append("path")
        .attr("class", "v3datlas-country")
        .attr("d", path);

      addFeaturedRoutes();
      renderMarkers();
      scheduleAutoPanResume();
    })
    .catch(function () {
      // The bundled world-shape data is served from this same site, not a
      // third-party host, so a failure here almost always means a caching/
      // hosting hiccup rather than an external outage -- still fails
      // gracefully rather than leaving a half-built map on screen.
      mount.innerHTML = "";
      mount.appendChild(el("p", "v3datlas-map-unavailable", strings.mapUnavailable || ""));
    });

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
