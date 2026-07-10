<?php
/**
 * Uninstall cleanup — removes the plugin's single option. All tipping and
 * currency data is shipped in files, so there is nothing else to remove.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('vtc_settings');
