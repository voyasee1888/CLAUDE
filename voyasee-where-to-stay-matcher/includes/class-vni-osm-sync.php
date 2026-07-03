<?php
/**
 * VNI_OSM_Sync
 *
 * Pulls POI-density signals from the OpenStreetMap Overpass API and turns
 * them into walkability / nightlife / transit proxy scores for each
 * neighborhood.
 *
 * LICENSE NOTE: OpenStreetMap data is ODbL-licensed. Commercial use is
 * explicitly permitted; the only requirement is visible attribution to
 * OpenStreetMap wherever the data (or anything derived from it) is shown
 * to the public. The Best Area to Stay Finder plugin's footer/result page
 * is responsible for rendering that attribution -- see its README.
 *
 * IMPORTANT: Overpass is a shared community resource, not a commercial
 * CDN. This class deliberately NEVER calls Overpass on a live visitor
 * request. It only runs on a daily cron, processing a small batch of
 * neighborhoods at a time, with a polite delay between requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VNI_OSM_Sync {

	const CRON_HOOK = 'vni_osm_sync_event';

	/** How many neighborhoods to refresh per cron run. Keep this small. */
	const BATCH_SIZE = 25;

	/** Re-sync a neighborhood at most this often. */
	const SYNC_INTERVAL_DAYS = 30;

	/** Overpass public endpoint. A self-hosted instance is preferable at scale. */
	const OVERPASS_ENDPOINT = 'https://overpass-api.de/api/interpreter';

	/** Search radius around the neighborhood's lat/lng, in meters. */
	const SEARCH_RADIUS_M = 800;

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
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function deactivate_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Process one batch of stale neighborhoods. Hooked to the daily cron.
	 * Also callable manually from the admin "Sync now" button.
	 */
	public function run_batch( $force = false ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		if ( $force ) {
			$rows = $wpdb->get_results(
				"SELECT id, lat, lng FROM {$table} ORDER BY poi_last_synced ASC LIMIT " . self::BATCH_SIZE,
				ARRAY_A
			);
		} else {
			$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( self::SYNC_INTERVAL_DAYS * DAY_IN_SECONDS ) );
			$rows   = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, lat, lng FROM {$table}
					 WHERE poi_last_synced IS NULL OR poi_last_synced < %s
					 ORDER BY poi_last_synced ASC
					 LIMIT %d",
					$cutoff,
					self::BATCH_SIZE
				),
				ARRAY_A
			);
		}

		if ( empty( $rows ) ) {
			return 0;
		}

		$processed = 0;
		foreach ( $rows as $row ) {
			$counts = $this->fetch_poi_counts( (float) $row['lat'], (float) $row['lng'] );
			if ( null === $counts ) {
				continue; // Network/API failure -- leave it stale, try again next run.
			}
			$scores = $this->counts_to_scores( $counts );
			VNI_Data::update_poi_scores( $row['id'], array_merge( $counts, $scores ) );
			$processed++;

			// Be polite to the shared Overpass instance.
			usleep( 500000 ); // 0.5s
		}

		return $processed;
	}

	/**
	 * Query Overpass for POI counts in a radius around a point: four small
	 * calls, one per category, each using "out count;" so each response is
	 * a single tiny JSON object rather than a full POI dump -- small
	 * payloads, kinder to the shared Overpass instance than fetching full
	 * node data.
	 *
	 * @return array{restaurant_count:int,bar_count:int,attraction_count:int,transit_count:int}|null
	 *         Null if the point is invalid, or if ANY category request
	 *         fails -- a partial result (e.g. 3 of 4 categories succeeded)
	 *         is deliberately treated as a full failure rather than saved
	 *         with the failed categories silently zeroed out, since a
	 *         confidently-wrong "0" is worse than staying unsynced and
	 *         retrying next batch.
	 */
	private function fetch_poi_counts( $lat, $lng ) {
		if ( ! $lat || ! $lng ) {
			return null;
		}
		return $this->fetch_poi_counts_per_category( $lat, $lng );
	}

	private function fetch_poi_counts_per_category( $lat, $lng ) {
		$radius = self::SEARCH_RADIUS_M;

		$categories = array(
			'restaurant_count'  => "node[\"amenity\"=\"restaurant\"](around:{$radius},{$lat},{$lng});",
			'bar_count'         => "node[\"amenity\"~\"^(bar|pub|nightclub)$\"](around:{$radius},{$lat},{$lng});",
			'attraction_count'  => "node[\"tourism\"~\"^(attraction|museum)$\"](around:{$radius},{$lat},{$lng});",
			'transit_count'     => "node[\"public_transport\"=\"station\"](around:{$radius},{$lat},{$lng});node[\"highway\"=\"bus_stop\"](around:{$radius},{$lat},{$lng});",
			// "Daily convenience" categories -- can I actually live here for
			// a week, not just visit. Same Overpass connection already in
			// use, just more categories in the same batched, cached, daily
			// sync -- no new licensing surface.
			'supermarket_count' => "node[\"shop\"~\"^(supermarket|convenience)$\"](around:{$radius},{$lat},{$lng});",
			'pharmacy_count'    => "node[\"amenity\"=\"pharmacy\"](around:{$radius},{$lat},{$lng});",
			'cafe_count'        => "node[\"amenity\"=\"cafe\"](around:{$radius},{$lat},{$lng});",
			'park_count'        => "node[\"leisure\"~\"^(park|garden)$\"](around:{$radius},{$lat},{$lng});way[\"leisure\"~\"^(park|garden)$\"](around:{$radius},{$lat},{$lng});",
		);

		$counts = array();

		foreach ( $categories as $key => $clause ) {
			$query = "[out:json][timeout:25];({$clause});out count;";

			$response = wp_remote_post(
				self::OVERPASS_ENDPOINT,
				array(
					'timeout' => 30,
					'body'    => array( 'data' => $query ),
					'headers' => array(
						'User-Agent' => 'VoyaseeNeighborhoodIntelligence/1.0 (+https://voyasee.com)',
					),
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return null; // Abort the whole neighborhood -- see docblock above.
			}

			$body  = json_decode( wp_remote_retrieve_body( $response ), true );
			$total = 0;
			if ( ! empty( $body['elements'][0]['tags']['total'] ) ) {
				$total = (int) $body['elements'][0]['tags']['total'];
			}
			$counts[ $key ] = $total;

			usleep( 300000 ); // 0.3s between category calls.
		}

		return $counts;
	}

	/**
	 * Translate raw POI counts into 0-100 proxy scores.
	 *
	 * These thresholds are deliberately simple and documented so they can
	 * be tuned per city tier later; the goal is "directionally useful",
	 * not scientifically precise -- the UI should present these as
	 * estimates, not facts.
	 */
	private function counts_to_scores( $counts ) {
		$walk_inputs = $counts['restaurant_count'] + $counts['attraction_count'] + ( $counts['transit_count'] * 2 );
		$walkability = $this->scale( $walk_inputs, 0, 60, 100 );

		$nightlife = $this->scale( $counts['bar_count'], 0, 25, 100 );

		$transit = $this->scale( $counts['transit_count'], 0, 15, 100 );

		// "Can I actually live here for a week" -- groceries and a
		// pharmacy matter more than cafes/parks for that question, so
		// they're weighted higher in this simple blend. Same
		// directionally-useful-not-precise philosophy as the other scores.
		$convenience_inputs = ( $counts['supermarket_count'] * 2 ) + ( $counts['pharmacy_count'] * 2 ) + $counts['cafe_count'] + $counts['park_count'];
		$convenience        = $this->scale( $convenience_inputs, 0, 40, 100 );

		return array(
			'walkability_score' => $walkability,
			'nightlife_score'   => $nightlife,
			'transit_score'     => $transit,
			'convenience_score' => $convenience,
		);
	}

	private function scale( $value, $min_in, $max_in, $max_out ) {
		if ( $max_in <= $min_in ) {
			return 0;
		}
		$pct = ( $value - $min_in ) / ( $max_in - $min_in );
		$pct = max( 0, min( 1, $pct ) );
		return (int) round( $pct * $max_out );
	}
}
