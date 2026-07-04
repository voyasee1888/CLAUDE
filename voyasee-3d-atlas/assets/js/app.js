function el(tag, className, text) {
  var node = document.createElement(tag);
  if (className) node.className = className;
  if (undefined !== text && null !== text) node.textContent = text;
  return node;
}

function countryCodeToFlag(code) {
  if (!code || code.length !== 2) return "";
  return String.fromCodePoint(
    0x1f1e6 + code.toUpperCase().charCodeAt(0) - 65,
    0x1f1e6 + code.toUpperCase().charCodeAt(1) - 65
  );
}

/* ── Visited / Want-to-go localStorage tracker ── */
var v3daTracker = (function () {
  var STORAGE_KEY = "v3da_tracker";
  function load() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; } catch (e) { return {}; }
  }
  function save(data) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch (e) { /* quota */ }
  }
  return {
    isVisited: function (slug) { return (load()[slug] || {}).visited === true; },
    isWantToGo: function (slug) { return (load()[slug] || {}).wantToGo === true; },
    toggleVisited: function (slug) {
      var d = load(); if (!d[slug]) d[slug] = {};
      d[slug].visited = !d[slug].visited;
      if (d[slug].visited) d[slug].wantToGo = false;
      save(d); return d[slug].visited;
    },
    toggleWantToGo: function (slug) {
      var d = load(); if (!d[slug]) d[slug] = {};
      d[slug].wantToGo = !d[slug].wantToGo;
      if (d[slug].wantToGo) d[slug].visited = false;
      save(d); return d[slug].wantToGo;
    },
    counts: function () {
      var d = load(), v = 0, w = 0;
      Object.keys(d).forEach(function (k) {
        if (d[k].visited) v++;
        if (d[k].wantToGo) w++;
      });
      return { visited: v, wantToGo: w };
    },
    getAll: function () { return load(); }
  };
})();

