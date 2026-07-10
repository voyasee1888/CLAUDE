<?php
/**
 * [voyasee_tipping_calculator] — renders the tool. Attaches the plugin's data
 * as an inline global (window.VTC_DATA) so the calculator runs instantly with
 * no runtime fetch, then renders the interactive form, a server-side "at a
 * glance" guide for the current country (works with JavaScript off), and the
 * Voyasee footer.
 */

defined('ABSPATH') || exit;

final class VTC_Shortcode {
    private static bool $data_printed = false;

    public static function init(): void {
        add_shortcode('voyasee_tipping_calculator', [self::class, 'render']);
    }

    public static function render(array $atts = []): string {
        $atts = shortcode_atts([
            'country' => '',
        ], $atts, 'voyasee_tipping_calculator');

        wp_enqueue_style('vtc-frontend');
        wp_enqueue_script('vtc-app');

        // Attach the dataset once per page as an inline global before the app.
        if (!self::$data_printed) {
            $payload = wp_json_encode(VTC_Data::js_payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            wp_add_inline_script('vtc-app', 'window.VTC_DATA=' . $payload . ';', 'before');
            self::$data_printed = true;
        }

        $settings = VTC_Settings::all();

        $default_country = strtoupper($atts['country']) ?: strtoupper($settings['default_country'] ?? 'US');
        if (!VTC_Data::raw_country($default_country)) {
            $default_country = 'US';
        }
        $home_currency = strtoupper($settings['default_home_currency'] ?? '');

        $uid = 'vtc-' . wp_unique_id();
        $country_list = VTC_Data::country_list();
        $services = VTC_Data::services();
        $currencies = VTC_Data::currencies();
        $overview = VTC_Calculator::country_overview($default_country);

        $config = wp_json_encode([
            'restBase'        => esc_url_raw(rest_url('voyasee-tipping/v1/')),
            'defaultCountry'  => $default_country,
            'defaultHome'     => $home_currency,
            'countryPageBase' => VTC_Settings::country_pages_enabled() ? esc_url_raw(home_url('/tipping-in-')) : '',
            'strings'         => self::strings(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        ob_start();
        include VTC_DIR . 'templates/calculator.php';
        return (string) ob_get_clean();
    }

    private static function strings(): array {
        return [
            'title'            => __('Tipping Calculator', 'voyasee-tipping-calculator'),
            'chooseCountry'    => __('Where are you?', 'voyasee-tipping-calculator'),
            'searchCountry'    => __('Search 200+ countries…', 'voyasee-tipping-calculator'),
            'service'          => __('What are you paying for?', 'voyasee-tipping-calculator'),
            'billAmount'       => __('Bill amount', 'voyasee-tipping-calculator'),
            'serviceQuality'   => __('How was the service?', 'voyasee-tipping-calculator'),
            'qualityPoor'      => __('Below par', 'voyasee-tipping-calculator'),
            'qualityStandard'  => __('As expected', 'voyasee-tipping-calculator'),
            'qualityGreat'     => __('Great', 'voyasee-tipping-calculator'),
            'splitBetween'     => __('Split between', 'voyasee-tipping-calculator'),
            'people'           => __('people', 'voyasee-tipping-calculator'),
            'nights'           => __('nights', 'voyasee-tipping-calculator'),
            'bags'             => __('bags', 'voyasee-tipping-calculator'),
            'roundUp'          => __('Round up the total', 'voyasee-tipping-calculator'),
            'homeCurrency'     => __('Also show in', 'voyasee-tipping-calculator'),
            'homeCurrencyNone' => __('— none —', 'voyasee-tipping-calculator'),
            'suggestedTip'     => __('Suggested tip', 'voyasee-tipping-calculator'),
            'totalToPay'       => __('Total to pay', 'voyasee-tipping-calculator'),
            'perPerson'        => __('Per person', 'voyasee-tipping-calculator'),
            'bill'             => __('Bill', 'voyasee-tipping-calculator'),
            'tip'              => __('Tip', 'voyasee-tipping-calculator'),
            'noTipTitle'       => __('No tip needed here', 'voyasee-tipping-calculator'),
            'approxIn'         => __('Approximately', 'voyasee-tipping-calculator'),
            'approxNote'       => __('Approximate, for guidance only.', 'voyasee-tipping-calculator'),
            'share'            => __('Share', 'voyasee-tipping-calculator'),
            'copied'           => __('Link copied!', 'voyasee-tipping-calculator'),
            'print'            => __('Print / PDF', 'voyasee-tipping-calculator'),
            'enterAmount'      => __('Enter a bill amount to see your tip', 'voyasee-tipping-calculator'),
            'cultureGaugeLabel'=> __('Local tipping culture', 'voyasee-tipping-calculator'),
            'gaugeNot'         => __('Not expected', 'voyasee-tipping-calculator'),
            'gaugeExpected'    => __('Expected', 'voyasee-tipping-calculator'),
            'rangeLabel'       => __('Typical range for this service', 'voyasee-tipping-calculator'),
            'low'              => __('Low', 'voyasee-tipping-calculator'),
            'standard'         => __('Standard', 'voyasee-tipping-calculator'),
            'high'             => __('Generous', 'voyasee-tipping-calculator'),
        ];
    }
}
