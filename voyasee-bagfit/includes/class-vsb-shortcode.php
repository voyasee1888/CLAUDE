<?php
defined('ABSPATH') || exit;

final class VSB_Shortcode {
    private static bool $configured = false;
    private static bool $schema_printed = false;

    public static function init(): void {
        add_shortcode('voyasee_bag_checker', [self::class, 'render']);
    }

    public static function render(array $atts = []): string {
        $atts = shortcode_atts([
            'airline' => '',
            'mode' => 'quick',
            'units' => 'auto',
        ], $atts, 'voyasee_bag_checker');

        wp_enqueue_style('vsb-frontend');
        wp_enqueue_script('vsb-app');

        $settings = get_option('vsb_settings', []);
        if (!is_array($settings)) $settings = [];

        if (!self::$configured) {
            $config = [
                'restUrl' => esc_url_raw(rest_url('voyasee-bagfit/v1/')),
                'nonce' => '',
                'adminPostUrl' => esc_url_raw(admin_url('admin-post.php')),
                'pageUrl' => esc_url_raw(self::current_url()),
                'dataVersion' => sanitize_text_field((string) ($settings['data_version'] ?? '2026.07.10-v7.0')),
                'limits' => ['bags' => 6, 'flights' => 10, 'sharedAirlines' => 20],
                'affiliates' => array_filter([
                    'flights' => esc_url_raw($settings['affiliate_flights'] ?? ''),
                    'kiwi' => esc_url_raw($settings['affiliate_kiwi'] ?? ''),
                    'booking' => esc_url_raw($settings['affiliate_booking'] ?? ''),
                    'bookingApac' => esc_url_raw($settings['affiliate_booking_apac'] ?? ''),
                    'insurance' => esc_url_raw($settings['affiliate_insurance'] ?? ''),
                    'storage' => esc_url_raw($settings['affiliate_storage'] ?? ''),
                    'transfer' => esc_url_raw($settings['affiliate_transfer'] ?? ''),
                    'compensation' => esc_url_raw($settings['affiliate_compensation'] ?? ''),
                    'esim' => esc_url_raw($settings['affiliate_esim'] ?? ''),
                    'visa' => esc_url_raw($settings['affiliate_visa'] ?? ''),
                    'asiaTransport' => esc_url_raw($settings['affiliate_asia_transport'] ?? ''),
                    'activities' => esc_url_raw($settings['affiliate_activities'] ?? ''),
                    'malaysiaAirlines' => esc_url_raw($settings['affiliate_malaysia_airlines'] ?? ''),
                    'luggage' => esc_url_raw($settings['affiliate_luggage'] ?? ''),
                ]),
                'tools' => array_filter([
                    'packing' => esc_url_raw($settings['tool_packing'] ?? ''),
                    'medicine' => esc_url_raw($settings['tool_medicine'] ?? ''),
                    'transit' => esc_url_raw($settings['tool_transit'] ?? ''),
                    'budget' => esc_url_raw($settings['tool_budget'] ?? ''),
                    'passport' => esc_url_raw($settings['tool_passport'] ?? ''),
                    'hub' => esc_url_raw($settings['tool_hub'] ?? ''),
                    'month' => esc_url_raw($settings['tool_month'] ?? ''),
                    'compare' => esc_url_raw($settings['tool_compare'] ?? ''),
                    'scam' => esc_url_raw($settings['tool_scam'] ?? ''),
                    'map' => esc_url_raw($settings['tool_map'] ?? ''),
                    'quiz' => esc_url_raw($settings['tool_quiz'] ?? ''),
                    'printables' => esc_url_raw($settings['tool_printables'] ?? ''),
                    'flights' => esc_url_raw($settings['tool_flights'] ?? ''),
                    'tours' => esc_url_raw($settings['tool_tours'] ?? ''),
                ]),
                'strings' => [
                    'networkError' => __('The checker could not connect. Please try again.', 'voyasee-bagfit'),
                    'required' => __('Complete the highlighted fields.', 'voyasee-bagfit'),
                    'copied' => __('Copied to clipboard.', 'voyasee-bagfit'),
                    'saving' => __('Saving…', 'voyasee-bagfit'),
                    'checking' => __('Comparing every bag with every selected flight…', 'voyasee-bagfit'),
                ],
            ];
            wp_add_inline_script('vsb-app', 'window.VSB_CONFIG=' . wp_json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';', 'before');
            self::$configured = true;
        }

        $uid = 'vsb-' . wp_unique_id();
        $initial_airline = sanitize_title((string) $atts['airline']);
        $mode = in_array($atts['mode'], ['quick', 'full', 'reverse', 'shared'], true) ? $atts['mode'] : 'quick';
        $units = in_array($atts['units'], ['auto', 'metric', 'imperial'], true) ? $atts['units'] : 'auto';
        $saved_token = isset($_GET['vsb-result']) ? sanitize_text_field(wp_unslash($_GET['vsb-result'])) : '';
        if (!preg_match('/^[A-Za-z0-9_-]{30,100}$/', $saved_token)) $saved_token = '';
        $ad_top = self::ad($settings['ad_top_shortcode'] ?? '');
        $ad_result = self::ad($settings['ad_result_shortcode'] ?? '');
        $ad_footer = self::ad($settings['ad_footer_shortcode'] ?? '');

        ob_start();
        include VSB_DIR . 'templates/checker.php';
        $html = (string) ob_get_clean();

        if (!self::$schema_printed) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebApplication',
                'name' => 'Airline Carry-On Size Checker & Baggage Allowance Checker',
                'applicationCategory' => 'TravelApplication',
                'operatingSystem' => 'Any',
                'url' => self::current_url(),
                'description' => 'Check carry-on, personal-item and checked-baggage rules across multiple airlines, compare a bag globally, or calculate a shared bag size.',
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                'publisher' => ['@type' => 'Organization', 'name' => 'Voyasee', 'url' => 'https://voyasee.com/'],
            ];
            $html .= '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
            self::$schema_printed = true;
        }
        return $html;
    }

    private static function ad(string $shortcode): string {
        return trim($shortcode) === '' ? '' : (string) do_shortcode($shortcode);
    }

    private static function current_url(): string {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : wp_parse_url(home_url('/'), PHP_URL_HOST);
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        return esc_url_raw($scheme . '://' . $host . remove_query_arg('vsb-result', $uri));
    }
}
