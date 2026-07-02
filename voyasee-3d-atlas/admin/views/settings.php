<?php
/**
 * @var array<string,string> $settings
 * @var string $notice
 */
defined('ABSPATH') || exit;

$fields = [
    'tool_interactive_map' => __('Interactive Travel Map URL', 'voyasee-3d-atlas'),
    'tool_destination_quiz' => __('Destination Quiz URL', 'voyasee-3d-atlas'),
    'tool_smart_travel_hub' => __('Smart Travel Hub URL', 'voyasee-3d-atlas'),
    'affiliate_booking_eu' => __('Booking.com — EU/Economic Area market link', 'voyasee-3d-atlas'),
    'affiliate_booking_apac' => __('Booking.com — Asia-Pacific/Middle East market link', 'voyasee-3d-atlas'),
    'affiliate_visa' => __('Visa support link (e.g. VisaHQ)', 'voyasee-3d-atlas'),
];
?>
<div class="wrap v3da-admin-wrap">
    <h1><?php echo esc_html__('Voyasee 3D Atlas — Settings', 'voyasee-3d-atlas'); ?></h1>

    <?php if ('saved' === $notice): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Settings saved.', 'voyasee-3d-atlas'); ?></p></div>
    <?php endif; ?>

    <p><?php echo esc_html__('Paste the current URLs from VOYASEE_TOOLS_AND_AFFILIATES_REGISTRY.md. Leave a field blank to hide that link from the hero CTA and footer — nothing is invented or hardcoded.', 'voyasee-3d-atlas'); ?></p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="v3da_save_settings">
        <?php wp_nonce_field('v3da_save_settings'); ?>
        <table class="form-table">
            <?php foreach ($fields as $key => $label): ?>
                <tr>
                    <th><label for="v3da-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td><input id="v3da-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" type="url" class="regular-text" value="<?php echo esc_attr($settings[$key] ?? ''); ?>"></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php submit_button(__('Save Settings', 'voyasee-3d-atlas')); ?>
    </form>
</div>
