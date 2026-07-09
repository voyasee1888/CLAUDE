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
        // v3da-app declares d3, supercluster, topojson, the ISO table and the
        // world-topology data as dependencies, so enqueuing it pulls all of
        // them in, in the right order.
        wp_enqueue_script('v3da-app');

        $uid = 'v3da-' . wp_unique_id();
        $settings = V3DA_Admin::get_settings();
        $destinations = V3DA_DB::get_all(['status' => 'active', 'orderby' => 'region']);

        $markers = self::build_markers($destinations);
        $groups = self::group_by_region($destinations);
        $regions = self::extract_regions($destinations);

        $config = wp_json_encode([
            'restBase' => esc_url_raw(rest_url('voyasee-3d-atlas/v1/')),
            'worldDataUrl' => esc_url_raw(V3DA_URL . 'assets/data/world-countries-110m.topo.json'),
            'markers' => $markers,
            'arcs' => V3DA_Admin::get_featured_arcs(),
            'regions' => $regions,
            'strings' => [
                'loading' => __('Loading…', 'voyasee-3d-atlas'),
                'weatherUnavailable' => __('Weather data is temporarily unavailable for this destination.', 'voyasee-3d-atlas'),
                'countryUnavailable' => __('Country details are temporarily unavailable for this destination.', 'voyasee-3d-atlas'),
                'loadError' => __('This destination could not be loaded. Please try again.', 'voyasee-3d-atlas'),
                'close' => __('Close', 'voyasee-3d-atlas'),
                'mapUnavailable' => __('The interactive map isn\'t available in this browser. Browse all destinations in the list below.', 'voyasee-3d-atlas'),
                'mapAriaLabel' => __('Interactive world map of Voyasee destinations', 'voyasee-3d-atlas'),
                'countryOverviewLabel' => __('Country overview', 'voyasee-3d-atlas'),
                'countryNoData' => __('No information is available for this country yet.', 'voyasee-3d-atlas'),
                'destinationsInCountry' => __('Destinations we cover here', 'voyasee-3d-atlas'),
                'countryFallbackName' => __('This country', 'voyasee-3d-atlas'),
                'filterAll' => __('All', 'voyasee-3d-atlas'),
                'surpriseMe' => __('Surprise me', 'voyasee-3d-atlas'),
                'compare' => __('Compare', 'voyasee-3d-atlas'),
                'visited' => __('Visited', 'voyasee-3d-atlas'),
                'wantToGo' => __('Want to go', 'voyasee-3d-atlas'),
                'share' => __('Share', 'voyasee-3d-atlas'),
                'wikiAttribution' => __('Source: Wikipedia (CC BY-SA)', 'voyasee-3d-atlas'),
                'sunriseAttribution' => __('Sunrise-Sunset.org', 'voyasee-3d-atlas'),
                'exchangeAttribution' => __('Frankfurter.dev (ECB)', 'voyasee-3d-atlas'),
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
            $weight = round(min(1, ($counts[$d['id']] ?? 0) / $max_count), 4);
            $best_for_raw = $d['best_for'] ?? '';
            $markers[] = [
                'slug' => $d['slug'],
                'name' => $d['name'],
                'region' => $d['region'],
                'countryCode' => $d['country_code'],
                'lat' => (float) $d['lat'],
                'lng' => (float) $d['lng'],
                'weight' => $weight,
                'costLevel' => (int) ($d['cost_level'] ?? 0),
                'bestFor' => '' !== $best_for_raw ? explode(',', $best_for_raw) : [],
            ];
        }
        return $markers;
    }

    private static function extract_regions(array $destinations): array {
        $regions = [];
        foreach ($destinations as $d) {
            $r = $d['region'] ?? '';
            if ('' !== $r && !in_array($r, $regions, true)) {
                $regions[] = $r;
            }
        }
        sort($regions);
        return $regions;
    }

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
            'name' => __('Interactive World Map — Destinations', 'voyasee-3d-atlas'),
            'itemListElement' => $items,
        ];
        return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
