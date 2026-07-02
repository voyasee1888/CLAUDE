<?php
/**
 * REST API for Voyasee Neighborhood Intelligence.
 *
 * Namespace: voyasee-wtsm/v1
 *
 * GET  /destinations?search=tok&limit=10
 * GET  /destinations/{slug}
 * GET  /destinations/{slug}/neighborhoods
 * POST /sync   (manage_options only) -- trigger an immediate OSM batch sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VNI_REST_API {

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
			'/destinations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_destinations' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'search' => array( 'type' => 'string', 'default' => '' ),
					'limit'  => array( 'type' => 'integer', 'default' => 10 ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/destinations/(?P<slug>[a-z0-9\-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_destination' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/destinations/(?P<slug>[a-z0-9\-]+)/neighborhoods',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_neighborhoods' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/sync',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'trigger_sync' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	public function get_destinations( WP_REST_Request $request ) {
		$search = sanitize_text_field( $request->get_param( 'search' ) );
		$limit  = min( 50, max( 1, (int) $request->get_param( 'limit' ) ) );

		if ( '' === $search ) {
			return new WP_REST_Response( array( 'destinations' => array() ), 200 );
		}

		$results = VNI_Data::search_destinations( $search, $limit );
		return new WP_REST_Response( array( 'destinations' => $results ), 200 );
	}

	public function get_destination( WP_REST_Request $request ) {
		$slug = sanitize_title( $request->get_param( 'slug' ) );
		$dest = VNI_Data::get_destination_by_slug( $slug );

		if ( ! $dest ) {
			return new WP_REST_Response( array( 'message' => 'Destination not found' ), 404 );
		}

		return new WP_REST_Response( $dest, 200 );
	}

	public function get_neighborhoods( WP_REST_Request $request ) {
		$slug = sanitize_title( $request->get_param( 'slug' ) );
		$dest = VNI_Data::get_destination_by_slug( $slug );

		if ( ! $dest ) {
			return new WP_REST_Response( array( 'message' => 'Destination not found' ), 404 );
		}

		$neighborhoods = VNI_Data::get_neighborhoods_for_destination( $dest['id'] );

		return new WP_REST_Response(
			array(
				'destination'   => $dest,
				'neighborhoods' => $neighborhoods,
			),
			200
		);
	}

	public function get_stats( WP_REST_Request $request ) {
		global $wpdb;
		$dest_table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$nb_table   = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		return new WP_REST_Response(
			array(
				'destinations'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$dest_table}" ),
				'neighborhoods' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$nb_table}" ),
			),
			200
		);
	}

	public function trigger_sync( WP_REST_Request $request ) {
		$processed = VNI_OSM_Sync::instance()->run_batch( true );
		return new WP_REST_Response( array( 'processed' => $processed ), 200 );
	}
}
