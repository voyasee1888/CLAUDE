<?php
/**
 * Database schema for Voyasee Neighborhood Intelligence.
 *
 * Two tables only, by design (see blueprint):
 *  - voyasee_ni_destinations  : the city/destination backbone
 *  - voyasee_ni_neighborhoods : neighborhoods/zones within a destination,
 *                               including cached POI-density scores.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VNI_DB {

	/**
	 * Create or upgrade the plugin's tables. Safe to call on every
	 * activation and on version-mismatch (dbDelta is idempotent).
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$destinations    = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$neighborhoods   = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		$sql = "CREATE TABLE {$destinations} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(191) NOT NULL,
			name VARCHAR(191) NOT NULL,
			country VARCHAR(191) NOT NULL DEFAULT '',
			country_code CHAR(2) NOT NULL DEFAULT '',
			seasonal_note TEXT NULL,
			lat DECIMAL(10,6) NOT NULL DEFAULT 0,
			lng DECIMAL(10,6) NOT NULL DEFAULT 0,
			airport_name VARCHAR(191) NOT NULL DEFAULT '',
			airport_lat DECIMAL(10,6) NOT NULL DEFAULT 0,
			airport_lng DECIMAL(10,6) NOT NULL DEFAULT 0,
			tier TINYINT UNSIGNED NOT NULL DEFAULT 2,
			timezone VARCHAR(64) NOT NULL DEFAULT '',
			cost_index TINYINT UNSIGNED NOT NULL DEFAULT 3,
			source_dataset VARCHAR(191) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY name (name),
			KEY tier (tier)
		) {$charset_collate};";

		$sql2 = "CREATE TABLE {$neighborhoods} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			destination_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(191) NOT NULL,
			slug VARCHAR(191) NOT NULL,
			archetype VARCHAR(32) NOT NULL DEFAULT 'residential_quiet',
			lat DECIMAL(10,6) NOT NULL DEFAULT 0,
			lng DECIMAL(10,6) NOT NULL DEFAULT 0,
			price_band TINYINT UNSIGNED NOT NULL DEFAULT 3,
			safety_tier TINYINT UNSIGNED NOT NULL DEFAULT 3,
			family_suitability TINYINT UNSIGNED NOT NULL DEFAULT 50,
			solo_suitability TINYINT UNSIGNED NOT NULL DEFAULT 50,
			distance_center_km DECIMAL(6,2) NOT NULL DEFAULT 0,
			time_center_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			distance_airport_km DECIMAL(6,2) NOT NULL DEFAULT 0,
			time_airport_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			best_for TEXT NULL,
			why_fits TEXT NULL,
			why_caution TEXT NULL,
			local_tip TEXT NULL,
			hero_image_url VARCHAR(500) NOT NULL DEFAULT '',
			hero_image_credit VARCHAR(255) NOT NULL DEFAULT '',
			photo_last_synced DATETIME NULL,
			data_tier TINYINT UNSIGNED NOT NULL DEFAULT 2,
			poi_restaurant_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			poi_bar_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			poi_attraction_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			poi_transit_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			walkability_score TINYINT UNSIGNED NOT NULL DEFAULT 50,
			nightlife_score TINYINT UNSIGNED NOT NULL DEFAULT 50,
			transit_score TINYINT UNSIGNED NOT NULL DEFAULT 50,
			poi_last_synced DATETIME NULL,
			poi_supermarket_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			poi_pharmacy_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			poi_cafe_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			poi_park_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			convenience_score TINYINT UNSIGNED NOT NULL DEFAULT 50,
			boundary_geojson LONGTEXT NULL,
			boundary_last_synced DATETIME NULL,
			nearby_landmarks TEXT NULL,
			landmarks_last_synced DATETIME NULL,
			discovery_status VARCHAR(20) NOT NULL DEFAULT 'published',
			discovery_source VARCHAR(50) NOT NULL DEFAULT '',
			last_reviewed DATE NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY destination_id (destination_id),
			KEY archetype (archetype),
			KEY discovery_status (discovery_status),
			UNIQUE KEY dest_slug (destination_id, slug)
		) {$charset_collate};";

		dbDelta( $sql );
		dbDelta( $sql2 );

		update_option( 'vni_db_version', VNI_DB_VERSION );

		self::maybe_seed_starter_data();

		// Best-effort, idempotent backfill for destinations that have a
		// free-text country name but no ISO code yet (either pre-4.0 rows,
		// or newly imported ones that didn't set it) -- cheap to re-run on
		// every activation since it only touches rows with an empty
		// country_code.
		if ( ! class_exists( 'WTSM_Country_Codes' ) ) {
			require_once VNI_PLUGIN_DIR . 'includes/class-wtsm-country-codes.php';
		}
		WTSM_Country_Codes::backfill_destination_codes();
	}

	/**
	 * Auto-load the bundled starter dataset (200 destinations / 641
	 * neighborhoods) the first time the plugin activates, so there is no
	 * manual CSV-import step required to see the tool working. Only runs
	 * if the destinations table is currently empty -- safe to call again
	 * on every activation/upgrade without duplicating or overwriting any
	 * data you've since added or edited yourself.
	 */
	public static function maybe_seed_starter_data() {
		global $wpdb;

		$destinations = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$existing      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$destinations}" );

		if ( $existing > 0 ) {
			return; // Already has data (either seeded before, or your own data) -- never overwrite.
		}

		$dest_csv = VNI_PLUGIN_DIR . 'sample-data/sample-destinations.csv';
		$nb_csv   = VNI_PLUGIN_DIR . 'sample-data/sample-neighborhoods.csv';

		if ( ! file_exists( $dest_csv ) || ! file_exists( $nb_csv ) ) {
			return;
		}

		if ( ! class_exists( 'VNI_CSV' ) ) {
			require_once VNI_PLUGIN_DIR . 'includes/class-vni-csv.php';
		}

		VNI_CSV::import_destinations( $dest_csv );
		VNI_CSV::import_neighborhoods( $nb_csv );

		update_option( 'vni_starter_data_seeded', current_time( 'mysql' ) );
	}
}
