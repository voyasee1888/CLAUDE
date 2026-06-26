<?php
defined('ABSPATH') || exit;

final class VSB_REST {
    private const NS = 'voyasee-bagfit/v1';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void {
        register_rest_route(self::NS, '/nonce', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'nonce'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/airlines', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'airlines'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/airports', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'airports'],
            'permission_callback' => '__return_true',
            'args' => ['search' => ['sanitize_callback' => 'sanitize_text_field'], 'limit' => ['sanitize_callback' => 'absint']],
        ]);
        register_rest_route(self::NS, '/check', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'check'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);
        register_rest_route(self::NS, '/reverse-search', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'reverse_search'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);
        register_rest_route(self::NS, '/shared-size', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'shared_size'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);
        register_rest_route(self::NS, '/parse-ticket', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'parse_ticket'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);
        register_rest_route(self::NS, '/save', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'save'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);
        register_rest_route(self::NS, '/saved/(?P<token>[A-Za-z0-9_-]{30,100})', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'saved'],
            'permission_callback' => '__return_true',
        ]);
    }


    public static function nonce(): WP_REST_Response {
        // This route deliberately creates the public nonce inside the REST request.
        // WordPress treats unauthenticated REST traffic as user 0, so the returned
        // token remains valid for both visitors and logged-in editors viewing a
        // cached/preview page. Cache-busting is handled by the front end.
        $response = new WP_REST_Response([
            'nonce' => wp_create_nonce('vsb_public'),
            'issued_at' => time(),
        ], 200);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', 'Wed, 11 Jan 1984 05:00:00 GMT');
        return $response;
    }

    public static function public_permission(WP_REST_Request $request): bool|WP_Error {
        $nonce = (string) $request->get_header('X-VSB-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'vsb_public')) {
            return new WP_Error('vsb_nonce', __('The secure session needs to be renewed.', 'voyasee-bagfit'), ['status' => 403]);
        }
        return true;
    }

    public static function airlines(): WP_REST_Response {
        $items = [];
        foreach (VSB_DB::public_airlines() as $airline) {
            $options = [];
            foreach (['personal', 'cabin', 'checked'] as $type) {
                $options[$type] = array_map(static function ($option): array {
                    return [
                        'id' => sanitize_key((string) ($option['id'] ?? '')),
                        'label' => sanitize_text_field((string) ($option['label'] ?? '')),
                        'inclusion' => sanitize_key((string) ($option['inclusion'] ?? 'conditional')),
                        'has_dimensions' => !empty($option['dimensions_mm']) || !empty($option['max_linear_mm']),
                        'has_weight' => !empty($option['max_weight_g']) || !empty($option['combined_weight_g']),
                        'piece_count' => max(0, (int) ($option['piece_count'] ?? 1)),
                    ];
                }, is_array($airline['allowance_options'][$type] ?? null) ? $airline['allowance_options'][$type] : []);
            }
            $items[] = [
                'slug' => sanitize_title((string) $airline['slug']),
                'name' => sanitize_text_field((string) $airline['name']),
                'iata' => sanitize_text_field((string) ($airline['iata'] ?? '')),
                'country' => sanitize_text_field((string) ($airline['country'] ?? '')),
                'aliases' => array_values(array_filter(array_map('sanitize_text_field', (array) ($airline['aliases'] ?? [])))),
                'source_url' => esc_url_raw((string) ($airline['source_url'] ?? '')),
                'last_verified' => sanitize_text_field((string) ($airline['last_verified'] ?? '')),
                'coverage_tier' => sanitize_key((string) ($airline['coverage_tier'] ?? 'directory')),
                'directory_only' => !empty($airline['directory_only']),
                'allowance_options' => $options,
            ];
        }
        return new WP_REST_Response(['airlines' => $items, 'count' => count($items), 'health' => VSB_DB::data_health()], 200);
    }

    public static function airports(WP_REST_Request $request): WP_REST_Response {
        $search = sanitize_text_field((string) $request->get_param('search'));
        $limit = max(1, min(50, absint($request->get_param('limit') ?: 30)));
        $items = VSB_DB::airport_search($search, $limit);
        return new WP_REST_Response(['airports' => $items, 'count' => count($items), 'search' => $search], 200);
    }

    public static function check(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $limited = self::rate_limit('check', 100, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) return $limited;
        $payload = self::payload($request);
        $result = VSB_Engine::check($payload);
        if (is_wp_error($result)) return $result;
        foreach ((array) ($result['bag_results'] ?? []) as $bag_result) {
            $bag_type = (string) ($bag_result['bag']['type'] ?? '');
            foreach ((array) ($bag_result['legs'] ?? []) as $leg) {
                VSB_DB::record_event('check', (string) ($leg['airline']['slug'] ?? ''), $bag_type, (string) ($leg['verdict_code'] ?? ''));
            }
        }
        return new WP_REST_Response(['result' => $result], 200);
    }

    public static function reverse_search(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $limited = self::rate_limit('reverse', 30, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) return $limited;
        $json = $request->get_json_params();
        if (!is_array($json)) $json = [];
        $result = VSB_Engine::reverse_search($json);
        if (is_wp_error($result)) return $result;
        VSB_DB::record_event('reverse_search', '', (string) ($result['bag']['type'] ?? ''), '');
        return new WP_REST_Response(['result' => $result], 200);
    }

    public static function shared_size(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $limited = self::rate_limit('shared', 40, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) return $limited;
        $json = $request->get_json_params();
        if (!is_array($json)) $json = [];
        $result = VSB_Engine::shared_size($json);
        if (is_wp_error($result)) return $result;
        VSB_DB::record_event('shared_size');
        return new WP_REST_Response(['result' => $result], 200);
    }

    public static function parse_ticket(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $limited = self::rate_limit('parse_ticket', 100, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) return $limited;
        $json = $request->get_json_params();
        $line = is_array($json) ? sanitize_text_field((string) ($json['line'] ?? '')) : '';
        return new WP_REST_Response(['result' => VSB_Engine::parse_ticket_line($line)], 200);
    }

    public static function save(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;
        $limited = self::rate_limit('save', 30, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) return $limited;
        $json = $request->get_json_params();
        // Accept a pre-computed result from the client (already ran /check) to avoid duplicate processing.
        // Fall back to re-running the engine only when no result is supplied.
        if (is_array($json) && isset($json['result']) && is_array($json['result']) && !empty($json['result']['schema_version'])) {
            $result = $json['result'];
        } else {
            $result = VSB_Engine::check(self::payload($request));
            if (is_wp_error($result)) return $result;
        }
        VSB_DB::cleanup_saved();
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $token);
        $settings = get_option('vsb_settings', []);
        $days = max(1, min(90, (int) ($settings['share_expiry_days'] ?? 30)));
        $created = current_time('mysql', true);
        $expires = gmdate('Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS);
        $payload = wp_json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen((string) $payload) > 600000) {
            return new WP_Error('vsb_save_size', __('The result is too large to save.', 'voyasee-bagfit'), ['status' => 413]);
        }
        $ok = $wpdb->insert(VSB_DB::saved_table(), ['token_hash' => $hash, 'payload' => $payload, 'created_at' => $created, 'expires_at' => $expires], ['%s', '%s', '%s', '%s']);
        if (false === $ok) return new WP_Error('vsb_save_failed', __('The result could not be saved.', 'voyasee-bagfit'), ['status' => 500]);
        $page = is_array($json) ? esc_url_raw((string) ($json['page_url'] ?? home_url('/'))) : home_url('/');
        return new WP_REST_Response([
            'token' => $token,
            'share_url' => add_query_arg('vsb-result', rawurlencode($token), $page),
            'report_url' => add_query_arg(['action' => 'vsb_report', 'token' => rawurlencode($token)], admin_url('admin-post.php')),
            'expires_at' => gmdate('c', strtotime($expires . ' UTC')),
        ], 201);
    }

    public static function saved(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;
        $token = (string) $request['token'];
        $row = $wpdb->get_row($wpdb->prepare('SELECT payload,expires_at FROM ' . VSB_DB::saved_table() . ' WHERE token_hash=%s', hash('sha256', $token)), ARRAY_A);
        if (!$row) return new WP_Error('vsb_saved_missing', __('This saved result was not found.', 'voyasee-bagfit'), ['status' => 404]);
        if (strtotime($row['expires_at'] . ' UTC') < time()) return new WP_Error('vsb_saved_expired', __('This saved result has expired.', 'voyasee-bagfit'), ['status' => 410]);
        $payload = json_decode((string) $row['payload'], true);
        if (!is_array($payload)) return new WP_Error('vsb_saved_invalid', __('This saved result is unavailable.', 'voyasee-bagfit'), ['status' => 500]);
        return new WP_REST_Response(['result' => $payload, 'expires_at' => gmdate('c', strtotime($row['expires_at'] . ' UTC'))], 200);
    }

    private static function payload(WP_REST_Request $request): array {
        $json = $request->get_json_params();
        if (!is_array($json)) $json = [];
        return [
            'mode' => sanitize_key((string) ($json['mode'] ?? 'full')),
            'bag' => is_array($json['bag'] ?? null) ? $json['bag'] : [],
            'bags' => is_array($json['bags'] ?? null) ? array_slice($json['bags'], 0, 6) : [],
            'flights' => is_array($json['flights'] ?? null) ? array_slice($json['flights'], 0, 10) : [],
            'journey' => is_array($json['journey'] ?? null) ? $json['journey'] : [],
        ];
    }

    private static function rate_limit(string $action, int $limit, int $period): true|WP_Error {
        $key = 'vsb_rl_' . md5($action . '|' . self::client_ip() . '|' . wp_salt('nonce'));
        $count = (int) get_transient($key);
        if ($count >= $limit) return new WP_Error('vsb_rate_limit', __('Too many requests. Please try again later.', 'voyasee-bagfit'), ['status' => 429]);
        set_transient($key, $count + 1, $period);
        return true;
    }

    private static function client_ip(): string {
        // Trust Cloudflare or a single trusted reverse-proxy; otherwise fall back to REMOTE_ADDR.
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                if (filter_var($ip, FILTER_VALIDATE_IP)) return substr($ip, 0, 64);
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $first = trim(explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']))[0]);
            $ip = sanitize_text_field($first);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return substr($ip, 0, 64);
        }
        return substr(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown', 0, 64);
    }
}
