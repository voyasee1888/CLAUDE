<?php
/**
 * Plugin Name:       Voyasee Where to Stay Matcher
 * Plugin URI:        https://voyasee.com
 * Description:       All-in-one neighborhood-matching tool: self-hosted destination/neighborhood dataset, OpenStreetMap POI sync, admin CRUD + CSV import, and the interactive 2-step "where should I stay" quiz with an explainable Match Score and the signature Wrong Area Warning -- all in a single plugin, operated through one shortcode.
 * Version:           3.3.0
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
define( 'WTSM_VERSION', '3.3.0' );
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

add_action( 'plugins_loaded', function () {
	$installed = get_option( 'vni_db_version', '0' );
	if ( version_compare( $installed, VNI_DB_VERSION, '<' ) ) {
		VNI_DB::activate();
	}
} );

/* ----------------------------------------------------------------------
 * Bootstrapping
 * -------------------------------------------------------------------- */
add_action( 'init', function () {
	VNI_OSM_Sync::instance()->register_cron();
	WTSM_Photo_Sync::instance()->register_cron();
	WTSM_Boundary_Sync::instance()->register_cron();
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
 * Optional integration point: if you ever wire in Voyasee Weather
 * Bridge's forecast function, this will be picked up automatically by
 * the matcher (adjust the function name below to match your plugin).
 */
function voyasee_ni_maybe_get_weather( $lat, $lng, $date ) {
	if ( function_exists( 'voyasee_wb_get_forecast' ) ) {
		return voyasee_wb_get_forecast( $lat, $lng, $date );
	}
	return null;
}
