<?php
defined('ABSPATH') || exit;

final class VSB_Advisor {
    public static function build(array $result): array {
        $strictest = self::strictest($result);
        $failed = is_array($strictest['failed_checks'] ?? null) ? $strictest['failed_checks'] : [];
        $actions = [];
        $what_if = [];
        foreach ($failed as $item) {
            $type = (string) ($item['type'] ?? '');
            if ('dimension' === $type) {
                $cm = self::num(((int) $item['over_by_mm']) / 10);
                $actions[] = ['icon' => '↔', 'title' => sprintf(__('Reduce the failed side by %s cm', 'voyasee-bagfit'), $cm), 'detail' => __('Close expansion zips, empty outer pockets, compress soft contents or use a smaller bag.', 'voyasee-bagfit')];
                $what_if[] = ['label' => sprintf(__('Reduce that side by %s cm', 'voyasee-bagfit'), $cm), 'outcome' => __('The recorded size rule would pass, subject to fare inclusion.', 'voyasee-bagfit')];
            } elseif ('linear_dimension' === $type) {
                $cm = self::num(((int) $item['over_by_mm']) / 10);
                $actions[] = (float) $cm >= 20
                    ? ['icon' => '▣', 'title' => __('Use a smaller suitcase or ask about oversized baggage', 'voyasee-bagfit'), 'detail' => sprintf(__('The current case is %s cm beyond the total-size limit, which is too large for a small packing adjustment.', 'voyasee-bagfit'), $cm)]
                    : ['icon' => '∑', 'title' => sprintf(__('Reduce the total size by %s cm', 'voyasee-bagfit'), $cm), 'detail' => __('Close expandable sections or switch to a slightly smaller case.', 'voyasee-bagfit')];
                $what_if[] = ['label' => __('Use a bag within the total-size limit', 'voyasee-bagfit'), 'outcome' => __('The recorded checked-bag size rule would pass.', 'voyasee-bagfit')];
            } elseif (in_array($type, ['weight', 'combined_weight'], true)) {
                $kg = self::num(((int) $item['over_by_g']) / 1000);
                $actions[] = ['icon' => 'kg', 'title' => sprintf(__('Remove at least %s kg', 'voyasee-bagfit'), $kg), 'detail' => 'combined_weight' === $type ? __('Reduce the total weight across the cabin bags sharing this allowance.', 'voyasee-bagfit') : __('Move permitted dense items, split the contents or purchase the correct baggage allowance.', 'voyasee-bagfit')];
                $what_if[] = ['label' => sprintf(__('Remove %s kg', 'voyasee-bagfit'), $kg), 'outcome' => __('The selected weight rule would pass.', 'voyasee-bagfit')];
            }
        }
        $verdict = (string) ($result['overall_verdict'] ?? 'CHECK_REQUIRED');
        if (!$actions) {
            $actions[] = match ($verdict) {
                'FITS' => ['icon' => '✓', 'title' => __('Keep every bag at this packed size', 'voyasee-bagfit'), 'detail' => __('Do not expand the cases or add external items after measuring.', 'voyasee-bagfit')],
                'NOT_INCLUDED' => ['icon' => '+', 'title' => __('Add the correct baggage product', 'voyasee-bagfit'), 'detail' => __('The bag may fit physically, but the selected fare or piece allowance does not include it.', 'voyasee-bagfit')],
                default => ['icon' => '?', 'title' => __('Confirm the exact booking allowance', 'voyasee-bagfit'), 'detail' => __('Check the operating carrier, included pieces, size limit and weight limit shown in the issued booking.', 'voyasee-bagfit')],
            };
        }
        if (count((array) ($result['flight_matrix'] ?? [])) > 1) {
            $actions[] = ['icon' => '✈', 'title' => __('Prepare for the strictest operating airline', 'voyasee-bagfit'), 'detail' => sprintf(__('Flight %1$d on %2$s is currently the limiting flight.', 'voyasee-bagfit'), (int) ($strictest['leg_number'] ?? 1), (string) ($strictest['airline']['name'] ?? __('the airline', 'voyasee-bagfit')))];
        }
        $bag = is_array($result['bags'][0] ?? null) ? $result['bags'][0] : (array) ($result['bag'] ?? []);
        $airport_script = sprintf(
            __('Please confirm whether my %1$s measuring %2$s and weighing %3$s is included on this fare and accepted by the operating carrier for flight %4$d.', 'voyasee-bagfit'),
            str_replace('_', ' ', (string) ($bag['type'] ?? 'bag')),
            self::dims((array) ($bag['dimensions_mm'] ?? [])),
            self::num(((int) ($bag['weight_g'] ?? 0)) / 1000) . ' kg',
            (int) ($strictest['leg_number'] ?? 1)
        );
        return [
            'headline' => self::headline($verdict),
            'summary' => self::summary($result, $strictest),
            'actions' => array_slice($actions, 0, 6),
            'what_if' => array_slice($what_if, 0, 4),
            'confirmation_questions' => array_slice((array) ($result['public_notices'] ?? []), 0, 6),
            'airport_script' => $airport_script,
            'source_policy' => __('Use the issued ticket and the linked official airline page as the final reference.', 'voyasee-bagfit'),
        ];
    }

    private static function strictest(array $result): array {
        $bag_name = (string) ($result['strictest_bag'] ?? '');
        $num = (int) ($result['strictest_leg_number'] ?? 1);
        foreach ((array) ($result['bag_results'] ?? []) as $bag_result) {
            if ($bag_name && (string) ($bag_result['bag']['name'] ?? '') !== $bag_name) continue;
            foreach ((array) ($bag_result['legs'] ?? []) as $leg) {
                if ((int) ($leg['leg_number'] ?? 0) === $num) return $leg;
            }
        }
        return (array) (($result['legs'][0] ?? []));
    }

    private static function headline(string $value): string {
        return match ($value) {
            'FITS' => __('Every checked bag is within the selected limits', 'voyasee-bagfit'),
            'FITS_WITH_CONDITIONS' => __('The bags fit physically, but one detail still needs confirmation', 'voyasee-bagfit'),
            'NOT_INCLUDED' => __('At least one bag is not included in the selected allowance', 'voyasee-bagfit'),
            'DOES_NOT_FIT' => __('At least one bag is over a selected size or weight limit', 'voyasee-bagfit'),
            default => __('More booking information is required', 'voyasee-bagfit'),
        };
    }

    private static function summary(array $result, array $strictest): string {
        $airline = (string) ($strictest['airline']['name'] ?? __('the selected airline', 'voyasee-bagfit'));
        $flight = (int) ($strictest['leg_number'] ?? 1);
        $bag = (string) ($result['strictest_bag'] ?? __('the strictest bag', 'voyasee-bagfit'));
        return sprintf(__('%1$s on flight %2$d with %3$s creates the strictest result.', 'voyasee-bagfit'), $bag, $flight, $airline);
    }

    private static function dims(array $dims): string {
        return count($dims) === 3 ? implode(' × ', array_map(static fn($v) => self::num(((int) $v) / 10), $dims)) . ' cm' : __('dimensions not recorded', 'voyasee-bagfit');
    }

    private static function num(float $value): string {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
