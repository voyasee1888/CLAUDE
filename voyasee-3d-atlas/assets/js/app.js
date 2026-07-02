import createGlobe from "v3da-cobe";

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

  if (!markers.length || !supportsWebGL()) {
    mount.style.display = "none";
    return;
  }

  const reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);

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
  };

  function buildCanvasAndHotspots() {
    mount.innerHTML = "";
    const canvas = document.createElement("canvas");
    canvas.className = "v3datlas-globe-canvas";
    mount.appendChild(canvas);
    state.canvas = canvas;
    state.hotspots.clear();

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
      baseColor: [0.13, 0.16, 0.24],
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

  function startLoop() {
    (function frame() {
      if (!state.dragging && !reduceMotion) {
        state.phi += 0.0032;
      }
      if (state.globe) state.globe.update({ phi: state.phi });
      updateHotspots();
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

    sidebarBody.appendChild(renderWeather(data.weather));
    sidebarBody.appendChild(renderCountry(data.country));
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
