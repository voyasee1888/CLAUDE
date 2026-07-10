<?php
defined('ABSPATH') || exit;

final class VSB_Report {
    public static function init(): void {
        add_action('admin_post_vsb_report', [self::class, 'render']);
        add_action('admin_post_nopriv_vsb_report', [self::class, 'render']);
    }

    public static function render(): void {
        global $wpdb;
        nocache_headers();
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        if (!preg_match('/^[A-Za-z0-9_-]{30,100}$/', $token)) {
            wp_die(esc_html__('Invalid report link.', 'voyasee-bagfit'), esc_html__('BagFit report', 'voyasee-bagfit'), ['response' => 400]);
        }
        $row = $wpdb->get_row($wpdb->prepare('SELECT payload,expires_at FROM ' . VSB_DB::saved_table() . ' WHERE token_hash=%s', hash('sha256', $token)), ARRAY_A);
        if (!$row || strtotime($row['expires_at'] . ' UTC') < time()) {
            wp_die(esc_html__('This report is unavailable or has expired.', 'voyasee-bagfit'), esc_html__('BagFit report', 'voyasee-bagfit'), ['response' => 404]);
        }
        $result = json_decode((string) $row['payload'], true);
        if (!is_array($result)) {
            wp_die(esc_html__('This report could not be read.', 'voyasee-bagfit'), esc_html__('BagFit report', 'voyasee-bagfit'), ['response' => 500]);
        }
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        echo self::document($result); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private static function document(array $result): string {
        $verdict = self::verdict((string) ($result['overall_verdict'] ?? 'CHECK_REQUIRED'));
        $bag_results = is_array($result['bag_results'] ?? null) ? $result['bag_results'] : [];
        if (!$bag_results && !empty($result['bag'])) {
            $bag_results = [['bag' => $result['bag'], 'legs' => $result['legs'] ?? [], 'overall_verdict' => $result['overall_verdict'] ?? 'CHECK_REQUIRED']];
        }
        $bags_html = '';
        foreach ($bag_results as $item) {
            $bag = is_array($item['bag'] ?? null) ? $item['bag'] : [];
            $dims = array_map('intval', (array) ($bag['dimensions_mm'] ?? []));
            $dim_text = count($dims) === 3 ? implode(' × ', array_map(static fn($v) => self::num($v / 10), $dims)) . ' cm' : '—';
            $weight = isset($bag['weight_g']) ? self::num(((int) $bag['weight_g']) / 1000) . ' kg' : '—';
            $bag_verdict = self::verdict((string) ($item['overall_verdict'] ?? 'CHECK_REQUIRED'));
            $next = sanitize_text_field((string) (($item['recommendations'][0] ?? '') ?: __('Confirm the official airline policy before departure.', 'voyasee-bagfit')));
            $bags_html .= '<article class="bag"><header><span>' . esc_html(strtoupper((string) ($bag['type'] ?? 'bag'))) . '</span><b>' . esc_html((string) ($bag['name'] ?? __('Bag', 'voyasee-bagfit'))) . '</b><em class="' . esc_attr($bag_verdict['tone']) . '">' . esc_html($bag_verdict['label']) . '</em></header><div class="baggrid"><div><small>' . esc_html__('Dimensions', 'voyasee-bagfit') . '</small><strong>' . esc_html($dim_text) . '</strong></div><div><small>' . esc_html__('Weight', 'voyasee-bagfit') . '</small><strong>' . esc_html($weight) . '</strong></div><div><small>' . esc_html__('Strictest flight', 'voyasee-bagfit') . '</small><strong>' . esc_html((string) ($item['strictest_airline'] ?? '—')) . '</strong></div><div><small>' . esc_html__('Next action', 'voyasee-bagfit') . '</small><strong>' . esc_html($next) . '</strong></div></div></article>';
        }

        $matrix_html = '';
        foreach ((array) ($result['flight_matrix'] ?? []) as $row) {
            $bag_cells = '';
            foreach ((array) ($row['bags'] ?? []) as $bag) {
                $status = self::code((string) ($bag['verdict_code'] ?? 'CHECK_REQUIRED'));
                $bag_cells .= '<div class="matrix-bag"><b>' . esc_html((string) ($bag['bag_name'] ?? 'Bag')) . '</b><span class="' . esc_attr($status['tone']) . '">' . esc_html($status['label']) . '</span><small>' . esc_html__('Size', 'voyasee-bagfit') . ': ' . esc_html(self::simple_status((string) ($bag['size_status'] ?? 'unknown'))) . ' · ' . esc_html__('Weight', 'voyasee-bagfit') . ': ' . esc_html(self::simple_status((string) ($bag['weight_status'] ?? 'unknown'))) . ' · ' . esc_html__('Booking', 'voyasee-bagfit') . ': ' . esc_html(self::booking((string) ($bag['booking_status'] ?? 'confirm'))) . '</small></div>';
            }
            $source = esc_url((string) ($row['source_url'] ?? ''));
            $matrix_html .= '<article class="flight"><header><div><span>' . esc_html(sprintf(__('Flight %d', 'voyasee-bagfit'), (int) ($row['leg_number'] ?? 0))) . '</span><b>' . esc_html((string) ($row['airline'] ?? '')) . '</b><small>' . esc_html((string) ($row['route'] ?? '')) . '</small></div>' . ($source ? '<a href="' . $source . '">' . esc_html__('Official source', 'voyasee-bagfit') . ' ↗</a>' : '') . '</header><div class="matrix">' . $bag_cells . '</div></article>';
        }

        $recommendations = '';
        foreach (array_slice(array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($result['recommendations'] ?? []))))), 0, 6) as $index => $recommendation) {
            $recommendations .= '<li><i>' . ($index + 1) . '</i><span>' . esc_html($recommendation) . '</span></li>';
        }
        if (!$recommendations) $recommendations = '<li><i>1</i><span>' . esc_html__('Confirm the issued ticket and official airline policy before departure.', 'voyasee-bagfit') . '</span></li>';

        $notices = '';
        foreach (array_slice((array) ($result['public_notices'] ?? []), 0, 7) as $notice) {
            $notices .= '<li>' . esc_html((string) $notice) . '</li>';
        }

        $plan = is_array($result['smart_plan'] ?? null) ? $result['smart_plan'] : [];
        $what_if_html = '';
        foreach (array_slice((array) ($plan['what_if'] ?? []), 0, 4) as $item) {
            $what_if_html .= '<div class="whatif-item"><b>' . esc_html((string) ($item['label'] ?? '')) . '</b><span>' . esc_html((string) ($item['outcome'] ?? '')) . '</span></div>';
        }
        $airport_script = sanitize_text_field((string) ($plan['airport_script'] ?? ''));
        $smart_plan_html = '';
        if ($what_if_html || $airport_script) {
            $smart_plan_html = '<section class="notes"><h3>' . esc_html__('Smart fix plan', 'voyasee-bagfit') . '</h3>'
                . ($what_if_html ? '<div class="whatif">' . $what_if_html . '</div>' : '')
                . ($airport_script ? '<div class="script"><small>' . esc_html__('Show this to airport or check-in staff', 'voyasee-bagfit') . '</small><p>&ldquo;' . esc_html($airport_script) . '&rdquo;</p></div>' : '')
                . '</section>';
        }

        $css = <<<'CSS'
