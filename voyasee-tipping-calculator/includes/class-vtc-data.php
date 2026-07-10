<?php
/**
 * Dataset accessor. Loads the plugin-owned tipping + currency data once and
 * resolves each country against its culture-cluster defaults.
 */

defined('ABSPATH') || exit;

final class VTC_Data {
    private static ?array $data = null;
    private static ?array $currencies = null;
    private static ?array $currency_meta = null;

    private static function load(): void {
        if (null !== self::$data) {
            return;
        }
        self::$data = require VTC_DIR . 'includes/data/tipping-data.php';
        $cur = require VTC_DIR . 'includes/data/currency-data.php';
        self::$currencies = $cur['currencies'];
        self::$currency_meta = $cur['meta'];
    }

    public static function services(): array {
        self::load();
        return self::$data['services'];
    }

    public static function clusters(): array {
        self::load();
        return self::$data['clusters'];
    }

    public static function currency_meta(): array {
        self::load();
        return self::$currency_meta;
    }

    public static function currency(string $code): ?array {
        self::load();
        return self::$currencies[$code] ?? null;
    }

    public static function currencies(): array {
        self::load();
        return self::$currencies;
    }

    public static function raw_country(string $code): ?array {
        self::load();
        $code = strtoupper($code);
        return self::$data['countries'][$code] ?? null;
    }

    /**
     * A country fully resolved against its cluster: merged flags, per-service
     * bands (country overrides win), verdict, and a note (country note, else
     * the cluster's default note).
     */
    public static function resolve_country(string $code): ?array {
        self::load();
        $code = strtoupper($code);
        $row = self::$data['countries'][$code] ?? null;
        if (!$row) {
            return null;
        }
        $cluster = self::$data['clusters'][$row['cluster']];
        $flags = array_merge($cluster['flags'], $row['flags'] ?? []);
        $services = [];
        foreach ($cluster['services'] as $skey => $band) {
            $services[$skey] = ($row['overrides'][$skey] ?? $band);
        }
        return [
            'code'        => $code,
            'name'        => $row['name'],
            'region'      => $row['region'],
            'currency'    => $row['currency'],
            'cluster'     => $row['cluster'],
            'cluster_key' => $cluster['key'],
            'cluster_label' => $cluster['label'],
            'verdict'     => $cluster['verdict'],
            'confidence'  => $row['confidence'],
            'flags'       => $flags,
            'services'    => $services,
            'note'        => $row['note'] ?? $cluster['note'],
        ];
    }

    /**
     * Lightweight list for the country selector, sorted by name.
     * @return array<int,array{code:string,name:string,region:string,cluster_key:string}>
     */
    public static function country_list(): array {
        self::load();
        $out = [];
        foreach (self::$data['countries'] as $code => $row) {
            $cluster = self::$data['clusters'][$row['cluster']];
            $out[] = [
                'code'        => $code,
                'name'        => $row['name'],
                'region'      => $row['region'],
                'cluster_key' => $cluster['key'],
            ];
        }
        usort($out, static fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $out;
    }

    public static function all_codes(): array {
        self::load();
        return array_keys(self::$data['countries']);
    }

    /** URL slug for a country page, e.g. "united-states" -> tipping-in-united-states. */
    public static function country_slug(string $name): string {
        $s = strtolower($name);
        $s = str_replace(['&', "'"], ['and', ''], $s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string) $s, '-');
    }

    /**
     * Compact dataset for the front-end engine, emitted inline as window.VTC_DATA.
     * The JS mirrors resolve_country() (merging cluster defaults), so only the
     * raw countries + clusters + currencies are shipped, keeping the payload
     * small and the logic single-sourced.
     */
    public static function js_payload(): array {
        self::load();
        return [
            'services'   => self::$data['services'],
            'clusters'   => self::$data['clusters'],
            'countries'  => self::$data['countries'],
            'currencies' => self::$currencies,
            'currencyMeta' => self::$currency_meta,
        ];
    }

    /** Reverse lookup: slug -> country code. */
    public static function code_from_slug(string $slug): ?string {
        self::load();
        $slug = strtolower(trim($slug));
        foreach (self::$data['countries'] as $code => $row) {
            if (self::country_slug($row['name']) === $slug) {
                return $code;
            }
        }
        return null;
    }
}
