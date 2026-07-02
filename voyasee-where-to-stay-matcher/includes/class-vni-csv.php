<?php
/**
 * Bulk CSV import/export so Tier 2/3 destinations (the bulk of the 500
 * target) can be loaded without hand-entering each one through the admin
 * UI form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VNI_CSV {

	/**
	 * Import destinations from an uploaded CSV.
	 *
	 * Expected columns (header row required):
	 * name,country,lat,lng,airport_name,airport_lat,airport_lng,tier,timezone,source_dataset
	 *
	 * @param string $file_path Path to the uploaded CSV (e.g. $_FILES['...']['tmp_name']).
	 * @return array{imported:int,skipped:int,errors:array}
	 */
	public static function import_destinations( $file_path ) {
		$result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

		$handle = self::open_csv( $file_path, $result );
		if ( ! $handle ) {
			return $result;
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			$result['errors'][] = 'CSV appears to be empty.';
			fclose( $handle );
			return $result;
		}
		$header = array_map( 'strtolower', array_map( 'trim', $header ) );

		$line = 1;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$line++;
			if ( count( $row ) !== count( $header ) ) {
				$result['skipped']++;
				$result['errors'][] = "Line {$line}: column count mismatch.";
				continue;
			}
			$data = array_combine( $header, $row );

			if ( empty( $data['name'] ) ) {
				$result['skipped']++;
				$result['errors'][] = "Line {$line}: missing required 'name'.";
				continue;
			}

			VNI_Data::upsert_destination( $data );
			$result['imported']++;
		}

		fclose( $handle );
		return $result;
	}

	/**
	 * Import neighborhoods from an uploaded CSV.
	 *
	 * Expected columns (header row required):
	 * destination_slug,name,archetype,lat,lng,price_band,safety_tier,
	 * family_suitability,solo_suitability,distance_center_km,time_center_min,
	 * distance_airport_km,time_airport_min,best_for,why_fits,why_caution,
	 * local_tip,hero_image_url,hero_image_credit,data_tier,last_reviewed
	 *
	 * best_for / why_fits / why_caution accept pipe-separated values
	 * within a single CSV cell, e.g. "Couples|First-time visitors".
	 *
	 * @param string $file_path
	 * @return array{imported:int,skipped:int,errors:array}
	 */
	public static function import_neighborhoods( $file_path ) {
		$result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

		$handle = self::open_csv( $file_path, $result );
		if ( ! $handle ) {
			return $result;
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			$result['errors'][] = 'CSV appears to be empty.';
			fclose( $handle );
			return $result;
		}
		$header = array_map( 'strtolower', array_map( 'trim', $header ) );

		$pipe_fields = array( 'best_for', 'why_fits', 'why_caution' );

		$line = 1;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$line++;
			if ( count( $row ) !== count( $header ) ) {
				$result['skipped']++;
				$result['errors'][] = "Line {$line}: column count mismatch.";
				continue;
			}
			$data = array_combine( $header, $row );

			if ( empty( $data['destination_slug'] ) || empty( $data['name'] ) ) {
				$result['skipped']++;
				$result['errors'][] = "Line {$line}: missing destination_slug or name.";
				continue;
			}

			$dest = VNI_Data::get_destination_by_slug( $data['destination_slug'] );
			if ( ! $dest ) {
				$result['skipped']++;
				$result['errors'][] = "Line {$line}: destination_slug '{$data['destination_slug']}' not found -- import destinations first.";
				continue;
			}

			$data['destination_id'] = $dest['id'];

			foreach ( $pipe_fields as $field ) {
				if ( isset( $data[ $field ] ) && '' !== $data[ $field ] ) {
					$data[ $field ] = array_filter( array_map( 'trim', explode( '|', $data[ $field ] ) ) );
				}
			}

			VNI_Data::upsert_neighborhood( $data );
			$result['imported']++;
		}

		fclose( $handle );
		return $result;
	}

	private static function open_csv( $file_path, &$result ) {
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			$result['errors'][] = 'Uploaded file could not be found.';
			return false;
		}
		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! $handle ) {
			$result['errors'][] = 'Uploaded file could not be opened.';
			return false;
		}
		return $handle;
	}
}
