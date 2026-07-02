<?php
/**
 * Plugin Name:       Voyasee Where to Stay Matcher
 * Plugin URI:        https://voyasee.com
 * Description:       All-in-one neighborhood-matching tool: self-hosted destination/neighborhood dataset, OpenStreetMap POI sync, admin CRUD + CSV import, and the interactive 2-step "where should I stay" quiz with an explainable Match Score and the signature Wrong Area Warning -- all in a single plugin, operated through one shortcode.
 * Version:           4.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Voyasee
 * Author URI:        https://voyasee.com
 * License:           GPL v2 or later
 * Text Domain:       voyasee-wtsm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ----------------------------------------------------------------------
 * Constants
 * -------------------------------------------------------------------- */
define( 'WTSM_VERSION', '4.2.0' );
define( 'WTSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Internal data-layer constants. Kept under the original "VNI_" naming
// inside the codebase (it began life as a separate plugin) -- aliased
// here so that code is reused unchanged inside this single plugin.
define( 'VNI_VERSION', WTSM_VERSION );
define( 'VNI_DB_VERSION', WTSM_VERSION );
define( 'VNI_PLUGIN_DIR', WTSM_PLUGIN_DIR );
define( 'VNI_PLUGIN_URL', WTSM_PLUGIN_URL );
define( 'VNI_TABLE_DESTINATIONS', 'voyasee_ni_destinations' );
define( 'VNI_TABLE_NEIGHBORHOODS', 'voyasee_ni_neighborhoods' );

/* ----------------------------------------------------------------------
 * Includes
 * -------------------------------------------------------------------- */
require_once WTSM_PLUGIN_DIR . 'includes/class-vni-db.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-vni-data.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-vni-csv.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-vni-osm-sync.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-vni-rest-api.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-matching-engine.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-rest-api.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-settings.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-photo-sync.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-boundary-sync.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-geonames.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-country-codes.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-currency.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-landmark-sync.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-neighborhood-discovery.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-tier1-upgrade.php';
require_once WTSM_PLUGIN_DIR . 'includes/class-wtsm-shortcode.php';

if ( is_admin() ) {
	require_once WTSM_PLUGIN_DIR . 'includes/class-vni-list-table-destinations.php';
	require_once WTSM_PLUGIN_DIR . 'includes/class-vni-list-table-neighborhoods.php';
	require_once WTSM_PLUGIN_DIR . 'includes/class-vni-admin.php';
}

/* ----------------------------------------------------------------------
 * Activation / Deactivation / Upgrade
 * -------------------------------------------------------------------- */
register_activation_hook( __FILE__, array( 'VNI_DB', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VNI_OSM_Sync', 'deactivate_cron' ) );
register_deactivation_hook( __FILE__, array( 'WTSM_Photo_Sync', 'deactivate_cron' ) );
register_deactivation_hook( __FILE__, array( 'WTSM_Boundary_Sync', 'deactivate_cron' ) );
register_deactivation_hook( __FILE__, array( 'WTSM_Landmark_Sync', 'deactivate_cron' ) );

add_action( 'plugins_loaded', function () {
	$installed = get_option( 'vni_db_version', '0' );
	if ( version_compare( $installed, VNI_DB_VERSION, '<' ) ) {
		VNI_DB::activate();
	}
	// Runs once, ever (self-gated via its own option flag) -- safe to call
	// on every request's plugins_loaded, and needs to run after the table
	// schema is confirmed current above.
	WTSM_Tier1_Upgrade::maybe_run();
} );

/* ----------------------------------------------------------------------
 * Bootstrapping
 * -------------------------------------------------------------------- */
add_action( 'init', function () {
	VNI_OSM_Sync::instance()->register_cron();
	WTSM_Photo_Sync::instance()->register_cron();
	WTSM_Boundary_Sync::instance()->register_cron();
	WTSM_Landmark_Sync::instance()->register_cron();
	WTSM_Shortcode::instance()->register();
} );

add_action( 'rest_api_init', function () {
	VNI_REST_API::instance()->register_routes();
	WTSM_REST_API::instance()->register_routes();
} );

if ( is_admin() ) {
	add_action( 'admin_menu', function () {
		VNI_Admin::instance()->register_menu();
		WTSM_Settings::instance()->register_menu();
	} );
	add_action( 'admin_init', function () {
		WTSM_Settings::instance()->register_settings();
	} );
	add_action( 'admin_enqueue_scripts', function ( $hook ) {
		VNI_Admin::instance()->maybe_enqueue_assets( $hook );
	} );
}

/* ----------------------------------------------------------------------
 * Public helper functions -- a stable API surface in case any other
 * Voyasee tool wants to read this data in the future.
 * -------------------------------------------------------------------- */

function voyasee_ni_get_destination( $slug ) {
	return VNI_Data::get_destination_by_slug( $slug );
}

function voyasee_ni_search_destinations( $term, $limit = 10 ) {
	return VNI_Data::search_destinations( $term, $limit );
}

function voyasee_ni_get_neighborhoods( $destination_id ) {
	return VNI_Data::get_neighborhoods_for_destination( $destination_id );
}

/**
 * Optional integration point: Voyasee Weather Bridge, if active.
 *
 * Weather Bridge exposes two genuinely separate data paths -- a
 * near-term forecast (OpenWeather/Visual Crossing, needs a key the site
 * owner configures on that plugin) and long-term climate normals (NASA
 * POWER, free, keyless, never fails for lack of a key). A trip date
 * within the forecast's reach uses the former; a date further out (the
 * common case -- most trips are planned months ahead) uses the latter,
 * so "best months to visit" keeps working even on a site with no
 * OpenWeather/Visual Crossing key configured at all.
 *
 * @param float  $lat
 * @param float  $lng
 * @param string $date Y-m-d, or '' if the traveler didn't give a date.
 * @return array|null {
 *     @type string     $type   'forecast'|'climate_normals'
 *     @type array|null $day    Matching forecastDaily entry, when type is 'forecast'.
 *     @type array|null $month  Matching climate-normals month, when type is 'climate_normals'.
 *     @type array      $raw    The full underlying Weather Bridge response, for anything else the UI wants.
 * }
 */
function voyasee_ni_maybe_get_weather( $lat, $lng, $date ) {
	if ( ! $lat || ! $lng ) {
		return null;
	}

	$days_out = null;
	if ( ! empty( $date ) ) {
		$ts = strtotime( $date . ' 00:00:00' );
		if ( false !== $ts ) {
			$days_out = (int) ceil( ( $ts - strtotime( 'today' ) ) / DAY_IN_SECONDS );
		}
	}

	// Near-term: a real forecast exists for roughly the next two weeks.
	if ( null !== $days_out && $days_out >= 0 && $days_out <= 15 && function_exists( 'voyasee_weather_get_forecast' ) ) {
		$forecast = voyasee_weather_get_forecast( $lat, $lng, max( 1, $days_out + 1 ) );
		if ( ! is_wp_error( $forecast ) && ! empty( $forecast['forecastDaily'] ) ) {
			$target_date = gmdate( 'Y-m-d', strtotime( $date ) );
			foreach ( $forecast['forecastDaily'] as $day ) {
				if ( ( $day['date'] ?? '' ) === $target_date ) {
					return array( 'type' => 'forecast', 'day' => $day, 'month' => null, 'raw' => $forecast );
				}
			}
			// Date fell inside the window but didn't line up with a
			// specific returned day (rounding) -- still return the
			// forecast bundle rather than nothing.
			return array( 'type' => 'forecast', 'day' => $forecast['forecastDaily'][0] ?? null, 'month' => null, 'raw' => $forecast );
		}
	}

	// Everything else (no date given, or a date months away): climate
	// normals, which need no key and never fail for lack of one.
	if ( function_exists( 'voyasee_weather_get_climate_normals' ) ) {
		$normals = voyasee_weather_get_climate_normals( $lat, $lng );
		if ( ! is_wp_error( $normals ) && ! empty( $normals['climateNormals']['months'] ) ) {
			$month_index = null !== $days_out ? ( (int) gmdate( 'n', strtotime( $date ) ) - 1 ) : (int) gmdate( 'n' ) - 1;
			$c           = $normals['climateNormals'];
			$month = array(
				'label'          => $c['months'][ $month_index ] ?? '',
				'temp_mean_c'    => $c['temperatureMeanC'][ $month_index ] ?? null,
				'temp_max_c'     => $c['temperatureMaxC'][ $month_index ] ?? null,
				'temp_min_c'     => $c['temperatureMinC'][ $month_index ] ?? null,
				'precip_mm'      => $c['precipitationMmMonth'][ $month_index ] ?? null,
				'rain_days_est'  => $c['estimatedRainDays'][ $month_index ] ?? null,
			);
			return array( 'type' => 'climate_normals', 'day' => null, 'month' => $month, 'raw' => $normals );
		}
	}

	return null;
}

/**
 * Optional integration point: Voyasee Weather Bridge's air quality data,
 * if active. Weather Bridge exposes this as a genuinely separate function
 * from the forecast/climate-normals ones above (a different upstream
 * provider), so it's handled as its own optional call rather than folded
 * into voyasee_ni_maybe_get_weather().
 *
 * This plugin doesn't own Weather Bridge's exact response shape, so this
 * defensively checks a few plausible key names for the headline index and
 * its human-readable category, the same "degrade gracefully instead of
 * guessing" approach already used for Country Intelligence's currency
 * data -- if nothing recognizable is found, this returns null and the
 * frontend simply omits the air quality fact rather than showing
 * something wrong.
 *
 * @return array{value:int,category:string}|null
 */
function voyasee_ni_maybe_get_air_quality( $lat, $lng ) {
	if ( ! $lat || ! $lng || ! function_exists( 'voyasee_weather_get_air_quality' ) ) {
		return null;
	}

	$raw = voyasee_weather_get_air_quality( $lat, $lng );
	if ( is_wp_error( $raw ) || empty( $raw ) || ! is_array( $raw ) ) {
		return null;
	}

	// Unwrap one common level of nesting (e.g. { airQuality: {...} }),
	// same defensive shape-guessing already used for weather/currency.
	$candidate = $raw['airQuality'] ?? $raw['air_quality'] ?? $raw;
	if ( ! is_array( $candidate ) ) {
		return null;
	}

	$value = $candidate['aqi'] ?? $candidate['index'] ?? $candidate['us_aqi'] ?? $candidate['value'] ?? null;
	if ( null === $value || ! is_numeric( $value ) ) {
		return null;
	}

	$category = $candidate['category'] ?? $candidate['level'] ?? $candidate['label'] ?? '';

	return array(
		'value'    => (int) round( (float) $value ),
		'category' => sanitize_text_field( (string) $category ),
	);
}

/**
 * Optional integration point: Voyasee Country Intelligence, if active.
 * Returns the full compiled per-country record (currency, timezone,
 * driving side, electrical, tipping/payment/tap-water guidance,
 * emergency numbers, flag) or null if the plugin isn't active or the
 * destination has no recognized ISO country_code yet.
 */
function voyasee_ni_maybe_get_country_intel( $country_code ) {
	if ( empty( $country_code ) || ! function_exists( 'voyasee_country_data_get_country' ) ) {
		return null;
	}
	$record = voyasee_country_data_get_country( $country_code );
	return ( $record && ! is_wp_error( $record ) ) ? $record : null;
}

/**
 * Optional integration point: whether the given trip dates overlap a
 * public holiday in the destination country, via Country Intelligence's
 * pre-compiled local holiday calendars (no external HTTP call at all).
 *
 * @return array{name:string,date:string}|null The first overlapping holiday, or null if none/unavailable.
 */
function voyasee_ni_maybe_get_holiday_overlap( $country_code, $start_date, $nights ) {
	if ( empty( $country_code ) || empty( $start_date ) || ! function_exists( 'voyasee_country_data_get_holidays' ) ) {
		return null;
	}

	$start_ts = strtotime( $start_date . ' 00:00:00' );
	if ( false === $start_ts ) {
		return null;
	}
	$nights  = max( 1, min( 60, (int) $nights ) );
	$end_ts  = $start_ts + ( $nights * DAY_IN_SECONDS );

	$years = array_unique( array( (int) gmdate( 'Y', $start_ts ), (int) gmdate( 'Y', $end_ts ) ) );

	foreach ( $years as $year ) {
		$holidays = voyasee_country_data_get_holidays( $country_code, $year );
		if ( is_wp_error( $holidays ) || empty( $holidays ) ) {
			continue;
		}
		$list = is_array( $holidays ) && isset( $holidays['holidays'] ) ? $holidays['holidays'] : $holidays;
		if ( ! is_array( $list ) ) {
			continue;
		}
		foreach ( $list as $holiday ) {
			$h_date = $holiday['date'] ?? '';
			if ( '' === $h_date ) {
				continue;
			}
			$h_ts = strtotime( $h_date . ' 00:00:00' );
			if ( false !== $h_ts && $h_ts >= $start_ts && $h_ts <= $end_ts ) {
				return array(
					'name' => sanitize_text_field( $holiday['name'] ?? $holiday['localName'] ?? __( 'a public holiday', 'voyasee-wtsm' ) ),
					'date' => gmdate( 'Y-m-d', $h_ts ),
				);
			}
		}
	}

	return null;
}
