<?php
defined('ABSPATH') || exit;

final class V3DA_Shortcode {
    public static function init(): void {
        add_shortcode('voyasee_3d_atlas', [self::class, 'render']);
    }

    public static function render(array $atts = []): string {
        wp_enqueue_style('v3da-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:wght@400;500;700&display=swap', [], null);
        wp_enqueue_style('v3da-frontend');
        wp_enqueue_script('v3da-app');

        $uid = 'v3da-' . wp_unique_id();

        ob_start();
        include V3DA_DIR . 'templates/atlas.php';
        return (string) ob_get_clean();
    }
}
