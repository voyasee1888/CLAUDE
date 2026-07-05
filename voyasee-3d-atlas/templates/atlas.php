<?php
/**
 * @var string $uid
 * @var string $config JSON-encoded {restBase, markers, strings}.
 * @var array<string,array> $groups Destinations grouped by region.
 * @var array<string,string> $settings
 * @var string[] $regions
 */
defined('ABSPATH') || exit;

$trip_readiness = $settings['tool_trip_readiness'] ?? '';
$hub_url = $settings['tool_smart_travel_hub'] ?? '';
$map_url = $settings['tool_interactive_map'] ?? '';
$month_planner_url = $settings['tool_travel_month_planner'] ?? '';
$budget_url = $settings['tool_trip_budget_calculator'] ?? '';
$quiz_url = $settings['tool_destination_quiz'] ?? '';
$comparison_url = $settings['tool_destination_comparison'] ?? '';
$packing_url = $settings['tool_smart_packing_list'] ?? '';
$scam_shield_url = $settings['tool_travel_scam_shield'] ?? '';
$jetlag_url = $settings['tool_jet_lag_planner'] ?? '';
$booking_url = ($settings['affiliate_booking_eu'] ?? '') ?: ($settings['affiliate_booking_apac'] ?? '');
$aviasales_url = $settings['affiliate_aviasales'] ?? '';
$kiwi_url = $settings['affiliate_kiwi'] ?? '';
$safetywing_url = $settings['affiliate_safetywing'] ?? '';
$visa_url = $settings['affiliate_visa'] ?? '';

$hero_cta_url = $trip_readiness ?: ($quiz_url ?: $map_url);

