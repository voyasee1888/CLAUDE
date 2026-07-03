<?php
defined('ABSPATH') || exit;

final class V3DA_DB {
    private const TABLE = 'v3da_destinations';
    private const DB_VERSION = '1.1';

    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public static function activate(): void {
        self::install();
        update_option('v3da_db_version', self::DB_VERSION, false);
        self::maybe_seed_defaults();
    }

    public static function maybe_upgrade(): void {
        if (get_option('v3da_db_version') !== self::DB_VERSION) {
            self::install();
            update_option('v3da_db_version', self::DB_VERSION, false);
        }
        // Runs on every plugins_loaded until the destinations table has ever
        // held a row, so sites that activated before this dataset existed
        // get it automatically on their next page load too -- not just on
        // a fresh install. The 'v3da_seeded' flag makes this a cheap option
        // read (no query against the destinations table) once it has run.
        self::maybe_seed_defaults();
        self::maybe_backfill_story_content();
        self::maybe_seed_new_defaults();
    }

    private static function maybe_seed_defaults(): void {
        if (get_option('v3da_seeded')) return;
        if (!empty(self::get_all())) {
            update_option('v3da_seeded', 1, false);
            return;
        }
        $defaults = require V3DA_DIR . 'includes/data/default-destinations.php';
        foreach ($defaults as $row) {
            $row['content_taxonomy'] = 'category';
            $row['content_term_slug'] = sanitize_title($row['name']);
            $row['status'] = 'active';
            self::insert($row);
        }
        update_option('v3da_seeded', 1, false);
    }

    /**
     * The default dataset grew from 117 to 167 destinations after initial
     * release. Sites that already seeded the first batch (v3da_seeded=1)
     * would otherwise never receive the additional ones, since
     * maybe_seed_defaults() only runs against a fully empty table. This
     * does a one-time pass adding any default destination whose slug isn't
     * already present -- it never touches or overwrites an existing row,
     * so hand-edited destinations are untouched either way.
     */
    private static function maybe_seed_new_defaults(): void {
        if (get_option('v3da_seeded_v2')) return;
        $defaults = require V3DA_DIR . 'includes/data/default-destinations.php';
        $existing_slugs = wp_list_pluck(self::get_all(), 'slug');
        foreach ($defaults as $row) {
            $slug = sanitize_title($row['name']);
            if (in_array($slug, $existing_slugs, true)) continue;
            $row['content_taxonomy'] = 'category';
            $row['content_term_slug'] = $slug;
            $row['status'] = 'active';
            self::insert($row);
        }
        update_option('v3da_seeded_v2', 1, false);
    }

    /**
     * signature_line / did_you_know were added after the initial dataset
     * shipped, so a site that already seeded destinations under an earlier
     * version has those two fields empty. This backfills them by matching
     * on slug, one time, without touching any field a site owner may have
     * since edited by hand (only fills in signature_line/did_you_know, and
     * only when they're still blank).
     */
    private static function maybe_backfill_story_content(): void {
        if (get_option('v3da_story_backfilled')) return;
        $defaults = require V3DA_DIR . 'includes/data/default-destinations.php';
        $bySlug = [];
        foreach ($defaults as $row) {
            $bySlug[sanitize_title($row['name'])] = $row;
        }
        foreach (self::get_all() as $existing) {
            $default = $bySlug[$existing['slug']] ?? null;
            if (!$default) continue;
            $update = [];
            if ('' === $existing['signature_line'] && !empty($default['signature_line'])) {
                $update['signature_line'] = $default['signature_line'];
            }
            if ('' === $existing['did_you_know'] && !empty($default['did_you_know'])) {
                $update['did_you_know'] = $default['did_you_know'];
            }
            if ($update) {
                global $wpdb;
                $wpdb->update(self::table(), $update, ['id' => $existing['id']]);
            }
        }
        update_option('v3da_story_backfilled', 1, false);
    }

