<?php
/**
 * Voyasee BagFit uninstall handler.
 * Data is preserved by default. Define VSB_REMOVE_DATA_ON_UNINSTALL as true
 * in wp-config.php before uninstalling to remove plugin tables and options.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;
if (!defined('VSB_REMOVE_DATA_ON_UNINSTALL') || true !== VSB_REMOVE_DATA_ON_UNINSTALL) {
    return;
}
global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'vsb_airlines');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'vsb_saved_results');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'vsb_source_checks');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'vsb_events');
delete_option('vsb_settings');
delete_option('vsb_db_version');
