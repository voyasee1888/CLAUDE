<?php
defined('ABSPATH') || exit;

final class V3DA_Shortcode {
    public static function init(): void {
        add_shortcode('voyasee_3d_atlas', [self::class, 'render']);
    }

    public static function render(array $atts = []): string {
        wp_enqueue_style('v3da-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:wght@400;500;700&display=swap', [], null);
        wp_enqueue_style('v3da-frontend');
        wp_enqueue_script_module('v3da-app');

        $uid = 'v3da-' . wp_unique_id();
        $demo_markers = wp_json_encode(self::demo_markers());

        ob_start();
        include V3DA_DIR . 'templates/atlas.php';
        return (string) ob_get_clean();
    }

    /**
     * Phase 2 placeholder markers, used only to prove out the COBE render
     * pipeline. Replaced by the real V3DA_DB-backed destination set in Phase 3.
     */
    private static function demo_markers(): array {
        return [
            ['lat' => 37.7749, 'lng' => -122.4194, 'size' => 0.06],
            ['lat' => 51.5072, 'lng' => -0.1276, 'size' => 0.06],
            ['lat' => 35.6762, 'lng' => 139.6503, 'size' => 0.06],
            ['lat' => -33.8688, 'lng' => 151.2093, 'size' => 0.06],
            ['lat' => -22.9068, 'lng' => -43.1729, 'size' => 0.06],
            ['lat' => -33.9249, 'lng' => 18.4241, 'size' => 0.06],
        ];
    }
}