    private static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(191) NOT NULL,
            name VARCHAR(191) NOT NULL,
            country VARCHAR(191) NOT NULL,
            country_code CHAR(2) NOT NULL DEFAULT '',
            region VARCHAR(64) NOT NULL DEFAULT '',
            lat DECIMAL(10,6) NOT NULL DEFAULT 0,
            lng DECIMAL(10,6) NOT NULL DEFAULT 0,
            content_taxonomy VARCHAR(32) NOT NULL DEFAULT 'category',
            content_term_slug VARCHAR(191) NOT NULL DEFAULT '',
            signature_line VARCHAR(200) NOT NULL DEFAULT '',
            did_you_know VARCHAR(400) NOT NULL DEFAULT '',
            hero_image_id BIGINT UNSIGNED NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'active',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY country_code (country_code),
            KEY region (region),
            KEY status (status)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    /**
     * @param array{status?:string,region?:string,country_code?:string,orderby?:string,order?:string} $args
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = self::table();
        $where = ['1=1'];
        $params = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_key($args['status']);
        }
        if (!empty($args['region'])) {
            $where[] = 'region = %s';
            $params[] = sanitize_text_field($args['region']);
        }
        if (!empty($args['country_code'])) {
            $where[] = 'country_code = %s';
            $params[] = strtoupper(sanitize_text_field($args['country_code']));
        }

        $orderby_allowed = ['name', 'country', 'region', 'sort_order', 'created_at'];
        $orderby = in_array($args['orderby'] ?? '', $orderby_allowed, true) ? $args['orderby'] : 'region';
        $order = strtoupper($args['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY {$orderby} {$order}, name ASC";
        if ($params) {
            $sql = $wpdb->prepare($sql, $params);
        }
        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public static function get(int $id): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', $id), ARRAY_A);
        return $row ?: null;
    }

    public static function get_by_slug(string $slug): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE slug = %s', sanitize_title($slug)), ARRAY_A);
        return $row ?: null;
    }

    /**
     * @return int|WP_Error New destination ID, or WP_Error on validation failure.
     */
    public static function insert(array $data) {
        global $wpdb;
        $clean = self::sanitize_fields($data);
        if (is_wp_error($clean)) return $clean;

        if ('' === $clean['slug']) {
            $clean['slug'] = sanitize_title($clean['name']);
        }
        if (self::get_by_slug($clean['slug'])) {
            return new WP_Error('v3da_duplicate_slug', __('A destination with this slug already exists.', 'voyasee-3d-atlas'));
        }

        $now = current_time('mysql');
        $clean['created_at'] = $now;
        $clean['updated_at'] = $now;

        $ok = $wpdb->insert(self::table(), $clean);
        if (false === $ok) {
            return new WP_Error('v3da_insert_failed', __('The destination could not be saved.', 'voyasee-3d-atlas'));
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * @return true|WP_Error
     */
    public static function update(int $id, array $data) {
        global $wpdb;
        if (!self::get($id)) {
            return new WP_Error('v3da_not_found', __('Destination not found.', 'voyasee-3d-atlas'));
        }
        $clean = self::sanitize_fields($data);
        if (is_wp_error($clean)) return $clean;

        if ('' === $clean['slug']) {
            $clean['slug'] = sanitize_title($clean['name']);
        }
        $existing = self::get_by_slug($clean['slug']);
        if ($existing && (int) $existing['id'] !== $id) {
            return new WP_Error('v3da_duplicate_slug', __('A destination with this slug already exists.', 'voyasee-3d-atlas'));
        }

        $clean['updated_at'] = current_time('mysql');
        $ok = $wpdb->update(self::table(), $clean, ['id' => $id]);
        if (false === $ok) {
            return new WP_Error('v3da_update_failed', __('The destination could not be updated.', 'voyasee-3d-atlas'));
        }
        return true;
    }

    public static function delete(int $id): bool {
        global $wpdb;
        return (bool) $wpdb->delete(self::table(), ['id' => $id], ['%d']);
    }

    /**
     * @return array|WP_Error
     */
    private static function sanitize_fields(array $data) {
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;
        if (null === $lat || $lat < -90 || $lat > 90) {
            return new WP_Error('v3da_invalid_lat', __('Latitude must be between -90 and 90.', 'voyasee-3d-atlas'));
        }
        if (null === $lng || $lng < -180 || $lng > 180) {
            return new WP_Error('v3da_invalid_lng', __('Longitude must be between -180 and 180.', 'voyasee-3d-atlas'));
        }

        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        if ('' === $name) {
            return new WP_Error('v3da_invalid_name', __('Destination name is required.', 'voyasee-3d-atlas'));
        }
        $country = sanitize_text_field((string) ($data['country'] ?? ''));
        if ('' === $country) {
            return new WP_Error('v3da_invalid_country', __('Country is required.', 'voyasee-3d-atlas'));
        }

        $country_code = strtoupper(sanitize_text_field((string) ($data['country_code'] ?? '')));
        if ('' !== $country_code && !preg_match('/^[A-Z]{2}$/', $country_code)) {
            return new WP_Error('v3da_invalid_country_code', __('Country code must be a 2-letter ISO code (e.g. JP, FR), or left blank.', 'voyasee-3d-atlas'));
        }

        $taxonomy = sanitize_key((string) ($data['content_taxonomy'] ?? 'category'));
        if (!in_array($taxonomy, ['category', 'post_tag'], true)) {
            $taxonomy = 'category';
        }

        $status = sanitize_key((string) ($data['status'] ?? 'active'));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $hero_image_id = isset($data['hero_image_id']) ? absint($data['hero_image_id']) : 0;

        return [
            'slug' => sanitize_title((string) ($data['slug'] ?? '')),
            'name' => $name,
            'country' => $country,
            'country_code' => $country_code,
            'region' => sanitize_text_field((string) ($data['region'] ?? '')),
            'lat' => round($lat, 6),
            'lng' => round($lng, 6),
            'content_taxonomy' => $taxonomy,
            'content_term_slug' => sanitize_title((string) ($data['content_term_slug'] ?? '')),
            'signature_line' => sanitize_text_field((string) ($data['signature_line'] ?? '')),
            'did_you_know' => sanitize_text_field((string) ($data['did_you_know'] ?? '')),
            'hero_image_id' => $hero_image_id > 0 ? $hero_image_id : null,
            'status' => $status,
            'sort_order' => absint($data['sort_order'] ?? 0),
        ];
    }
}
