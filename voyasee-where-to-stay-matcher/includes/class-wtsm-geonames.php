<?php
/**
 * WTSM_GeoNames
 *
 * Bulk-fetches basic destination facts (name, country, coordinates,
 * timezone) from the GeoNames API so new destinations can be added in
 * batches instead of one at a time through the admin form.
 *
 * LICENSE: GeoNames data is CC-BY licensed -- free, commercial use
 * permitted, attribution required (rendered in the footer alongside the
 * OpenStreetMap credit). Free tier limits: 1,000 requests/hour, 10,000/day
 * per username, which is generous for an admin-triggered batch job that
 * never runs on a live visitor request.
 *
 * Requires a free GeoNames username (Settings & Footer > Bulk destination
 * data). Every new destination this creates is added with tier=2 (generic
 * zones) by default -- promoting a destination to Tier 1 named
 * neighborhoods is still an editorial decision made by hand, same as
 * every other destination in this dataset.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_GeoNames {

	const SEARCH_ENDPOINT = 'https://secure.geonames.org/searchJSON';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Search GeoNames for populated places matching a free-text query
	 * (e.g. "beach resort towns Portugal" won't work -- GeoNames search is
	 * name-based, so this is intended for "fetch this specific city" use,
	 * one or a short list of names at a time, not open-ended discovery).
	 *
	 * @param string $query
	 * @param int    $max_rows
	 * @return array{results:array,error:string}
	 */
	public function search_places( $query, $max_rows = 10 ) {
		$username = WTSM_Settings::get( 'geonames_username', '' );

		if ( empty( $username ) ) {
			return array( 'results' => array(), 'error' => __( 'No GeoNames username configured -- add one under Settings & Footer.', 'voyasee-wtsm' ) );
		}

		$url = add_query_arg(
			array(
				'q'           => rawurlencode( $query ),
				'featureClass' => 'P', // populated places only.
				'maxRows'     => absint( $max_rows ),
				'username'    => $username,
			),
			self::SEARCH_ENDPOINT
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return array( 'results' => array(), 'error' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['status'] ) ) {
			// GeoNames returns {"status":{"message":"...","value":N}} on error (e.g. bad username, over quota).
			return array( 'results' => array(), 'error' => $body['status']['message'] ?? __( 'GeoNames returned an error.', 'voyasee-wtsm' ) );
		}

		if ( empty( $body['geonames'] ) ) {
			return array( 'results' => array(), 'error' => '' );
		}

		$results = array();
		foreach ( $body['geonames'] as $place ) {
			if ( empty( $place['lat'] ) || empty( $place['lng'] ) ) {
				continue;
			}
			$results[] = array(
				'name'      => $place['name'] ?? '',
				'country'   => $place['countryName'] ?? '',
				'lat'       => (float) $place['lat'],
				'lng'       => (float) $place['lng'],
				'population' => absint( $place['population'] ?? 0 ),
			);
		}

		return array( 'results' => $results, 'error' => '' );
	}

	/**
	 * Fetch timezone for a lat/lng from GeoNames (a second, cheap call --
	 * timezone isn't included in the basic search response).
	 */
	public function fetch_timezone( $lat, $lng ) {
		$username = WTSM_Settings::get( 'geonames_username', '' );
		if ( empty( $username ) ) {
			return '';
		}

		$url = add_query_arg(
			array( 'lat' => $lat, 'lng' => $lng, 'username' => $username ),
			'https://secure.geonames.org/timezoneJSON'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			return '';
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['timezoneId'] ?? '';
	}

	/**
	 * Import a batch of place names as new Tier-2 destinations in one go.
	 * Each line in $names_list is one destination name to look up (best
	 * effort -- ambiguous names take the first/most-populous GeoNames
	 * match). Existing destinations with a matching slug are left
	 * untouched, never overwritten.
	 *
	 * @param string[] $names_list
	 * @return array{imported:int,skipped:int,errors:array}
	 */
	public function bulk_import( $names_list ) {
		$result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

		foreach ( $names_list as $name ) {
			$name = trim( $name );
			if ( '' === $name ) {
				continue;
			}

			$existing = VNI_Data::get_destination_by_slug( sanitize_title( $name ) );
			if ( $existing ) {
				$result['skipped']++;
				continue;
			}

			$search = $this->search_places( $name, 1 );
			if ( empty( $search['results'][0] ) ) {
				$result['skipped']++;
				$result['errors'][] = sprintf( /* translators: %s: place name */ __( '"%s" -- no GeoNames match found.', 'voyasee-wtsm' ), $name );
				continue;
			}

			$place = $search['results'][0];
			$tz    = $this->fetch_timezone( $place['lat'], $place['lng'] );

			VNI_Data::upsert_destination( array(
				'name'           => $place['name'],
				'country'        => $place['country'],
				'lat'            => $place['lat'],
				'lng'            => $place['lng'],
				'tier'           => 2,
				'timezone'       => $tz,
				'cost_index'     => 3,
				'source_dataset' => 'geonames-bulk-import',
			) );

			$result['imported']++;
			usleep( 300000 ); // 0.3s between lookups -- stay well within GeoNames' hourly limit.
		}

		return $result;
	}
}
