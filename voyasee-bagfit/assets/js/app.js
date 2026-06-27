(function() {
  'use strict';

  const CONFIG = window.VSB_CONFIG || {};
  const $ = (root, selector) => root.querySelector(selector);
  const $$ = (root, selector) => Array.from(root.querySelectorAll(selector));
  const esc = (value) => String(value == null ? '' : value)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  const safeUrl = (value) => /^https?:\/\//i.test(String(value || '')) ? String(value) : '#';
  const num = (value, decimals) => {
    const n = Number(value || 0);
    return n.toLocaleString('en-US', {
      maximumFractionDigits: decimals == null ? 1 : decimals
    });
  };
  const debounce = (fn, wait) => {
    let timer;
    return function() {
      const args = arguments;
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(null, args), wait);
    };
  };

  let publicNonce = '';
  let nonceRequest = null;

  async function refreshPublicNonce(force) {
    if (publicNonce && !force) return publicNonce;
    if (nonceRequest && !force) return nonceRequest;

    nonceRequest = fetch((CONFIG.restUrl || '') + 'nonce?vsb_cache=' + Date.now(), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache'
      },
      credentials: 'same-origin',
      cache: 'no-store'
    }).then(async (response) => {
      let data = {};
      try {
        data = await response.json();
      } catch (error) {
        data = {};
      }
      if (!response.ok || !data.nonce) {
        throw new Error((data && data.message) || (CONFIG.strings && CONFIG.strings.networkError) ||
          'The secure session could not be started.');
      }
      publicNonce = String(data.nonce);
      CONFIG.nonce = publicNonce;
      return publicNonce;
    }).finally(() => {
      nonceRequest = null;
    });

    return nonceRequest;
  }

  async function request(path, options, retried) {
    const supplied = options || {};
    const method = String(supplied.method || 'GET').toUpperCase();
    const needsNonce = !['GET', 'HEAD', 'OPTIONS'].includes(method);

    if (needsNonce) await refreshPublicNonce(false);

    const headers = Object.assign({
      'Accept': 'application/json'
    }, supplied.headers || {});
    if (supplied.body != null && !headers['Content-Type']) headers['Content-Type'] = 'application/json';
    if (needsNonce) headers['X-VSB-Nonce'] = publicNonce || CONFIG.nonce || '';

    const fetchOptions = Object.assign({}, supplied, {
      method: method,
      headers: headers,
      credentials: 'same-origin',
      cache: method === 'GET' ? 'no-store' : 'no-cache'
    });

    const response = await fetch((CONFIG.restUrl || '') + path, fetchOptions);
    let data = {};
    try {
      data = await response.json();
    } catch (error) {
      data = {};
    }

    if (!response.ok && needsNonce && !retried && data && data.code === 'vsb_nonce') {
      await refreshPublicNonce(true);
      return request(path, options, true);
    }

    if (!response.ok) {
      throw new Error((data && data.message) || (CONFIG.strings && CONFIG.strings.networkError) ||
        'Request failed.');
    }
    return data;
  }

  function init(root) {
    const state = {
      mode: root.dataset.mode || 'quick',
      step: 1,
      airlines: [],
      airlineMap: {},
      bags: 0,
      flights: 0,
      busy: false,
      lastPayload: null,
      lastResult: null,
      reportUrl: '',
      selectedShared: new Set(),
      airportTimer: null
    };

    const live = $(root, '[data-vsb-live]');
    const bagsWrap = $(root, '[data-vsb-bags]');
    const bagTemplate = $(root, '[data-vsb-bag-template]');
    const flightsWrap = $(root, '[data-vsb-flights]');
    const flightTemplate = $(root, '[data-vsb-flight-template]');
    const airportList = $(root, '[data-vsb-airport-list]');
    const resultBox = $(root, '[data-vsb-result]');
    const loading = $(root, '[data-vsb-loading]');
    const actions = $(root, '[data-vsb-actions]');
    const shareOutput = $(root, '[data-vsb-share-output]');
    const reverseBagWrap = $(root, '[data-reverse-bag]');

    function announce(message, type) {
      if (!live) return;
      live.textContent = message;
      live.className = 'vsb7-live is-' + (type || 'info');
      window.setTimeout(() => {
        if (live.textContent === message) live.className = 'vsb7-live';
      }, 6000);
    }

    function setBusy(value) {
      state.busy = !!value;
      root.classList.toggle('is-busy', state.busy);
      $$(root, 'button').forEach((button) => {
        button.disabled = state.busy;
      });
      if (loading) loading.hidden = !state.busy;
    }

    function updateMode() {
      root.dataset.mode = state.mode;
      $$(root, '[data-vsb-mode]').forEach((button) => {
        const active = button.dataset.vsbMode === state.mode;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      const planner = $(root, '[data-vsb-planner]');
      const reverse = $(root, '[data-vsb-reverse]');
      const shared = $(root, '[data-vsb-shared]');
      planner.hidden = !['quick', 'full'].includes(state.mode);
      reverse.hidden = state.mode !== 'reverse';
      shared.hidden = state.mode !== 'shared';
      const summary = $(root, '[data-vsb-summary]');
      if (summary) summary.hidden = !['quick', 'full'].includes(state.mode);
      if (state.mode === 'quick') {
        while ($$(bagsWrap, '[data-vsb-bag]').length > 1) $$(bagsWrap, '[data-vsb-bag]').pop().remove();
        while ($$(flightsWrap, '[data-vsb-flight]').length > 1) $$(flightsWrap, '[data-vsb-flight]').pop().remove();
        renumberBags();
        renumberFlights();
        refreshAllAllowances();
      }
      if (state.mode === 'reverse' && !$(reverseBagWrap, '[data-vsb-bag]')) addReverseBag();
      if (state.mode === 'shared') renderSharedPicks('');
    }

    function goStep(number, scroll) {
      state.step = number;
      $$(root, '[data-vsb-step]').forEach((section) => {
        section.hidden = Number(section.dataset.vsbStep) !== number;
      });
      $$(root, '[data-vsb-step-nav]').forEach((button) => {
        const n = Number(button.dataset.vsbStepNav);
        button.classList.toggle('is-active', n === number);
        button.classList.toggle('is-complete', n < number);
        button.setAttribute('aria-current', n === number ? 'step' : 'false');
      });
      root.classList.toggle('has-started', number > 1);
      if (scroll !== false) root.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }

    function airlineOptions(selected, operating) {
      const first = operating ? 'Same as marketing airline / unknown' : 'Choose an airline';
      return '<option value="">' + esc(first) + '</option>' + state.airlines.map((airline) => {
        const label = (airline.iata ? airline.iata + ' · ' : '') + airline.name + (airline.country ? ' — ' +
          airline.country : '');
        return '<option value="' + esc(airline.slug) + '"' + (airline.slug === selected ? ' selected' : '') +
          '>' + esc(label) + '</option>';
      }).join('');
    }

    function effectiveAirline(card) {
      const marketing = $(card, '[data-flight="airline"]');
      const operating = $(card, '[data-flight="operating"]');
      return state.airlineMap[(operating && operating.value) || (marketing && marketing.value) || ''] || null;
    }

    function bagTypeLabel(type) {
      return {
        personal: 'Personal item',
        cabin: 'Carry-on bag',
        checked: 'Checked bag'
      } [type] || 'Bag';
    }

    function selectedBagTypes() {
      return Array.from(new Set($$(bagsWrap, '[data-vsb-bag]').map((card) => card.dataset.type || 'cabin')));
    }

    function addBag(seed) {
      if ($$(bagsWrap, '[data-vsb-bag]').length >= (CONFIG.limits && CONFIG.limits.bags || 6)) {
        announce('Up to six bags can be checked in one trip.', 'error');
        return;
      }
      const fragment = bagTemplate.content.cloneNode(true);
      bagsWrap.appendChild(fragment);
      const card = bagsWrap.lastElementChild;
      card.dataset.type = seed && seed.type || 'cabin';
      const defaults = Object.assign({
        name: 'My bag',
        owner: 'Traveller 1',
        dimension_unit: preferredDimensionUnit(),
        weight_unit: preferredWeightUnit(),
        wheels_included: true
      }, seed || {});
      Object.keys(defaults).forEach((key) => {
        const field = $(card, '[data-bag-field="' + key + '"]');
        if (!field) return;
        if (field.type === 'checkbox') field.checked = !!defaults[key];
        else field.value = defaults[key];
      });
      $$(card, '[data-bag-type]').forEach((button) => {
        button.classList.toggle('is-selected', button.dataset.bagType === card.dataset.type);
        button.addEventListener('click', () => {
          card.dataset.type = button.dataset.bagType;
          $$(card, '[data-bag-type]').forEach((item) => item.classList.toggle('is-selected', item ===
            button));
          refreshBagCard(card);
          refreshAllAllowances();
          updateSummary();
        });
      });
      const preset = $(card, '[data-bag-preset]');
      if (preset) preset.addEventListener('change', () => {
        applyBagPreset(card, preset.value);
        preset.value = '';
      });
      $$(card, 'input,select').forEach((field) => {
        if (field === preset) return;
        field.addEventListener('input', () => {
          refreshBagCard(card);
          updateSummary();
          saveBagsToStorage();
        });
      });
      $(card, '[data-bag-remove]').addEventListener('click', () => {
        if ($$(bagsWrap, '[data-vsb-bag]').length <= 1) return;
        card.remove();
        renumberBags();
        refreshAllAllowances();
        updateSummary();
        saveBagsToStorage();
      });
      refreshBagCard(card);
      renumberBags();
      refreshTravellerOptions();
      refreshAllAllowances();
      updateSummary();
    }

    function addReverseBag() {
      const fragment = bagTemplate.content.cloneNode(true);
      reverseBagWrap.appendChild(fragment);
      const card = reverseBagWrap.lastElementChild;
      card.dataset.type = 'personal';
      $(card, '[data-bag-remove]').remove();
      $$(card, '[data-full-only]').forEach((el) => el.remove());
      const title = $(card, '[data-bag-title]');
      if (title) title.textContent = 'Bag to compare';
      const number = $(card, '[data-bag-number]');
      if (number) number.textContent = 'YOUR BAG';
      $$(card, '[data-bag-type]').forEach((button) => {
        if (button.dataset.bagType === 'checked') button.remove();
        button.classList.toggle('is-selected', button.dataset.bagType === 'personal');
        button.addEventListener('click', () => {
          card.dataset.type = button.dataset.bagType;
          const reverseType = $(root, '[data-reverse="type"]');
          if (reverseType) reverseType.value = card.dataset.type;
          $$(card, '[data-bag-type]').forEach((item) => item.classList.toggle('is-selected', item ===
            button));
          refreshBagCard(card);
        });
      });
      const defaults = {
        name: 'My bag',
        dimension_unit: preferredDimensionUnit(),
        weight_unit: preferredWeightUnit(),
        wheels_included: true
      };
      Object.keys(defaults).forEach((key) => {
        const field = $(card, '[data-bag-field="' + key + '"]');
        if (field) field.type === 'checkbox' ? field.checked = defaults[key] : field.value = defaults[key];
      });
      $$(card, 'input,select').forEach((field) => field.addEventListener('input', () => refreshBagCard(card)));
      refreshBagCard(card);
    }

    function applyBagPreset(card, value) {
      if (!value) return;
      const parts = value.split(',');
      if (parts.length < 4) return;
      const l = parseFloat(parts[0]), w = parseFloat(parts[1]), h = parseFloat(parts[2]), unit = parts[3];
      const setField = (name, val) => {
        const el = $(card, '[data-bag-field="' + name + '"]');
        if (el) el.value = val;
      };
      setField('length', l);
      setField('width', w);
      setField('height', h);
      setField('dimension_unit', unit);
      refreshBagCard(card);
      updateSummary();
      saveBagsToStorage();
    }

    function preferredDimensionUnit() {
      const forced = root.dataset.units;
      if (forced === 'imperial') return 'in';
      if (forced === 'metric') return 'cm';
      return /^en-US/i.test(navigator.language || '') ? 'in' : 'cm';
    }

    function preferredWeightUnit() {
      return preferredDimensionUnit() === 'in' ? 'lb' : 'kg';
    }

    function refreshBagCard(card) {
      const get = (name) => $(card, '[data-bag-field="' + name + '"]');
      const dUnit = get('dimension_unit') ? get('dimension_unit').value : 'cm';
      const wUnit = get('weight_unit') ? get('weight_unit').value : 'kg';
      $$(card, '[data-d-unit]').forEach((el) => el.textContent = dUnit);
      $$(card, '[data-w-unit]').forEach((el) => el.textContent = wUnit);
      const vals = ['length', 'width', 'height'].map((key) => Number(get(key) && get(key).value || 0));
      const cm = vals.map((v) => dUnit === 'in' ? v * 2.54 : v);
      const linear = cm.reduce((sum, value) => sum + value, 0);
      const volume = cm.every((v) => v > 0) ? cm[0] * cm[1] * cm[2] / 1000 : 0;
      const weight = Number(get('weight') && get('weight').value || 0);
      const name = get('name') && get('name').value.trim() || bagTypeLabel(card.dataset.type);
      const title = $(card, '[data-bag-title]');
      if (title) title.textContent = name;
      const h = $(card, '[data-preview-height]');
      if (h) h.textContent = vals[0] ? num(vals[0]) + ' ' + dUnit : '—';
      const w = $(card, '[data-preview-width]');
      if (w) w.textContent = vals[1] ? num(vals[1]) + ' × ' + num(vals[2]) + ' ' + dUnit : '—';
      const l = $(card, '[data-preview-linear]');
      if (l) l.textContent = linear ? (dUnit === 'in' ? num(linear / 2.54) + ' in' : num(linear) + ' cm') : '—';
      const v = $(card, '[data-preview-volume]');
      if (v) v.textContent = volume ? num(volume) + ' L' : '—';
      const wt = $(card, '[data-preview-weight]');
      if (wt) wt.textContent = weight ? num(weight) + ' ' + wUnit : '—';
    }

    function renumberBags() {
      $$(bagsWrap, '[data-vsb-bag]').forEach((card, index) => {
        card.dataset.index = String(index);
        const number = $(card, '[data-bag-number]');
        if (number) number.textContent = 'BAG ' + (index + 1);
        const remove = $(card, '[data-bag-remove]');
        if (remove) remove.hidden = state.mode === 'quick' || index === 0 && $$(bagsWrap, '[data-vsb-bag]')
          .length === 1;
      });
      state.bags = $$(bagsWrap, '[data-vsb-bag]').length;
    }

    function refreshTravellerOptions() {
      const travellers = Number($(root, '[data-journey="travellers"]') && $(root, '[data-journey="travellers"]')
        .value || 1);
      $$(bagsWrap, '[data-bag-field="owner"]').forEach((select) => {
        const old = select.value;
        select.innerHTML = Array.from({
          length: Math.max(1, travellers)
        }, (_, i) => '<option value="Traveller ' + (i + 1) + '">Traveller ' + (i + 1) + '</option>').join('');
        if ([...select.options].some((o) => o.value === old)) select.value = old;
      });
    }

    function collectBag(card) {
      const value = (name) => {
        const field = $(card, '[data-bag-field="' + name + '"]');
        return field ? field.value : '';
      };
      const checked = (name) => {
        const field = $(card, '[data-bag-field="' + name + '"]');
        return !!(field && field.checked);
      };
      return {
        id: 'bag-' + (Number(card.dataset.index || 0) + 1),
        name: value('name') || bagTypeLabel(card.dataset.type),
        owner: value('owner') || 'Traveller 1',
        type: card.dataset.type || 'cabin',
        length: value('length'),
        width: value('width'),
        height: value('height'),
        weight: value('weight'),
        dimension_unit: value('dimension_unit') || 'cm',
        weight_unit: value('weight_unit') || 'kg',
        wheels_included: checked('wheels_included'),
        soft_sided: checked('soft_sided'),
        expandable: checked('expandable')
      };
    }

    function validateBag(bag) {
      return Number(bag.length) > 0 && Number(bag.width) > 0 && Number(bag.height) > 0 && Number(bag.weight) >= 0;
    }

    function addFlight(seed) {
      if ($$(flightsWrap, '[data-vsb-flight]').length >= (CONFIG.limits && CONFIG.limits.flights || 10)) {
        announce('Up to ten flights can be compared.', 'error');
        return;
      }
      const fragment = flightTemplate.content.cloneNode(true);
      flightsWrap.appendChild(fragment);
      const card = flightsWrap.lastElementChild;
      const marketing = $(card, '[data-flight="airline"]');
      const operating = $(card, '[data-flight="operating"]');
      marketing.innerHTML = airlineOptions(seed && seed.airline_slug || root.dataset.initialAirline || '', false);
      operating.innerHTML = airlineOptions(seed && seed.operating_airline_slug || '', true);
      if (seed) {
        const map = {
          origin: 'origin',
          destination: 'destination',
          date: 'date',
          cabin_class: 'cabin',
          codeshare_unknown: 'codeshare',
          ticket_allowance_text: 'ticket-text',
          ticket_weight_kg: 'ticket-weight',
          ticket_linear_cm: 'ticket-linear',
          ticket_pieces: 'ticket-pieces'
        };
        Object.keys(map).forEach((key) => {
          const field = $(card, '[data-flight="' + map[key] + '"]');
          if (field && seed[key] != null) field.type === 'checkbox' ? field.checked = !!seed[key] : field.value =
            seed[key];
        });
      }
      [marketing, operating].forEach((select) => select.addEventListener('change', () => {
        refreshFlight(card);
        updateSummary();
      }));
      const parse = $(card, '[data-parse-ticket]');
      if (parse) parse.addEventListener('click', () => parseTicket(card));
      $(card, '[data-flight-remove]').addEventListener('click', () => {
        if ($$(flightsWrap, '[data-vsb-flight]').length <= 1) return;
        card.remove();
        renumberFlights();
        updateSummary();
      });
      $$(card, '[data-flight="origin"],[data-flight="destination"]').forEach((input) => input.addEventListener(
        'input', debounce(() => searchAirports(input.value), 250)));
      refreshFlight(card);
      renumberFlights();
      updateSummary();
    }

    function renumberFlights() {
      $$(flightsWrap, '[data-vsb-flight]').forEach((card, index) => {
        card.dataset.index = String(index);
        const number = $(card, '[data-flight-number]');
        if (number) number.textContent = 'FLIGHT ' + (index + 1);
        const remove = $(card, '[data-flight-remove]');
        if (remove) remove.hidden = state.mode === 'quick' || ($$(flightsWrap, '[data-vsb-flight]').length === 1);
      });
      state.flights = $$(flightsWrap, '[data-vsb-flight]').length;
    }

    function refreshFlight(card) {
      const airline = effectiveAirline(card);
      const title = $(card, '[data-flight-title]');
      if (title) title.textContent = airline ? airline.name : 'Choose an airline';
      const source = $(card, '[data-flight-source]');
      if (source) source.innerHTML = airline ? '<a href="' + esc(safeUrl(airline.source_url)) +
        '" target="_blank" rel="noopener noreferrer">Open official baggage policy ↗</a>' + (airline.last_verified ?
          '<small>Checked ' + esc(airline.last_verified) + '</small>' : '') :
        'Choose an airline to view the official source.';
      const coverage = $(card, '[data-flight-coverage]');
      if (coverage) coverage.textContent = !airline ? '—' : airline.directory_only ? 'LINK ONLY' : airline
        .coverage_tier === 'deep_verified' ? 'DEEP RULE SET' : 'CORE RULE SET';
      renderAllowances(card);
    }

    function renderAllowances(card) {
      const wrap = $(card, '[data-flight-allowances]');
      const airline = effectiveAirline(card);
      const old = {};
      $$(wrap, 'select[data-allowance-type]').forEach((select) => old[select.dataset.allowanceType] = select.value);
      wrap.innerHTML = selectedBagTypes().map((type) => {
        const options = airline && airline.allowance_options && airline.allowance_options[type] || [];
        const choices = options.length ? options.map((option) => '<option value="' + esc(option.id) + '">' + esc(
            option.label) + '</option>').join('') :
          '<option value="official-confirmation">Official confirmation required</option>';
        return '<label><span>' + esc(bagTypeLabel(type)) + ' allowance</span><select data-allowance-type="' + esc(
          type) + '">' + choices + '</select></label>';
      }).join('');
      Object.keys(old).forEach((type) => {
        const select = $(wrap, '[data-allowance-type="' + type + '"]');
        if (select && [...select.options].some((o) => o.value === old[type])) select.value = old[type];
      });
      const checked = selectedBagTypes().includes('checked');
      const parser = $(card, '[data-ticket-parser]');
      if (parser) parser.hidden = !checked;
    }

    function refreshAllAllowances() {
      $$(flightsWrap, '[data-vsb-flight]').forEach(renderAllowances);
    }

    async function parseTicket(card) {
      const input = $(card, '[data-flight="ticket-text"]');
      const line = input && input.value.trim();
      if (!line) {
        announce('Paste the baggage allowance line first.', 'error');
        return;
      }
      try {
        const data = await request('parse-ticket', {
          method: 'POST',
          body: JSON.stringify({
            line: line
          })
        });
        const parsed = data.result || {};
        if (!parsed.recognized) {
          announce('No common baggage format was recognized. Enter the values manually.', 'error');
          return;
        }
        const weight = $(card, '[data-flight="ticket-weight"]');
        const linear = $(card, '[data-flight="ticket-linear"]');
        const pieces = $(card, '[data-flight="ticket-pieces"]');
        if (weight && parsed.weight_kg != null) weight.value = parsed.weight_kg;
        if (linear && parsed.linear_cm != null) linear.value = parsed.linear_cm;
        if (pieces && parsed.pieces != null) pieces.value = parsed.pieces;
        if (parsed.no_bag) {
          if (pieces) pieces.value = 0;
        }
        announce('Allowance read. Review the values before checking.', 'success');
      } catch (error) {
        announce(error.message, 'error');
      }
    }

    async function searchAirports(term) {
      const q = String(term || '').trim();
      if (q.length < 2) return;
      try {
        const data = await request('airports?search=' + encodeURIComponent(q) + '&limit=30', {
          method: 'GET',
          headers: {
            'X-VSB-Nonce': CONFIG.nonce || ''
          }
        });
        airportList.innerHTML = (data.airports || []).map((airport) => '<option value="' + esc((airport.iata ?
            airport.iata + ' · ' : '') + airport.name + ' · ' + airport.city + ' · ' + airport.country) +
          '"></option>').join('');
      } catch (error) {
        /* Airport search is an optional convenience. */
      }
    }

    function collectFlight(card) {
      const value = (name) => {
        const field = $(card, '[data-flight="' + name + '"]');
        return field ? field.value : '';
      };
      const allowances = {};
      $$(card, '[data-allowance-type]').forEach((select) => allowances[select.dataset.allowanceType] = select.value);
      return {
        airline_slug: value('airline'),
        operating_airline_slug: value('operating'),
        origin: value('origin'),
        destination: value('destination'),
        date: value('date'),
        cabin_class: value('cabin') || 'unknown',
        fare_class: value('fare_class') || 'unknown',
        allowances: allowances,
        allowance_option: allowances[selectedBagTypes()[0]] || 'unknown',
        codeshare_unknown: !!($(card, '[data-flight="codeshare"]') && $(card, '[data-flight="codeshare"]').checked),
        ticket_allowance_text: value('ticket-text'),
        ticket_weight_kg: value('ticket-weight'),
        ticket_linear_cm: value('ticket-linear'),
        ticket_pieces: value('ticket-pieces')
      };
    }

    function journeyPayload() {
      const value = (name, fallback) => {
        const field = $(root, '[data-journey="' + name + '"]');
        return field ? (field.type === 'checkbox' ? field.checked : field.value) : fallback;
      };
      return {
        ticket_type: value('ticket_type', 'one_ticket'),
        checked_through: value('checked_through', 'unknown'),
        self_transfer: value('self_transfer', false),
        travellers: Number(value('travellers', 1)) || 1
      };
    }

    function buildPayload() {
      return {
        mode: state.mode,
        bags: $$(bagsWrap, '[data-vsb-bag]').map(collectBag),
        flights: $$(flightsWrap, '[data-vsb-flight]').map(collectFlight),
        journey: journeyPayload(),
        page_url: CONFIG.pageUrl || window.location.href
      };
    }

    function validatePlanner(step) {
      if (step === 1) {
        const bags = $$(bagsWrap, '[data-vsb-bag]').map(collectBag);
        if (!bags.length || bags.some((bag) => !validateBag(bag))) {
          announce('Enter all three dimensions for every bag.', 'error');
          return false;
        }
      }
      if (step === 2) {
        const flights = $$(flightsWrap, '[data-vsb-flight]').map(collectFlight);
        if (!flights.length || flights.some((flight) => !flight.airline_slug)) {
          announce('Choose an airline for every flight.', 'error');
          return false;
        }
        if (selectedBagTypes().includes('checked')) {
          const missing = flights.some((flight) => !(
            Number(flight.ticket_weight_kg) > 0 ||
            Number(flight.ticket_linear_cm) > 0 ||
            Number(flight.ticket_pieces) === 0 ||
            (flight.ticket_allowance_text && flight.ticket_allowance_text.trim().length > 0)
          ));
          if (missing) {
            announce('For checked baggage, enter the allowance shown in the booking.', 'error');
            return false;
          }
        }
      }
      return true;
    }

    async function runCheck() {
      if (!validatePlanner(1) || !validatePlanner(2)) return;
      const payload = buildPayload();
      state.lastPayload = payload;
      setBusy(true);
      goStep(3);
      try {
        const data = await request('check', {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        state.lastResult = data.result;
        renderDecision(data.result);
        if (actions) actions.hidden = false;
        const ad = $(root, '[data-vsb-result-ad]');
        if (ad) ad.hidden = false;
      } catch (error) {
        resultBox.innerHTML = '<div class="vsb7-error"><b>We could not complete this check.</b><p>' + esc(error
          .message) + '</p></div>';
        announce(error.message, 'error');
      } finally {
        setBusy(false);
      }
    }

    function verdictInfo(code) {
      return {
        FITS: ['pass', 'Your baggage fits the selected rules',
          'Every measured bag is within the stored physical limits.'
        ],
        FITS_WITH_CONDITIONS: ['warn', 'Your baggage fits, but confirm one detail',
          'The physical comparison passes, but a booking or source detail still needs confirmation.'
        ],
        DOES_NOT_FIT: ['fail', 'One or more bags exceed a limit',
          'See the exact bag, flight and measurement that creates the problem.'
        ],
        NOT_INCLUDED: ['warn', 'A selected allowance does not include this bag',
          'The bag may fit physically, but the chosen fare or allowance does not include it.'
        ],
        CHECK_REQUIRED: ['check', 'More booking information is required',
          'BagFit needs a numerical allowance or operating-airline detail before making a pass claim.'
        ]
      } [code] || ['check', 'Review required', 'Confirm the result with the airline.'];
    }

    function statusLabel(status, type) {
      if (type === 'booking') return status === 'included' ? ['pass', 'Included'] : status === 'not_included' ? [
        'fail', 'Not included'
      ] : ['warn', 'Confirm'];
      return status === 'pass' ? ['pass', 'Within limit'] : status === 'fail' ? ['fail', 'Over limit'] : ['warn',
        'Not published'
      ];
    }

    function limitText(rule) {
      if (!rule) return 'No numerical limit stored';
      if (Array.isArray(rule.dimensions_mm) && rule.dimensions_mm.length === 3) return rule.dimensions_mm.map((v) =>
        num(v / 10)).join(' × ') + ' cm';
      if (rule.max_linear_mm) return num(rule.max_linear_mm / 10) + ' cm total';
      return 'Size confirmation required';
    }

    function weightText(rule) {
      if (!rule) return 'Not universally published';
      if (rule.max_weight_g) return num(rule.max_weight_g / 1000) + ' kg';
      if (rule.combined_weight_g) return num(rule.combined_weight_g / 1000) + ' kg combined';
      return 'Not universally published';
    }

    function failureText(failed, dimUnit, weightUnit) {
      if (!failed) return '';
      const dUnit = dimUnit || 'cm';
      const wUnit = weightUnit || 'kg';
      if (failed.type === 'weight' || failed.type === 'combined_weight') {
        if (wUnit === 'lb') return num(failed.over_by_g / 453.592) + ' lb over';
        return num(failed.over_by_g / 1000) + ' kg over';
      }
      if (failed.type === 'linear_dimension') {
        if (dUnit === 'in') return num(failed.over_by_mm / 25.4) + ' in over total-size limit';
        return num(failed.over_by_mm / 10) + ' cm over total-size limit';
      }
      if (failed.type === 'dimension') {
        if (dUnit === 'in') return num(failed.over_by_mm / 25.4) + ' in over on one side';
        return num(failed.over_by_mm / 10) + ' cm over on one side';
      }
      if (failed.type === 'pieces') return 'Too many pieces';
      return 'Limit exceeded';
    }

    function confidenceBadge(tier) {
      const map = {
        deep_verified: {label: 'Deep verified', cls: 'high', title: 'Checked directly against this airline\u2019s current official page.'},
        core_source_linked: {label: 'Source linked', cls: 'medium', title: 'Sourced from the airline, not yet individually re-confirmed. Worth a quick double-check.'},
        directory: {label: 'Needs confirmation', cls: 'low', title: 'No verified number stored yet for this airline. Confirm directly with the airline.'},
        discontinued: {label: 'No longer operating', cls: 'low', title: 'This airline has ceased operations.'}
      };
      const info = map[tier] || map.directory;
      return '<span class="vsb7-confidence is-' + info.cls + '" title="' + esc(info.title) + '">' + esc(info.label) +
        '</span>';
    }

    function renderDecision(result) {
      const info = verdictInfo(result.overall_verdict);
      const bags = result.bag_results || [];
      const strictBag = bags.find((item) => item.bag && item.bag.name === result.strictest_bag) || bags[0] || {};
      const strictLeg = (strictBag.legs || []).find((leg) => Number(leg.leg_number) === Number(result
        .strictest_leg_number)) || (strictBag.legs || [])[0] || {};
      const failed = (strictLeg.failed_checks || [])[0];
      const bag = strictBag.bag || result.bag || {};
      const bagDims = (bag.dimensions_mm || []).map((v) => num(v / 10)).join(' × ') + ' cm';
      const practical = (strictBag.recommendations || result.recommendations || [])[0] || result.strictest_reason ||
        'Confirm the official policy before departure.';

      const bagCards = bags.map((bagResult) => renderBagResult(bagResult)).join('');
      const matrix = renderFlightMatrix(result.flight_matrix || []);
      const notices = (result.public_notices || []).slice(0, 8);
      const sourceRows = uniqueSources(bags).map((source) => '<a href="' + esc(safeUrl(source.url)) +
          '" target="_blank" rel="noopener noreferrer"><span><b>' + esc(source.name) + '</b><small>' + (source
            .checked ? 'Checked ' + esc(source.checked) : 'Official airline source') + '</small></span><i>↗</i></a>')
        .join('');

      resultBox.innerHTML =
        '<div class="vsb7-verdict is-' + info[0] + '">' +
        '<div class="vsb7-verdict-icon">' + ({
          pass: '✓',
          warn: '!',
          fail: '×',
          check: '?'
        })[info[0]] + '</div>' +
        '<div><span>YOUR BAGGAGE RESULT</span><h4>' + esc(info[1]) + '</h4><p>' + esc(info[2]) + '</p></div>' +
        '<dl><div><dt>Limiting bag</dt><dd>' + esc(result.strictest_bag || bag.name || '—') +
        '</dd></div><div><dt>Strictest flight</dt><dd>' + esc(result.strictest_airline || '—') + ' ' +
        confidenceBadge(strictLeg.airline && strictLeg.airline.coverage_tier) +
        '</dd></div><div><dt>Route leg</dt><dd>Flight ' + esc(result.strictest_leg_number || 1) + '</dd></div></dl>' +
        '</div>' +
        '<div class="vsb7-focus-grid">' +
        '<div class="vsb7-bag-compare"><div class="vsb7-bag-outline"><i></i><b>' + esc(bagDims) + '</b><small>' + num(
          (bag.weight_g || 0) / 1000) + ' kg</small></div><div><span>YOUR BAG VS STRICTEST RULE</span><h5>' + esc(
          failureText(failed, bag.dimension_unit, bag.weight_unit) || strictLeg.strictest_reason || 'Within the selected limit') + '</h5><p>' + esc(
          practical) + '</p></div></div>' +
        renderStatusTile('Size', strictLeg.size_status, limitText(strictLeg.rule), strictLeg
          .dimension_usage_percent) +
        renderStatusTile('Weight', strictLeg.weight_status, weightText(strictLeg.rule), strictLeg
          .weight_usage_percent) +
        renderStatusTile('Booking', strictLeg.booking_status, strictLeg.allowance_label || 'Selected allowance', null,
          'booking') +
        '</div>' +
        renderMoreDetail(strictLeg, bag.type, info[0]) +
        '<section class="vsb7-next-actions"><div><span>BEST NEXT ACTION</span><h5>' + esc(practical) +
        '</h5></div><div>' + (result.recommendations || []).slice(0, 3).map((rec, i) => '<article><i>' + (i + 1) +
          '</i><p>' + esc(rec) + '</p></article>').join('') + '</div></section>' +
        '<section class="vsb7-bag-results"><div class="vsb7-subhead"><span>EVERY BAG</span><h5>See which bag creates the issue</h5></div>' +
        bagCards + '</section>' +
        matrix +
        '<div class="vsb7-confirm-grid"><section><div class="vsb7-subhead"><span>OFFICIAL CONFIRMATION</span><h5>Open the relevant airline source</h5></div><div class="vsb7-source-list">' +
        sourceRows + '</div></section>' +
        '<section><div class="vsb7-subhead"><span>BEFORE YOU FLY</span><h5>Only the details that still matter</h5></div>' +
        (notices.length ? '<ul>' + notices.map((n) => '<li>' + esc(n) + '</li>').join('') + '</ul>' :
          '<p>No additional warning was generated. Recheck the airline policy near departure.</p>') +
        '</section></div>' +
        renderSmartPlan(result.smart_plan) +
        '<p class="vsb7-disclaimer">' + esc(result.disclaimer || '') + '</p>';
      renderContextLinks(result);
    }

    function renderSmartPlan(plan) {
      if (!plan) return '';
      const whatIf = (plan.what_if || []).slice(0, 4);
      const whatIfHtml = whatIf.length ? '<div class="vsb7-whatif-grid">' + whatIf.map((item) =>
        '<article><span>IF YOU…</span><b>' + esc(item.label) + '</b><p>' + esc(item.outcome) +
        '</p></article>').join('') + '</div>' : '';
      const script = plan.airport_script ? '<div class="vsb7-airport-script"><div><span>SHOW THIS TO AIRPORT OR CHECK-IN STAFF</span><p>“' +
        esc(plan.airport_script) + '”</p></div><button type="button" data-copy-script="' + esc(plan
          .airport_script) + '">⧉ <span>Copy</span></button></div>' : '';
      if (!whatIfHtml && !script) return '';
      return '<section class="vsb7-smart-plan"><div class="vsb7-subhead"><span>SMART FIX PLAN</span><h5>' +
        esc(plan.headline || 'Practical next steps') + '</h5></div>' + whatIfHtml + script + '</section>';
    }

    function renderEnforcementBadge(leg, bagType) {
      if ('checked' === bagType) return ''; // enforcement variability is about cabin/personal gate-checking; checked bags are weighed at the counter as standard procedure everywhere, so this framing doesn't apply
      const enf = leg.enforcement || {};
      const level = enf.level || 'low';
      const labels = {high: 'Strictly enforced', medium: 'Selectively enforced', low: 'Rarely enforced'};
      const icons = {high: '⚠', medium: '◉', low: '◎'};
      return '<div class="vsb7-enforcement is-' + level + '"><i>' + icons[level] + '</i><div><span>GATE ENFORCEMENT</span><b>' +
        esc(labels[level] || 'Unknown') + '</b>' + (enf.note ? '<p>' + esc(enf.note) + '</p>' : '') + '</div></div>';
    }

    function renderFeeEstimate(leg) {
      const fee = leg.fee_estimate;
      if (!fee) return '';
      const failed = leg.failed_checks && leg.failed_checks.length > 0;
      return '<div class="vsb7-fee-estimate' + (failed ? ' is-active' : '') + '"><span>ESTIMATED COST IF THIS BAG IS REJECTED</span><div class="vsb7-fee-grid">' +
        (fee.gate_fee_usd ? '<article><b>~$' + fee.gate_fee_usd + '</b><small>Gate fee</small></article>' : '') +
        (fee.online_checked_usd ? '<article><b>~$' + fee.online_checked_usd + '</b><small>Checked online</small></article>' : '') +
        (fee.gate_checked_usd ? '<article><b>~$' + fee.gate_checked_usd + '</b><small>Checked at counter</small></article>' : '') +
        '</div><p>Fees vary by route, date, fare, and purchase stage. Use the airline booking page for the exact price.</p></div>';
    }

    function renderFareWarning(leg) {
      if (!leg.fare_class_warning) return '';
      return '<div class="vsb7-fare-warning"><i>⚑</i><div><span>FARE CLASS NOTICE</span><p>' + esc(leg.fare_class_warning) + '</p></div></div>';
    }

    function renderMoreDetail(leg, bagType, verdictTone) {
      const body = renderEnforcementBadge(leg, bagType) + renderFeeEstimate(leg) + renderFareWarning(leg);
      if (!body) return '';
      // A clean pass rarely needs this extra context immediately, so it stays one tap away.
      // A fail or a result that still needs confirming benefits from it being open already.
      const openByDefault = verdictTone !== 'pass';
      return '<details class="vsb7-more-detail"' + (openByDefault ? ' open' : '') + '><summary><span>MORE ABOUT THIS RESULT</span><b>Enforcement, fees and fare details</b></summary><div class="vsb7-more-detail-body">' +
        body + '</div></details>';
    }

    function renderStatusTile(title, status, detail, percent, type) {
      const meta = statusLabel(status, type);
      const pct = Math.min(100, Math.max(0, Number(percent || 0)));
      const r = 18;
      const circ = +(2 * Math.PI * r).toFixed(2);
      const offset = +(circ * (1 - pct / 100)).toFixed(2);
      const ringHtml = percent == null ? '' :
        '<div class="vsb7-ring-wrap">' +
        '<svg width="48" height="48" viewBox="0 0 48 48">' +
        '<circle class="ring-track" cx="24" cy="24" r="' + r + '" fill="none" stroke-width="5"/>' +
        '<circle class="ring-fill" cx="24" cy="24" r="' + r + '" fill="none" stroke-width="5"' +
        ' stroke-dasharray="' + circ + '" stroke-dashoffset="' + offset + '"' +
        ' stroke-linecap="round" transform="rotate(-90 24 24)"/>' +
        '<text x="24" y="28" text-anchor="middle" font-size="10" font-weight="900" font-family="inherit">' + num(pct, 0) + '%</text>' +
        '</svg>' +
        '<small>' + num(pct, 0) + '% of<br>selected limit</small></div>';
      return '<article class="vsb7-status-tile is-' + meta[0] + '"><header><span>' + esc(title) + '</span><b>' + esc(meta[1]) + '</b></header><p>' + esc(detail) + '</p>' + ringHtml + '</article>';
    }

    function renderBagResult(item) {
      const info = verdictInfo(item.overall_verdict);
      const strict = (item.legs || []).find((leg) => Number(leg.leg_number) === Number(item.strictest_leg_number)) ||
        (item.legs || [])[0] || {};
      const bag = item.bag || {};
      const dUnit = bag.dimension_unit || 'cm';
      const wUnit = bag.weight_unit || 'kg';
      const dims = (bag.dimensions_mm || []).map((v) => dUnit === 'in' ? num(v / 25.4) : num(v / 10)).join(' × ');
      const weight = wUnit === 'lb' ? num((bag.weight_g || 0) / 453.592) + ' lb' : num((bag.weight_g || 0) / 1000) + ' kg';
      const orient = strict.best_orientation_mm;
      const orientHtml = orient && Array.isArray(orient) ? '<div><b>Best orientation</b><span>' +
        orient.map((v) => dUnit === 'in' ? num(v / 25.4) : num(v / 10)).join(' × ') + ' ' + esc(dUnit) +
        ' <small>(length × width × depth)</small></span></div>' : '';
      return '<details class="vsb7-bag-result is-' + info[0] +
        '"><summary><div class="vsb7-result-bag-icon">▣</div><span><b>' + esc(bag.name || 'Bag') + '</b><small>' +
        esc(bagTypeLabel(bag.type)) + ' · ' + esc(dims) + ' ' + esc(dUnit) + ' · ' + esc(weight) +
        '</small></span><em>' + esc(info[1].replace('Your baggage ', '').replace('One or more bags ', '')) +
        '</em></summary><div class="vsb7-bag-result-body"><div><b>Strictest flight</b><span>Flight ' + esc(item
          .strictest_leg_number) + ' · ' + esc(item.strictest_airline) + '</span></div><div><b>Reason</b><span>' +
        esc(item.strictest_reason || '—') + '</span></div>' + orientHtml + '<div><b>Next step</b><span>' + esc((item.recommendations ||
          [])[0] || 'Confirm the official airline page.') + '</span></div><div><b>Selected rule</b><span>' + esc(
          strict.allowance_label || '—') + '</span></div></div></details>';
    }

    function renderFlightMatrix(matrix) {
      if (!matrix.length) return '';
      const rows = matrix.map((row) => '<details class="vsb7-flight-row"><summary><span><i>✈</i><b>Flight ' + esc(row
        .leg_number) + ' · ' + esc(row.airline || 'Airline') + '</b><small>' + esc(row.route ||
        'Route not supplied') + '</small>' + confidenceBadge(row.coverage_tier) + '</span><em>' + esc(worstCode((row.bags || []).map((b) => b
        .verdict_code))) + '</em></summary><div class="vsb7-flight-bags">' + (row.bags || []).map((bag) =>
        '<article><b>' + esc(bag.bag_name) + '</b><span class="is-' + esc(statusLabel(bag.size_status)[0]) +
        '">Size: ' + esc(statusLabel(bag.size_status)[1]) + '</span><span class="is-' + esc(statusLabel(bag
          .weight_status)[0]) + '">Weight: ' + esc(statusLabel(bag.weight_status)[1]) +
        '</span><span class="is-' + esc(statusLabel(bag.booking_status, 'booking')[0]) + '">Booking: ' + esc(
          statusLabel(bag.booking_status, 'booking')[1]) + '</span></article>').join('') + '</div></details>').join(
        '');
      return '<section class="vsb7-flight-matrix"><div class="vsb7-subhead"><span>EVERY FLIGHT</span><h5>Open a leg to see every bag result</h5></div>' +
        rows + '</section>';
    }

    function worstCode(codes) {
      if (codes.some((c) => /FAIL/.test(c))) return 'Over limit';
      if (codes.includes('NOT_INCLUDED')) return 'Not included';
      if (codes.some((c) => /CHECK|UNKNOWN/.test(c))) return 'Confirm';
      if (codes.some((c) => /CONDITIONAL|STALE/.test(c))) return 'Conditional';
      return 'Fits';
    }

    function uniqueSources(bags) {
      const map = {};
      bags.forEach((bag) => (bag.legs || []).forEach((leg) => {
        const url = leg.airline && leg.airline.source_url;
        if (url) map[url] = {
          url: url,
          name: leg.airline.name,
          checked: leg.airline.last_verified
        };
      }));
      return Object.values(map);
    }

    function renderContextLinks(result) {
      const wrap = $(root, '[data-vsb-context-links]');
      if (!wrap) return;
      const tools = CONFIG.tools || {};
      const cards = [];
      if (tools.packing) cards.push(['packing', 'Packing List', 'Rebuild the load around the airline limit.', tools
        .packing
      ]);
      if ((result.journey && (result.journey.self_transfer || result.journey.ticket_type === 'separate_tickets')) &&
        tools.transit) cards.push(['transit', 'Transit Risk Checker', 'Review recheck and self-transfer risk.', tools
        .transit
      ]);
      else if (tools.medicine) cards.push(['medicine', 'Medicine Checker',
        'Check medicines, batteries and restricted items.', tools.medicine
      ]);
      if (result.overall_verdict !== 'FITS' && tools.budget) cards.push(['budget', 'Trip Budget',
        'Add a possible baggage cost to your plan.', tools.budget
      ]);
      else if (tools.passport) cards.push(['passport', 'Travel Passport', 'Finish the rest of your pre-flight check.',
        tools.passport
      ]);
      wrap.innerHTML =
        '<section class="vsb7-context"><div class="vsb7-subhead"><span>CONTINUE WITH VOYASEE</span><h5>The next useful step for this result</h5></div><div>' +
        cards.slice(0, 3).map((card) => '<a href="' + esc(safeUrl(card[3])) +
          '" target="_blank" rel="noopener"><i>→</i><span><b>' + esc(card[1]) + '</b><small>' + esc(card[2]) +
          '</small></span></a>').join('') + '</div></section>';
    }

    async function saveResult() {
      if (!state.lastPayload) return;
      setBusy(true);
      try {
        const body = Object.assign({}, state.lastPayload, {
          result: state.lastResult || undefined
        });
        const data = await request('save', {
          method: 'POST',
          body: JSON.stringify(body)
        });
        state.reportUrl = data.report_url || '';
        shareOutput.hidden = false;
        shareOutput.innerHTML = '<b>Saved until ' + esc(new Date(data.expires_at).toLocaleDateString()) +
          '</b><input readonly value="' + esc(data.share_url) + '">';
        await navigator.clipboard.writeText(data.share_url);
        announce(CONFIG.strings && CONFIG.strings.copied || 'Copied to clipboard.', 'success');
      } catch (error) {
        announce(error.message, 'error');
      } finally {
        setBusy(false);
      }
    }

    function saveBagsToStorage() {
      try {
        const bags = $$(bagsWrap, '[data-vsb-bag]').map(collectBag);
        localStorage.setItem('vsb_saved_bags', JSON.stringify(bags));
      } catch (e) { /* storage unavailable */ }
    }

    function loadBagsFromStorage() {
      try {
        const raw = localStorage.getItem('vsb_saved_bags');
        if (!raw) return;
        const bags = JSON.parse(raw);
        if (!Array.isArray(bags) || !bags.length) return;
        // Remove existing default bag and add stored ones
        $$(bagsWrap, '[data-vsb-bag]').forEach((card) => card.remove());
        bags.forEach((bag) => addBag(bag));
      } catch (e) { /* ignore */ }
    }

    async function openReport() {
      if (!state.reportUrl) {
        await saveResult();
      }
      if (state.reportUrl) window.open(state.reportUrl, '_blank', 'noopener');
    }

    async function loadSaved(token) {
      try {
        setBusy(true);
        const data = await request('saved/' + encodeURIComponent(token), {
          method: 'GET',
          headers: {}
        });
        state.lastResult = data.result;
        state.reportUrl = (CONFIG.adminPostUrl || '') + '?action=vsb_report&token=' + encodeURIComponent(token);
        renderDecision(data.result);
        if (actions) actions.hidden = false;
        const saveButton = $(root, '[data-vsb-save]');
        if (saveButton) saveButton.hidden = true;
        goStep(3, false);
      } catch (error) {
        announce(error.message, 'error');
      } finally {
        setBusy(false);
      }
    }

    async function runReverse() {
      const card = $(reverseBagWrap, '[data-vsb-bag]');
      const bag = collectBag(card);
      bag.type = $(root, '[data-reverse="type"]').value;
      if (!validateBag(bag)) {
        announce('Enter all three bag dimensions.', 'error');
        return;
      }
      const output = $(root, '[data-reverse-result]');
      setBusy(true);
      output.innerHTML = '<div class="vsb7-inline-loading">Comparing airline rules…</div>';
      try {
        const data = await request('reverse-search', {
          method: 'POST',
          body: JSON.stringify({
            bag: bag,
            free_only: $(root, '[data-reverse="free_only"]').checked
          })
        });
        renderReverse(data.result, output);
      } catch (error) {
        output.innerHTML = '<div class="vsb7-error"><b>Comparison unavailable</b><p>' + esc(error.message) +
          '</p></div>';
      } finally {
        setBusy(false);
      }
    }

    function renderReverse(result, output) {
      const groups = result.groups || {};
      const section = (key, title, tone, intro) => {
        const items = groups[key] || [];
        return '<details class="vsb7-reverse-group is-' + tone + '"' + (key === 'fits_free' ? ' open' : '') +
          '><summary><span><b>' + esc(title) + '</b><small>' + esc(intro) + '</small></span><em>' + items.length +
          '</em></summary><div class="vsb7-airline-results">' + (items.length ? items.map(renderReverseItem).join(
            '') : '<p>No airlines in this group.</p>') + '</div></details>';
      };
      output.innerHTML = '<div class="vsb7-reverse-summary"><div><span>GLOBAL BAG MATCH</span><h4>' + (result
          .counts && result.counts.fits_free || 0) +
        ' included matches</h4><p>Matches use stored numerical rules. Fare inclusion and official policies still need confirmation.</p></div><dl><div><dt>Fits included</dt><dd>' +
        (result.counts.fits_free || 0) + '</dd></div><div><dt>Fits with add-on</dt><dd>' + (result.counts
          .fits_addon || 0) + '</dd></div><div><dt>Does not fit</dt><dd>' + (result.counts.does_not_fit || 0) +
        '</dd></div><div><dt>Confirm</dt><dd>' + (result.counts.confirm || 0) + '</dd></div></dl></div>' +
        section('fits_free', 'Fits an included recorded allowance', 'pass', 'Best starting matches') + section(
          'fits_addon', 'Fits with fare or add-on', 'warn', 'Physical fit, booking may cost extra') + section(
          'does_not_fit', 'Does not fit', 'fail', 'At least one stored limit fails') + section('confirm',
          'Official confirmation required', 'check', 'No complete current numerical rule');
    }

    function renderReverseItem(item) {
      const dims = Array.isArray(item.dimensions_mm) ? item.dimensions_mm.map((v) => num(v / 10)).join(' × ') +
        ' cm' : 'No numerical dimensions';
      return '<article><div><span>' + esc(item.iata || '—') + '</span><p><b>' + esc(item.airline) + '</b><small>' +
        esc(item.country || '') + '</small></p></div><dl><div><dt>Rule</dt><dd>' + esc(item.label ||
          'Confirm officially') + '</dd></div><div><dt>Size</dt><dd>' + esc(dims) +
        '</dd></div><div><dt>Weight</dt><dd>' + (item.max_weight_g ? num(item.max_weight_g / 1000) + ' kg' :
          'Not published') + '</dd></div></dl><p>' + esc(item.reason || '') + '</p><a href="' + esc(safeUrl(item
          .source_url)) + '" target="_blank" rel="noopener">Official policy ↗</a></article>';
    }

    function renderSharedPicks(search) {
      const wrap = $(root, '[data-shared-picks]');
      if (!wrap) return;
      const term = String(search || '').toLowerCase();
      const list = state.airlines.filter((airline) => !term || (airline.name + ' ' + airline.iata + ' ' + airline
        .country).toLowerCase().includes(term)).slice(0, term ? 60 : 36);
      wrap.innerHTML = list.map((airline) => '<button type="button" data-shared-airline="' + esc(airline.slug) +
        '" class="' + (state.selectedShared.has(airline.slug) ? 'is-selected' : '') + '"><i>' + esc(airline.iata ||
          '✈') + '</i><span><b>' + esc(airline.name) + '</b><small>' + esc(airline.country || '') +
        '</small></span><em>✓</em></button>').join('');
      $$(wrap, '[data-shared-airline]').forEach((button) => button.addEventListener('click', () => {
        const slug = button.dataset.sharedAirline;
        if (state.selectedShared.has(slug)) state.selectedShared.delete(slug);
        else if (state.selectedShared.size < (CONFIG.limits && CONFIG.limits.sharedAirlines || 20)) state
          .selectedShared.add(slug);
        else announce('Choose up to 20 airlines.', 'error');
        renderSharedPicks($(root, '[data-shared-search]').value);
        const count = $(root, '[data-shared-count]');
        if (count) count.textContent = state.selectedShared.size;
      }));
    }

    async function runShared() {
      if (state.selectedShared.size < 2) {
        announce('Choose at least two airlines.', 'error');
        return;
      }
      const output = $(root, '[data-shared-result]');
      setBusy(true);
      output.innerHTML = '<div class="vsb7-inline-loading">Calculating the strictest shared size…</div>';
      try {
        const data = await request('shared-size', {
          method: 'POST',
          body: JSON.stringify({
            airlines: Array.from(state.selectedShared),
            bag_type: $(root, '[data-shared="type"]').value,
            free_only: $(root, '[data-shared="free_only"]').checked
          })
        });
        renderShared(data.result, output);
      } catch (error) {
        output.innerHTML = '<div class="vsb7-error"><b>Shared size unavailable</b><p>' + esc(error.message) +
          '</p></div>';
      } finally {
        setBusy(false);
      }
    }

    function renderShared(result, output) {
      const dims = result.dimensions_mm || [];
      output.innerHTML =
        '<div class="vsb7-shared-result"><div class="vsb7-shared-case"><span>BEST SHARED SIZE</span><h4>' + dims.map((
          v) => num(v / 10)).join(' × ') + ' cm</h4><b>' + (result.max_weight_g ? num(result.max_weight_g / 1000) +
          ' kg shared maximum' : 'No common numerical weight published') + '</b><small>Approx. ' + num(result
          .volume_l) + ' L</small></div><div><h5>What creates each limit?</h5><ul>' + (result.dimension_limiters ||
        []).map((list, index) => '<li><b>' + ['Longest side', 'Middle side', 'Shortest side'][index] + '</b><span>' +
          esc((list || []).join(', ') || '—') + '</span></li>').join('') + '<li><b>Weight</b><span>' + esc((result
          .weight_limiters || []).join(', ') || 'No common numerical limit') + '</span></li></ul><p>' + esc(result
          .message || '') + '</p></div></div><div class="vsb7-shared-airlines">' + (result.airlines || []).map((
          airline) => '<a href="' + esc(safeUrl(airline.source_url)) + '" target="_blank" rel="noopener"><b>' + esc(
          airline.name) + '</b><small>' + esc(airline.label) + '</small><span>' + esc((airline.dimensions_mm || [])
          .map((v) => num(v / 10)).join(' × ')) + ' cm</span></a>').join('') + '</div>' + ((result.unknown || [])
          .length ? '<details class="vsb7-unknown"><summary>' + result.unknown.length +
          ' selected airlines still need numerical confirmation</summary><div>' + result.unknown.map((a) =>
            '<a href="' + esc(safeUrl(a.source_url)) + '" target="_blank" rel="noopener">' + esc(a.airline) + ' ↗</a>'
          ).join('') + '</div></details>' : '');
    }

    function updateSummary() {
      const bagCards = $$(bagsWrap, '[data-vsb-bag]');
      const flightCards = $$(flightsWrap, '[data-vsb-flight]');
      const bagCount = $(root, '[data-summary-bags]');
      if (bagCount) bagCount.textContent = bagCards.length + (bagCards.length === 1 ? ' bag' : ' bags');
      const detail = $(root, '[data-summary-bag-detail]');
      if (detail) {
        const first = bagCards[0] && collectBag(bagCards[0]);
        detail.textContent = first && Number(first.length) ? first.length + ' × ' + first.width + ' × ' + first
          .height + ' ' + first.dimension_unit : 'Waiting for measurements';
      }
      const flightCount = $(root, '[data-summary-flights]');
      if (flightCount) flightCount.textContent = flightCards.length + (flightCards.length === 1 ? ' flight' :
        ' flights');
      const airlineText = $(root, '[data-summary-airline]');
      if (airlineText) {
        const names = flightCards.map(effectiveAirline).filter(Boolean).map((a) => a.name);
        airlineText.textContent = names.length ? Array.from(new Set(names)).join(', ') : 'Choose an airline';
      }
    }

    function bindFooter() {
      $$(root, '[data-tool]').forEach((link) => {
        const url = CONFIG.tools && CONFIG.tools[link.dataset.tool];
        if (!url) {
          link.hidden = true;
          return;
        }
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
      });
      $$(root, '[data-affiliate]').forEach((link) => {
        const url = CONFIG.affiliates && CONFIG.affiliates[link.dataset.affiliate];
        if (!url) {
          link.hidden = true;
          return;
        }
        link.href = url;
        link.target = '_blank';
        link.rel = 'sponsored nofollow noopener';
      });
      const version = $(root, '[data-vsb-data-version]');
      if (version) version.textContent = CONFIG.dataVersion || '7.0';
    }

    async function loadAirlines() {
      setBusy(true);
      const retryWrap = $(root, '[data-vsb-retry-load]') && $(root, '[data-vsb-retry-load]').closest('.vsb7-load-error');
      if (retryWrap) retryWrap.hidden = true;
      try {
        const data = await request('airlines', {
          method: 'GET',
          headers: {
            'X-VSB-Nonce': CONFIG.nonce || ''
          }
        });
        state.airlines = data.airlines || [];
        state.airlineMap = Object.fromEntries(state.airlines.map((airline) => [airline.slug, airline]));
        // Only add default bag if none exist yet (avoids duplicates on retry)
        if (!$$(bagsWrap, '[data-vsb-bag]').length) {
          addBag();
          loadBagsFromStorage();
        }
        if (!$$(flightsWrap, '[data-vsb-flight]').length) addFlight();
        renderSharedPicks('');
        const saved = root.dataset.savedToken;
        if (saved) loadSaved(saved);
      } catch (error) {
        announce(error.message, 'error');
        if (retryWrap) retryWrap.hidden = false;
      } finally {
        setBusy(false);
      }
    }

    root.addEventListener('click', (event) => {
      const copyBtn = event.target.closest('[data-copy-script]');
      if (copyBtn) {
        const text = copyBtn.dataset.copyScript || '';
        (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(() => announce(
          'Script copied. Show it at the airport.', 'success')).catch(() => announce(
          'Could not copy automatically. Select the text and copy it manually.', 'error'));
        return;
      }
      const mode = event.target.closest('[data-vsb-mode]');
      if (mode) {
        state.mode = mode.dataset.vsbMode;
        updateMode();
        return;
      }
      const next = event.target.closest('[data-vsb-next]');
      if (next) {
        if (validatePlanner(1)) goStep(Number(next.dataset.vsbNext));
        return;
      }
      const back = event.target.closest('[data-vsb-back]');
      if (back) {
        goStep(Number(back.dataset.vsbBack));
        return;
      }
      const nav = event.target.closest('[data-vsb-step-nav]');
      if (nav && Number(nav.dataset.vsbStepNav) <= state.step) goStep(Number(nav.dataset.vsbStepNav));
    });
    const retryLoad = $(root, '[data-vsb-retry-load]');
    if (retryLoad) retryLoad.addEventListener('click', loadAirlines);
    $(root, '[data-vsb-add-bag]').addEventListener('click', () => addBag());
    $(root, '[data-vsb-add-flight]').addEventListener('click', () => addFlight());
    $(root, '[data-vsb-check]').addEventListener('click', runCheck);
    $(root, '[data-vsb-restart]').addEventListener('click', () => {
      state.lastPayload = null;
      state.lastResult = null;
      state.reportUrl = '';
      resultBox.innerHTML = '';
      if (actions) actions.hidden = true;
      goStep(1);
    });
    $(root, '[data-vsb-save]').addEventListener('click', saveResult);
    $(root, '[data-vsb-pdf]').addEventListener('click', openReport);
    $(root, '[data-reverse-submit]').addEventListener('click', runReverse);
    $(root, '[data-shared-submit]').addEventListener('click', runShared);
    $(root, '[data-shared-search]').addEventListener('input', (event) => renderSharedPicks(event.target.value));
    const travellers = $(root, '[data-journey="travellers"]');
    if (travellers) travellers.addEventListener('input', refreshTravellerOptions);
    bindFooter();
    updateMode();
    goStep(1, false);
    refreshPublicNonce(false).catch(() => {
      // Protected requests renew the public token automatically before retrying.
    });
    loadAirlines();
  }

  document.addEventListener('DOMContentLoaded', () => $$(document, '[data-vsb-root]').forEach(init));
})();
