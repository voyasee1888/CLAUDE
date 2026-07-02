<?php
/**
 * Admin UI controller: registers the menu, handles form submissions
 * (add/edit/delete/import/sync), and hands off rendering to the view
 * templates in admin/views/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VNI_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_menu() {
		add_menu_page(
			__( 'Voyasee Where to Stay', 'voyasee-wtsm' ),
			__( 'Voyasee Where to Stay', 'voyasee-wtsm' ),
			'manage_options',
			'vni-destinations',
			array( $this, 'render_destinations_page' ),
			'dashicons-location-alt',
			58
		);

		add_submenu_page(
			'vni-destinations',
			__( 'Destinations', 'voyasee-ni' ),
			__( 'Destinations', 'voyasee-ni' ),
			'manage_options',
			'vni-destinations',
			array( $this, 'render_destinations_page' )
		);

		add_submenu_page(
			'vni-destinations',
			__( 'Neighborhoods', 'voyasee-ni' ),
			__( 'Neighborhoods', 'voyasee-ni' ),
			'manage_options',
			'vni-neighborhoods',
			array( $this, 'render_neighborhoods_page' )
		);

		add_submenu_page(
			'vni-destinations',
			__( 'Import CSV', 'voyasee-ni' ),
			__( 'Import CSV', 'voyasee-ni' ),
			'manage_options',
			'vni-import',
			array( $this, 'render_import_page' )
		);

		add_submenu_page(
			'vni-destinations',
			__( 'Sync Data', 'voyasee-ni' ),
			__( 'Sync Data', 'voyasee-ni' ),
			'manage_options',
			'vni-sync',
			array( $this, 'render_sync_page' )
		);

		add_submenu_page(
			'vni-destinations',
			__( 'Neighborhood Discovery', 'voyasee-wtsm' ),
			__( 'Neighborhood Discovery', 'voyasee-wtsm' ),
			'manage_options',
			'wtsm-discovery',
			array( $this, 'render_discovery_page' )
		);
	}

	public function maybe_enqueue_assets( $hook ) {
		$page = (string) ( $_GET['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_plugin_page = strpos( $hook, 'vni-' ) !== false || strpos( $page, 'vni-' ) !== false
			|| strpos( $hook, 'wtsm-' ) !== false || strpos( $page, 'wtsm-' ) !== false;
		if ( ! $is_plugin_page ) {
			return;
		}
		wp_enqueue_style( 'vni-admin', VNI_PLUGIN_URL . 'assets/css/admin.css', array(), VNI_VERSION );
		wp_enqueue_script( 'vni-admin', VNI_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), VNI_VERSION, true );
	}

	/* ------------------------------------------------------------------
	 * Destinations
	 * ------------------------------------------------------------------ */

	public function render_destinations_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'voyasee-ni' ) );
		}

		$action  = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice  = '';

		if ( 'delete' === $action && isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			check_admin_referer( 'vni_delete_destination_' . $id );
			VNI_Data::delete_destination( $id );
			$notice = __( 'Destination deleted.', 'voyasee-ni' );
			$action = 'list';
		}

		if ( isset( $_POST['vni_destination_nonce'] ) && wp_verify_nonce( $_POST['vni_destination_nonce'], 'vni_save_destination' ) ) {
			$this->handle_save_destination();
			$notice = __( 'Destination saved.', 'voyasee-ni' );
			$action = 'list';
		}

		if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
			$destination = null;
			if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$destination = VNI_Data::get_destination_by_id( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			$this->render_view( 'destination-edit', array( 'destination' => $destination, 'notice' => $notice ) );
			return;
		}

		$table = new VNI_List_Table_Destinations();
		$table->prepare_items();
		$this->render_view( 'destinations-list', array( 'table' => $table, 'notice' => $notice ) );
	}

	private function handle_save_destination() {
		check_admin_referer( 'vni_save_destination', 'vni_destination_nonce' );

		$data = array(
			'id'             => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
			'slug'           => sanitize_title( $_POST['slug'] ?? ( $_POST['name'] ?? '' ) ),
			'country'        => sanitize_text_field( $_POST['country'] ?? '' ),
			'country_code'   => sanitize_text_field( $_POST['country_code'] ?? '' ),
			'lat'            => (float) ( $_POST['lat'] ?? 0 ),
			'lng'            => (float) ( $_POST['lng'] ?? 0 ),
			'airport_name'   => sanitize_text_field( $_POST['airport_name'] ?? '' ),
			'airport_lat'    => (float) ( $_POST['airport_lat'] ?? 0 ),
			'airport_lng'    => (float) ( $_POST['airport_lng'] ?? 0 ),
			'tier'           => absint( $_POST['tier'] ?? 2 ),
			'timezone'       => sanitize_text_field( $_POST['timezone'] ?? '' ),
			'cost_index'     => absint( $_POST['cost_index'] ?? 3 ),
			'source_dataset' => sanitize_text_field( $_POST['source_dataset'] ?? '' ),
		);

		VNI_Data::upsert_destination( $data );
	}

	/* ------------------------------------------------------------------
	 * Neighborhoods
	 * ------------------------------------------------------------------ */

	public function render_neighborhoods_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'voyasee-ni' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = '';

		if ( 'delete' === $action && isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			check_admin_referer( 'vni_delete_neighborhood_' . $id );
			VNI_Data::delete_neighborhood( $id );
			$notice = __( 'Neighborhood deleted.', 'voyasee-ni' );
			$action = 'list';
		}

		if ( isset( $_POST['vni_neighborhood_nonce'] ) && wp_verify_nonce( $_POST['vni_neighborhood_nonce'], 'vni_save_neighborhood' ) ) {
			$this->handle_save_neighborhood();
			$notice = __( 'Neighborhood saved.', 'voyasee-ni' );
			$action = 'list';
		}

		if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
			$neighborhood = null;
			if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$neighborhood = VNI_Data::get_neighborhood( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			$destinations = VNI_Data::list_destinations( array( 'per_page' => 500 ) );
			$this->render_view(
				'neighborhood-edit',
				array(
					'neighborhood' => $neighborhood,
					'destinations' => $destinations,
					'notice'       => $notice,
				)
			);
			return;
		}

		$table = new VNI_List_Table_Neighborhoods();
		$table->prepare_items();
		$this->render_view( 'neighborhoods-list', array( 'table' => $table, 'notice' => $notice ) );
	}

	private function handle_save_neighborhood() {
		check_admin_referer( 'vni_save_neighborhood', 'vni_neighborhood_nonce' );

		$data = array(
			'id'                  => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'destination_id'      => absint( $_POST['destination_id'] ?? 0 ),
			'name'                => sanitize_text_field( $_POST['name'] ?? '' ),
			'slug'                => sanitize_title( $_POST['slug'] ?? ( $_POST['name'] ?? '' ) ),
			'archetype'           => sanitize_key( $_POST['archetype'] ?? 'residential_quiet' ),
			'lat'                 => (float) ( $_POST['lat'] ?? 0 ),
			'lng'                 => (float) ( $_POST['lng'] ?? 0 ),
			'price_band'          => absint( $_POST['price_band'] ?? 3 ),
			'safety_tier'         => absint( $_POST['safety_tier'] ?? 3 ),
			'family_suitability'  => absint( $_POST['family_suitability'] ?? 50 ),
			'solo_suitability'    => absint( $_POST['solo_suitability'] ?? 50 ),
			'distance_center_km'  => (float) ( $_POST['distance_center_km'] ?? 0 ),
			'time_center_min'     => absint( $_POST['time_center_min'] ?? 0 ),
			'distance_airport_km' => (float) ( $_POST['distance_airport_km'] ?? 0 ),
			'time_airport_min'    => absint( $_POST['time_airport_min'] ?? 0 ),
			'best_for'            => sanitize_textarea_field( $_POST['best_for'] ?? '' ),
			'why_fits'            => sanitize_textarea_field( $_POST['why_fits'] ?? '' ),
			'why_caution'         => sanitize_textarea_field( $_POST['why_caution'] ?? '' ),
			'local_tip'           => sanitize_textarea_field( $_POST['local_tip'] ?? '' ),
			'hero_image_url'      => sanitize_text_field( $_POST['hero_image_url'] ?? '' ),
			'hero_image_credit'   => sanitize_text_field( $_POST['hero_image_credit'] ?? '' ),
			'data_tier'           => absint( $_POST['data_tier'] ?? 2 ),
			'last_reviewed'       => sanitize_text_field( $_POST['last_reviewed'] ?? '' ),
		);

		// The textarea fields above use comma-separated input in the form;
		// VNI_Data::encode_list() (called internally by upsert) already
		// handles comma-split strings, so no extra processing needed here.

		VNI_Data::upsert_neighborhood( $data );
	}

	/* ------------------------------------------------------------------
	 * Import
	 * ------------------------------------------------------------------ */

	public function render_import_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'voyasee-ni' ) );
		}

		$reseed_result = null;

		if ( isset( $_POST['vni_reseed_nonce'] ) && wp_verify_nonce( $_POST['vni_reseed_nonce'], 'vni_reseed_starter' ) ) {
			global $wpdb;
			$dest_table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
			$nb_table   = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

			$dest_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$dest_table}" );
			$nb_before   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$nb_table}" );

			$dest_csv = VNI_PLUGIN_DIR . 'sample-data/sample-destinations.csv';
			$nb_csv   = VNI_PLUGIN_DIR . 'sample-data/sample-neighborhoods.csv';

			if ( file_exists( $dest_csv ) ) {
				VNI_CSV::import_destinations( $dest_csv );
			}
			if ( file_exists( $nb_csv ) ) {
				VNI_CSV::import_neighborhoods( $nb_csv );
			}

			$dest_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$dest_table}" );
			$nb_after   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$nb_table}" );

			$reseed_result = array(
				'dest_imported' => max( 0, $dest_after - $dest_before ),
				'nb_imported'   => max( 0, $nb_after - $nb_before ),
			);
		}

		$result = null;
		$type   = '';

		if ( isset( $_POST['vni_import_nonce'] ) && wp_verify_nonce( $_POST['vni_import_nonce'], 'vni_import' ) ) {
			$type = sanitize_key( $_POST['import_type'] ?? '' );

			if ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
				$tmp_name = $_FILES['csv_file']['tmp_name'];
				if ( 'destinations' === $type ) {
					$result = VNI_CSV::import_destinations( $tmp_name );
				} elseif ( 'neighborhoods' === $type ) {
					$result = VNI_CSV::import_neighborhoods( $tmp_name );
				}
			} else {
				$result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array( __( 'No file uploaded.', 'voyasee-ni' ) ) );
			}
		}

		$geonames_result = null;

		if ( isset( $_POST['wtsm_geonames_nonce'] ) && wp_verify_nonce( $_POST['wtsm_geonames_nonce'], 'wtsm_geonames_import' ) ) {
			$raw   = wp_unslash( $_POST['geonames_names'] ?? '' );
			$names = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
			if ( ! empty( $names ) ) {
				$geonames_result = WTSM_GeoNames::instance()->bulk_import( $names );
			}
		}

		$this->render_view( 'import', array( 'result' => $result, 'type' => $type, 'geonames_result' => $geonames_result, 'reseed_result' => $reseed_result ) );
	}

	/* ------------------------------------------------------------------
	 * Sync
	 * ------------------------------------------------------------------ */

	public function render_sync_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'voyasee-ni' ) );
		}

		$processed = null;

		if ( isset( $_POST['vni_sync_nonce'] ) && wp_verify_nonce( $_POST['vni_sync_nonce'], 'vni_sync_now' ) ) {
			$processed = VNI_OSM_Sync::instance()->run_batch( true );
		}

		$photo_result = null;

		if ( isset( $_POST['wtsm_photo_sync_nonce'] ) && wp_verify_nonce( $_POST['wtsm_photo_sync_nonce'], 'wtsm_photo_sync_now' ) ) {
			$photo_result = WTSM_Photo_Sync::instance()->run_batch( false );
		}

		$boundary_result = null;

		if ( isset( $_POST['wtsm_boundary_sync_nonce'] ) && wp_verify_nonce( $_POST['wtsm_boundary_sync_nonce'], 'wtsm_boundary_sync_now' ) ) {
			$boundary_result = WTSM_Boundary_Sync::instance()->run_batch( false );
		}

		$landmark_result = null;

		if ( isset( $_POST['wtsm_landmark_sync_nonce'] ) && wp_verify_nonce( $_POST['wtsm_landmark_sync_nonce'], 'wtsm_landmark_sync_now' ) ) {
			$landmark_result = WTSM_Landmark_Sync::instance()->run_batch( false );
		}

		global $wpdb;
		$table               = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		$total               = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$never               = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE poi_last_synced IS NULL" );
		$next_cron           = wp_next_scheduled( VNI_OSM_Sync::CRON_HOOK );
		$missing_photos      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE hero_image_url = '' OR hero_image_url IS NULL" );
		$with_boundary       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE boundary_geojson IS NOT NULL" );
		$boundary_unattempted = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE boundary_last_synced IS NULL" );
		$missing_landmarks   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE landmarks_last_synced IS NULL" );

		$this->render_view(
			'sync',
			array(
				'processed'            => $processed,
				'total'                => $total,
				'never'                => $never,
				'next_cron'            => $next_cron,
				'photo_result'         => $photo_result,
				'missing_photos'       => $missing_photos,
				'boundary_result'      => $boundary_result,
				'with_boundary'        => $with_boundary,
				'boundary_unattempted' => $boundary_unattempted,
				'landmark_result'      => $landmark_result,
				'missing_landmarks'    => $missing_landmarks,
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Neighborhood Discovery (OSM-assisted, draft review queue)
	 * ------------------------------------------------------------------ */

	public function render_discovery_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'voyasee-wtsm' ) );
		}

		$discovery_result = null;

		if ( isset( $_POST['wtsm_discovery_nonce'] ) && wp_verify_nonce( $_POST['wtsm_discovery_nonce'], 'wtsm_discovery_run' ) ) {
			$destination_id   = absint( $_POST['destination_id'] ?? 0 );
			$discovery_result = WTSM_Neighborhood_Discovery::instance()->discover_for_destination( $destination_id );
		}

		if ( isset( $_GET['action'], $_GET['id'] ) && 'publish' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = absint( $_GET['id'] );
			check_admin_referer( 'wtsm_publish_draft_' . $id );
			VNI_Data::publish_neighborhood( $id );
		}

		if ( isset( $_GET['action'], $_GET['id'] ) && 'discard' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = absint( $_GET['id'] );
			check_admin_referer( 'wtsm_discard_draft_' . $id );
			VNI_Data::delete_neighborhood( $id );
		}

		$destinations = VNI_Data::list_destinations( array( 'per_page' => 500 ) );
		$drafts       = VNI_Data::list_draft_neighborhoods();

		$this->render_view(
			'neighborhood-discovery',
			array(
				'destinations'      => $destinations,
				'drafts'            => $drafts,
				'discovery_result'  => $discovery_result,
			)
		);
	}

	/* ------------------------------------------------------------------
	 * View loader
	 * ------------------------------------------------------------------ */

	private function render_view( $view, $args = array() ) {
		$path = VNI_PLUGIN_DIR . 'admin/views/' . $view . '.php';
		if ( ! file_exists( $path ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( sprintf( 'View not found: %s', $view ) ) . '</p></div>';
			return;
		}
		// Extract args into local scope for the view template.
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract
		include $path;
	}
}
