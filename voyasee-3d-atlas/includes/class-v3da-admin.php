<?php
defined('ABSPATH') || exit;

final class V3DA_Admin {
    private const CAP = 'manage_options';

    private const SETTINGS_FIELDS = [
        // Voyasee tools
        'tool_trip_readiness',
        'tool_smart_travel_hub',
        'tool_interactive_map',
        'tool_travel_month_planner',
        'tool_trip_budget_calculator',
        'tool_destination_quiz',
        'tool_destination_comparison',
        'tool_smart_packing_list',
        'tool_travel_scam_shield',
        'tool_jet_lag_planner',
        // Affiliate partners
        'affiliate_booking_eu',
        'affiliate_booking_apac',
        'affiliate_aviasales',
        'affiliate_kiwi',
        'affiliate_safetywing',
        'affiliate_visa',
    ];

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_post_v3da_save_destination', [self::class, 'handle_save']);
        add_action('admin_post_v3da_delete_destination', [self::class, 'handle_delete']);
        add_action('admin_post_v3da_save_settings', [self::class, 'handle_save_settings']);
        add_action('admin_post_v3da_run_automap', [self::class, 'handle_run_automap']);
        add_action('admin_post_v3da_create_category', [self::class, 'handle_create_category']);
        add_action('admin_enqueue_scripts', [self::class, 'maybe_enqueue']);
    }

    public static function register_menu(): void {
        add_menu_page(
            __('Voyasee 3D Atlas', 'voyasee-3d-atlas'),
            __('3D Atlas', 'voyasee-3d-atlas'),
            self::CAP,
            'voyasee-3d-atlas',
            [self::class, 'render'],
            'dashicons-admin-site-alt3',
            58
        );
        add_submenu_page(
            'voyasee-3d-atlas',
            __('Voyasee 3D Atlas — Health Check', 'voyasee-3d-atlas'),
            __('Health Check', 'voyasee-3d-atlas'),
            self::CAP,
            'voyasee-3d-atlas-health',
            [self::class, 'render_health_check']
        );
        add_submenu_page(
            'voyasee-3d-atlas',
            __('Voyasee 3D Atlas — Settings', 'voyasee-3d-atlas'),
            __('Settings', 'voyasee-3d-atlas'),
            self::CAP,
            'voyasee-3d-atlas-settings',
            [self::class, 'render_settings']
        );
    }

    public static function maybe_enqueue(string $hook): void {
        if (!in_array($hook, ['toplevel_page_voyasee-3d-atlas', '3d-atlas_page_voyasee-3d-atlas-settings', '3d-atlas_page_voyasee-3d-atlas-health'], true)) return;
        wp_enqueue_style('v3da-admin', V3DA_URL . 'assets/css/admin.css', [], V3DA_VERSION);
        wp_enqueue_media();
        wp_enqueue_script('v3da-admin', V3DA_URL . 'assets/js/admin.js', ['jquery'], V3DA_VERSION, true);
    }

    public static function render_health_check(): void {
        if (!current_user_can(self::CAP)) return;
        $destinations = V3DA_DB::get_all();
        $report = get_transient('v3da_automap_report');

        $stats = ['total' => count($destinations), 'mapped' => 0, 'unmapped' => 0, 'with_hero' => 0, 'coord_outliers' => []];
        foreach ($destinations as $d) {
            $link = V3DA_Content::term_link($d['content_taxonomy'], $d['content_term_slug']);
            if ($link) $stats['mapped']++; else $stats['unmapped']++;
            if ($d['hero_image_id']) $stats['with_hero']++;

            if (function_exists('voyasee_country_data_get_country') && $d['country_code']) {
                $country = voyasee_country_data_get_country($d['country_code']);
                if (!is_wp_error($country)) {
                    $centroid = $country['core']['geography']['centroid'] ?? null;
                    $capital = $country['core']['capital'] ?? null;
                    if ($centroid) {
                        $dist_centroid = V3DA_Content::haversine_km((float) $d['lat'], (float) $d['lng'], (float) $centroid['latitude'], (float) $centroid['longitude']);
                        $dist_capital = $capital ? V3DA_Content::haversine_km((float) $d['lat'], (float) $d['lng'], (float) $capital['latitude'], (float) $capital['longitude']) : PHP_FLOAT_MAX;
                        if ($dist_centroid > 3500 && $dist_capital > 3500) {
                            $stats['coord_outliers'][] = ['name' => $d['name'], 'country' => $d['country'], 'distanceKm' => round(min($dist_centroid, $dist_capital))];
                        }
                    }
                }
            }
        }

        include V3DA_DIR . 'admin/views/health-check.php';
    }

    public static function handle_run_automap(): void {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You are not allowed to do this.', 'voyasee-3d-atlas'));
        check_admin_referer('v3da_run_automap');

        $force_recheck = !empty($_POST['force_recheck']);
        $report = V3DA_AutoMap::run_bulk($force_recheck);
        set_transient('v3da_automap_report', $report, 300);

        self::maybe_purge_page_cache();
        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas-health', 'v3da_notice' => 'automapped'], admin_url('admin.php')));
        exit;
    }

    public static function handle_create_category(): void {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You are not allowed to do this.', 'voyasee-3d-atlas'));
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        check_admin_referer('v3da_create_category_' . $id);

        $result = $id ? V3DA_AutoMap::create_category_for($id) : new WP_Error('v3da_missing_id', __('Missing destination.', 'voyasee-3d-atlas'));

        $notice = is_wp_error($result) ? 'category-error' : 'category-created';
        self::maybe_purge_page_cache();
        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas', 'v3da_notice' => $notice], admin_url('admin.php')));
        exit;
    }

    /**
     * @return array<int,array{0:string,1:string}>
     */
    public static function get_featured_arcs(): array {
        $stored = get_option('v3da_featured_arcs', null);
        return is_array($stored) ? $stored : [];
    }

    /**
     * Parses one "slug-one, slug-two" pair per line into a clean list,
     * silently dropping malformed lines rather than erroring -- this is a
     * convenience field, not a strict schema the site owner needs to
     * fight with.
     *
     * @return array<int,array{0:string,1:string}>
     */
    private static function sanitize_featured_arcs(string $raw): array {
        $pairs = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $parts = array_map('sanitize_title', array_map('trim', explode(',', $line)));
            $parts = array_values(array_filter($parts));
            if (2 === count($parts)) $pairs[] = [$parts[0], $parts[1]];
        }
        return array_slice($pairs, 0, 10);
    }

    /**
     * A site that has never saved the Settings screen gets the real,
     * registry-sourced tool/affiliate URLs (see includes/data/default-tool-
     * links.php) out of the box, so the footer never ships empty. The
     * moment an admin saves Settings even once, every field -- including
     * an intentionally blank one meant to hide that link -- is explicitly
     * present in the saved option and always wins over this default.
     *
     * @return array<string,string>
     */
    public static function get_settings(): array {
        $settings = get_option('v3da_settings', []);
        if (!is_array($settings)) $settings = [];
        $defaults = require V3DA_DIR . 'includes/data/default-tool-links.php';
        $out = [];
        foreach (self::SETTINGS_FIELDS as $field) {
            $value = array_key_exists($field, $settings) ? $settings[$field] : ($defaults[$field] ?? '');
            $out[$field] = esc_url_raw((string) $value);
        }
        return $out;
    }

    public static function get_pexels_api_key(): string {
        return trim((string) get_option('v3da_pexels_api_key', ''));
    }

    public static function render_settings(): void {
        if (!current_user_can(self::CAP)) return;
        $settings = self::get_settings();
        $pexels_api_key = self::get_pexels_api_key();
        $notice = isset($_GET['v3da_notice']) ? sanitize_key(wp_unslash($_GET['v3da_notice'])) : '';
        include V3DA_DIR . 'admin/views/settings.php';
    }

    public static function handle_save_settings(): void {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You are not allowed to do this.', 'voyasee-3d-atlas'));
        check_admin_referer('v3da_save_settings');

        $settings = [];
        foreach (self::SETTINGS_FIELDS as $field) {
            $settings[$field] = esc_url_raw((string) wp_unslash($_POST[$field] ?? ''));
        }
        update_option('v3da_settings', $settings, false);
        update_option('v3da_featured_arcs', self::sanitize_featured_arcs((string) wp_unslash($_POST['featured_arcs'] ?? '')), false);
        update_option('v3da_pexels_api_key', sanitize_text_field((string) wp_unslash($_POST['pexels_api_key'] ?? '')), false);

        self::maybe_purge_page_cache();
        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas-settings', 'v3da_notice' => 'saved'], admin_url('admin.php')));
        exit;
    }

    public static function render(): void {
        if (!current_user_can(self::CAP)) return;

        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : 'list';
        if (in_array($action, ['new', 'edit'], true)) {
            self::render_edit($action);
            return;
        }
        self::render_list();
    }

    private static function render_list(): void {
        $destinations = V3DA_DB::get_all(['orderby' => 'region']);
        $notice = isset($_GET['v3da_notice']) ? sanitize_key(wp_unslash($_GET['v3da_notice'])) : '';
        include V3DA_DIR . 'admin/views/destinations-list.php';
    }

    private static function render_edit(string $action): void {
        $destination = null;
        if ('edit' === $action) {
            $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
            $destination = $id ? V3DA_DB::get($id) : null;
            if (!$destination) {
                wp_die(esc_html__('Destination not found.', 'voyasee-3d-atlas'));
            }
        }
        $error = get_transient('v3da_form_error_' . get_current_user_id());
        delete_transient('v3da_form_error_' . get_current_user_id());
        include V3DA_DIR . 'admin/views/destination-edit.php';
    }

    public static function handle_save(): void {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You are not allowed to do this.', 'voyasee-3d-atlas'));
        check_admin_referer('v3da_save_destination');

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $data = [
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'slug' => sanitize_text_field(wp_unslash($_POST['slug'] ?? '')),
            'country' => sanitize_text_field(wp_unslash($_POST['country'] ?? '')),
            'country_code' => sanitize_text_field(wp_unslash($_POST['country_code'] ?? '')),
            'region' => sanitize_text_field(wp_unslash($_POST['region'] ?? '')),
            'lat' => wp_unslash($_POST['lat'] ?? ''),
            'lng' => wp_unslash($_POST['lng'] ?? ''),
            'content_taxonomy' => sanitize_text_field(wp_unslash($_POST['content_taxonomy'] ?? 'category')),
            'content_term_slug' => sanitize_text_field(wp_unslash($_POST['content_term_slug'] ?? '')),
            'signature_line' => sanitize_text_field(wp_unslash($_POST['signature_line'] ?? '')),
            'did_you_know' => sanitize_text_field(wp_unslash($_POST['did_you_know'] ?? '')),
            'hero_image_id' => wp_unslash($_POST['hero_image_id'] ?? ''),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')),
            'sort_order' => wp_unslash($_POST['sort_order'] ?? 0),
        ];

        $result = $id ? V3DA_DB::update($id, $data) : V3DA_DB::insert($data);

        if (is_wp_error($result)) {
            set_transient('v3da_form_error_' . get_current_user_id(), $result->get_error_message(), 60);
            $back = add_query_arg([
                'page' => 'voyasee-3d-atlas',
                'action' => $id ? 'edit' : 'new',
                'id' => $id ?: null,
            ], admin_url('admin.php'));
            wp_safe_redirect($back);
            exit;
        }

        self::maybe_purge_page_cache();
        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas', 'v3da_notice' => 'saved'], admin_url('admin.php')));
        exit;
    }

    public static function handle_delete(): void {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You are not allowed to do this.', 'voyasee-3d-atlas'));
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        check_admin_referer('v3da_delete_destination_' . $id);

        if ($id) V3DA_DB::delete($id);

        self::maybe_purge_page_cache();
        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas', 'v3da_notice' => 'deleted'], admin_url('admin.php')));
        exit;
    }

    /**
     * Destinations are rendered server-side into the shortcode's HTML, so any
     * page carrying [voyasee_3d_atlas] can be served stale by a full-page
     * cache (LiteSpeed Cache / QUIC.cloud) after an admin edit. LiteSpeed's
     * own plugin listens for this action to purge both its local disk cache
     * and the QUIC.cloud CDN edge together -- it's a documented no-op action
     * that does nothing if LiteSpeed Cache isn't installed, so this is safe
     * to call unconditionally on any host.
     */
    private static function maybe_purge_page_cache(): void {
        delete_transient('v3da_post_counts');
        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }
    }
}
