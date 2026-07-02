<?php
/**
 * WTSM_Neighborhood_Discovery
 *
 * Finds candidate neighborhood names + coordinates for a destination from
 * OpenStreetMap (the same "place=suburb/neighbourhood/quarter" tag the
 * boundary sync already queries) and creates DRAFT rows for an admin to
 * review, edit, and publish -- never auto-published. This removes the
 * slowest step in growing the dataset (finding out what a city's real
 * neighborhoods are even called) while keeping every editorial judgment
 * (archetype, why_fits, why_caution, price/safety tiers) a human
 * decision, exactly like every other neighborhood in this plugin.
 *
 * Deliberately does NOT attempt to auto-guess an archetype from a
 * secondary POI-density query -- that would multiply the Overpass load
 * per destination for a detail an admin has to confirm by hand anyway.
 * Drafts default to a neutral archetype and Tier 2, identical to a
 * freshly-added Tier 2 neighborhood added any other way.
 *
 * Admin-triggered only (from the Sync Data screen), not cron-scheduled --
 * this is an occasional "expand this destination" action, not routine
 * maintenance like the POI/boundary/photo syncs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Neighborhood_Discovery {

	const OVERPASS_ENDPOINT = 'https://overpass-api.de/api/interpreter';

	/** How far from the destination's own point to look for named neighbourhoods. */
	const SEARCH_RADIUS_M = 20000;

	/** Safety cap on how many candidates one run will create as drafts. */
	const MAX_CREATE = 60;

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @param int $destination_id
	 * @return array{found:int,created:int,duplicate:int,errors:array}
	 */
	public function discover_for_destination( $destination_id ) {
		$result = array( 'found' => 0, 'created' => 0, 'duplicate' => 0, 'errors' => array() );

		$destination = VNI_Data::get_destination_by_id( $destination_id );
		if ( ! $destination || ! $destination['lat'] || ! $destination['lng'] ) {
			$result['errors'][] = __( 'Destination not found, or has no coordinates set.', 'voyasee-wtsm' );
			return $result;
		}

		$candidates = $this->fetch_candidates( (float) $destination['lat'], (float) $destination['lng'] );
		if ( null === $candidates ) {
			$result['errors'][] = __( 'The OpenStreetMap lookup failed (network or API issue) -- try again shortly.', 'voyasee-wtsm' );
			return $result;
		}

		$result['found'] = count( $candidates );

		$existing_slugs = $this->existing_slugs( $destination_id );

		foreach ( $candidates as $candidate ) {
			if ( $result['created'] >= self::MAX_CREATE ) {
				break;
			}

			$slug = sanitize_title( $candidate['name'] );
			if ( '' === $slug || isset( $existing_slugs[ $slug ] ) ) {
				$result['duplicate']++;
				continue;
			}

			VNI_Data::upsert_neighborhood(
				array(
					'destination_id'   => $destination_id,
					'name'             => $candidate['name'],
					'slug'             => $slug,
					'archetype'        => 'residential_quiet',
					'lat'              => $candidate['lat'],
					'lng'              => $candidate['lng'],
					'price_band'       => 3,
					'safety_tier'      => 3,
					'data_tier'        => 2,
					'discovery_status' => 'draft',
					'discovery_source' => 'osm_discovery',
				)
			);

			$existing_slugs[ $slug ] = true;
			$result['created']++;
		}

		return $result;
	}

	private function existing_slugs( $destination_id ) {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;
		$slugs = $wpdb->get_col(
			$wpdb->prepare( "SELECT slug FROM {$table} WHERE destination_id = %d", $destination_id )
		);
		return array_fill_keys( $slugs, true );
	}

	/**
	 * @return array<array{name:string,lat:float,lng:float}>|null
	 */
	private function fetch_candidates( $lat, $lng ) {
		$radius = self::SEARCH_RADIUS_M;

		// Nodes are already a point; ways/relations get "out center" so
		// every result type yields one usable lat/lng regardless of shape.
		$query = "[out:json][timeout:30];
(
  node[\"place\"~\"^(suburb|neighbourhood|quarter)$\"][\"name\"](around:{$radius},{$lat},{$lng});
  way[\"place\"~\"^(suburb|neighbourhood|quarter)$\"][\"name\"](around:{$radius},{$lat},{$lng});
  relation[\"place\"~\"^(suburb|neighbourhood|quarter)$\"][\"name\"](around:{$radius},{$lat},{$lng});
);
out center;";

		$response = wp_remote_post(
			self::OVERPASS_ENDPOINT,
			array(
				'timeout' => 40,
				'body'    => array( 'data' => $query ),
				'headers' => array(
					'User-Agent' => 'VoyaseeWhereToStayMatcher/4.0 (+https://voyasee.com)',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$elements = $body['elements'] ?? array();

		if ( ! is_array( $elements ) ) {
			return array();
		}

		$seen_names = array();
		$candidates = array();

		foreach ( $elements as $el ) {
			$name = trim( (string) ( $el['tags']['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$key = strtolower( $name );
			if ( isset( $seen_names[ $key ] ) ) {
				continue; // Same name matched as both a node and a way, etc.
			}

			$point_lat = $el['lat'] ?? ( $el['center']['lat'] ?? null );
			$point_lng = $el['lon'] ?? ( $el['center']['lon'] ?? null );
			if ( null === $point_lat || null === $point_lng ) {
				continue;
			}

			$seen_names[ $key ] = true;
			$candidates[]       = array(
				'name' => sanitize_text_field( $name ),
				'lat'  => round( (float) $point_lat, 6 ),
				'lng'  => round( (float) $point_lng, 6 ),
			);
		}

		return $candidates;
	}
}
