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
    return { openSidebar: function () {}, openCountry: function () {}, byslug: byslug };
  }

  const reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);

  const ro = new ResizeObserver(function (entries) {
    for (const entry of entries) {
      root.toggleAttribute("data-v3datlas-narrow", entry.contentRect.width < 400);
    }
  });
  ro.observe(root);

  sidebarClose && sidebarClose.addEventListener("click", closeSidebar);
  // Listens on the document, not just root, because the sidebar is most
  // often opened by clicking an SVG marker or country shape on the map --
  // neither is focusable, so keyboard focus stays wherever it was before
  // the click (often nowhere inside root at all). Scoping this to root
  // would mean Escape silently did nothing for exactly the two most common
  // ways of opening the sidebar.
  document.addEventListener("keydown", function (e) {
    if ("Escape" === e.key && !sidebar.hidden) closeSidebar();
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

  /**
   * Opens the same sidebar panel for a bare country click (a country shape
   * with no destination pin under the cursor, or clicked away from any
   * pin) -- shows whatever country-level facts are available instead of a
   * single destination's page, so clicking "blank" parts of the globe
   * still surfaces something useful rather than doing nothing.
   */
  function openCountry(code, name) {
    sidebar.hidden = false;
    requestAnimationFrame(function () { sidebar.classList.add("is-open"); });
    sidebarBody.innerHTML = "";

    if (!code) {
      renderCountrySidebar(null, name);
      return;
    }

    sidebarBody.appendChild(el("p", "v3datlas-sidebar-loading", strings.loading || "Loading…"));
    fetch(restBase + "countries/" + encodeURIComponent(code), { headers: { Accept: "application/json" } })
      .then(function (r) {
        return r.json().then(function (data) { return { ok: r.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok || !result.data || false === result.data.ok) throw new Error("load failed");
        renderCountrySidebar(result.data, name);
      })
      .catch(function () {
        renderCountrySidebar(null, name);
      });
  }

  function renderCountrySidebar(data, name) {
    sidebarBody.innerHTML = "";
    sidebarBody.appendChild(el("h3", "v3datlas-sidebar-name", name || strings.countryFallbackName || "This country"));
    sidebarBody.appendChild(el("p", "v3datlas-sidebar-country", strings.countryOverviewLabel || "Country overview"));

    if (!data) {
      sidebarBody.appendChild(el("p", "v3datlas-sidebar-error", strings.countryNoData || "No information is available for this country yet."));
      return;
    }

    sidebarBody.appendChild(renderCountry(data.country, data.upcomingHoliday));

    if (data.destinations && data.destinations.length) {
      const section = el("div", "v3datlas-sidebar-section v3datlas-related");
      section.appendChild(el("h4", null, strings.destinationsInCountry || "Destinations we cover here"));
      const list = el("div", "v3datlas-related-chips");
      data.destinations.slice(0, 8).forEach(function (item) {
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
      sidebarBody.appendChild(section);
    }
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

  return { openSidebar: openSidebar, openCountry: openCountry, byslug: byslug };
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
// Zoom bounds, expressed as a multiple of the globe's own fitted radius
// (baseScale below) rather than an absolute pixel value, since that radius
// itself depends on the container size.
const MAP_MIN_SCALE = 1;
const MAP_MAX_SCALE = 8;

/**
 * A self-contained SVG rotating globe: real country boundary shapes
 * (bundled with the plugin, no external tile/map server involved at all)
 * rendered via D3's orthographic geographic projection, with Supercluster
 * grouping destination markers at low zoom. Every marker and every country
 * shape is a real SVG element handling its own native click/hover -- there
 * is no custom hit-testing math here to get subtly wrong. Rotation is
 * driven by dragging (mouse or touch) directly manipulating the
 * projection's own rotate() parameters, which is what makes this a true
 * 360-degree globe rather than a flat map: the far side of the world is
 * never permanently out of reach, only ever a drag (or, left idle, a
 * gentle continuous auto-rotation) away, mirroring how the original 3D
 * globe felt while keeping this version's fully-native-SVG click
 * reliability. Needs d3, Supercluster, and topojson (each vendored as a
 * classic global) plus the bundled world country topology; if any of
 * those didn't load for some reason, this fails gracefully to a text
 * message rather than a broken half-rendered map.
 */
function initMap(root, markers, config, openSidebar, openCountry) {
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

  const sphere = svg.append("path").attr("class", "v3datlas-globe-sphere");
  const graticuleLayer = svg.append("path").attr("class", "v3datlas-globe-graticule");
  const countriesLayer = svg.append("g").attr("class", "v3datlas-map-countries");
  const routesLayer = svg.append("g").attr("class", "v3datlas-map-routes");
  const markersLayer = svg.append("g").attr("class", "v3datlas-map-markers");

  // A slight initial tilt (rather than a dead-on equatorial view) shows a
  // bit of both hemispheres right away, closer to how a physical globe
  // usually sits than a flat head-on view would.
  const projection = d3.geoOrthographic().clipAngle(90).rotate([-10, -15, 0]);
  const path = d3.geoPath(projection);
  const graticule = d3.geoGraticule();
  projection.fitSize([MAP_WIDTH, MAP_HEIGHT], { type: "Sphere" });
  const baseScale = projection.scale();
  // Points beyond this angular distance from the view center are on the
  // globe's far side; a small pad keeps markers from rendering right at
  // the horizon edge, where the orthographic projection gets visually
  // distorted just before a point would disappear.
  const HORIZON_LIMIT = Math.PI / 2 - 0.02;

  function clampScale(s) {
    return Math.max(baseScale * MAP_MIN_SCALE, Math.min(baseScale * MAP_MAX_SCALE, s));
  }

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

  let countryPaths = null;
  let routePaths = null;

  function render() {
    sphere.attr("d", path({ type: "Sphere" }));
    graticuleLayer.attr("d", path(graticule()));
    if (countryPaths) countryPaths.attr("d", path);
    if (routePaths) routePaths.attr("d", path);
    renderMarkers();
  }

  /** Ambient "world keeps turning" auto-rotation: a continuous, unbounded
   *  spin around the polar axis whenever the globe is left idle -- unlike
   *  a flat map, every rotation is always hiding half the world, so this
   *  runs by default (not just once zoomed in), the same way the original
   *  3D globe rotated on its own. It pauses the instant a real drag/wheel
   *  gesture starts and resumes automatically a couple of seconds after
   *  the user lets go. */
  let autoPanFrame = null;
  let autoPanActive = false;
  let autoPanResumeTimer = null;
  let autoPanLastTime = null;
  const AUTO_ROTATE_DEG_PER_SEC = 4;
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

    const r = projection.rotate();
    let lambda = r[0] + AUTO_ROTATE_DEG_PER_SEC * dt;
    if (lambda > 360 || lambda < -360) lambda %= 360;
    projection.rotate([lambda, r[1], r[2]]);
    render();

    autoPanFrame = requestAnimationFrame(stepAutoPan);
  }

  // Dragging (mouse or a single finger) rotates the globe directly --
  // degrees-per-pixel is derived from the current scale so a drag always
  // feels like grabbing the globe's own surface, at any zoom level.
  // clickDistance() is what keeps this reliable: d3 suppresses the
  // resulting native "click" event on the dragged element whenever the
  // pointer moved more than that many pixels, so a drag-rotate can never
  // misfire as a marker/country click (the exact bug class that made the
  // original COBE globe's manual hit-testing unreliable).
  let dragRotateStart = null;
  let dragRotateFrom = null;
  const drag = d3.drag()
    .clickDistance(6)
    .on("start", function (event) {
      stopAutoPan();
      dragRotateFrom = projection.rotate();
      dragRotateStart = [event.x, event.y];
    })
    .on("drag", function (event) {
      const degPerPixel = 180 / (Math.PI * projection.scale());
      const dx = event.x - dragRotateStart[0];
      const dy = event.y - dragRotateStart[1];
      const lambda = dragRotateFrom[0] + dx * degPerPixel;
      const phi = Math.max(-90, Math.min(90, dragRotateFrom[1] - dy * degPerPixel));
      projection.rotate([lambda, phi, 0]);
      render();
    })
    .on("end", function () {
      scheduleAutoPanResume();
    });
  svg.call(drag);

  svg.on("wheel", function (event) {
    event.preventDefault();
    stopAutoPan();
    const factor = event.deltaY < 0 ? 1.08 : 1 / 1.08;
    projection.scale(clampScale(projection.scale() * factor));
    render();
    scheduleAutoPanResume();
  });

  function animateScale(targetScale, duration) {
    const clamped = clampScale(targetScale);
    if (reduceMotion || !duration) {
      projection.scale(clamped);
      render();
      scheduleAutoPanResume();
      return;
    }
    const interp = d3.interpolate(projection.scale(), clamped);
    d3.transition().duration(duration).tween("v3da-scale", function () {
      return function (t) {
        projection.scale(interp(t));
        render();
      };
    }).on("end", scheduleAutoPanResume);
  }

  function zoomBy(factor) {
    stopAutoPan();
    animateScale(projection.scale() * factor, reduceMotion ? 0 : 300);
  }

  zoomControls.querySelector("[data-zoom-in]").addEventListener("click", function () { zoomBy(1.6); });
  zoomControls.querySelector("[data-zoom-out]").addEventListener("click", function () { zoomBy(1 / 1.6); });

  /** Rotates the globe to center the given [lng, lat] point (taking the
   *  shorter way around the pole rather than however d3.interpolate's raw
   *  linear array interpolation would happen to go) while animating to a
   *  target scale, used by marker/cluster/list/search/country-click "fly
   *  to" moves alike. */
  function flyToPoint(lngLat, targetScale, duration) {
    stopAutoPan();
    const current = projection.rotate();
    let dLambda = -lngLat[0] - current[0];
    dLambda = ((dLambda + 180) % 360 + 360) % 360 - 180;
    const targetRotate = [current[0] + dLambda, -lngLat[1], 0];
    const clampedScale = clampScale(targetScale);

    if (reduceMotion || !duration) {
      projection.rotate(targetRotate).scale(clampedScale);
      render();
      scheduleAutoPanResume();
      return;
    }

    const rotateInterp = d3.interpolate(current, targetRotate);
    const scaleInterp = d3.interpolate(projection.scale(), clampedScale);
    d3.transition().duration(duration).tween("v3da-globe", function () {
      return function (t) {
        projection.rotate(rotateInterp(t)).scale(scaleInterp(t));
        render();
      };
    }).on("end", scheduleAutoPanResume);
  }

  /** Approximate Supercluster "zoom level" for the current globe scale --
   *  Supercluster's clustering radius is calibrated in web-mercator-style
   *  zoom levels (roughly a doubling of visual scale per level), which is
   *  a close enough match here for the purpose of deciding how
   *  aggressively to group markers. */
  function superclusterZoom() {
    const ratio = projection.scale() / baseScale;
    return Math.max(0, Math.min(9, Math.round(Math.log2(Math.max(ratio, 1e-6)) + 2)));
  }

  function renderMarkers() {
    const clusters = index.getClusters([-180, -85, 180, 85], superclusterZoom());
    const rotate = projection.rotate();
    const center = [-rotate[0], -rotate[1]];
    // Only the front hemisphere is ever drawn -- a marker on the far side
    // of the globe is exactly as reachable as any other, just a rotation
    // away, rather than rendered (wrongly) on top of the visible side.
    const visible = clusters.filter(function (d) {
      return d3.geoDistance(d.geometry.coordinates, center) < HORIZON_LIMIT;
    });

    const sel = markersLayer.selectAll("g.v3datlas-marker")
      .data(visible, function (d) { return d.properties.cluster ? "cluster-" + d.id : d.properties.slug; });

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
      const [x, y] = projection(d.geometry.coordinates);
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
      const targetScale = baseScale * Math.pow(2, expansionZoom - 2);
      flyToPoint(d.geometry.coordinates, targetScale, 900);
    } else {
      const marker = byslug[d.properties.slug];
      if (marker) openSidebar(marker);
    }
  }

  function flyToMarker(marker) {
    if (!marker) return;
    const targetScale = Math.max(projection.scale(), baseScale * 4);
    flyToPoint([marker.lng, marker.lat], targetScale, 900);
  }

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

    routePaths = routesLayer.selectAll("path")
      .data(lines)
      .enter()
      .append("path")
      .attr("class", "v3datlas-route")
      .attr("d", path)
      .style("opacity", reduceMotion ? 0.45 : 0);

    if (!reduceMotion) {
      routePaths.transition().duration(1200).style("opacity", 0.45);
    }
  }

  fetch(config.worldDataUrl)
    .then(function (r) { return r.json(); })
    .then(function (topo) {
      const objectName = Object.keys(topo.objects)[0];
      const world = topojson.feature(topo, topo.objects[objectName]);
      const numericToAlpha2 = window.V3DA_ISO_NUMERIC_ALPHA2 || {};

      countryPaths = countriesLayer.selectAll("path")
        .data(world.features)
        .enter()
        .append("path")
        .attr("class", "v3datlas-country")
        .style("cursor", "pointer")
        .on("click", function (event, d) {
          const code = numericToAlpha2[String(d.id)] || null;
          const name = (d.properties && d.properties.name) || "";
          if (openCountry) openCountry(code, name);
          const centroid = d3.geoCentroid(d);
          if (centroid && isFinite(centroid[0]) && isFinite(centroid[1])) {
            flyToPoint(centroid, projection.scale(), 900);
          }
        });

      addFeaturedRoutes();
      render();
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
  mapApi = initMap(root, markers, config, sidebarApi.openSidebar, sidebarApi.openCountry);
});
