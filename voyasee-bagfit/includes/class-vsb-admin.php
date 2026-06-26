<?php
defined('ABSPATH') || exit;

final class VSB_Admin {
    public static function init(): void {
        if (!is_admin()) return;
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_action('admin_post_vsb_save_settings', [self::class, 'save_settings']);
        add_action('admin_post_vsb_reseed', [self::class, 'reseed']);
        add_action('admin_post_vsb_export', [self::class, 'export']);
        add_action('admin_post_vsb_check_source', [self::class, 'check_source']);
        add_action('admin_post_vsb_save_airline', [self::class, 'save_airline']);
        add_action('admin_post_vsb_run_monitor_batch', [self::class, 'run_monitor_batch']);
    }

    public static function menu(): void {
        add_menu_page(__('Voyasee BagFit', 'voyasee-bagfit'), __('BagFit', 'voyasee-bagfit'), 'manage_options', 'voyasee-bagfit', [self::class, 'page'], 'dashicons-airplane', 58);
    }

    public static function assets(string $hook): void {
        if (false === strpos($hook, 'voyasee-bagfit')) return;
        wp_enqueue_style('vsb-admin', VSB_URL . 'assets/css/admin.css', [], VSB_VERSION);
    }

    private static function guard(): void {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Access denied.', 'voyasee-bagfit'));
    }

