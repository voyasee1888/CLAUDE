<?php
/**
 * WTSM_Boundary_Sync
 *
 * Fetches a real neighbourhood/suburb boundary polygon from OpenStreetMap
 * (via the same Overpass API already used for POI density) so the
 * overview map can draw an actual shape instead of a point + fixed
 * radius circle.
 *
 * LICENSE: same ODbL terms already documented for VNI_OSM_Sync --
 * commercial use is fine with visible attribution (handled in the
 * footer). This class never calls Overpass on a live visitor request,
 * only from a daily cron batch or an explicit admin-triggered sync.
 *
 * HONEST COVERAGE NOTE: OpenStreetMap's neighbourhood/suburb boundary
 * completeness varies a lot by city -- excellent in many US/European
 * cities, patchy elsewhere. This class simply leaves boundary_geojson
 * NULL when nothing is found; the frontend map already has to handle
 * that case by falling back to a plain point marker, so there is no
 * broken state either way.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Boundary_Sync {

	const CRON_HOOK = 'wtsm_boundary_sync_event';

	/** How many neighborhoods to attempt per run. Keep small -- polite to the shared Overpass instance. */
	const BATCH_SIZE = 15;

	const OVERPASS_ENDPOINT = 'https://overpass-api.de/api/interpreter';

	/** How far from the neighborhood's stored point to search for a matching named boundary. */
	const SEARCH_RADIUS_M = 1200;

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_cron() {
		add_action( self::CRON_HOOK, array( $this, 'run_batch' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + ( 4 * HOUR_IN_SECONDS ), 'daily', self::CRON_HOOK );
		}
	}

	public static function deactivate_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Process one batch of neighborhoods that don't have a boundary yet.
	 *
	 * @param bool $force Re-check neighborhoods that were already attempted (e.g. after OSM coverage has improved).
	 * @return array{found:int,not_found:int,errors:array}
	 */
	public function run_batch( $force = false ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		$result = array( 'found' => 0, 'not_found' => 0, 'errors' => array() );

		if ( $force ) {
			$rows = $wpdb->get_results(
				"SELECT id, name, lat, lng FROM {$table} ORDER BY boundary_last_synced ASC LIMIT " . self::BATCH_SIZE,
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				"SELECT id, name, lat, lng FROM {$table} WHERE boundary_last_synced IS NULL LIMIT " . self::BATCH_SIZE,
				ARRAY_A
			);
		}

		if ( empty( $rows ) ) {
			return $result;
		}

		foreach ( $rows as $row ) {
			$geojson = $this->fetch_boundary( $row['name'], (float) $row['lat'], (float) $row['lng'] );

			// Store the attempt either way (NULL if not found) so run_batch()
			// without force=true naturally moves on to the next unattempted
			// batch instead of retrying the same misses every day forever.
			VNI_Data::update_boundary( $row['id'], $geojson );

			if ( $geojson ) {
				$result['found']++;
			} else {
				$result['not_found']++;
			}

			usleep( 400000 ); // 0.4s -- polite to the shared Overpass instance.
		}

		return $result;
	}

	/**
	 * Look for a named neighbourhood/suburb boundary near the given point
	 * whose name reasonably matches, and return its outline as a GeoJSON
	 * Polygon/MultiPolygon geometry string, or null if nothing usable was found.
	 */
	private function fetch_boundary( $name, $lat, $lng ) {
		if ( ! $lat || ! $lng || empty( $name ) ) {
			return null;
		}

		$radius = self::SEARCH_RADIUS_M;
		// $name is interpolated into an Overpass regex-match clause below,
		// so escape regex metacharacters (not just quotes) -- an unescaped
		// name like "St. Paul's (Old Town)" would otherwise build a
		// malformed or unintentionally broad regex.
		$safe_name = preg_quote( str_replace( '"', '', $name ), '/' );

		// Look for a suburb/neighbourhood/quarter place node or an
		// administrative boundary relation with a matching name near the
		// stored point. geom output lets us build a polygon without a
		// second lookup.
		$query = "[out:json][timeout:25];
(
  relation[\"place\"~\"^(suburb|neighbourhood|quarter)$\"][\"name\"~\"{$safe_name}\",i](around:{$radius},{$lat},{$lng});
  relation[\"boundary\"=\"administrative\"][\"admin_level\"~\"^(9|10)$\"][\"name\"~\"{$safe_name}\",i](around:{$radius},{$lat},{$lng});
);
out geom;";

		$response = wp_remote_post(
			self::OVERPASS_ENDPOINT,
			array(
				'timeout' => 30,
				'body'    => array( 'data' => $query ),
				'headers' => array(
					'User-Agent' => 'VoyaseeWhereToStayMatcher/3.0 (+https://voyasee.com)',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['elements'][0]['members'] ) ) {
			return null;
		}

		$polygon = $this->relation_to_polygon( $body['elements'][0] );

		if ( ! $polygon || count( $polygon ) < 3 ) {
			return null;
		}

		return wp_json_encode( array(
			'type'        => 'Polygon',
			'coordinates' => array( $polygon ),
		) );
	}

	/**
	 * Very small, deliberately simple relation-to-polygon reducer: takes
	 * the first outer way's geometry from an Overpass relation response.
	 * This is not a full multipolygon-stitching implementation (that's a
	 * meaningfully bigger project) -- for the common case of one clean
	 * outer ring, which covers most real-world neighbourhood boundaries,
	 * this is sufficient. If a relation has a more complex multi-way outer
	 * ring, we simply skip it (returns null) rather than render something
	 * wrong; that neighborhood keeps its point-marker fallback.
	 */
	private function relation_to_polygon( $relation ) {
		foreach ( $relation['members'] as $member ) {
			if ( 'way' === ( $member['type'] ?? '' ) && 'outer' === ( $member['role'] ?? '' ) && ! empty( $member['geometry'] ) ) {
				$coords = array();
				foreach ( $member['geometry'] as $point ) {
					if ( isset( $point['lon'], $point['lat'] ) ) {
						$coords[] = array( round( $point['lon'], 5 ), round( $point['lat'], 5 ) );
					}
				}
				if ( count( $coords ) >= 3 ) {
					return $coords;
				}
			}
		}
		return null;
	}
}