$has_plan_links = $trip_readiness || $hub_url || $map_url || $month_planner_url || $budget_url;
$has_decide_links = $quiz_url || $comparison_url || $packing_url || $scam_shield_url || $jetlag_url;
$has_book_links = $booking_url || $aviasales_url || $kiwi_url;
$has_safety_links = $safetywing_url || $visa_url;
?>
<section class="v3datlas-root" id="<?php echo esc_attr($uid); ?>" data-v3datlas-root data-v3datlas-config="<?php echo esc_attr($config); ?>">
    <div class="v3datlas-hero">
        <p class="v3datlas-eyebrow"><?php echo esc_html__('Interactive World Map', 'voyasee-3d-atlas'); ?></p>
        <h2 class="v3datlas-title"><?php echo esc_html__('Spin the Globe and Discover Your Next Destination', 'voyasee-3d-atlas'); ?></h2>
        <p class="v3datlas-intro"><?php echo esc_html__('Spin the globe, click any destination for live data and travel insights, or browse the full A–Z list below.', 'voyasee-3d-atlas'); ?></p>
        <?php if ($hero_cta_url): ?>
            <p class="v3datlas-hero-cta">
                <a href="<?php echo esc_url($hero_cta_url); ?>"><?php echo esc_html__('Not sure where to start? Find your next destination', 'voyasee-3d-atlas'); ?></a>
            </p>
        <?php endif; ?>
    </div>

    <div class="v3datlas-filter-bar" data-v3datlas-filter-bar>
        <div class="v3datlas-filter-group" data-v3datlas-filter-region>
            <button type="button" class="v3datlas-filter-chip is-active" data-filter-value="all"><?php echo esc_html__('All', 'voyasee-3d-atlas'); ?></button>
            <?php foreach ($regions as $region): ?>
                <button type="button" class="v3datlas-filter-chip" data-filter-value="<?php echo esc_attr($region); ?>"><?php echo esc_html($region); ?></button>
            <?php endforeach; ?>
        </div>
        <div class="v3datlas-filter-actions">
            <button type="button" class="v3datlas-surprise-btn" data-v3datlas-surprise aria-label="<?php echo esc_attr__('Random destination', 'voyasee-3d-atlas'); ?>"><?php echo esc_html__('Surprise me', 'voyasee-3d-atlas'); ?></button>
        </div>
    </div>

    <div class="v3datlas-stage">
        <div class="v3datlas-map-mount" data-v3datlas-map-mount aria-hidden="true">
            <noscript><?php echo esc_html__('Enable JavaScript to view the interactive map.', 'voyasee-3d-atlas'); ?></noscript>
        </div>

        <aside class="v3datlas-sidebar" data-v3datlas-sidebar hidden aria-label="<?php echo esc_attr__('Destination details', 'voyasee-3d-atlas'); ?>">
            <div class="v3datlas-sidebar-actions">
                <button type="button" class="v3datlas-sidebar-action-btn" data-v3datlas-sidebar-visited aria-label="<?php echo esc_attr__('Mark as visited', 'voyasee-3d-atlas'); ?>">&#10003;</button>
                <button type="button" class="v3datlas-sidebar-action-btn" data-v3datlas-sidebar-wantgo aria-label="<?php echo esc_attr__('Add to want-to-go list', 'voyasee-3d-atlas'); ?>">&#9734;</button>
                <button type="button" class="v3datlas-sidebar-action-btn" data-v3datlas-sidebar-share aria-label="<?php echo esc_attr__('Share destination', 'voyasee-3d-atlas'); ?>">&#8599;</button>
                <button type="button" class="v3datlas-sidebar-close" data-v3datlas-sidebar-close aria-label="<?php echo esc_attr__('Close', 'voyasee-3d-atlas'); ?>">&times;</button>
            </div>
            <div class="v3datlas-sidebar-body" data-v3datlas-sidebar-body>
                <p class="v3datlas-sidebar-loading"><?php echo esc_html__('Loading…', 'voyasee-3d-atlas'); ?></p>
            </div>
        </aside>
    </div>

    <div class="v3datlas-visited-strip" data-v3datlas-visited-strip hidden>
        <span class="v3datlas-visited-count" data-v3datlas-visited-count></span>
    </div>

    <div class="v3datlas-destination-list" data-v3datlas-destination-list>
        <p class="v3datlas-list-heading"><?php echo esc_html__('Browse all destinations A–Z', 'voyasee-3d-atlas'); ?></p>

        <?php if (!empty($groups)): ?>
            <input type="search" class="v3datlas-list-search" data-v3datlas-list-search placeholder="<?php echo esc_attr__('Search destinations…', 'voyasee-3d-atlas'); ?>" aria-label="<?php echo esc_attr__('Search destinations', 'voyasee-3d-atlas'); ?>">
        <?php else: ?>
            <p class="v3datlas-list-empty"><?php echo esc_html__('Destinations are being added — check back soon.', 'voyasee-3d-atlas'); ?></p>
        <?php endif; ?>

        <?php foreach ($groups as $region => $items): ?>
            <details class="v3datlas-region" data-v3datlas-region>
                <summary><?php echo esc_html($region); ?> <span class="v3datlas-region-count">(<?php echo count($items); ?>)</span></summary>
                <ul class="v3datlas-region-list">
                    <?php foreach ($items as $d): ?>
                        <li data-v3datlas-region-item data-name="<?php echo esc_attr(strtolower($d['name'] . ' ' . $d['country'])); ?>">
                            <button type="button" class="v3datlas-region-link" data-v3datlas-open-slug="<?php echo esc_attr($d['slug']); ?>"><?php echo esc_html($d['name']); ?>, <?php echo esc_html($d['country']); ?></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    </div>

    <footer class="v3datlas-footer">
        <div class="v3datlas-footer-banner">
            <p class="v3datlas-footer-eyebrow"><?php echo esc_html__('Keep Exploring', 'voyasee-3d-atlas'); ?></p>
            <h3 class="v3datlas-footer-heading"><?php echo esc_html__('Turn this map into your next trip', 'voyasee-3d-atlas'); ?></h3>
            <p class="v3datlas-footer-subtitle"><?php echo esc_html__('Free Voyasee tools to help you plan, decide, and travel with confidence.', 'voyasee-3d-atlas'); ?></p>
            <?php if ($hero_cta_url): ?>
                <a class="v3datlas-footer-banner-cta" href="<?php echo esc_url($hero_cta_url); ?>"><?php echo esc_html__('Start planning', 'voyasee-3d-atlas'); ?></a>
            <?php endif; ?>
        </div>

        <?php if ($has_plan_links || $has_decide_links): ?>
        <div class="v3datlas-footer-section">
            <p class="v3datlas-footer-section-label"><?php echo esc_html__('Voyasee Trip-Planning Tools', 'voyasee-3d-atlas'); ?></p>
            <div class="v3datlas-footer-cards">
                <?php if ($trip_readiness): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($trip_readiness); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#10004;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Trip Readiness Checklist', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__("Make sure nothing gets left behind before you go.", 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($hub_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($hub_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#9741;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Smart Travel Hub', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('One place for guides, tips, and destination stories.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($map_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($map_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#128506;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Interactive Travel Map', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Browse destinations visually before you decide.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($month_planner_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($month_planner_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#128197;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Travel Month Planner', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Find the best month to visit any destination.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($budget_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($budget_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#129518;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Trip Budget Calculator', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Estimate what your trip will really cost.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($quiz_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($quiz_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#10068;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Destination Quiz', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Not sure where to go? Answer a few questions.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($comparison_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($comparison_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#9878;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Destination Comparison Tool', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Compare two destinations side by side.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($packing_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($packing_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#127890;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Smart Packing List Generator', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Get a packing list tailored to your trip.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($scam_shield_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($scam_shield_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#128737;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Travel Scam Shield', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Learn the common scams at your destination.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($jetlag_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($jetlag_url); ?>">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#127769;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Jet Lag Recovery Planner', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Adjust your sleep schedule before you fly.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($has_book_links || $has_safety_links): ?>
        <div class="v3datlas-footer-section v3datlas-footer-section--affiliate">
            <p class="v3datlas-footer-section-label"><?php echo esc_html__('Book & Travel Safe', 'voyasee-3d-atlas'); ?></p>
            <div class="v3datlas-footer-cards v3datlas-footer-cards--compact">
                <?php if ($booking_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($booking_url); ?>" rel="nofollow sponsored noopener" target="_blank">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#127976;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Booking.com', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Find and book your accommodation.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($aviasales_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($aviasales_url); ?>" rel="nofollow sponsored noopener" target="_blank">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#9992;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Aviasales', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Search and compare flight deals.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($kiwi_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($kiwi_url); ?>" rel="nofollow sponsored noopener" target="_blank">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#129373;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Kiwi.com', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Book flights with flexible routing.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($safetywing_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($safetywing_url); ?>" rel="nofollow sponsored noopener" target="_blank">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#128735;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('SafetyWing Insurance', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Travel insurance built for nomads.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
                <?php if ($visa_url): ?>
                <a class="v3datlas-footer-card" href="<?php echo esc_url($visa_url); ?>" rel="nofollow sponsored noopener" target="_blank">
                    <span class="v3datlas-footer-card-icon" aria-hidden="true">&#128706;</span>
                    <span class="v3datlas-footer-card-title"><?php echo esc_html__('Check Visa Requirements', 'voyasee-3d-atlas'); ?></span>
                    <span class="v3datlas-footer-card-desc"><?php echo esc_html__('Confirm entry rules before you fly.', 'voyasee-3d-atlas'); ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="v3datlas-footer-bottom">
            <div class="v3datlas-footer-wordmark"><?php echo esc_html__('Voyasee', 'voyasee-3d-atlas'); ?></div>
            <p class="v3datlas-footer-tagline"><?php echo esc_html__('Interactive World Map — every place, one map.', 'voyasee-3d-atlas'); ?></p>
            <div class="v3datlas-footer-legal">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__('voyasee.com', 'voyasee-3d-atlas'); ?></a>
                <span class="v3datlas-footer-version">v<?php echo esc_html(V3DA_VERSION); ?></span>
            </div>
        </div>
    </footer>
</section>
