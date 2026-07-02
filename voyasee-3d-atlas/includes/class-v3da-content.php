<?php
defined('ABSPATH') || exit;

final class V3DA_Content {
    public static function term_post_count(string $taxonomy, string $slug): int {
        if ('' === $slug) return 0;
        $term = get_term_by('slug', $slug, $taxonomy);
        if (!$term || is_wp_error($term)) return 0;
        return (int) $term->count;
    }

    public static function term_link(string $taxonomy, string $slug): ?string {
        if ('' === $slug) return null;
        $term = get_term_by('slug', $slug, $taxonomy);
        if (!$term || is_wp_error($term)) return null;
        $link = get_term_link($term, $taxonomy);
        return is_wp_error($link) ? null : $link;
    }

    /**
     * @return array<int,array{title:string,url:string,excerpt:string,thumbnail:?string,date:string}>
     */
    public static function related_articles(string $taxonomy, string $slug, int $limit = 6): array {
        if ('' === $slug) return [];

        $tax_query = [[
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => $slug,
        ]];

        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => max(1, min(12, $limit)),
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => $tax_query,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);

        $articles = [];
        foreach ($query->posts as $post) {
            $articles[] = [
                'title' => get_the_title($post),
                'url' => get_permalink($post),
                'excerpt' => wp_strip_all_tags(get_the_excerpt($post)),
                'thumbnail' => get_the_post_thumbnail_url($post, 'medium') ?: null,
                'date' => get_the_date('', $post),
            ];
        }
        wp_reset_postdata();
        return $articles;
    }

    /**
     * Mirrors the graceful-degradation pattern already used by
     * voyasee-where-to-stay-matcher: check function_exists() before calling
     * into Weather Bridge, never let a missing/inactive plugin break the page.
     *
     * @return array|null
     */
    public static function weather_snapshot(float $lat, float $lng): ?array {
        if (!function_exists('voyasee_weather_get_current')) return null;

        $current = voyasee_weather_get_current($lat, $lng);
        if (!is_wp_error($current) && !empty($current['current'])) {
            $c = $current['current'];
            return [
                'type' => 'current',
                'tempC' => $c['tempC'] ?? null,
                'feelsLikeC' => $c['feelsLikeC'] ?? null,
                'humidityPct' => $c['humidityPct'] ?? null,
                'conditionText' => $c['condition']['text'] ?? null,
                'conditionIconUrl' => $c['condition']['iconUrl'] ?? null,
            ];
        }

        if (!function_exists('voyasee_weather_get_climate_normals')) return null;
        $normals = voyasee_weather_get_climate_normals($lat, $lng);
        if (is_wp_error($normals) || empty($normals['climateNormals']['months'])) return null;

        $c = $normals['climateNormals'];
        $month_index = (int) gmdate('n') - 1;
        return [
            'type' => 'climate_normals',
            'month' => $c['months'][$month_index] ?? null,
            'tempMeanC' => $c['temperatureMeanC'][$month_index] ?? null,
            'tempMaxC' => $c['temperatureMaxC'][$month_index] ?? null,
            'tempMinC' => $c['temperatureMinC'][$month_index] ?? null,
            'precipitationMmMonth' => $c['precipitationMmMonth'][$month_index] ?? null,
        ];
    }

    /**
     * @return array|null
     */
    public static function country_snapshot(string $country_code): ?array {
        if ('' === $country_code || !function_exists('voyasee_country_data_get_country')) return null;

        $record = voyasee_country_data_get_country($country_code);
        if (is_wp_error($record) || !is_array($record)) return null;

        $core = $record['core'] ?? [];
        $travel = $record['travel'] ?? [];
        $safety = $record['safety'] ?? [];
        $currency = $core['currencies'][0] ?? null;

        return [
            'currencyCode' => $currency['code'] ?? null,
            'currencyName' => $currency['name'] ?? null,
            'currencySymbol' => $currency['symbol'] ?? null,
            'callingCode' => $core['communications']['callingCodes'][0] ?? null,
            'timezone' => $core['timezones']['iana'][0] ?? null,
            'drivingSide' => $travel['road']['drivingSide'] ?? null,
            'electricalPlugTypes' => $travel['electrical']['plugTypes'] ?? [],
            'tippingGuidance' => $travel['tipping']['guidance'] ?? null,
            'tapWaterGuidance' => $travel['tapWater']['guidance'] ?? null,
            'emergencyPolice' => $safety['emergencyNumbers']['police'] ?? null,
            'emergencyAmbulance' => $safety['emergencyNumbers']['ambulance'] ?? null,
            'advisoryLinks' => $safety['officialVerificationLinks']['globalTravelAdviceDirectories'] ?? [],
        ];
    }
}
