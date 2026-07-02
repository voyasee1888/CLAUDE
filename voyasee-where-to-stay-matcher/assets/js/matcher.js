/**
 * Voyasee Where to Stay Matcher -- frontend controller (v2, unified plugin).
 * Vanilla JS, no build step. Chart.js (cdnjs) is the only external dep.
 */
( function () {
	'use strict';

	if ( typeof window.VWTSM === 'undefined' ) {
		return;
	}

	var ARCHETYPE_COLORS = {
		historic: '#c97a47',
		beach: '#45a3b3',
		nightlife: '#8a5184',
		business: '#c9a24b',
		residential_quiet: '#739572',
		family_suburban: '#82a87d',
		budget_backpacker: '#c08458',
		luxury: '#b8973f',
		airport_transit: '#63708a',
	};

	var INTERESTS = [
		{ key: 'historic', label: 'Historic sites' },
		{ key: 'food_nightlife', label: 'Food & nightlife' },
		{ key: 'museums_culture', label: 'Museums & culture' },
		{ key: 'beach', label: 'Beach' },
		{ key: 'shopping', label: 'Shopping' },
		{ key: 'family_activities', label: 'Family activities' },
	];

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.querySelector( '.vwtsm-root' );
		if ( ! root ) {
			return;
		}
		new VoyaseeMatcher( root ).init();
	} );

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = String( str == null ? '' : str );
		return div.innerHTML;
	}

	function debounce( fn, wait ) {
		var t;
		return function () {
			var args = arguments, ctx = this;
			clearTimeout( t );
			t = setTimeout( function () { fn.apply( ctx, args ); }, wait );
		};
	}

	function clamp( v, min, max ) { return Math.max( min, Math.min( max, v ) ); }
	function archetypeColor( a ) { return ARCHETYPE_COLORS[ a ] || '#8a8a8a'; }
	function archetypeLabel( a ) { return ( VWTSM.archetypeLabels && VWTSM.archetypeLabels[ a ] ) || a; }

	/** Simple word-wrap for canvas fillText, used by the downloadable match card. */
	function wrapCanvasText( ctx, text, x, y, maxWidth, lineHeight ) {
		var words = text.split( ' ' );
		var line = '';
		var lineY = y;
		for ( var i = 0; i < words.length; i++ ) {
			var testLine = line + words[ i ] + ' ';
			if ( ctx.measureText( testLine ).width > maxWidth && line !== '' ) {
				ctx.fillText( line, x, lineY );
				line = words[ i ] + ' ';
				lineY += lineHeight;
			} else {
				line = testLine;
			}
		}
		ctx.fillText( line, x, lineY );
	}

	function VoyaseeMatcher( root ) {
		this.root = root;
		this.container = root.querySelector( '[data-vwtsm-step-container]' );
		this.charts = {};
		this.answers = {
			destination_slug: root.getAttribute( 'data-prefill-destination' ) || '',
			destination_name: root.getAttribute( 'data-prefill-name' ) || '',
			nights: 3,
			traveler_type: 'solo',
			first_visit: true,
			vibe_slider: 50,
			walkability_importance: false,
			budget_band: 3,
			public_transport_reliance: true,
			early_departure: false,
			luggage_amount: 'medium',
			accessibility_needs: false,
			interests: [],
			safety_comfort: 3,
		};
		this.result = null;
		this.compareExtra = null; // an optional 4th neighborhood promoted into the compare scorecard.
	}

	VoyaseeMatcher.prototype.init = function () {
		this.bindOutsideAutocompleteClose();
		if ( this.restoreFromUrl() ) {
			this.renderLoading();
			this.fetchMatch();
		} else {
			this.renderStep1();
		}
		this.loadCoverageStats();
	};

	/**
	 * Closes the destination autocomplete dropdown when clicking outside
	 * it. Bound once here (not inside renderStep1(), which re-runs on
	 * every Back/Start Over navigation) so repeated navigation never
	 * accumulates extra document-level listeners -- it looks up the
	 * current autocomplete elements at click time instead of closing over
	 * DOM nodes from whichever render pass first created it.
	 */
	VoyaseeMatcher.prototype.bindOutsideAutocompleteClose = function () {
		var self = this;
		document.addEventListener( 'click', function ( e ) {
			var resultsBox = self.container.querySelector( '[data-vwtsm-ac-results]' );
			var destInput = self.container.querySelector( '#vwtsm-destination' );
			if ( ! resultsBox || ! destInput ) { return; }
			if ( ! resultsBox.contains( e.target ) && e.target !== destInput ) {
				resultsBox.classList.remove( 'is-open' );
			}
		} );
	};

	/* ---------------------------------------------------------------------
	 * Shareable result URL
	 * ------------------------------------------------------------------- */

	/**
	 * If the current URL has a ?destination=... param (from a previously
	 * shared link), populate this.answers from the query string and
	 * return true so init() can skip straight to fetching the match.
	 */
	VoyaseeMatcher.prototype.restoreFromUrl = function () {
		var params = new URLSearchParams( window.location.search );
		var slug = params.get( 'vwtsm_destination' );
		if ( ! slug ) {
			return false;
		}

		this.answers.destination_slug = slug;
		this.answers.destination_name = params.get( 'vwtsm_name' ) || '';
		this.answers.nights = clamp( parseInt( params.get( 'vwtsm_nights' ), 10 ) || 3, 1, 60 );
		this.answers.traveler_type = params.get( 'vwtsm_traveler' ) || 'solo';
		this.answers.first_visit = params.get( 'vwtsm_first_visit' ) !== '0';
		this.answers.vibe_slider = clamp( parseInt( params.get( 'vwtsm_vibe' ), 10 ), 0, 100 );
		if ( isNaN( this.answers.vibe_slider ) ) { this.answers.vibe_slider = 50; }
		this.answers.walkability_importance = params.get( 'vwtsm_walk' ) === '1';
		this.answers.budget_band = clamp( parseInt( params.get( 'vwtsm_budget' ), 10 ) || 3, 1, 5 );
		this.answers.public_transport_reliance = params.get( 'vwtsm_transit' ) !== '0';
		this.answers.early_departure = params.get( 'vwtsm_early' ) === '1';
		this.answers.luggage_amount = params.get( 'vwtsm_luggage' ) || 'medium';
		this.answers.accessibility_needs = params.get( 'vwtsm_access' ) === '1';
		this.answers.safety_comfort = clamp( parseInt( params.get( 'vwtsm_safety' ), 10 ) || 3, 1, 5 );
		var interests = params.get( 'vwtsm_interests' );
		this.answers.interests = interests ? interests.split( ',' ).filter( Boolean ) : [];

		return true;
	};

	/**
	 * Push the current answers into the URL (without a page reload) so
	 * the result can be bookmarked or shared and reopened directly.
	 */
	VoyaseeMatcher.prototype.updateShareUrl = function () {
		var a = this.answers;
		var params = new URLSearchParams();
		params.set( 'vwtsm_destination', a.destination_slug );
		params.set( 'vwtsm_name', a.destination_name );
		params.set( 'vwtsm_nights', a.nights );
		params.set( 'vwtsm_traveler', a.traveler_type );
		params.set( 'vwtsm_first_visit', a.first_visit ? '1' : '0' );
		params.set( 'vwtsm_vibe', a.vibe_slider );
		params.set( 'vwtsm_walk', a.walkability_importance ? '1' : '0' );
		params.set( 'vwtsm_budget', a.budget_band );
		params.set( 'vwtsm_transit', a.public_transport_reliance ? '1' : '0' );
		params.set( 'vwtsm_early', a.early_departure ? '1' : '0' );
		params.set( 'vwtsm_luggage', a.luggage_amount );
		params.set( 'vwtsm_access', a.accessibility_needs ? '1' : '0' );
		params.set( 'vwtsm_safety', a.safety_comfort );
		if ( a.interests.length ) { params.set( 'vwtsm_interests', a.interests.join( ',' ) ); }

		var newUrl = window.location.pathname + '?' + params.toString() + window.location.hash;
		try {
			window.history.pushState( null, '', newUrl );
		} catch ( e ) { /* pushState can fail in some sandboxed/preview iframes -- non-fatal, sharing just won't update the visible URL. */ }
	};

	VoyaseeMatcher.prototype.clearShareUrl = function () {
		try {
			window.history.pushState( null, '', window.location.pathname + window.location.hash );
		} catch ( e ) {}
	};

	/* ---------------------------------------------------------------------
	 * Step 1
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.renderStep1 = function () {
		var self = this;

		this.container.innerHTML =
			'<div class="vwtsm-step-progress"><span class="is-active"></span><span></span></div>' +
			'<p class="vwtsm-eyebrow">Step 1 of 2</p>' +
			'<h2 class="vwtsm-heading">' + escapeHtml( VWTSM.i18n.step1Title ) + '</h2>' +

			'<div class="vwtsm-field-group">' +
				'<label class="vwtsm-field-label" for="vwtsm-destination">Destination</label>' +
				'<div class="vwtsm-autocomplete">' +
					'<input type="text" id="vwtsm-destination" class="vwtsm-input" placeholder="e.g. Tokyo, Paris, Bangkok..." autocomplete="off" />' +
					'<div class="vwtsm-autocomplete-results" data-vwtsm-ac-results></div>' +
				'</div>' +
			'</div>' +

			'<div class="vwtsm-field-group">' +
				'<label class="vwtsm-field-label" for="vwtsm-nights">How many nights?</label>' +
				'<input type="number" id="vwtsm-nights" class="vwtsm-input" min="1" max="60" value="' + this.answers.nights + '" style="max-width:140px" />' +
			'</div>' +

			'<div class="vwtsm-field-group">' +
				'<span class="vwtsm-field-label">Who\'s traveling?</span>' +
				'<div class="vwtsm-segmented" data-vwtsm-segmented="traveler_type">' +
					this.buildSegmentedButtons( [
						{ v: 'solo', l: 'Solo' }, { v: 'couple', l: 'Couple' }, { v: 'family', l: 'Family' },
						{ v: 'group', l: 'Group' }, { v: 'business', l: 'Business' },
					], this.answers.traveler_type ) +
				'</div>' +
			'</div>' +

			'<div class="vwtsm-toggle-row">' +
				'<span class="vwtsm-field-label" style="margin:0">First time visiting?</span>' +
				'<button type="button" class="vwtsm-toggle' + ( this.answers.first_visit ? ' is-on' : '' ) + '" data-vwtsm-toggle="first_visit"></button>' +
			'</div>' +

			'<div class="vwtsm-step-actions">' +
				'<span></span>' +
				'<button type="button" class="vwtsm-btn-primary" data-vwtsm-next disabled>' + escapeHtml( VWTSM.i18n.continue ) + '</button>' +
			'</div>';

		this.bindSegmented( this.container, this.answers );
		this.bindToggles( this.container, this.answers );

		var destInput = this.container.querySelector( '#vwtsm-destination' );
		var resultsBox = this.container.querySelector( '[data-vwtsm-ac-results]' );
		var nextBtn = this.container.querySelector( '[data-vwtsm-next]' );

		if ( this.answers.destination_name ) {
			destInput.value = this.answers.destination_name;
			nextBtn.disabled = false;
		} else if ( this.answers.destination_slug ) {
			// A slug was pre-filled (e.g. from a shortcode landing page or
			// a share link) but no display name came with it -- still
			// unlock Continue rather than leaving the button silently
			// stuck disabled, and show a readable fallback in the field.
			destInput.value = this.answers.destination_slug.replace( /-/g, ' ' ).replace( /\b\w/g, function ( c ) { return c.toUpperCase(); } );
			nextBtn.disabled = false;
		}

		var doSearch = debounce( function () {
			var term = destInput.value.trim();
			if ( term.length < 2 ) {
				resultsBox.classList.remove( 'is-open' );
				resultsBox.innerHTML = '';
				return;
			}
			fetch( VWTSM.destinationsUrl + '?search=' + encodeURIComponent( term ) + '&limit=8' )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					var list = ( data && data.destinations ) || [];
					if ( ! list.length ) {
						resultsBox.innerHTML = '<div class="vwtsm-autocomplete-result">No matches yet -- try a different spelling, or a city name instead of a country.</div>';
						resultsBox.classList.add( 'is-open' );
						return;
					}
					resultsBox.innerHTML = list.map( function ( d ) {
						return '<div class="vwtsm-autocomplete-result" data-slug="' + escapeHtml( d.slug ) + '" data-name="' + escapeHtml( d.name ) + '">' +
							escapeHtml( d.name ) + ( d.country ? ', ' + escapeHtml( d.country ) : '' ) + '</div>';
					} ).join( '' );
					resultsBox.classList.add( 'is-open' );
				} )
				.catch( function () {} );
		}, 300 );

		destInput.addEventListener( 'input', doSearch );
		destInput.addEventListener( 'focus', doSearch );

		resultsBox.addEventListener( 'click', function ( e ) {
			var row = e.target.closest( '[data-slug]' );
			if ( ! row ) { return; }
			self.answers.destination_slug = row.getAttribute( 'data-slug' );
			self.answers.destination_name = row.getAttribute( 'data-name' );
			destInput.value = self.answers.destination_name;
			resultsBox.classList.remove( 'is-open' );
			nextBtn.disabled = false;
		} );

		nextBtn.addEventListener( 'click', function () {
			self.answers.nights = clamp( parseInt( self.container.querySelector( '#vwtsm-nights' ).value, 10 ) || 3, 1, 60 );
			if ( ! self.answers.destination_slug ) { return; }
			self.renderStep2();
		} );
	};

	/* ---------------------------------------------------------------------
	 * Step 2
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.renderStep2 = function () {
		var self = this;

		this.container.innerHTML =
			'<div class="vwtsm-step-progress"><span class="is-complete"></span><span class="is-active"></span></div>' +
			'<p class="vwtsm-eyebrow">Step 2 of 2</p>' +
			'<h2 class="vwtsm-heading">' + escapeHtml( VWTSM.i18n.step2Title ) + '</h2>' +

			'<div class="vwtsm-field-group">' +
				'<span class="vwtsm-field-label">Pace & vibe</span>' +
				'<div class="vwtsm-slider-row">' +
					'<span>Quiet</span><input type="range" min="0" max="100" value="' + this.answers.vibe_slider + '" class="vwtsm-slider" id="vwtsm-vibe" /><span>Lively</span>' +
				'</div>' +
				'<div class="vwtsm-toggle-row" style="margin-top:0.75rem">' +
					'<span class="vwtsm-field-label" style="margin:0">Walkability matters a lot to me</span>' +
					'<button type="button" class="vwtsm-toggle' + ( this.answers.walkability_importance ? ' is-on' : '' ) + '" data-vwtsm-toggle="walkability_importance"></button>' +
				'</div>' +
			'</div>' +

			'<div class="vwtsm-field-group">' +
				'<span class="vwtsm-field-label">Budget per night</span>' +
				'<div class="vwtsm-segmented" data-vwtsm-segmented="budget_band">' +
					this.buildSegmentedButtons( [ { v: 1, l: '$' }, { v: 2, l: '$$' }, { v: 3, l: '$$$' }, { v: 4, l: '$$$$' }, { v: 5, l: '$$$$$' } ], this.answers.budget_band ) +
				'</div>' +
			'</div>' +

			'<div class="vwtsm-field-group">' +
				'<span class="vwtsm-field-label">Logistics</span>' +
				'<div class="vwtsm-toggle-row"><span>I\'ll mostly use public transport / walking</span>' +
					'<button type="button" class="vwtsm-toggle' + ( this.answers.public_transport_reliance ? ' is-on' : '' ) + '" data-vwtsm-toggle="public_transport_reliance"></button></div>' +
				'<div class="vwtsm-toggle-row"><span>I have an early departure or late arrival</span>' +
					'<button type="button" class="vwtsm-toggle' + ( this.answers.early_departure ? ' is-on' : '' ) + '" data-vwtsm-toggle="early_departure"></button></div>' +
				'<div class="vwtsm-toggle-row"><span>I need step-free / accessible access</span>' +
					'<button type="button" class="vwtsm-toggle' + ( this.answers.accessibility_needs ? ' is-on' : '' ) + '" data-vwtsm-toggle="accessibility_needs"></button></div>' +
				'<div style="margin-top:0.75rem">' +
					'<span class="vwtsm-field-label">Luggage amount</span>' +
					'<div class="vwtsm-segmented" data-vwtsm-segmented="luggage_amount">' +
						this.buildSegmentedButtons( [ { v: 'low', l: 'Light' }, { v: 'medium', l: 'Medium' }, { v: 'high', l: 'Heavy' } ], this.answers.luggage_amount ) +
					'</div>' +
				'</div>' +
			'</div>' +

			'<div class="vwtsm-field-group">' +
				'<span class="vwtsm-field-label">What are you most interested in? (pick any)</span>' +
				'<div class="vwtsm-chips" data-vwtsm-chips="interests">' +
					INTERESTS.map( function ( i ) {
						var sel = self.answers.interests.indexOf( i.key ) > -1;
						return '<button type="button" class="vwtsm-chip' + ( sel ? ' is-selected' : '' ) + '" data-value="' + i.key + '">' + escapeHtml( i.label ) + '</button>';
					} ).join( '' ) +
				'</div>' +
			'</div>' +

			'<div class="vwtsm-step-actions">' +
				'<button type="button" class="vwtsm-btn-secondary" data-vwtsm-back>&larr; Back</button>' +
				'<button type="button" class="vwtsm-btn-primary" data-vwtsm-submit>' + escapeHtml( VWTSM.i18n.showMatches ) + '</button>' +
			'</div>';

		this.bindSegmented( this.container, this.answers );
		this.bindToggles( this.container, this.answers );

		var vibeSlider = this.container.querySelector( '#vwtsm-vibe' );
		vibeSlider.addEventListener( 'input', function () { self.answers.vibe_slider = parseInt( vibeSlider.value, 10 ); } );

		var chipsBox = this.container.querySelector( '[data-vwtsm-chips="interests"]' );
		chipsBox.addEventListener( 'click', function ( e ) {
			var chip = e.target.closest( '[data-value]' );
			if ( ! chip ) { return; }
			var val = chip.getAttribute( 'data-value' );
			var idx = self.answers.interests.indexOf( val );
			if ( idx > -1 ) { self.answers.interests.splice( idx, 1 ); chip.classList.remove( 'is-selected' ); }
			else { self.answers.interests.push( val ); chip.classList.add( 'is-selected' ); }
		} );

		var backBtn = this.container.querySelector( '[data-vwtsm-back]' );
		var submitBtn = this.container.querySelector( '[data-vwtsm-submit]' );
		if ( backBtn ) { backBtn.addEventListener( 'click', function () { self.renderStep1(); } ); }
		if ( submitBtn ) {
			submitBtn.addEventListener( 'click', function () {
				self.renderLoading();
				self.fetchMatch();
			} );
		}
	};

	/* ---------------------------------------------------------------------
	 * Shared control binders
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.buildSegmentedButtons = function ( options, current ) {
		return options.map( function ( opt ) {
			var sel = String( opt.v ) === String( current );
			return '<button type="button" class="' + ( sel ? 'is-selected' : '' ) + '" data-value="' + opt.v + '">' + escapeHtml( opt.l ) + '</button>';
		} ).join( '' );
	};

	VoyaseeMatcher.prototype.bindSegmented = function ( scope, answers ) {
		scope.querySelectorAll( '[data-vwtsm-segmented]' ).forEach( function ( group ) {
			var key = group.getAttribute( 'data-vwtsm-segmented' );
			group.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest( 'button' );
				if ( ! btn ) { return; }
				var raw = btn.getAttribute( 'data-value' );
				answers[ key ] = isNaN( raw ) ? raw : parseInt( raw, 10 );
				group.querySelectorAll( 'button' ).forEach( function ( b ) { b.classList.toggle( 'is-selected', b === btn ); } );
			} );
		} );
	};

	VoyaseeMatcher.prototype.bindToggles = function ( scope, answers ) {
		scope.querySelectorAll( '[data-vwtsm-toggle]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var key = btn.getAttribute( 'data-vwtsm-toggle' );
				answers[ key ] = ! answers[ key ];
				btn.classList.toggle( 'is-on', answers[ key ] );
			} );
		} );
	};

	/* ---------------------------------------------------------------------
	 * Loading / fetch
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.renderLoading = function () {
		this.container.innerHTML = '<div class="vwtsm-loading"><div class="vwtsm-loading-spinner"></div><p>' + escapeHtml( VWTSM.i18n.loading ) + '</p></div>';
	};

	VoyaseeMatcher.prototype.fetchMatch = function () {
		var self = this;
		fetch( VWTSM.restUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': VWTSM.nonce },
			body: JSON.stringify( self.answers ),
		} )
			.then( function ( r ) {
				if ( ! r.ok ) {
					console.error( 'Voyasee matcher: REST request failed with status', r.status );
				}
				return r.json();
			} )
			.then( function ( data ) {
				self.result = data;
				if ( ! data.matches || ! data.matches.length ) { self.renderEmpty( data ); return; }
				self.renderResults( data );
			} )
			.catch( function ( err ) {
				console.error( 'Voyasee matcher: fetchMatch failed', err );
				self.renderEmpty( { message: 'Something went wrong reaching the matcher. Please try again.' } );
			} );
	};

	VoyaseeMatcher.prototype.renderEmpty = function ( data ) {
		var self = this;
		this.container.innerHTML =
			'<div class="vwtsm-empty-state">' +
				'<h2 class="vwtsm-heading">No detailed matches yet</h2>' +
				'<p>' + escapeHtml( ( data && data.message ) || VWTSM.i18n.noData ) + '</p>' +
				'<button type="button" class="vwtsm-btn-secondary" data-vwtsm-restart style="margin-top:1rem">Try another destination</button>' +
			'</div>';
		var restartBtn1 = this.container.querySelector( '[data-vwtsm-restart]' );
		if ( restartBtn1 ) {
			restartBtn1.addEventListener( 'click', function () {
				self.answers.destination_slug = ''; self.answers.destination_name = '';
				self.clearShareUrl();
				self.renderStep1();
			} );
		}
	};

	/* ---------------------------------------------------------------------
	 * Results
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.renderResults = function ( data ) {
		var self = this;
		try {
			this.renderResultsInner( data );
		} catch ( err ) {
			console.error( 'Voyasee matcher: renderResults failed', err );
			self.renderEmpty( { message: 'Something went wrong showing your matches. Please try again, or try another destination.' } );
		}
	};

	VoyaseeMatcher.prototype.renderResultsInner = function ( data ) {
		var self = this;
		var destination = data.destination;
		var matches = data.matches;
		var explored = data.explored || [];
		var all = matches.concat( explored );

		this.compareExtra = null; // reset any 4th-slot pick from a previous result.
		this._lastMatches = matches;
		this.updateShareUrl();

		// The previous result's map container (if any) is about to be
		// discarded via innerHTML replacement below -- tear down its Leaflet
		// instance first so its window resize listener doesn't leak across
		// repeated searches (Back / Start Over / a new destination).
		if ( this._leafletMap ) {
			this._leafletMap.remove();
			this._leafletMap = null;
		}

		var tierNote = '';
		if ( data.data_tier && data.data_tier > 1 ) {
			tierNote = '<p class="vwtsm-data-tier-note">' +
				( data.data_tier === 2 ? 'General-zone guidance for this destination -- not yet named-neighborhood detail.' : 'Simplified guidance for this destination -- limited data so far.' ) +
				'</p>';
		}

		var confirmedCount = matches.filter( function ( n ) { return ! ( n.scoring && n.scoring.any_unsynced ); } ).length;
		var confidenceNote = confirmedCount < matches.length
			? '<p class="vwtsm-data-tier-note vwtsm-confidence-summary"><span class="vwtsm-confidence-dot"></span>' +
				confirmedCount + ' of ' + matches.length + ' matches have fully synced walkability/nightlife data -- others are marked "est." until the next data sync.</p>'
			: '';

		// Neighborhoods missing coordinates can't be plotted -- filter once
		// and share the exact same list between the map and its legend so
		// they can never disagree about which areas are shown.
		var mappable = all.filter( function ( n ) { return n.lat && n.lng; } );
		var mapCaption = mappable.length < all.length
			? '<p class="vwtsm-field-hint">' + ( all.length - mappable.length ) + ' area(s) without map coordinates yet aren\'t shown below.</p>'
			: '';

		this.container.innerHTML =
			'<div class="vwtsm-results-header">' +
				'<div>' +
					'<p class="vwtsm-eyebrow">Your matches</p>' +
					'<h2 class="vwtsm-heading">Best Areas to Stay in ' + escapeHtml( destination.name ) + '</h2>' +
					tierNote +
					confidenceNote +
				'</div>' +
				'<div class="vwtsm-results-header-actions">' +
					'<button type="button" class="vwtsm-btn-secondary" data-vwtsm-share">Copy share link</button>' +
					'<button type="button" class="vwtsm-btn-secondary" data-vwtsm-restart>Start over</button>' +
				'</div>' +
			'</div>' +

			this.buildTripRealityStrip( matches[0] ) +

			( data.split_stay ? this.buildSplitStaySuggestion( data.split_stay ) : '' ) +

			'<div class="vwtsm-match-grid" data-vwtsm-match-grid></div>' +

			'<h3 class="vwtsm-subheading">' + escapeHtml( VWTSM.i18n.compareTable ) + '</h3>' +
			'<div data-vwtsm-compare-scorecard>' + this.buildCompareScorecard( matches ) + '</div>' +

			'<h3 class="vwtsm-subheading">Where these areas sit in ' + escapeHtml( destination.name ) + '</h3>' +
			'<p class="vwtsm-field-hint">Approximate relative positions, plotted from each area\'s coordinates.</p>' +
			mapCaption +
			this.buildOverviewMap( mappable ) +
			this.buildOverviewLegend( mappable ) +

			( explored.length ? this.buildAccordionShell( explored.length ) : '' ) +

			'<button type="button" class="vwtsm-refine-link" data-vwtsm-refine-toggle>' + escapeHtml( VWTSM.i18n.refine ) + ' &darr;</button>' +
			this.buildRefinePanel();

		var grid = this.container.querySelector( '[data-vwtsm-match-grid]' );
		matches.forEach( function ( n, idx ) { grid.insertAdjacentHTML( 'beforeend', self.buildMatchCard( n, idx ) ); } );

		grid.querySelectorAll( '.vwtsm-match-card' ).forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				self.toggleCardExpansion( grid, matches, parseInt( card.getAttribute( 'data-idx' ), 10 ) );
			} );
		} );

		this.animateMatchCardsIn( grid );
		this.initOverviewMap( mappable );

		if ( explored.length ) { this.bindAccordion( explored ); }

		var restartBtn2 = this.container.querySelector( '[data-vwtsm-restart]' );
		if ( restartBtn2 ) {
			restartBtn2.addEventListener( 'click', function () {
				self.result = null;
				self.clearShareUrl();
				self.renderStep1();
			} );
		}

		this.bindRefinePanel();
		this.bindShareButton();
	};

	/**
	 * Staggered fade/rise-in for the match-card grid, plus a count-up
	 * animation for each card's match-score ring, on first reveal. Both
	 * skip straight to the end state under prefers-reduced-motion.
	 */
	VoyaseeMatcher.prototype.animateMatchCardsIn = function ( grid ) {
		var reduceMotion = !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

		// Cards are visible by default (fail-safe if this never runs); to
		// animate, briefly mark them "entering" (hidden) and peel that off
		// with a stagger so each one fades/rises in instead of popping in
		// all at once.
		if ( ! reduceMotion ) {
			var cards = grid.querySelectorAll( '.vwtsm-match-card' );
			cards.forEach( function ( card ) { card.classList.add( 'is-entering' ); } );
			// Force a reflow so the "entering" state actually paints before
			// we start removing it -- otherwise the browser may coalesce
			// both class changes into one frame and skip the transition.
			void grid.offsetWidth;
			cards.forEach( function ( card, i ) {
				setTimeout( function () { card.classList.remove( 'is-entering' ); }, 90 * i );
			} );
		}

		grid.querySelectorAll( '[data-target-score]' ).forEach( function ( stamp ) {
			var target = clamp( parseInt( stamp.getAttribute( 'data-target-score' ), 10 ) || 0, 0, 100 );
			var digits = stamp.querySelector( '[data-vwtsm-score-digits]' );

			if ( reduceMotion ) {
				stamp.style.setProperty( '--score', target );
				if ( digits ) { digits.textContent = target; }
				return;
			}

			var start = null;
			var duration = 900;
			function tick( ts ) {
				if ( start === null ) { start = ts; }
				var progress = Math.min( 1, ( ts - start ) / duration );
				var eased = 1 - Math.pow( 1 - progress, 3 ); // ease-out cubic
				var current = Math.round( target * eased );
				stamp.style.setProperty( '--score', current );
				if ( digits ) { digits.textContent = current; }
				if ( progress < 1 ) { requestAnimationFrame( tick ); }
			}
			requestAnimationFrame( tick );
		} );
	};

	VoyaseeMatcher.prototype.buildPhotoCredit = function ( raw ) {
		if ( ! raw ) { return ''; }
		try {
			var parsed = JSON.parse( raw );
			if ( parsed && parsed.name ) {
				var sourceName = parsed.source || 'Pexels';
				var nameLink = parsed.profile_url
					? '<a href="' + escapeHtml( parsed.profile_url ) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml( parsed.name ) + '</a>'
					: escapeHtml( parsed.name );
				var sourceLink = parsed.unsplash_url
					? '<a href="' + escapeHtml( parsed.unsplash_url ) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml( sourceName ) + '</a>'
					: escapeHtml( sourceName );
				return 'Photo by ' + nameLink + ' on ' + sourceLink;
			}
		} catch ( e ) {
			// Not JSON -- fall through to plain text (manually-entered credits).
		}
		return escapeHtml( raw );
	};

	/** Safe for interpolation into a style="..." attribute: escapes HTML entities, then percent-encodes any quote so it can never break out of the attribute. */
	function escapeForStyleUrl( str ) {
		return escapeHtml( str ).replace( /"/g, '%22' ).replace( /'/g, '%27' );
	}

	VoyaseeMatcher.prototype.buildMatchCard = function ( n, idx ) {
		var color = archetypeColor( n.archetype );
		var hero = n.hero_image_url
			? 'background-image:url(' + escapeForStyleUrl( n.hero_image_url ) + ');background-color:' + color
			: 'background-color:' + color;
		var filled = clamp( parseInt( n.price_band, 10 ) || 0, 0, 5 );
		var priceHtml = '';
		for ( var i = 1; i <= 5; i++ ) {
			priceHtml += '<span class="' + ( i <= filled ? 'vwtsm-price-filled' : 'vwtsm-price-empty' ) + '">$</span>';
		}
		var estimateBadge = ( n.scoring && n.scoring.any_unsynced )
			? '<span class="vwtsm-ticket-estimate" title="' + escapeHtml( VWTSM.i18n.estimateTooltip ) + '">est.</span>'
			: '';

		return (
			'<div class="vwtsm-match-card" data-idx="' + idx + '">' +
				'<div class="vwtsm-card-archetype-bar" style="background:' + color + '"></div>' +
				'<div class="vwtsm-card-hero" style="' + hero + '">' +
					( n.hero_image_credit ? '<span class="vwtsm-card-hero-credit">' + this.buildPhotoCredit( n.hero_image_credit ) + '</span>' : '' ) +
				'</div>' +
				'<div class="vwtsm-card-body">' +
					'<h3 class="vwtsm-card-name">' + escapeHtml( n.name ) + '</h3>' +
					'<div class="vwtsm-card-archetype-label">' + escapeHtml( archetypeLabel( n.archetype ) ) + '</div>' +
				'</div>' +
				'<div class="vwtsm-ticket-seam">' +
					'<span class="vwtsm-ticket-notch vwtsm-notch-left"></span>' +
					'<span class="vwtsm-ticket-notch vwtsm-notch-right"></span>' +
				'</div>' +
				'<div class="vwtsm-ticket-stub">' +
					'<div class="vwtsm-ticket-stamp' + ( estimateBadge ? ' has-estimate' : '' ) + '" style="--score:0" data-target-score="' + n.match_score + '">' +
						'<span data-vwtsm-score-digits>0</span><small>Match</small>' + estimateBadge +
					'</div>' +
					'<div class="vwtsm-ticket-price">' + priceHtml + '</div>' +
					'<div class="vwtsm-ticket-barcode" aria-hidden="true"></div>' +
				'</div>' +
			'</div>'
		);
	};

	VoyaseeMatcher.prototype.toggleCardExpansion = function ( grid, matches, idx ) {
		var existing = grid.querySelector( '.vwtsm-detail-panel' );
		var alreadyThis = existing && existing.getAttribute( 'data-for-idx' ) === String( idx );

		if ( existing ) {
			if ( this.charts.detail ) { this.charts.detail.destroy(); this.charts.detail = null; }
			existing.remove();
			grid.querySelectorAll( '.vwtsm-match-card' ).forEach( function ( c ) { c.classList.remove( 'is-expanded' ); } );
			if ( alreadyThis ) { return; }
		}

		var n = matches[ idx ];
		var panel = document.createElement( 'div' );
		panel.className = 'vwtsm-detail-panel';
		panel.setAttribute( 'data-for-idx', String( idx ) );
		panel.innerHTML = this.buildDetailPanelInner( n, false );
		grid.appendChild( panel );

		grid.querySelector( '.vwtsm-match-card[data-idx="' + idx + '"]' ).classList.add( 'is-expanded' );
		this.renderRadarChart( panel.querySelector( 'canvas' ), n, 'detail' );
		this.bindDetailPanelActions( panel, n );
	};

	VoyaseeMatcher.prototype.buildDetailPanelInner = function ( n, showAddCompare ) {
		var whyFits = ( n.scoring && n.scoring.why_fits ) || [];
		var whyCaution = ( n.scoring && n.scoring.why_caution ) || [];
		var filled = clamp( parseInt( n.price_band, 10 ) || 0, 0, 5 );

		var priceHtml = '';
		for ( var i = 1; i <= 5; i++ ) {
			priceHtml += '<span class="' + ( i <= filled ? 'vwtsm-price-filled' : 'vwtsm-price-empty' ) + '">$</span>';
		}

		var bookingBtn = VWTSM.bookingUrl
			? '<a class="vwtsm-btn-primary" href="' + escapeHtml( VWTSM.bookingUrl ) + '" target="_blank" rel="nofollow sponsored noopener">' + escapeHtml( VWTSM.i18n.bookOn ) + '</a>'
			: '';

		var freshnessBadge = n.last_reviewed
			? '<span class="vwtsm-badge vwtsm-badge-freshness">Reviewed ' + escapeHtml( this.formatReviewDate( n.last_reviewed ) ) + '</span>'
			: '';

		var confidence = ( n.scoring && n.scoring.confidence ) || {};
		var dimLabels = { walkability: 'walkability', attractions: 'attractions' };
		var unsyncedAxes = Object.keys( dimLabels ).filter( function ( k ) { return confidence[ k ] === false; } ).map( function ( k ) { return dimLabels[ k ]; } );
		var radarNote = unsyncedAxes.length
			? '<p class="vwtsm-confidence-note"><span class="vwtsm-confidence-dot"></span>' +
				'The hollow point' + ( unsyncedAxes.length > 1 ? 's' : '' ) + ' on the chart (' + unsyncedAxes.join( ', ' ) + ') ' +
				( unsyncedAxes.length > 1 ? 'are' : 'is' ) + ' an estimate -- OpenStreetMap sync hasn\'t run for this area yet.</p>'
			: '';

		return (
			'<div class="vwtsm-detail-grid">' +
				'<div>' +
					'<div class="vwtsm-radar-wrap"><canvas></canvas></div>' +
					radarNote +
					'<div class="vwtsm-price-band" style="margin-top:1rem">' + priceHtml + '</div>' +
					'<div class="vwtsm-badge-row">' +
						'<span class="vwtsm-badge">Family fit: ' + ( n.family_suitability || 0 ) + '/100</span>' +
						'<span class="vwtsm-badge">Solo fit: ' + ( n.solo_suitability || 0 ) + '/100</span>' +
						'<span class="vwtsm-badge">Safety comfort: ' + ( n.safety_tier || 0 ) + '/5</span>' +
						freshnessBadge +
					'</div>' +
					( n.local_tip ? '<p class="vwtsm-local-tip">' + escapeHtml( n.local_tip ) + '</p>' : '' ) +
					'<div class="vwtsm-cta-row">' + bookingBtn +
						'<button type="button" class="vwtsm-btn-secondary" data-vwtsm-download-card>Download match card</button>' +
						( showAddCompare ? '<button type="button" class="vwtsm-btn-secondary" data-vwtsm-add-compare>Add to comparison</button>' : '' ) +
					'</div>' +
				'</div>' +
				'<div>' +
					'<h4 class="vwtsm-subheading vwtsm-subheading-success">' + escapeHtml( VWTSM.i18n.whyFits ) + '</h4>' +
					'<ul class="vwtsm-list-fits">' + whyFits.map( function ( l ) { return '<li>' + escapeHtml( l ) + '</li>'; } ).join( '' ) + '</ul>' +
					'<div class="vwtsm-caution-box">' +
						'<h4 class="vwtsm-subheading">' + escapeHtml( VWTSM.i18n.whyCaution ) + '</h4>' +
						'<ul class="vwtsm-list-caution">' + whyCaution.map( function ( l ) { return '<li>' + escapeHtml( l ) + '</li>'; } ).join( '' ) + '</ul>' +
					'</div>' +
					this.buildCompass( n ) +
				'</div>' +
			'</div>'
		);
	};

	VoyaseeMatcher.prototype.formatReviewDate = function ( dateStr ) {
		try {
			var d = new Date( dateStr + 'T00:00:00' );
			if ( isNaN( d.getTime() ) ) { return dateStr; }
			var months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
			return months[ d.getMonth() ] + ' ' + d.getFullYear();
		} catch ( e ) {
			return dateStr;
		}
	};

	VoyaseeMatcher.prototype.renderRadarChart = function ( canvas, n, slot ) {
		if ( ! canvas ) {
			console.error( 'Voyasee matcher: radar chart canvas not found in the detail panel.' );
			return;
		}
		if ( typeof Chart === 'undefined' ) {
			console.error( 'Voyasee matcher: Chart.js did not load (likely blocked by an ad-blocker, content-security-policy, or a CDN network issue) -- the radar chart will stay blank.' );
			return;
		}
		if ( this.charts[ slot ] ) { this.charts[ slot ].destroy(); }

		var dims = ( n.scoring && n.scoring.dimensions ) || {};
		var confidence = ( n.scoring && n.scoring.confidence ) || {};
		var dimKeys = [ 'budget', 'vibe', 'attractions', 'walkability', 'airport', 'suitability', 'safety' ];

		// Points for dimensions without confirmed data render hollow/muted
		// (a lighter fill + slightly larger radius) instead of the normal
		// solid gold point, so an estimate never looks visually identical
		// to a verified score on the chart.
		var pointBg    = dimKeys.map( function ( k ) { return false === confidence[ k ] ? 'rgba(207,200,184,0.25)' : '#e8cf86'; } );
		var pointStyle = dimKeys.map( function ( k ) { return false === confidence[ k ] ? 'triangle' : 'circle'; } );
		var pointRad   = dimKeys.map( function ( k ) { return false === confidence[ k ] ? 5 : 3; } );

		this.charts[ slot ] = new Chart( canvas.getContext( '2d' ), {
			type: 'radar',
			data: {
				labels: [ 'Budget', 'Vibe', 'Attractions', 'Walkability', 'Airport', 'Suitability', 'Safety' ],
				datasets: [ {
					label: n.name,
					data: [ dims.budget || 0, dims.vibe || 0, dims.attractions || 0, dims.walkability || 0, dims.airport || 0, dims.suitability || 0, dims.safety || 0 ],
					backgroundColor: 'rgba(201,162,75,0.25)',
					borderColor: '#c9a24b',
					pointBackgroundColor: pointBg,
					pointStyle: pointStyle,
					pointRadius: pointRad,
				} ],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: {
					r: {
						min: 0, max: 100,
						ticks: { display: false, stepSize: 25 },
						grid: { color: '#2c3c61' },
						angleLines: { color: '#2c3c61' },
						pointLabels: { color: '#cfc8b8', font: { size: 10 } },
					},
				},
			},
		} );
	};

	/* ---------------------------------------------------------------------
	 * Compass diagram
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.buildCompass = function ( n ) {
		function timeToRadiusPct( min ) {
			var t = clamp( min || 0, 0, 90 );
			return 20 + ( t / 90 ) * 28;
		}
		function polarPoint( angleDeg, radiusPct ) {
			var rad = ( angleDeg * Math.PI ) / 180;
			return { x: 50 + radiusPct * Math.cos( rad ), y: 50 + radiusPct * Math.sin( rad ) };
		}
		var centerPt = polarPoint( -90, timeToRadiusPct( n.time_center_min ) );
		var airportPt = polarPoint( -30, timeToRadiusPct( n.time_airport_min ) );

		return (
			'<div class="vwtsm-compass" aria-hidden="true">' +
				'<div class="vwtsm-compass-ring"></div><div class="vwtsm-compass-ring vwtsm-ring-inner"></div>' +
				'<div class="vwtsm-compass-center" title="' + escapeHtml( n.name ) + '"></div>' +
				'<div class="vwtsm-compass-point is-neighborhood" style="left:' + centerPt.x + '%;top:' + centerPt.y + '%"></div>' +
				'<div class="vwtsm-compass-label" style="left:' + centerPt.x + '%;top:' + centerPt.y + '%">City center · ' + ( n.time_center_min || '?' ) + ' min</div>' +
				'<div class="vwtsm-compass-point is-airport" style="left:' + airportPt.x + '%;top:' + airportPt.y + '%"></div>' +
				'<div class="vwtsm-compass-label" style="left:' + airportPt.x + '%;top:' + airportPt.y + '%">Airport · ' + ( n.time_airport_min || '?' ) + ' min</div>' +
			'</div>' +
			'<p class="vwtsm-field-hint" style="text-align:center">Travel times are accurate; positions on this diagram are illustrative, not true compass directions.</p>'
		);
	};

	/* ---------------------------------------------------------------------
	 * Comparison scorecard -- horizontal bars (infographic style)
	 * ------------------------------------------------------------------- */

	/* ---------------------------------------------------------------------
	 * Trip Reality strip -- a glanceable gauge summarizing the top match,
	 * echoing the "Trip Reality" concept used across Voyasee's other tools.
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.buildTripRealityStrip = function ( top ) {
		if ( ! top ) { return ''; }
		var dims = ( top.scoring && top.scoring.dimensions ) || {};
		var confidence = ( top.scoring && top.scoring.confidence ) || {};
		var color = archetypeColor( top.archetype );
		var walkabilityPending = false === confidence.walkability;

		var chips = [
			{ label: 'Budget fit', value: dims.budget || 0 },
			{ label: 'Walkability', value: dims.walkability || 0, pending: walkabilityPending },
			{ label: 'Airport ease', value: dims.airport || 0 },
			{ label: 'Safety comfort', value: dims.safety || 0 },
		];

		var chipsHtml = chips.map( function ( c ) {
			if ( c.pending ) {
				return (
					'<div class="vwtsm-reality-chip">' +
						'<div class="vwtsm-reality-chip-bg vwtsm-compare-bar-pending"></div>' +
						'<span>' + escapeHtml( c.label ) + ' <em>(' + escapeHtml( VWTSM.i18n.notSynced.toLowerCase() ) + ')</em></span>' +
					'</div>'
				);
			}
			return (
				'<div class="vwtsm-reality-chip">' +
					'<div class="vwtsm-reality-chip-bg"><div class="vwtsm-reality-chip-fill" style="width:' + c.value + '%;background:' + color + '"></div></div>' +
					'<span>' + escapeHtml( c.label ) + '</span>' +
				'</div>'
			);
		} ).join( '' );

		return (
			'<div class="vwtsm-reality-strip">' +
				'<div class="vwtsm-reality-gauge">' +
					'<div class="vwtsm-reality-gauge-ring" style="--score:' + top.match_score + ';--ring-color:' + color + '"><span>' + top.match_score + '</span></div>' +
					'<div class="vwtsm-reality-gauge-label">' +
						'<span class="vwtsm-eyebrow">Top match</span>' +
						'<strong>' + escapeHtml( top.name ) + '</strong>' +
					'</div>' +
				'</div>' +
				'<div class="vwtsm-reality-chips">' + chipsHtml + '</div>' +
			'</div>'
		);
	};

	/* ---------------------------------------------------------------------
	 * Detail panel actions: download-as-image, add-to-comparison
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.bindDetailPanelActions = function ( panel, n ) {
		var self = this;

		var downloadBtn = panel.querySelector( '[data-vwtsm-download-card]' );
		if ( downloadBtn ) {
			downloadBtn.addEventListener( 'click', function () { self.downloadMatchCard( n ); } );
		}

		var addCompareBtn = panel.querySelector( '[data-vwtsm-add-compare]' );
		if ( addCompareBtn ) {
			addCompareBtn.addEventListener( 'click', function () {
				self.addToCompare( n );
				addCompareBtn.textContent = 'Added to comparison';
				addCompareBtn.disabled = true;
			} );
		}
	};

	/**
	 * Draw a small shareable summary card for one match onto an offscreen
	 * canvas, then trigger a PNG download. No server round-trip, no new
	 * library -- Chart.js already pulled in canvas support we can reuse
	 * for plain 2D drawing here.
	 */
	VoyaseeMatcher.prototype.downloadMatchCard = function ( n ) {
		var canvas = document.createElement( 'canvas' );
		var W = 800, H = 500;
		canvas.width = W;
		canvas.height = H;
		var ctx = canvas.getContext( '2d' );
		var color = archetypeColor( n.archetype );

		// Background.
		var grad = ctx.createLinearGradient( 0, 0, W, H );
		grad.addColorStop( 0, '#0c1426' );
		grad.addColorStop( 1, '#1c2944' );
		ctx.fillStyle = grad;
		ctx.fillRect( 0, 0, W, H );

		// Archetype color bar.
		ctx.fillStyle = color;
		ctx.fillRect( 0, 0, W, 8 );

		// Score ring (simple arc, not conic-gradient -- canvas 2D arcs are enough here).
		var cx = W - 100, cy = 100, r = 52;
		ctx.lineWidth = 10;
		ctx.strokeStyle = 'rgba(255,255,255,0.12)';
		ctx.beginPath(); ctx.arc( cx, cy, r, 0, Math.PI * 2 ); ctx.stroke();
		ctx.strokeStyle = '#c9a24b';
		ctx.beginPath();
		ctx.arc( cx, cy, r, -Math.PI / 2, ( -Math.PI / 2 ) + ( Math.PI * 2 * ( n.match_score / 100 ) ) );
		ctx.stroke();
		ctx.fillStyle = '#f6f1e6';
		ctx.font = '700 34px Georgia, serif';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.fillText( String( n.match_score ), cx, cy );

		// Title.
		ctx.textAlign = 'left';
		ctx.fillStyle = '#f6f1e6';
		ctx.font = '600 42px Georgia, serif';
		ctx.fillText( n.name, 48, 90 );

		ctx.fillStyle = '#e8cf86';
		ctx.font = '500 18px sans-serif';
		ctx.fillText( ( archetypeLabel( n.archetype ) || '' ).toUpperCase(), 48, 130 );

		// Why-fits lines.
		var fits = ( n.scoring && n.scoring.why_fits ) || [];
		ctx.fillStyle = '#67b389';
		ctx.font = '600 16px sans-serif';
		ctx.fillText( 'WHY THIS FITS', 48, 200 );
		ctx.fillStyle = '#cfc8b8';
		ctx.font = '400 16px sans-serif';
		var y = 230;
		fits.slice( 0, 3 ).forEach( function ( line ) {
			wrapCanvasText( ctx, '\u2713 ' + line, 48, y, W - 96, 22 );
			y += 48;
		} );

		// Footer.
		ctx.fillStyle = 'rgba(246,241,230,0.5)';
		ctx.font = '400 14px sans-serif';
		ctx.fillText( 'Matched with the Voyasee Where to Stay tool -- voyasee.com', 48, H - 32 );

		var link = document.createElement( 'a' );
		link.download = 'voyasee-' + ( n.slug || n.name || 'match' ).toString().toLowerCase().replace( /[^a-z0-9]+/g, '-' ) + '.png';
		link.href = canvas.toDataURL( 'image/png' );
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
	};

	/**
	 * Promote an "explored" neighborhood into the compare scorecard as an
	 * optional 4th column, re-rendering just that section in place.
	 */
	VoyaseeMatcher.prototype.addToCompare = function ( n ) {
		if ( ! this._lastMatches ) { return; }
		this.compareExtra = n;
		var container = this.container.querySelector( '[data-vwtsm-compare-scorecard]' );
		if ( container ) {
			container.innerHTML = this.buildCompareScorecard( this._lastMatches.concat( [ n ] ) );
		}
	};

	VoyaseeMatcher.prototype.buildCompareScorecard = function ( matches ) {
		// Both Walkability and Nightlife are read from the same OpenStreetMap
		// POI sync, so they share one confidence flag (scoring.confidence.walkability).
		var metrics = [
			{ label: 'Match Score', get: function ( n ) { return n.match_score || 0; } },
			{ label: 'Walkability', get: function ( n ) { return n.walkability_score || 0; }, confidenceKey: 'walkability' },
			{ label: 'Nightlife', get: function ( n ) { return n.nightlife_score || 0; }, confidenceKey: 'walkability' },
			{ label: 'Safety Comfort', get: function ( n ) { return ( n.safety_tier || 0 ) * 20; } },
		];

		var html = '<div class="vwtsm-compare-scorecard">';

		metrics.forEach( function ( metric ) {
			html += '<div class="vwtsm-compare-row-label">' + escapeHtml( metric.label ) + '</div><div class="vwtsm-compare-bars">';
			matches.forEach( function ( n ) {
				var confidence = ( n.scoring && n.scoring.confidence ) || {};
				var notSynced = metric.confidenceKey && false === confidence[ metric.confidenceKey ];
				var color = archetypeColor( n.archetype );
				if ( notSynced ) {
					// A dedicated 2-column layout (not the 3-column numeric
					// layout below) so the pending label gets the full
					// remaining row width instead of being squeezed into
					// the ~42px column sized for a 2-3 digit score.
					html += '<div class="vwtsm-compare-bar-track vwtsm-compare-bar-track-pending">' +
						'<span class="vwtsm-compare-bar-name">' + escapeHtml( n.name ) + '</span>' +
						'<span class="vwtsm-compare-bar-pending-label"><span class="vwtsm-confidence-dot"></span>' + escapeHtml( VWTSM.i18n.notSynced ) + '</span>' +
					'</div>';
					return;
				}
				var val = clamp( metric.get( n ), 0, 100 );
				html += '<div class="vwtsm-compare-bar-track">' +
					'<span class="vwtsm-compare-bar-name">' + escapeHtml( n.name ) + '</span>' +
					'<span class="vwtsm-compare-bar-bg"><span class="vwtsm-compare-bar-fill" style="width:' + val + '%;background:' + color + '"></span></span>' +
					'<span class="vwtsm-compare-bar-value">' + Math.round( val ) + '</span>' +
				'</div>';
			} );
			html += '</div>';
		} );

		html += '<div class="vwtsm-compare-row-label">Price &amp; Logistics</div><div class="vwtsm-compare-bars">';
		matches.forEach( function ( n ) {
			html += '<div class="vwtsm-compare-bar-track" style="grid-template-columns:110px 1fr">' +
				'<span class="vwtsm-compare-bar-name">' + escapeHtml( n.name ) + '</span>' +
				'<span class="vwtsm-compare-bar-value" style="text-align:left">' +
					'$'.repeat( n.price_band || 0 ) + ' &middot; Airport ' + ( n.time_airport_min || '?' ) + 'min &middot; Center ' + ( n.time_center_min || '?' ) + 'min' +
				'</span>' +
			'</div>';
		} );
		html += '</div>';

		html += '</div>';
		return html;
	};

	/* ---------------------------------------------------------------------
	 * Overview map
	 * ------------------------------------------------------------------- */

	/**
	 * Returns an empty placeholder container only -- the real map is built
	 * by initOverviewMap() afterwards, once this markup is actually
	 * attached to the document (Leaflet requires a live, sized DOM element
	 * to measure before it can draw tiles).
	 */
	VoyaseeMatcher.prototype.buildOverviewMap = function ( neighborhoods ) {
		if ( ! neighborhoods.length ) { return '<div class="vwtsm-overview-map"></div>'; }
		return '<div class="vwtsm-overview-map" data-vwtsm-overview-map></div>';
	};

	/**
	 * Draw a real, geographically accurate map (Leaflet + a free CARTO dark
	 * basemap, see class-wtsm-shortcode.php for the licensing note) with a
	 * pin and, where OpenStreetMap has one mapped, a real boundary polygon
	 * for each neighborhood. Falls back to the previous relative-position
	 * diagram if Leaflet failed to load or fails to initialize for any
	 * reason -- this mirrors the same defensive pattern already used for
	 * Chart.js (renderRadarChart) elsewhere in this file.
	 */
	VoyaseeMatcher.prototype.initOverviewMap = function ( neighborhoods ) {
		var container = this.container.querySelector( '[data-vwtsm-overview-map]' );
		if ( ! container || ! neighborhoods.length ) { return; }

		if ( typeof L === 'undefined' ) {
			console.error( 'Voyasee matcher: Leaflet did not load (likely blocked by an ad-blocker, content-security-policy, or a CDN network issue) -- showing the relative-position diagram instead.' );
			this.renderOverviewMapFallback( container, neighborhoods );
			return;
		}

		try {
			this.renderOverviewMapLeaflet( container, neighborhoods );
		} catch ( err ) {
			console.error( 'Voyasee matcher: the real map failed to initialize -- showing the relative-position diagram instead.', err );
			if ( this._leafletMap ) { try { this._leafletMap.remove(); } catch ( e2 ) {} this._leafletMap = null; }
			container.innerHTML = '';
			this.renderOverviewMapFallback( container, neighborhoods );
		}
	};

	VoyaseeMatcher.prototype.renderOverviewMapLeaflet = function ( container, neighborhoods ) {
		var map = L.map( container, {
			scrollWheelZoom: false, // don't trap page-scroll inside an embedded map
			attributionControl: true,
		} );

		L.tileLayer( 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener noreferrer">CARTO</a>',
			subdomains: 'abcd',
			maxZoom: 19,
		} ).addTo( map );

		var latLngs = neighborhoods.map( function ( n ) { return [ parseFloat( n.lat ), parseFloat( n.lng ) ]; } );
		var bounds = L.latLngBounds( latLngs );
		if ( latLngs.length === 1 || ! bounds.isValid() || bounds.getNorthEast().equals( bounds.getSouthWest() ) ) {
			map.setView( latLngs[ 0 ], 13 );
		} else {
			map.fitBounds( bounds, { padding: [ 34, 34 ], maxZoom: 15 } );
		}

		// Real neighbourhood boundary shapes where OpenStreetMap has them
		// mapped (honest best-effort -- coverage varies by city); areas
		// without one simply keep their pin only, nothing looks broken.
		neighborhoods.forEach( function ( n ) {
			if ( ! n.boundary_geojson ) { return; }
			try {
				var geo = JSON.parse( n.boundary_geojson );
				var color = archetypeColor( n.archetype );
				L.geoJSON( geo, {
					style: { color: color, weight: 1.5, fillColor: color, fillOpacity: 0.22 },
					interactive: false,
				} ).addTo( map );
			} catch ( e ) { /* malformed geometry -- skip silently, pin still shows */ }
		} );

		neighborhoods.forEach( function ( n ) {
			var color = archetypeColor( n.archetype );
			var icon = L.divIcon( {
				className: 'vwtsm-leaflet-pin-wrap',
				html: '<span class="vwtsm-overview-pin" style="position:static;transform:none;background:' + color + ';color:' + color + '"></span>',
				iconSize: [ 13, 13 ],
				iconAnchor: [ 7, 7 ],
			} );
			L.marker( [ parseFloat( n.lat ), parseFloat( n.lng ) ], { icon: icon, keyboard: false } )
				.bindTooltip( escapeHtml( n.name ), { permanent: true, direction: 'top', offset: [ 0, -4 ], className: 'vwtsm-leaflet-tooltip' } )
				.addTo( map );
		} );

		// A visitor has to click before scroll-wheel zoom activates, so
		// scrolling the results page past the map doesn't get hijacked.
		container.addEventListener( 'click', function () { map.scrollWheelZoom.enable(); }, { once: true } );

		this._leafletMap = map;
	};

	/** Previous relative-position diagram -- kept as an automatic fallback. */
	VoyaseeMatcher.prototype.renderOverviewMapFallback = function ( container, pts ) {
		container.classList.add( 'is-fallback' );

		var lats = pts.map( function ( n ) { return parseFloat( n.lat ); } );
		var lngs = pts.map( function ( n ) { return parseFloat( n.lng ); } );
		var minLat = Math.min.apply( null, lats ), maxLat = Math.max.apply( null, lats );
		var minLng = Math.min.apply( null, lngs ), maxLng = Math.max.apply( null, lngs );
		var latSpan = maxLat - minLat || 0.01, lngSpan = maxLng - minLng || 0.01;

		var project = function ( lat, lng ) {
			return {
				x: 12 + ( ( lng - minLng ) / lngSpan ) * 76,
				y: 12 + ( 1 - ( lat - minLat ) / latSpan ) * 76,
			};
		};

		var polygons = '';
		pts.forEach( function ( n ) {
			if ( ! n.boundary_geojson ) { return; }
			try {
				var geo = JSON.parse( n.boundary_geojson );
				var ring = geo.type === 'Polygon' ? geo.coordinates[0] : null;
				if ( ! ring || ring.length < 3 ) { return; }
				var color = archetypeColor( n.archetype );
				var points = ring.map( function ( coord ) {
					var p = project( coord[1], coord[0] );
					return p.x + ',' + p.y;
				} ).join( ' ' );
				polygons += '<polygon points="' + points + '" fill="' + color + '" fill-opacity="0.22" stroke="' + color + '" stroke-width="0.4" />';
			} catch ( e ) { /* malformed geometry -- skip silently, dot marker still shows */ }
		} );

		var svgOverlay = polygons
			? '<svg class="vwtsm-overview-polygons" viewBox="0 0 100 100" preserveAspectRatio="none">' + polygons + '</svg>'
			: '';

		// Neighborhoods with identical or near-identical coordinates would
		// otherwise stack exactly on top of each other, fully hiding all
		// but the last-painted pin. Detect collisions on a coarse grid and
		// fan any duplicates out in a small spiral so every pin stays
		// visible and clickable.
		var placedCells = {};
		var pins = pts.map( function ( n ) {
			var p = project( parseFloat( n.lat ), parseFloat( n.lng ) );
			var cellKey = Math.round( p.x / 3 ) + ',' + Math.round( p.y / 3 );
			var collision = placedCells[ cellKey ] || 0;
			placedCells[ cellKey ] = collision + 1;
			if ( collision > 0 ) {
				var angle = collision * 137.5 * ( Math.PI / 180 ); // golden-angle spiral
				var radius = 2.5 + collision * 1.2;
				p = {
					x: clamp( p.x + radius * Math.cos( angle ), 2, 98 ),
					y: clamp( p.y + radius * Math.sin( angle ), 2, 98 ),
				};
			}
			var color = archetypeColor( n.archetype );
			return (
				'<div class="vwtsm-overview-pin" style="left:' + p.x + '%;top:' + p.y + '%;background:' + color + ';color:' + color + '" title="' + escapeHtml( n.name ) + '"></div>' +
				'<div class="vwtsm-overview-pin-label" style="left:' + p.x + '%;top:' + p.y + '%">' + escapeHtml( n.name ) + '</div>'
			);
		} ).join( '' );

		container.innerHTML = svgOverlay + pins;
	};

	VoyaseeMatcher.prototype.buildOverviewLegend = function ( neighborhoods ) {
		var seen = {};
		neighborhoods.forEach( function ( n ) { seen[ n.archetype ] = true; } );
		var items = Object.keys( seen ).map( function ( arch ) {
			return '<span><span class="vwtsm-swatch" style="background:' + archetypeColor( arch ) + '"></span>' + escapeHtml( archetypeLabel( arch ) ) + '</span>';
		} ).join( '' );
		return '<div class="vwtsm-overview-legend">' + items + '</div>';
	};

	/* ---------------------------------------------------------------------
	 * Accordion
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.buildAccordionShell = function ( count ) {
		return (
			'<button type="button" class="vwtsm-accordion-toggle" data-vwtsm-accordion-toggle>' +
				'<span>' + escapeHtml( VWTSM.i18n.exploreMore ) + ' (' + count + ')</span><span>&darr;</span>' +
			'</button>' +
			'<div class="vwtsm-accordion-body" data-vwtsm-accordion-body></div>'
		);
	};

	VoyaseeMatcher.prototype.bindAccordion = function ( explored ) {
		var self = this;
		var toggle = this.container.querySelector( '[data-vwtsm-accordion-toggle]' );
		var body = this.container.querySelector( '[data-vwtsm-accordion-body]' );

		body.innerHTML = explored.map( function ( n, i ) {
			return (
				'<div class="vwtsm-accordion-row" data-explore-idx="' + i + '">' +
					'<span><span style="background:' + archetypeColor( n.archetype ) + ';display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px"></span>' + escapeHtml( n.name ) + '</span>' +
					'<span>' + n.match_score + ' / 100</span>' +
				'</div>'
			);
		} ).join( '' );

		toggle.addEventListener( 'click', function () { body.classList.toggle( 'is-open' ); } );

		body.querySelectorAll( '[data-explore-idx]' ).forEach( function ( row ) {
			row.addEventListener( 'click', function () {
				self.toggleAccordionDetail( row, explored[ parseInt( row.getAttribute( 'data-explore-idx' ), 10 ) ] );
			} );
		} );
	};

	VoyaseeMatcher.prototype.toggleAccordionDetail = function ( row, n ) {
		var next = row.nextElementSibling;
		if ( next && next.classList.contains( 'vwtsm-detail-panel' ) ) {
			if ( this.charts.accordion ) { this.charts.accordion.destroy(); this.charts.accordion = null; }
			next.remove();
			return;
		}

		var openPanel = row.parentElement.querySelector( '.vwtsm-detail-panel' );
		if ( openPanel ) {
			if ( this.charts.accordion ) { this.charts.accordion.destroy(); this.charts.accordion = null; }
			openPanel.remove();
		}

		var panel = document.createElement( 'div' );
		panel.className = 'vwtsm-detail-panel';
		panel.innerHTML = this.buildDetailPanelInner( n, true );
		row.insertAdjacentElement( 'afterend', panel );

		this.renderRadarChart( panel.querySelector( 'canvas' ), n, 'accordion' );
		this.bindDetailPanelActions( panel, n );
	};

	/* ---------------------------------------------------------------------
	 * Refine panel
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.buildRefinePanel = function () {
		return (
			'<div class="vwtsm-refine-panel" data-vwtsm-refine-panel>' +
				'<div class="vwtsm-field-group">' +
					'<span class="vwtsm-field-label">Safety comfort level</span>' +
					'<div class="vwtsm-slider-row"><span>Flexible</span>' +
						'<input type="range" min="1" max="5" value="' + this.answers.safety_comfort + '" class="vwtsm-slider" data-vwtsm-refine-safety />' +
						'<span>Very comfortable</span></div>' +
				'</div>' +
				'<button type="button" class="vwtsm-btn-primary" data-vwtsm-refine-submit>Update my matches</button>' +
			'</div>'
		);
	};

	VoyaseeMatcher.prototype.bindRefinePanel = function () {
		var self = this;
		var toggleBtn = this.container.querySelector( '[data-vwtsm-refine-toggle]' );
		var panel = this.container.querySelector( '[data-vwtsm-refine-panel]' );
		if ( ! toggleBtn || ! panel ) { return; }

		toggleBtn.addEventListener( 'click', function () { panel.classList.toggle( 'is-open' ); } );

		var safetySlider = panel.querySelector( '[data-vwtsm-refine-safety]' );
		panel.querySelector( '[data-vwtsm-refine-submit]' ).addEventListener( 'click', function () {
			self.answers.safety_comfort = parseInt( safetySlider.value, 10 );
			self.renderLoading();
			self.fetchMatch();
		} );
	};

	/* ---------------------------------------------------------------------
	 * Share link
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.bindShareButton = function () {
		var btn = this.container.querySelector( '[data-vwtsm-share]' );
		if ( ! btn ) { return; }
		btn.addEventListener( 'click', function () {
			var originalText = btn.textContent;
			var finish = function ( text ) {
				btn.textContent = text;
				setTimeout( function () { btn.textContent = originalText; }, 2000 );
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( window.location.href )
					.then( function () { finish( 'Link copied!' ); } )
					.catch( function () { finish( 'Copy failed -- copy from the address bar' ); } );
			} else {
				finish( 'Copy the link from the address bar' );
			}
		} );
	};

	/* ---------------------------------------------------------------------
	 * Split-stay suggestion
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.buildSplitStaySuggestion = function ( split ) {
		if ( ! split || ! split.primary || ! split.secondary ) { return ''; }
		var colorA = archetypeColor( split.primary.archetype );
		var colorB = archetypeColor( split.secondary.archetype );

		return (
			'<div class="vwtsm-split-stay">' +
				'<p class="vwtsm-eyebrow">Worth considering for a trip this long</p>' +
				'<h3 class="vwtsm-subheading">Split your stay instead of picking one</h3>' +
				'<div class="vwtsm-split-stay-bar">' +
					'<div class="vwtsm-split-stay-segment" style="flex-grow:' + split.primary_nights + ';background:' + colorA + '">' +
						'<span>' + escapeHtml( split.primary.name ) + '</span><small>' + split.primary_nights + ' nights</small>' +
					'</div>' +
					'<div class="vwtsm-split-stay-segment" style="flex-grow:' + split.secondary_nights + ';background:' + colorB + '">' +
						'<span>' + escapeHtml( split.secondary.name ) + '</span><small>' + split.secondary_nights + ' nights</small>' +
					'</div>' +
				'</div>' +
				'<p class="vwtsm-field-hint">' + escapeHtml( split.reason ) + '</p>' +
			'</div>'
		);
	};

	/* ---------------------------------------------------------------------
	 * Footer coverage counter (the rest of the footer is server-rendered)
	 * ------------------------------------------------------------------- */

	VoyaseeMatcher.prototype.loadCoverageStats = function () {
		var counter = this.root.querySelector( '[data-vwtsm-coverage-counter]' );
		var heroBadge = this.root.querySelector( '[data-vwtsm-badge-destinations]' );
		if ( ( ! counter && ! heroBadge ) || ! VWTSM.statsUrl ) { return; }
		fetch( VWTSM.statsUrl )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( counter ) {
					counter.textContent = data.neighborhoods + ' neighborhoods matched across ' + data.destinations + ' destinations';
				}
				if ( heroBadge ) {
					heroBadge.textContent = data.destinations + ' destinations · ' + data.neighborhoods + ' neighborhoods';
				}
			} )
			.catch( function () {
				if ( counter ) { counter.textContent = ''; }
				if ( heroBadge ) { heroBadge.textContent = ''; }
			} );
	};
} )();
