<?php
/**
 * Lightweight settings page (Settings -> Tipping Calculator). Lets an admin set
 * the default country, an optional default home currency, toggle the
 * programmatic country pages, and override any footer tool / affiliate link.
 */

defined('ABSPATH') || exit;

final class VTC_Admin {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
    }

    public static function menu(): void {
        add_options_page(
            __('Voyasee Tipping Calculator', 'voyasee-tipping-calculator'),
            __('Tipping Calculator', 'voyasee-tipping-calculator'),
            'manage_options',
            'voyasee-tipping-calculator',
            [self::class, 'render']
        );
    }

    public static function register(): void {
        register_setting('vtc_settings_group', VTC_Settings::option_name(), [self::class, 'sanitize']);
    }

    public static function sanitize($input): array {
        $input = is_array($input) ? $input : [];
        $defaults = VTC_Settings::defaults();
        $out = [];
        foreach ($defaults as $key => $default) {
            $val = $input[$key] ?? '';
            if ('default_country' === $key) {
                $val = strtoupper(substr((string) $val, 0, 2));
                if (!VTC_Data::raw_country($val)) {
                    $val = 'US';
                }
            } elseif ('default_home_currency' === $key) {
                $val = strtoupper(substr((string) $val, 0, 3));
                if ('' !== $val && !VTC_Data::currency($val)) {
                    $val = '';
                }
            } elseif ('country_pages_enabled' === $key) {
                $val = !empty($val) ? '1' : '';
            } else {
                // tool / affiliate links
                $val = trim((string) $val);
                $val = '' === $val ? '' : esc_url_raw($val);
            }
            $out[$key] = $val;
        }
        return $out;
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = VTC_Settings::all();
        $opt = VTC_Settings::option_name();
        $countries = VTC_Data::country_list();
        $currencies = VTC_Data::currencies();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Voyasee Tipping Calculator', 'voyasee-tipping-calculator'); ?></h1>
            <p><?php echo esc_html__('Add the calculator to any page with the shortcode:', 'voyasee-tipping-calculator'); ?>
               <code>[voyasee_tipping_calculator]</code></p>
            <form method="post" action="options.php">
                <?php settings_fields('vtc_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="vtc_default_country"><?php echo esc_html__('Default country', 'voyasee-tipping-calculator'); ?></label></th>
                        <td>
                            <select id="vtc_default_country" name="<?php echo esc_attr($opt); ?>[default_country]">
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo esc_attr($c['code']); ?>" <?php selected($s['default_country'], $c['code']); ?>><?php echo esc_html($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vtc_home"><?php echo esc_html__('Default "also show in" currency', 'voyasee-tipping-calculator'); ?></label></th>
                        <td>
                            <select id="vtc_home" name="<?php echo esc_attr($opt); ?>[default_home_currency]">
                                <option value=""><?php echo esc_html__('— none —', 'voyasee-tipping-calculator'); ?></option>
                                <?php foreach ($currencies as $code => $meta): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($s['default_home_currency'], $code); ?>><?php echo esc_html($code . ' — ' . $meta['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Per-country SEO pages', 'voyasee-tipping-calculator'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($opt); ?>[country_pages_enabled]" value="1" <?php checked($s['country_pages_enabled'], '1'); ?>>
                            <?php echo esc_html__('Enable /tipping-in-{country}/ pages, /tipping-in-{country}/{service}/ pages, region hubs, the /tipping-guides/ index and the embed route (visit Settings → Permalinks → Save once after toggling).', 'voyasee-tipping-calculator'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Installable app (PWA + offline)', 'voyasee-tipping-calculator'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($opt); ?>[pwa_enabled]" value="1" <?php checked($s['pwa_enabled'], '1'); ?>>
                            <?php echo esc_html__('Serve a web app manifest + service worker so the calculator can be installed and used offline (visit Settings → Permalinks → Save once after toggling).', 'voyasee-tipping-calculator'); ?></label>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Footer links', 'voyasee-tipping-calculator'); ?></h2>
                <p class="description"><?php echo esc_html__('Shown in the tool footer. Leave blank to hide a link.', 'voyasee-tipping-calculator'); ?></p>
                <table class="form-table" role="presentation">
                    <?php
                    $labels = self::link_labels();
                    foreach ($labels as $key => $label):
                        if (!array_key_exists($key, $s)) {
                            continue;
                        }
                        ?>
                        <tr>
                            <th scope="row"><label for="vtc_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td><input type="url" class="regular-text" id="vtc_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($opt); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($s[$key]); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2><?php echo esc_html__('Embed on another site', 'voyasee-tipping-calculator'); ?></h2>
            <p class="description"><?php echo esc_html__('Paste this where you want the calculator to appear (requires the programmatic pages to be enabled above). Add ?country=JP to preset a country.', 'voyasee-tipping-calculator'); ?></p>
            <textarea readonly rows="3" class="large-text code" onclick="this.select()"><?php
                echo esc_textarea('<iframe src="' . esc_url(home_url('/tipping-embed/')) . '" title="Voyasee Tipping Calculator" style="width:100%;max-width:1120px;height:1400px;border:0;" loading="lazy"></iframe>');
            ?></textarea>
        </div>
        <?php
    }

    private static function link_labels(): array {
        return [
            'tool_travel_passport'        => __('Tool: Travel Passport', 'voyasee-tipping-calculator'),
            'tool_interactive_travel_map' => __('Tool: Interactive Travel Map', 'voyasee-tipping-calculator'),
            'tool_interactive_world_map'  => __('Tool: Interactive World Map', 'voyasee-tipping-calculator'),
            'tool_smart_travel_hub'       => __('Tool: Smart Travel Hub', 'voyasee-tipping-calculator'),
            'tool_trip_budget_calculator' => __('Tool: Trip Budget Calculator', 'voyasee-tipping-calculator'),
            'tool_packing_list'           => __('Tool: Packing List Generator', 'voyasee-tipping-calculator'),
            'tool_carry_on_checker'       => __('Tool: Carry-On Size Checker', 'voyasee-tipping-calculator'),
            'tool_scam_checker'           => __('Tool: Travel Scam Checker', 'voyasee-tipping-calculator'),
            'tool_destination_quiz'       => __('Tool: Destination Quiz', 'voyasee-tipping-calculator'),
            'tool_comparison'             => __('Tool: Destination Comparison', 'voyasee-tipping-calculator'),
            'tool_month_planner'          => __('Tool: Travel Month Planner', 'voyasee-tipping-calculator'),
            'tool_medicine_checker'       => __('Tool: Medicine & Items Checker', 'voyasee-tipping-calculator'),
            'tool_jet_lag'                => __('Tool: Jet Lag Recovery Planner', 'voyasee-tipping-calculator'),
            'tool_transit_visa'           => __('Tool: Transit Visa & Layover', 'voyasee-tipping-calculator'),
            'tool_schengen'               => __('Tool: Schengen Day Bank', 'voyasee-tipping-calculator'),
            'tool_best_area'              => __('Tool: Best Area to Stay', 'voyasee-tipping-calculator'),
            'affiliate_booking_eu'        => __('Affiliate: Booking.com (EU)', 'voyasee-tipping-calculator'),
            'affiliate_booking_apac'      => __('Affiliate: Booking.com (APAC)', 'voyasee-tipping-calculator'),
            'affiliate_aviasales'         => __('Affiliate: Aviasales', 'voyasee-tipping-calculator'),
            'affiliate_kiwi'              => __('Affiliate: Kiwi.com', 'voyasee-tipping-calculator'),
            'affiliate_safetywing'        => __('Affiliate: SafetyWing', 'voyasee-tipping-calculator'),
            'affiliate_visa'              => __('Affiliate: VisaHQ', 'voyasee-tipping-calculator'),
        ];
    }
}
