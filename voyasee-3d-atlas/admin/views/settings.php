<?php
/**
 * @var array<string,string> $settings
 * @var string $pexels_api_key
 * @var string $notice
 */
defined('ABSPATH') || exit;

$tool_fields = [
    'tool_trip_readiness' => __('Travel Passport / Trip Readiness Checklist URL', 'voyasee-3d-atlas'),
    'tool_smart_travel_hub' => __('Smart Travel Hub URL', 'voyasee-3d-atlas'),
    'tool_interactive_map' => __('Interactive Travel Map URL', 'voyasee-3d-atlas'),
    'tool_travel_month_planner' => __('Travel Month Planner URL', 'voyasee-3d-atlas'),
    'tool_trip_budget_calculator' => __('Trip Budget Calculator URL', 'voyasee-3d-atlas'),
    'tool_destination_quiz' => __('Destination Quiz URL', 'voyasee-3d-atlas'),
    'tool_destination_comparison' => __('Travel Destination Comparison Tool URL', 'voyasee-3d-atlas'),
    'tool_smart_packing_list' => __('Smart Packing List Generator URL', 'voyasee-3d-atlas'),
    'tool_travel_scam_shield' => __('Travel Scam Shield URL', 'voyasee-3d-atlas'),
    'tool_jet_lag_planner' => __('Jet Lag Recovery Planner URL', 'voyasee-3d-atlas'),
];

$affiliate_fields = [
    'affiliate_booking_eu' => __('Booking.com — EU/Economic Area market link', 'voyasee-3d-atlas'),
    'affiliate_booking_apac' => __('Booking.com — Asia-Pacific/Middle East market link', 'voyasee-3d-atlas'),
    'affiliate_aviasales' => __('Aviasales (flight comparison) URL', 'voyasee-3d-atlas'),
    'affiliate_kiwi' => __('Kiwi.com (flight routing) URL', 'voyasee-3d-atlas'),
    'affiliate_safetywing' => __('SafetyWing (travel insurance) URL', 'voyasee-3d-atlas'),
    'affiliate_visa' => __('VisaHQ (visa support) URL', 'voyasee-3d-atlas'),
];
?>
<div class="wrap v3da-admin-wrap">
    <h1><?php echo esc_html__('Voyasee 3D Atlas — Settings', 'voyasee-3d-atlas'); ?></h1>

    <?php if ('saved' === $notice): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Settings saved.', 'voyasee-3d-atlas'); ?></p></div>
    <?php endif; ?>

    <p><?php echo esc_html__('These fields are pre-filled with the current URLs from VOYASEE_TOOLS_AND_AFFILIATES_REGISTRY.md, so the footer works out of the box — nothing here is invented. Edit any field to correct or replace it, or clear one and save to hide that link from the footer.', 'voyasee-3d-atlas'); ?></p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="v3da_save_settings">
        <?php wp_nonce_field('v3da_save_settings'); ?>

        <h2><?php echo esc_html__('Voyasee Tools', 'voyasee-3d-atlas'); ?></h2>
        <table class="form-table">
            <?php foreach ($tool_fields as $key => $label): ?>
                <tr>
                    <th><label for="v3da-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td><input id="v3da-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" type="url" class="regular-text" value="<?php echo esc_attr($settings[$key] ?? ''); ?>"></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2><?php echo esc_html__('Affiliate Partners', 'voyasee-3d-atlas'); ?></h2>
        <table class="form-table">
            <?php foreach ($affiliate_fields as $key => $label): ?>
                <tr>
                    <th><label for="v3da-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td><input id="v3da-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" type="url" class="regular-text" value="<?php echo esc_attr($settings[$key] ?? ''); ?>"></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2><?php echo esc_html__('Destination Photos', 'voyasee-3d-atlas'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="v3da-pexels-api-key"><?php echo esc_html__('Pexels API key', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-pexels-api-key" name="pexels_api_key" type="text" class="regular-text" autocomplete="off" value="<?php echo esc_attr($pexels_api_key); ?>">
                    <p class="description"><?php echo esc_html__('Optional. When set, a destination with no manually picked photo (below) shows a real photo of that actual place, fetched from Pexels\' free photo API. This Atlas never pulls a destination\'s photo from a blog post — get a free key at pexels.com/api. Leave blank to show no photo for destinations without one manually set.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php echo esc_html__('Map Featured Routes', 'voyasee-3d-atlas'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="v3da-featured-arcs"><?php echo esc_html__('Featured route pairs', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <textarea id="v3da-featured-arcs" name="featured_arcs" class="large-text code" rows="6"><?php
                        foreach (V3DA_Admin::get_featured_arcs() as $pair) {
                            echo esc_textarea($pair[0] . ', ' . $pair[1]) . "\n";
                        }
                    ?></textarea>
                    <p class="description"><?php echo esc_html__('One destination-slug pair per line (e.g. "tokyo, paris"). Draws a curved flight-route line between each pair on the map. Leave blank to use the built-in default route set.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'voyasee-3d-atlas')); ?>
    </form>
</div>
