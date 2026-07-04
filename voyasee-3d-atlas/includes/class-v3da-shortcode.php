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
        wp_enqueue_script('v3da-maplibre');
        wp_enqueue_script_module('v3da-app');

        $uid = 'v3da-' . wp_unique_id();
        $settings = V3DA_Admin::get_settings();
        $destinations = V3DA_DB::get_all(['status' => 'active', 'orderby' => 'region']);

        $markers = self::build_markers($destinations);
        $groups = self::group_by_region($destinations);

        $config = wp_json_encode([
            'restBase' => esc_url_raw(rest_url('voyasee-3d-atlas/v1/')),
            'markers' => $markers,
            'arcs' => V3DA_Admin::get_featured_arcs(),
            'strings' => [
                'loading' => __('Loading…', 'voyasee-3d-atlas'),
                'weatherUnavailable' => __('Weather data is temporarily unavailable for this destination.', 'voyasee-3d-atlas'),
                'countryUnavailable' => __('Country details are temporarily unavailable for this destination.', 'voyasee-3d-atlas'),
                'loadError' => __('This destination could not be loaded. Please try again.', 'voyasee-3d-atlas'),
                'close' => __('Close', 'voyasee-3d-atlas'),
                'mapUnavailable' => __('The interactive map isn\'t available in this browser. Browse all destinations in the list below.', 'voyasee-3d-atlas'),
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
        // Marker glow only needs to be roughly fresh, not live-to-the-second,
        // and as the destination count grows this is one term lookup per
        // destination on every single page load carrying the shortcode --
        // worth a short cache rather than repeating it on every request.
        // Invalidated immediately on any admin save/delete/auto-map via
        // V3DA_Admin::maybe_purge_page_cache(), so this TTL is just a
        // safety net, not the only way it stays fresh.
        $counts = get_transient('v3da_post_counts');
        if (!is_array($counts)) {
            $counts = [];
            foreach ($destinations as $d) {
                $counts[$d['id']] = V3DA_Content::term_post_count($d['content_taxonomy'], $d['content_term_slug']);
            }
            set_transient('v3da_post_counts', $counts, 15 * MINUTE_IN_SECONDS);
        }
        $max_count = max(1, ...array_values($counts ?: [1]));

        $markers = [];
        foreach ($destinations as $d) {
            // Normalized 0-1 "how much content exists for this destination"
            // signal, used to scale marker radius/glow on the map -- a
            // purely cosmetic, non-essential signal (a destination with no
            // linked content still renders and works identically otherwise).
            $weight = round(min(1, ($counts[$d['id']] ?? 0) / $max_count), 4);
            $markers[] = [
                'slug' => $d['slug'],
                'name' => $d['name'],
                'lat' => (float) $d['lat'],
                'lng' => (float) $d['lng'],
                'weight' => $weight,
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
            $place = [
                '@type' => 'Place',
                'name' => $d['name'],
                'address' => ['@type' => 'PostalAddress', 'addressCountry' => $d['country']],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => (float) $d['lat'], 'longitude' => (float) $d['lng']],
            ];
            // No 'url' is set here on purpose when this destination has no
            // real mapped category/tag -- this Atlas never links a
            // destination to a site-search results page or an unrelated
            // article; every destination's own weather/country/fact data is
            // shown directly in the on-page sidebar instead.
            $link = V3DA_Content::term_link($d['content_taxonomy'], $d['content_term_slug']);
            if ($link) $place['url'] = $link;
            if ($d['signature_line']) $place['description'] = $d['signature_line'];
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => $place,
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
