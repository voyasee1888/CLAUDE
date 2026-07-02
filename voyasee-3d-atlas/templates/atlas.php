<?php
/**
 * @var string $uid
 */
defined('ABSPATH') || exit;
?>
<section class="v3datlas-root" id="<?php echo esc_attr($uid); ?>" data-v3datlas-root>
    <div class="v3datlas-hero">
        <p class="v3datlas-eyebrow"><?php echo esc_html__('Voyasee World Story Atlas', 'voyasee-3d-atlas'); ?></p>
        <h2 class="v3datlas-title"><?php echo esc_html__('An interactive globe of every place Voyasee has covered', 'voyasee-3d-atlas'); ?></h2>
        <p class="v3datlas-intro"><?php echo esc_html__('The 3D globe is being finalized. In the meantime, browse the destinations list below.', 'voyasee-3d-atlas'); ?></p>
    </div>

    <div class="v3datlas-globe-mount" data-v3datlas-globe-mount aria-hidden="true">
        <!-- Phase 2: COBE canvas mounts here, lazy-booted via IntersectionObserver. -->
    </div>

    <div class="v3datlas-destination-list" data-v3datlas-destination-list>
        <!-- Phase 5: visible, crawlable "Browse all destinations A–Z" <details>/<summary> list mounts here. -->
    </div>
</section>
