import createGlobe from "v3da-cobe";

/**
 * Subsolar point (the lat/lng where the sun is directly overhead right now)
 * from a standard low-precision solar-position algorithm (the same family
 * of equations used by NOAA's solar calculator and the common
 * Leaflet.Terminator technique): ecliptic longitude from orbital elements,
 * obliquity-corrected right ascension/declination, then subsolar longitude
 * from the difference between right ascension and Greenwich Mean Sidereal
 * Time. Verified against known solstice/equinox reference points (e.g. the
 * June solstice at 12:00 UTC should give ~+23.4 deg latitude, ~0 deg
 * longitude) before being wired into rendering.
 */
function getSubsolarPoint(date) {
  const jd = date.getTime() / 86400000 + 2440587.5;
  const n = jd - 2451545.0;
  const rad = Math.PI / 180;
  let L = (280.46 + 0.9856474 * n) % 360; if (L < 0) L += 360;
  let g = (357.528 + 0.9856003 * n) % 360; if (g < 0) g += 360;
  const lambda = L + 1.915 * Math.sin(g * rad) + 0.02 * Math.sin(2 * g * rad);
  const epsilon = 23.439 - 0.0000004 * n;
  const lambdaRad = lambda * rad, epsilonRad = epsilon * rad;
  let alpha = Math.atan2(Math.cos(epsilonRad) * Math.sin(lambdaRad), Math.cos(lambdaRad)) / rad;
  const delta = Math.asin(Math.sin(epsilonRad) * Math.sin(lambdaRad)) / rad;
  const T = n / 36525;
  let gmst = (280.46061837 + 360.98564736629 * n + T * T * (0.000387933 - T / 38710000)) % 360;
  if (gmst < 0) gmst += 360;
  if (alpha < 0) alpha += 360;
  let lng = alpha - gmst;
  lng = (((lng + 180) % 360) + 360) % 360 - 180;
  return { lat: delta, lng: lng };
}

/**
 * Marker screen-space projection, empirically calibrated against the
 * vendored cobe build (it exposes no hit-testing API of its own): for a
 * given lat/lng/phi/theta it reproduces the exact rotation math cobe uses
 * to place markers, so DOM hotspot buttons can be kept in sync with the
 * rendered dots every frame. Verified against known marker pixel positions
 * captured from real WebGL renders at several phi/theta combinations.
 */
function projectMarker(lat, lng, phi, theta, radius) {
  const latRad = (lat * Math.PI) / 180;
  const lngRad = (lng * Math.PI) / 180;
  const cosLat = Math.cos(latRad);
  const x0 = cosLat * Math.cos(lngRad + phi);
  const y0 = Math.sin(latRad);
  const z0 = -cosLat * Math.sin(lngRad + phi);
  const cosT = Math.cos(theta);
  const sinT = Math.sin(theta);
  const y1 = y0 * cosT - z0 * sinT;
  const z1 = y0 * sinT + z0 * cosT;
  const x1 = x0;
  return {
    x: radius + radius * x1,
    y: radius - radius * y1,
    visible: z1 > 0.02,
  };
}

/** Standard geographic-to-unit-vector conversion for great-circle math
 *  (independent of the view-projection formula above -- this is purely
 *  about interpolating a point between two real lat/lng coordinates). */
function latLngToVec3(lat, lng) {
  const latRad = (lat * Math.PI) / 180;
  const lngRad = (lng * Math.PI) / 180;
  const cosLat = Math.cos(latRad);
  return [cosLat * Math.cos(lngRad), cosLat * Math.sin(lngRad), Math.sin(latRad)];
}

function vec3ToLatLng(v) {
  return [(Math.asin(v[2]) * 180) / Math.PI, (Math.atan2(v[1], v[0]) * 180) / Math.PI];
}

/** Spherical linear interpolation between two points on a great circle. */
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

