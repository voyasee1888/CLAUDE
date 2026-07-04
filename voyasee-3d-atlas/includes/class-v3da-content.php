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

    public static function pexels_photo_url(string $slug, string $query): ?string {
        if ('' === $slug || '' === $query) return null;
        $api_key = trim((string) get_option('v3da_pexels_api_key', ''));
        if ('' === $api_key) return null;

        $cache_key = 'v3da_pexels_' . $slug;
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return '' !== $cached ? $cached : null;
        }

        $url = add_query_arg([
            'query' => $query,
            'per_page' => 1,
            'orientation' => 'landscape',
        ], 'https://api.pexels.com/v1/search');

        $response = wp_remote_get($url, [
            'headers' => ['Authorization' => $api_key],
            'timeout' => 8,
        ]);

        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            set_transient($cache_key, '', HOUR_IN_SECONDS);
            return null;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $photo_url = $body['photos'][0]['src']['large'] ?? null;
        $photo_url = is_string($photo_url) ? esc_url_raw($photo_url) : null;

        set_transient($cache_key, $photo_url ?: '', 30 * DAY_IN_SECONDS);
        return $photo_url;
    }

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

    public static function country_snapshot(string $country_code): ?array {
        if ('' === $country_code || !function_exists('voyasee_country_data_get_country')) return null;

        $record = voyasee_country_data_get_country($country_code);
        if (is_wp_error($record) || !is_array($record)) return null;

        $core = $record['core'] ?? [];
        $travel = $record['travel'] ?? [];
        $safety = $record['safety'] ?? [];
        $currency = $core['currencies'][0] ?? null;

        // core.languages and core.capital are structured records
        // ({code,name,localeHint} / {name,latitude,longitude}), not plain
        // strings/scalars -- flatten them here so the frontend never has to
        // guess a shape and risk rendering a raw object.
        $languages = array_values(array_filter(array_map(
            static fn($l) => is_array($l) ? ($l['name'] ?? null) : null,
            $core['languages'] ?? []
        )));

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
            'population' => $core['populationSnapshot']['value'] ?? null,
            'area' => $core['geography']['areaKm2'] ?? null,
            'languages' => $languages,
            'capital' => $core['capital']['name'] ?? null,
        ];
    }

    /**
     * Sunrise/sunset times from sunrise-sunset.org (free, no key, attribution required).
     * Cached for 6 hours per lat/lng pair.
     */
    public static function sunrise_sunset(float $lat, float $lng, ?string $timezone = null): ?array {
        $cache_key = 'v3da_sun_' . md5($lat . '|' . $lng . '|' . gmdate('Y-m-d'));
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return is_array($cached) ? $cached : null;
        }

        $url = add_query_arg([
            'lat' => $lat,
            'lng' => $lng,
            'formatted' => 0,
            'date' => gmdate('Y-m-d'),
        ], 'https://api.sunrise-sunset.org/json');

        $response = wp_remote_get($url, ['timeout' => 6]);
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            set_transient($cache_key, 'null', 2 * HOUR_IN_SECONDS);
            return null;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if ('OK' !== ($body['status'] ?? '') || empty($body['results'])) {
            set_transient($cache_key, 'null', 2 * HOUR_IN_SECONDS);
            return null;
        }

        $r = $body['results'];
        $result = [
            'sunrise' => $r['sunrise'] ?? null,
            'sunset' => $r['sunset'] ?? null,
            'day_length' => $r['day_length'] ?? null,
            'timezone' => $timezone,
        ];

        set_transient($cache_key, $result, 6 * HOUR_IN_SECONDS);
        return $result;
    }

    /**
     * Exchange rate from Frankfurter (free, open-source, ECB data).
     * Cached for 24 hours. Returns rate relative to USD.
     */
    public static function exchange_rate(?string $currency_code): ?array {
        if (null === $currency_code || '' === $currency_code || 'USD' === $currency_code) return null;

        $cache_key = 'v3da_fx_' . strtoupper($currency_code);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return is_array($cached) ? $cached : null;
        }

        $url = 'https://api.frankfurter.dev/v1/latest?base=USD&symbols=' . urlencode(strtoupper($currency_code));
        $response = wp_remote_get($url, ['timeout' => 6]);
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            set_transient($cache_key, 'null', 2 * HOUR_IN_SECONDS);
            return null;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $rate = $body['rates'][strtoupper($currency_code)] ?? null;
        if (null === $rate) {
            set_transient($cache_key, 'null', 2 * HOUR_IN_SECONDS);
            return null;
        }

        $result = [
            'base' => 'USD',
            'target' => strtoupper($currency_code),
            'rate' => round((float) $rate, 2),
            'date' => $body['date'] ?? gmdate('Y-m-d'),
        ];

        set_transient($cache_key, $result, DAY_IN_SECONDS);
        return $result;
    }

    /**
     * Short Wikipedia excerpt (2-3 sentences) for a destination.
     * Uses MediaWiki REST API (CC BY-SA, free, no key).
     * Cached for 30 days.
     */
    public static function wikipedia_excerpt(string $name, string $country): ?string {
        if ('' === $name) return null;

        $cache_key = 'v3da_wiki_' . sanitize_title($name);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return '' !== $cached ? $cached : null;
        }

        $search_term = $name;
        $url = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode(str_replace(' ', '_', $search_term));
        $response = wp_remote_get($url, [
            'timeout' => 6,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            set_transient($cache_key, '', 7 * DAY_IN_SECONDS);
            return null;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $extract = $body['extract'] ?? '';

        if ('' === $extract) {
            set_transient($cache_key, '', 7 * DAY_IN_SECONDS);
            return null;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $extract, 4);
        $short = implode(' ', array_slice($sentences, 0, 3));
        if (mb_strlen($short) > 400) {
            $short = mb_substr($short, 0, 397) . '...';
        }

        set_transient($cache_key, $short, 30 * DAY_IN_SECONDS);
        return $short;
    }
}
