<?php
/**
 * Plugin Name: Voyasee Tipping Calculator
 * Plugin URI: https://voyasee.com/
 * Description: A premium, worldwide tipping calculator powered entirely by Voyasee's own curated tipping intelligence (no third-party API). Covers 200+ countries and territories with culture-aware advice, an infographic result, bill splitting, share links, and print/PDF.
 * Version: 1.0.0
 * Author: Voyasee
 * Author URI: https://voyasee.com/
 * Text Domain: voyasee-tipping-calculator
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('VTC_VERSION', '1.0.0');
define('VTC_FILE', __FILE__);
define('VTC_DIR', plugin_dir_path(__FILE__));
define('VTC_URL', plugin_dir_url(__FILE__));
define('VTC_BASENAME', plugin_basename(__FILE__));

require_once VTC_DIR . 'includes/class-vtc-data.php';
require_once VTC_DIR . 'includes/class-vtc-calculator.php';
require_once VTC_DIR . 'includes/class-vtc-settings.php';
require_once VTC_DIR . 'includes/class-vtc-rest.php';
require_once VTC_DIR . 'includes/class-vtc-shortcode.php';
require_once VTC_DIR . 'includes/class-vtc-country-pages.php';
require_once VTC_DIR . 'includes/class-vtc-schema.php';
require_once VTC_DIR . 'includes/class-vtc-admin.php';

final class Voyasee_Tipping_Calculator {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_assets']);
        add_filter('plugin_action_links_' . VTC_BASENAME, [$this, 'action_links']);

        VTC_REST::init();
        VTC_Shortcode::init();
        VTC_Country_Pages::init();
        VTC_Schema::init();
        VTC_Admin::init();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('voyasee-tipping-calculator', false, dirname(VTC_BASENAME) . '/languages');
    }

    public function register_assets(): void {
        wp_register_style('vtc-fonts', 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap', [], null);
        wp_register_style('vtc-frontend', VTC_URL . 'assets/css/frontend.css', ['vtc-fonts'], VTC_VERSION);
        // Single classic script, authored ES5-safe so it runs in every desktop,
        // tablet, mobile and in-app browser (Facebook/Instagram/WebView).
        // The dataset is attached as an inline global (window.VTC_DATA) just
        // before it, so the calculator never depends on a runtime fetch.
        wp_register_script('vtc-app', VTC_URL . 'assets/js/app.js', [], VTC_VERSION, true);
    }

    public function action_links(array $links): array {
        $settings = '<a href="' . esc_url(admin_url('options-general.php?page=voyasee-tipping-calculator')) . '">' . esc_html__('Settings', 'voyasee-tipping-calculator') . '</a>';
        array_unshift($links, $settings);
        return $links;
    }
}

register_activation_hook(__FILE__, ['VTC_Country_Pages', 'on_activate']);
register_deactivation_hook(__FILE__, ['VTC_Country_Pages', 'on_deactivate']);
Voyasee_Tipping_Calculator::instance();
