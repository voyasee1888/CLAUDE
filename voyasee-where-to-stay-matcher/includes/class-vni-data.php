<?php
/**
 * Data access layer. Every query against the VNI tables should go through
 * this class so the rest of the plugin (and other Voyasee tools) never
 * writes raw SQL against table names directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VNI_Data {

	/**
	 * Fields that are stored as JSON in the DB but should be exposed as
	 * arrays everywhere else.
	 */
	private static $json_fields = array( 'best_for', 'why_fits', 'why_caution' );

	/* ------------------------------------------------------------------
	 * Destinations
	 * ------------------------------------------------------------------ */

	public static function get_destination_by_slug( $slug ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", sanitize_title( $slug ) ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function get_destination_by_id( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function search_destinations( $term, $limit = 10 ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$like  = '%' . $wpdb->esc_like( $term ) . '%';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, slug, name, country, tier FROM {$table}
				 WHERE name LIKE %s OR country LIKE %s
				 ORDER BY tier ASC, name ASC
				 LIMIT %d",
				$like,
				$like,
				absint( $limit )
			),
			ARRAY_A
		);
	}

	public static function list_destinations( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;

		$defaults = array(
			'tier'     => 0, // 0 = any
			'orderby'  => 'name',
			'order'    => 'ASC',
			'per_page' => 50,
			'page'     => 1,
			'search'   => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( $args['tier'] > 0 ) {
			$where[]  = 'tier = %d';
			$params[] = absint( $args['tier'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(name LIKE %s OR country LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$orderby = in_array( $args['orderby'], array( 'name', 'tier', 'country', 'id' ), true ) ? $args['orderby'] : 'name';
		$order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		$offset = max( 0, ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] ) );

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = absint( $args['per_page'] );
		$params[] = $offset;

		$prepared = $wpdb->prepare( $sql, $params );
		return $wpdb->get_results( $prepared, ARRAY_A );
	}

	public static function count_destinations( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['tier'] ) ) {
			$where[]  = 'tier = %d';
			$params[] = absint( $args['tier'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(name LIKE %s OR country LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}
		return (int) $wpdb->get_var( $sql );
	}

	public static function upsert_destination( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;

		$row = array(
			'slug'           => sanitize_title( $data['slug'] ?? $data['name'] ),
			'name'           => sanitize_text_field( $data['name'] ?? '' ),
			'country'        => sanitize_text_field( $data['country'] ?? '' ),
			'lat'            => isset( $data['lat'] ) ? (float) $data['lat'] : 0,
			'lng'            => isset( $data['lng'] ) ? (float) $data['lng'] : 0,
			'airport_name'   => sanitize_text_field( $data['airport_name'] ?? '' ),
			'airport_lat'    => isset( $data['airport_lat'] ) ? (float) $data['airport_lat'] : 0,
			'airport_lng'    => isset( $data['airport_lng'] ) ? (float) $data['airport_lng'] : 0,
			'tier'           => isset( $data['tier'] ) ? absint( $data['tier'] ) : 2,
			'timezone'       => sanitize_text_field( $data['timezone'] ?? '' ),
			'cost_index'     => isset( $data['cost_index'] ) ? self::clamp( $data['cost_index'], 1, 5 ) : 3,
			'source_dataset' => sanitize_text_field( $data['source_dataset'] ?? '' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$existing = self::get_destination_by_slug( $row['slug'] );

		if ( $existing ) {
			$wpdb->update( $table, $row, array( 'id' => $existing['id'] ) );
			return (int) $existing['id'];
		}

		$row['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	public static function delete_destination( $id ) {
		global $wpdb;
		$dest_table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;
		$nb_table   = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		// Cascade: remove neighborhoods first (no FK constraints by design,
		// to keep dbDelta happy across MySQL/MariaDB versions).
		$wpdb->delete( $nb_table, array( 'destination_id' => absint( $id ) ) );
		return $wpdb->delete( $dest_table, array( 'id' => absint( $id ) ) );
	}

	/* ------------------------------------------------------------------
	 * Neighborhoods
	 * ------------------------------------------------------------------ */

	public static function get_neighborhood( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);
		return $row ? self::decode_json_fields( $row ) : null;
	}

	public static function get_neighborhoods_for_destination( $destination_id ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE destination_id = %d ORDER BY name ASC",
				absint( $destination_id )
			),
			ARRAY_A
		);
		return array_map( array( __CLASS__, 'decode_json_fields' ), $rows );
	}

	public static function list_neighborhoods( $args = array() ) {
		global $wpdb;
		$nb_table   = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		$dest_table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;

		$defaults = array(
			'destination_id' => 0,
			'search'         => '',
			'per_page'       => 50,
			'page'           => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['destination_id'] ) ) {
			$where[]  = 'n.destination_id = %d';
			$params[] = absint( $args['destination_id'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'n.name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$offset = max( 0, ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] ) );

		$sql = "SELECT n.*, d.name AS destination_name, d.slug AS destination_slug
				FROM {$nb_table} n
				LEFT JOIN {$dest_table} d ON d.id = n.destination_id
				WHERE " . implode( ' AND ', $where ) . "
				ORDER BY d.name ASC, n.name ASC
				LIMIT %d OFFSET %d";

		$params[] = absint( $args['per_page'] );
		$params[] = $offset;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		return array_map( array( __CLASS__, 'decode_json_fields' ), $rows );
	}

	public static function count_neighborhoods( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['destination_id'] ) ) {
			$where[]  = 'destination_id = %d';
			$params[] = absint( $args['destination_id'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}
		return (int) $wpdb->get_var( $sql );
	}

	public static function upsert_neighborhood( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		$slug = sanitize_title( $data['slug'] ?? $data['name'] );

		$row = array(
			'destination_id'      => absint( $data['destination_id'] ?? 0 ),
			'name'                => sanitize_text_field( $data['name'] ?? '' ),
			'slug'                => $slug,
			'archetype'           => self::sanitize_archetype( $data['archetype'] ?? 'residential_quiet' ),
			'lat'                 => isset( $data['lat'] ) ? (float) $data['lat'] : 0,
			'lng'                 => isset( $data['lng'] ) ? (float) $data['lng'] : 0,
			'price_band'          => self::clamp( $data['price_band'] ?? 3, 1, 5 ),
			'safety_tier'         => self::clamp( $data['safety_tier'] ?? 3, 1, 5 ),
			'family_suitability'  => self::clamp( $data['family_suitability'] ?? 50, 0, 100 ),
			'solo_suitability'    => self::clamp( $data['solo_suitability'] ?? 50, 0, 100 ),
			'distance_center_km'  => isset( $data['distance_center_km'] ) ? (float) $data['distance_center_km'] : 0,
			'time_center_min'     => absint( $data['time_center_min'] ?? 0 ),
			'distance_airport_km' => isset( $data['distance_airport_km'] ) ? (float) $data['distance_airport_km'] : 0,
			'time_airport_min'    => absint( $data['time_airport_min'] ?? 0 ),
			'best_for'            => self::encode_list( $data['best_for'] ?? array() ),
			'why_fits'            => self::encode_list( $data['why_fits'] ?? array() ),
			'why_caution'         => self::encode_list( $data['why_caution'] ?? array() ),
			'local_tip'           => sanitize_textarea_field( $data['local_tip'] ?? '' ),
			'hero_image_url'      => sanitize_text_field( $data['hero_image_url'] ?? '' ),
			'hero_image_credit'   => sanitize_text_field( $data['hero_image_credit'] ?? '' ),
			'data_tier'           => self::clamp( $data['data_tier'] ?? 2, 1, 3 ),
			'last_reviewed'       => ! empty( $data['last_reviewed'] ) ? sanitize_text_field( $data['last_reviewed'] ) : null,
			'updated_at'          => current_time( 'mysql' ),
		);

		$existing = null;
		if ( ! empty( $data['id'] ) ) {
			$existing = self::get_neighborhood( $data['id'] );
		} else {
			$existing_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE destination_id = %d AND slug = %s",
					$row['destination_id'],
					$slug
				),
				ARRAY_A
			);
			$existing = $existing_row ?: null;
		}

		if ( $existing ) {
			$wpdb->update( $table, $row, array( 'id' => $existing['id'] ) );
			return (int) $existing['id'];
		}

		$row['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	public static function delete_neighborhood( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		return $wpdb->delete( $table, array( 'id' => absint( $id ) ) );
	}

	/**
	 * Update only the cached POI/score fields for a neighborhood (called by
	 * the OSM sync job). Kept separate from upsert_neighborhood() so the
	 * sync job never has to know about/clobber editorial fields.
	 */
	public static function update_poi_scores( $id, $scores ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		$wpdb->update(
			$table,
			array(
				'poi_restaurant_count' => absint( $scores['restaurant_count'] ?? 0 ),
				'poi_bar_count'        => absint( $scores['bar_count'] ?? 0 ),
				'poi_attraction_count' => absint( $scores['attraction_count'] ?? 0 ),
				'poi_transit_count'    => absint( $scores['transit_count'] ?? 0 ),
				'walkability_score'    => self::clamp( $scores['walkability_score'] ?? 50, 0, 100 ),
				'nightlife_score'      => self::clamp( $scores['nightlife_score'] ?? 50, 0, 100 ),
				'transit_score'        => self::clamp( $scores['transit_score'] ?? 50, 0, 100 ),
				'poi_last_synced'      => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) )
		);
	}

	/**
	 * Store a real neighbourhood boundary polygon (GeoJSON) fetched from
	 * OpenStreetMap, so the frontend map can draw the actual shape instead
	 * of a point + fixed radius. Kept separate from upsert_neighborhood()
	 * for the same reason as update_poi_scores() -- this is written by an
	 * automated sync job, not the admin editorial form.
	 *
	 * @param int         $id
	 * @param string|null $geojson_string Valid GeoJSON geometry as a JSON string, or null to clear.
	 */
	public static function update_boundary( $id, $geojson_string ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		$wpdb->update(
			$table,
			array(
				'boundary_geojson'     => $geojson_string, // Already validated JSON from the sync job; stored as-is.
				'boundary_last_synced' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) )
		);
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private static function decode_json_fields( $row ) {
		if ( ! is_array( $row ) ) {
			return $row;
		}
		foreach ( self::$json_fields as $field ) {
			if ( isset( $row[ $field ] ) ) {
				$decoded        = json_decode( $row[ $field ], true );
				$row[ $field ]  = is_array( $decoded ) ? $decoded : array();
			}
		}
		return $row;
	}

	private static function encode_list( $value ) {
		if ( is_string( $value ) ) {
			// Allow comma-separated input from simple admin form fields.
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}
		if ( ! is_array( $value ) ) {
			$value = array();
		}
		$value = array_map( 'sanitize_text_field', $value );
		return wp_json_encode( array_values( $value ) );
	}

	private static function clamp( $value, $min, $max ) {
		$value = (int) $value;
		return max( $min, min( $max, $value ) );
	}

	public static function sanitize_archetype( $archetype ) {
		$allowed = array(
			'historic',
			'beach',
			'nightlife',
			'business',
			'residential_quiet',
			'family_suburban',
			'budget_backpacker',
			'luxury',
			'airport_transit',
		);
		$archetype = sanitize_key( $archetype );
		return in_array( $archetype, $allowed, true ) ? $archetype : 'residential_quiet';
	}

	public static function archetype_labels() {
		return array(
			'historic'           => __( 'Historic / Old Town', 'voyasee-ni' ),
			'beach'              => __( 'Beach / Waterfront', 'voyasee-ni' ),
			'nightlife'          => __( 'Nightlife / Entertainment', 'voyasee-ni' ),
			'business'           => __( 'Business / Financial', 'voyasee-ni' ),
			'residential_quiet'  => __( 'Residential / Quiet', 'voyasee-ni' ),
			'family_suburban'    => __( 'Family / Suburban', 'voyasee-ni' ),
			'budget_backpacker'  => __( 'Budget / Backpacker', 'voyasee-ni' ),
			'luxury'             => __( 'Luxury', 'voyasee-ni' ),
			'airport_transit'    => __( 'Airport / Transit Hub', 'voyasee-ni' ),
		);
	}
}
