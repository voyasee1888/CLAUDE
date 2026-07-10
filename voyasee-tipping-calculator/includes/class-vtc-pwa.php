<?php
/**
 * Progressive Web App support — an installable, offline-capable tipping
 * calculator. The tool already computes fully offline (its data is embedded,
 * never fetched), so this adds:
 *   - a web app manifest at /voyasee-tipping.webmanifest (installable),
 *   - a root-scoped service worker at /voyasee-tipping-sw.js that caches the
 *     plugin's own assets and tipping pages for offline use — and passes every
 *     other request straight through, so it never interferes with the rest of
 *     the site,
 *   - the <link rel="manifest">, theme-color and apple-touch-icon head tags on
 *     the tool and guide pages.
 *
 * The service worker is registered from app.js using the URL in the config.
 */

defined('ABSPATH') || exit;

final class VTC_PWA {

    public static function init(): void {
        if (!VTC_Settings::pwa_enabled()) {
            return;
        }
        add_action('init', [self::class, 'add_rewrite']);
        add_filter('query_vars', [self::class, 'query_vars']);
        add_action('template_redirect', [self::class, 'maybe_serve'], 5);
        add_action('wp_head', [self::class, 'head_tags'], 3);
    }

    public static function add_rewrite(): void {
        add_rewrite_rule('^voyasee-tipping\.webmanifest$', 'index.php?vtc_manifest=1', 'top');
        add_rewrite_rule('^voyasee-tipping-sw\.js$', 'index.php?vtc_sw=1', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'vtc_manifest';
        $vars[] = 'vtc_sw';
        return $vars;
    }

    public static function sw_url(): string {
        return home_url('/voyasee-tipping-sw.js');
    }

    private static function should_show(): bool {
        if (class_exists('VTC_Country_Pages') && VTC_Country_Pages::current()) {
            return true;
        }
        if (is_singular()) {
            $post = get_post();
            return $post && has_shortcode((string) $post->post_content, 'voyasee_tipping_calculator');
        }
        return false;
    }

    public static function head_tags(): void {
        if (!self::should_show()) {
            return;
        }
        echo '<link rel="manifest" href="' . esc_url(home_url('/voyasee-tipping.webmanifest')) . '">' . "\n";
        echo '<meta name="theme-color" content="#0d1116">' . "\n";
        echo '<link rel="apple-touch-icon" href="' . esc_url(VTC_URL . 'assets/icons/icon-180.png') . '">' . "\n";
    }

    public static function maybe_serve(): void {
        if (get_query_var('vtc_manifest')) {
            self::serve_manifest();
        }
        if (get_query_var('vtc_sw')) {
            self::serve_sw();
        }
    }

    private static function serve_manifest(): void {
        nocache_headers();
        header('Content-Type: application/manifest+json; charset=utf-8');
        $manifest = [
            'name'             => 'Voyasee Tipping Calculator',
            'short_name'       => 'Tip Calc',
            'description'      => 'Culture-aware tipping for 200+ countries — works offline.',
            'start_url'        => home_url('/tipping-calculator/'),
            'scope'            => home_url('/'),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#0d1116',
            'theme_color'      => '#0d1116',
            'categories'       => ['travel', 'utilities', 'finance'],
            'icons'            => [
                ['src' => VTC_URL . 'assets/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => VTC_URL . 'assets/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ];
        echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function serve_sw(): void {
        nocache_headers();
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /');
        $ver = 'vtc-' . VTC_VERSION;
        $css = VTC_URL . 'assets/css/frontend.css?ver=' . VTC_VERSION;
        $js  = VTC_URL . 'assets/js/app.js?ver=' . VTC_VERSION;
        $plugin_path = wp_parse_url(VTC_URL, PHP_URL_PATH);
        ?>
/* Voyasee Tipping Calculator service worker — conservative, non-invasive. */
var VTC_CACHE = <?php echo wp_json_encode($ver); ?>;
var VTC_PRECACHE = [<?php echo wp_json_encode($css) . ',' . wp_json_encode($js); ?>];
var VTC_PLUGIN = <?php echo wp_json_encode($plugin_path); ?>;

self.addEventListener("install", function (e) {
  e.waitUntil(caches.open(VTC_CACHE).then(function (c) { return c.addAll(VTC_PRECACHE); }).then(function () { return self.skipWaiting(); }));
});
self.addEventListener("activate", function (e) {
  e.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.map(function (k) { if (k !== VTC_CACHE) return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});
self.addEventListener("fetch", function (e) {
  var req = e.request;
  if (req.method !== "GET") return;
  var url;
  try { url = new URL(req.url); } catch (err) { return; }
  if (url.origin !== self.location.origin) return;
  var p = url.pathname;
  if (p.indexOf("/wp-admin") === 0 || p.indexOf("/wp-json") === 0 || p.indexOf("/wp-login") === 0) return;
  // Our own static assets: cache-first.
  if (VTC_PLUGIN && p.indexOf(VTC_PLUGIN) === 0) {
    e.respondWith(caches.match(req).then(function (r) {
      return r || fetch(req).then(function (res) {
        var copy = res.clone(); caches.open(VTC_CACHE).then(function (c) { c.put(req, copy); }); return res;
      });
    }));
    return;
  }
  // Tipping pages (tool + guides): network-first, fall back to cache offline.
  if (req.mode === "navigate" && p.indexOf("/tipping-") !== -1) {
    e.respondWith(fetch(req).then(function (res) {
      var copy = res.clone(); caches.open(VTC_CACHE).then(function (c) { c.put(req, copy); }); return res;
    }).catch(function () { return caches.match(req); }));
    return;
  }
  // Everything else: pass through untouched.
});
        <?php
        exit;
    }
}
