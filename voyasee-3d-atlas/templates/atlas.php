<?php
/**
 * @var string $uid
 * @var string $config JSON-encoded {restBase, markers, strings}.
 * @var array<string,array> $groups Destinations grouped by region.
 * @var array<string,string> $settings
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
// Only one Booking.com link is ever shown in the footer, not both approved
// market variants -- the EU/EEA link is preferred, falling back to the
// Asia-Pacific/Middle East one only if the EU link isn't configured.
$booking_url = ($settings['affiliate_booking_eu'] ?? '') ?: ($settings['affiliate_booking_apac'] ?? '');
$aviasales_url = $settings['affiliate_aviasales'] ?? '';
$kiwi_url = $settings['affiliate_kiwi'] ?? '';
$safetywing_url = $settings['affiliate_safetywing'] ?? '';
$visa_url = $settings['affiliate_visa'] ?? '';

// Hero CTA deliberately doesn't name a specific Voyasee tool (avoids reading
// like it's pointing away to a competing "explore the map" concept right
// next to this Atlas) -- it still links to whichever discovery tool is
// configured, just with generic wording.
$hero_cta_url = $trip_readiness ?: ($quiz_url ?: $map_url);

$has_plan_links = $trip_readiness || $hub_url || $map_url || $month_planner_url || $budget_url;
$has_decide_links = $quiz_url || $comparison_url || $packing_url || $scam_shield_url || $jetlag_url;
$has_book_links = $booking_url || $aviasales_url || $kiwi_url;
$has_safety_links = $safetywing_url || $visa_url;
?>
<section class="v3datlas-root" id="<?php echo esc_attr($uid); ?>" data-v3datlas-root data-v3datlas-config="<?php echo esc_attr($config); ?>">
    <div class="v3datlas-starfield" aria-hidden="true"></div>

    <div class="v3datlas-hero">
        <p class="v3datlas-eyebrow"><?php echo esc_html__('Voyasee World Story Atlas', 'voyasee-3d-atlas'); ?></p>
        <h2 class="v3datlas-title"><?php echo esc_html__('An interactive globe of every place Voyasee has covered', 'voyasee-3d-atlas'); ?></h2>
        <p class="v3datlas-intro"><?php echo esc_html__('Spin the globe, click a destination for weather and country notes, or browse the full A–Z list below.', 'voyasee-3d-atlas'); ?></p>
        <?php if ($hero_cta_url): ?>
            <p class="v3datlas-hero-cta">
                <a href="<?php echo esc_url($hero_cta_url); ?>"><?php echo esc_html__('Not sure where to start? Find your next destination', 'voyasee-3d-atlas'); ?></a>
            </p>
        <?php endif; ?>
    </div>

    <div class="v3datlas-stage">
        <div class="v3datlas-globe-mount" data-v3datlas-globe-mount aria-hidden="true">
            <noscript><?php echo esc_html__('Enable JavaScript to view the interactive globe.', 'voyasee-3d-atlas'); ?></noscript>
        </div>

        <aside class="v3datlas-sidebar" data-v3datlas-sidebar hidden aria-label="<?php echo esc_attr__('Destination details', 'voyasee-3d-atlas'); ?>">
            <button type="button" class="v3datlas-sidebar-close" data-v3datlas-sidebar-close aria-label="<?php echo esc_attr__('Close', 'voyasee-3d-atlas'); ?>">&times;</button>
            <div class="v3datlas-sidebar-body" data-v3datlas-sidebar-body>
                <p class="v3datlas-sidebar-loading"><?php echo esc_html__('Loading…', 'voyasee-3d-atlas'); ?></p>
            </div>
        </aside>
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

    <?php if ($has_plan_links || $has_decide_links || $has_book_links || $has_safety_links): ?>
    <footer class="v3datlas-footer">
        <div class="v3datlas-footer-columns">
            <?php if ($has_plan_links): ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Plan Your Trip', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <?php if ($trip_readiness): ?><li><a href="<?php echo esc_url($trip_readiness); ?>"><?php echo esc_html__('Trip Readiness Checklist', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($hub_url): ?><li><a href="<?php echo esc_url($hub_url); ?>"><?php echo esc_html__('Smart Travel Hub', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($map_url): ?><li><a href="<?php echo esc_url($map_url); ?>"><?php echo esc_html__('Interactive Travel Map', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($month_planner_url): ?><li><a href="<?php echo esc_url($month_planner_url); ?>"><?php echo esc_html__('Travel Month Planner', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($budget_url): ?><li><a href="<?php echo esc_url($budget_url); ?>"><?php echo esc_html__('Trip Budget Calculator', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if ($has_decide_links): ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Decide & Prepare', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <?php if ($quiz_url): ?><li><a href="<?php echo esc_url($quiz_url); ?>"><?php echo esc_html__('Destination Quiz', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($comparison_url): ?><li><a href="<?php echo esc_url($comparison_url); ?>"><?php echo esc_html__('Destination Comparison Tool', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($packing_url): ?><li><a href="<?php echo esc_url($packing_url); ?>"><?php echo esc_html__('Smart Packing List Generator', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($scam_shield_url): ?><li><a href="<?php echo esc_url($scam_shield_url); ?>"><?php echo esc_html__('Travel Scam Shield', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($jetlag_url): ?><li><a href="<?php echo esc_url($jetlag_url); ?>"><?php echo esc_html__('Jet Lag Recovery Planner', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if ($has_book_links): ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Book Your Trip', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <?php if ($booking_url): ?><li><a href="<?php echo esc_url($booking_url); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Booking.com', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($aviasales_url): ?><li><a href="<?php echo esc_url($aviasales_url); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Aviasales', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($kiwi_url): ?><li><a href="<?php echo esc_url($kiwi_url); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Kiwi.com', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Travel Safe', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <?php if ($safetywing_url): ?><li><a href="<?php echo esc_url($safetywing_url); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('SafetyWing Insurance', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($visa_url): ?><li><a href="<?php echo esc_url($visa_url); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Check visa requirements', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__('voyasee.com', 'voyasee-3d-atlas'); ?></a></li>
                </ul>
            </div>
        </div>
    </footer>
    <?php endif; ?>
</section>
