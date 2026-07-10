<?php
/**
 * Master index (Phase 3): /tipping-guides/ — every country grouped by region.
 */
defined('ABSPATH') || exit;

$by_region = [];
foreach (VTC_Data::country_list() as $c) {
    $by_region[$c['region']][] = $c;
}
ksort($by_region);
$total = count(VTC_Data::all_codes());
?>
<main class="vtc-country-page">
    <div class="vtc-country-intro">
        <h1 class="vtc-country-h1"><?php echo esc_html__('Tipping Guides for Every Country', 'voyasee-tipping-calculator'); ?></h1>
        <p class="vtc-country-lead"><?php echo esc_html(sprintf(__('Culture-aware tipping guidance for %d countries and territories. Choose a destination for its full guide and an exact-tip calculator.', 'voyasee-tipping-calculator'), $total)); ?></p>
    </div>

    <?php foreach ($by_region as $region => $countries): ?>
        <section class="vtc-index-region">
            <h2 class="vtc-related-title">
                <a href="<?php echo esc_url(home_url('/tipping-in-' . VTC_Data::region_slug($region) . '/')); ?>"><?php echo esc_html($region); ?></a>
                <span class="vtc-index-count">(<?php echo count($countries); ?>)</span>
            </h2>
            <ul class="vtc-related-list">
                <?php foreach ($countries as $c): ?>
                    <li><a href="<?php echo esc_url(home_url('/tipping-in-' . VTC_Data::country_slug($c['name']) . '/')); ?>"><?php echo esc_html($c['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</main>
