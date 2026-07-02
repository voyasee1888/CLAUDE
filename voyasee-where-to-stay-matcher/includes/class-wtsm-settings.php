<?php
/**
 * WTSM_Settings
 *
 * Every external URL the footer and result page can show lives here as
 * configuration. All defaults below are the REAL, verified live URLs
 * from voyasee.com (confirmed via the site's own navigation and the
 * VOYASEE_TOOLS_AND_AFFILIATES_REGISTRY.md file) so the tool works fully
 * populated out of the box -- nothing here is a guess. Anything can
 * still be overridden or cleared from the Settings screen.
 *
 * Per the affiliate registry's standing rule, the Booking.com link must
 * always be the dpbolvw.net domain, labeled only "Booking.com" -- the
 * label is fixed in code, only the URL itself is configurable.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Settings {

	const OPTION_KEY = 'wtsm_settings';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function get( $key, $default = '' ) {
		$opts = get_option( self::OPTION_KEY, array() );
		if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
			return $opts[ $key ];
		}
		$defaults = self::hardcoded_defaults();
		return $defaults[ $key ] ?? $default;
	}

	/**
	 * Real, verified default values so the tool is fully populated
	 * immediately after activation. Sourced from voyasee.com's live
	 * navigation and VOYASEE_TOOLS_AND_AFFILIATES_REGISTRY.md.
	 */
	private static function hardcoded_defaults() {
		return array(
			// Tools -- planning.
			'tool_travel_passport_url'      => 'https://voyasee.com/trip-readiness-checklist/',
			'tool_smart_travel_hub_url'     => 'https://voyasee.com/free-smart-travel-hub/',
			'tool_interactive_map_url'      => 'https://voyasee.com/interactive-travel-map/',
			'tool_month_planner_url'        => 'https://voyasee.com/best-time-to-visit-travel-planner/',
			'tool_trip_budget_url'          => 'https://voyasee.com/trip-budget-calculator/',
			'tool_destination_quiz_url'     => 'https://voyasee.com/destination-quiz/',
			'tool_destination_battle_url'   => 'https://voyasee.com/travel-destination-comparison-tool/',
			'tool_packing_generator_url'    => 'https://voyasee.com/packing-list-generator/',
			'tool_schengen_calculator_url'  => 'https://voyasee.com/schengen-calculator/',
			'tool_carry_on_checker_url'     => 'https://voyasee.com/carry-on-size-checker/',
			// Tools -- safety & documents.
			'tool_scam_shield_url'          => 'https://voyasee.com/travel-scam-checker/',
			'tool_transit_visa_url'         => 'https://voyasee.com/transit-visa-layover-risk-checker/',
			'tool_medicine_checker_url'     => 'https://voyasee.com/medicine-restricted-item-checker/',
			'tool_jetlag_planner_url'       => 'https://voyasee.com/jet-lag-recovery-planner/',
			'tool_printables_url'           => 'https://voyasee.com/travel-printables/',

			// Affiliate partners.
			'booking_affiliate_url'         => 'https://www.dpbolvw.net/click-101719993-11891539',
			'safetywing_affiliate_url'      => 'https://safetywing.com/?referenceID=26504574&utm_source=26504574&utm_medium=Ambassador',
			'visahq_affiliate_url'          => 'https://www.visahq.co.uk/?a_aid=vaff18435',
			'aviasales_affiliate_url'       => 'https://aviasales.tpm.li/jergleAu',
			'airalo_affiliate_url'          => 'https://airalo.tpm.li/SYrMCqBw',
			'discovercars_affiliate_url'    => 'https://www.discovercars.com/?a_aid=jagu1888',
			'getyourguide_affiliate_url'    => 'https://gyg.me/6BogCHuY',
			'kiwitaxi_affiliate_url'        => 'https://kiwitaxi.tpm.li/KWy4T7aR',
		);
	}

	/** Affiliate partners shown in the footer's "Travel Partners" column. */
	public static function affiliate_fields() {
		return array(
			'booking_affiliate_url'    => array(
				'label'       => __( 'Booking.com affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Must stay a dpbolvw.net link. Button label is fixed to "Booking.com" per the affiliate registry.', 'voyasee-wtsm' ),
				'fixed_label' => 'Booking.com',
			),
			'safetywing_affiliate_url' => array(
				'label'       => __( 'SafetyWing affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Travel & nomad insurance.', 'voyasee-wtsm' ),
				'fixed_label' => 'Travel Insurance (SafetyWing)',
			),
			'visahq_affiliate_url'     => array(
				'label'       => __( 'VisaHQ affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Visa & entry-document support.', 'voyasee-wtsm' ),
				'fixed_label' => 'Visa Assistance (VisaHQ)',
			),
			'aviasales_affiliate_url'  => array(
				'label'       => __( 'Aviasales affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Flight fare comparison.', 'voyasee-wtsm' ),
				'fixed_label' => 'Flight Deals (Aviasales)',
			),
			'airalo_affiliate_url'     => array(
				'label'       => __( 'Airalo affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Global eSIM data plans.', 'voyasee-wtsm' ),
				'fixed_label' => 'eSIM Data (Airalo)',
			),
			'discovercars_affiliate_url' => array(
				'label'       => __( 'DiscoverCars affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Car rental comparison.', 'voyasee-wtsm' ),
				'fixed_label' => 'Car Rental (DiscoverCars)',
			),
			'getyourguide_affiliate_url' => array(
				'label'       => __( 'GetYourGuide affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Tours & attraction tickets.', 'voyasee-wtsm' ),
				'fixed_label' => 'Tours & Activities (GetYourGuide)',
			),
			'kiwitaxi_affiliate_url'   => array(
				'label'       => __( 'Kiwitaxi affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Prebooked airport transfers.', 'voyasee-wtsm' ),
				'fixed_label' => 'Airport Transfer (Kiwitaxi)',
			),
		);
	}

	/** Voyasee tools cross-promoted in the footer, grouped to match the live site's own convention. */
	public static function tool_fields() {
		return array(
			'tool_travel_passport_url'     => array( 'label' => __( 'Travel Passport', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_smart_travel_hub_url'    => array( 'label' => __( 'Smart Travel Hub', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_interactive_map_url'     => array( 'label' => __( 'Interactive Travel Map', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_month_planner_url'       => array( 'label' => __( 'Travel Month Planner', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_trip_budget_url'         => array( 'label' => __( 'Trip Budget Calculator', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_destination_quiz_url'    => array( 'label' => __( 'Destination Quiz', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_destination_battle_url'  => array( 'label' => __( 'Destination Comparison', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_packing_generator_url'   => array( 'label' => __( 'Smart Packing Generator', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_schengen_calculator_url' => array( 'label' => __( 'Schengen Calculator', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_carry_on_checker_url'    => array( 'label' => __( 'Airline Carry-On Size Checker', 'voyasee-wtsm' ), 'group' => 'plan' ),
			'tool_scam_shield_url'         => array( 'label' => __( 'Travel Scam Shield', 'voyasee-wtsm' ), 'group' => 'safety' ),
			'tool_transit_visa_url'        => array( 'label' => __( 'Transit Visa & Layover Checker', 'voyasee-wtsm' ), 'group' => 'safety' ),
			'tool_medicine_checker_url'    => array( 'label' => __( 'Medicine & Restricted Item Checker', 'voyasee-wtsm' ), 'group' => 'safety' ),
			'tool_jetlag_planner_url'      => array( 'label' => __( 'Jet Lag Recovery Planner', 'voyasee-wtsm' ), 'group' => 'safety' ),
			'tool_printables_url'          => array( 'label' => __( 'Travel Printables & Checklists', 'voyasee-wtsm' ), 'group' => 'safety' ),
		);
	}

	public function register_menu() {
		add_submenu_page(
			'vni-destinations',
			__( 'Settings & Footer Links', 'voyasee-wtsm' ),
			__( 'Settings & Footer', 'voyasee-wtsm' ),
			'manage_options',
			'wtsm-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( self::OPTION_KEY, self::OPTION_KEY, array( $this, 'sanitize' ) );
	}

	public function sanitize( $input ) {
		$clean = array();

		foreach ( array_keys( self::affiliate_fields() ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_url( $input[ $key ] ) : '';
		}
		foreach ( array_keys( self::tool_fields() ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_url( $input[ $key ] ) : '';
		}
		$clean['about_text']          = isset( $input['about_text'] ) ? sanitize_textarea_field( $input['about_text'] ) : '';
		$clean['pexels_api_key']      = isset( $input['pexels_api_key'] ) ? sanitize_text_field( $input['pexels_api_key'] ) : '';
		$clean['geonames_username']   = isset( $input['geonames_username'] ) ? sanitize_text_field( $input['geonames_username'] ) : '';

		return $clean;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'voyasee-wtsm' ) );
		}

		$opts          = get_option( self::OPTION_KEY, array() );
		$defaults      = self::hardcoded_defaults();
		$default_about = __( 'Voyasee helps travelers plan smarter trips with free, practical tools -- from budgeting to packing to figuring out exactly where to stay.', 'voyasee-wtsm' );

		$tool_groups = array(
			'plan'   => __( 'Plan the trip', 'voyasee-wtsm' ),
			'safety' => __( 'Safety, documents & offline backup', 'voyasee-wtsm' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Where to Stay Matcher -- Settings & Footer Links', 'voyasee-wtsm' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Every field below is pre-filled with your real, live voyasee.com tool URLs and registry affiliate links -- the footer already works out of the box. Override or clear any field here if a link ever changes.', 'voyasee-wtsm' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_KEY ); ?>

				<h2><?php esc_html_e( 'Travel partners (affiliate links)', 'voyasee-wtsm' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( self::affiliate_fields() as $key => $field ) : ?>
						<tr>
							<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<input type="url" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
									class="large-text" value="<?php echo esc_attr( $opts[ $key ] ?? ( $defaults[ $key ] ?? '' ) ); ?>" />
								<?php if ( ! empty( $field['note'] ) ) : ?>
									<p class="description"><?php echo esc_html( $field['note'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php foreach ( $tool_groups as $group_key => $group_label ) : ?>
					<h2>
						<?php
						printf(
							/* translators: %s: group label, e.g. "Plan the trip" */
							esc_html__( 'Voyasee tools -- %s (footer cross-promotion)', 'voyasee-wtsm' ),
							esc_html( $group_label )
						);
						?>
					</h2>
					<table class="form-table" role="presentation">
						<?php foreach ( self::tool_fields() as $key => $field ) : ?>
							<?php if ( $field['group'] !== $group_key ) { continue; } ?>
							<tr>
								<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
								<td>
									<input type="url" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
										class="large-text" value="<?php echo esc_attr( $opts[ $key ] ?? ( $defaults[ $key ] ?? '' ) ); ?>" placeholder="https://voyasee.com/..." />
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>

				<h2><?php esc_html_e( 'Real neighborhood photos', 'voyasee-wtsm' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pexels_api_key"><?php esc_html_e( 'Pexels API Key', 'voyasee-wtsm' ); ?></label></th>
						<td>
							<input type="text" id="pexels_api_key" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[pexels_api_key]"
								class="large-text" value="<?php echo esc_attr( $opts['pexels_api_key'] ?? '' ); ?>" placeholder="e.g. 563492ad6f9170..." />
							<p class="description">
								<?php
								printf(
									/* translators: %s: link to Pexels API signup */
									esc_html__( 'Free -- sign up at %s, then paste your API key here (shown immediately, no approval wait). Once set, go to Voyasee Where to Stay > Sync Data and click "Fetch neighborhood photos". Leave blank to keep the current solid-color cards.', 'voyasee-wtsm' ),
									'<a href="https://www.pexels.com/api/" target="_blank" rel="noopener noreferrer">pexels.com/api</a>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Bulk destination data (GeoNames)', 'voyasee-wtsm' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="geonames_username"><?php esc_html_e( 'GeoNames Username', 'voyasee-wtsm' ); ?></label></th>
						<td>
							<input type="text" id="geonames_username" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[geonames_username]"
								class="large-text" value="<?php echo esc_attr( $opts['geonames_username'] ?? '' ); ?>" placeholder="e.g. voyasee_travel" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: link to GeoNames signup */
									esc_html__( 'Free -- create an account at %s (no credit card, just confirm the free web services checkbox in your account settings), then enter your username here. Once set, go to Voyasee Where to Stay > Sync Data to bulk-fetch new destinations (name, country, coordinates, timezone) instead of adding them one at a time. GeoNames data is CC-BY licensed and free for commercial use.', 'voyasee-wtsm' ),
									'<a href="https://www.geonames.org/login" target="_blank" rel="noopener noreferrer">geonames.org</a>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'About text (footer)', 'voyasee-wtsm' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="about_text"><?php esc_html_e( 'About blurb', 'voyasee-wtsm' ); ?></label></th>
						<td>
							<textarea id="about_text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[about_text]" rows="3" class="large-text"><?php
								echo esc_textarea( $opts['about_text'] ?? $default_about );
							?></textarea>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
