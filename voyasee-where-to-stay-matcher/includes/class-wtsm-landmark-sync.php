<?php
/**
 * WTSM_Landmark_Sync
 *
 * Looks up named, real-world points of interest near each neighborhood's
 * coordinates via Wikipedia's GeoSearch API, so match cards can show real
 * local landmarks ("Near Tsukiji Outer Market, Hamarikyu Gardens")
 * instead of only an archetype label.
 *
 * Deliberately stores only titles + coordinates + page IDs -- never
 * article prose -- both to stay within Wikipedia's CC BY-SA terms without
 * needing to reproduce/attribute full text, and to match this plugin's
 * existing "never paste editorial text from an external source" rule for
 * why_fits/why_caution/local_tip.
 *
 * LICENSE: Wikipedia article titles/metadata via the public MediaWiki
 * API are free for any use, commercial included; the API itself has no
 * key requirement, only a documented User-Agent and reasonable-use
 * expectation (https://www.mediawiki.org/wiki/API:Etiquette). Same
 * "never on a live visitor request, batched daily" pattern as every
 * other sync job in this plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Landmark_Sync {

	const CRON_HOOK = 'wtsm_landmark_sync_event';

	/** How many neighborhoods to look up per run. Keep small -- polite to a shared public API. */
	const BATCH_SIZE = 20;

	/** Re-sync a neighborhood's landmarks at most this often. */
	const SYNC_INTERVAL_DAYS = 90;

	const GEOSEARCH_ENDPOINT = 'https://en.wikipedia.org/w/api.php';

	/** Search radius around the neighborhood's lat/lng, in meters. Wikipedia's geosearch caps at 10000. */
	const SEARCH_RADIUS_M = 700;

	/** Max landmarks stored per neighborhood. */
	const MAX_RESULTS = 5;

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
			wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'daily', self::CRON_HOOK );
		}
	}

	public static function deactivate_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Process one batch of neighborhoods needing a landmark refresh.
	 *
	 * @param bool $force Ignore the staleness interval, e.g. from the admin "Sync now" button.
	 * @return array{processed:int,skipped:int}
	 */
	public function run_batch( $force = false ) {
		global $wpdb;
		$table  = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		$result = array( 'processed' => 0, 'skipped' => 0 );

		if ( $force ) {
			$rows = $wpdb->get_results(
				"SELECT id, lat, lng FROM {$table} ORDER BY landmarks_last_synced ASC LIMIT " . self::BATCH_SIZE,
				ARRAY_A
			);
		} else {
			$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( self::SYNC_INTERVAL_DAYS * DAY_IN_SECONDS ) );
			$rows   = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, lat, lng FROM {$table}
					 WHERE landmarks_last_synced IS NULL OR landmarks_last_synced < %s
					 ORDER BY landmarks_last_synced ASC
					 LIMIT %d",
					$cutoff,
					self::BATCH_SIZE
				),
				ARRAY_A
			);
		}

		if ( empty( $rows ) ) {
			return $result;
		}

		foreach ( $rows as $row ) {
			$landmarks = $this->fetch_landmarks( (float) $row['lat'], (float) $row['lng'] );

			if ( null === $landmarks ) {
				$result['skipped']++;
				continue; // Network/API failure -- leave it stale, try again next run.
			}

			VNI_Data::update_landmarks( $row['id'], wp_json_encode( $landmarks ) );
			$result['processed']++;

			usleep( 300000 ); // 0.3s -- polite to a shared public API.
		}

		return $result;
	}

	/**
	 * @return array<array{title:string,pageid:int}>|null Null on failure, empty array if nothing nearby.
	 */
	private function fetch_landmarks( $lat, $lng ) {
		if ( ! $lat || ! $lng ) {
			return array();
		}

		$url = add_query_arg(
			array(
				'action'  => 'query',
				'list'    => 'geosearch',
				'gscoord' => $lat . '|' . $lng,
				'gsradius' => self::SEARCH_RADIUS_M,
				'gslimit' => self::MAX_RESULTS,
				'format'  => 'json',
			),
			self::GEOSEARCH_ENDPOINT
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent' => 'VoyaseeWhereToStayMatcher/4.0 (+https://voyasee.com; contact via site)',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$hits = $body['query']['geosearch'] ?? array();

		if ( ! is_array( $hits ) ) {
			return array();
		}

		$landmarks = array();
		foreach ( $hits as $hit ) {
			if ( empty( $hit['title'] ) ) {
				continue;
			}
			$landmarks[] = array(
				'title'  => sanitize_text_field( $hit['title'] ),
				'pageid' => absint( $hit['pageid'] ?? 0 ),
			);
		}

		return $landmarks;
	}
}
