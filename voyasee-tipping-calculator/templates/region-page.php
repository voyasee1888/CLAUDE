<?php
/**
 * Region hub page (Phase 3): /tipping-in-{region}/ — a country-by-country
 * overview for one region, each linking to its full guide.
 * @var string $region  Region name.
 */
defined('ABSPATH') || exit;

$countries = [];
foreach (VTC_Data::country_list() as $c) {
    if ($c['region'] === $region) {
        $countries[] = $c;
    }
}
?>
<main class="vtc-country-page">
    <div class="vtc-country-intro">
        <p class="vtc-country-eyebrow"><a href="<?php echo esc_url(home_url('/tipping-guides/')); ?>"><?php echo esc_html__('Voyasee Tipping Guides', 'voyasee-tipping-calculator'); ?></a></p>
        <h1 class="vtc-country-h1"><?php echo esc_html(sprintf(__('Tipping in %s', 'voyasee-tipping-calculator'), $region)); ?></h1>
        <p class="vtc-country-lead"><?php echo esc_html(sprintf(__('Tipping customs vary widely across %s. Pick a country for its full guide and a calculator that gives you the exact tip on any bill.', 'voyasee-tipping-calculator'), $region)); ?></p>
    </div>

    <div class="vtc-region-grid">
        <?php foreach ($countries as $c):
            $country = VTC_Data::resolve_country($c['code']); ?>
            <a class="vtc-region-card" href="<?php echo esc_url(home_url('/tipping-in-' . VTC_Data::country_slug($c['name']) . '/')); ?>">
                <span class="vtc-region-card-name"><?php echo esc_html($c['name']); ?></span>
                <span class="vtc-region-card-verdict"><?php echo esc_html($country['verdict']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</main>
