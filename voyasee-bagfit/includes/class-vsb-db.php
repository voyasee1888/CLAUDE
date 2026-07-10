<?php
defined('ABSPATH') || exit;

final class VSB_DB {
    public static function airlines_table(): string { global $wpdb; return $wpdb->prefix . 'vsb_airlines'; }
    public static function saved_table(): string { global $wpdb; return $wpdb->prefix . 'vsb_saved_results'; }
    public static function source_table(): string { global $wpdb; return $wpdb->prefix . 'vsb_source_checks'; }
    public static function events_table(): string { global $wpdb; return $wpdb->prefix . 'vsb_events'; }

    public static function activate(): void {
        self::create_tables();
        self::seed_airlines();
        self::defaults();
        VSB_Source_Monitor::schedule();
        flush_rewrite_rules(false);
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('vsb_source_monitor_tick');
        flush_rewrite_rules(false);
    }

    public static function maybe_upgrade(): void {
        $previous = (string) get_option('vsb_db_version', '');
        if ($previous === VSB_VERSION) return;
        self::create_tables();
        self::upgrade_bundled_profiles();
        self::defaults();
        VSB_Source_Monitor::schedule();
    }

    private static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $airlines = self::airlines_table();
        $saved = self::saved_table();
        $sources = self::source_table();
        $events = self::events_table();
        dbDelta("CREATE TABLE {$airlines} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            iata varchar(8) NOT NULL DEFAULT '',
            name varchar(190) NOT NULL,
            country varchar(100) NOT NULL DEFAULT '',
            source_url text NOT NULL,
            source_title varchar(255) NOT NULL DEFAULT '',
            last_verified date DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            confidence varchar(20) NOT NULL DEFAULT 'medium',
            rule_json longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY slug (slug), KEY iata (iata), KEY status (status), KEY name (name)
        ) {$charset};");
        dbDelta("CREATE TABLE {$saved} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token_hash char(64) NOT NULL,
            payload longtext NOT NULL,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY token_hash (token_hash), KEY expires_at (expires_at)
        ) {$charset};");
        dbDelta("CREATE TABLE {$sources} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            airline_id bigint(20) unsigned NOT NULL,
            source_url text NOT NULL,
            http_code smallint unsigned NOT NULL DEFAULT 0,
            content_hash char(64) NOT NULL DEFAULT '',
            previous_hash char(64) NOT NULL DEFAULT '',
            status varchar(30) NOT NULL DEFAULT 'never_checked',
            checked_at datetime DEFAULT NULL,
            changed_at datetime DEFAULT NULL,
            note text NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY airline_id (airline_id), KEY status (status)
        ) {$charset};");
        dbDelta("CREATE TABLE {$events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            airline_slug varchar(100) NOT NULL DEFAULT '',
            bag_type varchar(20) NOT NULL DEFAULT '',
            verdict_code varchar(40) NOT NULL DEFAULT '',
            event_date date NOT NULL,
            event_count int unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY (id), UNIQUE KEY aggregate (event_type,airline_slug,bag_type,verdict_code,event_date), KEY event_date (event_date)
        ) {$charset};");
        update_option('vsb_db_version', VSB_VERSION, false);
    }

    public static function defaults(): void {
        $defaults = [
            'share_expiry_days' => 30,
            'source_monitor_enabled' => 0,
            'source_monitor_batch' => 2,
            'ad_top_shortcode' => '',
            'ad_result_shortcode' => '',
            'ad_footer_shortcode' => '',
            'affiliate_booking' => 'https://www.dpbolvw.net/click-101719993-11891539',
            'affiliate_booking_apac' => 'https://www.kqzyfj.com/click-101719993-17289006',
            'affiliate_flights' => 'https://aviasales.tpm.li/jergleAu',
            'affiliate_kiwi' => 'https://kiwi.tpm.li/tDvkubl3',
            'affiliate_insurance' => 'https://safetywing.com/?referenceID=26504574&utm_source=26504574&utm_medium=Ambassador',
            'affiliate_storage' => 'https://radicalstorage.tpm.li/zuiSFLZl',
            'affiliate_transfer' => 'https://kiwitaxi.tpm.li/KWy4T7aR',
            'affiliate_compensation' => 'https://compensair.tpm.li/u34afsuE',
            'affiliate_esim' => 'https://yesim.tpm.li/gp5GJXhw',
            'affiliate_visa' => 'https://www.visahq.co.uk/?a_aid=vaff18435',
            'affiliate_asia_transport' => 'https://12go.asia/?z=15621940',
            'affiliate_activities' => 'https://klook.tpm.li/mCJbps88',
            'affiliate_malaysia_airlines' => 'https://www.kqzyfj.com/click-101719993-17158983',
            'affiliate_luggage' => '',
            'tool_packing' => 'https://voyasee.com/packing-list-generator/',
            'tool_medicine' => 'https://voyasee.com/medicine-restricted-item-checker/',
            'tool_transit' => 'https://voyasee.com/transit-visa-layover-risk-checker/',
            'tool_budget' => 'https://voyasee.com/trip-budget-calculator/',
            'tool_passport' => 'https://voyasee.com/trip-readiness-checklist/',
            'tool_hub' => 'https://voyasee.com/free-smart-travel-hub/',
            'tool_month' => 'https://voyasee.com/best-time-to-visit-travel-planner/',
            'tool_compare' => 'https://voyasee.com/travel-destination-comparison-tool/',
            'tool_scam' => 'https://voyasee.com/travel-scam-checker/',
            'tool_map' => 'https://voyasee.com/interactive-travel-map/',
            'tool_quiz' => 'https://voyasee.com/destination-quiz/',
            'tool_printables' => 'https://voyasee.com/travel-printables/',
            'tool_flights' => 'https://voyasee.com/book-cheap-flights/',
            'tool_tours' => 'https://voyasee.com/book-tours/',
            'data_version' => '2026.06.22-v7.0',
        ];
        $current = get_option('vsb_settings', []);
        if (!is_array($current)) $current = [];
        foreach (['ai_enabled','ai_ask_enabled','deepseek_endpoint','deepseek_model','deepseek_api_key','deepseek_thinking','deepseek_reasoning_effort','deepseek_max_tokens','ai_cache_hours'] as $legacy) unset($current[$legacy]);
        foreach ($defaults as $key => $value) {
            if ((str_starts_with($key, 'affiliate_') || str_starts_with($key, 'tool_')) && empty($current[$key])) {
                $current[$key] = $value;
            }
        }
        update_option('vsb_settings', array_merge($defaults, $current, ['data_version' => $defaults['data_version']]), false);
    }

    private static function bundled(): array {
        $path = VSB_DIR . 'data/airlines.json';
        if (!is_readable($path)) return [];
        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded['airlines'] ?? null) ? $decoded['airlines'] : [];
    }

    private static function upgrade_bundled_profiles(): void {
        global $wpdb;
        foreach (self::bundled() as $airline) {
            if (!is_array($airline) || empty($airline['slug'])) continue;
            $slug = sanitize_title((string) $airline['slug']);
            $existing = $wpdb->get_row($wpdb->prepare('SELECT id,rule_json FROM ' . self::airlines_table() . ' WHERE slug=%s', $slug), ARRAY_A);
            if (!$existing) {
                self::write_airline($airline, null);
                continue;
            }
            $old = json_decode((string) $existing['rule_json'], true);
            $custom = is_array($old) && !empty($old['admin_customised']);
            if (!$custom) self::write_airline($airline, (int) $existing['id']);
        }
    }

    public static function seed_airlines(bool $replace = false): int {
        global $wpdb;
        $count = 0;
        foreach (self::bundled() as $airline) {
            if (!is_array($airline) || empty($airline['slug']) || empty($airline['name'])) continue;
            $existing = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . self::airlines_table() . ' WHERE slug=%s', sanitize_title((string) $airline['slug'])));
            if ($existing && !$replace) continue;
            if (self::write_airline($airline, $existing ? (int) $existing : null)) $count++;
        }
        return $count;
    }

    private static function write_airline(array $airline, ?int $id): bool {
        global $wpdb;
        $now = current_time('mysql');
        $airline['schema_version'] = '7.0';
        $row = [
            'slug' => sanitize_title((string) ($airline['slug'] ?? '')),
            'iata' => strtoupper(sanitize_text_field((string) ($airline['iata'] ?? ''))),
            'name' => sanitize_text_field((string) ($airline['name'] ?? '')),
            'country' => sanitize_text_field((string) ($airline['country'] ?? '')),
            'source_url' => esc_url_raw((string) ($airline['source_url'] ?? '')),
            'source_title' => sanitize_text_field((string) ($airline['source_title'] ?? '')),
            'last_verified' => !empty($airline['last_verified']) ? sanitize_text_field((string) $airline['last_verified']) : null,
            'status' => in_array(($airline['status'] ?? 'draft'), ['published', 'draft', 'archived'], true) ? $airline['status'] : 'draft',
            'confidence' => in_array(($airline['confidence'] ?? 'medium'), ['high', 'medium', 'low'], true) ? $airline['confidence'] : 'medium',
            'rule_json' => wp_json_encode($airline, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => $now,
        ];
        if ($id) {
            $result = false !== $wpdb->update(self::airlines_table(), $row, ['id' => $id]);
        } else {
            $row['created_at'] = $now;
            $result = false !== $wpdb->insert(self::airlines_table(), $row);
        }
        wp_cache_delete('vsb_public_airlines', 'vsb');
        return $result;
    }

    public static function public_airlines(): array {
        $cache_key = 'vsb_public_airlines';
        $cached = wp_cache_get($cache_key, 'vsb');
        if (is_array($cached)) return $cached;
        global $wpdb;
        $rows = $wpdb->get_results('SELECT id,rule_json FROM ' . self::airlines_table() . " WHERE status='published' ORDER BY name ASC", ARRAY_A);
        $out = [];
        foreach ($rows ?: [] as $row) {
            $rule = json_decode((string) $row['rule_json'], true);
            if (is_array($rule)) {
                $rule['id'] = (int) $row['id'];
                $out[] = $rule;
            }
        }
        wp_cache_set($cache_key, $out, 'vsb', 300);
        return $out;
    }

    public static function airline_by_slug(string $slug): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT id,rule_json FROM ' . self::airlines_table() . " WHERE slug=%s AND status='published'", sanitize_title($slug)), ARRAY_A);
        if (!$row) return null;
        $rule = json_decode((string) $row['rule_json'], true);
        if (!is_array($rule)) return null;
        $rule['id'] = (int) $row['id'];
        return $rule;
    }

    public static function airport_index(): array {
        static $cache = null;
        if (is_array($cache)) return $cache;
        $path = VSB_DIR . 'data/airports.json';
        if (!is_readable($path)) return [];
        $decoded = json_decode((string) file_get_contents($path), true);
        $cache = is_array($decoded['airports'] ?? null) ? $decoded['airports'] : [];
        return $cache;
    }

    public static function airport_search(string $query = '', int $limit = 30): array {
        $lower = static fn(string $value): string => function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        $contains = static fn(string $haystack, string $needle): bool => function_exists('mb_strpos') ? false !== mb_strpos($haystack, $needle) : false !== strpos($haystack, $needle);
        $query = trim($lower($query));
        $limit = max(1, min(50, $limit));
        $matches = [];
        foreach (self::airport_index() as $airport) {
            $haystack = $lower(implode(' ', [(string) ($airport['iata'] ?? ''), (string) ($airport['icao'] ?? ''), (string) ($airport['name'] ?? ''), (string) ($airport['city'] ?? ''), (string) ($airport['country'] ?? ''), (string) ($airport['subdivision'] ?? '')]));
            if ('' !== $query && !$contains($haystack, $query)) continue;
            $score = !empty($airport['featured']) ? 100 : 0;
            if ($query) {
                if ($lower((string) ($airport['iata'] ?? '')) === $query) $score += 1000;
                elseif (str_starts_with($lower((string) ($airport['iata'] ?? '')), $query)) $score += 500;
                if (str_starts_with($lower((string) ($airport['city'] ?? '')), $query)) $score += 300;
                if (str_starts_with($lower((string) ($airport['name'] ?? '')), $query)) $score += 200;
            }
            $airport['_score'] = $score;
            $matches[] = $airport;
        }
        usort($matches, static fn(array $a, array $b): int => (($b['_score'] ?? 0) <=> ($a['_score'] ?? 0)) ?: strcmp((string) ($a['city'] ?? ''), (string) ($b['city'] ?? '')));
        $matches = array_slice($matches, 0, $limit);
        return array_map(static function (array $airport): array { unset($airport['_score']); return $airport; }, $matches);
    }

    public static function data_health(): array {
        $today = time();
        $health = ['total' => 0, 'deep_verified' => 0, 'source_linked' => 0, 'directory' => 0, 'review_due' => 0, 'missing_source' => 0, 'missing_personal_dimensions' => 0, 'missing_cabin_dimensions' => 0, 'ticket_specific_checked' => 0];
        foreach (self::public_airlines() as $airline) {
            $health['total']++;
            $tier = (string) ($airline['coverage_tier'] ?? 'directory');
            if ('deep_verified' === $tier) $health['deep_verified']++;
            elseif ('core_source_linked' === $tier) $health['source_linked']++;
            else $health['directory']++;
            if (empty($airline['source_url'])) $health['missing_source']++;
            $personal_opts = is_array($airline['allowance_options']['personal'] ?? null) ? $airline['allowance_options']['personal'] : [];
            $health['missing_personal_dimensions'] += empty(array_filter($personal_opts, fn($o) => !empty($o['dimensions_mm']))) ? 1 : 0;
            $cabin_opts = is_array($airline['allowance_options']['cabin'] ?? null) ? $airline['allowance_options']['cabin'] : [];
            $health['missing_cabin_dimensions'] += empty(array_filter($cabin_opts, fn($o) => !empty($o['dimensions_mm']))) ? 1 : 0;
            if ('ticket_specific' === ($airline['checked']['included'] ?? '')) $health['ticket_specific_checked']++;
            $date = (string) ($airline['last_verified'] ?? '');
            $days = max(30, (int) ($airline['data_quality']['recommended_review_days'] ?? 90));
            if (!$date || !strtotime($date) || ($today - strtotime($date . ' UTC')) > $days * DAY_IN_SECONDS) $health['review_due']++;
        }
        return $health;
    }

    public static function record_event(string $event_type, string $airline_slug = '', string $bag_type = '', string $verdict_code = ''): void {
        global $wpdb;
        $event_type = sanitize_key($event_type);
        if (!$event_type) return;
        $date = gmdate('Y-m-d');
        $table = self::events_table();
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (event_type,airline_slug,bag_type,verdict_code,event_date,event_count) VALUES (%s,%s,%s,%s,%s,1) ON DUPLICATE KEY UPDATE event_count=event_count+1",
            $event_type, sanitize_title($airline_slug), sanitize_key($bag_type), sanitize_key($verdict_code), $date
        ));
    }

    public static function cleanup_saved(): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . self::saved_table() . ' WHERE expires_at < %s', current_time('mysql', true)));
    }
}
