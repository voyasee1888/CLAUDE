<?php
/** @var array|null $result */
/** @var string $type */
/** @var array|null $geonames_result */
/** @var array|null $reseed_result */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap vni-wrap">
	<h1><?php esc_html_e( 'Import CSV', 'voyasee-ni' ); ?></h1>

	<div class="notice notice-info inline" style="padding:14px 18px;">
		<p style="margin-top:0"><strong><?php esc_html_e( 'Updated the plugin without deleting it first?', 'voyasee-ni' ); ?></strong></p>
		<p>
			<?php esc_html_e( 'The starter dataset only auto-loads on a completely fresh install, so newly-added destinations from a plugin update won\'t appear automatically if your database already has data. This button fixes that safely -- it only adds destinations/neighborhoods you don\'t already have; anything you\'ve added or edited yourself is never touched or overwritten.', 'voyasee-ni' ); ?>
		</p>
		<?php if ( null !== $reseed_result ) : ?>
			<p>
				<strong>
				<?php
				printf(
					/* translators: 1: new destinations added, 2: new neighborhoods added */
					esc_html__( 'Added %1$d new destinations and %2$d new neighborhoods.', 'voyasee-ni' ),
					(int) $reseed_result['dest_imported'],
					(int) $reseed_result['nb_imported']
				);
				?>
				</strong>
				<?php esc_html_e( '(Anything already in your database was left exactly as-is.)', 'voyasee-ni' ); ?>
			</p>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'vni_reseed_starter', 'vni_reseed_nonce' ); ?>
			<?php submit_button( __( 'Add any new starter destinations', 'voyasee-ni' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<p class="description">
		<?php esc_html_e( 'A starter dataset of 156 destinations and neighborhoods loads automatically the first time this plugin activates -- you do not need to import anything to get started. Use this screen only if you want to add more destinations/neighborhoods beyond the starter set, or bulk-update existing ones.', 'voyasee-ni' ); ?>
	</p>
	<p class="description">
		<?php esc_html_e( 'Import destinations first, then neighborhoods (neighborhoods are matched to destinations by destination_slug).', 'voyasee-ni' ); ?>
	</p>

	<?php if ( $result ) : ?>
		<div class="notice <?php echo empty( $result['errors'] ) ? 'notice-success' : 'notice-warning'; ?>">
			<p>
				<?php
				printf(
					/* translators: 1: imported count, 2: skipped count */
					esc_html__( 'Imported: %1$d. Skipped: %2$d.', 'voyasee-ni' ),
					(int) $result['imported'],
					(int) $result['skipped']
				);
				?>
			</p>
			<?php if ( ! empty( $result['errors'] ) ) : ?>
				<ul>
					<?php foreach ( array_slice( $result['errors'], 0, 25 ) as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'vni_import', 'vni_import_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Import type', 'voyasee-ni' ); ?></th>
				<td>
					<label><input type="radio" name="import_type" value="destinations" checked /> <?php esc_html_e( 'Destinations', 'voyasee-ni' ); ?></label><br />
					<label><input type="radio" name="import_type" value="neighborhoods" /> <?php esc_html_e( 'Neighborhoods', 'voyasee-ni' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="csv_file"><?php esc_html_e( 'CSV file', 'voyasee-ni' ); ?></label></th>
				<td><input type="file" id="csv_file" name="csv_file" accept=".csv" required /></td>
			</tr>
		</table>

		<?php submit_button( __( 'Upload and Import', 'voyasee-ni' ) ); ?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Expected columns', 'voyasee-ni' ); ?></h2>

	<h3><?php esc_html_e( 'Destinations CSV', 'voyasee-ni' ); ?></h3>
	<code>name, country, country_code, seasonal_note, lat, lng, airport_name, airport_lat, airport_lng, tier, timezone, cost_index, source_dataset</code>
	<p class="description">
		<?php esc_html_e( 'cost_index is a 1-5 relative cost-of-living scale used to make budget matching fair across destinations (1 = cheap relative to the global average, 5 = expensive). Defaults to 3 if left blank.', 'voyasee-ni' ); ?>
	</p>
	<p class="description">
		<?php esc_html_e( 'country_code is the 2-letter ISO 3166-1 code (e.g. JP, FR) -- optional; leave blank and it will be auto-filled from the country name where recognized. Powers the optional Voyasee Country Intelligence / Weather Bridge integrations if those plugins are active. Omitting this column entirely from a re-import will NOT erase a code you already have set.', 'voyasee-ni' ); ?>
	</p>

	<h3><?php esc_html_e( 'Neighborhoods CSV', 'voyasee-ni' ); ?></h3>
	<code>destination_slug, name, archetype, lat, lng, price_band, safety_tier, family_suitability, solo_suitability, distance_center_km, time_center_min, distance_airport_km, time_airport_min, best_for, why_fits, why_caution, local_tip, hero_image_url, hero_image_credit, data_tier, last_reviewed</code>
	<p class="description">
		<?php esc_html_e( 'For best_for / why_fits / why_caution, separate multiple values within a single cell with a pipe character, e.g. "Couples|First-time visitors".', 'voyasee-ni' ); ?>
	</p>

	<p>
		<a href="<?php echo esc_url( VNI_PLUGIN_URL . 'sample-data/sample-destinations.csv' ); ?>"><?php esc_html_e( 'Download sample destinations.csv', 'voyasee-ni' ); ?></a>
		&nbsp;|&nbsp;
		<a href="<?php echo esc_url( VNI_PLUGIN_URL . 'sample-data/sample-neighborhoods.csv' ); ?>"><?php esc_html_e( 'Download sample neighborhoods.csv', 'voyasee-ni' ); ?></a>
	</p>

	<hr style="margin:32px 0" />

	<h2><?php esc_html_e( 'Bulk-add destinations by name (GeoNames)', 'voyasee-ni' ); ?></h2>

	<?php if ( empty( WTSM_Settings::get( 'geonames_username', '' ) ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php
				printf(
					/* translators: %s: link to settings page */
					esc_html__( 'No GeoNames username configured yet. Add one (free) on the %s page first.', 'voyasee-ni' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wtsm-settings' ) ) . '">' . esc_html__( 'Settings & Footer', 'voyasee-ni' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'Type one destination name per line. Each is looked up on GeoNames (free, CC-BY licensed, commercial use permitted) and added as a Tier 2 destination with real coordinates and timezone -- much faster than the admin form for adding many destinations at once. You still write the neighborhood detail by hand afterwards, same as every other destination.', 'voyasee-ni' ); ?>
		</p>

		<?php if ( null !== $geonames_result ) : ?>
			<div class="notice <?php echo empty( $geonames_result['errors'] ) ? 'notice-success' : 'notice-warning'; ?>">
				<p>
					<?php
					printf(
						/* translators: 1: imported count, 2: skipped count */
						esc_html__( 'Added: %1$d. Skipped: %2$d.', 'voyasee-ni' ),
						(int) $geonames_result['imported'],
						(int) $geonames_result['skipped']
					);
					?>
				</p>
				<?php foreach ( array_slice( $geonames_result['errors'], 0, 25 ) as $err ) : ?>
					<p><?php echo esc_html( $err ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'wtsm_geonames_import', 'wtsm_geonames_nonce' ); ?>
			<textarea name="geonames_names" rows="6" class="large-text" placeholder="Salzburg&#10;Split&#10;Hoi An"></textarea>
			<?php submit_button( __( 'Look up and add destinations', 'voyasee-ni' ) ); ?>
		</form>
	<?php endif; ?>
</div>
