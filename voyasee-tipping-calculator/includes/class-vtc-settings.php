<?php
/**
 * Settings accessor. Saved options (from the admin page) are merged over the
 * bundled defaults in data/tool-links.php plus a few tool-level preferences, so
 * the plugin works fully on a fresh install and an admin can override anything.
 */

defined('ABSPATH') || exit;

final class VTC_Settings {
    private const OPTION = 'vtc_settings';
    private static ?array $cache = null;

    public static function defaults(): array {
        $links = require VTC_DIR . 'includes/data/tool-links.php';
        return array_merge($links, [
            'default_country'       => 'US',
            'default_home_currency' => '',
            'country_pages_enabled' => '1',
        ]);
    }

    public static function all(): array {
        if (null !== self::$cache) {
            return self::$cache;
        }
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        // A key present in saved (even as an empty string) wins, so an admin can
        // deliberately blank a footer link.
        self::$cache = array_merge(self::defaults(), $saved);
        return self::$cache;
    }

    public static function get(string $key, string $fallback = ''): string {
        $all = self::all();
        return isset($all[$key]) ? (string) $all[$key] : $fallback;
    }

    public static function country_pages_enabled(): bool {
        return '1' === self::get('country_pages_enabled', '1');
    }

    public static function save(array $values): void {
        update_option(self::OPTION, $values);
        self::$cache = null;
    }

    public static function option_name(): string {
        return self::OPTION;
    }
}
