<?php
/**
 * Plugin Name: Voyasee 3D World Story Atlas
 * Plugin URI: https://voyasee.com/
 * Description: A premium, interactive vector-map entry point into Voyasee's destination content, built on a self-hosted, plugin-owned destinations dataset with live weather and country-intelligence enrichment on marker click.
 * Version: 3.0.0
 * Author: Voyasee
 * Author URI: https://voyasee.com/
 * Text Domain: voyasee-3d-atlas
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('V3DA_VERSION', '3.0.0');
define('V3DA_FILE', __FILE__);
define('V3DA_DIR', plugin_dir_path(__FILE__));
define('V3DA_URL', plugin_dir_url(__FILE__));
define('V3DA_BASENAME', plugin_basename(__FILE__));

require_once V3DA_DIR . 'includes/class-v3da-db.php';
require_once V3DA_DIR . 'includes/class-v3da-content.php';
require_once V3DA_DIR . 'includes/class-v3da-travelmonth.php';
require_once V3DA_DIR . 'includes/class-v3da-automap.php';
require_once V3DA_DIR . 'includes/class-v3da-rest.php';
require_once V3DA_DIR . 'includes/class-v3da-admin.php';
require_once V3DA_DIR . 'includes/class-v3da-shortcode.php';

final class Voyasee_3D_Atlas {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', ['V3DA_DB', 'maybe_upgrade'], 5);
        add_action('plugins_loaded', [$this, 'load_textdomain'], 10);
        add_action('init', [$this, 'register_assets']);
        add_filter('plugin_action_links_' . V3DA_BASENAME, [$this, 'action_links']);
        V3DA_REST::init();
        V3DA_Admin::init();
        V3DA_Shortcode::init();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('voyasee-3d-atlas', false, dirname(V3DA_BASENAME) . '/languages');
    }

    public function register_assets(): void {
        wp_register_style('v3da-frontend', V3DA_URL . 'assets/css/frontend.css', [], V3DA_VERSION);
        // None of these vendored libraries publish an ESM build, so they're
        // registered as classic scripts (each attaches its own global:
        // `d3`, `Supercluster`, `topojson`) rather than script modules.
        // Classic scripts without a defer/async strategy run synchronously
        // in document order, and script modules are always deferred by
        // spec, so all three always finish evaluating before v3da-app runs
        // -- no explicit dependency link is needed (WP's module dependency
        // list only accepts other modules, not classic scripts, anyway).
        wp_register_script('v3da-d3', V3DA_URL . 'assets/js/vendor/d3.min.js', [], V3DA_VERSION);
        wp_register_script('v3da-supercluster', V3DA_URL . 'assets/js/vendor/supercluster.min.js', [], V3DA_VERSION);
        wp_register_script('v3da-topojson', V3DA_URL . 'assets/js/vendor/topojson-client.min.js', [], V3DA_VERSION);
        wp_register_script('v3da-iso-country-codes', V3DA_URL . 'assets/js/vendor/iso-country-codes.js', [], V3DA_VERSION);
        wp_register_script_module('v3da-app', V3DA_URL . 'assets/js/app.js', [], V3DA_VERSION);
    }

    public function action_links(array $links): array {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=voyasee-3d-atlas')) . '">' . esc_html__('Destinations', 'voyasee-3d-atlas') . '</a>');
        return $links;
    }
}

register_activation_hook(__FILE__, ['V3DA_DB', 'activate']);
Voyasee_3D_Atlas::instance();
