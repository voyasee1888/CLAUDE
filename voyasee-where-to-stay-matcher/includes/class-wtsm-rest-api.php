<?php
/**
 * REST API for Voyasee Where to Stay Matcher.
 *
 * Namespace: voyasee-wtsm/v1
 *
 * POST /match  -- body: { destination_slug, ...quiz answers } -> top matches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_REST_API {

	const NAMESPACE_ = 'voyasee-wtsm/v1';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_,
			'/match',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_match' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle_match( WP_REST_Request $request ) {
		$slug = sanitize_title( $request->get_param( 'destination_slug' ) );
		if ( '' === $slug ) {
			return new WP_REST_Response( array( 'message' => __( 'destination_slug is required.', 'voyasee-wtsm' ) ), 400 );
		}

		$destination = voyasee_ni_get_destination( $slug );
		if ( ! $destination ) {
			return new WP_REST_Response( array( 'message' => __( 'Destination not found.', 'voyasee-wtsm' ) ), 404 );
		}

		$answers = $this->sanitize_answers( $request->get_json_params() ?: $request->get_params() );

		$engine = new WTSM_Matching_Engine();
		$result = $engine->match( $destination, $answers );

		if ( empty( $result['matches'] ) ) {
			return new WP_REST_Response(
				array(
					'destination' => $destination,
					'matches'     => array(),
					'explored'    => array(),
					'message'     => __( 'We don\'t have neighborhood data for this destination yet.', 'voyasee-wtsm' ),
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'destination' => $destination,
				'matches'     => $result['matches'],
				'explored'    => $result['explored'],
				'data_tier'   => $result['data_tier'],
				'split_stay'  => $result['split_stay'],
			),
			200
		);
	}

	private function sanitize_answers( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();

		$interests = array();
		if ( ! empty( $raw['interests'] ) && is_array( $raw['interests'] ) ) {
			$allowed_interests = array( 'historic', 'food_nightlife', 'museums_culture', 'beach', 'shopping', 'family_activities' );
			foreach ( $raw['interests'] as $interest ) {
				$interest = sanitize_key( $interest );
				if ( in_array( $interest, $allowed_interests, true ) ) {
					$interests[] = $interest;
				}
			}
		}

		$allowed_traveler_types = array( 'solo', 'couple', 'family', 'group', 'business' );
		$traveler_type          = sanitize_key( $raw['traveler_type'] ?? 'solo' );
		if ( ! in_array( $traveler_type, $allowed_traveler_types, true ) ) {
			$traveler_type = 'solo';
		}

		$allowed_luggage = array( 'low', 'medium', 'high' );
		$luggage         = sanitize_key( $raw['luggage_amount'] ?? 'medium' );
		if ( ! in_array( $luggage, $allowed_luggage, true ) ) {
			$luggage = 'medium';
		}

		return array(
			'nights'                  => isset( $raw['nights'] ) ? absint( $raw['nights'] ) : 0,
			'traveler_type'           => $traveler_type,
			'first_visit'             => ! empty( $raw['first_visit'] ),
			'vibe_slider'             => isset( $raw['vibe_slider'] ) ? max( 0, min( 100, (int) $raw['vibe_slider'] ) ) : 50,
			'walkability_importance'  => ! empty( $raw['walkability_importance'] ),
			'budget_band'             => isset( $raw['budget_band'] ) ? max( 1, min( 5, (int) $raw['budget_band'] ) ) : 3,
			'public_transport_reliance' => ! empty( $raw['public_transport_reliance'] ),
			'early_departure'        => ! empty( $raw['early_departure'] ),
			'luggage_amount'          => $luggage,
			'accessibility_needs'     => ! empty( $raw['accessibility_needs'] ),
			'interests'               => $interests,
			'safety_comfort'          => isset( $raw['safety_comfort'] ) ? max( 1, min( 5, (int) $raw['safety_comfort'] ) ) : 3,
		);
	}
}