@page{size:A4;margin:12mm}*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;color:#10213d;background:#edf5fc}.page{width:100%;min-height:273mm;background:#fff;padding:12mm;page-break-after:always}.page:last-child{page-break-after:auto}.brand{display:flex;align-items:center;justify-content:space-between;border-bottom:2px solid #13cbd0;padding-bottom:10px}.brand b{letter-spacing:.15em;color:#092d65}.brand span{font-size:10px;color:#63758e}.verdict{margin:18px 0;border-radius:18px;padding:20px;color:#fff;background:linear-gradient(120deg,#09275b,#087d8b)}.verdict.fail{background:linear-gradient(120deg,#641037,#c62f65)}.verdict.warn{background:linear-gradient(120deg,#644007,#bd7417)}.verdict.check{background:linear-gradient(120deg,#1b315e,#1772a6)}.verdict small{font-size:9px;letter-spacing:.12em}.verdict h1{font-size:25px;margin:5px 0 7px}.verdict p{font-size:11px;margin:0;color:#edf8ff}.overview{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px}.overview div{border:1px solid #dbe8f4;border-radius:12px;padding:10px;background:#f7fbff}.overview small,.bag small{display:block;font-size:8px;color:#6a7d95;text-transform:uppercase}.overview strong{display:block;font-size:11px;margin-top:4px}.bag{border:1px solid #dbe8f4;border-radius:14px;margin-bottom:10px;overflow:hidden;page-break-inside:avoid}.bag header{display:flex;align-items:center;gap:8px;background:#eef6fd;padding:10px}.bag header span{font-size:8px;color:#157e9f;font-weight:bold}.bag header b{flex:1;font-size:12px}.bag header em,.matrix-bag>span{font-style:normal;border-radius:999px;padding:4px 7px;font-size:8px;font-weight:bold}.pass{background:#dff7ed;color:#087153}.warn{background:#fff0d3;color:#955900}.fail{background:#ffe1ea;color:#a9254c}.check{background:#e2effc;color:#185e9d}.baggrid{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:#dbe8f4}.baggrid div{background:#fff;padding:10px}.baggrid strong{font-size:9px;line-height:1.45}.actions{border-radius:15px;background:#092b63;color:#fff;padding:15px;margin-top:15px}.actions h2{font-size:15px;margin:0 0 10px}.actions ol{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:8px}.actions li{display:flex;gap:8px;background:#ffffff12;border-radius:9px;padding:8px;font-size:9px;line-height:1.4}.actions i{width:20px;height:20px;border-radius:6px;background:#32ddd4;color:#08325a;display:grid;place-items:center;font-style:normal;font-weight:bold;flex:0 0 auto}.page-title{margin:0 0 14px}.page-title span{font-size:8px;letter-spacing:.13em;color:#1284a5;font-weight:bold}.page-title h2{font-size:21px;margin:4px 0}.flight{border:1px solid #dbe7f3;border-radius:14px;margin-bottom:10px;page-break-inside:avoid}.flight>header{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:#eef6fd}.flight header span{display:block;font-size:8px;color:#1580a3}.flight header b{font-size:11px}.flight header small{display:block;font-size:8px;color:#6f8196}.flight header a{font-size:8px;color:#0c65ae}.matrix{padding:8px;display:grid;gap:6px}.matrix-bag{display:grid;grid-template-columns:1fr auto;gap:4px;border-radius:9px;background:#f7fafc;padding:8px}.matrix-bag b{font-size:9px}.matrix-bag small{grid-column:1/-1;font-size:8px;color:#617188}.notes{border:1px solid #dbe7f3;border-radius:13px;padding:12px;margin-top:14px}.notes h3{font-size:12px;margin:0 0 8px}.notes ul{margin:0;padding-left:18px;font-size:9px;line-height:1.55;color:#566881}.whatif{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}.whatif-item{background:#f7fafc;border-radius:10px;padding:9px}.whatif-item b{display:block;font-size:9px;color:#10213d}.whatif-item span{display:block;font-size:8px;color:#5a6c84;margin-top:3px;line-height:1.4}.script{background:#092b63;color:#fff;border-radius:12px;padding:12px}.script small{font-size:8px;letter-spacing:.1em;color:#5be3da;text-transform:uppercase}.script p{margin:6px 0 0;font-size:10px;line-height:1.5;font-style:italic}.footer{margin-top:14px;border-top:1px solid #dbe7f3;padding-top:9px;font-size:8px;color:#687a90;display:flex;justify-content:space-between}.no-print{position:fixed;right:16px;top:16px;background:#13cbd0;color:#06254d;border:0;border-radius:10px;padding:10px 14px;font-weight:bold;cursor:pointer}@media print{body{background:#fff}.no-print{display:none}.page{padding:0;min-height:auto}}
CSS;

        $overall = sprintf(__('Strictest bag: %1$s · Strictest airline: %2$s', 'voyasee-bagfit'), (string) ($result['strictest_bag'] ?? '—'), (string) ($result['strictest_airline'] ?? '—'));
        $html = '<!doctype html><html><head><meta charset="' . esc_attr(get_bloginfo('charset')) . '"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html__('Voyasee BagFit Report', 'voyasee-bagfit') . '</title><style>' . $css . '</style></head><body><button class="no-print" onclick="window.print()">' . esc_html__('Print / Save PDF', 'voyasee-bagfit') . '</button>';
        $html .= '<section class="page"><div class="brand"><b>VOYASEE BAGFIT</b><span>' . esc_html(gmdate('d M Y')) . '</span></div><div class="verdict ' . esc_attr($verdict['tone']) . '"><small>' . esc_html__('AIRLINE BAGGAGE DECISION', 'voyasee-bagfit') . '</small><h1>' . esc_html($verdict['label']) . '</h1><p>' . esc_html($verdict['detail']) . '</p></div><div class="overview"><div><small>' . esc_html__('Strictest bag', 'voyasee-bagfit') . '</small><strong>' . esc_html((string) ($result['strictest_bag'] ?? '—')) . '</strong></div><div><small>' . esc_html__('Strictest airline', 'voyasee-bagfit') . '</small><strong>' . esc_html((string) ($result['strictest_airline'] ?? '—')) . '</strong></div><div><small>' . esc_html__('Limiting flight', 'voyasee-bagfit') . '</small><strong>' . esc_html(sprintf(__('Flight %d', 'voyasee-bagfit'), (int) ($result['strictest_leg_number'] ?? 1))) . '</strong></div></div>' . $bags_html . '<section class="actions"><h2>' . esc_html__('Recommended next steps', 'voyasee-bagfit') . '</h2><ol>' . $recommendations . '</ol></section><div class="footer"><span>VOYASEE.COM</span><span>' . esc_html($overall) . '</span></div></section>';
        $html .= '<section class="page"><div class="brand"><b>VOYASEE BAGFIT</b><span>' . esc_html__('Flight-by-flight evidence', 'voyasee-bagfit') . '</span></div><div class="page-title"><span>' . esc_html__('PAGE 2', 'voyasee-bagfit') . '</span><h2>' . esc_html__('Every flight and every bag', 'voyasee-bagfit') . '</h2></div>' . $matrix_html . $smart_plan_html . ($notices ? '<section class="notes"><h3>' . esc_html__('Before you fly', 'voyasee-bagfit') . '</h3><ul>' . $notices . '</ul></section>' : '') . '<section class="notes"><h3>' . esc_html__('Important', 'voyasee-bagfit') . '</h3><p>' . esc_html((string) ($result['disclaimer'] ?? '')) . '</p></section><div class="footer"><span>VOYASEE.COM</span><span>' . esc_html__('The issued ticket and operating airline remain authoritative.', 'voyasee-bagfit') . '</span></div></section></body></html>';
        return $html;
    }

    private static function verdict(string $code): array {
        return match ($code) {
            'FITS' => ['label' => __('Your baggage fits the selected rules', 'voyasee-bagfit'), 'detail' => __('Every measured bag is within the stored physical limits.', 'voyasee-bagfit'), 'tone' => 'pass'],
            'FITS_WITH_CONDITIONS' => ['label' => __('Your baggage fits, but confirm one detail', 'voyasee-bagfit'), 'detail' => __('A booking or source condition still needs confirmation.', 'voyasee-bagfit'), 'tone' => 'warn'],
            'DOES_NOT_FIT' => ['label' => __('One or more bags exceed a limit', 'voyasee-bagfit'), 'detail' => __('Use the exact failure and next action shown below.', 'voyasee-bagfit'), 'tone' => 'fail'],
            'NOT_INCLUDED' => ['label' => __('A selected allowance does not include this bag', 'voyasee-bagfit'), 'detail' => __('The bag may fit physically but needs a different fare or baggage add-on.', 'voyasee-bagfit'), 'tone' => 'warn'],
            default => ['label' => __('More booking information is required', 'voyasee-bagfit'), 'detail' => __('Confirm the numerical allowance or operating airline.', 'voyasee-bagfit'), 'tone' => 'check'],
        };
    }

    private static function code(string $code): array {
        if (str_starts_with($code, 'FAIL')) return ['label' => __('Over limit', 'voyasee-bagfit'), 'tone' => 'fail'];
        if ('NOT_INCLUDED' === $code) return ['label' => __('Not included', 'voyasee-bagfit'), 'tone' => 'warn'];
        if (in_array($code, ['PASS_CONDITIONAL', 'SOURCE_STALE'], true)) return ['label' => __('Conditional', 'voyasee-bagfit'), 'tone' => 'warn'];
        if (in_array($code, ['CHECK_REQUIRED', 'UNKNOWN_RULE'], true)) return ['label' => __('Confirm', 'voyasee-bagfit'), 'tone' => 'check'];
        return ['label' => __('Fits', 'voyasee-bagfit'), 'tone' => 'pass'];
    }

    private static function simple_status(string $status): string {
        return match ($status) {'pass' => __('Within limit', 'voyasee-bagfit'), 'fail' => __('Over limit', 'voyasee-bagfit'), default => __('Not published', 'voyasee-bagfit')};
    }

    private static function booking(string $status): string {
        return match ($status) {'included' => __('Included', 'voyasee-bagfit'), 'not_included' => __('Not included', 'voyasee-bagfit'), default => __('Confirm', 'voyasee-bagfit')};
    }

    private static function num(float $number): string {
        return rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');
    }
}
