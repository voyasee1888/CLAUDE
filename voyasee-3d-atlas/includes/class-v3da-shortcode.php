<?php
defined('ABSPATH') || exit;

final class V3DA_Shortcode {
    private static bool $schema_printed = false;

    public static function init(): void {
        add_shortcode('voyasee_3d_atlas', [self::class, 'render']);
    }

    public static function render(array $atts = []): string {
        wp_enqueue_style('v3da-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:wght@400;500;700&display=swap', [], null);
        wp_enqueue_style('v3da-frontend');
        wp_enqueue_script_module('v3da-app');

        $uid = 'v3da-' . wp_unique_id();
        $settings = V3DA_Admin::get_settings();
        $destinations = V3DA_DB::get_all(['status' => 'active', 'orderby' => 'region']);

        $markers = self::build_markers($destinations);
        $groups = self::group_by_region($destinations);

        $config = wp_json_encode([
            'restBase' => esc_url_raw(rest_url('voyasee-3d-atlas/v1/')),
            'markers' => $markers,
            'strings' => [
                'loading' => __('Loading…', 'voyasee-3d-atlas'),
                'noArticles' => __('No articles yet for this destination.', 'voyasee-3d-atlas'),
                'weatherUnavailable' => __('Weather data is temporarily unavailable for this destination.', 'voyasee-3d-atlas'),
                'countryUnavailable' => __('Country details are temporarily unavailable for this destination.', 'voyasee-3d-atlas'),
                'loadError' => __('This destination could not be loaded. Please try again.', 'voyasee-3d-atlas'),
                'close' => __('Close', 'voyasee-3d-atlas'),
            ],
        ]);

        ob_start();
        include V3DA_DIR . 'templates/atlas.php';
        $html = (string) ob_get_clean();

        if (!self::$schema_printed && !empty($destinations)) {
            $html .= self::destinations_schema($destinations);
            self::$schema_printed = true;
        }

        return $html;
    }

    private static function build_markers(array $destinations): array {
        $counts = [];
        $max_count = 1;
        foreach ($destinations as $d) {
            $count = V3DA_Content::term_post_count($d['content_taxonomy'], $d['content_term_slug']);
            $counts[$d['id']] = $count;
            if ($count > $max_count) $max_count = $count;
        }

        $markers = [];
        foreach ($destinations as $d) {
            $glow = 0.35 + (0.65 * min(1, $counts[$d['id']] / $max_count));
            $markers[] = [
                'slug' => $d['slug'],
                'name' => $d['name'],
                'lat' => (float) $d['lat'],
                'lng' => (float) $d['lng'],
                'size' => round(0.04 + (0.03 * $glow), 4),
            ];
        }
        return $markers;
    }

    /**
     * @return array<string,array> Destinations grouped by region, each entry sorted by name.
     */
    private static function group_by_region(array $destinations): array {
        $groups = [];
        foreach ($destinations as $d) {
            $region = '' !== $d['region'] ? $d['region'] : __('Other', 'voyasee-3d-atlas');
            $groups[$region][] = $d;
        }
        ksort($groups);
        foreach ($groups as &$items) {
            usort($items, static fn($a, $b) => strcasecmp($a['name'], $b['name']));
        }
        return $groups;
    }

    private static function destinations_schema(array $destinations): string {
        $items = [];
        foreach ($destinations as $i => $d) {
            $link = V3DA_Content::term_link($d['content_taxonomy'], $d['content_term_slug']);
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $d['name'] . ', ' . $d['country'],
                'url' => $link ?: home_url('/'),
            ];
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => __('Voyasee World Story Atlas — Destinations', 'voyasee-3d-atlas'),
            'itemListElement' => $items,
        ];
        return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
