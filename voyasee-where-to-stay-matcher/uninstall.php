<?php
/**
 * Fires only when the plugin is deleted (not on simple deactivation) via
 * the WordPress Plugins screen. Single plugin now, so this cleans up
 * everything: both database tables and the settings option.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$destinations  = $wpdb->prefix . 'voyasee_ni_destinations';
$neighborhoods = $wpdb->prefix . 'voyasee_ni_neighborhoods';

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- table names only, no user input.
$wpdb->query( "DROP TABLE IF EXISTS {$neighborhoods}" );
$wpdb->query( "DROP TABLE IF EXISTS {$destinations}" );
// phpcs:enable

delete_option( 'vni_db_version' );
delete_option( 'wtsm_settings' );

$timestamp = wp_next_scheduled( 'vni_osm_sync_event' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'vni_osm_sync_event' );
}
