<?php
/**
 * @var string $uid
 * @var string $config JSON-encoded {restBase, markers, strings}.
 * @var array<string,array> $groups Destinations grouped by region.
 * @var array<string,string> $settings
 */
defined('ABSPATH') || exit;

$map_url = $settings['tool_interactive_map'] ?? '';
$quiz_url = $settings['tool_destination_quiz'] ?? '';
$hub_url = $settings['tool_smart_travel_hub'] ?? '';
$booking_eu = $settings['affiliate_booking_eu'] ?? '';
$booking_apac = $settings['affiliate_booking_apac'] ?? '';
$visa_url = $settings['affiliate_visa'] ?? '';
?>
<section class="v3datlas-root" id="<?php echo esc_attr($uid); ?>" data-v3datlas-root data-v3datlas-config="<?php echo esc_attr($config); ?>">
    <div class="v3datlas-hero">
        <p class="v3datlas-eyebrow"><?php echo esc_html__('Voyasee World Story Atlas', 'voyasee-3d-atlas'); ?></p>
        <h2 class="v3datlas-title"><?php echo esc_html__('An interactive globe of every place Voyasee has covered', 'voyasee-3d-atlas'); ?></h2>
        <p class="v3datlas-intro"><?php echo esc_html__('Spin the globe, click a destination for weather and country notes, or browse the full A–Z list below.', 'voyasee-3d-atlas'); ?></p>
        <?php if ($map_url || $quiz_url): ?>
            <p class="v3datlas-hero-cta">
                <?php if ($map_url): ?>
                    <a href="<?php echo esc_url($map_url); ?>"><?php echo esc_html__('Not sure where to start? Try the Interactive Travel Map', 'voyasee-3d-atlas'); ?></a>
                <?php elseif ($quiz_url): ?>
                    <a href="<?php echo esc_url($quiz_url); ?>"><?php echo esc_html__('Not sure where to start? Take the Destination Quiz', 'voyasee-3d-atlas'); ?></a>
                <?php endif; ?>
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

        <?php if (empty($groups)): ?>
            <p class="v3datlas-list-empty"><?php echo esc_html__('Destinations are being added — check back soon.', 'voyasee-3d-atlas'); ?></p>
        <?php endif; ?>

        <?php foreach ($groups as $region => $items): ?>
            <details class="v3datlas-region">
                <summary><?php echo esc_html($region); ?> <span class="v3datlas-region-count">(<?php echo count($items); ?>)</span></summary>
                <ul class="v3datlas-region-list">
                    <?php foreach ($items as $d):
                        $link = V3DA_Content::term_link($d['content_taxonomy'], $d['content_term_slug']);
                        if (!$link) continue;
                    ?>
                        <li><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($d['name']); ?></a>, <?php echo esc_html($d['country']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    </div>

    <?php if ($map_url || $quiz_url || $hub_url || $booking_eu || $booking_apac || $visa_url): ?>
    <footer class="v3datlas-footer">
        <div class="v3datlas-footer-columns">
            <?php if ($map_url || $quiz_url || $hub_url): ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Explore Voyasee', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <?php if ($map_url): ?><li><a href="<?php echo esc_url($map_url); ?>"><?php echo esc_html__('Interactive Travel Map', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($quiz_url): ?><li><a href="<?php echo esc_url($quiz_url); ?>"><?php echo esc_html__('Destination Quiz', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($hub_url): ?><li><a href="<?php echo esc_url($hub_url); ?>"><?php echo esc_html__('Smart Travel Hub', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if ($booking_eu || $booking_apac): ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Stay', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <?php if ($booking_eu): ?><li><a href="<?php echo esc_url($booking_eu); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Booking.com', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                    <?php if ($booking_apac): ?><li><a href="<?php echo esc_url($booking_apac); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Booking.com', 'voyasee-3d-atlas'); ?></a></li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if ($visa_url): ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Documents', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url($visa_url); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html__('Check visa requirements', 'voyasee-3d-atlas'); ?></a></li>
                </ul>
            </div>
            <?php endif; ?>
            <div class="v3datlas-footer-col">
                <h3><?php echo esc_html__('Voyasee', 'voyasee-3d-atlas'); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__('voyasee.com', 'voyasee-3d-atlas'); ?></a></li>
                </ul>
            </div>
        </div>
    </footer>
    <?php endif; ?>
</section>
