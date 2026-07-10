<?php
/**
 * Server-rendered country guide (Phase 2) and service-specific guide (Phase 3).
 * @var string $code        ISO alpha-2.
 * @var array|null $overview  From VTC_Calculator::country_overview().
 * @var string|null $service  Service key when this is a service page, else null.
 */
defined('ABSPATH') || exit;

$country = $overview['country'];
$name = $country['name'];
$region = $country['region'];
$cslug = VTC_Data::country_slug($name);

// Service-specific display value (from the real dataset row).
$service_display = '';
$service_label = '';
if ($service) {
    foreach ($overview['rows'] as $row) {
        if ($row['key'] === $service) {
            $service_display = $row['display'];
            $service_label = $row['label'];
        }
    }
}

// Services that are actually tipped here, for internal linking.
$tipped = [];
if (empty($country['flags']['not_customary']) && empty($country['flags']['tipping_offensive'])) {
    foreach ($overview['rows'] as $row) {
        $band = $country['services'][$row['key']];
        if (($band[1] ?? 0) > 0 || ($band[2] ?? 0) > 0) {
            $tipped[] = $row;
        }
    }
}

// A few related countries in the same region.
$related = [];
foreach (VTC_Data::country_list() as $c) {
    if ($c['region'] === $region && $c['code'] !== $code) {
        $related[] = $c;
    }
}
shuffle($related);
$related = array_slice($related, 0, 8);
?>
<main class="vtc-country-page">
    <div class="vtc-country-intro">
        <p class="vtc-country-eyebrow">
            <a href="<?php echo esc_url(home_url('/tipping-guides/')); ?>"><?php echo esc_html__('Voyasee Tipping Guides', 'voyasee-tipping-calculator'); ?></a>
            · <a href="<?php echo esc_url(home_url('/tipping-in-' . VTC_Data::region_slug($region) . '/')); ?>"><?php echo esc_html($region); ?></a>
        </p>
        <?php if ($service): ?>
            <h1 class="vtc-country-h1"><?php echo esc_html(sprintf(__('How Much to Tip %s in %s', 'voyasee-tipping-calculator'), VTC_Data::service_phrase($service), $name)); ?></h1>
            <p class="vtc-country-lead">
                <?php
                if ('Not expected' === $service_display) {
                    echo esc_html(sprintf(__('Tipping %s is not generally expected in %s. %s', 'voyasee-tipping-calculator'), VTC_Data::service_phrase($service), $name, $country['note']));
                } else {
                    echo esc_html(sprintf(__('For %s in %s, the usual guidance is %s. Enter your exact amount below for the precise tip.', 'voyasee-tipping-calculator'), VTC_Data::service_phrase($service), $name, $service_display));
                }
                ?>
            </p>
            <p class="vtc-country-back"><a href="<?php echo esc_url(home_url('/tipping-in-' . $cslug . '/')); ?>">← <?php echo esc_html(sprintf(__('Full tipping guide for %s', 'voyasee-tipping-calculator'), $name)); ?></a></p>
        <?php else: ?>
            <h1 class="vtc-country-h1"><?php echo esc_html(sprintf(__('How Much to Tip in %s', 'voyasee-tipping-calculator'), $name)); ?></h1>
            <p class="vtc-country-lead"><?php echo esc_html($country['note']); ?></p>
        <?php endif; ?>
    </div>

    <?php echo do_shortcode('[voyasee_tipping_calculator country="' . esc_attr($code) . '"' . ($service ? ' service="' . esc_attr($service) . '"' : '') . ']'); ?>

    <?php if (!empty($tipped)): ?>
    <nav class="vtc-related" aria-label="<?php echo esc_attr(sprintf(__('Tipping by service in %s', 'voyasee-tipping-calculator'), $name)); ?>">
        <h2 class="vtc-related-title"><?php echo esc_html(sprintf(__('Tipping by service in %s', 'voyasee-tipping-calculator'), $name)); ?></h2>
        <ul class="vtc-related-list">
            <?php foreach ($tipped as $row): ?>
                <?php if ($row['key'] === $service) continue; ?>
                <li><a href="<?php echo esc_url(home_url('/tipping-in-' . $cslug . '/' . VTC_Data::service_slug($row['key']) . '/')); ?>"><?php echo esc_html(sprintf(__('Tipping %s', 'voyasee-tipping-calculator'), VTC_Data::service_phrase($row['key']))); ?> — <?php echo esc_html($row['display']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php endif; ?>

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
