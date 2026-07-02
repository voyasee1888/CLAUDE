<?php
defined('ABSPATH') || exit;

final class V3DA_Admin {
    private const CAP = 'manage_options';

    private const SETTINGS_FIELDS = [
        'tool_interactive_map',
        'tool_destination_quiz',
        'tool_smart_travel_hub',
        'affiliate_booking_eu',
        'affiliate_booking_apac',
        'affiliate_visa',
    ];

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_post_v3da_save_destination', [self::class, 'handle_save']);
        add_action('admin_post_v3da_delete_destination', [self::class, 'handle_delete']);
        add_action('admin_post_v3da_save_settings', [self::class, 'handle_save_settings']);
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
            __('Voyasee 3D Atlas — Settings', 'voyasee-3d-atlas'),
            __('Settings', 'voyasee-3d-atlas'),
            self::CAP,
            'voyasee-3d-atlas-settings',
            [self::class, 'render_settings']
        );
    }

    public static function maybe_enqueue(string $hook): void {
        if (!in_array($hook, ['toplevel_page_voyasee-3d-atlas', '3d-atlas_page_voyasee-3d-atlas-settings'], true)) return;
        wp_enqueue_style('v3da-admin', V3DA_URL . 'assets/css/admin.css', [], V3DA_VERSION);
        wp_enqueue_media();
        wp_enqueue_script('v3da-admin', V3DA_URL . 'assets/js/admin.js', ['jquery'], V3DA_VERSION, true);
    }

    /**
     * @return array<string,string>
     */
    public static function get_settings(): array {
        $settings = get_option('v3da_settings', []);
        if (!is_array($settings)) $settings = [];
        $out = [];
        foreach (self::SETTINGS_FIELDS as $field) {
            $out[$field] = isset($settings[$field]) ? esc_url_raw((string) $settings[$field]) : '';
        }
        return $out;
    }

    public static function render_settings(): void {
        if (!current_user_can(self::CAP)) return;
        $settings = self::get_settings();
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

        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas', 'v3da_notice' => 'saved'], admin_url('admin.php')));
        exit;
    }

    public static function handle_delete(): void {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You are not allowed to do this.', 'voyasee-3d-atlas'));
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        check_admin_referer('v3da_delete_destination_' . $id);

        if ($id) V3DA_DB::delete($id);

        wp_safe_redirect(add_query_arg(['page' => 'voyasee-3d-atlas', 'v3da_notice' => 'deleted'], admin_url('admin.php')));
        exit;
    }
}
