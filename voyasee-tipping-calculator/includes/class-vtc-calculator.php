<?php
/**
 * The tipping engine — the authoritative calculation. The front-end mirrors
 * this exact logic in JS for instant results; this PHP copy powers the REST
 * endpoint and the server-rendered per-country pages (so they show real,
 * correct numbers with no JavaScript). The two implementations must stay in
 * step, so the rounding helpers below are intentionally simple and identical.
 */

defined('ABSPATH') || exit;

final class VTC_Calculator {

    private const QUALITY_INDEX = ['poor' => 0, 'standard' => 1, 'great' => 2];

    /**
     * @param array $args {
     *   country (code), service (key), amount (float, local currency),
     *   quality ('poor'|'standard'|'great'), party (int>=1),
     *   units (int>=1, flat services only),
     *   round_up (bool), home_currency (code|''),
     * }
     * @return array|null  Full structured result, or null on unknown country/service.
     */
    public static function calculate(array $args): ?array {
        $country = VTC_Data::resolve_country((string) ($args['country'] ?? ''));
        if (!$country) {
            return null;
        }
        $services = VTC_Data::services();
        $skey = (string) ($args['service'] ?? 'restaurant');
        if (!isset($services[$skey], $country['services'][$skey])) {
            $skey = 'restaurant';
        }
        $service = $services[$skey];
        $band = $country['services'][$skey];

        $quality = (string) ($args['quality'] ?? 'standard');
        $qidx = self::QUALITY_INDEX[$quality] ?? 1;

        $amount = max(0.0, (float) ($args['amount'] ?? 0));
        $party  = max(1, (int) ($args['party'] ?? 1));
        $units  = max(1, (int) ($args['units'] ?? 1));
        $round_up = !empty($args['round_up']);

        $cur = VTC_Data::currency($country['currency']) ?? ['symbol' => $country['currency'], 'decimals' => 2, 'rate' => 1.0, 'name' => $country['currency']];
        $decimals = (int) $cur['decimals'];

        $flags = $country['flags'];
        $not_expected = !empty($flags['not_customary']) || !empty($flags['tipping_offensive']);

        $type = $service['type'];
        $tip = 0.0;
        $rate_pct = null;
        $per_unit = null;
        $band_amounts = ['low' => 0.0, 'standard' => 0.0, 'high' => 0.0];

        if ('percent' === $type) {
            $rate_pct = (float) $band[$qidx];
            $tip = self::round_money($amount * $rate_pct / 100, $decimals);
            $band_amounts = [
                'low'      => self::round_money($amount * $band[0] / 100, $decimals),
                'standard' => self::round_money($amount * $band[1] / 100, $decimals),
                'high'     => self::round_money($amount * $band[2] / 100, $decimals),
            ];
        } else {
            // Flat per-unit (housekeeping/night, porter/bag). Band anchors are
            // USD; convert to local and round to a clean local guideline.
            $rate = (float) $cur['rate'];
            $per_unit = self::nice_local($band[$qidx] * $rate, $decimals);
            $tip = self::round_money($per_unit * $units, $decimals);
            $band_amounts = [
                'low'      => self::nice_local($band[0] * $rate, $decimals) * $units,
                'standard' => self::nice_local($band[1] * $rate, $decimals) * $units,
                'high'     => self::nice_local($band[2] * $rate, $decimals) * $units,
            ];
        }

        if ($not_expected) {
            $tip = 0.0;
            $band_amounts = ['low' => 0.0, 'standard' => 0.0, 'high' => 0.0];
        }

        // Optional: round the grand total up to a clean note (percent services
        // on a real bill only — pointless where there is no bill to round).
        if ($round_up && 'percent' === $type && !$not_expected && $amount > 0) {
            $new_total = self::round_up_total($amount + $tip, $decimals);
            $tip = self::round_money($new_total - $amount, $decimals);
            if ($tip < 0) {
                $tip = 0.0;
            }
        }

        $total = ('percent' === $type) ? self::round_money($amount + $tip, $decimals) : $tip; // flat services have no "bill"
        $per_person_tip = self::round_money($tip / $party, $decimals);
        $per_person_total = self::round_money($total / $party, $decimals);

        $messages = self::messages($country, $type, $not_expected);

        $home = self::home_conversion($args['home_currency'] ?? '', $country['currency'], $tip, $total, $type);

        return [
            'country' => [
                'code'        => $country['code'],
                'name'        => $country['name'],
                'region'      => $country['region'],
                'cluster_key' => $country['cluster_key'],
                'cluster_label' => $country['cluster_label'],
                'verdict'     => $country['verdict'],
                'confidence'  => $country['confidence'],
                'note'        => $country['note'],
                'flags'       => $flags,
            ],
            'service' => ['key' => $skey, 'label' => $service['label'], 'type' => $type, 'unit' => $service['unit'] ?? null],
            'currency' => ['code' => $country['currency'], 'symbol' => $cur['symbol'], 'decimals' => $decimals],
            'input'   => ['amount' => $amount, 'quality' => $quality, 'party' => $party, 'units' => $units, 'round_up' => $round_up],
            'result'  => [
                'tip'              => $tip,
                'total'            => $total,
                'per_person_tip'   => $per_person_tip,
                'per_person_total' => $per_person_total,
                'rate_pct'         => $rate_pct,
                'per_unit'         => $per_unit,
                'band'             => $band_amounts,
                'not_expected'     => $not_expected,
            ],
            'home'     => $home,
            'messages' => $messages,
        ];
    }

