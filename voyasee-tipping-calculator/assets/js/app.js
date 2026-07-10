/* Voyasee Tipping Calculator — front-end engine + infographics.
 * Authored ES5-safe (var, plain functions, string concatenation) so it runs in
 * every desktop, tablet, mobile and in-app browser. The calculation mirrors the
 * PHP engine in includes/class-vtc-calculator.php exactly. */
(function () {
  "use strict";

  var DATA = window.VTC_DATA || null;
  var SVGNS = "http://www.w3.org/2000/svg";
  var QUALITY_INDEX = { poor: 0, standard: 1, great: 2 };
  var CULTURE_POS = { not_customary: 0.06, service_included: 0.35, appreciated: 0.58, baksheesh: 0.72, expected: 0.95 };

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined && text !== null) n.textContent = text;
    return n;
  }
  function svg(tag, attrs) {
    var n = document.createElementNS(SVGNS, tag);
    for (var k in attrs) { if (attrs.hasOwnProperty(k)) n.setAttribute(k, attrs[k]); }
    return n;
  }
  function flagEmoji(code) {
    if (!code || code.length !== 2) return "";
    return String.fromCodePoint(0x1f1e6 + code.toUpperCase().charCodeAt(0) - 65,
                                0x1f1e6 + code.toUpperCase().charCodeAt(1) - 65);
  }

  /* ---- calculation (mirror of PHP) ---- */
  function roundMoney(v, d) { var f = Math.pow(10, d); return Math.round(v * f) / f; }
  function niceLocal(v, d) {
    if (v <= 0) return 0;
    if (v < 1) return d > 0 ? roundMoney(v, Math.min(d, 1)) : 1;
    if (v < 10) return Math.round(v);
    if (v < 100) return Math.round(v / 5) * 5;
    if (v < 1000) return Math.round(v / 10) * 10;
    if (v < 10000) return Math.round(v / 50) * 50;
    return Math.round(v / 100) * 100;
  }
  function roundUpTotal(v, d) {
    if (v <= 0) return 0;
    var step;
    if (v >= 100000) step = 1000;
    else if (v >= 10000) step = 100;
    else if (v >= 1000) step = 50;
    else if (v >= 100) step = 10;
    else if (v >= 20) step = 5;
    else if (v >= 5) step = 1;
    else step = d > 0 ? 0.5 : 1;
    return Math.ceil(v / step) * step;
  }
  function formatAmount(v, d) {
    var parts = (v).toFixed(d).split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
  }
  function currency(code) {
    return DATA.currencies[code] || { symbol: code, decimals: 2, rate: 1, name: code };
  }
  function resolveCountry(code) {
    code = (code || "").toUpperCase();
    var row = DATA.countries[code];
    if (!row) return null;
    var cluster = DATA.clusters[row.cluster];
    var flags = {}, k;
    for (k in cluster.flags) if (cluster.flags.hasOwnProperty(k)) flags[k] = cluster.flags[k];
    if (row.flags) for (k in row.flags) if (row.flags.hasOwnProperty(k)) flags[k] = row.flags[k];
    var services = {};
    for (k in cluster.services) {
      if (!cluster.services.hasOwnProperty(k)) continue;
      services[k] = (row.overrides && row.overrides[k]) ? row.overrides[k] : cluster.services[k];
    }
    return {
      code: code, name: row.name, region: row.region, currency: row.currency,
      cluster: row.cluster, cluster_key: cluster.key, cluster_label: cluster.label,
      verdict: cluster.verdict, confidence: row.confidence, flags: flags,
      services: services, note: row.note || cluster.note
    };
  }

  function messages(country, type, notExpected) {
    var f = country.flags, out = [];
    if (notExpected) { out.push({ type: "verdict", text: country.note }); return out; }
    if (f.service_charge_common && type === "percent")
      out.push({ type: "warning", text: "A service charge is often already added to the bill here. Check first so you do not tip twice." });
    if (f.cash_preferred)
      out.push({ type: "info", text: "Cash is preferred for tips here — it is more likely to reach the staff directly." });
    if (f.tip_pretax_common && type === "percent")
      out.push({ type: "info", text: "Locals usually tip on the pre-tax amount. If your entered total includes sales tax, the tip can be a little lower." });
    if (country.confidence === "medium")
      out.push({ type: "guidance", text: "This is general regional guidance for " + country.name + " — customs can vary locally, so treat it as a helpful starting point." });
    return out;
  }

  function calculate(args) {
    var country = resolveCountry(args.country);
    if (!country) return null;
    var services = DATA.services;
    var skey = args.service;
    if (!services[skey] || !country.services[skey]) skey = "restaurant";
    var service = services[skey];
    var band = country.services[skey];
    var qidx = QUALITY_INDEX[args.quality]; if (qidx === undefined) qidx = 1;

    var amount = Math.max(0, parseFloat(args.amount) || 0);
    var party = Math.max(1, parseInt(args.party, 10) || 1);
    var units = Math.max(1, parseInt(args.units, 10) || 1);
    var roundUp = !!args.round_up;

    var cur = currency(country.currency);
    var d = cur.decimals | 0;
    var notExpected = !!(country.flags.not_customary || country.flags.tipping_offensive);

    var type = service.type;
    var tip = 0, ratePct = null, perUnit = null;
    var bandAmt = { low: 0, standard: 0, high: 0 };

    if (type === "percent") {
      ratePct = band[qidx];
      tip = roundMoney(amount * ratePct / 100, d);
      bandAmt = {
        low: roundMoney(amount * band[0] / 100, d),
        standard: roundMoney(amount * band[1] / 100, d),
        high: roundMoney(amount * band[2] / 100, d)
      };
    } else {
      var rate = cur.rate;
      perUnit = niceLocal(band[qidx] * rate, d);
      tip = roundMoney(perUnit * units, d);
      bandAmt = {
        low: niceLocal(band[0] * rate, d) * units,
        standard: niceLocal(band[1] * rate, d) * units,
        high: niceLocal(band[2] * rate, d) * units
      };
    }

    if (notExpected) { tip = 0; bandAmt = { low: 0, standard: 0, high: 0 }; }

    if (roundUp && type === "percent" && !notExpected && amount > 0) {
      var nt = roundUpTotal(amount + tip, d);
      tip = roundMoney(nt - amount, d);
      if (tip < 0) tip = 0;
    }

    var total = (type === "percent") ? roundMoney(amount + tip, d) : tip;
    var ppTip = roundMoney(tip / party, d);
    var ppTotal = roundMoney(total / party, d);
    var cashTip = (tip > 0) ? roundUpTotal(tip, d) : 0;

    var home = homeConversion(args.home_currency, country.currency, tip, total, type);

    return {
      country: country, service: { key: skey, label: service.label, type: type, unit: service.unit || null },
      currency: { code: country.currency, symbol: cur.symbol, decimals: d },
      input: { amount: amount, quality: args.quality, party: party, units: units, round_up: roundUp },
      result: { tip: tip, total: total, per_person_tip: ppTip, per_person_total: ppTotal,
                cash_tip: cashTip, rate_pct: ratePct, per_unit: perUnit, band: bandAmt, not_expected: notExpected },
      home: home, messages: messages(country, type, notExpected)
    };
  }

  function homeConversion(homeCode, localCode, tip, total, type) {
    homeCode = (homeCode || "").toUpperCase();
    if (!homeCode || homeCode === localCode) return null;
    var home = DATA.currencies[homeCode], local = DATA.currencies[localCode];
    if (!home || !local || local.rate <= 0) return null;
    function conv(v) { var usd = v / local.rate; return roundMoney(usd * home.rate, home.decimals | 0); }
    return { code: homeCode, symbol: home.symbol, decimals: home.decimals | 0,
             tip: conv(tip), total: (type === "percent") ? conv(total) : conv(tip), approx: true };
  }

  function money(sym, v, d) { return sym + formatAmount(v, d); }

  /* ---- gauge ---- */
  function buildGauge(clusterKey, verdict, strings) {
    var wrap = el("div", "vtc-gauge");
    var s = svg("svg", { viewBox: "0 0 200 118" });
    var cx = 100, cy = 100, r = 82;
    function pt(deg, rad) { var a = deg * Math.PI / 180; return [cx + rad * Math.cos(a), cy - rad * Math.sin(a)]; }
    var defs = svg("defs", {});
    var grad = svg("linearGradient", { id: "vtcGaugeGrad", x1: "0", y1: "0", x2: "1", y2: "0" });
    grad.appendChild(svg("stop", { offset: "0%", "stop-color": "#3f6f88" }));
    grad.appendChild(svg("stop", { offset: "60%", "stop-color": "#7d8f6a" }));
    grad.appendChild(svg("stop", { offset: "100%", "stop-color": "#e6b968" }));
    defs.appendChild(grad); s.appendChild(defs);
    var a1 = pt(180, r), a2 = pt(0, r);
    s.appendChild(svg("path", { d: "M " + a1[0] + " " + a1[1] + " A " + r + " " + r + " 0 0 1 " + a2[0] + " " + a2[1],
      fill: "none", stroke: "rgba(255,255,255,0.10)", "stroke-width": "12", "stroke-linecap": "round" }));
    s.appendChild(svg("path", { d: "M " + a1[0] + " " + a1[1] + " A " + r + " " + r + " 0 0 1 " + a2[0] + " " + a2[1],
      fill: "none", stroke: "url(#vtcGaugeGrad)", "stroke-width": "12", "stroke-linecap": "round", opacity: "0.85" }));
    var p = CULTURE_POS[clusterKey]; if (p === undefined) p = 0.5;
    var deg = 180 - p * 180;
    var np = pt(deg, r - 6);
    s.appendChild(svg("line", { x1: cx, y1: cy, x2: np[0], y2: np[1], stroke: "#fff", "stroke-width": "3", "stroke-linecap": "round" }));
    s.appendChild(svg("circle", { cx: cx, cy: cy, r: "6", fill: "#e6b968" }));
    wrap.appendChild(s);
    wrap.appendChild(el("div", "vtc-gauge-verdict", verdict));
    wrap.appendChild(el("div", "vtc-gauge-caption", strings.cultureGaugeLabel || ""));
    return wrap;
  }

  /* ---- result rendering ---- */
  function renderResult(mount, res, strings) {
    mount.innerHTML = "";
    var cur = res.currency, r = res.result, type = res.service.type;
    var card = el("div", "vtc-result-card");

    if (r.not_expected) {
      var no = el("div", "vtc-noTip");
      no.appendChild(el("div", "vtc-noTip-badge", res.country.flags.tipping_offensive ? "🙏" : "✅"));
      no.appendChild(el("div", "vtc-noTip-title", strings.noTipTitle));
      no.appendChild(el("p", "vtc-noTip-text", res.country.note));
      card.appendChild(no);
      card.appendChild(buildGauge(res.country.cluster_key, res.country.verdict, strings));
      appendActions(card, res, strings);
      mount.appendChild(card); return;
    }

    // head
    var head = el("div", "vtc-r-head");
    var hs = el("div", "vtc-r-headings");
    hs.appendChild(el("div", "vtc-r-tiplabel", strings.suggestedTip));
    hs.appendChild(el("div", "vtc-r-tip", money(cur.symbol, r.tip, cur.decimals)));
    if (type === "percent" && r.rate_pct !== null && r.rate_pct > 0)
      hs.appendChild(el("span", "vtc-r-ratepill", r.rate_pct + "% " + res.service.label.toLowerCase()));
    else if (type !== "percent" && r.per_unit !== null)
      hs.appendChild(el("span", "vtc-r-ratepill", money(cur.symbol, r.per_unit, cur.decimals) + " × " + res.input.units + " " + (res.service.unit || "")));
    head.appendChild(hs);

    var tot = el("div", "vtc-r-total");
    tot.appendChild(el("div", "vtc-r-total-label", strings.totalToPay));
    tot.appendChild(el("div", "vtc-r-total-val", money(cur.symbol, r.total, cur.decimals)));
    head.appendChild(tot);
    card.appendChild(head);

    // bill vs tip bar (percent only, with a real bill)
    if (type === "percent" && res.input.amount > 0) {
      var bar = el("div", "vtc-bar");
      var track = el("div", "vtc-bar-track");
      var billPct = r.total > 0 ? (res.input.amount / r.total * 100) : 100;
      var billSeg = el("div", "vtc-bar-bill"); billSeg.style.width = "0%";
      var tipSeg = el("div", "vtc-bar-tip"); tipSeg.style.width = "0%";
      track.appendChild(billSeg); track.appendChild(tipSeg);
      bar.appendChild(track);
      var legend = el("div", "vtc-bar-legend");
      var lb = el("span", "vtc-legend-bill", strings.bill + " " + money(cur.symbol, res.input.amount, cur.decimals));
      var lt = el("span", "vtc-legend-tip", strings.tip + " " + money(cur.symbol, r.tip, cur.decimals));
      legend.appendChild(lb); legend.appendChild(lt);
      bar.appendChild(legend);
      card.appendChild(bar);
      setTimeout(function () { billSeg.style.width = billPct + "%"; tipSeg.style.width = (100 - billPct) + "%"; }, 30);
    }

    // per person
    if (res.input.party > 1) {
      var pp = el("div", "vtc-perperson");
      var av = el("div", "vtc-pp-avatars");
      var n = Math.min(res.input.party, 5);
      for (var i = 0; i < n; i++) av.appendChild(el("span", "vtc-pp-avatar", "👤"));
      pp.appendChild(av);
      var ppt = el("div", "vtc-pp-text");
      ppt.innerHTML = strings.perPerson + ": <strong>" + money(cur.symbol, r.per_person_tip, cur.decimals) + "</strong> " +
        strings.tip.toLowerCase() + (type === "percent" ? (" · <strong>" + money(cur.symbol, r.per_person_total, cur.decimals) + "</strong> " + strings.totalToPay.toLowerCase()) : "");
      pp.appendChild(ppt);
      card.appendChild(pp);
    }

    // easiest cash tip
    if (r.cash_tip && r.cash_tip !== r.tip) {
      var cashLine = el("div", "vtc-cashline");
      cashLine.appendChild(el("span", "vtc-cashline-icon", "💵"));
      var ct = el("span", null, (strings.cashTip || "Easiest cash tip") + ": ");
      ct.appendChild(el("strong", null, money(cur.symbol, r.cash_tip, cur.decimals)));
      cashLine.appendChild(ct);
      card.appendChild(cashLine);
    }

    // meters: gauge + range
    var meters = el("div", "vtc-meters");
    meters.appendChild(buildGauge(res.country.cluster_key, res.country.verdict, strings));
    var range = el("div", "vtc-range");
    range.appendChild(el("div", "vtc-range-label", strings.rangeLabel));
    var rtrack = el("div", "vtc-range-track");
    var marker = el("div", "vtc-range-marker");
    var qpos = { poor: 8, standard: 50, great: 92 }[res.input.quality]; if (qpos === undefined) qpos = 50;
    marker.style.left = qpos + "%";
    rtrack.appendChild(marker);
    range.appendChild(rtrack);
    var scale = el("div", "vtc-range-scale");
    scale.appendChild(el("span", null, strings.low + " " + money(cur.symbol, r.band.low, cur.decimals)));
    scale.appendChild(el("span", null, strings.standard + " " + money(cur.symbol, r.band.standard, cur.decimals)));
    scale.appendChild(el("span", null, strings.high + " " + money(cur.symbol, r.band.high, cur.decimals)));
    range.appendChild(scale);
    meters.appendChild(range);
    card.appendChild(meters);

    // home currency
    if (res.home) {
      var hl = el("div", "vtc-home-line");
      hl.innerHTML = strings.approxIn + " <strong>" + money(res.home.symbol, res.home.tip, res.home.decimals) + " " + res.home.code + "</strong> " + strings.tip.toLowerCase() +
        (type === "percent" ? (" · <strong>" + money(res.home.symbol, res.home.total, res.home.decimals) + " " + res.home.code + "</strong> " + strings.totalToPay.toLowerCase()) : "");
      hl.appendChild(el("span", "vtc-home-approx", strings.approxNote));
      card.appendChild(hl);
    }

    // note + messages
    var note = el("div", "vtc-note");
    note.appendChild(el("span", "vtc-note-icon", "💡"));
    note.appendChild(el("span", null, res.country.note));
    card.appendChild(note);
    for (var m = 0; m < res.messages.length; m++) {
      var msg = res.messages[m];
      var mm = el("div", "vtc-msg vtc-msg-" + msg.type);
      mm.appendChild(el("span", null, msg.type === "warning" ? "⚠️" : (msg.type === "info" ? "ℹ️" : "📍")));
      mm.appendChild(el("span", null, msg.text));
      card.appendChild(mm);
    }

    appendActions(card, res, strings);
    mount.appendChild(card);
  }

  function appendActions(card, res, strings) {
    var actions = el("div", "vtc-actions");
    var share = el("button", "vtc-btn vtc-btn-primary");
    share.type = "button";
    share.innerHTML = "↗ " + strings.share;
    share.setAttribute("data-vtc-share", "1");
    var print = el("button", "vtc-btn");
    print.type = "button";
    print.innerHTML = "🖨 " + strings.print;
    print.onclick = function () { window.print(); };
    actions.appendChild(share); actions.appendChild(print);
    card.appendChild(actions);
  }

  /* ---- one calculator instance ---- */
  function initRoot(root) {
    if (!DATA || root.getAttribute("data-vtc-ready")) return;
    root.setAttribute("data-vtc-ready", "1");
    var config = {};
    try { config = JSON.parse(root.getAttribute("data-vtc-config") || "{}"); } catch (e) { config = {}; }
    var strings = config.strings || {};

    var state = {
      country: config.defaultCountry || "US",
      service: (config.defaultService && DATA.services[config.defaultService]) ? config.defaultService : "restaurant",
      amount: "",
      quality: "standard",
      party: 1,
      units: 1,
      round_up: false,
      home: config.defaultHome || ""
    };

    var q = function (sel) { return root.querySelector(sel); };
    var comboBtn = q("[data-vtc-combo-btn]"),
        comboPop = q("[data-vtc-combo-pop]"),
        comboSearch = q("[data-vtc-combo-search]"),
        comboList = q("[data-vtc-combo-list]"),
        comboFlag = q("[data-vtc-combo-flag]"),
        comboName = q("[data-vtc-combo-name]"),
        servicesWrap = q("[data-vtc-services]"),
        amountField = q("[data-vtc-amount-field]"),
        amountInput = q("[data-vtc-amount]"),
        amountSym = q("[data-vtc-amount-sym]"),
        amountLabel = q("[data-vtc-amount-label]"),
        unitsField = q("[data-vtc-units-field]"),
        unitsLabel = q("[data-vtc-units-label]"),
        unitsVal = q("[data-vtc-units-val]"),
        qualityWrap = q("[data-vtc-quality]"),
        partyVal = q("[data-vtc-party-val]"),
        roundupInput = q("[data-vtc-roundup]"),
        homeSelect = q("[data-vtc-home]"),
        resultMount = q("[data-vtc-result-card]"),
        resultEmpty = q("[data-vtc-result-empty]"),
        detectedWrap = q("[data-vtc-detected]");

    /* offline country auto-detect from the browser timezone (no GPS/API) */
    function detectCountry() {
      try {
        if (!DATA.tz || !window.Intl || !Intl.DateTimeFormat) return null;
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (tz && DATA.tz[tz] && resolveCountry(DATA.tz[tz])) return DATA.tz[tz];
      } catch (e) {}
      return null;
    }
    function showDetected(code) {
      if (!detectedWrap) return;
      var c = resolveCountry(code);
      detectedWrap.innerHTML = "";
      detectedWrap.appendChild(el("span", null, "📍 " + (strings.detected || "Detected") + ": "));
      detectedWrap.appendChild(el("strong", null, flagEmoji(code) + " " + c.name));
      detectedWrap.hidden = false;
    }

    /* country combo */
    var countryList = [];
    for (var code in DATA.countries) {
      if (DATA.countries.hasOwnProperty(code)) countryList.push({ code: code, name: DATA.countries[code].name, region: DATA.countries[code].region });
    }
    countryList.sort(function (a, b) { return a.name < b.name ? -1 : (a.name > b.name ? 1 : 0); });

    function renderComboList(filter) {
      comboList.innerHTML = "";
      filter = (filter || "").toLowerCase();
      var count = 0;
      for (var i = 0; i < countryList.length; i++) {
        var c = countryList[i];
        if (filter && c.name.toLowerCase().indexOf(filter) === -1) continue;
        count++;
        var li = el("li");
        var b = el("button", "vtc-combo-opt" + (c.code === state.country ? " is-active" : ""));
        b.type = "button";
        b.appendChild(el("span", "vtc-combo-opt-flag", flagEmoji(c.code)));
        b.appendChild(el("span", "vtc-combo-opt-name", c.name));
        b.appendChild(el("span", "vtc-combo-opt-tag", c.region));
        (function (code) { b.onclick = function () { if (detectedWrap) detectedWrap.hidden = true; setCountry(code); closeCombo(); }; })(c.code);
        li.appendChild(b);
        comboList.appendChild(li);
      }
      if (!count) comboList.appendChild(el("li", "vtc-combo-empty", "No match"));
    }
    function openCombo() { comboPop.hidden = false; comboBtn.setAttribute("aria-expanded", "true"); comboSearch.value = ""; renderComboList(""); comboSearch.focus(); }
    function closeCombo() { comboPop.hidden = true; comboBtn.setAttribute("aria-expanded", "false"); }
    comboBtn.onclick = function () { comboPop.hidden ? openCombo() : closeCombo(); };
    comboSearch.oninput = function () { renderComboList(comboSearch.value); };
    document.addEventListener("click", function (e) { if (!comboPop.hidden && !root.querySelector("[data-vtc-combo]").contains(e.target)) closeCombo(); });

    function setCountry(code) {
      var c = resolveCountry(code);
      if (!c) return;
      state.country = code;
      comboFlag.textContent = flagEmoji(code);
      comboName.textContent = c.name;
      updateSymbol();
      updateGlance(c);
      recalc();
    }

    /* services */
    function renderServices() {
      servicesWrap.innerHTML = "";
      for (var skey in DATA.services) {
        if (!DATA.services.hasOwnProperty(skey)) continue;
        var meta = DATA.services[skey];
        var b = el("button", "vtc-service-chip" + (skey === state.service ? " is-active" : ""));
        b.type = "button";
        b.appendChild(el("span", "vtc-service-chip-icon", meta.icon || ""));
        b.appendChild(el("span", null, meta.label));
        (function (key) { b.onclick = function () { setService(key); }; })(skey);
        servicesWrap.appendChild(b);
      }
    }
    function setService(skey) {
      state.service = skey;
      var chips = servicesWrap.querySelectorAll(".vtc-service-chip");
      var idx = 0, k;
      for (k in DATA.services) { if (!DATA.services.hasOwnProperty(k)) continue; chips[idx].className = "vtc-service-chip" + (k === skey ? " is-active" : ""); idx++; }
      toggleFields();
      recalc();
    }
    function toggleFields() {
      var meta = DATA.services[state.service];
      if (meta.type === "percent") {
        amountField.hidden = false; unitsField.hidden = true;
      } else {
        amountField.hidden = true; unitsField.hidden = false;
        unitsLabel.textContent = meta.unit === "bag" ? "How many bags?" : "How many nights?";
        unitsVal.textContent = state.units;
      }
    }

    function updateSymbol() {
      var c = resolveCountry(state.country);
      var cur = currency(c.currency);
      amountSym.textContent = cur.symbol;
    }

    /* amount */
    amountInput.oninput = function () {
      var v = amountInput.value.replace(/[^0-9.]/g, "");
      state.amount = v;
      recalc();
    };

    /* units stepper */
    q("[data-vtc-units-inc]").onclick = function () { state.units++; unitsVal.textContent = state.units; recalc(); };
    q("[data-vtc-units-dec]").onclick = function () { if (state.units > 1) { state.units--; unitsVal.textContent = state.units; recalc(); } };

    /* quality */
    qualityWrap.addEventListener("click", function (e) {
      var btn = e.target.closest ? e.target.closest("[data-q]") : null;
      if (!btn) return;
      state.quality = btn.getAttribute("data-q");
      var bs = qualityWrap.querySelectorAll("[data-q]");
      for (var i = 0; i < bs.length; i++) bs[i].className = (bs[i] === btn ? "is-active" : "");
      recalc();
    });

    /* party stepper */
    q("[data-vtc-party-inc]").onclick = function () { state.party++; partyVal.textContent = state.party; recalc(); };
    q("[data-vtc-party-dec]").onclick = function () { if (state.party > 1) { state.party--; partyVal.textContent = state.party; recalc(); } };

    /* options */
    roundupInput.onchange = function () { state.round_up = roundupInput.checked; recalc(); };
    if (homeSelect) { state.home = homeSelect.value || state.home; homeSelect.onchange = function () { state.home = homeSelect.value; recalc(); }; }

    /* glance (client update on country change) */
    function updateGlance(c) {
      var gc = root.querySelector("[data-vtc-glance-country]");
      var gv = root.querySelector("[data-vtc-glance-verdict]");
      var gn = root.querySelector("[data-vtc-glance-note]");
      var grid = root.querySelector("[data-vtc-glance-grid]");
      if (gc) gc.textContent = c.name;
      if (gv) gv.textContent = c.verdict;
      if (gn) gn.textContent = c.note;
      if (grid) {
        grid.innerHTML = "";
        var cur = currency(c.currency);
        for (var skey in DATA.services) {
          if (!DATA.services.hasOwnProperty(skey)) continue;
          var meta = DATA.services[skey];
          var band = c.services[skey];
          var item = el("div", "vtc-glance-item");
          item.appendChild(el("span", "vtc-glance-icon", meta.icon || ""));
          item.appendChild(el("span", "vtc-glance-label", meta.label));
          item.appendChild(el("span", "vtc-glance-value", bandDisplay(band, meta, cur)));
          grid.appendChild(item);
        }
      }
    }
    function bandDisplay(band, meta, cur) {
      var low = band[0], std = band[1], high = band[2];
      if (meta.type === "percent") {
        if (std === 0 && high === 0) return "Not expected";
        if (low === high) return high + "%";
        if (low === 0) return "Round up to ~" + high + "%";
        return low + "-" + high + "%";
      }
      if (std === 0) return "Not expected";
      var amt = niceLocal(std * cur.rate, cur.decimals | 0);
      return "~" + cur.symbol + formatAmount(amt, cur.decimals | 0) + (meta.unit ? " per " + meta.unit : "");
    }

    /* share */
    function buildShareUrl() {
      var base = location.href.split("#")[0];
      var h = "#c=" + state.country + "&s=" + state.service + "&a=" + encodeURIComponent(state.amount) +
        "&q=" + state.quality + "&p=" + state.party + "&u=" + state.units + "&r=" + (state.round_up ? 1 : 0) +
        (state.home ? "&h=" + state.home : "");
      return base + h;
    }
    function doShare(btn) {
      var url = buildShareUrl();
      if (navigator.share) {
        navigator.share({ title: strings.title, url: url }).catch(function () {});
        return;
      }
      var done = function () {
        var old = btn.innerHTML; btn.innerHTML = "✓ " + (strings.copied || "Copied");
        setTimeout(function () { btn.innerHTML = old; }, 1600);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(done, function () { window.prompt("Copy this link", url); });
      } else { window.prompt("Copy this link", url); }
    }
    resultMount.addEventListener("click", function (e) {
      var btn = e.target.closest ? e.target.closest("[data-vtc-share]") : null;
      if (btn) doShare(btn);
    });

    /* recalc + hash */
    function recalc() {
      var meta = DATA.services[state.service];
      var hasInput = meta.type === "percent" ? (parseFloat(state.amount) > 0) : true;
      if (!hasInput) {
        resultEmpty.hidden = false; resultMount.hidden = true; resultMount.innerHTML = "";
        writeHash();
        return;
      }
      var res = calculate({
        country: state.country, service: state.service, amount: state.amount,
        quality: state.quality, party: state.party, units: state.units,
        round_up: state.round_up, home_currency: state.home
      });
      if (!res) return;
      resultEmpty.hidden = true; resultMount.hidden = false;
      renderResult(resultMount, res, strings);
      writeHash();
    }
    function writeHash() {
      if (!window.history || !window.history.replaceState) return;
      try { window.history.replaceState(null, "", buildShareUrl()); } catch (e) {}
    }

    function readHash() {
      var h = location.hash.replace(/^#/, "");
      if (!h) return false;
      var parts = h.split("&"), got = {};
      for (var i = 0; i < parts.length; i++) { var kv = parts[i].split("="); got[kv[0]] = decodeURIComponent(kv[1] || ""); }
      if (got.c && resolveCountry(got.c)) state.country = got.c.toUpperCase();
      if (got.s && DATA.services[got.s]) state.service = got.s;
      if (got.a) { state.amount = got.a; amountInput.value = got.a; }
      if (got.q && QUALITY_INDEX[got.q] !== undefined) state.quality = got.q;
      if (got.p) state.party = Math.max(1, parseInt(got.p, 10) || 1);
      if (got.u) state.units = Math.max(1, parseInt(got.u, 10) || 1);
      if (got.r === "1") state.round_up = true;
      if (got.h && DATA.currencies[got.h]) state.home = got.h.toUpperCase();
      return true;
    }

    /* build UI */
    renderServices();
    var hadHash = readHash();
    // Offline auto-detect: only when no shared link and not a country-preset page.
    if (!hadHash && config.autodetect) {
      var det = detectCountry();
      if (det) { state.country = det; showDetected(det); }
    }
    // reflect state into controls
    setCountry(state.country);
    setService(state.service);
    (function syncQuality() {
      var bs = qualityWrap.querySelectorAll("[data-q]");
      for (var i = 0; i < bs.length; i++) bs[i].className = (bs[i].getAttribute("data-q") === state.quality ? "is-active" : "");
    })();
    partyVal.textContent = state.party;
    unitsVal.textContent = state.units;
    roundupInput.checked = state.round_up;
    if (homeSelect) homeSelect.value = state.home;
    toggleFields();
    recalc();
  }

  function boot() {
    var roots = document.querySelectorAll("[data-vtc-root]");
    for (var i = 0; i < roots.length; i++) initRoot(roots[i]);
    registerPWA(roots[0]);
  }

  /* PWA: register the service worker + wire an install button (opt-in via config.swUrl) */
  function registerPWA(root) {
    if (!root) return;
    var config = {};
    try { config = JSON.parse(root.getAttribute("data-vtc-config") || "{}"); } catch (e) { config = {}; }
    var strings = config.strings || {};
    if (config.swUrl && navigator.serviceWorker) {
      window.addEventListener("load", function () {
        navigator.serviceWorker.register(config.swUrl, { scope: "/" }).catch(function () {});
      });
    }
    var deferred = null;
    window.addEventListener("beforeinstallprompt", function (e) {
      e.preventDefault();
      deferred = e;
      var stats = document.querySelectorAll("[data-vtc-root] .vtc-stats");
      for (var i = 0; i < stats.length; i++) {
        if (stats[i].querySelector(".vtc-install")) continue;
        var b = el("button", "vtc-install", "⤓ " + (strings.install || "Install app"));
        b.type = "button";
        b.onclick = function () {
          if (!deferred) return;
          deferred.prompt();
          deferred.userChoice.then(function () { deferred = null; });
          var btns = document.querySelectorAll(".vtc-install");
          for (var j = 0; j < btns.length; j++) btns[j].style.display = "none";
        };
        stats[i].appendChild(b);
      }
    });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