    public static function page(): void {
        self::guard();
        $tab = sanitize_key($_GET['tab'] ?? 'dashboard');
        echo '<div class="wrap vsb-admin"><header class="vsba-hero"><div><span>VOYASEE DATA CONTROL</span><h1>BagFit 7.0</h1><p>Manage airline rules, global airport search, source health, saved results, footer integrations and anonymous demand signals.</p></div><b>DATABASE FIRST</b></header>';
        echo '<nav class="nav-tab-wrapper">';
        foreach (['dashboard' => 'Data Health', 'airlines' => 'Airlines', 'sources' => 'Source Monitor', 'settings' => 'Settings'] as $key => $label) {
            echo '<a class="nav-tab ' . ($tab === $key ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=voyasee-bagfit&tab=' . $key)) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
        if ('settings' === $tab) self::settings();
        elseif ('airlines' === $tab) self::airlines();
        elseif ('sources' === $tab) self::sources();
        else self::dashboard();
        echo '</div>';
    }

    private static function dashboard(): void {
        global $wpdb;
        $health = VSB_DB::data_health();
        $changed = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . VSB_DB::source_table() . " WHERE status='changed_requires_review'");
        $saved = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . VSB_DB::saved_table());
        $airport_path = VSB_DIR . 'data/airports.json';
        $airport_data = is_readable($airport_path) ? json_decode((string) file_get_contents($airport_path), true) : [];
        $airport_count = count((array) ($airport_data['airports'] ?? []));
        $events = $wpdb->get_results('SELECT event_type,SUM(event_count) total FROM ' . VSB_DB::events_table() . ' WHERE event_date >= DATE_SUB(UTC_DATE(), INTERVAL 30 DAY) GROUP BY event_type ORDER BY total DESC', ARRAY_A);

        echo '<div class="vsba-stats">';
        self::stat('Published profiles', (int) ($health['total'] ?? 0), '250-profile airline directory');
        self::stat('Deeply reviewed', (int) ($health['deep_verified'] ?? 0), 'Official page reviewed rule sets');
        self::stat('Core source-linked', (int) ($health['source_linked'] ?? 0), 'Numerical starter rules with official links');
        self::stat('Directory only', (int) ($health['directory'] ?? 0), 'Official link, no confident numerical pass');
        self::stat('Global airports', $airport_count, 'Offline airport search records');
        self::stat('Review due', (int) ($health['review_due'] ?? 0), 'Admin review queue');
        self::stat('Source changes', $changed, 'Never auto-published');
        self::stat('Saved reports', $saved, 'Anonymous temporary links');
        echo '</div>';

        echo '<div class="vsba-grid"><section class="vsba-panel"><h2>Data publication model</h2><ol><li><strong>Deep verified:</strong> official page manually reviewed with structured size, weight and inclusion fields.</li><li><strong>Core source-linked:</strong> official source and starter numerical fields; public passes remain conditional until refreshed.</li><li><strong>Directory only:</strong> airline identity and official baggage page only; no numerical pass is generated.</li><li>Checked baggage uses ticket-entered values, not a universal 23 kg assumption.</li><li>No source monitor event can change a live rule automatically.</li></ol></section><section class="vsba-panel"><h2>Quality gaps</h2><ul><li>Missing personal-item dimensions: <strong>' . esc_html((string) ($health['missing_personal_dimensions'] ?? 0)) . '</strong></li><li>Missing cabin dimensions: <strong>' . esc_html((string) ($health['missing_cabin_dimensions'] ?? 0)) . '</strong></li><li>Missing official source: <strong>' . esc_html((string) ($health['missing_source'] ?? 0)) . '</strong></li><li>Ticket-specific checked profiles: <strong>' . esc_html((string) ($health['ticket_specific_checked'] ?? 0)) . '</strong></li></ul></section></div>';

        echo '<section class="vsba-panel"><h2>Anonymous use signals · last 30 days</h2><div class="vsba-stats compact">';
        if ($events) {
            foreach ($events as $event) self::stat(ucwords(str_replace('_', ' ', (string) $event['event_type'])), (int) $event['total'], 'Aggregated count only');
        } else {
            echo '<p>No usage signals have been recorded yet.</p>';
        }
        echo '</div></section>';

        echo '<div class="vsba-actions"><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">' . wp_nonce_field('vsb_reseed', '_wpnonce', true, false) . '<input type="hidden" name="action" value="vsb_reseed"><button class="button button-primary">Restore bundled 250-airline dataset</button></form><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=vsb_export'), 'vsb_export')) . '">Export airline JSON</a></div>';
    }

    private static function stat(string $label, int $value, string $note): void {
        echo '<article><span>' . esc_html($label) . '</span><strong>' . esc_html(number_format_i18n($value)) . '</strong><small>' . esc_html($note) . '</small></article>';
    }

    private static function settings(): void {
        $s = get_option('vsb_settings', []);
        if (!is_array($s)) $s = [];
        echo '<form class="vsba-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">' . wp_nonce_field('vsb_save_settings', '_wpnonce', true, false) . '<input type="hidden" name="action" value="vsb_save_settings">';
        echo '<section class="vsba-panel"><h2>Database and saved results</h2><div class="vsba-fields"><label><span>Saved-link expiry (days)</span><input type="number" min="1" max="90" name="share_expiry_days" value="' . esc_attr($s['share_expiry_days'] ?? 30) . '"></label><label class="wide"><span>Source monitor</span><label class="check"><input type="checkbox" name="source_monitor_enabled" value="1" ' . checked(!empty($s['source_monitor_enabled']), true, false) . '> Check a controlled batch of approved official URLs. All changes require manual review.</label></label><label><span>URLs per monitor batch</span><input type="number" min="1" max="5" name="source_monitor_batch" value="' . esc_attr($s['source_monitor_batch'] ?? 2) . '"></label></div></section>';
        echo '<section class="vsba-panel"><h2>Advertisement zones</h2><p>Ads remain outside the factual result and are excluded from the report.</p><div class="vsba-fields">';
        foreach (['ad_top_shortcode' => 'Top ad shortcode', 'ad_result_shortcode' => 'After-result ad shortcode', 'ad_footer_shortcode' => 'Footer ad shortcode'] as $key => $label) {
            echo '<label class="wide"><span>' . esc_html($label) . '</span><input name="' . esc_attr($key) . '" value="' . esc_attr($s[$key] ?? '') . '"></label>';
        }
        echo '</div></section>';

        $affiliates = [
            'affiliate_flights' => 'Aviasales', 'affiliate_kiwi' => 'Kiwi', 'affiliate_booking' => 'Booking.com EU', 'affiliate_booking_apac' => 'Booking.com APAC / Middle East',
            'affiliate_storage' => 'Radical Storage', 'affiliate_transfer' => 'Kiwitaxi', 'affiliate_insurance' => 'SafetyWing', 'affiliate_compensation' => 'Compensair',
            'affiliate_esim' => 'Yesim', 'affiliate_visa' => 'VisaHQ', 'affiliate_asia_transport' => '12Go Asia', 'affiliate_activities' => 'Klook',
            'affiliate_malaysia_airlines' => 'Malaysia Airlines', 'affiliate_luggage' => 'Luggage retailer',
        ];
        echo '<section class="vsba-panel"><h2>Approved affiliate URLs</h2><p>Empty fields stay hidden. Public links are marked sponsored and do not affect results.</p><div class="vsba-fields">';
        foreach ($affiliates as $key => $label) echo '<label><span>' . esc_html($label) . '</span><input type="url" name="' . esc_attr($key) . '" value="' . esc_attr($s[$key] ?? '') . '"></label>';
        echo '</div></section>';

        $tools = [
            'tool_packing' => 'Packing List', 'tool_medicine' => 'Medicine Checker', 'tool_transit' => 'Transit Checker', 'tool_budget' => 'Trip Budget',
            'tool_passport' => 'Travel Passport', 'tool_hub' => 'Smart Travel Hub', 'tool_month' => 'Travel Month Planner', 'tool_compare' => 'Destination Comparison',
            'tool_scam' => 'Travel Scam Shield', 'tool_map' => 'Interactive Travel Map', 'tool_quiz' => 'Destination Quiz', 'tool_printables' => 'Travel Printables',
            'tool_flights' => 'Book Flights page', 'tool_tours' => 'Book Tours page',
        ];
        echo '<section class="vsba-panel"><h2>Voyasee tool links</h2><div class="vsba-fields">';
        foreach ($tools as $key => $label) echo '<label><span>' . esc_html($label) . '</span><input type="url" name="' . esc_attr($key) . '" value="' . esc_attr($s[$key] ?? '') . '"></label>';
        echo '</div></section><p><button class="button button-primary button-hero">Save settings</button></p></form>';
    }

    private static function airlines(): void {
        global $wpdb;
        $edit = absint($_GET['edit'] ?? 0);
        if ($edit) {
            $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . VSB_DB::airlines_table() . ' WHERE id=%d', $edit), ARRAY_A);
            if (!$row) { echo '<p>Airline not found.</p>'; return; }
            $rule = json_decode((string) $row['rule_json'], true);
            echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=voyasee-bagfit&tab=airlines')) . '">← Back</a><form class="vsba-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">' . wp_nonce_field('vsb_save_airline', '_wpnonce', true, false) . '<input type="hidden" name="action" value="vsb_save_airline"><input type="hidden" name="id" value="' . esc_attr($edit) . '"><section class="vsba-panel"><h2>Edit ' . esc_html($row['name']) . '</h2><div class="vsba-fields"><label><span>Name</span><input name="name" value="' . esc_attr($row['name']) . '"></label><label><span>IATA</span><input name="iata" value="' . esc_attr($row['iata']) . '"></label><label><span>Country</span><input name="country" value="' . esc_attr($row['country']) . '"></label><label><span>Last verified</span><input type="date" name="last_verified" value="' . esc_attr($row['last_verified']) . '"></label><label class="wide"><span>Official source URL</span><input type="url" name="source_url" value="' . esc_attr($row['source_url']) . '"></label><label><span>Status</span><select name="status"><option value="published" ' . selected($row['status'], 'published', false) . '>Published</option><option value="draft" ' . selected($row['status'], 'draft', false) . '>Draft</option><option value="archived" ' . selected($row['status'], 'archived', false) . '>Archived</option></select></label><label><span>Verification state</span><select name="verification_status"><option value="reviewed_against_official_page" ' . selected($rule['data_quality']['verification_status'] ?? '', 'reviewed_against_official_page', false) . '>Deep verified</option><option value="source_linked_requires_manual_review" ' . selected($rule['data_quality']['verification_status'] ?? '', 'source_linked_requires_manual_review', false) . '>Core source-linked</option><option value="directory_only_official_site" ' . selected($rule['data_quality']['verification_status'] ?? '', 'directory_only_official_site', false) . '>Directory only</option></select></label><label class="wide"><span>Structured airline rule JSON</span><textarea name="rule_json" rows="28">' . esc_textarea(wp_json_encode($rule, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</textarea></label></div></section><button class="button button-primary button-hero">Save airline</button></form>';
            return;
        }
        $q = sanitize_text_field($_GET['s'] ?? '');
        $tier = sanitize_key($_GET['tier'] ?? '');
        $conditions = [];
        $params = [];
        if ($q !== '') { $conditions[] = '(name LIKE %s OR iata LIKE %s OR country LIKE %s)'; $like = '%' . $wpdb->esc_like($q) . '%'; array_push($params, $like, $like, $like); }
        if ($tier) { $conditions[] = 'rule_json LIKE %s'; $params[] = '%"coverage_tier":"' . $wpdb->esc_like($tier) . '"%'; }
        $sql = 'SELECT id,name,iata,country,last_verified,status,rule_json FROM ' . VSB_DB::airlines_table() . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY name ASC LIMIT 300';
        if ($params) $sql = $wpdb->prepare($sql, ...$params);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        echo '<form class="vsba-search"><input type="hidden" name="page" value="voyasee-bagfit"><input type="hidden" name="tab" value="airlines"><input type="search" name="s" value="' . esc_attr($q) . '" placeholder="Search airline, IATA or country"><select name="tier"><option value="">All tiers</option><option value="deep_verified" ' . selected($tier, 'deep_verified', false) . '>Deep verified</option><option value="core_source_linked" ' . selected($tier, 'core_source_linked', false) . '>Core source-linked</option><option value="directory" ' . selected($tier, 'directory', false) . '>Directory only</option></select><button class="button">Filter</button></form><table class="widefat striped"><thead><tr><th>Airline</th><th>Country</th><th>Coverage tier</th><th>Verification</th><th>Reviewed</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows ?: [] as $row) {
            $r = json_decode((string) $row['rule_json'], true);
            $v = $r['data_quality']['verification_status'] ?? 'unknown';
            $coverage = $r['coverage_tier'] ?? $r['data_quality']['coverage_tier'] ?? 'directory';
            echo '<tr><td><strong>' . esc_html($row['name']) . '</strong> <code>' . esc_html($row['iata']) . '</code></td><td>' . esc_html($row['country']) . '</td><td>' . esc_html(str_replace('_', ' ', $coverage)) . '</td><td>' . esc_html(str_replace('_', ' ', $v)) . '</td><td>' . esc_html($row['last_verified'] ?: '—') . '</td><td>' . esc_html($row['status']) . '</td><td><a href="' . esc_url(admin_url('admin.php?page=voyasee-bagfit&tab=airlines&edit=' . (int) $row['id'])) . '">Edit</a></td></tr>';
        }
        echo '</tbody></table>';
    }

    private static function sources(): void {
        global $wpdb;
        $updated = !empty($_GET['batch_done']) ? absint($_GET['batch_done']) : 0;
        if ($updated) echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Batch check completed: %d airline(s) checked.', 'voyasee-bagfit'), $updated) . '</p></div>';
        $rows = $wpdb->get_results('SELECT a.id,a.name,a.iata,a.source_url,a.last_verified,s.http_code,s.status,s.checked_at,s.changed_at FROM ' . VSB_DB::airlines_table() . ' a LEFT JOIN ' . VSB_DB::source_table() . ' s ON s.airline_id=a.id WHERE a.status="published" ORDER BY CASE WHEN s.status="changed_requires_review" THEN 0 WHEN s.checked_at IS NULL THEN 1 ELSE 2 END,a.name ASC LIMIT 300', ARRAY_A);
        echo '<div class="vsba-panel"><h2>Official-source availability monitor</h2><p>This monitor detects availability or page-content changes. A change can come from navigation, cookies or layout, so it never edits a baggage rule automatically.</p><div class="vsba-actions"><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">' . wp_nonce_field('vsb_run_monitor_batch', '_wpnonce', true, false) . '<input type="hidden" name="action" value="vsb_run_monitor_batch"><button class="button button-primary">Run batch check now</button></form></div></div><table class="widefat striped"><thead><tr><th>Airline</th><th>Official source</th><th>Rule reviewed</th><th>Monitor status</th><th>HTTP</th><th>Last checked</th><th></th></tr></thead><tbody>';
        foreach ($rows ?: [] as $r) {
            $url = esc_url($r['source_url']);
            echo '<tr><td><strong>' . esc_html($r['name']) . '</strong> <code>' . esc_html($r['iata']) . '</code></td><td>' . ($url ? '<a target="_blank" rel="noopener" href="' . $url . '">Open official page</a>' : '—') . '</td><td>' . esc_html($r['last_verified'] ?: '—') . '</td><td>' . esc_html(str_replace('_', ' ', $r['status'] ?: 'never checked')) . '</td><td>' . esc_html($r['http_code'] ?: '—') . '</td><td>' . esc_html($r['checked_at'] ?: '—') . '</td><td><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=vsb_check_source&id=' . (int) $r['id']), 'vsb_check_source_' . $r['id'])) . '">Check now</a></td></tr>';
        }
        echo '</tbody></table>';
    }

    public static function run_monitor_batch(): void {
        self::guard();
        check_admin_referer('vsb_run_monitor_batch');
        $s = get_option('vsb_settings', []);
        $batch = max(1, min(10, (int) ($s['source_monitor_batch'] ?? 2)));
        global $wpdb;
        $rows = $wpdb->get_results('SELECT a.id,a.source_url FROM ' . VSB_DB::airlines_table() . ' a LEFT JOIN ' . VSB_DB::source_table() . ' s ON s.airline_id=a.id WHERE a.status="published" AND a.source_url<>"" ORDER BY COALESCE(s.checked_at,"1970-01-01") ASC LIMIT ' . (int) $batch, ARRAY_A);
        $checked = 0;
        foreach ($rows ?: [] as $row) {
            VSB_Source_Monitor::check((int) $row['id'], (string) $row['source_url']);
            $checked++;
        }
        wp_safe_redirect(admin_url('admin.php?page=voyasee-bagfit&tab=sources&batch_done=' . $checked));
        exit;
    }

    public static function save_settings(): void {
        self::guard(); check_admin_referer('vsb_save_settings');
        $old = get_option('vsb_settings', []); if (!is_array($old)) $old = [];
        $new = $old;
        $new['share_expiry_days'] = max(1, min(90, absint($_POST['share_expiry_days'] ?? 30)));
        $new['source_monitor_enabled'] = !empty($_POST['source_monitor_enabled']) ? 1 : 0;
        $new['source_monitor_batch'] = max(1, min(5, absint($_POST['source_monitor_batch'] ?? 2)));
        foreach (['ad_top_shortcode', 'ad_result_shortcode', 'ad_footer_shortcode'] as $key) $new[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? ''));
        $urls = ['affiliate_booking','affiliate_booking_apac','affiliate_flights','affiliate_kiwi','affiliate_insurance','affiliate_storage','affiliate_transfer','affiliate_compensation','affiliate_esim','affiliate_visa','affiliate_asia_transport','affiliate_activities','affiliate_malaysia_airlines','affiliate_luggage','tool_packing','tool_medicine','tool_transit','tool_budget','tool_passport','tool_hub','tool_month','tool_compare','tool_scam','tool_map','tool_quiz','tool_printables','tool_flights','tool_tours'];
        foreach ($urls as $key) $new[$key] = esc_url_raw(wp_unslash($_POST[$key] ?? ''));
        update_option('vsb_settings', $new, false);
        wp_safe_redirect(admin_url('admin.php?page=voyasee-bagfit&tab=settings&updated=1')); exit;
    }

    public static function reseed(): void { self::guard(); check_admin_referer('vsb_reseed'); VSB_DB::seed_airlines(true); wp_safe_redirect(admin_url('admin.php?page=voyasee-bagfit&updated=1')); exit; }
    public static function export(): void { self::guard(); check_admin_referer('vsb_export'); nocache_headers(); header('Content-Type: application/json'); header('Content-Disposition: attachment; filename=voyasee-bagfit-airlines-' . gmdate('Y-m-d') . '.json'); echo wp_json_encode(['exported_at' => gmdate('c'), 'airlines' => VSB_DB::public_airlines()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }
    public static function check_source(): void { self::guard(); $id = absint($_GET['id'] ?? 0); check_admin_referer('vsb_check_source_' . $id); global $wpdb; $url = $wpdb->get_var($wpdb->prepare('SELECT source_url FROM ' . VSB_DB::airlines_table() . ' WHERE id=%d', $id)); if ($url) VSB_Source_Monitor::check($id, (string) $url); wp_safe_redirect(admin_url('admin.php?page=voyasee-bagfit&tab=sources')); exit; }

    public static function save_airline(): void {
        self::guard(); check_admin_referer('vsb_save_airline'); global $wpdb;
        $id = absint($_POST['id'] ?? 0);
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . VSB_DB::airlines_table() . ' WHERE id=%d', $id), ARRAY_A);
        if (!$row) wp_die('Airline not found');
        $json = json_decode(wp_unslash($_POST['rule_json'] ?? ''), true);
        if (!is_array($json)) wp_die('Invalid JSON');
        $json['name'] = sanitize_text_field(wp_unslash($_POST['name'] ?? $row['name']));
        $json['iata'] = strtoupper(sanitize_text_field(wp_unslash($_POST['iata'] ?? $row['iata'])));
        $json['country'] = sanitize_text_field(wp_unslash($_POST['country'] ?? $row['country']));
        $json['source_url'] = esc_url_raw(wp_unslash($_POST['source_url'] ?? $row['source_url']));
        $json['last_verified'] = sanitize_text_field(wp_unslash($_POST['last_verified'] ?? ''));
        $json['status'] = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived'], true) ? $_POST['status'] : 'draft';
        $allowed = ['reviewed_against_official_page', 'source_linked_requires_manual_review', 'directory_only_official_site'];
        $verification = in_array($_POST['verification_status'] ?? '', $allowed, true) ? $_POST['verification_status'] : 'source_linked_requires_manual_review';
        $json['data_quality']['verification_status'] = $verification;
        $json['data_quality']['reviewed_at'] = $json['last_verified'];
        $json['coverage_tier'] = match ($verification) {'reviewed_against_official_page' => 'deep_verified', 'source_linked_requires_manual_review' => 'core_source_linked', default => 'directory'};
        $json['data_quality']['coverage_tier'] = $json['coverage_tier'];
        $json['directory_only'] = 'directory_only_official_site' === $verification;
        $json['admin_customised'] = true;
        $wpdb->update(VSB_DB::airlines_table(), ['name' => $json['name'], 'iata' => $json['iata'], 'country' => $json['country'], 'source_url' => $json['source_url'], 'last_verified' => $json['last_verified'], 'status' => $json['status'], 'confidence' => 'reviewed_against_official_page' === $verification ? 'high' : ('directory_only_official_site' === $verification ? 'low' : 'medium'), 'rule_json' => wp_json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'updated_at' => current_time('mysql')], ['id' => $id]);
        wp_safe_redirect(admin_url('admin.php?page=voyasee-bagfit&tab=airlines&edit=' . $id . '&updated=1')); exit;
    }
}
