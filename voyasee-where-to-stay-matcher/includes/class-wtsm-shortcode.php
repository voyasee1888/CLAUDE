<?php
/**
 * WTSM_Shortcode
 *
 * Registers [voyasee_where_to_stay] -- the single entry point for the
 * whole tool (data + matching + UI all live in this one plugin now).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Shortcode {

	private static $instance = null;
	private static $rendered_once = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register() {
		add_shortcode( 'voyasee_where_to_stay', array( $this, 'render' ) );

		register_block_type(
			'voyasee/where-to-stay-matcher',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	public function render( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'destination' => '',
			),
			$atts
		);

		// Look up the real destination name server-side so a landing page
		// like [voyasee_where_to_stay destination="tokyo"] can pre-fill
		// both the slug AND a proper display name -- without this, Step 1
		// shows an empty destination field and the Continue button never
		// enables, even though the slug was technically set.
		$prefill_name = '';
		if ( ! empty( $atts['destination'] ) && function_exists( 'voyasee_ni_get_destination' ) ) {
			$dest = voyasee_ni_get_destination( sanitize_title( $atts['destination'] ) );
			if ( $dest ) {
				$prefill_name = $dest['name'];
			}
		}
		$atts['prefill_name'] = $prefill_name;

		$this->enqueue_assets();

		ob_start();
		include WTSM_PLUGIN_DIR . 'templates/quiz-container.php';
		return ob_get_clean();
	}

	private function enqueue_assets() {
		wp_enqueue_style(
			'vwtsm-fonts',
			'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:wght@400;500;700&display=swap',
			array(),
			null
		);

		// Leaflet (MIT license) + a free, no-API-key CARTO "Dark Matter" basemap
		// (OSM data, CC-BY/BSD-3, free for any use with attribution -- see
		// https://carto.com/basemaps) gives the overview map a real,
		// geographically accurate map instead of an abstract dot grid. We
		// deliberately don't call tile.openstreetmap.org's raster tiles
		// directly -- that service's usage policy forbids "heavy use" from a
		// redistributed plugin/app, whereas CARTO's basemap CDN is built for
		// exactly this kind of embedding. If either CDN fails to load (an
		// ad-blocker, offline admin preview, restrictive CSP), the frontend
		// falls back to the previous relative-position diagram automatically.
		wp_enqueue_style( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true );

		wp_enqueue_style( 'vwtsm-matcher', WTSM_PLUGIN_URL . 'assets/css/matcher.css', array( 'leaflet' ), WTSM_VERSION );

		wp_enqueue_script( 'chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js', array(), '4.4.4', true );

		wp_enqueue_script( 'vwtsm-matcher', WTSM_PLUGIN_URL . 'assets/js/matcher.js', array( 'chart-js', 'leaflet' ), WTSM_VERSION, true );

		wp_localize_script(
			'vwtsm-matcher',
			'VWTSM',
			array(
				'restUrl'         => rest_url( 'voyasee-wtsm/v1/match' ),
				'destinationsUrl' => rest_url( 'voyasee-wtsm/v1/destinations' ),
				'statsUrl'        => rest_url( 'voyasee-wtsm/v1/stats' ),
				'reportIssueUrl'  => rest_url( 'voyasee-wtsm/v1/report-issue' ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'bookingUrl'      => WTSM_Settings::get( 'booking_affiliate_url', '' ),
				'jetlagPlannerUrl' => WTSM_Settings::get( 'tool_jetlag_planner_url', '' ),
				'archetypeLabels' => array(
					'historic'           => __( 'Historic / Old Town', 'voyasee-wtsm' ),
					'beach'              => __( 'Beach / Waterfront', 'voyasee-wtsm' ),
					'nightlife'          => __( 'Nightlife / Entertainment', 'voyasee-wtsm' ),
					'business'           => __( 'Business / Financial', 'voyasee-wtsm' ),
					'residential_quiet'  => __( 'Residential / Quiet', 'voyasee-wtsm' ),
					'family_suburban'    => __( 'Family / Suburban', 'voyasee-wtsm' ),
					'budget_backpacker'  => __( 'Budget / Backpacker', 'voyasee-wtsm' ),
					'luxury'             => __( 'Luxury', 'voyasee-wtsm' ),
					'airport_transit'    => __( 'Airport / Transit Hub', 'voyasee-wtsm' ),
				),
				'i18n' => array(
					'step1Title'   => __( 'Where and when are you headed?', 'voyasee-wtsm' ),
					'step2Title'   => __( 'What matters most for this trip?', 'voyasee-wtsm' ),
					'continue'     => __( 'Continue', 'voyasee-wtsm' ),
					'showMatches'  => __( 'Show My Matches', 'voyasee-wtsm' ),
					'refine'       => __( 'Refine my match', 'voyasee-wtsm' ),
					'whyFits'      => __( 'Why this fits', 'voyasee-wtsm' ),
					'whyCaution'   => __( 'Why this may not fit', 'voyasee-wtsm' ),
					'bookOn'       => __( 'Booking.com', 'voyasee-wtsm' ),
					'noData'       => __( 'We don\'t have detailed neighborhood data for this destination yet. Try one of our more deeply covered destinations, or check back soon.', 'voyasee-wtsm' ),
					'exploreMore'  => __( 'Explore other neighborhoods', 'voyasee-wtsm' ),
					'compareTable' => __( 'Compare your top matches', 'voyasee-wtsm' ),
					'loading'      => __( 'Finding your best matches...', 'voyasee-wtsm' ),
					'estimateTooltip' => __( 'Includes an estimated walkability/nightlife score -- OpenStreetMap sync hasn\'t run for this area yet.', 'voyasee-wtsm' ),
					'notSynced'    => __( 'Not synced yet', 'voyasee-wtsm' ),
				),
			)
		);
	}
}
