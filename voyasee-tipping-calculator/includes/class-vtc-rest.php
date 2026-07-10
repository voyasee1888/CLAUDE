<?php
/**
 * REST API. The front-end calculates locally for instant results, so these
 * endpoints exist mainly for integrations and for verifying the same numbers
 * server-side. All data is plugin-owned; no external service is contacted.
 */

defined('ABSPATH') || exit;

final class VTC_REST {
    private const NS = 'voyasee-tipping/v1';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void {
        register_rest_route(self::NS, '/calculate', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'calculate'],
            'args'                => [
                'country'  => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'service'  => ['default' => 'restaurant', 'sanitize_callback' => 'sanitize_text_field'],
                'amount'   => ['default' => 0],
                'quality'  => ['default' => 'standard', 'sanitize_callback' => 'sanitize_text_field'],
                'party'    => ['default' => 1],
                'units'    => ['default' => 1],
                'round_up' => ['default' => 0],
                'home'     => ['default' => '', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        register_rest_route(self::NS, '/country/(?P<code>[A-Za-z]{2})', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'country'],
        ]);
    }

    public static function calculate(WP_REST_Request $req): WP_REST_Response {
        $result = VTC_Calculator::calculate([
            'country'       => (string) $req['country'],
            'service'       => (string) $req['service'],
            'amount'        => (float) $req['amount'],
            'quality'       => (string) $req['quality'],
            'party'         => (int) $req['party'],
            'units'         => (int) $req['units'],
            'round_up'      => (bool) (int) $req['round_up'],
            'home_currency' => (string) $req['home'],
        ]);
        if (null === $result) {
            return new WP_REST_Response(['ok' => false, 'error' => 'unknown_country'], 404);
        }
        return new WP_REST_Response(['ok' => true, 'result' => $result], 200);
    }

    public static function country(WP_REST_Request $req): WP_REST_Response {
        $c = VTC_Data::resolve_country((string) $req['code']);
        if (null === $c) {
            return new WP_REST_Response(['ok' => false, 'error' => 'unknown_country'], 404);
        }
        return new WP_REST_Response(['ok' => true, 'country' => $c], 200);
    }
}
