<?php
/**
 * @var string $uid
 * @var string $demo_markers JSON-encoded array of {lat,lng,size}.
 */
defined('ABSPATH') || exit;
?>
<section class="v3datlas-root" id="<?php echo esc_attr($uid); ?>" data-v3datlas-root>
    <div class="v3datlas-hero">
        <p class="v3datlas-eyebrow"><?php echo esc_html__('Voyasee World Story Atlas', 'voyasee-3d-atlas'); ?></p>
        <h2 class="v3datlas-title"><?php echo esc_html__('An interactive globe of every place Voyasee has covered', 'voyasee-3d-atlas'); ?></h2>
        <p class="v3datlas-intro"><?php echo esc_html__('Browse the destinations list below the globe for the full, linkable A–Z index.', 'voyasee-3d-atlas'); ?></p>
    </div>

    <div class="v3datlas-globe-mount" data-v3datlas-globe-mount data-markers="<?php echo esc_attr($demo_markers); ?>" aria-hidden="true">
        <noscript><?php echo esc_html__('Enable JavaScript to view the interactive globe.', 'voyasee-3d-atlas'); ?></noscript>
    </div>

    <div class="v3datlas-destination-list" data-v3datlas-destination-list>
        <!-- Phase 5: visible, crawlable "Browse all destinations A–Z" <details>/<summary> list mounts here. -->
    </div>
</section>