    private static function messages(array $country, string $type, bool $not_expected): array {
        $flags = $country['flags'];
        $out = [];
        if ($not_expected) {
            $out[] = ['type' => 'verdict', 'text' => $country['note']];
            return $out;
        }
        if (!empty($flags['service_charge_common']) && 'percent' === $type) {
            $out[] = ['type' => 'warning', 'text' => 'A service charge is often already added to the bill here. Check first so you do not tip twice.'];
        }
        if (!empty($flags['cash_preferred'])) {
            $out[] = ['type' => 'info', 'text' => 'Cash is preferred for tips here — it is more likely to reach the staff directly.'];
        }
        if (!empty($flags['tip_pretax_common']) && 'percent' === $type) {
            $out[] = ['type' => 'info', 'text' => 'Locals usually tip on the pre-tax amount. If your entered total includes sales tax, the tip can be a little lower.'];
        }
        if ('medium' === $country['confidence']) {
            $out[] = ['type' => 'guidance', 'text' => 'This is general regional guidance for ' . $country['name'] . ' — customs can vary locally, so treat it as a helpful starting point.'];
        }
        return $out;
    }

    private static function home_conversion(string $home_code, string $local_code, float $tip, float $total, string $type): ?array {
        $home_code = strtoupper(trim($home_code));
        if ('' === $home_code || $home_code === $local_code) {
            return null;
        }
        $home = VTC_Data::currency($home_code);
        $local = VTC_Data::currency($local_code);
        if (!$home || !$local || (float) $local['rate'] <= 0) {
            return null;
        }
        $conv = static function (float $v) use ($home, $local): float {
            $usd = $v / (float) $local['rate'];
            return self::round_money($usd * (float) $home['rate'], (int) $home['decimals']);
        };
        return [
            'code'     => $home_code,
            'symbol'   => $home['symbol'],
            'decimals' => (int) $home['decimals'],
            'tip'      => $conv($tip),
            'total'    => ('percent' === $type) ? $conv($total) : $conv($tip),
            'approx'   => true,
        ];
    }

    /**
     * A server-rendered "at a glance" summary of the standard tip for every
     * service in a country. Real dataset values only — used for the no-JS
     * fallback and the per-country SEO pages. Returns null on unknown country.
     */
    public static function country_overview(string $code): ?array {
        $country = VTC_Data::resolve_country($code);
        if (!$country) {
            return null;
        }
        $services = VTC_Data::services();
        $cur = VTC_Data::currency($country['currency']) ?? ['symbol' => $country['currency'], 'decimals' => 2, 'rate' => 1.0];
        $rows = [];
        foreach ($services as $skey => $meta) {
            $band = $country['services'][$skey];
            $rows[] = [
                'key'     => $skey,
                'label'   => $meta['label'],
                'icon'    => $meta['icon'] ?? '',
                'type'    => $meta['type'],
                'unit'    => $meta['unit'] ?? null,
                'display' => self::band_display($band, $meta, $cur),
            ];
        }
        return [
            'country'  => $country,
            'currency' => ['code' => $country['currency'], 'symbol' => $cur['symbol'], 'decimals' => (int) $cur['decimals']],
            'rows'     => $rows,
        ];
    }

    /** Human range string for a service band (e.g. "15-20%" or "~THB 35 per bag"). */
    private static function band_display(array $band, array $meta, array $cur): string {
        [$low, $std, $high] = [$band[0], $band[1], $band[2]];
        if ('percent' === $meta['type']) {
            if (0 == $std && 0 == $high) {
                return 'Not expected';
            }
            if ($low == $high) {
                return $high . '%';
            }
            if (0 == $low) {
                return 'Round up to ~' . $high . '%';
            }
            return $low . '-' . $high . '%';
        }
        // flat
        if (0 == $std) {
            return 'Not expected';
        }
        $rate = (float) ($cur['rate'] ?? 1.0);
        $decimals = (int) ($cur['decimals'] ?? 2);
        $amt = self::nice_local($std * $rate, $decimals);
        $amt_str = self::format_amount($amt, $decimals);
        $unit = $meta['unit'] ?? '';
        return '~' . $cur['symbol'] . $amt_str . ($unit ? ' per ' . $unit : '');
    }

    public static function format_amount(float $v, int $decimals): string {
        return number_format($v, $decimals, '.', ',');
    }

    /* ---- rounding helpers (mirrored exactly in app.js) ---- */

    private static function round_money(float $v, int $decimals): float {
        $f = 10 ** $decimals;
        return round($v * $f) / $f;
    }

    /**
     * Convert a raw local amount into a clean, human tip guideline. Steps grow
     * with magnitude so we never suggest an odd figure like "37.4 baht".
     */
    private static function nice_local(float $v, int $decimals): float {
        if ($v <= 0) {
            return 0.0;
        }
        if ($v < 1)      { return $decimals > 0 ? self::round_money($v, min($decimals, 1)) : 1.0; }
        if ($v < 10)     { return round($v); }
        if ($v < 100)    { return round($v / 5) * 5; }
        if ($v < 1000)   { return round($v / 10) * 10; }
        if ($v < 10000)  { return round($v / 50) * 50; }
        return round($v / 100) * 100;
    }

    /** Round a grand total UP to the next clean note. */
    private static function round_up_total(float $v, int $decimals): float {
        if ($v <= 0) {
            return 0.0;
        }
        $step = 1.0;
        if ($v >= 100000) { $step = 1000; }
        elseif ($v >= 10000) { $step = 100; }
        elseif ($v >= 1000)  { $step = 50; }
        elseif ($v >= 100)   { $step = 10; }
        elseif ($v >= 20)    { $step = 5; }
        elseif ($v >= 5)     { $step = 1; }
        else { $step = ($decimals > 0) ? 0.5 : 1; }
        return ceil($v / $step) * $step;
    }
}