/* ── Deep-link hash state ── */
var v3daHash = {
  read: function () {
    var h = window.location.hash.replace(/^#/, "");
    if (!h) return null;
    var params = {};
    h.split("&").forEach(function (part) {
      var kv = part.split("=");
      if (kv.length === 2) params[kv[0]] = decodeURIComponent(kv[1]);
    });
    return params;
  },
  write: function (obj) {
    var parts = [];
    Object.keys(obj).forEach(function (k) {
      if (obj[k] !== undefined && obj[k] !== null && obj[k] !== "") {
        parts.push(k + "=" + encodeURIComponent(obj[k]));
      }
    });
    history.replaceState(null, "", "#" + parts.join("&"));
  }
};

/* ── Solar terminator calculation ── */
function solarPosition(date) {
  var rad = Math.PI / 180;
  var dayOfYear = Math.floor((date - new Date(date.getFullYear(), 0, 0)) / 86400000);
  var declination = -23.44 * Math.cos(rad * (360 / 365) * (dayOfYear + 10));
  var hourAngle = ((date.getUTCHours() + date.getUTCMinutes() / 60) / 24 - 0.5) * 360;
  return [-hourAngle, declination];
}

function initSidebar(root, markers, strings, restBase, onOpen) {
  var byslug = {};
  markers.forEach(function (m) { byslug[m.slug] = m; });

  var sidebar = root.querySelector("[data-v3datlas-sidebar]");
  var sidebarBody = root.querySelector("[data-v3datlas-sidebar-body]");
  var sidebarClose = root.querySelector("[data-v3datlas-sidebar-close]");
  var btnVisited = root.querySelector("[data-v3datlas-sidebar-visited]");
  var btnWantGo = root.querySelector("[data-v3datlas-sidebar-wantgo]");
  var btnShare = root.querySelector("[data-v3datlas-sidebar-share]");
  if (!sidebar || !sidebarBody) {
    return { openSidebar: function () {}, openCountry: function () {}, byslug: byslug };
  }

  var reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  var currentSlug = null;
  var currentData = null;

  var ro = new ResizeObserver(function (entries) {
    for (var i = 0; i < entries.length; i++) {
      root.toggleAttribute("data-v3datlas-narrow", entries[i].contentRect.width < 400);
    }
  });
  ro.observe(root);

  sidebarClose && sidebarClose.addEventListener("click", closeSidebar);
  document.addEventListener("keydown", function (e) {
    if ("Escape" === e.key && !sidebar.hidden) closeSidebar();
  });

  if (btnVisited) btnVisited.addEventListener("click", function () {
    if (!currentSlug) return;
    var isNow = v3daTracker.toggleVisited(currentSlug);
    btnVisited.classList.toggle("is-active", isNow);
    if (btnWantGo) btnWantGo.classList.remove("is-active");
    updateVisitedStrip();
  });
  if (btnWantGo) btnWantGo.addEventListener("click", function () {
    if (!currentSlug) return;
    var isNow = v3daTracker.toggleWantToGo(currentSlug);
    btnWantGo.classList.toggle("is-active", isNow);
    if (btnVisited) btnVisited.classList.remove("is-active");
    updateVisitedStrip();
  });
  if (btnShare) btnShare.addEventListener("click", function () {
    if (!currentData) return;
    shareDestination(currentData);
  });

  function updateVisitedStrip() {
    var strip = root.querySelector("[data-v3datlas-visited-strip]");
    var countEl = root.querySelector("[data-v3datlas-visited-count]");
    if (!strip || !countEl) return;
    var c = v3daTracker.counts();
    if (c.visited > 0 || c.wantToGo > 0) {
      strip.hidden = false;
      countEl.textContent = "You’ve explored " + c.visited + " of " + markers.length + " destinations" + (c.wantToGo > 0 ? " · " + c.wantToGo + " on your list" : "");
    } else {
      strip.hidden = true;
    }
  }
  updateVisitedStrip();

  function updateActionButtons(slug) {
    if (btnVisited) btnVisited.classList.toggle("is-active", v3daTracker.isVisited(slug));
    if (btnWantGo) btnWantGo.classList.toggle("is-active", v3daTracker.isWantToGo(slug));
  }

  function shareDestination(data) {
    var dest = data.destination || {};
    var text = dest.name + ", " + dest.country + " — " + (dest.signature_line || "Discover it on Voyasee");
    var url = window.location.href.split("#")[0] + "#dest=" + (dest.slug || "");
    if (navigator.share) {
      navigator.share({ title: dest.name + " | Voyasee Atlas", text: text, url: url }).catch(function () {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(text + "\n" + url).then(function () {
        btnShare.textContent = "✓";
        setTimeout(function () { btnShare.innerHTML = "&#8599;"; }, 1500);
      });
    }
  }

  function openSidebar(marker) {
    if (!marker) return;
    currentSlug = marker.slug;
    if (onOpen) onOpen(marker);
    sidebar.hidden = false;
    requestAnimationFrame(function () { sidebar.classList.add("is-open"); });
    sidebarBody.innerHTML = "";
    sidebarBody.appendChild(el("p", "v3datlas-sidebar-loading", strings.loading || "Loading…"));
    updateActionButtons(marker.slug);

    v3daHash.write({ dest: marker.slug });

    fetch(restBase + "destinations/" + encodeURIComponent(marker.slug), { headers: { Accept: "application/json" } })
      .then(function (r) {
        return r.json().then(function (data) { return { ok: r.ok, data: data }; });
      })
      .then(function (result) {
        if (!result.ok || !result.data || false === result.data.ok) throw new Error("load failed");
        currentData = result.data;
        renderSidebar(result.data);
      })
      .catch(function () {
        sidebarBody.innerHTML = "";
        sidebarBody.appendChild(el("p", "v3datlas-sidebar-error", strings.loadError || "This destination could not be loaded."));
      });
  }

  function closeSidebar() {
    sidebar.classList.remove("is-open");
    currentSlug = null;
    currentData = null;
    setTimeout(function () { sidebar.hidden = true; }, reduceMotion ? 0 : 300);
    history.replaceState(null, "", window.location.pathname + window.location.search);
  }

  function openCountry(code, name) {
    sidebar.hidden = false;
    currentSlug = null;
    currentData = null;
    requestAnimationFrame(function () { sidebar.classList.add("is-open"); });
    sidebarBody.innerHTML = "";
    if (btnVisited) btnVisited.classList.remove("is-active");
    if (btnWantGo) btnWantGo.classList.remove("is-active");

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
    var code = data ? data.countryCode : null;
    var flag = code ? countryCodeToFlag(code) : "";
    sidebarBody.appendChild(el("h3", "v3datlas-sidebar-name", (flag ? flag + " " : "") + (name || strings.countryFallbackName || "This country")));
    sidebarBody.appendChild(el("p", "v3datlas-sidebar-country", strings.countryOverviewLabel || "Country overview"));

    if (!data) {
      sidebarBody.appendChild(el("p", "v3datlas-sidebar-error", strings.countryNoData || "No information is available for this country yet."));
      return;
    }

    if (data.country) {
      sidebarBody.appendChild(renderCountryDashboard(data.country, data.upcomingHoliday));
    }

    if (data.destinations && data.destinations.length) {
      var section = el("div", "v3datlas-sidebar-card");
      section.appendChild(el("h4", "v3datlas-card-title", strings.destinationsInCountry || "Destinations we cover here"));
      var list = el("div", "v3datlas-related-chips");
      data.destinations.slice(0, 8).forEach(function (item) {
        var chip = document.createElement("button");
        chip.type = "button";
        chip.className = "v3datlas-related-chip";
        chip.textContent = item.name;
        chip.addEventListener("click", function () {
          var marker = byslug[item.slug];
          if (marker) openSidebar(marker);
        });
        list.appendChild(chip);
      });
      section.appendChild(list);
      sidebarBody.appendChild(section);
    }
  }

  function renderCountryStatsAndLanguages(card, country) {
    if (country.population || country.area || country.capital) {
      var statsRow = el("div", "v3datlas-stat-row");
      if (country.population) {
        var popVal = country.population > 1e6 ? (country.population / 1e6).toFixed(1) + "M" : (country.population / 1e3).toFixed(0) + "K";
        statsRow.appendChild(renderStatTile("Population", popVal));
      }
      if (country.area) {
        var areaVal = country.area > 1e6 ? (country.area / 1e6).toFixed(1) + "M km²" : Math.round(country.area).toLocaleString() + " km²";
        statsRow.appendChild(renderStatTile("Area", areaVal));
      }
      if (country.capital) {
        statsRow.appendChild(renderStatTile("Capital", country.capital));
      }
      card.appendChild(statsRow);
    }

    if (country.languages && country.languages.length) {
      card.appendChild(el("p", "v3datlas-country-detail", "Languages: " + (Array.isArray(country.languages) ? country.languages.slice(0, 4).join(", ") : country.languages)));
    }
  }

  function renderCountryDashboard(country, upcomingHoliday) {
    var card = el("div", "v3datlas-sidebar-card");
    card.appendChild(el("h4", "v3datlas-card-title", "Country notes"));
    if (!country) {
      card.appendChild(el("p", "v3datlas-muted", strings.countryUnavailable || "Country details are temporarily unavailable."));
      return card;
    }

    renderCountryStatsAndLanguages(card, country);

    var list = el("ul", "v3datlas-country-facts");
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
    card.appendChild(list);
    return card;
  }

  function renderStatTile(label, value) {
    var tile = el("div", "v3datlas-stat-tile");
    tile.appendChild(el("span", "v3datlas-stat-value", value));
    tile.appendChild(el("span", "v3datlas-stat-label", label));
    return tile;
  }

  function renderSidebar(data) {
    var dest = data.destination || {};
    sidebarBody.innerHTML = "";

    if (dest.hero_image_url) {
      var img = document.createElement("img");
      img.className = "v3datlas-sidebar-hero";
      img.src = dest.hero_image_url;
      img.alt = "";
      img.loading = "lazy";
      sidebarBody.appendChild(img);
    }

    var flag = dest.country_code ? countryCodeToFlag(dest.country_code) : "";
    sidebarBody.appendChild(el("h3", "v3datlas-sidebar-name", dest.name || ""));
    sidebarBody.appendChild(el("p", "v3datlas-sidebar-country", (flag ? flag + " " : "") + (dest.country || "")));

    if (dest.signature_line) {
      sidebarBody.appendChild(el("p", "v3datlas-sidebar-signature", dest.signature_line));
    }

    // "Right Now" live strip
    sidebarBody.appendChild(renderLiveStrip(data));

    // Travel Snapshot stat tiles
    sidebarBody.appendChild(renderTravelSnapshot(dest));

    // Weather card
    sidebarBody.appendChild(renderWeatherCard(data.weather, data.bestTime, data.monthlyAppeal));

    // Country notes card
    sidebarBody.appendChild(renderCountryCard(data.country, data.upcomingHoliday, data.exchangeRate));

    // Did You Know card
    if (dest.did_you_know) {
      var factCard = el("div", "v3datlas-sidebar-card");
      factCard.appendChild(el("h4", "v3datlas-card-title", "Did you know?"));
      factCard.appendChild(el("p", "v3datlas-fact-text", dest.did_you_know));
      sidebarBody.appendChild(factCard);
    }

    // Wikipedia excerpt
    if (data.wikiExcerpt) {
      var wikiCard = el("div", "v3datlas-sidebar-card");
      wikiCard.appendChild(el("h4", "v3datlas-card-title", "About " + dest.name));
      wikiCard.appendChild(el("p", "v3datlas-wiki-text", data.wikiExcerpt));
      var attr = el("p", "v3datlas-attribution", strings.wikiAttribution || "Source: Wikipedia (CC BY-SA)");
      wikiCard.appendChild(attr);
      sidebarBody.appendChild(wikiCard);
    }

    sidebarBody.appendChild(renderRelatedDestinations(data.nearby, data.sameCountry));
  }

  function renderLiveStrip(data) {
    var strip = el("div", "v3datlas-live-strip");
    var dest = data.destination || {};
    var country = data.country || {};

    // Local time
    if (country.timezone) {
      try {
        var now = new Date();
        var localTime = now.toLocaleTimeString("en-US", { timeZone: country.timezone, hour: "2-digit", minute: "2-digit", hour12: true });
        var item = el("span", "v3datlas-live-item");
        item.appendChild(el("span", "v3datlas-live-icon", "⏰"));
        item.appendChild(el("span", null, localTime));
        strip.appendChild(item);
      } catch (e) { /* timezone not supported */ }
    }

    // Current weather temperature
    if (data.weather && data.weather.tempC !== undefined && data.weather.tempC !== null) {
      var tempItem = el("span", "v3datlas-live-item");
      tempItem.appendChild(el("span", "v3datlas-live-icon", "☀"));
      tempItem.appendChild(el("span", null, Math.round(data.weather.tempC) + "°C"));
      strip.appendChild(tempItem);
    }

    // Sunrise/sunset
    if (data.sunriseSunset && data.sunriseSunset.sunset) {
      try {
        var sunsetDate = new Date(data.sunriseSunset.sunset);
        var tz = data.sunriseSunset.timezone || country.timezone;
        var sunsetStr = sunsetDate.toLocaleTimeString("en-US", { timeZone: tz || undefined, hour: "2-digit", minute: "2-digit", hour12: true });
        var sunItem = el("span", "v3datlas-live-item");
        sunItem.appendChild(el("span", "v3datlas-live-icon", "🌅"));
        sunItem.appendChild(el("span", null, sunsetStr));
        strip.appendChild(sunItem);
      } catch (e) { /* fallback */ }
    }

    return strip.children.length ? strip : el("div");
  }

  function renderTravelSnapshot(dest) {
    if (!dest.cost_level && !dest.safety_rating && !dest.walkability) return el("div");
    var card = el("div", "v3datlas-sidebar-card");
    card.appendChild(el("h4", "v3datlas-card-title", "Travel snapshot"));

    var grid = el("div", "v3datlas-snapshot-grid");

    if (dest.cost_level) {
      var costTile = el("div", "v3datlas-snapshot-tile");
      costTile.appendChild(el("span", "v3datlas-snapshot-label", "Cost"));
      var dots = "";
      for (var i = 0; i < 4; i++) dots += i < dest.cost_level ? "$" : "·";
      costTile.appendChild(el("span", "v3datlas-snapshot-value v3datlas-snapshot-cost", dots));
      grid.appendChild(costTile);
    }

    if (dest.safety_rating) {
      var safeTile = el("div", "v3datlas-snapshot-tile");
      safeTile.appendChild(el("span", "v3datlas-snapshot-label", "Safety"));
      var bars = el("span", "v3datlas-snapshot-bars");
      for (var j = 0; j < 5; j++) {
        var bar = el("span", "v3datlas-bar" + (j < dest.safety_rating ? " is-filled" : ""));
        bars.appendChild(bar);
      }
      safeTile.appendChild(bars);
      grid.appendChild(safeTile);
    }

    if (dest.walkability) {
      var walkTile = el("div", "v3datlas-snapshot-tile");
      walkTile.appendChild(el("span", "v3datlas-snapshot-label", "Walkability"));
      walkTile.appendChild(el("span", "v3datlas-snapshot-value", dest.walkability.charAt(0).toUpperCase() + dest.walkability.slice(1)));
      grid.appendChild(walkTile);
    }

    if (dest.english_level) {
      var engTile = el("div", "v3datlas-snapshot-tile");
      engTile.appendChild(el("span", "v3datlas-snapshot-label", "English"));
      engTile.appendChild(el("span", "v3datlas-snapshot-value", dest.english_level.charAt(0).toUpperCase() + dest.english_level.slice(1)));
      grid.appendChild(engTile);
    }

    if (dest.avg_days) {
      var daysTile = el("div", "v3datlas-snapshot-tile");
      daysTile.appendChild(el("span", "v3datlas-snapshot-label", "Ideal stay"));
      daysTile.appendChild(el("span", "v3datlas-snapshot-value", dest.avg_days));
      grid.appendChild(daysTile);
    }

    card.appendChild(grid);

    if (dest.best_for && dest.best_for.length) {
      var tagRow = el("div", "v3datlas-best-for-tags");
      dest.best_for.forEach(function (tag) {
        tagRow.appendChild(el("span", "v3datlas-best-for-tag", tag));
      });
      card.appendChild(tagRow);
    }

    // Radar chart
    if (dest.cost_level && dest.safety_rating) {
      card.appendChild(renderRadarChart(dest));
    }

    return card;
  }

  function renderRadarChart(dest) {
    var axes = [
      { label: "Cost", value: dest.cost_level / 4 },
      { label: "Safety", value: dest.safety_rating / 5 },
      { label: "Walk", value: dest.walkability === "high" ? 1 : dest.walkability === "medium" ? 0.6 : 0.3 },
      { label: "English", value: dest.english_level === "high" ? 1 : dest.english_level === "medium" ? 0.6 : 0.3 }
    ];
    var size = 120, cx = size / 2, cy = size / 2, r = size / 2 - 20;
    var n = axes.length;
    // Side axis labels ("English", "Safety") sit close to the plot's own
    // 0/120 edges at this radius, so a plain 0-size viewBox clips them --
    // pad the viewBox itself rather than the geometry so cx/cy/r (and the
    // polygon math below) stay simple.
    var pad = 20;

    var svgNS = "http://www.w3.org/2000/svg";
    var svg = document.createElementNS(svgNS, "svg");
    svg.setAttribute("viewBox", (-pad) + " " + (-pad) + " " + (size + pad * 2) + " " + (size + pad * 2));
    svg.setAttribute("class", "v3datlas-radar-chart");
    svg.setAttribute("aria-hidden", "true");

    // Grid circles
    [0.33, 0.66, 1].forEach(function (level) {
      var circle = document.createElementNS(svgNS, "circle");
      circle.setAttribute("cx", cx);
      circle.setAttribute("cy", cy);
      circle.setAttribute("r", r * level);
      circle.setAttribute("class", "v3datlas-radar-grid");
      svg.appendChild(circle);
    });

    // Axes + labels
    var points = [];
    axes.forEach(function (axis, i) {
      var angle = (Math.PI * 2 * i) / n - Math.PI / 2;
      var x = cx + r * Math.cos(angle);
      var y = cy + r * Math.sin(angle);
      var line = document.createElementNS(svgNS, "line");
      line.setAttribute("x1", cx); line.setAttribute("y1", cy);
      line.setAttribute("x2", x); line.setAttribute("y2", y);
      line.setAttribute("class", "v3datlas-radar-grid");
      svg.appendChild(line);

      var lx = cx + (r + 14) * Math.cos(angle);
      var ly = cy + (r + 14) * Math.sin(angle);
      var text = document.createElementNS(svgNS, "text");
      text.setAttribute("x", lx); text.setAttribute("y", ly);
      text.setAttribute("text-anchor", "middle");
      text.setAttribute("dominant-baseline", "central");
      text.setAttribute("class", "v3datlas-radar-label");
      text.textContent = axis.label;
      svg.appendChild(text);

      var px = cx + r * axis.value * Math.cos(angle);
      var py = cy + r * axis.value * Math.sin(angle);
      points.push(px + "," + py);
    });

    var polygon = document.createElementNS(svgNS, "polygon");
    polygon.setAttribute("points", points.join(" "));
    polygon.setAttribute("class", "v3datlas-radar-fill");
    svg.appendChild(polygon);

    var wrap = el("div", "v3datlas-radar-wrap");
    wrap.appendChild(svg);
    return wrap;
  }

  function renderWeatherCard(weather, bestTime, monthlyAppeal) {
    var card = el("div", "v3datlas-sidebar-card");
    card.appendChild(el("h4", "v3datlas-card-title", "Weather"));
    if (!weather) {
      card.appendChild(el("p", "v3datlas-muted", strings.weatherUnavailable || "Weather data is temporarily unavailable."));
    } else if ("current" === weather.type) {
      var row = el("p", "v3datlas-weather-now");
      if (null !== weather.tempC && undefined !== weather.tempC) {
        row.appendChild(el("strong", null, Math.round(weather.tempC) + "°C"));
      }
      if (weather.conditionText) row.appendChild(document.createTextNode(" " + weather.conditionText));
      card.appendChild(row);
    } else if ("climate_normals" === weather.type) {
      var normRow = el("p", "v3datlas-weather-normals");
      var label = weather.month ? weather.month + " avg: " : "Typical this month: ";
      normRow.appendChild(document.createTextNode(label));
      if (null !== weather.tempMeanC && undefined !== weather.tempMeanC) {
        normRow.appendChild(el("strong", null, Math.round(weather.tempMeanC) + "°C"));
      }
      card.appendChild(normRow);
    }

    if (bestTime && bestTime.months && bestTime.months.length) {
      var btRow = el("p", "v3datlas-best-time");
      btRow.appendChild(document.createTextNode("Best time to visit: "));
      btRow.appendChild(el("strong", null, bestTime.months.join(" & ")));
      if (bestTime.highlight) btRow.appendChild(document.createTextNode(" (" + bestTime.highlight + ")"));
      card.appendChild(btRow);
    }

    // Monthly heatmap calendar
    if (monthlyAppeal && monthlyAppeal.appeal) {
      card.appendChild(renderHeatmapCalendar(monthlyAppeal));
    }

    return card;
  }

  function renderHeatmapCalendar(monthlyAppeal) {
    var wrap = el("div", "v3datlas-heatmap-wrap");
    var appeal = monthlyAppeal.appeal;
    var months = monthlyAppeal.months || ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    var minA = Math.min.apply(null, appeal);
    var maxA = Math.max.apply(null, appeal);
    var range = maxA - minA || 1;
    var currentMonth = new Date().getMonth();

    var strip = el("div", "v3datlas-heatmap-strip");
    for (var i = 0; i < 12; i++) {
      var cell = el("div", "v3datlas-heatmap-cell" + (i === currentMonth ? " is-current" : ""));
      var normalized = (appeal[i] - minA) / range;
      var hue = Math.round(normalized * 120);
      cell.style.backgroundColor = "hsla(" + hue + ", 70%, 45%, 0.7)";
      cell.appendChild(el("span", "v3datlas-heatmap-month", months[i].substring(0, 3)));
      cell.title = months[i] + ": " + appeal[i] + "/100";
      strip.appendChild(cell);
    }
    wrap.appendChild(strip);
    var legend = el("div", "v3datlas-heatmap-legend");
    legend.appendChild(el("span", null, "Avoid"));
    legend.appendChild(el("span", null, "Ideal"));
    wrap.appendChild(legend);
    return wrap;
  }

  function renderCountryCard(country, upcomingHoliday, exchangeRate) {
    var card = el("div", "v3datlas-sidebar-card");
    card.appendChild(el("h4", "v3datlas-card-title", "Country notes"));
    if (!country) {
      card.appendChild(el("p", "v3datlas-muted", strings.countryUnavailable || "Country details are temporarily unavailable."));
      return card;
    }

    renderCountryStatsAndLanguages(card, country);

    var list = el("ul", "v3datlas-country-facts");
    if (country.currencyName) {
      var currText = "Currency: " + country.currencyName + (country.currencyCode ? " (" + country.currencyCode + ")" : "");
      if (exchangeRate && exchangeRate.rate) {
        currText += " — 1 " + exchangeRate.base + " = " + exchangeRate.rate + " " + exchangeRate.target;
      }
      list.appendChild(el("li", null, currText));
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
    card.appendChild(list);

    if (exchangeRate) {
      card.appendChild(el("p", "v3datlas-attribution", strings.exchangeAttribution || "Frankfurter.dev (ECB)"));
    }
    return card;
  }

  function renderRelatedDestinations(nearby, sameCountry) {
    var combined = [];
    var seen = {};
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

    var card = el("div", "v3datlas-sidebar-card");
    card.appendChild(el("h4", "v3datlas-card-title", "You might also like"));
    var list = el("div", "v3datlas-related-chips");
    combined.slice(0, 5).forEach(function (item) {
      var chip = document.createElement("button");
      chip.type = "button";
      chip.className = "v3datlas-related-chip";
      chip.textContent = item.name;
      chip.addEventListener("click", function () {
        var marker = byslug[item.slug];
        if (marker) openSidebar(marker);
      });
      list.appendChild(chip);
    });
    card.appendChild(list);
    return card;
  }

  return { openSidebar: openSidebar, openCountry: openCountry, byslug: byslug };
}

function initDestinationList(root, openSidebar, byslug) {
  var list = root.querySelector("[data-v3datlas-destination-list]");
  if (!list) return;
  list.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-v3datlas-open-slug]");
    if (!btn) return;
    e.preventDefault();
    var marker = byslug[btn.getAttribute("data-v3datlas-open-slug")];
    if (marker) openSidebar(marker);
  });
}

function initListSearch(root) {
  var input = root.querySelector("[data-v3datlas-list-search]");
  if (!input) return;
  var regions = root.querySelectorAll("[data-v3datlas-region]");

  input.addEventListener("input", function () {
    var q = input.value.trim().toLowerCase();
    regions.forEach(function (region) {
      var anyVisible = false;
      region.querySelectorAll("[data-v3datlas-region-item]").forEach(function (item) {
        var match = !q || (item.getAttribute("data-name") || "").indexOf(q) !== -1;
        item.toggleAttribute("data-v3datlas-hidden", !match);
        if (match) anyVisible = true;
      });
      region.toggleAttribute("data-v3datlas-hidden", !anyVisible);
      if (q) region.open = anyVisible;
    });
  });
}

function latLngToVec3(lat, lng) {
  var latRad = (lat * Math.PI) / 180;
  var lngRad = (lng * Math.PI) / 180;
  var cosLat = Math.cos(latRad);
  return [cosLat * Math.cos(lngRad), cosLat * Math.sin(lngRad), Math.sin(latRad)];
}

function vec3ToLatLng(v) {
  return [(Math.asin(v[2]) * 180) / Math.PI, (Math.atan2(v[1], v[0]) * 180) / Math.PI];
}

function slerp(a, b, t) {
  var dot = a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
  dot = Math.max(-1, Math.min(1, dot));
  var omega = Math.acos(dot);
  if (omega < 1e-6) return a;
  var sinOmega = Math.sin(omega);
  var s0 = Math.sin((1 - t) * omega) / sinOmega;
  var s1 = Math.sin(t * omega) / sinOmega;
  return [a[0] * s0 + b[0] * s1, a[1] * s0 + b[1] * s1, a[2] * s0 + b[2] * s1];
}

function greatCircleLine(from, to, steps) {
  var a = latLngToVec3(from.lat, from.lng);
  var b = latLngToVec3(to.lat, to.lng);
  var points = [];
  for (var i = 0; i <= steps; i++) {
    var pt = vec3ToLatLng(slerp(a, b, i / steps));
    points.push([pt[1], pt[0]]);
  }
  var lines = [[]];
  for (var j = 0; j < points.length; j++) {
    var cur = points[j];
    var prev = points[j - 1];
    if (prev && Math.abs(cur[0] - prev[0]) > 180) {
      lines.push([]);
    }
    lines[lines.length - 1].push(cur);
  }
  return lines.filter(function (line) { return line.length > 1; });
}

var DEFAULT_FEATURED_ARC_PAIRS = [
  ["new-york-city", "london"],
  ["paris", "tokyo"],
  ["dubai", "sydney"],
  ["cape-town", "rio-de-janeiro"],
  ["singapore", "los-angeles"],
];

// Region color tints for country fills, keyed by this plugin's own
// destination region names. The bundled world topology carries no region
// property of its own to key off, so the tint is looked up at runtime via
// each country's alpha-2 code -> the region of a Voyasee destination in
// that country (see buildCountryRegionMap). Countries with no Voyasee
// destination simply keep the default fill rather than guessing a region.
var REGION_TINTS = {
  "Africa": "rgba(210, 160, 60, 0.10)",
  "Asia": "rgba(180, 80, 120, 0.08)",
  "Middle East": "rgba(180, 120, 80, 0.09)",
  "Europe": "rgba(80, 140, 180, 0.10)",
  "North America": "rgba(100, 180, 100, 0.08)",
  "Caribbean": "rgba(100, 180, 160, 0.09)",
  "South America": "rgba(120, 170, 90, 0.09)",
  "Oceania": "rgba(80, 180, 160, 0.10)"
};

function buildCountryRegionMap(markers) {
  var map = {};
  markers.forEach(function (m) {
    if (m.countryCode && m.region && !map[m.countryCode]) map[m.countryCode] = m.region;
  });
  return map;
}

var MAP_WIDTH = 960;
var MAP_HEIGHT = 600;
var MAP_MIN_SCALE = 1;
var MAP_MAX_SCALE = 8;

function initMap(root, markers, config, openSidebar, openCountry) {
  var mount = root.querySelector("[data-v3datlas-map-mount]");
  if (!mount || !markers.length) return { flyToMarker: function () {} };

  var strings = config.strings || {};
  if (!window.d3 || !window.Supercluster || !window.topojson || !config.worldDataUrl) {
    mount.innerHTML = "";
    mount.appendChild(el("p", "v3datlas-map-unavailable", strings.mapUnavailable || ""));
    return { flyToMarker: function () {} };
  }

  var reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  var byslug = {};
  markers.forEach(function (m) { byslug[m.slug] = m; });

  // Active filter state
  var activeRegionFilter = "all";
  var filteredSlugs = null;

  mount.innerHTML = "";
  var svg = d3.select(mount)
    .append("svg")
    .attr("class", "v3datlas-map-svg")
    .attr("viewBox", "0 0 " + MAP_WIDTH + " " + MAP_HEIGHT)
    .attr("role", "img")
    .attr("aria-label", strings.mapAriaLabel || "");

  // Defs for atmospheric glow
  var defs = svg.append("defs");
  var glowGrad = defs.append("radialGradient").attr("id", "v3da-atmo-glow");
  glowGrad.append("stop").attr("offset", "85%").attr("stop-color", "rgba(100,160,220,0)");
  glowGrad.append("stop").attr("offset", "100%").attr("stop-color", "rgba(100,160,220,0.25)");

  var sphere = svg.append("path").attr("class", "v3datlas-globe-sphere");
  var atmosphereGlow = svg.append("circle").attr("class", "v3datlas-atmosphere");
  var graticuleLayer = svg.append("path").attr("class", "v3datlas-globe-graticule");
  var countriesLayer = svg.append("g").attr("class", "v3datlas-map-countries");
  var terminatorLayer = svg.append("path").attr("class", "v3datlas-terminator");
  var routesLayer = svg.append("g").attr("class", "v3datlas-map-routes");
  var flightArcLayer = svg.append("path").attr("class", "v3datlas-flight-arc");
  var markersLayer = svg.append("g").attr("class", "v3datlas-map-markers");

  // Seasonal tilt: favour the hemisphere currently in summer
  var month = new Date().getMonth();
  var seasonalPhi = (month >= 3 && month <= 8) ? -20 : -8;
  var projection = d3.geoOrthographic().clipAngle(90).rotate([-10, seasonalPhi, 0]);
  var path = d3.geoPath(projection);
  var graticule = d3.geoGraticule();
  projection.fitSize([MAP_WIDTH, MAP_HEIGHT], { type: "Sphere" });
  var baseScale = projection.scale();
  var HORIZON_LIMIT = Math.PI / 2 - 0.02;
  var userLocation = null;

  // Try to get user location for flight arcs + distance
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (pos) {
      userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
    }, function () {}, { timeout: 5000, enableHighAccuracy: false });
  }

  function clampScale(s) {
    return Math.max(baseScale * MAP_MIN_SCALE, Math.min(baseScale * MAP_MAX_SCALE, s));
  }

  // Controls
  var zoomControls = document.createElement("div");
  zoomControls.className = "v3datlas-map-zoom-controls";
  zoomControls.innerHTML =
    '<button type="button" class="v3datlas-map-zoom-btn" data-zoom-in aria-label="Zoom in">+</button>' +
    '<button type="button" class="v3datlas-map-zoom-btn" data-zoom-out aria-label="Zoom out">−</button>';
  mount.appendChild(zoomControls);

  var attribution = el("p", "v3datlas-map-attribution", "Map data © Natural Earth");
  mount.appendChild(attribution);

  // Minimap container
  var minimapWrap = document.createElement("div");
  minimapWrap.className = "v3datlas-minimap";
  minimapWrap.style.display = "none";
  mount.appendChild(minimapWrap);
  var minimapSvg = d3.select(minimapWrap).append("svg").attr("viewBox", "0 0 120 120");
  var miniProjection = d3.geoOrthographic().clipAngle(90).translate([60, 60]).scale(55);
  var miniPath = d3.geoPath(miniProjection);
  var miniSphere = minimapSvg.append("path").attr("class", "v3datlas-mini-sphere");
  var miniCountries = minimapSvg.append("g").attr("class", "v3datlas-mini-countries");

  var index = new Supercluster({ radius: 50, maxZoom: 9 });
  function rebuildIndex() {
    var filteredMarkers = filteredSlugs ? markers.filter(function (m) { return filteredSlugs[m.slug]; }) : markers;
    index.load(filteredMarkers.map(function (m) {
      return {
        type: "Feature",
        properties: { slug: m.slug, name: m.name, weight: m.weight || 0 },
        geometry: { type: "Point", coordinates: [m.lng, m.lat] },
      };
    }));
  }
  rebuildIndex();

  var countryPaths = null;
  var routePaths = null;
  var worldFeatures = null;

  function render() {
    sphere.attr("d", path({ type: "Sphere" }));
    graticuleLayer.attr("d", path(graticule()));

    // Atmosphere glow
    var center = projection([0, 0]);
    if (center) {
      var globeR = projection.scale();
      // Use the sphere center (projection translation point)
      var trans = projection.translate();
      atmosphereGlow.attr("cx", trans[0]).attr("cy", trans[1]).attr("r", globeR + 8).attr("fill", "url(#v3da-atmo-glow)");
    }

    // Day/night terminator
    var sunPos = solarPosition(new Date());
    var nightCircle = d3.geoCircle().center([sunPos[0] + 180, -sunPos[1]]).radius(90);
    terminatorLayer.attr("d", path(nightCircle()));

    if (countryPaths) countryPaths.attr("d", path);
    if (routePaths) routePaths.attr("d", path);

    // Flight arc
    flightArcLayer.attr("d", null);

    renderMarkers();
    updateMinimap();
  }

  function updateMinimap() {
    var ratio = projection.scale() / baseScale;
    if (ratio > 2) {
      minimapWrap.style.display = "block";
      var rot = projection.rotate();
      miniProjection.rotate(rot);
      miniSphere.attr("d", miniPath({ type: "Sphere" }));
      if (worldFeatures) {
        var sel = miniCountries.selectAll("path").data(worldFeatures);
        sel.enter().append("path").attr("class", "v3datlas-mini-country").merge(sel).attr("d", miniPath);
      }
    } else {
      minimapWrap.style.display = "none";
    }
  }

  // Auto-rotation
  var autoPanFrame = null;
  var autoPanActive = false;
  var autoPanResumeTimer = null;
  var autoPanLastTime = null;
  var AUTO_ROTATE_DEG_PER_SEC = 4;
  var AUTO_PAN_RESUME_DELAY = 2500;

  function stopAutoPan() {
    autoPanActive = false;
    autoPanLastTime = null;
    if (autoPanResumeTimer) { clearTimeout(autoPanResumeTimer); autoPanResumeTimer = null; }
    if (autoPanFrame) { cancelAnimationFrame(autoPanFrame); autoPanFrame = null; }
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
    var dt = (now - autoPanLastTime) / 1000;
    autoPanLastTime = now;
    var r = projection.rotate();
    var lambda = r[0] + AUTO_ROTATE_DEG_PER_SEC * dt;
    if (lambda > 360 || lambda < -360) lambda %= 360;
    projection.rotate([lambda, r[1], r[2]]);
    render();
    autoPanFrame = requestAnimationFrame(stepAutoPan);
  }

  // Drag rotation
  var dragRotateStart = null;
  var dragRotateFrom = null;
  var drag = d3.drag()
    .clickDistance(6)
    .on("start", function (event) {
      stopAutoPan();
      dragRotateFrom = projection.rotate();
      dragRotateStart = [event.x, event.y];
    })
    .on("drag", function (event) {
      var degPerPixel = 180 / (Math.PI * projection.scale());
      var dx = event.x - dragRotateStart[0];
      var dy = event.y - dragRotateStart[1];
      var lambda = dragRotateFrom[0] + dx * degPerPixel;
      var phi = Math.max(-90, Math.min(90, dragRotateFrom[1] - dy * degPerPixel));
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
    var factor = event.deltaY < 0 ? 1.08 : 1 / 1.08;
    projection.scale(clampScale(projection.scale() * factor));
    render();
    scheduleAutoPanResume();
  });

  function animateScale(targetScale, duration) {
    var clamped = clampScale(targetScale);
    if (reduceMotion || !duration) {
      projection.scale(clamped);
      render();
      scheduleAutoPanResume();
      return;
    }
    var interp = d3.interpolate(projection.scale(), clamped);
    d3.transition().duration(duration).tween("v3da-scale", function () {
      return function (t) { projection.scale(interp(t)); render(); };
    }).on("end", scheduleAutoPanResume);
  }

  function zoomBy(factor) {
    stopAutoPan();
    animateScale(projection.scale() * factor, reduceMotion ? 0 : 300);
  }

  zoomControls.querySelector("[data-zoom-in]").addEventListener("click", function () { zoomBy(1.6); });
  zoomControls.querySelector("[data-zoom-out]").addEventListener("click", function () { zoomBy(1 / 1.6); });

  function flyToPoint(lngLat, targetScale, duration) {
    stopAutoPan();
    var current = projection.rotate();
    var dLambda = -lngLat[0] - current[0];
    dLambda = ((dLambda + 180) % 360 + 360) % 360 - 180;
    var targetRotate = [current[0] + dLambda, -lngLat[1], 0];
    var clampedScale = clampScale(targetScale);

    if (reduceMotion || !duration) {
      projection.rotate(targetRotate).scale(clampedScale);
      render();
      scheduleAutoPanResume();
      return;
    }

    var rotateInterp = d3.interpolate(current, targetRotate);
    var scaleInterp = d3.interpolate(projection.scale(), clampedScale);
    d3.transition().duration(duration).tween("v3da-globe", function () {
      return function (t) { projection.rotate(rotateInterp(t)).scale(scaleInterp(t)); render(); };
    }).on("end", scheduleAutoPanResume);
  }

  function superclusterZoom() {
    var ratio = projection.scale() / baseScale;
    return Math.max(0, Math.min(9, Math.round(Math.log2(Math.max(ratio, 1e-6)) + 2)));
  }

  function renderMarkers() {
    var clusters = index.getClusters([-180, -85, 180, 85], superclusterZoom());
    var rotate = projection.rotate();
    var center = [-rotate[0], -rotate[1]];
    var visible = clusters.filter(function (d) {
      return d3.geoDistance(d.geometry.coordinates, center) < HORIZON_LIMIT;
    });

    // Mark visited/want-to-go
    var trackerData = v3daTracker.getAll();

    var sel = markersLayer.selectAll("g.v3datlas-marker")
      .data(visible, function (d) { return d.properties.cluster ? "cluster-" + d.id : d.properties.slug; });

    sel.exit().remove();

    var entered = sel.enter().append("g").attr("class", "v3datlas-marker");
    entered.each(function (d) {
      var g = d3.select(this);
      if (d.properties.cluster) {
        g.attr("class", "v3datlas-marker v3datlas-marker-cluster");
        g.append("circle").attr("class", "v3datlas-cluster-glow");
        g.append("circle").attr("class", "v3datlas-cluster-dot");
        g.append("text").attr("class", "v3datlas-cluster-label").attr("text-anchor", "middle").attr("dy", "0.32em");
      } else {
        var markerClass = "v3datlas-marker v3datlas-marker-point";
        var td = trackerData[d.properties.slug];
        if (td && td.visited) markerClass += " is-visited";
        if (td && td.wantToGo) markerClass += " is-wantgo";
        g.attr("class", markerClass);
        g.append("circle").attr("class", "v3datlas-point-glow");
        g.append("circle").attr("class", "v3datlas-point-dot");
        g.append("title");
      }
      g.style("cursor", "pointer");
      g.on("click", function (event, dd) { handleMarkerClick(dd); });
    });

    var merged = entered.merge(sel);
    merged.each(function (d) {
      var xy = projection(d.geometry.coordinates);
      var g = d3.select(this);
      g.attr("transform", "translate(" + xy[0] + "," + xy[1] + ")");
      if (d.properties.cluster) {
        var count = d.properties.point_count;
        var r = count < 10 ? 13 : count < 30 ? 17 : 21;
        g.select(".v3datlas-cluster-glow").attr("r", r + 8);
        g.select(".v3datlas-cluster-dot").attr("r", r);
        g.select(".v3datlas-cluster-label").text(d.properties.point_count_abbreviated);
      } else {
        var pr = 4.5 + 2.5 * (d.properties.weight || 0);
        g.select(".v3datlas-point-glow").attr("r", pr + 6);
        g.select(".v3datlas-point-dot").attr("r", pr);
        g.select("title").text(d.properties.name);
      }
    });
  }

  function handleMarkerClick(d) {
    if (d.properties.cluster) {
      var expansionZoom = Math.min(9, index.getClusterExpansionZoom(d.id));
      var targetScale = baseScale * Math.pow(2, expansionZoom - 2);
      flyToPoint(d.geometry.coordinates, targetScale, 900);
    } else {
      var marker = byslug[d.properties.slug];
      if (marker) {
        // Draw flight arc from user location
        if (userLocation) {
          drawFlightArc(userLocation, { lat: marker.lat, lng: marker.lng });
        }
        openSidebar(marker);
      }
    }
  }

  function drawFlightArc(from, to) {
    var arcPoints = [];
    var a = latLngToVec3(from.lat, from.lng);
    var b = latLngToVec3(to.lat, to.lng);
    for (var i = 0; i <= 64; i++) {
      var pt = vec3ToLatLng(slerp(a, b, i / 64));
      arcPoints.push([pt[1], pt[0]]);
    }
    var geojson = { type: "Feature", geometry: { type: "LineString", coordinates: arcPoints } };
    flightArcLayer.attr("d", path(geojson));
    if (!reduceMotion) {
      var totalLen = flightArcLayer.node().getTotalLength();
      if (totalLen) {
        flightArcLayer
          .attr("stroke-dasharray", totalLen)
          .attr("stroke-dashoffset", totalLen)
          .transition().duration(1500).ease(d3.easeQuadOut)
          .attr("stroke-dashoffset", 0);
      }
    }
  }

  function flyToMarker(marker) {
    if (!marker) return;
    var targetScale = Math.max(projection.scale(), baseScale * 4);
    flyToPoint([marker.lng, marker.lat], targetScale, 900);
  }

  function addFeaturedRoutes() {
    var arcPairs = Array.isArray(config.arcs) && config.arcs.length ? config.arcs : DEFAULT_FEATURED_ARC_PAIRS;
    var featuredArcs = arcPairs
      .map(function (pair) {
        var from = byslug[pair[0]];
        var to = byslug[pair[1]];
        return from && to ? { from: from, to: to } : null;
      })
      .filter(Boolean);
    if (!featuredArcs.length) return;

    var lines = [];
    featuredArcs.forEach(function (arc) {
      greatCircleLine(arc.from, arc.to, 64).forEach(function (coords) {
        lines.push({ type: "Feature", geometry: { type: "LineString", coordinates: coords }, properties: {} });
      });
    });

    routePaths = routesLayer.selectAll("path")
      .data(lines).enter().append("path")
      .attr("class", "v3datlas-route").attr("d", path)
      .style("opacity", reduceMotion ? 0.45 : 0);

    if (!reduceMotion) {
      routePaths.transition().duration(1200).style("opacity", 0.45);
    }
  }

  // Keyboard navigation
  document.addEventListener("keydown", function (e) {
    if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA") return;
    var handled = true;
    switch (e.key) {
      case "ArrowLeft":
        stopAutoPan();
        var r1 = projection.rotate();
        projection.rotate([r1[0] + 5, r1[1], r1[2]]);
        render();
        scheduleAutoPanResume();
        break;
      case "ArrowRight":
        stopAutoPan();
        var r2 = projection.rotate();
        projection.rotate([r2[0] - 5, r2[1], r2[2]]);
        render();
        scheduleAutoPanResume();
        break;
      case "ArrowUp":
        stopAutoPan();
        var r3 = projection.rotate();
        projection.rotate([r3[0], Math.min(90, r3[1] + 5), r3[2]]);
        render();
        scheduleAutoPanResume();
        break;
      case "ArrowDown":
        stopAutoPan();
        var r4 = projection.rotate();
        projection.rotate([r4[0], Math.max(-90, r4[1] - 5), r4[2]]);
        render();
        scheduleAutoPanResume();
        break;
      case "+": case "=": zoomBy(1.3); break;
      case "-": case "_": zoomBy(1 / 1.3); break;
      default: handled = false;
    }
    if (handled) e.preventDefault();
  });

  // Filter bar wiring
  function applyFilter(region) {
    activeRegionFilter = region;
    if (region === "all") {
      filteredSlugs = null;
    } else {
      filteredSlugs = {};
      markers.forEach(function (m) {
        if (m.region === region) filteredSlugs[m.slug] = true;
      });
    }
    rebuildIndex();
    render();
  }

  var filterBar = root.querySelector("[data-v3datlas-filter-bar]");
  if (filterBar) {
    filterBar.addEventListener("click", function (e) {
      var chip = e.target.closest("[data-filter-value]");
      if (!chip) return;
      filterBar.querySelectorAll("[data-filter-value]").forEach(function (c) { c.classList.remove("is-active"); });
      chip.classList.add("is-active");
      applyFilter(chip.getAttribute("data-filter-value"));
    });
  }

  // Surprise me
  var surpriseBtn = root.querySelector("[data-v3datlas-surprise]");
  if (surpriseBtn) {
    surpriseBtn.addEventListener("click", function () {
      var pool = filteredSlugs ? markers.filter(function (m) { return filteredSlugs[m.slug]; }) : markers;
      if (!pool.length) return;
      var pick = pool[Math.floor(Math.random() * pool.length)];
      flyToPoint([pick.lng, pick.lat], baseScale * 4, 1200);
      setTimeout(function () { openSidebar(pick); }, 600);
    });
  }

  fetch(config.worldDataUrl)
    .then(function (r) { return r.json(); })
    .then(function (topo) {
      var objectName = Object.keys(topo.objects)[0];
      var world = topojson.feature(topo, topo.objects[objectName]);
      worldFeatures = world.features;
      var numericToAlpha2 = window.V3DA_ISO_NUMERIC_ALPHA2 || {};
      var countryRegionMap = buildCountryRegionMap(markers);

      countryPaths = countriesLayer.selectAll("path")
        .data(world.features).enter().append("path")
        .attr("class", "v3datlas-country")
        .style("cursor", "pointer")
        .each(function (d) {
          // Region-based tinting, keyed off whichever Voyasee destination
          // region this country's own alpha-2 code maps to (see
          // buildCountryRegionMap) -- countries with no destination in
          // them keep the default fill.
          var alpha2 = numericToAlpha2[String(d.id)] || null;
          var region = alpha2 ? countryRegionMap[alpha2] : null;
          var tint = region ? REGION_TINTS[region] : null;
          if (tint) d3.select(this).style("fill", tint);
        })
        .on("click", function (event, d) {
          var code = numericToAlpha2[String(d.id)] || null;
          var name = (d.properties && d.properties.name) || "";
          if (openCountry) openCountry(code, name);
          var centroid = d3.geoCentroid(d);
          if (centroid && isFinite(centroid[0]) && isFinite(centroid[1])) {
            flyToPoint(centroid, projection.scale(), 900);
          }
        });

      addFeaturedRoutes();
      render();
      scheduleAutoPanResume();

      // Deep-link: open destination from hash
      var hashParams = v3daHash.read();
      if (hashParams && hashParams.dest) {
        var destMarker = byslug[hashParams.dest];
        if (destMarker) {
          setTimeout(function () {
            flyToPoint([destMarker.lng, destMarker.lat], baseScale * 4, 1200);
            setTimeout(function () { openSidebar(destMarker); }, 600);
          }, 500);
        }
      }
    })
    .catch(function () {
      mount.innerHTML = "";
      mount.appendChild(el("p", "v3datlas-map-unavailable", strings.mapUnavailable || ""));
    });

  return { flyToMarker: flyToMarker };
}

document.querySelectorAll("[data-v3datlas-root]").forEach(function (root) {
  if (root.hasAttribute("data-v3datlas-ready")) return;
  root.setAttribute("data-v3datlas-ready", "1");

  var config = {};
  try { config = JSON.parse(root.getAttribute("data-v3datlas-config") || "{}"); } catch (err) { config = {}; }
  var markers = Array.isArray(config.markers) ? config.markers : [];
  var strings = config.strings || {};
  var restBase = config.restBase || "";

  var mapApi = { flyToMarker: function () {} };
  var sidebarApi = initSidebar(root, markers, strings, restBase, function (marker) {
    mapApi.flyToMarker(marker);
  });
  initListSearch(root);
  initDestinationList(root, sidebarApi.openSidebar, sidebarApi.byslug);
  mapApi = initMap(root, markers, config, sidebarApi.openSidebar, sidebarApi.openCountry);
});
