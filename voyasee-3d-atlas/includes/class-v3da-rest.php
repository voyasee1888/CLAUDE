<?php
defined('ABSPATH') || exit;

final class V3DA_REST {
    private const NS = 'voyasee-3d-atlas/v1';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void {
        register_rest_route(self::NS, '/destinations/(?P<slug>[a-z0-9-]{1,191})', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'destination'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);
    }

    public static function public_permission(): bool|WP_Error {
        $ip = self::client_ip();
        $bucket = floor(time() / 60);
        $key = 'v3da_rl_' . md5($ip . '|' . $bucket);
        $count = (int) get_transient($key);
        if ($count >= 60) {
            return new WP_Error('v3da_rate_limited', __('Too many requests. Please try again shortly.', 'voyasee-3d-atlas'), ['status' => 429]);
        }
        set_transient($key, $count + 1, 70);
        return true;
    }

    public static function destination(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $slug = sanitize_title((string) $request['slug']);
        $destination = V3DA_DB::get_by_slug($slug);
        if (!$destination || 'active' !== $destination['status']) {
            return new WP_Error('v3da_not_found', __('Destination not found.', 'voyasee-3d-atlas'), ['status' => 404]);
        }

        $term_link = V3DA_Content::term_link($destination['content_taxonomy'], $destination['content_term_slug']);
        $articles = V3DA_Content::related_articles($destination['content_taxonomy'], $destination['content_term_slug'], 6);
        $weather = V3DA_Content::weather_snapshot((float) $destination['lat'], (float) $destination['lng']);
        $country = V3DA_Content::country_snapshot($destination['country_code']);
        $best_time = V3DA_TravelMonth::best_time($destination['slug']);
        $holiday = V3DA_Content::upcoming_holiday($destination['country_code']);

        $hero_image_url = $destination['hero_image_id']
            ? wp_get_attachment_image_url((int) $destination['hero_image_id'], 'large')
            : V3DA_Content::term_latest_thumbnail($destination['content_taxonomy'], $destination['content_term_slug']);

        $all_destinations = V3DA_DB::get_all(['status' => 'active']);

        $response = new WP_REST_Response([
            'ok' => true,
            'destination' => [
                'slug' => $destination['slug'],
                'name' => $destination['name'],
                'country' => $destination['country'],
                'country_code' => $destination['country_code'],
                'region' => $destination['region'],
                'lat' => (float) $destination['lat'],
                'lng' => (float) $destination['lng'],
                'term_link' => $term_link,
                'post_count' => V3DA_Content::term_post_count($destination['content_taxonomy'], $destination['content_term_slug']),
                'signature_line' => $destination['signature_line'],
                'did_you_know' => $destination['did_you_know'],
                'hero_image_url' => $hero_image_url ?: null,
            ],
            'articles' => $articles,
            'weather' => $weather,
            'country' => $country,
            'bestTime' => $best_time,
            'upcomingHoliday' => $holiday,
            'nearby' => V3DA_Content::nearby_destinations($all_destinations, $destination, 3),
            'sameCountry' => V3DA_Content::same_country_destinations($all_destinations, $destination, 4),
        ], 200);
        $response->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=900');
        return $response;
    }

    private static function client_ip(): string {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                if (filter_var($ip, FILTER_VALIDATE_IP)) return substr($ip, 0, 64);
            }
        }
        return substr(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown', 0, 64);
    }
}
