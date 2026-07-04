<?php
/**
 * @var array $stats
 * @var array<int,array{name:string,status:string,detail:string}>|false $report
 */
defined('ABSPATH') || exit;

$notice = isset($_GET['v3da_notice']) ? sanitize_key(wp_unslash($_GET['v3da_notice'])) : '';
?>
<div class="wrap v3da-admin-wrap">
    <h1><?php echo esc_html__('Voyasee 3D Atlas — Data Health Check', 'voyasee-3d-atlas'); ?></h1>

    <?php if ('automapped' === $notice): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Auto-map finished — see the report below.', 'voyasee-3d-atlas'); ?></p></div>
    <?php endif; ?>

    <div class="v3da-health-cards">
        <div class="v3da-health-card">
            <strong><?php echo (int) $stats['total']; ?></strong>
            <span><?php echo esc_html__('Total destinations', 'voyasee-3d-atlas'); ?></span>
        </div>
        <div class="v3da-health-card">
            <strong><?php echo (int) $stats['mapped']; ?></strong>
            <span><?php echo esc_html__('Linked to a real category/tag', 'voyasee-3d-atlas'); ?></span>
        </div>
        <div class="v3da-health-card">
            <strong><?php echo (int) $stats['unmapped']; ?></strong>
            <span><?php echo esc_html__('Not yet linked to a category/tag', 'voyasee-3d-atlas'); ?></span>
        </div>
        <div class="v3da-health-card">
            <strong><?php echo (int) $stats['with_hero']; ?></strong>
            <span><?php echo esc_html__('Have a manually-set photo', 'voyasee-3d-atlas'); ?></span>
        </div>
    </div>

    <p class="description">
        <?php echo esc_html__('"Not yet linked to a category/tag" no longer affects what a visitor sees when clicking that destination -- every destination always shows its own weather/country/fact data in the sidebar regardless. It only slightly reduces that destination\'s marker glow/size on the map (based on how much related content exists) and means its structured-data entry has no article URL attached.', 'voyasee-3d-atlas'); ?>
    </p>
    <p class="description">
        <?php if ($stats['pexels_configured']): ?>
            <?php echo esc_html__('Pexels photo API: configured. "Have a manually-set photo" only counts images picked by hand in 3D Atlas -> Destinations -- destinations without one still get a live photo of the actual place from Pexels automatically, which isn\'t reflected in that count.', 'voyasee-3d-atlas'); ?>
        <?php else: ?>
            <?php echo esc_html__('Pexels photo API: not configured. Destinations without a manually-set photo currently show no photo at all. Add a free Pexels API key in Settings -> Destination Photos to have those destinations automatically show a real photo instead.', 'voyasee-3d-atlas'); ?>
        <?php endif; ?>
    </p>

    <h2><?php echo esc_html__('Auto-Map Content', 'voyasee-3d-atlas'); ?></h2>
    <p><?php echo esc_html__('Looks for a category/tag literally named after each destination first, and otherwise requires at least two published posts with that destination\'s name in their own title sharing a category/tag -- a single passing mention elsewhere in a post\'s body is never enough to map a destination on its own.', 'voyasee-3d-atlas'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="v3da_run_automap">
        <?php wp_nonce_field('v3da_run_automap'); ?>
        <p>
            <label>
                <input type="checkbox" name="force_recheck" value="1">
                <?php echo esc_html__('Also re-check destinations that are already mapped', 'voyasee-3d-atlas'); ?>
            </label>
            <br>
            <span class="description"><?php echo esc_html__('Use this once after updating the plugin if a destination looks mapped to the wrong category/tag -- a mapping that no longer meets the confidence bar above is cleared rather than left pointing at the wrong content.', 'voyasee-3d-atlas'); ?></span>
        </p>
        <?php submit_button(__('Run Auto-Map Now', 'voyasee-3d-atlas'), 'primary', 'submit', false); ?>
    </form>

    <?php if ($report): ?>
        <h3><?php echo esc_html__('Last auto-map report', 'voyasee-3d-atlas'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th><?php echo esc_html__('Destination', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Result', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Detail', 'voyasee-3d-atlas'); ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($report as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['name']); ?></td>
                        <td><?php echo esc_html($row['status']); ?></td>
                        <td><?php echo esc_html($row['detail']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($stats['coord_outliers'])): ?>
        <h2><?php echo esc_html__('Coordinate check', 'voyasee-3d-atlas'); ?></h2>
        <p><?php echo esc_html__('Flagged only if a destination is unusually far from its own country (using Voyasee Country Intelligence\'s country data) — worth a quick look, though a large country can legitimately produce a false flag (e.g. Hawaii vs. the continental US).', 'voyasee-3d-atlas'); ?></p>
        <ul>
            <?php foreach ($stats['coord_outliers'] as $o): ?>
                <li><?php echo esc_html($o['name'] . ', ' . $o['country'] . ' — ' . $o['distanceKm'] . ' km from its country\'s centroid/capital'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (function_exists('voyasee_country_data_get_country')): ?>
        <h2><?php echo esc_html__('Coordinate check', 'voyasee-3d-atlas'); ?></h2>
        <p><?php echo esc_html__('No coordinate outliers found.', 'voyasee-3d-atlas'); ?></p>
    <?php else: ?>
        <h2><?php echo esc_html__('Coordinate check', 'voyasee-3d-atlas'); ?></h2>
        <p><?php echo esc_html__('Voyasee Country Intelligence isn\'t active, so this check can\'t run right now.', 'voyasee-3d-atlas'); ?></p>
    <?php endif; ?>
</div>
