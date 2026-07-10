<?php
/**
 * Plugin Name: Voyasee BagFit – Airline Carry-On Size Checker
 * Plugin URI: https://voyasee.com/
 * Description: A database-first airline baggage checker with Quick Check, full multi-bag trip analysis, reverse airline search, shared bag-size planning, 250 airline profiles and a global offline airport index.
 * Version: 7.9.3
 * Author: Voyasee
 * Author URI: https://voyasee.com/
 * Text Domain: voyasee-bagfit
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('VSB_VERSION', '7.9.3');
define('VSB_FILE', __FILE__);
define('VSB_DIR', plugin_dir_path(__FILE__));
define('VSB_URL', plugin_dir_url(__FILE__));
define('VSB_BASENAME', plugin_basename(__FILE__));

require_once VSB_DIR . 'includes/class-vsb-db.php';
require_once VSB_DIR . 'includes/class-vsb-advisor.php';
require_once VSB_DIR . 'includes/class-vsb-engine.php';
require_once VSB_DIR . 'includes/class-vsb-source-monitor.php';
require_once VSB_DIR . 'includes/class-vsb-rest.php';
require_once VSB_DIR . 'includes/class-vsb-report.php';
require_once VSB_DIR . 'includes/class-vsb-admin.php';
require_once VSB_DIR . 'includes/class-vsb-shortcode.php';

final class Voyasee_BagFit {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', ['VSB_DB', 'maybe_upgrade'], 5);
        add_action('plugins_loaded', [$this, 'load_textdomain'], 10);
        add_action('init', [$this, 'register_assets']);
        add_filter('plugin_action_links_' . VSB_BASENAME, [$this, 'action_links']);
        VSB_Source_Monitor::init();
        VSB_REST::init();
        VSB_Report::init();
        VSB_Admin::init();
        VSB_Shortcode::init();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('voyasee-bagfit', false, dirname(VSB_BASENAME) . '/languages');
    }

    public function register_assets(): void {
        wp_register_style('vsb-frontend', VSB_URL . 'assets/css/frontend.css', [], VSB_VERSION);
        wp_register_script('vsb-app', VSB_URL . 'assets/js/app.js', [], VSB_VERSION, true);
    }

    public function action_links(array $links): array {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=voyasee-bagfit')) . '">' . esc_html__('Settings', 'voyasee-bagfit') . '</a>');
        return $links;
    }
}

register_activation_hook(__FILE__, ['VSB_DB', 'activate']);
register_deactivation_hook(__FILE__, ['VSB_DB', 'deactivate']);
Voyasee_BagFit::instance();
