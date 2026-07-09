<?php
/**
 * WTSM_Matching_Engine
 *
 * Transparent, weighted-rule scoring -- deliberately NOT a black-box ML
 * model, so every score is explainable in the UI. Mirrors the "Reality
 * Score" / "Trip Compression Warning" philosophy used elsewhere in the
 * Voyasee tool suite: tell the traveler what's actually true, not just
 * what sounds nice.
 *
 * Depends on Voyasee Neighborhood Intelligence for data (soft dependency
 * via function_exists checks -- this plugin never queries its tables
 * directly).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Matching_Engine {

	/** Default dimension weights. Sum to 1.0. */
	private $weights = array(
		'budget'      => 0.20,
		'vibe'        => 0.18,
		'attractions' => 0.18,
		'walkability' => 0.14,
		'airport'     => 0.10,
		'suitability' => 0.10,
		'safety'      => 0.10,
	);

	/** Archetype -> "vibe" position on a quiet(0) <-> lively(100) scale. */
	private $archetype_vibe = array(
		'residential_quiet' => 15,
		'family_suburban'   => 20,
		'historic'          => 40,
		'airport_transit'   => 30,
		'beach'             => 50,
		'business'          => 50,
		'luxury'            => 55,
		'budget_backpacker' => 60,
		'nightlife'         => 90,
	);

	/** Interest tag -> archetypes it naturally fits, used for attraction-fit scoring. */
	private $interest_archetype_affinity = array(
		'historic'          => array( 'historic' => 100, 'luxury' => 40, 'residential_quiet' => 20 ),
		'food_nightlife'    => array( 'nightlife' => 100, 'business' => 50, 'budget_backpacker' => 60 ),
		'museums_culture'   => array( 'historic' => 90, 'luxury' => 50, 'business' => 40 ),
		'beach'             => array( 'beach' => 100 ),
		'shopping'          => array( 'luxury' => 100, 'business' => 70, 'nightlife' => 50 ),
		'family_activities' => array( 'family_suburban' => 100, 'historic' => 50, 'residential_quiet' => 40 ),
	);

	/**
	 * Run the match for a destination given Step 1 + Step 2 answers.
	 *
	 * @param array $destination Row from voyasee_ni_get_destination().
	 * @param array $answers     Sanitized quiz answers.
	 * @return array{matches:array,explored:array,data_tier:int,split_stay:array|null}
	 */
	public function match( $destination, $answers ) {
		$neighborhoods = function_exists( 'voyasee_ni_get_neighborhoods' )
			? voyasee_ni_get_neighborhoods( $destination['id'] )
			: array();

		if ( empty( $neighborhoods ) ) {
			return array( 'matches' => array(), 'explored' => array(), 'data_tier' => 0, 'split_stay' => null );
		}

		$scored = array();
		foreach ( $neighborhoods as $n ) {
			$scored[] = $this->score_neighborhood( $n, $answers, $destination );
		}

		usort( $scored, function ( $a, $b ) {
			return $b['match_score'] <=> $a['match_score'];
		} );

		$top3     = array_slice( $scored, 0, 3 );
		$explored = array_slice( $scored, 3 );

		// Data-tier confidence is a property of the destination's dataset
		// (see readme: Tier 1 = real named neighborhoods, Tier 2 = honest
		// generic zones), not of any one neighborhood row -- read it from
		// the destination itself rather than an arbitrary neighborhood.
		$data_tier = (int) ( $destination['tier'] ?? 2 );

		return array(
			'matches'    => $top3,
			'explored'   => $explored,
			'data_tier'  => $data_tier,
			'split_stay' => $this->maybe_suggest_split_stay( $top3, $answers ),
		);
	}

	/**
	 * Score one neighborhood against the answers. Returns the original
	 * neighborhood row plus a "scoring" sub-array with everything the UI
	 * needs to render the radar chart and explanations.
	 */
	private function score_neighborhood( $n, $answers, $destination = array() ) {
		$budget_fit      = $this->score_budget( $n, $answers, $destination );
		$vibe_fit        = $this->score_vibe( $n, $answers );
		$attraction_fit  = $this->score_attractions( $n, $answers );
		$walkability_fit = $this->score_walkability( $n, $answers );
		$airport_fit     = $this->score_airport( $n, $answers );
		$suitability_fit = $this->score_suitability( $n, $answers );
		$safety_fit      = $this->score_safety( $n, $answers );

		$weights = $this->effective_weights( $answers );

		$dims = array(
			'budget'      => $budget_fit,
			'vibe'        => $vibe_fit,
			'attractions' => $attraction_fit,
			'walkability' => $walkability_fit,
			'airport'     => $airport_fit,
			'suitability' => $suitability_fit,
			'safety'      => $safety_fit,
		);

		$total = 0;
		foreach ( $dims as $key => $score ) {
			$total += $score * $weights[ $key ];
		}

		$confidence = $this->dimension_confidence( $n, $answers );
		$persona    = $this->build_persona_label( $answers );

		$area_dna = $this->build_area_dna( $n );

		$poi_synced_flag = ! empty( $n['poi_last_synced'] );
		$poi_facts = $poi_synced_flag ? array(
			'restaurants'  => (int) ( $n['poi_restaurant_count'] ?? 0 ),
			'bars'         => (int) ( $n['poi_bar_count'] ?? 0 ),
			'cafes'        => (int) ( $n['poi_cafe_count'] ?? 0 ),
			'attractions'  => (int) ( $n['poi_attraction_count'] ?? 0 ),
			'transit'      => (int) ( $n['poi_transit_count'] ?? 0 ),
			'supermarkets' => (int) ( $n['poi_supermarket_count'] ?? 0 ),
			'pharmacies'   => (int) ( $n['poi_pharmacy_count'] ?? 0 ),
			'parks'        => (int) ( $n['poi_park_count'] ?? 0 ),
		) : null;

		$n['scoring'] = array(
			'match_score' => (int) round( $total ),
			'dimensions'  => $dims,
			'confidence'  => $confidence,
			'any_unsynced' => in_array( false, $confidence, true ),
			'why_fits'    => $this->build_why_fits( $n, $dims, $answers, $confidence ),
			'why_caution' => $this->build_why_caution( $n, $dims, $answers, $confidence ),
			'confidence_label'    => $this->confidence_label( $total ),
			'strong_factor_count' => $this->count_strong_factors( $dims, $confidence ),
			'persona'             => $persona,
			'narrative'           => $this->build_narrative( $n, $dims, $confidence, $answers, $persona ),
			'late_arrival_friendly' => (int) ( $n['time_airport_min'] ?? 999 ) <= 25 && (int) ( $n['safety_tier'] ?? 3 ) >= 3,
			'area_dna'    => $area_dna,
			'poi_facts'   => $poi_facts,
		);
		$n['match_score'] = $n['scoring']['match_score']; // convenience top-level for sort.

		return $n;
	}

	/**
	 * A short, qualitative label for the overall match_score -- purely a
	 * restatement of the number in words, not a second opinion.
	 */
	private function confidence_label( $total_score ) {
		$score = (int) round( $total_score );
		if ( $score >= 90 ) { return __( 'Excellent fit', 'voyasee-wtsm' ); }
		if ( $score >= 75 ) { return __( 'Strong fit', 'voyasee-wtsm' ); }
		if ( $score >= 60 ) { return __( 'Good fit', 'voyasee-wtsm' ); }
		return __( 'Fair fit', 'voyasee-wtsm' );
	}

	/**
	 * How many dimensions are genuinely strong (>=75) with confirmed data
	 * behind them -- an unsynced dimension landing high by coincidence of
	 * the neutral default doesn't count, same discipline as build_why_fits().
	 */
	private function count_strong_factors( $dims, $confidence ) {
		$count = 0;
		foreach ( $dims as $key => $val ) {
			if ( $val >= 75 && ( $confidence[ $key ] ?? true ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * A short "kind of traveler" descriptor built entirely from the
	 * traveler's own answers (traveler type + budget + vibe + interests +
	 * the walkability toggle) -- a plain-language restatement of inputs
	 * already collected, not an inferred/guessed classification.
	 */
	private function build_persona_label( $answers ) {
		$traits = array();

		$budget = (int) ( $answers['budget_band'] ?? 3 );
		if ( $budget <= 2 ) {
			$traits[] = __( 'budget-conscious', 'voyasee-wtsm' );
		} elseif ( $budget >= 4 ) {
			$traits[] = __( 'upscale', 'voyasee-wtsm' );
		}

		$vibe = (int) ( $answers['vibe_slider'] ?? 50 );
		if ( $vibe >= 65 ) {
			$traits[] = __( 'nightlife-seeking', 'voyasee-wtsm' );
		} elseif ( $vibe <= 35 ) {
			$traits[] = __( 'quiet-neighborhood-loving', 'voyasee-wtsm' );
		}

		$interests = is_array( $answers['interests'] ?? null ) ? $answers['interests'] : array();
		if ( in_array( 'food_nightlife', $interests, true ) ) {
			$traits[] = __( 'food-loving', 'voyasee-wtsm' );
		} elseif ( in_array( 'museums_culture', $interests, true ) || in_array( 'historic', $interests, true ) ) {
			$traits[] = __( 'culture-curious', 'voyasee-wtsm' );
		} elseif ( in_array( 'beach', $interests, true ) ) {
			$traits[] = __( 'beach-loving', 'voyasee-wtsm' );
		} elseif ( in_array( 'shopping', $interests, true ) ) {
			$traits[] = __( 'shopping-focused', 'voyasee-wtsm' );
		}

		if ( ! empty( $answers['walkability_importance'] ) ) {
			$traits[] = __( 'walkability-focused', 'voyasee-wtsm' );
		}

		$traveler_labels = array(
			'solo'     => __( 'solo traveler', 'voyasee-wtsm' ),
			'couple'   => __( 'couple', 'voyasee-wtsm' ),
			'family'   => __( 'family', 'voyasee-wtsm' ),
			'group'    => __( 'group', 'voyasee-wtsm' ),
			'business' => __( 'business traveler', 'voyasee-wtsm' ),
		);
		$traveler = $traveler_labels[ $answers['traveler_type'] ?? 'solo' ] ?? __( 'traveler', 'voyasee-wtsm' );

		// At most two descriptors, so this reads as a sentence rather than a tag dump.
		$traits = array_slice( $traits, 0, 2 );

		return $traits ? implode( ' ', $traits ) . ' ' . $traveler : $traveler;
	}

	/**
	 * One plain-language sentence explaining the single strongest reason
	 * this neighborhood scored where it did -- built entirely from the
	 * dimension scores and answers already computed, the same underlying
	 * facts the radar chart and why_fits list already show, just narrated
	 * as a sentence. Skips a dimension whose data isn't confirmed yet,
	 * same discipline as build_why_fits().
	 */
	private function build_narrative( $n, $dims, $confidence, $answers, $persona ) {
		$ranked = $dims;
		arsort( $ranked );

		$top_dim = null;
		foreach ( $ranked as $key => $val ) {
			if ( $confidence[ $key ] ?? true ) {
				$top_dim = $key;
				break;
			}
		}
		if ( null === $top_dim ) {
			return '';
		}

		$dim_phrases = array(
			'budget'      => __( 'fits the budget you selected', 'voyasee-wtsm' ),
			'vibe'        => __( 'matches the pace you\'re after', 'voyasee-wtsm' ),
			'attractions' => __( 'lines up with the interests you picked', 'voyasee-wtsm' ),
			'walkability' => __( 'is easy to get around on foot', 'voyasee-wtsm' ),
			'airport'     => __( 'keeps you close to the airport', 'voyasee-wtsm' ),
			'suitability' => __( 'suits how you\'re traveling', 'voyasee-wtsm' ),
			'safety'      => __( 'matches the safety comfort you asked for', 'voyasee-wtsm' ),
		);

		$nights = (int) ( $answers['nights'] ?? 0 );

		return sprintf(
			/* translators: 1: persona e.g. "budget-conscious couple", 2: nights, 3: neighborhood name, 4: reason phrase */
			__( 'As a %1$s planning %2$d night(s), %3$s %4$s -- the strongest reason it topped your matches.', 'voyasee-wtsm' ),
			$persona,
			$nights,
			$n['name'],
			$dim_phrases[ $top_dim ] ?? ''
		);
	}

	/**
	 * Per-dimension data confidence. Only walkability, and attractions when
	 * its POI blend was actually used (see score_attractions()), depend on
	 * the OpenStreetMap sync job; everything else comes from editorial
	 * admin fields and is always "confirmed".
	 *
	 * @return array<string,bool>
	 */
	private function dimension_confidence( $n, $answers ) {
		$poi_synced = ! empty( $n['poi_last_synced'] );
		$interests  = is_array( $answers['interests'] ?? null ) ? $answers['interests'] : array();

		$attractions_uses_poi = in_array( 'food_nightlife', $interests, true )
			|| in_array( 'museums_culture', $interests, true )
			|| in_array( 'historic', $interests, true );

		return array(
			'budget'      => true,
			'vibe'        => true,
			'attractions' => $poi_synced || ! $attractions_uses_poi,
			'walkability' => $poi_synced,
			'airport'     => true,
			'suitability' => true,
			'safety'      => true,
		);
	}

	/**
	 * Boost airport weight if early departure or heavy luggage is
	 * flagged, or if the trip is very short (a couple of nights makes
	 * transit friction proportionally more painful). Boost walkability
	 * if the traveler asked for it, or if the trip is long enough that
	 * daily local-life quality matters more than one-off transit ease.
	 */
	private function effective_weights( $answers ) {
		$weights = $this->weights;
		$nights  = (int) ( $answers['nights'] ?? 0 );

		if ( ! empty( $answers['early_departure'] ) || 'high' === ( $answers['luggage_amount'] ?? '' ) ) {
			$weights['airport'] += 0.10;
			$weights['vibe']    -= 0.05;
			$weights['budget']  -= 0.05;
		}

		if ( ! empty( $answers['walkability_importance'] ) ) {
			$weights['walkability'] += 0.10;
			$weights['attractions'] -= 0.05;
			$weights['suitability'] -= 0.05;
		}

		// Short trips: every hour lost to transit is a bigger share of the
		// whole trip, so airport ease matters proportionally more.
		if ( $nights > 0 && $nights <= 2 ) {
			$weights['airport']     += 0.08;
			$weights['walkability'] -= 0.04;
			$weights['attractions'] -= 0.04;
		}

		// Long trips: the traveler will live daily life in this
		// neighborhood far more than they'll shuttle to/from the airport,
		// so walkability matters proportionally more and airport ease less.
		// The shift is capped at whatever airport weight can actually give
		// up (floored at 0.02) so this always nets to zero and weights
		// keep summing to 1.0, regardless of what earlier answers already
		// did to the airport weight.
		if ( $nights >= 8 ) {
			$shift                   = min( 0.08, max( 0, $weights['airport'] - 0.02 ) );
			$weights['walkability'] += $shift;
			$weights['airport']     -= $shift;
		}

		return $weights;
	}

	private function score_budget( $n, $answers, $destination = array() ) {
		$user_budget = (int) ( $answers['budget_band'] ?? 3 );

		// Adjust the neighborhood's price band relative to how expensive
		// the destination is overall, so "$$$" reads as pricier in an
		// expensive destination than in a cheap one, rather than treating
		// every destination's 1-5 scale as directly comparable.
		$cost_index      = (int) ( $destination['cost_index'] ?? 3 );
		$effective_price = (float) $n['price_band'] + ( ( $cost_index - 3 ) * 0.5 );
		$effective_price = max( 1, min( 5, $effective_price ) );

		$diff = abs( $effective_price - $user_budget );
		return max( 0, 100 - ( $diff * 25 ) );
	}

	/**
	 * For trips of 8+ nights, check whether the top two matches are
	 * genuinely different enough (different archetype family, or a big
	 * vibe/attraction gap) that splitting the stay across both would beat
	 * compromising on one. Returns null when the trip is short, or when
	 * the top two matches are similar enough that a split wouldn't add
	 * real value -- this is deliberately conservative so the suggestion
	 * only appears when it's a genuine, defensible idea.
	 *
	 * @param array $top3
	 * @param array $answers
	 * @return array{primary:array,secondary:array,primary_nights:int,secondary_nights:int,reason:string}|null
	 */
	private function maybe_suggest_split_stay( $top3, $answers ) {
		$nights = (int) ( $answers['nights'] ?? 0 );

		if ( $nights < 8 || count( $top3 ) < 2 ) {
			return null;
		}

		$first  = $top3[0];
		$second = $top3[1];

		$vibe_gap = abs(
			( $first['scoring']['dimensions']['vibe'] ?? 50 ) - ( $second['scoring']['dimensions']['vibe'] ?? 50 )
		);
		$different_archetype = ( $first['archetype'] ?? '' ) !== ( $second['archetype'] ?? '' );
		$score_gap           = abs( ( $first['match_score'] ?? 0 ) - ( $second['match_score'] ?? 0 ) );

		// Only suggest a split when the two areas are meaningfully
		// different in character AND close enough in score that neither
		// is a clearly wrong compromise -- otherwise splitting just
		// dilutes a genuinely strong single match.
		if ( ! $different_archetype || $vibe_gap < 25 || $score_gap > 20 ) {
			return null;
		}

		// Weight the nights split toward the stronger match, in roughly
		// 60/40 proportions, with a floor of 2 nights per stop so neither
		// half feels rushed.
		$primary_nights   = max( 2, (int) round( $nights * 0.6 ) );
		$secondary_nights = max( 2, $nights - $primary_nights );

		return array(
			'primary'          => $first,
			'secondary'        => $second,
			'primary_nights'   => $primary_nights,
			'secondary_nights' => $secondary_nights,
			'reason'           => sprintf(
				/* translators: 1: first area name, 2: second area name */
				__( '%1$s and %2$s offer genuinely different experiences and score closely -- for a trip this long, splitting your stay can beat compromising on one.', 'voyasee-wtsm' ),
				$first['name'],
				$second['name']
			),
		);
	}

	private function score_vibe( $n, $answers ) {
		$slider     = (int) ( $answers['vibe_slider'] ?? 50 ); // 0 quiet -> 100 lively
		$archetype  = $n['archetype'];
		$vibe_value = $this->archetype_vibe[ $archetype ] ?? 50;
		$diff       = abs( $vibe_value - $slider );
		return max( 0, 100 - $diff );
	}

	private function score_attractions( $n, $answers ) {
		$interests = is_array( $answers['interests'] ?? null ) ? $answers['interests'] : array();
		if ( empty( $interests ) ) {
			return 60; // neutral when the user didn't specify interests.
		}

		$archetype = $n['archetype'];
		$total     = 0;
		$count     = 0;

		foreach ( $interests as $interest ) {
			$affinity = $this->interest_archetype_affinity[ $interest ][ $archetype ] ?? 25;
			$total   += $affinity;
			$count++;
		}

		$archetype_score = $count ? ( $total / $count ) : 60;

		// Blend in the live POI signal so two neighborhoods of the same
		// archetype aren't scored identically -- a "nightlife" zone with
		// genuinely more bars/restaurants nearby should edge out a thinner
		// one. Only blend real data: if this neighborhood's OSM POI sync
		// has never run, walkability_score/nightlife_score are just the
		// DB's neutral-50 default, not a real signal -- blending that in
		// would silently nudge the score without any actual data behind
		// it, so fall back to the pure archetype score instead.
		$poi_synced = ! empty( $n['poi_last_synced'] );
		$poi_blend  = $archetype_score;
		if ( $poi_synced && in_array( 'food_nightlife', $interests, true ) ) {
			$poi_blend = (int) ( $n['nightlife_score'] ?? 50 );
		} elseif ( $poi_synced && ( in_array( 'museums_culture', $interests, true ) || in_array( 'historic', $interests, true ) ) ) {
			$poi_blend = self::attraction_count_to_score( (int) ( $n['poi_attraction_count'] ?? 0 ) );
		}

		return (int) round( ( $archetype_score * 0.7 ) + ( $poi_blend * 0.3 ) );
	}

	private function score_walkability( $n, $answers ) {
		return (int) ( $n['walkability_score'] ?? 50 );
	}

	private function score_airport( $n, $answers ) {
		$minutes = (int) ( $n['time_airport_min'] ?? 60 );
		// 0 min -> 100, 90+ min -> floor near 10. Linear with a floor.
		$score = 100 - ( $minutes * ( 90 / 100 ) );
		return (int) max( 10, min( 100, round( $score ) ) );
	}

	private function score_suitability( $n, $answers ) {
		$traveler_type = $answers['traveler_type'] ?? 'solo';
		$family        = (int) ( $n['family_suitability'] ?? 50 );
		$solo          = (int) ( $n['solo_suitability'] ?? 50 );

		switch ( $traveler_type ) {
			case 'family':
				return $family;
			case 'solo':
				return $solo;
			case 'couple':
				$bonus = in_array( $n['archetype'], array( 'luxury', 'historic', 'beach' ), true ) ? 10 : 0;
				return (int) min( 100, round( ( $family + $solo ) / 2 ) + $bonus );
			case 'business':
				return 'business' === $n['archetype'] ? 95 : (int) round( ( $family + $solo ) / 2 );
			case 'group':
				return max( $family, $solo );
			default:
				return (int) round( ( $family + $solo ) / 2 );
		}
	}

	private function score_safety( $n, $answers ) {
		$desired = (int) ( $answers['safety_comfort'] ?? 3 ); // 1-5
		$actual  = (int) ( $n['safety_tier'] ?? 3 );
		if ( $actual >= $desired ) {
			$surplus = $actual - $desired;
			return min( 100, 85 + ( $surplus * 8 ) );
		}
		$gap = $desired - $actual;
		return max( 0, 60 - ( $gap * 25 ) );
	}

	/**
	 * Build the "why this fits" list: starts with curated editorial
	 * content from the dataset, then appends a dynamic line for the
	 * single strongest dimension if it isn't already covered.
	 */
	private function build_why_fits( $n, $dims, $answers, $confidence = array() ) {
		$lines = is_array( $n['why_fits'] ?? null ) ? $n['why_fits'] : array();

		arsort( $dims );
		$top_dim = array_key_first( $dims );
		$top_val = $dims[ $top_dim ];

		// Don't praise a dimension we don't actually have confirmed data
		// for yet -- an unsynced walkability/attractions score landing at
		// the top is a coincidence of the neutral default, not a real
		// strength worth claiming.
		if ( $top_val >= 80 && ( $confidence[ $top_dim ] ?? true ) ) {
			$dynamic = $this->dimension_strength_sentence( $top_dim, $n, $answers );
			if ( $dynamic ) {
				$lines[] = $dynamic;
			}
		}

		return array_values( array_unique( $lines ) );
	}

	/**
	 * Build the Wrong Area Warning: curated editorial cautions, plus one
	 * dynamically generated trade-off sentence for the weakest dimension.
	 * This is the tool's signature differentiating feature.
	 */
	private function build_why_caution( $n, $dims, $answers, $confidence = array() ) {
		$lines = is_array( $n['why_caution'] ?? null ) ? $n['why_caution'] : array();

		asort( $dims );
		$weak_dim = array_key_first( $dims );
		$weak_val = $dims[ $weak_dim ];

		if ( $weak_val < 60 ) {
			if ( ! ( $confidence[ $weak_dim ] ?? true ) ) {
				// Honest substitute: a neighborhood that's never had its
				// OpenStreetMap POI sync run scores a neutral default here,
				// which can coincidentally land below the 60 "weakness"
				// threshold -- that's a data gap, not a genuine downside,
				// so say so instead of implying we measured something weak.
				$lines[] = __( 'We don\'t have live walkability/nightlife data for this area yet -- treat it as unrated rather than weak, and check listings/reviews directly.', 'voyasee-wtsm' );
			} else {
				$dynamic = $this->dimension_weakness_sentence( $weak_dim, $n, $answers );
				if ( $dynamic ) {
					$lines[] = $dynamic;
				}
			}
		}

		// Honest transparency note: we don't yet have a verified
		// accessibility data source (see the v3 research brief), so
		// rather than silently ignoring this question or inventing a
		// score we can't back up, say so plainly.
		if ( ! empty( $answers['accessibility_needs'] ) ) {
			$lines[] = __( 'We don\'t have verified step-free/accessibility data for this area yet -- check specific listings and recent reviews before booking.', 'voyasee-wtsm' );
		}

		return array_values( array_unique( $lines ) );
	}

	private function dimension_strength_sentence( $dim, $n, $answers ) {
		switch ( $dim ) {
			case 'airport':
				return sprintf(
					/* translators: %d minutes */
					__( 'Only about %d minutes from the main airport, which matters for your trip.', 'voyasee-wtsm' ),
					(int) ( $n['time_airport_min'] ?? 0 )
				);
			case 'walkability':
				return __( 'Strong walkability based on nearby restaurant, attraction, and transit density.', 'voyasee-wtsm' );
			case 'budget':
				return __( 'Priced right in line with the budget you selected.', 'voyasee-wtsm' );
			case 'attractions':
				return __( 'A strong fit for the interests you picked, based on what this area is known for.', 'voyasee-wtsm' );
			case 'suitability':
				return sprintf(
					/* translators: %s traveler type, e.g. "family" */
					__( 'A well-suited area for %s travelers.', 'voyasee-wtsm' ),
					str_replace( '_', ' ', (string) ( $answers['traveler_type'] ?? 'this trip' ) )
				);
			default:
				return '';
		}
	}

	/**
	 * Convert a raw attraction POI count (museums, galleries, landmarks)
	 * into a 0–100 score using a log curve — 5 attractions ≈ 50,
	 * 20 ≈ 80, 40+ ≈ 95. Returns a neutral 40 for zero.
	 */
	private static function attraction_count_to_score( $count ) {
		if ( $count <= 0 ) {
			return 40;
		}
		return (int) min( 100, round( 30 + 30 * log( $count + 1, 5 ) ) );
	}

	/**
	 * Transparent "Area DNA" tags derived entirely from data we already
	 * have — a human-readable fingerprint of what this area is actually
	 * like, based on POI ratios, suitability scores, and distance data.
	 * No external source or guesswork; every tag maps directly to a
	 * verifiable fact in the dataset.
	 *
	 * @return array<array{tag:string,label:string}>
	 */
	public function build_area_dna( $n ) {
		$tags       = array();
		$poi_synced = ! empty( $n['poi_last_synced'] );

		if ( $poi_synced ) {
			if ( (int) ( $n['nightlife_score'] ?? 0 ) >= 65 && (int) ( $n['poi_bar_count'] ?? 0 ) >= 5 ) {
				$tags[] = array( 'tag' => 'late-night-friendly', 'label' => __( 'Late-night friendly', 'voyasee-wtsm' ) );
			}
			if ( (int) ( $n['walkability_score'] ?? 0 ) >= 70 && (int) ( $n['transit_score'] ?? 0 ) >= 60 ) {
				$tags[] = array( 'tag' => 'car-free-ready', 'label' => __( 'Great for car-free trips', 'voyasee-wtsm' ) );
			}
			if ( (int) ( $n['poi_restaurant_count'] ?? 0 ) >= 15 && (int) ( $n['poi_cafe_count'] ?? 0 ) >= 5 ) {
				$tags[] = array( 'tag' => 'foodie-area', 'label' => __( 'Foodie neighborhood', 'voyasee-wtsm' ) );
			}
			if ( (int) ( $n['poi_supermarket_count'] ?? 0 ) >= 2 && (int) ( $n['poi_pharmacy_count'] ?? 0 ) >= 1 ) {
				$tags[] = array( 'tag' => 'essentials-nearby', 'label' => __( 'Daily essentials on-site', 'voyasee-wtsm' ) );
			}
			if ( (int) ( $n['poi_park_count'] ?? 0 ) >= 3 ) {
				$tags[] = array( 'tag' => 'green-space', 'label' => __( 'Parks nearby', 'voyasee-wtsm' ) );
			}
		}

		if ( (int) ( $n['family_suitability'] ?? 0 ) >= 70 && (int) ( $n['safety_tier'] ?? 0 ) >= 4 ) {
			$tags[] = array( 'tag' => 'family-ready', 'label' => __( 'Family-ready area', 'voyasee-wtsm' ) );
		}
		if ( (int) ( $n['time_airport_min'] ?? 999 ) <= 20 ) {
			$tags[] = array( 'tag' => 'airport-close', 'label' => __( 'Quick airport access', 'voyasee-wtsm' ) );
		}
		if ( (int) ( $n['time_center_min'] ?? 999 ) <= 10 ) {
			$tags[] = array( 'tag' => 'central', 'label' => __( 'Central location', 'voyasee-wtsm' ) );
		}
		if ( (int) ( $n['safety_tier'] ?? 0 ) >= 5 ) {
			$tags[] = array( 'tag' => 'high-safety', 'label' => __( 'High safety comfort', 'voyasee-wtsm' ) );
		}

		return array_slice( $tags, 0, 5 );
	}

	private function dimension_weakness_sentence( $dim, $n, $answers ) {
		switch ( $dim ) {
			case 'airport':
				return sprintf(
					/* translators: %d minutes */
					__( 'Roughly %d minutes from the main airport -- worth factoring in if you have an early flight or heavy luggage.', 'voyasee-wtsm' ),
					(int) ( $n['time_airport_min'] ?? 0 )
				);
			case 'budget':
				$diff = (int) $n['price_band'] - (int) ( $answers['budget_band'] ?? 3 );
				return $diff > 0
					? __( 'Runs more expensive than the budget you selected.', 'voyasee-wtsm' )
					: __( 'Priced noticeably lower than the area\'s typical going rate -- check what\'s included.', 'voyasee-wtsm' );
			case 'vibe':
				$slider = (int) ( $answers['vibe_slider'] ?? 50 );
				return $slider > 60
					? __( 'Quieter than the lively vibe you said you wanted.', 'voyasee-wtsm' )
					: __( 'Livelier/noisier at night than the quiet vibe you said you wanted.', 'voyasee-wtsm' );
			case 'walkability':
				return __( 'Lower walkability -- you may rely more on taxis or transit here than in other matches.', 'voyasee-wtsm' );
			case 'safety':
				return __( 'Below the safety comfort level you selected -- treat this as general guidance, not a verified statistic, and use normal travel precautions.', 'voyasee-wtsm' );
			default:
				return '';
		}
	}
}
