<?php
/** @var array|null $destination */
/** @var string $notice */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$d = $destination ?: array(
	'id' => 0, 'name' => '', 'slug' => '', 'country' => '', 'country_code' => '',
	'lat' => '', 'lng' => '', 'airport_name' => '', 'airport_lat' => '', 'airport_lng' => '',
	'tier' => 2, 'timezone' => '', 'cost_index' => 3, 'source_dataset' => '',
);
?>
<div class="wrap vni-wrap">
	<h1><?php echo $d['id'] ? esc_html__( 'Edit Destination', 'voyasee-ni' ) : esc_html__( 'Add Destination', 'voyasee-ni' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'vni_save_destination', 'vni_destination_nonce' ); ?>
		<input type="hidden" name="id" value="<?php echo esc_attr( $d['id'] ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="name"><?php esc_html_e( 'Name', 'voyasee-ni' ); ?></label></th>
				<td><input type="text" id="name" name="name" class="regular-text" value="<?php echo esc_attr( $d['name'] ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="slug"><?php esc_html_e( 'Slug', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="slug" name="slug" class="regular-text" value="<?php echo esc_attr( $d['slug'] ); ?>" placeholder="<?php esc_attr_e( 'auto-generated from name if left blank', 'voyasee-ni' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="country"><?php esc_html_e( 'Country', 'voyasee-ni' ); ?></label></th>
				<td><input type="text" id="country" name="country" class="regular-text" value="<?php echo esc_attr( $d['country'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="country_code"><?php esc_html_e( 'Country code (ISO 3166-1 alpha-2)', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="country_code" name="country_code" maxlength="2" style="width:70px;text-transform:uppercase" value="<?php echo esc_attr( $d['country_code'] ); ?>" placeholder="e.g. JP" />
					<p class="description"><?php esc_html_e( 'Auto-filled from Country on activation when recognized; set/correct by hand if needed. Powers the optional Voyasee Country Intelligence integration (currency, driving side, emergency numbers, public holidays) and Voyasee Weather Bridge climate data, if those plugins are active.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lat"><?php esc_html_e( 'Latitude / Longitude', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="lat" name="lat" value="<?php echo esc_attr( $d['lat'] ); ?>" placeholder="lat" style="width:120px" />
					<input type="text" id="lng" name="lng" value="<?php echo esc_attr( $d['lng'] ); ?>" placeholder="lng" style="width:120px" />
				</td>
			</tr>
			<tr>
				<th><label for="airport_name"><?php esc_html_e( 'Main airport', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="airport_name" name="airport_name" class="regular-text" value="<?php echo esc_attr( $d['airport_name'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Narita International (NRT)', 'voyasee-ni' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="airport_lat"><?php esc_html_e( 'Airport latitude / longitude', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="airport_lat" name="airport_lat" value="<?php echo esc_attr( $d['airport_lat'] ); ?>" placeholder="lat" style="width:120px" />
					<input type="text" id="airport_lng" name="airport_lng" value="<?php echo esc_attr( $d['airport_lng'] ); ?>" placeholder="lng" style="width:120px" />
				</td>
			</tr>
			<tr>
				<th><label for="tier"><?php esc_html_e( 'Tier', 'voyasee-ni' ); ?></label></th>
				<td>
					<select id="tier" name="tier">
						<option value="1" <?php selected( $d['tier'], 1 ); ?>><?php esc_html_e( 'Tier 1 -- full named-neighborhood curation', 'voyasee-ni' ); ?></option>
						<option value="2" <?php selected( $d['tier'], 2 ); ?>><?php esc_html_e( 'Tier 2 -- generic zone curation', 'voyasee-ni' ); ?></option>
						<option value="3" <?php selected( $d['tier'], 3 ); ?>><?php esc_html_e( 'Tier 3 -- simplified fallback', 'voyasee-ni' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="timezone"><?php esc_html_e( 'Timezone', 'voyasee-ni' ); ?></label></th>
				<td><input type="text" id="timezone" name="timezone" class="regular-text" value="<?php echo esc_attr( $d['timezone'] ); ?>" placeholder="Asia/Tokyo" /></td>
			</tr>
			<tr>
				<th><label for="cost_index"><?php esc_html_e( 'Relative cost index', 'voyasee-ni' ); ?></label></th>
				<td>
					<select id="cost_index" name="cost_index">
						<option value="1" <?php selected( $d['cost_index'], 1 ); ?>><?php esc_html_e( '1 -- Very cheap relative to global average', 'voyasee-ni' ); ?></option>
						<option value="2" <?php selected( $d['cost_index'], 2 ); ?>><?php esc_html_e( '2 -- Cheap', 'voyasee-ni' ); ?></option>
						<option value="3" <?php selected( $d['cost_index'], 3 ); ?>><?php esc_html_e( '3 -- Average', 'voyasee-ni' ); ?></option>
						<option value="4" <?php selected( $d['cost_index'], 4 ); ?>><?php esc_html_e( '4 -- Expensive', 'voyasee-ni' ); ?></option>
						<option value="5" <?php selected( $d['cost_index'], 5 ); ?>><?php esc_html_e( '5 -- Very expensive relative to global average', 'voyasee-ni' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Used to make budget matching fair across destinations -- a neighborhood\'s 1-5 price band is read relative to this, so "$$$" means something different in a cheap destination vs. an expensive one.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="source_dataset"><?php esc_html_e( 'Source dataset', 'voyasee-ni' ); ?></label></th>
				<td>
					<input type="text" id="source_dataset" name="source_dataset" class="regular-text" value="<?php echo esc_attr( $d['source_dataset'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. destination-quiz-v7', 'voyasee-ni' ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional note on where this row was sourced from, useful when reconciling against your other tools\' destination lists.', 'voyasee-ni' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( $d['id'] ? __( 'Update Destination', 'voyasee-ni' ) : __( 'Add Destination', 'voyasee-ni' ) ); ?>
	</form>

	<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vni-destinations' ), admin_url( 'admin.php' ) ) ); ?>">&larr; <?php esc_html_e( 'Back to list', 'voyasee-ni' ); ?></a></p>
</div>
