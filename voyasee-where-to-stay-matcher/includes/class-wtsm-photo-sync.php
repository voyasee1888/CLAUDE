<?php
/**
 * WTSM_Photo_Sync
 *
 * Fetches one representative photo per neighborhood from the Pexels API
 * so match cards can show a real photo of the area instead of a solid
 * archetype-colored block.
 *
 * Switched from Unsplash to Pexels: simpler auth (a single API key in
 * one header, no Client-ID scheme), no required "download" tracking
 * ping for display-only use, and a more generous free-tier rate limit
 * (200 requests/hour, 20,000/month on the default free key) -- better
 * suited to a growing destination count than Unsplash's 50/hour demo
 * limit.
 *
 * LICENSE / COMPLIANCE NOTES (Pexels License):
 *  - Commercial use is explicitly permitted, free, no attribution
 *    legally required. Voyasee credits the photographer anyway as good
 *    practice and because it's a nice, low-cost trust signal for
 *    visitors -- stored as a small JSON string in hero_image_credit,
 *    same shape the frontend already expects.
 *  - This class NEVER calls Pexels on a live visitor request -- only
 *    from a daily cron batch or an explicit admin-triggered sync, same
 *    pattern as the OSM POI and boundary sync jobs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Photo_Sync {

	const CRON_HOOK = 'wtsm_photo_sync_event';

	/**
	 * How many neighborhoods to fetch a photo for per run. Unlike the OSM
	 * POI/boundary and Wikipedia landmark syncs, Pexels is a dedicated,
	 * commercially-licensed key with a generous quota (200 req/hour,
	 * 20,000/month on the default free key) -- not a shared community
	 * resource -- so this can run a much larger batch than those without
	 * any fair-use concern. A larger batch also matters in practice: at
	 * the old batch of 20/day, a full destination catalog of a few
	 * hundred neighborhoods took weeks to finish its very first pass,
	 * which read as "photos are broken" for every destination not yet
	 * reached rather than "still catching up."
	 */
	const BATCH_SIZE = 50;

	const PEXELS_SEARCH_ENDPOINT = 'https://api.pexels.com/v1/search';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_cron() {
		add_action( self::CRON_HOOK, array( $this, 'run_batch' ) );

		$scheduled = wp_get_scheduled_event( self::CRON_HOOK );

		// Sites upgrading from an earlier version already have this event
		// scheduled on the old 'daily' recurrence; wp_next_scheduled() alone
		// would see it as "already scheduled" and never pick up the faster
		// interval below. Reschedule in place whenever the recurrence on
		// record doesn't match what this version wants.
		if ( $scheduled && 'hourly' !== $scheduled->schedule ) {
			wp_unschedule_event( $scheduled->timestamp, self::CRON_HOOK );
			$scheduled = false;
		}

		if ( ! $scheduled ) {
			// 'hourly' (not 'daily') for the same reason the batch size was
			// raised above: this key has ample quota to spare, and clearing
			// the initial backlog across an entire catalog in hours rather
			// than weeks is what makes "runs automatically" actually feel
			// automatic instead of stuck. Once every neighborhood has a
			// photo, each run's query returns nothing and does no work.
			wp_schedule_event( time() + ( 10 * MINUTE_IN_SECONDS ), 'hourly', self::CRON_HOOK );
		}
	}

	public static function deactivate_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Process one batch of neighborhoods missing a hero image.
	 *
	 * @param bool $force If true, also re-fetch neighborhoods that already have a photo.
	 * @return array{processed:int,skipped:int,errors:array}
	 */
	public function run_batch( $force = false ) {
		global $wpdb;

		$api_key = WTSM_Settings::get( 'pexels_api_key', '' );
		$result  = array( 'processed' => 0, 'skipped' => 0, 'errors' => array() );

		if ( empty( $api_key ) ) {
			$result['errors'][] = __( 'No Pexels API key configured -- add one under Settings & Footer to enable real photos.', 'voyasee-wtsm' );
			return $result;
		}

		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		if ( $force ) {
			$rows = $wpdb->get_results(
				"SELECT id, name, destination_id FROM {$table} ORDER BY photo_last_synced ASC LIMIT " . self::BATCH_SIZE,
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				"SELECT id, name, destination_id FROM {$table} WHERE hero_image_url = '' OR hero_image_url IS NULL ORDER BY photo_last_synced ASC LIMIT " . self::BATCH_SIZE,
				ARRAY_A
			);
		}

		if ( empty( $rows ) ) {
			return $result;
		}

		foreach ( $rows as $row ) {
			$destination = VNI_Data::get_destination_by_id( $row['destination_id'] );
			$dest_name   = $destination ? $destination['name'] : '';

			$photo = $this->fetch_photo( $api_key, $row['name'], $dest_name );

			if ( null === $photo ) {
				$result['skipped']++;
				continue;
			}

			$wpdb->update(
				$table,
				array(
					'hero_image_url'    => $photo['url'],
					'hero_image_credit' => $photo['credit_json'],
					'photo_last_synced' => current_time( 'mysql' ),
				),
				array( 'id' => $row['id'] )
			);

			$result['processed']++;
			usleep( 250000 ); // 0.25s -- comfortably within Pexels' rate limit.
		}

		return $result;
	}

	/**
	 * Search Pexels for one photo matching "{neighborhood}, {destination}".
	 *
	 * @return array{url:string,credit_json:string}|null
	 */
	private function fetch_photo( $api_key, $neighborhood_name, $destination_name ) {
		$query = trim( $neighborhood_name . ' ' . $destination_name . ' street' );

		$url = add_query_arg(
			array(
				'query'       => rawurlencode( $query ),
				'per_page'    => 1,
				'orientation' => 'landscape',
			),
			self::PEXELS_SEARCH_ENDPOINT
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null; // Includes rate-limit (429) and auth (401) errors -- fail quietly, retry next batch.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['photos'][0] ) ) {
			return null;
		}

		$photo = $body['photos'][0];

		if ( empty( $photo['src']['large'] ) ) {
			return null;
		}

		$photographer_name = sanitize_text_field( $photo['photographer'] ?? '' );
		$photographer_url   = ! empty( $photo['photographer_url'] )
			? esc_url_raw( $photo['photographer_url'] )
			: '';
		$pexels_url          = ! empty( $photo['url'] ) ? esc_url_raw( $photo['url'] ) : 'https://www.pexels.com';

		$credit_json = wp_json_encode( array(
			'name'         => $photographer_name ?: 'Pexels contributor',
			'profile_url'  => $photographer_url,
			'unsplash_url' => $pexels_url, // Field name kept for frontend compatibility -- now points to the Pexels photo page.
			'source'       => 'Pexels',
		) );

		return array(
			'url'         => esc_url_raw( $photo['src']['large'] ),
			'credit_json' => $credit_json,
		);
	}
}
