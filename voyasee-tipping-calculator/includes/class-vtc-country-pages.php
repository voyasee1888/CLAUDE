<?php
/**
 * Phase 2 + Phase 3 — programmatic pages.
 *
 *   /tipping-in-{country}/                     country guide (Phase 2)
 *   /tipping-in-{country}/{service}/           service-specific guide (Phase 3)
 *   /tipping-in-{region}/                      region hub (Phase 3)
 *   /tipping-guides/                           master index of all guides (Phase 3)
 *
 * Every page reuses the active theme's header/footer, renders the calculator
 * pre-set to the relevant country (and service), and adds real server-rendered
 * content so it ranks and works with JavaScript disabled. Service pages are
 * only served where that service is actually tipped (otherwise the country
 * page is the canonical target), which keeps the set free of thin pages.
 */

defined('ABSPATH') || exit;

final class VTC_Country_Pages {

    public static function init(): void {
        if (!VTC_Settings::country_pages_enabled()) {
            return;
        }
        add_action('init', [self::class, 'add_rewrite']);
        add_filter('query_vars', [self::class, 'query_vars']);
        add_action('template_redirect', [self::class, 'maybe_render']);
        add_filter('pre_get_document_title', [self::class, 'document_title']);
        add_action('init', [self::class, 'register_sitemap'], 20);
    }

    public static function add_rewrite(): void {
        // Order matters: most specific first (add_rewrite_rule prepends).
        add_rewrite_rule('^tipping-embed/?$', 'index.php?vtc_embed=1', 'top');
        add_rewrite_rule('^tipping-guides/?$', 'index.php?vtc_index=1', 'top');
        add_rewrite_rule('^tipping-in-([a-z0-9-]+)/?$', 'index.php?vtc_place=$matches[1]', 'top');
        add_rewrite_rule('^tipping-in-([a-z0-9-]+)/([a-z0-9-]+)/?$', 'index.php?vtc_place=$matches[1]&vtc_service=$matches[2]', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'vtc_place';
        $vars[] = 'vtc_service';
        $vars[] = 'vtc_index';
        $vars[] = 'vtc_embed';
        return $vars;
    }

    /** Resolve the current request into a structured target, or null. */
    public static function current(): ?array {
        if (get_query_var('vtc_index')) {
            return ['type' => 'index'];
        }
        $place = get_query_var('vtc_place');
        if (!$place) {
            return null;
        }
        $service_slug = get_query_var('vtc_service');
        $code = VTC_Data::code_from_slug((string) $place);
        if ($code) {
            if ($service_slug) {
                $service = VTC_Data::service_from_slug((string) $service_slug);
                if (!$service) {
                    return ['type' => '404'];
                }
                return ['type' => 'service', 'code' => $code, 'service' => $service];
            }
            return ['type' => 'country', 'code' => $code];
        }
        $region = VTC_Data::region_from_slug((string) $place);
        if ($region && !$service_slug) {
            return ['type' => 'region', 'region' => $region];
        }
        return ['type' => '404'];
    }

    /** Back-compat helper used by the schema class. */
    public static function current_code(): ?string {
        $t = self::current();
        return ($t && in_array($t['type'], ['country', 'service'], true)) ? $t['code'] : null;
    }

    public static function document_title($title) {
        $t = self::current();
        if (!$t) {
            return $title;
        }
        if ('index' === $t['type']) {
            return __('Tipping Guides for Every Country — Voyasee Tipping Calculator', 'voyasee-tipping-calculator');
        }
        if ('region' === $t['type']) {
            return sprintf(__('Tipping in %s — Country-by-Country Guide | Voyasee', 'voyasee-tipping-calculator'), $t['region']);
        }
        if ('service' === $t['type']) {
            $c = VTC_Data::resolve_country($t['code']);
            $phrase = VTC_Data::service_phrase($t['service']);
            return sprintf(__('How Much to Tip %s in %s | Voyasee', 'voyasee-tipping-calculator'), $phrase, $c['name']);
        }
        $c = VTC_Data::resolve_country($t['code']);
        return sprintf(__('How Much to Tip in %s — Tipping Calculator & Guide | Voyasee', 'voyasee-tipping-calculator'), $c['name']);
    }

    public static function maybe_render(): void {
        if (get_query_var('vtc_embed')) {
            self::render_embed();
            return;
        }
        $t = self::current();
        if (!$t) {
            return;
        }
        if ('404' === $t['type']) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        status_header(200);
        wp_enqueue_style('vtc-frontend');
        wp_enqueue_script('vtc-app');
        get_header();

        if ('index' === $t['type']) {
            include VTC_DIR . 'templates/index-page.php';
        } elseif ('region' === $t['type']) {
            $region = $t['region'];
            include VTC_DIR . 'templates/region-page.php';
        } else {
            $code = $t['code'];
            $service = $t['type'] === 'service' ? $t['service'] : null;
            $overview = VTC_Calculator::country_overview($code);
            include VTC_DIR . 'templates/country-page.php';
        }

        get_footer();
        exit;
    }

    /**
     * Standalone, theme-free document for embedding the calculator on other
     * sites via <iframe src="/tipping-embed/?country=XX">. Sends no
     * X-Frame-Options, so it can be framed cross-origin.
     */
    public static function render_embed(): void {
        $country = isset($_GET['country']) ? strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $_GET['country'])) : '';
        if ($country && !VTC_Data::raw_country($country)) {
            $country = '';
        }
        $html = do_shortcode('[voyasee_tipping_calculator' . ($country ? ' country="' . esc_attr($country) . '"' : '') . ']');
        $payload = wp_json_encode(VTC_Data::js_payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        $fonts = 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap';
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<meta name="robots" content="noindex,follow">';
        echo '<title>Voyasee Tipping Calculator</title>';
        echo '<base target="_top">';
        echo '<link rel="stylesheet" href="' . esc_url($fonts) . '">';
        echo '<link rel="stylesheet" href="' . esc_url(VTC_URL . 'assets/css/frontend.css?ver=' . VTC_VERSION) . '">';
        echo '<style>html,body{margin:0;padding:0;background:#0d1116;}</style>';
        echo '</head><body>';
        echo $html; // phpcs:ignore -- built from trusted shortcode output
        echo '<script>window.VTC_DATA=' . $payload . ';</script>';
        echo '<script src="' . esc_url(VTC_URL . 'assets/js/app.js?ver=' . VTC_VERSION) . '"></script>';
        echo '</body></html>';
        exit;
    }

    /* ---- WordPress core sitemap provider ---- */

    public static function register_sitemap(): void {
        if (!function_exists('wp_register_sitemap_provider')) {
            return;
        }
        require_once VTC_DIR . 'includes/class-vtc-sitemap.php';
        wp_register_sitemap_provider('tipping', new VTC_Sitemap());
    }

    public static function on_activate(): void {
        self::add_rewrite();
        if (class_exists('VTC_PWA')) {
            VTC_PWA::add_rewrite();
        }
        flush_rewrite_rules();
    }

    public static function on_deactivate(): void {
        flush_rewrite_rules();
    }
}