function easeInOutCubic(t) {
  return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

/** Editorial "world tour" pairs for the on-load intro animation, resolved
 *  against whichever destinations actually exist on this site -- a pair is
 *  simply skipped if either slug isn't found (e.g. a destination was
 *  deleted, or the seed data was never populated). */
const FEATURED_ARC_PAIRS = [
  ["new-york-city", "london"],
  ["paris", "tokyo"],
  ["dubai", "sydney"],
  ["cape-town", "rio-de-janeiro"],
  ["singapore", "los-angeles"],
];

// Low-res grid the day/night mask is computed at, then upscaled with
// smoothing onto the full-size overlay -- cheap enough to recompute every
// frame (needed since the mapping from screen pixel to real lat/lng shifts
// as the globe rotates), and the softness this resolution produces looks
// like a natural twilight gradient rather than a hard line.
const TERMINATOR_RES = 100;

function supportsWebGL() {
  try {
    const canvas = document.createElement("canvas");
    return !!(window.WebGLRenderingContext && (canvas.getContext("webgl") || canvas.getContext("experimental-webgl")));
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

function initRoot(root) {
  let config = {};
  try {
    config = JSON.parse(root.getAttribute("data-v3datlas-config") || "{}");
  } catch (err) {
    config = {};
  }
  const markers = Array.isArray(config.markers) ? config.markers : [];
  const strings = config.strings || {};
  const restBase = config.restBase || "";

  const mount = root.querySelector("[data-v3datlas-globe-mount]");
  const sidebar = root.querySelector("[data-v3datlas-sidebar]");
  const sidebarBody = root.querySelector("[data-v3datlas-sidebar-body]");
  const sidebarClose = root.querySelector("[data-v3datlas-sidebar-close]");
  if (!mount) return;

  // The ambient globe renders regardless of whether any destinations exist
  // yet -- only the clickable hotspots depend on markers.length, and that
  // array is simply empty in that case (nothing further to guard here).
  if (!supportsWebGL()) {
    mount.style.display = "none";
    return;
  }

  const reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);

  const byslug = {};
  markers.forEach(function (m) { byslug[m.slug] = m; });
  const featuredArcs = FEATURED_ARC_PAIRS
    .map(function (pair) {
      const from = byslug[pair[0]];
      const to = byslug[pair[1]];
      return from && to ? { from: from, to: to } : null;
    })
    .filter(Boolean);

  const TOUR_ROTATE_MS = 1200;
  const TOUR_DRAW_MS = 2200;
  const TOUR_HOLD_MS = 500;
  const TOUR_PHASE_MS = TOUR_ROTATE_MS + TOUR_DRAW_MS + TOUR_HOLD_MS;

  const state = {
    canvas: null,
    globe: null,
    rafId: null,
    phi: 0,
    theta: 0.3,
    dpr: 1,
    size: 600,
    dragging: false,
    lastX: 0,
    hotspots: new Map(),
    booted: false,
    destroyTimer: null,
    captionEl: null,
    tourActive: !reduceMotion && featuredArcs.length > 0,
    tourIndex: 0,
    tourPhaseStartedAt: 0,
    tourFromPhi: 0,
  };

  function buildCanvasAndHotspots() {
    mount.innerHTML = "";
    const canvas = document.createElement("canvas");
    canvas.className = "v3datlas-globe-canvas";
    mount.appendChild(canvas);
    state.canvas = canvas;
    state.hotspots.clear();

    const termCanvas = document.createElement("canvas");
    termCanvas.className = "v3datlas-terminator-canvas";
    mount.appendChild(termCanvas);
    state.termCanvas = termCanvas;
    state.termCtx = termCanvas.getContext("2d");
    state.termOffscreen = document.createElement("canvas");
    state.termOffscreen.width = TERMINATOR_RES;
    state.termOffscreen.height = TERMINATOR_RES;
    state.termOffCtx = state.termOffscreen.getContext("2d");
    state.termImageData = state.termOffCtx.createImageData(TERMINATOR_RES, TERMINATOR_RES);

    const caption = document.createElement("p");
    caption.className = "v3datlas-tour-caption";
    caption.hidden = true;
    mount.appendChild(caption);
    state.captionEl = caption;

    markers.forEach(function (marker) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "v3datlas-marker-hotspot";
      btn.setAttribute("aria-label", marker.name || "");
      btn.hidden = true;
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        openSidebar(marker);
      });
      mount.appendChild(btn);
      state.hotspots.set(marker, btn);
    });
  }

  function createGlobeInstance() {
    // cobe multiplies width/height by devicePixelRatio internally
    // (canvas.width = options.width * options.devicePixelRatio) -- pass
    // CSS pixel dimensions here, not pre-multiplied, or the backing store
    // ends up devicePixelRatio^2 too large.
    state.globe = createGlobe(state.canvas, {
      devicePixelRatio: state.dpr,
      width: state.size,
      height: state.size,
      phi: state.phi,
      theta: state.theta,
      dark: 1,
      diffuse: 1.2,
      mapSamples: 16000,
      mapBrightness: 6,
      baseColor: [0.06, 0.24, 0.18],
      markerColor: [0.79, 0.64, 0.29],
      glowColor: [0.51, 0.4, 0.16],
      markers: markers.map(function (m) {
        return { location: [m.lat, m.lng], size: m.size || 0.05 };
      }),
    });
    state.canvas.addEventListener("webglcontextlost", onContextLost, false);
    state.canvas.addEventListener("webglcontextrestored", onContextRestored, false);
  }

  function onContextLost(e) {
    e.preventDefault();
    stopLoop();
    if (state.globe) {
      try { state.globe.destroy(); } catch (err) { /* context already gone */ }
      state.globe = null;
    }
  }

  function onContextRestored() {
    buildCanvasAndHotspots();
    sizeCanvas();
    createGlobeInstance();
    attachInteraction();
    startLoop();
  }

  function attachInteraction() {
    const canvas = state.canvas;
    let pointerId = null;

    canvas.addEventListener("pointerdown", function (e) {
      state.dragging = true;
      state.lastX = e.clientX;
      pointerId = e.pointerId;
      canvas.setPointerCapture(pointerId);
      canvas.classList.add("is-dragging");
    });
    canvas.addEventListener("pointermove", function (e) {
      if (!state.dragging) return;
      const delta = e.clientX - state.lastX;
      state.lastX = e.clientX;
      state.phi += delta * 0.008;
    });
    function endDrag() {
      state.dragging = false;
      canvas.classList.remove("is-dragging");
      if (null !== pointerId) {
        try { canvas.releasePointerCapture(pointerId); } catch (err) { /* noop */ }
      }
    }
    canvas.addEventListener("pointerup", endDrag);
    canvas.addEventListener("pointercancel", endDrag);
    canvas.addEventListener("pointerleave", endDrag);
  }

  function sizeCanvas() {
    state.size = mount.clientWidth || 600;
    state.dpr = Math.min(window.devicePixelRatio || 1, 2);
    // Backing-store pixel dimensions are set by cobe itself from the
    // width/height/devicePixelRatio passed to createGlobe(); only the CSS
    // display size needs setting here.
    state.canvas.style.width = state.size + "px";
    state.canvas.style.height = state.size + "px";
    state.termCanvas.width = state.size;
    state.termCanvas.height = state.size;
  }

  function renderTerminator(now) {
    // Real subsolar position moves slowly -- recomputing it once every 60s
    // (rather than every frame) is indistinguishable visually and avoids
    // redoing the trig for it 60 times a second.
    if (!state.subsolar || now - state.subsolarComputedAt > 60000) {
      state.subsolar = getSubsolarPoint(new Date());
      state.subsolarComputedAt = now;
    }
    const decRad = (state.subsolar.lat * Math.PI) / 180;
    const subLngRad = (state.subsolar.lng * Math.PI) / 180;
    const sinDec = Math.sin(decRad), cosDec = Math.cos(decRad);
    const cosT = Math.cos(state.theta), sinT = Math.sin(state.theta);
    const res = TERMINATOR_RES;
    const data = state.termImageData.data;

    for (let py = 0; py < res; py++) {
      for (let px = 0; px < res; px++) {
        const idx = (py * res + px) * 4;
        const u = ((px + 0.5) / res) * 2 - 1;
        const v = -(((py + 0.5) / res) * 2 - 1);
        const rr = u * u + v * v;
        if (rr > 1) {
          data[idx + 3] = 0;
          continue;
        }
        const z1 = Math.sqrt(Math.max(0, 1 - rr));
        // Undo the theta tilt, then the phi spin, to recover this pixel's
        // real (unrotated) latitude/longitude on the globe as it's
        // currently oriented -- the inverse of projectMarker() above.
        const y0 = v * cosT + z1 * sinT;
        const z0 = -v * sinT + z1 * cosT;
        const x0 = u;
        const lat = Math.asin(Math.max(-1, Math.min(1, y0)));
        const lngPlusPhi = Math.atan2(-z0, x0);
        const lngReal = lngPlusPhi - state.phi;

        const cosZenith = Math.sin(lat) * sinDec + Math.cos(lat) * cosDec * Math.cos(lngReal - subLngRad);
        // Smooth twilight band roughly +-8 degrees either side of the
        // terminator, rather than a hard day/night line.
        const t = Math.max(0, Math.min(1, 0.5 - cosZenith * 3.5));
        data[idx] = 3;
        data[idx + 1] = 10;
        data[idx + 2] = 8;
        data[idx + 3] = Math.round(t * 100);
      }
    }

    state.termOffCtx.putImageData(state.termImageData, 0, 0);
    state.termCtx.clearRect(0, 0, state.size, state.size);
    // CSS border-radius on the element does not reliably clip a canvas's
    // own raster content (confirmed empirically -- upscale smoothing was
    // spreading edge darkness into the square canvas's corners, outside the
    // circle, visible as a dark crescent). Clipping via the 2D API instead
    // constrains the draw operation itself, which is reliable everywhere.
    state.termCtx.save();
    state.termCtx.beginPath();
    state.termCtx.arc(state.size / 2, state.size / 2, state.size / 2, 0, Math.PI * 2);
    state.termCtx.clip();
    state.termCtx.imageSmoothingEnabled = true;
    state.termCtx.drawImage(state.termOffscreen, 0, 0, state.size, state.size);
    state.termCtx.restore();
  }

  function updateHotspots() {
    const radius = state.size / 2;
    state.hotspots.forEach(function (btn, marker) {
      const p = projectMarker(marker.lat, marker.lng, state.phi, state.theta, radius);
      if (!p.visible) {
        btn.hidden = true;
        return;
      }
      btn.hidden = false;
      btn.style.transform = "translate(" + (p.x - 9) + "px," + (p.y - 9) + "px)";
    });
  }

  function wrapAngle(a) {
    while (a > Math.PI) a -= 2 * Math.PI;
    while (a < -Math.PI) a += 2 * Math.PI;
    return a;
  }

  function destinationMarkerConfigs() {
    return markers.map(function (m) {
      return { location: [m.lat, m.lng], size: m.size || 0.05 };
    });
  }

  function runTourFrame(now) {
    const arc = featuredArcs[state.tourIndex];
    if (!state.tourPhaseStartedAt) {
      state.tourPhaseStartedAt = now;
      state.tourFromPhi = state.phi;
      const midpoint = vec3ToLatLng(slerp(latLngToVec3(arc.from.lat, arc.from.lng), latLngToVec3(arc.to.lat, arc.to.lng), 0.5));
      state.tourTargetPhi = state.phi + wrapAngle(-((midpoint[1] * Math.PI) / 180) - state.phi);
    }

    const elapsed = now - state.tourPhaseStartedAt;
    const update = {};

    if (elapsed < TOUR_ROTATE_MS) {
      const t = easeInOutCubic(elapsed / TOUR_ROTATE_MS);
      state.phi = state.tourFromPhi + (state.tourTargetPhi - state.tourFromPhi) * t;
      update.arcs = [];
      state.captionEl.hidden = true;
    } else if (elapsed < TOUR_ROTATE_MS + TOUR_DRAW_MS) {
      state.phi = state.tourTargetPhi;
      const t = (elapsed - TOUR_ROTATE_MS) / TOUR_DRAW_MS;
      const dotLatLng = vec3ToLatLng(slerp(latLngToVec3(arc.from.lat, arc.from.lng), latLngToVec3(arc.to.lat, arc.to.lng), Math.min(1, t)));
      update.arcs = [{ from: [arc.from.lat, arc.from.lng], to: [arc.to.lat, arc.to.lng], color: [0.91, 0.8, 0.55] }];
      update.markers = destinationMarkerConfigs().concat([{ location: dotLatLng, size: 0.07 }]);
      state.captionEl.hidden = false;
      state.captionEl.textContent = arc.from.name + " → " + arc.to.name;
    } else {
      state.phi = state.tourTargetPhi;
      if (elapsed >= TOUR_PHASE_MS) {
        state.tourIndex += 1;
        state.tourPhaseStartedAt = 0;
        if (state.tourIndex >= featuredArcs.length) {
          state.tourActive = false;
          state.captionEl.hidden = true;
          update.arcs = [];
          update.markers = destinationMarkerConfigs();
        }
      }
    }

    update.phi = state.phi;
    if (state.globe) state.globe.update(update);
  }

  function startLoop() {
    (function frame(now) {
      now = now || performance.now();
      if (state.tourActive) {
        runTourFrame(now);
      } else {
        if (!state.dragging && !reduceMotion) {
          state.phi += 0.0032;
        }
        if (state.globe) state.globe.update({ phi: state.phi });
      }
      updateHotspots();
      renderTerminator(now);
      state.rafId = requestAnimationFrame(frame);
    })();
  }

  function stopLoop() {
    if (state.rafId) cancelAnimationFrame(state.rafId);
    state.rafId = null;
  }

  function boot() {
    if (state.booted) return;
    buildCanvasAndHotspots();
    sizeCanvas();
    createGlobeInstance();
    attachInteraction();
    state.booted = true;
    startLoop();
  }

  function teardown() {
    stopLoop();
    if (state.globe) {
      try { state.globe.destroy(); } catch (err) { /* noop */ }
      state.globe = null;
    }
    mount.innerHTML = "";
    state.hotspots.clear();
    state.booted = false;
  }

  const io = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          if (state.destroyTimer) {
            clearTimeout(state.destroyTimer);
            state.destroyTimer = null;
          }
          boot();
        } else if (state.booted && !state.destroyTimer) {
          // Scrolled far out of view: free the WebGL context after a grace
          // period rather than immediately, so brief scroll-past doesn't
          // thrash context creation.
          state.destroyTimer = setTimeout(function () {
            teardown();
            state.destroyTimer = null;
          }, 20000);
        }
      });
    },
    { threshold: 0.01 }
  );
  io.observe(mount);

  if (sidebar && sidebarBody) {
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
  }

  function openSidebar(marker) {
    if (!sidebar || !sidebarBody) return;
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
    if (!sidebar) return;
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

    sidebarBody.appendChild(renderWeather(data.weather));
    sidebarBody.appendChild(renderCountry(data.country));

    if (dest.did_you_know) {
      const fact = el("div", "v3datlas-sidebar-section v3datlas-fact");
      fact.appendChild(el("h4", null, "Did you know?"));
      fact.appendChild(el("p", null, dest.did_you_know));
      sidebarBody.appendChild(fact);
    }

    sidebarBody.appendChild(renderArticles(data.articles, dest));
  }

  function renderWeather(weather) {
    const section = el("div", "v3datlas-sidebar-section v3datlas-weather");
    section.appendChild(el("h4", null, "Weather"));
    if (!weather) {
      section.appendChild(el("p", "v3datlas-muted", strings.weatherUnavailable || "Weather data is temporarily unavailable."));
      return section;
    }
    if ("current" === weather.type) {
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
    return section;
  }

  function renderCountry(country) {
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
    section.appendChild(list);
    return section;
  }

  function renderArticles(articles, dest) {
    const section = el("div", "v3datlas-sidebar-section v3datlas-articles");
    section.appendChild(el("h4", null, "Related articles"));
    if (!articles || !articles.length) {
      section.appendChild(el("p", "v3datlas-muted", strings.noArticles || "No articles yet for this destination."));
    } else {
      const list = el("ul", "v3datlas-article-list");
      articles.forEach(function (article) {
        const item = el("li", "v3datlas-article-item");
        const link = document.createElement("a");
        link.href = article.url;
        link.textContent = article.title;
        item.appendChild(link);
        list.appendChild(item);
      });
      section.appendChild(list);
    }
    if (dest.term_link) {
      const more = document.createElement("a");
      more.className = "v3datlas-sidebar-more";
      more.href = dest.term_link;
      more.textContent = "See all articles about " + (dest.name || "this destination");
      section.appendChild(more);
    }
    return section;
  }
}

document.querySelectorAll("[data-v3datlas-root]").forEach(function (root) {
  if (root.hasAttribute("data-v3datlas-ready")) return;
  root.setAttribute("data-v3datlas-ready", "1");
  initRoot(root);
});
