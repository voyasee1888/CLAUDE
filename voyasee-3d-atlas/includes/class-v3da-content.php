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
     * Automatic hero-image fallback: if a destination has no manually
     * picked hero image, use the featured image of the most recent post in
     * its mapped category/tag instead -- so hero images fill themselves in
     * as your content library grows, no per-destination media picking
     * required once 1a (auto-mapping) has connected real content.
     */
    public static function term_latest_thumbnail(string $taxonomy, string $slug): ?string {
        if ('' === $slug) return null;
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slug]],
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);
        foreach ($query->posts as $post) {
            $url = get_the_post_thumbnail_url($post, 'large');
            if ($url) {
                wp_reset_postdata();
                return $url;
            }
        }
        wp_reset_postdata();
        return null;
    }

    /**
     * Next public holiday in this country from today, using Country
     * Intelligence's pre-compiled local holiday calendars (no external
     * HTTP call). Mirrors the same graceful degradation pattern.
     */
    public static function upcoming_holiday(string $country_code): ?array {
        if ('' === $country_code || !function_exists('voyasee_country_data_get_holidays')) return null;

        $today = current_time('Y-m-d');
        $year = (int) current_time('Y');
        foreach ([$year, $year + 1] as $y) {
            $result = voyasee_country_data_get_holidays($country_code, $y);
            if (is_wp_error($result) || empty($result['holidays'])) continue;
            foreach ($result['holidays'] as $holiday) {
                $date = $holiday['date'] ?? '';
                if ($date >= $today) {
                    return ['name' => $holiday['name'] ?? '', 'date' => $date];
                }
            }
        }
        return null;
    }

    /**
     * @param array $all Full destination rows (as from V3DA_DB::get_all()).
     * @return array<int,array{name:string,slug:string,distanceKm:float}>
     */
    public static function nearby_destinations(array $all, array $current, int $limit = 3): array {
        $withDistance = [];
        foreach ($all as $d) {
            if ($d['id'] === $current['id'] || 'active' !== $d['status']) continue;
            $withDistance[] = [
                'name' => $d['name'],
                'slug' => $d['slug'],
                'distanceKm' => self::haversine_km((float) $current['lat'], (float) $current['lng'], (float) $d['lat'], (float) $d['lng']),
            ];
        }
        usort($withDistance, static fn($a, $b) => $a['distanceKm'] <=> $b['distanceKm']);
        return array_slice($withDistance, 0, $limit);
    }

    /**
     * @return array<int,array{name:string,slug:string}>
     */
    public static function same_country_destinations(array $all, array $current, int $limit = 4): array {
        if ('' === $current['country_code']) return [];
        $matches = [];
        foreach ($all as $d) {
            if ($d['id'] === $current['id'] || 'active' !== $d['status']) continue;
            if ($d['country_code'] !== $current['country_code']) continue;
            $matches[] = ['name' => $d['name'], 'slug' => $d['slug']];
            if (count($matches) >= $limit) break;
        }
        return $matches;
    }

    public static function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
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
