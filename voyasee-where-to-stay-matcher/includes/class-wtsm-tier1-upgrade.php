<?php
/**
 * WTSM_Tier1_Upgrade
 *
 * One-time, self-running migration for the 4.2.0 destination-quality
 * upgrade: 15 of the bundled starter dataset's most globally-searched
 * Tier 2 ("generic zone") destinations -- Los Angeles, San Francisco,
 * Chicago, Miami, Las Vegas, Rio de Janeiro, Buenos Aires, Cape Town,
 * Bali (Denpasar), Kuala Lumpur, Cancun, Delhi, Kyoto, Shanghai, and
 * Beijing -- gained real, named neighborhoods (Tier 1) in
 * sample-data/sample-destinations.csv and sample-data/sample-neighborhoods.csv.
 *
 * A CSV re-import alone can only ever ADD rows, never remove ones a site
 * already has (see VNI_CSV::import_neighborhoods() / the "Add any new
 * starter destinations" admin button, which rely on exactly that
 * safety property elsewhere). So a site that already seeded the old
 * generic-zone rows for these destinations needs an explicit one-time
 * cleanup before the new real neighborhoods are imported, or the old
 * "City Center" / "Beachfront" placeholder rows would keep sitting
 * alongside the new real ones.
 *
 * Safety: a generic-zone row is only ever deleted if its why_caution
 * still contains the literal, distinctive disclaimer text that
 * WTSM_Neighborhood_Discovery / the original CSV seeding wrote
 * ("...starting-point zone...") -- if a site owner has since hand-edited
 * that row's caution text (even to fix a typo), it no longer matches and
 * is left completely alone, so nothing anyone has customized is ever
 * touched by this migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Tier1_Upgrade {

	const OPTION_FLAG = 'wtsm_tier1_batch_2026_07_done';

	const UPGRADED_SLUGS = array(
		'los-angeles', 'san-francisco', 'chicago', 'miami', 'las-vegas',
		'rio-de-janeiro', 'buenos-aires', 'cape-town', 'bali-denpasar',
		'kuala-lumpur', 'cancun', 'delhi', 'kyoto', 'shanghai', 'beijing',
	);

	/** Distinctive substring only ever present in auto-generated generic-zone rows. */
	const GENERIC_ZONE_MARKER = 'starting-point zone';

	public static function maybe_run() {
		if ( get_option( self::OPTION_FLAG, false ) ) {
			return;
		}
		self::run();
		update_option( self::OPTION_FLAG, current_time( 'mysql' ) );
	}

	private static function run() {
		global $wpdb;

		if ( ! class_exists( 'VNI_Data' ) ) {
			require_once VNI_PLUGIN_DIR . 'includes/class-vni-data.php';
		}

		$nb_table = $wpdb->prefix . VNI_TABLE_NEIGHBORHOODS;

		foreach ( self::UPGRADED_SLUGS as $slug ) {
			$dest = VNI_Data::get_destination_by_slug( $slug );
			if ( ! $dest ) {
				// Not on this site yet -- the CSV import below adds it
				// fresh, already at Tier 1, nothing to clean up first.
				continue;
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$nb_table}
					 WHERE destination_id = %d
					   AND data_tier = 2
					   AND why_caution LIKE %s",
					$dest['id'],
					'%' . $wpdb->esc_like( self::GENERIC_ZONE_MARKER ) . '%'
				)
			);
		}

		// Re-run the same starter-dataset import the "Add any new starter
		// destinations" admin button already uses. It's a straight upsert
		// keyed by slug -- existing destinations/neighborhoods not in the
		// list above are re-upserted with identical data (a no-op in
		// practice), the 15 destinations above pick up tier=1 from the
		// updated CSV, and the 30 new named neighborhoods get inserted as
		// new rows now that their old generic-zone siblings are gone.
		if ( ! class_exists( 'VNI_CSV' ) ) {
			require_once VNI_PLUGIN_DIR . 'includes/class-vni-csv.php';
		}

		$dest_csv = VNI_PLUGIN_DIR . 'sample-data/sample-destinations.csv';
		$nb_csv   = VNI_PLUGIN_DIR . 'sample-data/sample-neighborhoods.csv';

		if ( file_exists( $dest_csv ) ) {
			VNI_CSV::import_destinations( $dest_csv );
		}
		if ( file_exists( $nb_csv ) ) {
			VNI_CSV::import_neighborhoods( $nb_csv );
		}
	}
}
