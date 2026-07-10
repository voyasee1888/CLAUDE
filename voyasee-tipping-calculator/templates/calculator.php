<?php
/**
 * @var string $uid
 * @var string $config      JSON config for the front-end.
 * @var array  $settings    Merged settings (footer links etc.).
 * @var string $default_country
 * @var string $home_currency
 * @var array  $country_list
 * @var array  $services
 * @var array  $currencies
 * @var array|null $overview  Server-rendered "at a glance" for the default country.
 * @var array  $atts
 */
defined('ABSPATH') || exit;

$flag = static function (string $code): string {
    if (strlen($code) !== 2) return '';
    // Encode a Unicode code point to UTF-8 without relying on mbstring or the
    // deprecated HTML-ENTITIES path (regional-indicator letters are 4-byte).
    $utf8 = static function (int $n): string {
        return chr(0xF0 | ($n >> 18)) . chr(0x80 | (($n >> 12) & 0x3F)) . chr(0x80 | (($n >> 6) & 0x3F)) . chr(0x80 | ($n & 0x3F));
    };
    return $utf8(0x1F1E6 + ord($code[0]) - 65) . $utf8(0x1F1E6 + ord($code[1]) - 65);
};

// Footer links — all 16 sibling Voyasee tools.
$tools = [
    ['url' => $settings['tool_interactive_travel_map'] ?? '', 'title' => __('Interactive Travel Map', 'voyasee-tipping-calculator'), 'desc' => __('Find destinations by budget, month & style.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F5FA}"],
    ['url' => $settings['tool_interactive_world_map'] ?? '',  'title' => __('Interactive World Map', 'voyasee-tipping-calculator'),  'desc' => __('Spin a globe of 200+ destinations.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F30D}"],
    ['url' => $settings['tool_destination_quiz'] ?? '',       'title' => __('Destination Quiz', 'voyasee-tipping-calculator'),       'desc' => __('Get matches for your travel style.', 'voyasee-tipping-calculator'), 'icon' => "\u{2753}"],
    ['url' => $settings['tool_comparison'] ?? '',             'title' => __('Destination Comparison', 'voyasee-tipping-calculator'), 'desc' => __('Battle two places side by side.', 'voyasee-tipping-calculator'), 'icon' => "\u{2696}"],
    ['url' => $settings['tool_best_area'] ?? '',              'title' => __('Best Area to Stay', 'voyasee-tipping-calculator'),      'desc' => __('Match your trip to the right neighbourhood.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F3E8}"],
    ['url' => $settings['tool_smart_travel_hub'] ?? '',       'title' => __('Smart Travel Hub', 'voyasee-tipping-calculator'),       'desc' => __('Live weather, currency & local basics.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F9ED}"],
    ['url' => $settings['tool_month_planner'] ?? '',          'title' => __('Travel Month Planner', 'voyasee-tipping-calculator'),   'desc' => __('Find the best month to visit anywhere.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F4C5}"],
    ['url' => $settings['tool_trip_budget_calculator'] ?? '', 'title' => __('Trip Budget Calculator', 'voyasee-tipping-calculator'), 'desc' => __('Estimate what your trip will really cost.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F4B0}"],
    ['url' => $settings['tool_packing_list'] ?? '',           'title' => __('Packing List Generator', 'voyasee-tipping-calculator'), 'desc' => __('A climate-aware checklist for your trip.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F9F3}"],
    ['url' => $settings['tool_carry_on_checker'] ?? '',       'title' => __('Carry-On Size Checker', 'voyasee-tipping-calculator'),  'desc' => __('Check your bag against airline rules.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F4CF}"],
    ['url' => $settings['tool_medicine_checker'] ?? '',       'title' => __('Medicine & Items Checker', 'voyasee-tipping-calculator'),'desc' => __('Check what you can legally bring.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F48A}"],
    ['url' => $settings['tool_transit_visa'] ?? '',           'title' => __('Transit Visa & Layover', 'voyasee-tipping-calculator'), 'desc' => __('Check layover & transit-visa risk.', 'voyasee-tipping-calculator'), 'icon' => "\u{2708}"],
    ['url' => $settings['tool_schengen'] ?? '',               'title' => __('Schengen Day Bank', 'voyasee-tipping-calculator'),      'desc' => __('Track your 90/180-day balance.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F1EA}\u{1F1FA}"],
    ['url' => $settings['tool_scam_checker'] ?? '',           'title' => __('Travel Scam Checker', 'voyasee-tipping-calculator'),    'desc' => __('Spot suspicious offers before you pay.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F6E1}"],
    ['url' => $settings['tool_jet_lag'] ?? '',                'title' => __('Jet Lag Recovery Planner', 'voyasee-tipping-calculator'),'desc' => __('Beat jet lag with a sleep plan.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F319}"],
    ['url' => $settings['tool_travel_passport'] ?? '',        'title' => __('Travel Passport', 'voyasee-tipping-calculator'),        'desc' => __('Your final trip-readiness check.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F6C2}"],
];
$tools = array_values(array_filter($tools, static fn($t) => !empty($t['url'])));

$affiliates = [
    ['url' => ($settings['affiliate_booking_eu'] ?? '') ?: ($settings['affiliate_booking_apac'] ?? ''), 'title' => __('Booking.com', 'voyasee-tipping-calculator'), 'desc' => __('Find and book your stay.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F3E8}"],
    ['url' => $settings['affiliate_aviasales'] ?? '',  'title' => __('Aviasales', 'voyasee-tipping-calculator'),  'desc' => __('Compare flight deals.', 'voyasee-tipping-calculator'), 'icon' => "\u{2708}"],
    ['url' => $settings['affiliate_kiwi'] ?? '',       'title' => __('Kiwi.com', 'voyasee-tipping-calculator'),   'desc' => __('Flexible flight routing.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F95D}"],
    ['url' => $settings['affiliate_safetywing'] ?? '', 'title' => __('SafetyWing', 'voyasee-tipping-calculator'), 'desc' => __('Travel insurance for nomads.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F6E1}"],
    ['url' => $settings['affiliate_visa'] ?? '',       'title' => __('Visa Requirements', 'voyasee-tipping-calculator'), 'desc' => __('Check entry rules before you fly.', 'voyasee-tipping-calculator'), 'icon' => "\u{1F6C2}"],
];
$affiliates = array_values(array_filter($affiliates, static fn($t) => !empty($t['url'])));
?>
<section class="vtc" id="<?php echo esc_attr($uid); ?>" data-vtc-root data-vtc-config="<?php echo esc_attr($config); ?>">
    <div class="vtc-bg" aria-hidden="true">
        <span class="vtc-bg-glow vtc-bg-glow-1"></span>
        <span class="vtc-bg-glow vtc-bg-glow-2"></span>
        <span class="vtc-bg-grain"></span>
    </div>

    <div class="vtc-inner">
        <header class="vtc-header">
            <p class="vtc-eyebrow"><?php echo esc_html__('Voyasee · Worldwide', 'voyasee-tipping-calculator'); ?></p>
            <h2 class="vtc-title"><?php echo esc_html__('Tipping Calculator', 'voyasee-tipping-calculator'); ?></h2>
            <p class="vtc-sub"><?php echo esc_html__('Know exactly what to tip — anywhere in the world. Culture-aware advice for 200+ countries, so you never over-tip, under-tip, or tip where you shouldn\'t.', 'voyasee-tipping-calculator'); ?></p>
            <div class="vtc-stats">
                <span class="vtc-stat"><strong>200+</strong> <?php echo esc_html__('countries', 'voyasee-tipping-calculator'); ?></span>
                <span class="vtc-stat"><strong>10</strong> <?php echo esc_html__('service types', 'voyasee-tipping-calculator'); ?></span>
                <span class="vtc-stat"><strong><?php echo esc_html__('0', 'voyasee-tipping-calculator'); ?></strong> <?php echo esc_html__('guesswork', 'voyasee-tipping-calculator'); ?></span>
            </div>
        </header>

        <div class="vtc-app">
            <form class="vtc-form" data-vtc-form autocomplete="off">
                <div class="vtc-field">
                    <label class="vtc-label"><?php echo esc_html__('Where are you?', 'voyasee-tipping-calculator'); ?></label>
                    <div class="vtc-combo" data-vtc-combo>
                        <button type="button" class="vtc-combo-btn" data-vtc-combo-btn aria-haspopup="listbox" aria-expanded="false">
                            <span class="vtc-combo-flag" data-vtc-combo-flag><?php echo $flag($default_country); ?></span>
                            <span class="vtc-combo-name" data-vtc-combo-name><?php echo esc_html($overview['country']['name'] ?? $default_country); ?></span>
                            <span class="vtc-combo-caret" aria-hidden="true">▾</span>
                        </button>
                        <div class="vtc-combo-pop" data-vtc-combo-pop hidden>
                            <input type="search" class="vtc-combo-search" data-vtc-combo-search placeholder="<?php echo esc_attr__('Search 200+ countries…', 'voyasee-tipping-calculator'); ?>" aria-label="<?php echo esc_attr__('Search countries', 'voyasee-tipping-calculator'); ?>">
                            <ul class="vtc-combo-list" data-vtc-combo-list role="listbox"></ul>
                        </div>
                    </div>
                    <div class="vtc-detected" data-vtc-detected hidden></div>
                </div>

                <div class="vtc-field">
                    <label class="vtc-label"><?php echo esc_html__('What are you paying for?', 'voyasee-tipping-calculator'); ?></label>
                    <div class="vtc-services" data-vtc-services role="tablist"></div>
                </div>

                <div class="vtc-field" data-vtc-amount-field>
                    <label class="vtc-label" data-vtc-amount-label><?php echo esc_html__('Bill amount', 'voyasee-tipping-calculator'); ?></label>
                    <div class="vtc-amount">
                        <span class="vtc-amount-sym" data-vtc-amount-sym>$</span>
                        <input type="text" inputmode="decimal" class="vtc-amount-input" data-vtc-amount placeholder="0.00" aria-label="<?php echo esc_attr__('Bill amount', 'voyasee-tipping-calculator'); ?>">
                    </div>
                </div>

                <div class="vtc-field" data-vtc-units-field hidden>
                    <label class="vtc-label" data-vtc-units-label><?php echo esc_html__('How many nights?', 'voyasee-tipping-calculator'); ?></label>
                    <div class="vtc-stepper" data-vtc-units>
                        <button type="button" class="vtc-step-btn" data-vtc-units-dec aria-label="<?php echo esc_attr__('Decrease', 'voyasee-tipping-calculator'); ?>">−</button>
                        <span class="vtc-step-val" data-vtc-units-val>1</span>
                        <button type="button" class="vtc-step-btn" data-vtc-units-inc aria-label="<?php echo esc_attr__('Increase', 'voyasee-tipping-calculator'); ?>">+</button>
                    </div>
                </div>

                <div class="vtc-field">
                    <label class="vtc-label"><?php echo esc_html__('How was the service?', 'voyasee-tipping-calculator'); ?></label>
                    <div class="vtc-seg" data-vtc-quality role="group">
                        <button type="button" data-q="poor"><?php echo esc_html__('Below par', 'voyasee-tipping-calculator'); ?></button>
                        <button type="button" data-q="standard" class="is-active"><?php echo esc_html__('As expected', 'voyasee-tipping-calculator'); ?></button>
                        <button type="button" data-q="great"><?php echo esc_html__('Great', 'voyasee-tipping-calculator'); ?></button>
                    </div>
                </div>

                <div class="vtc-field vtc-field-split">
                    <label class="vtc-label"><?php echo esc_html__('Split between', 'voyasee-tipping-calculator'); ?></label>
                    <div class="vtc-stepper" data-vtc-party>
                        <button type="button" class="vtc-step-btn" data-vtc-party-dec aria-label="<?php echo esc_attr__('Fewer people', 'voyasee-tipping-calculator'); ?>">−</button>
                        <span class="vtc-step-val"><span data-vtc-party-val>1</span> <?php echo esc_html__('people', 'voyasee-tipping-calculator'); ?></span>
                        <button type="button" class="vtc-step-btn" data-vtc-party-inc aria-label="<?php echo esc_attr__('More people', 'voyasee-tipping-calculator'); ?>">+</button>
                    </div>
                </div>

                <div class="vtc-field vtc-options">
                    <label class="vtc-check">
                        <input type="checkbox" data-vtc-roundup>
                        <span><?php echo esc_html__('Round up the total', 'voyasee-tipping-calculator'); ?></span>
                    </label>
                    <label class="vtc-home">
                        <span class="vtc-home-label"><?php echo esc_html__('Also show in', 'voyasee-tipping-calculator'); ?></span>
                        <select data-vtc-home>
                            <option value=""><?php echo esc_html__('— none —', 'voyasee-tipping-calculator'); ?></option>
                            <?php foreach ($currencies as $ccode => $cmeta): ?>
                                <option value="<?php echo esc_attr($ccode); ?>" <?php selected($home_currency, $ccode); ?>><?php echo esc_html($ccode . ' — ' . $cmeta['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <noscript><p class="vtc-noscript"><?php echo esc_html__('Enable JavaScript for the interactive calculator. The country guide below still works.', 'voyasee-tipping-calculator'); ?></p></noscript>
            </form>

            <div class="vtc-result" data-vtc-result aria-live="polite">
                <div class="vtc-result-empty" data-vtc-result-empty>
                    <span class="vtc-result-empty-icon" aria-hidden="true">&#128176;</span>
                    <p><?php echo esc_html__('Enter a bill amount to see your tip', 'voyasee-tipping-calculator'); ?></p>
                </div>
                <div class="vtc-result-card" data-vtc-result-card hidden></div>
            </div>
        </div>

        <?php if ($overview): ?>
        <section class="vtc-glance" data-vtc-glance>
            <h3 class="vtc-glance-title"><?php echo esc_html__('Tipping in', 'voyasee-tipping-calculator'); ?> <span data-vtc-glance-country><?php echo esc_html($overview['country']['name']); ?></span> <?php echo esc_html__('at a glance', 'voyasee-tipping-calculator'); ?></h3>
            <p class="vtc-glance-verdict" data-vtc-glance-verdict><?php echo esc_html($overview['country']['verdict']); ?></p>
            <div class="vtc-glance-grid" data-vtc-glance-grid>
                <?php foreach ($overview['rows'] as $row): ?>
                    <div class="vtc-glance-item">
                        <span class="vtc-glance-item-top">
                            <span class="vtc-glance-icon" aria-hidden="true"><?php echo esc_html($row['icon']); ?></span>
                            <span class="vtc-glance-label"><?php echo esc_html($row['label']); ?></span>
                        </span>
                        <span class="vtc-glance-value"><?php echo esc_html($row['display']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="vtc-glance-note" data-vtc-glance-note><?php echo esc_html($overview['country']['note']); ?></p>
            <p class="vtc-disclaimer"><?php echo esc_html__('Guidance based on Voyasee\'s own tipping intelligence — customs evolve and can vary locally, so use it as a confident starting point, not a hard rule.', 'voyasee-tipping-calculator'); ?></p>
        </section>
        <?php endif; ?>

        <footer class="vtc-footer">
            <?php if (!empty($tools)): ?>
            <div class="vtc-footer-section">
                <p class="vtc-footer-label"><?php echo esc_html__('More free Voyasee travel tools', 'voyasee-tipping-calculator'); ?></p>
                <div class="vtc-footer-grid">
                    <?php foreach ($tools as $t): ?>
                        <a class="vtc-footer-card" href="<?php echo esc_url($t['url']); ?>">
                            <span class="vtc-footer-icon" aria-hidden="true"><?php echo esc_html($t['icon']); ?></span>
                            <span class="vtc-footer-card-title"><?php echo esc_html($t['title']); ?></span>
                            <span class="vtc-footer-card-desc"><?php echo esc_html($t['desc']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($affiliates)): ?>
            <div class="vtc-footer-section vtc-footer-section--affiliate">
                <p class="vtc-footer-label"><?php echo esc_html__('Plan & book the rest of your trip', 'voyasee-tipping-calculator'); ?></p>
                <div class="vtc-footer-grid vtc-footer-grid--compact">
                    <?php foreach ($affiliates as $t): ?>
                        <a class="vtc-footer-card" href="<?php echo esc_url($t['url']); ?>" rel="nofollow sponsored noopener" target="_blank">
                            <span class="vtc-footer-icon" aria-hidden="true"><?php echo esc_html($t['icon']); ?></span>
                            <span class="vtc-footer-card-title"><?php echo esc_html($t['title']); ?></span>
                            <span class="vtc-footer-card-desc"><?php echo esc_html($t['desc']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="vtc-footer-bottom">
                <span class="vtc-footer-wordmark"><?php echo esc_html__('Voyasee', 'voyasee-tipping-calculator'); ?></span>
                <span class="vtc-footer-tagline"><?php echo esc_html__('Tip right, everywhere.', 'voyasee-tipping-calculator'); ?></span>
                <span class="vtc-footer-version">v<?php echo esc_html(VTC_VERSION); ?></span>
            </div>
        </footer>
    </div>
</section>
