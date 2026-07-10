<?php
/**
 * Phase 2 — programmatic per-country pages at /tipping-in-{country}/.
 *
 * Each page reuses the active theme's header/footer, renders the calculator
 * pre-set to that country, and adds real server-rendered "how much to tip in
 * {country}" content so it ranks and works with JavaScript disabled. These
 * capture the large "how much to tip in X" long-tail around the main tool.
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
    }

    public static function add_rewrite(): void {
        add_rewrite_rule('^tipping-in-([a-z0-9-]+)/?$', 'index.php?vtc_country=$matches[1]', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'vtc_country';
        return $vars;
    }

    public static function current_code(): ?string {
        $slug = get_query_var('vtc_country');
        if (!$slug) {
            return null;
        }
        return VTC_Data::code_from_slug((string) $slug);
    }

    public static function document_title($title) {
        $code = self::current_code();
        if (!$code) {
            return $title;
        }
        $c = VTC_Data::resolve_country($code);
        return sprintf(
            /* translators: %s: country name */
            __('How Much to Tip in %s — Tipping Calculator & Guide | Voyasee', 'voyasee-tipping-calculator'),
            $c['name']
        );
    }

    public static function maybe_render(): void {
        $slug = get_query_var('vtc_country');
        if (!$slug) {
            return;
        }
        $code = VTC_Data::code_from_slug((string) $slug);
        if (!$code) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        status_header(200);
        // Enqueue before the theme prints wp_head so the stylesheet lands in
        // the <head> rather than being deferred to the footer.
        wp_enqueue_style('vtc-frontend');
        wp_enqueue_script('vtc-app');
        get_header();
        $overview = VTC_Calculator::country_overview($code);
        include VTC_DIR . 'templates/country-page.php';
        get_footer();
        exit;
    }

    public static function on_activate(): void {
        self::add_rewrite();
        flush_rewrite_rules();
    }

    public static function on_deactivate(): void {
        flush_rewrite_rules();
    }
}
