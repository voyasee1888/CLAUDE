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
				'desc'        => __( 'Book your stay in this area', 'voyasee-wtsm' ),
				'fixed_label' => 'Booking.com',
				'icon'        => 'bed',
			),
			'safetywing_affiliate_url' => array(
				'label'       => __( 'SafetyWing affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Travel & nomad insurance.', 'voyasee-wtsm' ),
				'desc'        => __( 'Travel & nomad insurance', 'voyasee-wtsm' ),
				'fixed_label' => 'Travel Insurance (SafetyWing)',
				'icon'        => 'shield',
			),
			'visahq_affiliate_url'     => array(
				'label'       => __( 'VisaHQ affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Visa & entry-document support.', 'voyasee-wtsm' ),
				'desc'        => __( 'Visa & entry-document support', 'voyasee-wtsm' ),
				'fixed_label' => 'Visa Assistance (VisaHQ)',
				'icon'        => 'document',
			),
			'aviasales_affiliate_url'  => array(
				'label'       => __( 'Aviasales affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Flight fare comparison.', 'voyasee-wtsm' ),
				'desc'        => __( 'Compare flight fares', 'voyasee-wtsm' ),
				'fixed_label' => 'Flight Deals (Aviasales)',
				'icon'        => 'plane',
			),
			'airalo_affiliate_url'     => array(
				'label'       => __( 'Airalo affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Global eSIM data plans.', 'voyasee-wtsm' ),
				'desc'        => __( 'Global eSIM data plans', 'voyasee-wtsm' ),
				'fixed_label' => 'eSIM Data (Airalo)',
				'icon'        => 'signal',
			),
			'discovercars_affiliate_url' => array(
				'label'       => __( 'DiscoverCars affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Car rental comparison.', 'voyasee-wtsm' ),
				'desc'        => __( 'Car rental comparison', 'voyasee-wtsm' ),
				'fixed_label' => 'Car Rental (DiscoverCars)',
				'icon'        => 'car',
			),
			'getyourguide_affiliate_url' => array(
				'label'       => __( 'GetYourGuide affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Tours & attraction tickets.', 'voyasee-wtsm' ),
				'desc'        => __( 'Tours near your matched area', 'voyasee-wtsm' ),
				'fixed_label' => 'Tours & Activities (GetYourGuide)',
				'icon'        => 'ticket',
			),
			'kiwitaxi_affiliate_url'   => array(
				'label'       => __( 'Kiwitaxi affiliate URL', 'voyasee-wtsm' ),
				'note'        => __( 'Prebooked airport transfers.', 'voyasee-wtsm' ),
				'desc'        => __( 'Prebooked airport transfers', 'voyasee-wtsm' ),
				'fixed_label' => 'Airport Transfer (Kiwitaxi)',
				'icon'        => 'car',
			),
		);
	}

	/**
	 * Inline SVG path markup for the footer's tool/partner icon cards,
	 * keyed by the short name used in tool_fields()/affiliate_fields()
	 * above. Kept as plain inline paths (same convention already used for
	 * the footer column headers) rather than pulling in an icon font or
	 * library -- one more external dependency this plugin doesn't need.
	 *
	 * @return string Inner <path>/<circle> markup for the given icon key, or a plain dot if the key is unrecognized.
	 */
	public static function footer_icon( $key ) {
		$icons = array(
			'passport'     => '<rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M9 16h6"/>',
			'globe'        => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18a14 14 0 0 1 0-18"/>',
			'pin'          => '<path d="M12 21s7-7.5 7-12a7 7 0 0 0-14 0c0 4.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
			'calendar'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
			'coin'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M9 9.7c0-1.5 1.4-2.2 3-2.2s3 .8 3 2-1.4 1.8-3 1.8-3 .7-3 2 1.4 2.2 3 2.2 3-.8 3-2.2"/>',
			'compass'      => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-2 6-6 2 2-6z"/>',
			'scale'        => '<path d="M12 3v18M6 7h12M6 7l-3 6a3 3 0 0 0 6 0zM18 7l-3 6a3 3 0 0 0 6 0z"/>',
			'suitcase'     => '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
			'clock'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
			'bag'          => '<rect x="6" y="7" width="12" height="14" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M6 12h12"/>',
			'shield-alert' => '<path d="M12 3l7 3v5c0 4.5-3 7.7-7 10-4-2.3-7-5.5-7-10V6z"/><path d="M12 8v5M12 15h.01"/>',
			'swap'         => '<path d="M7 7h11l-3-3M17 17H6l3 3"/>',
			'plus'         => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
			'moon'         => '<path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5z"/>',
			'document'     => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path d="M9 12h6M9 16h6"/>',
			'bed'          => '<path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7"/><path d="M3 14h18"/><path d="M7 11V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>',
			'shield'       => '<path d="M12 3l7 3v5c0 4.5-3 7.7-7 10-4-2.3-7-5.5-7-10V6z"/>',
			'plane'        => '<path d="M3 11l18-8-8 18-2-8-8-2z"/>',
			'signal'       => '<path d="M3 18h.01M8 18v-3M13 18v-6M18 18v-9"/>',
			'car'          => '<path d="M4 16V11l2-5h12l2 5v5"/><circle cx="7.5" cy="16.5" r="1.5"/><circle cx="16.5" cy="16.5" r="1.5"/>',
			'ticket'       => '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/>',
		);
		return $icons[ $key ] ?? '<circle cx="12" cy="12" r="3"/>';
	}

	/** Voyasee tools cross-promoted in the footer, grouped to match the live site's own convention. */
	public static function tool_fields() {
		return array(
			'tool_travel_passport_url'     => array( 'label' => __( 'Travel Passport', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'passport', 'desc' => __( 'Final trip readiness check', 'voyasee-wtsm' ) ),
			'tool_smart_travel_hub_url'    => array( 'label' => __( 'Smart Travel Hub', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'globe', 'desc' => __( 'Weather, safety and advisories', 'voyasee-wtsm' ) ),
			'tool_interactive_map_url'     => array( 'label' => __( 'Interactive Travel Map', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'pin', 'desc' => __( 'Explore destinations visually', 'voyasee-wtsm' ) ),
			'tool_month_planner_url'       => array( 'label' => __( 'Travel Month Planner', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'calendar', 'desc' => __( 'Plan around the right season', 'voyasee-wtsm' ) ),
			'tool_trip_budget_url'         => array( 'label' => __( 'Trip Budget Calculator', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'coin', 'desc' => __( 'Add up baggage and hidden costs', 'voyasee-wtsm' ) ),
			'tool_destination_quiz_url'    => array( 'label' => __( 'Destination Quiz', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'compass', 'desc' => __( 'Find your next destination', 'voyasee-wtsm' ) ),
			'tool_destination_battle_url'  => array( 'label' => __( 'Destination Comparison', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'scale', 'desc' => __( 'Weigh two trips side by side', 'voyasee-wtsm' ) ),
			'tool_packing_generator_url'   => array( 'label' => __( 'Smart Packing Generator', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'suitcase', 'desc' => __( 'Pack right for this trip', 'voyasee-wtsm' ) ),
			'tool_schengen_calculator_url' => array( 'label' => __( 'Schengen Calculator', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'clock', 'desc' => __( 'Check your 90/180-day balance', 'voyasee-wtsm' ) ),
			'tool_carry_on_checker_url'    => array( 'label' => __( 'Airline Carry-On Size Checker', 'voyasee-wtsm' ), 'group' => 'plan', 'icon' => 'bag', 'desc' => __( 'Confirm your bag before the gate', 'voyasee-wtsm' ) ),
			'tool_scam_shield_url'         => array( 'label' => __( 'Travel Scam Shield', 'voyasee-wtsm' ), 'group' => 'safety', 'icon' => 'shield-alert', 'desc' => __( 'Spot common travel scams', 'voyasee-wtsm' ) ),
			'tool_transit_visa_url'        => array( 'label' => __( 'Transit Visa & Layover Checker', 'voyasee-wtsm' ), 'group' => 'safety', 'icon' => 'swap', 'desc' => __( 'Review layover and transfer risk', 'voyasee-wtsm' ) ),
			'tool_medicine_checker_url'    => array( 'label' => __( 'Medicine & Restricted Item Checker', 'voyasee-wtsm' ), 'group' => 'safety', 'icon' => 'plus', 'desc' => __( 'Check restricted items before you fly', 'voyasee-wtsm' ) ),
			'tool_jetlag_planner_url'      => array( 'label' => __( 'Jet Lag Recovery Planner', 'voyasee-wtsm' ), 'group' => 'safety', 'icon' => 'moon', 'desc' => __( 'Recover faster after long flights', 'voyasee-wtsm' ) ),
			'tool_printables_url'          => array( 'label' => __( 'Travel Printables & Checklists', 'voyasee-wtsm' ), 'group' => 'safety', 'icon' => 'document', 'desc' => __( 'Free offline checklists and cards', 'voyasee-wtsm' ) ),
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
			<h1><?php esc_html_e( 'Best Area to Stay Finder -- Settings & Footer Links', 'voyasee-wtsm' ); ?></h1>
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
									esc_html__( 'Free -- sign up at %s, then paste your API key here (shown immediately, no approval wait). Once set, go to Voyasee Best Area to Stay Finder > Sync Data and click "Fetch neighborhood photos". Leave blank to keep the current solid-color cards.', 'voyasee-wtsm' ),
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
									esc_html__( 'Free -- create an account at %s (no credit card, just confirm the free web services checkbox in your account settings), then enter your username here. Once set, go to Voyasee Best Area to Stay Finder > Sync Data to bulk-fetch new destinations (name, country, coordinates, timezone) instead of adding them one at a time. GeoNames data is CC-BY licensed and free for commercial use.', 'voyasee-wtsm' ),
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
