<?php
/** @var array|null $neighborhood */
/** @var array $destinations */
/** @var string $notice */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$n = $neighborhood ?: array(
	'id' => 0, 'destination_id' => 0, 'name' => '', 'slug' => '', 'archetype' => 'residential_quiet',
	'lat' => '', 'lng' => '', 'price_band' => 3, 'safety_tier' => 3,
	'family_suitability' => 50, 'solo_suitability' => 50,
	'distance_center_km' => '', 'time_center_min' => '', 'distance_airport_km' => '', 'time_airport_min' => '',
	'best_for' => array(), 'why_fits' => array(), 'why_caution' => array(), 'local_tip' => '',
	'hero_image_url' => '', 'hero_image_credit' => '', 'data_tier' => 2, 'last_reviewed' => '',
);

$to_csv = function ( $value ) {
	return is_array( $value ) ? implode( ', ', $value ) : (string) $value;
};

$archetypes = VNI_Data::archetype_labels();
?>
<div class="wrap vni-wrap">
	<h1><?php echo $n['id'] ? esc_html__( 'Edit Neighborhood', 'voyasee-ni' ) : esc_html__( 'Add Neighborhood', 'voyasee-ni' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'vni_save_neighborhood', 'vni_neighborhood_nonce' ); ?>
		<input type="hidden" name="id" value="<?php echo esc_attr( $n['id'] ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="destination_id"><?php esc_html_e( 'Destination', 'voyasee-ni' ); ?></label></th>
				<td>
					<select id="destination_id" name="destination_id" required>
						<option value=""><?php esc_html_e( '-- choose --', 'voyasee-ni' ); ?></option>
						<?php foreach ( $destinations as $dest ) : ?>
							<option value="<?php echo esc_attr( $dest['id'] ); ?>" <?php selected( $n['destination_id'], $dest['id'] ); ?>>
								<?php echo esc_html( $dest['name'] . ' (' . $dest['country'] . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="name"><?php esc_html_e( 'Name', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="name" name="name" class="regular-text" value="<?php echo esc_attr( $n['name'] ); ?>" required
						placeholder="<?php esc_attr_e( 'e.g. Shibuya, or for a Tier 2/3 entry: Old Town / Historic Center', 'voyasee-ni' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="slug"><?php esc_html_e( 'Slug', 'voyasee-ni' ); ?></label></th>
				<td><input type="text" id="slug" name="slug" class="regular-text" value="<?php echo esc_attr( $n['slug'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="archetype"><?php esc_html_e( 'Archetype', 'voyasee-ni' ); ?></label></th>
				<td>
					<select id="archetype" name="archetype">
						<?php foreach ( $archetypes as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $n['archetype'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Drives the color-coding on the matcher\'s map and cards.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lat"><?php esc_html_e( 'Latitude / Longitude', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="lat" name="lat" value="<?php echo esc_attr( $n['lat'] ); ?>" placeholder="lat" style="width:120px" />
					<input type="text" id="lng" name="lng" value="<?php echo esc_attr( $n['lng'] ); ?>" placeholder="lng" style="width:120px" />
					<p class="description"><?php esc_html_e( 'Used both for the map pin and as the center point for the OSM POI-density sync.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="price_band"><?php esc_html_e( 'Price band (1=budget, 5=luxury)', 'voyasee-ni' ); ?></label></th>
				<td><input type="number" id="price_band" name="price_band" min="1" max="5" value="<?php echo esc_attr( $n['price_band'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="safety_tier"><?php esc_html_e( 'Safety tier (1=use caution, 5=very comfortable)', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="number" id="safety_tier" name="safety_tier" min="1" max="5" value="<?php echo esc_attr( $n['safety_tier'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Editorial judgment call, optionally cross-checked against travel-advisory.info. Never present this as a verified crime statistic.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="family_suitability"><?php esc_html_e( 'Family suitability (0-100)', 'voyasee-ni' ); ?></label></th>
				<td><input type="number" id="family_suitability" name="family_suitability" min="0" max="100" value="<?php echo esc_attr( $n['family_suitability'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="solo_suitability"><?php esc_html_e( 'Solo traveler suitability (0-100)', 'voyasee-ni' ); ?></label></th>
				<td><input type="number" id="solo_suitability" name="solo_suitability" min="0" max="100" value="<?php echo esc_attr( $n['solo_suitability'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="distance_center_km"><?php esc_html_e( 'Distance / time to city center', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="distance_center_km" name="distance_center_km" value="<?php echo esc_attr( $n['distance_center_km'] ); ?>" placeholder="km" style="width:100px" />
					<input type="number" id="time_center_min" name="time_center_min" value="<?php echo esc_attr( $n['time_center_min'] ); ?>" placeholder="minutes" style="width:100px" />
				</td>
			</tr>
			<tr>
				<th><label for="distance_airport_km"><?php esc_html_e( 'Distance / time to main airport', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="distance_airport_km" name="distance_airport_km" value="<?php echo esc_attr( $n['distance_airport_km'] ); ?>" placeholder="km" style="width:100px" />
					<input type="number" id="time_airport_min" name="time_airport_min" value="<?php echo esc_attr( $n['time_airport_min'] ); ?>" placeholder="minutes" style="width:100px" />
				</td>
			</tr>
			<tr>
				<th><label for="best_for"><?php esc_html_e( 'Best for (comma-separated tags)', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="best_for" name="best_for" class="large-text" value="<?php echo esc_attr( $to_csv( $n['best_for'] ) ); ?>"
						placeholder="<?php esc_attr_e( 'Couples, First-time visitors, Nightlife seekers', 'voyasee-ni' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="why_fits"><?php esc_html_e( 'Why this fits (comma-separated, your own words)', 'voyasee-ni' ); ?></label></th>
				<td>
					<textarea id="why_fits" name="why_fits" rows="3" class="large-text"><?php echo esc_textarea( $to_csv( $n['why_fits'] ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Write these yourself -- never copy text from Wikivoyage, blogs, or other sources. They must be 100% original.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="why_caution"><?php esc_html_e( 'Why this may not fit -- the Wrong Area Warning (comma-separated)', 'voyasee-ni' ); ?></label></th>
				<td>
					<textarea id="why_caution" name="why_caution" rows="3" class="large-text"><?php echo esc_textarea( $to_csv( $n['why_caution'] ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'This is the tool\'s signature feature -- the honest trade-off line. Be specific (e.g. "80 minutes from most major attractions by transit").', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="local_tip"><?php esc_html_e( 'Local insider tip', 'voyasee-ni' ); ?></label></th>
				<td><textarea id="local_tip" name="local_tip" rows="2" class="large-text"><?php echo esc_textarea( $n['local_tip'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="hero_image_url"><?php esc_html_e( 'Hero image URL', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="url" id="hero_image_url" name="hero_image_url" class="large-text" value="<?php echo esc_attr( $n['hero_image_url'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Use a single curated image (Pexels or similar), chosen by hand -- not a live search per page view.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="hero_image_credit"><?php esc_html_e( 'Hero image credit', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="hero_image_credit" name="hero_image_credit" class="large-text" value="<?php echo esc_attr( $n['hero_image_credit'] ); ?>"
						placeholder="<?php esc_attr_e( 'Photo by Jane Doe on Pexels', 'voyasee-ni' ); ?>" />
					<p class="description"><?php esc_html_e( 'Not legally required by Pexels\' license, but a nice trust signal to include if you add a photo by hand.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="data_tier"><?php esc_html_e( 'Data tier', 'voyasee-ni' ); ?></label></th>
				<td>
					<select id="data_tier" name="data_tier">
						<option value="1" <?php selected( $n['data_tier'], 1 ); ?>><?php esc_html_e( '1 -- fully curated named neighborhood', 'voyasee-ni' ); ?></option>
						<option value="2" <?php selected( $n['data_tier'], 2 ); ?>><?php esc_html_e( '2 -- generic zone, lighter curation', 'voyasee-ni' ); ?></option>
						<option value="3" <?php selected( $n['data_tier'], 3 ); ?>><?php esc_html_e( '3 -- simplified fallback', 'voyasee-ni' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="last_reviewed"><?php esc_html_e( 'Last reviewed', 'voyasee-ni' ); ?></label></th>
				<td><input type="date" id="last_reviewed" name="last_reviewed" value="<?php echo esc_attr( $n['last_reviewed'] ); ?>" /></td>
			</tr>
		</table>

		<?php submit_button( $n['id'] ? __( 'Update Neighborhood', 'voyasee-ni' ) : __( 'Add Neighborhood', 'voyasee-ni' ) ); ?>
	</form>

	<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vni-neighborhoods' ), admin_url( 'admin.php' ) ) ); ?>">&larr; <?php esc_html_e( 'Back to list', 'voyasee-ni' ); ?></a></p>
</div>
