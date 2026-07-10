<?php
/**
 * Server-rendered per-country landing page body (Phase 2).
 * @var string $code       ISO alpha-2 of the country.
 * @var array|null $overview  From VTC_Calculator::country_overview().
 */
defined('ABSPATH') || exit;

$country = $overview['country'];
$name = $country['name'];
$region = $country['region'];

// Related countries in the same region for internal linking.
$related = [];
foreach (VTC_Data::country_list() as $c) {
    if ($c['region'] === $region && $c['code'] !== $code) {
        $related[] = $c;
    }
}
shuffle($related);
$related = array_slice($related, 0, 10);
?>
<main class="vtc-country-page">
    <div class="vtc-country-intro">
        <p class="vtc-country-eyebrow"><?php echo esc_html__('Voyasee Tipping Guide', 'voyasee-tipping-calculator'); ?> · <?php echo esc_html($region); ?></p>
        <h1 class="vtc-country-h1"><?php echo esc_html(sprintf(__('How Much to Tip in %s', 'voyasee-tipping-calculator'), $name)); ?></h1>
        <p class="vtc-country-lead"><?php echo esc_html($country['note']); ?></p>
    </div>

    <?php echo do_shortcode('[voyasee_tipping_calculator country="' . esc_attr($code) . '"]'); ?>

    <?php if (!empty($related)): ?>
    <nav class="vtc-related" aria-label="<?php echo esc_attr(sprintf(__('Tipping guides near %s', 'voyasee-tipping-calculator'), $name)); ?>">
        <h2 class="vtc-related-title"><?php echo esc_html(sprintf(__('Tipping in other %s destinations', 'voyasee-tipping-calculator'), $region)); ?></h2>
        <ul class="vtc-related-list">
            <?php foreach ($related as $c): ?>
                <li><a href="<?php echo esc_url(home_url('/tipping-in-' . VTC_Data::country_slug($c['name']) . '/')); ?>"><?php echo esc_html(sprintf(__('Tipping in %s', 'voyasee-tipping-calculator'), $c['name'])); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php endif; ?>
</main>
