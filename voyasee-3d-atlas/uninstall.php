<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;
$table = $wpdb->prefix . 'v3da_destinations';
$wpdb->query("DROP TABLE IF EXISTS {$table}");

delete_option('v3da_db_version');
