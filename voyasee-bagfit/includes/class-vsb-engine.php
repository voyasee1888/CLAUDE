<?php
defined('ABSPATH') || exit;

/**
 * Deterministic baggage rules engine.
 * No AI or paid API is used. All decisions come from the local, versioned database
 * plus ticket values explicitly confirmed by the traveller.
 */
final class VSB_Engine {
    private const VERDICT_SEVERITY = [
        'PASS' => 0,
        'PASS_CONDITIONAL' => 20,
        'SOURCE_STALE' => 25,
        'UNKNOWN_RULE' => 45,
        'CHECK_REQUIRED' => 50,
        'NOT_INCLUDED' => 70,
        'FAIL_WEIGHT' => 90,
        'FAIL_SIZE' => 90,
        'FAIL_SIZE_WEIGHT' => 100,
    ];

    public static function check(array $input): array|WP_Error {
        $raw_bags = is_array($input['bags'] ?? null) ? $input['bags'] : [];
        if (!$raw_bags && is_array($input['bag'] ?? null)) {
            $raw_bags = [$input['bag']];
        }
        if (count($raw_bags) < 1 || count($raw_bags) > 6) {
            return new WP_Error('vsb_bags', __('Add between one and six bags.', 'voyasee-bagfit'), ['status' => 400]);
        }

        $bags = [];
        foreach ($raw_bags as $index => $raw_bag) {
            if (!is_array($raw_bag)) {
                continue;
            }
            $bag = self::normalise_bag($raw_bag, $index + 1);
            if (is_wp_error($bag)) {
                return $bag;
            }
            $bags[] = $bag;
        }
        if (!$bags) {
            return new WP_Error('vsb_bags_empty', __('No valid bags were supplied.', 'voyasee-bagfit'), ['status' => 400]);
        }

        $raw_flights = is_array($input['flights'] ?? null) ? $input['flights'] : [];
        if (count($raw_flights) < 1 || count($raw_flights) > 10) {
            return new WP_Error('vsb_flights', __('Add between one and ten flights.', 'voyasee-bagfit'), ['status' => 400]);
        }

        $journey = self::normalise_journey(is_array($input['journey'] ?? null) ? $input['journey'] : []);
        $bag_results = [];
        foreach ($bags as $bag) {
            $legs = [];
            foreach ($raw_flights as $index => $raw_flight) {
                if (!is_array($raw_flight)) {
                    continue;
                }
                $leg = self::evaluate_leg($bag, $raw_flight, $index + 1, $journey);
                if (is_wp_error($leg)) {
                    return $leg;
                }
                $legs[] = $leg;
            }
            if (!$legs) {
                return new WP_Error('vsb_flights_empty', __('No valid flights were supplied.', 'voyasee-bagfit'), ['status' => 400]);
            }
            $bag_results[] = ['bag' => $bag, 'legs' => $legs];
        }

        self::apply_piece_and_combined_weight_rules($bag_results, (int) $journey['travellers']);
        foreach ($bag_results as $index => $bag_result) {
            $bag_results[$index] = self::summarise_bag_result($bag_result['bag'], $bag_result['legs']);
        }

        $all_legs = [];
        foreach ($bag_results as $bag_index => $bag_result) {
            foreach ($bag_result['legs'] as $leg) {
                $leg['_bag_index'] = $bag_index;
                $leg['_bag_name'] = $bag_result['bag']['name'];
                $all_legs[] = $leg;
            }
        }
        usort($all_legs, static function (array $a, array $b): int {
            $severity = ($b['severity_score'] ?? 0) <=> ($a['severity_score'] ?? 0);
            return 0 !== $severity ? $severity : (($b['pressure_percent'] ?? 0) <=> ($a['pressure_percent'] ?? 0));
        });
        $strictest = $all_legs[0];
        $overall = self::overall_from_codes(array_column($all_legs, 'verdict_code'));
        $flight_matrix = self::flight_matrix($bag_results, $raw_flights);
        $public_notices = self::public_notices($bags, $bag_results, $journey);
        $recommendations = self::journey_recommendations($overall, $bag_results, $strictest, $journey);

        $result = [
            'schema_version' => '7.0',
            'result_id' => wp_generate_uuid4(),
            'generated_at' => gmdate('c'),
            'mode' => self::choice($input['mode'] ?? 'full', ['quick', 'full'], 'full'),
            'journey' => $journey,
            'bags' => $bags,
            'bag_results' => $bag_results,
            // Backwards-compatible keys for old saved-report readers.
            'bag' => $bags[0],
            'legs' => $bag_results[0]['legs'],
            'overall_verdict' => $overall,
            'strictest_leg_number' => (int) ($strictest['leg_number'] ?? 1),
            'strictest_airline' => (string) ($strictest['airline']['name'] ?? ''),
            'strictest_bag' => (string) ($strictest['_bag_name'] ?? ''),
            'strictest_reason' => (string) ($strictest['strictest_reason'] ?? ''),
            'pressure_percent' => (int) ($strictest['pressure_percent'] ?? 0),
            'flight_matrix' => $flight_matrix,
            'public_notices' => $public_notices,
            'recommendations' => $recommendations,
            'fee_status' => 'live_booking_required',
            'fee_message' => __('Baggage prices can change by route, fare, date, airport and purchase stage. Use the airline booking page for the current price.', 'voyasee-bagfit'),
            'disclaimer' => __('Voyasee BagFit is a planning assistant. The issued ticket, operating airline and airport staff remain the final authority. Recheck the official airline rule before departure.', 'voyasee-bagfit'),
        ];
        $result['smart_plan'] = VSB_Advisor::build($result);
        return $result;
    }

    public static function reverse_search(array $input): array|WP_Error {
        $bag = self::normalise_bag(is_array($input['bag'] ?? null) ? $input['bag'] : [], 1);
        if (is_wp_error($bag)) {
            return $bag;
        }
        $free_only = !empty($input['free_only']);
        $region = sanitize_text_field((string) ($input['region'] ?? ''));
        $results = ['fits_free' => [], 'fits_addon' => [], 'does_not_fit' => [], 'confirm' => []];

        foreach (VSB_DB::public_airlines() as $airline) {
            if ($region && stripos((string) ($airline['country'] ?? ''), $region) === false) {
                continue;
            }
            $option = self::best_option_for_reverse($airline, $bag['type'], $free_only);
            if (!$option) {
                $results['confirm'][] = self::reverse_item($airline, null, 'CHECK_REQUIRED', [], __('No structured numerical rule is stored yet.', 'voyasee-bagfit'));
                continue;
            }
            $rule = self::rule_from_option($option);
            $size = self::size_check($bag, $rule['dimensions_mm'], $rule['max_linear_mm']);
            $weight = self::weight_check($bag, $rule['max_weight_g']);
            $failed = array_merge($size['failed_checks'], $weight['failed_checks']);
            $verification = (string) ($airline['data_quality']['verification_status'] ?? '');
            if ('fail' === $size['status'] || 'fail' === $weight['status']) {
                $results['does_not_fit'][] = self::reverse_item($airline, $option, self::code_from_status($size['status'], $weight['status'], $rule['inclusion']), $failed, self::failure_summary($failed));
            } elseif (!in_array($verification, ['reviewed_against_official_page'], true) || 'unknown' === $size['status']) {
                $results['confirm'][] = self::reverse_item($airline, $option, 'CHECK_REQUIRED', [], __('The official source needs confirmation before relying on this match.', 'voyasee-bagfit'));
            } elseif ('included' === $rule['inclusion']) {
                $results['fits_free'][] = self::reverse_item($airline, $option, 'PASS', [], __('Fits the stored included allowance.', 'voyasee-bagfit'));
            } else {
                $results['fits_addon'][] = self::reverse_item($airline, $option, 'PASS_CONDITIONAL', [], __('Fits the stored dimensions, but a fare or baggage add-on may be required.', 'voyasee-bagfit'));
            }
        }

        foreach ($results as $key => $items) {
            usort($items, static fn(array $a, array $b): int => strcmp($a['airline'], $b['airline']));
            $results[$key] = array_slice($items, 0, 250);
        }
        return ['schema_version' => '7.0', 'bag' => $bag, 'groups' => $results, 'counts' => array_map('count', $results)];
    }

    public static function shared_size(array $input): array|WP_Error {
        $slugs = is_array($input['airlines'] ?? null) ? array_values(array_unique(array_map('sanitize_title', $input['airlines']))) : [];
        $slugs = array_slice(array_filter($slugs), 0, 20);
        if (count($slugs) < 2) {
            return new WP_Error('vsb_shared_airlines', __('Choose at least two airlines.', 'voyasee-bagfit'), ['status' => 400]);
        }
        $type = self::choice($input['bag_type'] ?? 'cabin', ['personal', 'cabin'], 'cabin');
        $free_only = !empty($input['free_only']);
        $selected = [];
        $unknown = [];
        foreach ($slugs as $slug) {
            $airline = VSB_DB::airline_by_slug($slug);
            if (!$airline) {
                continue;
            }
            $option = self::best_option_for_reverse($airline, $type, $free_only);
            if (!$option || empty($option['dimensions_mm']) || !is_array($option['dimensions_mm'])) {
                $unknown[] = ['airline' => (string) ($airline['name'] ?? $slug), 'source_url' => esc_url_raw((string) ($airline['source_url'] ?? ''))];
                continue;
            }
            $dims = array_map('intval', $option['dimensions_mm']);
            rsort($dims, SORT_NUMERIC);
            $selected[] = ['airline' => $airline, 'option' => $option, 'dims' => $dims, 'weight' => !empty($option['max_weight_g']) ? (int) $option['max_weight_g'] : null];
        }
        if (count($selected) < 2) {
            return new WP_Error('vsb_shared_rules', __('At least two selected airlines need stored numerical dimensions.', 'voyasee-bagfit'), ['status' => 400]);
        }
        $shared = [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX];
        foreach ($selected as $item) {
            foreach ($item['dims'] as $i => $value) {
                $shared[$i] = min($shared[$i], $value);
            }
        }
        $weights = array_values(array_filter(array_column($selected, 'weight')));
        $weight = $weights ? min($weights) : null;
        $limiters = [[], [], []];
        foreach ($selected as $item) {
            foreach ($item['dims'] as $i => $value) {
                if ($value === $shared[$i]) {
                    $limiters[$i][] = (string) $item['airline']['name'];
                }
            }
        }
        if ($weight) {
            $weight_limiters = [];
            foreach ($selected as $item) {
                if ($item['weight'] === $weight) {
                    $weight_limiters[] = (string) $item['airline']['name'];
                }
            }
        } else {
            $weight_limiters = [];
        }
        return [
            'schema_version' => '7.0',
            'bag_type' => $type,
            'dimensions_mm' => $shared,
            'max_weight_g' => $weight,
            'volume_l' => round(($shared[0] * $shared[1] * $shared[2]) / 1000000, 1),
            'dimension_limiters' => $limiters,
            'weight_limiters' => $weight_limiters,
            'airlines' => array_map(static fn(array $item): array => [
                'name' => (string) $item['airline']['name'],
                'slug' => (string) $item['airline']['slug'],
                'label' => (string) ($item['option']['label'] ?? ''),
                'dimensions_mm' => $item['dims'],
                'max_weight_g' => $item['weight'],
                'inclusion' => (string) ($item['option']['inclusion'] ?? 'conditional'),
                'source_url' => esc_url_raw((string) ($item['airline']['source_url'] ?? '')),
            ], $selected),
            'unknown' => $unknown,
            'message' => __('This is the largest rectangular bag size that stays within every stored dimensional limit selected. Confirm fare inclusion and the latest official rule before purchase.', 'voyasee-bagfit'),
        ];
    }

    public static function parse_ticket_line(string $line): array {
        $raw = strtoupper(trim(wp_strip_all_tags($line)));
        $raw = preg_replace('/\s+/', ' ', $raw) ?: '';
        $out = ['raw' => $raw, 'recognized' => false, 'no_bag' => false, 'pieces' => null, 'weight_kg' => null, 'linear_cm' => null, 'scope' => 'checked', 'notes' => []];
        if ('' === $raw) {
            return $out;
        }
        if (preg_match('/\b(NO\s*(CHECKED\s*)?BAG|NIL|0\s*PC|NO\s*BAGGAGE|NO\s*FREE\s*BAG)\b/', $raw)) {
            $out['recognized'] = true;
            $out['no_bag'] = true;
            $out['pieces'] = 0;
            $out['notes'][] = __('The pasted line appears to show no included checked bag.', 'voyasee-bagfit');
            return $out;
        }
        if (str_contains($raw, 'CABIN') || str_contains($raw, 'HAND BAG') || str_contains($raw, 'CARRY')) {
            $out['scope'] = 'cabin';
        }
        if (str_contains($raw, 'HOLD') || str_contains($raw, 'HLD') || str_contains($raw, 'CHECKED')) {
            $out['scope'] = 'checked';
        }
        // Piece count: "2PC", "2 PC", "2 BAG", "2 BAGS", "2 PIECE"
        if (preg_match('/\b(\d{1,2})\s*(?:PC|BAG|BAGS|PIECE|PIECES)\b/', $raw, $m)) {
            $out['pieces'] = min(10, (int) $m[1]);
            $out['recognized'] = true;
        }
        // Weight: "23KG", "23 KG", "23K" (shorthand), "50LB"
        if (preg_match('/\b(\d{1,3}(?:[\.,]\d+)?)\s*KG\b/', $raw, $m)) {
            $out['weight_kg'] = (float) str_replace(',', '.', $m[1]);
            $out['recognized'] = true;
        } elseif (preg_match('/\b(\d{1,3}(?:[\.,]\d+)?)\s*K\b/', $raw, $m)) {
            // "23K" shorthand for kilograms
            $out['weight_kg'] = (float) str_replace(',', '.', $m[1]);
            $out['recognized'] = true;
            $out['notes'][] = __('Weight read as kg from shorthand format.', 'voyasee-bagfit');
        } elseif (preg_match('/\b(\d{1,3}(?:[\.,]\d+)?)\s*LB(?:S)?\b/', $raw, $m)) {
            $out['weight_kg'] = round(((float) str_replace(',', '.', $m[1])) * 0.45359237, 2);
            $out['recognized'] = true;
            $out['notes'][] = __('Pounds were converted to kilograms.', 'voyasee-bagfit');
        }
        // Linear total: "158CM", "62IN"
        if (preg_match('/\b(?:LINEAR\s*)?(1[2-9]\d|2\d\d)\s*CM\b/', $raw, $m)) {
            $out['linear_cm'] = (float) $m[1];
            $out['recognized'] = true;
        } elseif (preg_match('/\b(5\d|6\d|7\d)\s*(?:IN|INCH|INCHES)\b/', $raw, $m)) {
            $out['linear_cm'] = round(((float) $m[1]) * 2.54, 1);
            $out['recognized'] = true;
        }
        // 3D dimensions: "55×40×20CM", "55 x 40 x 20 CM", "55X40X20"
        if (null === $out['linear_cm'] && preg_match('/(\d{1,3})\s*[×xX\*]\s*(\d{1,3})\s*[×xX\*]\s*(\d{1,3})\s*(?:CM)?/', $raw, $m)) {
            $d1 = (int) $m[1]; $d2 = (int) $m[2]; $d3 = (int) $m[3];
            // Only accept if values are in plausible bag range (5–200)
            if ($d1 >= 5 && $d2 >= 5 && $d3 >= 5 && $d1 <= 200 && $d2 <= 200 && $d3 <= 200) {
                $out['linear_cm'] = $d1 + $d2 + $d3;
                $out['recognized'] = true;
                $out['notes'][] = __('Three-dimensional size detected; linear total computed.', 'voyasee-bagfit');
            }
        }
        if (null === $out['pieces'] && null !== $out['weight_kg']) {
            $out['pieces'] = 1;
        }
        if ($out['recognized']) {
            $out['notes'][] = __('Review the extracted values against the issued booking before using them.', 'voyasee-bagfit');
        }
        return $out;
    }

    private static function normalise_bag(array $raw, int $index): array|WP_Error {
        $type = self::choice($raw['type'] ?? 'cabin', ['personal', 'cabin', 'checked'], 'cabin');
        $unit_d = self::choice($raw['dimension_unit'] ?? 'cm', ['cm', 'in'], 'cm');
        $unit_w = self::choice($raw['weight_unit'] ?? 'kg', ['kg', 'lb'], 'kg');
        $dims = [];
        foreach (['length', 'width', 'height'] as $key) {
            $value = isset($raw[$key]) ? (float) $raw[$key] : 0.0;
            if ($value <= 0 || $value > 500) {
                return new WP_Error('vsb_dimensions', sprintf(__('Enter all three realistic dimensions for bag %d.', 'voyasee-bagfit'), $index), ['status' => 400]);
            }
            $dims[] = (int) round('in' === $unit_d ? $value * 25.4 : $value * 10);
        }
        $weight = isset($raw['weight']) && '' !== (string) $raw['weight'] ? (float) $raw['weight'] : 0.0;
        if ($weight < 0 || $weight > 200) {
            return new WP_Error('vsb_weight', sprintf(__('Enter a realistic weight for bag %d.', 'voyasee-bagfit'), $index), ['status' => 400]);
        }
        $grams = (int) round('lb' === $unit_w ? $weight * 453.59237 : $weight * 1000);
        return [
            'id' => sanitize_key((string) ($raw['id'] ?? 'bag-' . $index)),
            'name' => sanitize_text_field((string) ($raw['name'] ?? sprintf(__('Bag %d', 'voyasee-bagfit'), $index))),
            'owner' => sanitize_text_field((string) ($raw['owner'] ?? __('Traveller 1', 'voyasee-bagfit'))),
            'type' => $type,
            'dimensions_mm' => $dims,
            'weight_g' => $grams,
            'dimension_unit' => $unit_d,
            'weight_unit' => $unit_w,
            'soft_sided' => !empty($raw['soft_sided']),
            'expandable' => !empty($raw['expandable']),
            'wheels_included' => !empty($raw['wheels_included']),
            'linear_mm' => array_sum($dims),
            'volume_l' => round(($dims[0] * $dims[1] * $dims[2]) / 1000000, 1),
        ];
    }

    private static function normalise_journey(array $raw): array {
        return [
            'ticket_type' => self::choice($raw['ticket_type'] ?? 'one_ticket', ['one_ticket', 'separate_tickets', 'unknown'], 'one_ticket'),
            'checked_through' => self::choice($raw['checked_through'] ?? 'unknown', ['yes', 'no', 'unknown'], 'unknown'),
            'self_transfer' => !empty($raw['self_transfer']),
            'travellers' => max(1, min(9, absint($raw['travellers'] ?? 1))),
        ];
    }

    private static function evaluate_leg(array $bag, array $raw, int $number, array $journey): array|WP_Error {
        $marketing_slug = sanitize_title((string) ($raw['airline_slug'] ?? ''));
        $operating_slug = sanitize_title((string) ($raw['operating_airline_slug'] ?? ''));
        $effective_slug = $operating_slug ?: $marketing_slug;
        $airline = VSB_DB::airline_by_slug($effective_slug);
        if (!$airline) {
            return new WP_Error('vsb_airline', sprintf(__('Airline not found for flight %d.', 'voyasee-bagfit'), $number), ['status' => 400]);
        }
        $origin = strtoupper(substr(sanitize_text_field((string) ($raw['origin'] ?? '')), 0, 100));
        $destination = strtoupper(substr(sanitize_text_field((string) ($raw['destination'] ?? '')), 0, 100));
        $date = sanitize_text_field((string) ($raw['date'] ?? ''));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
        $cabin_class = self::choice($raw['cabin_class'] ?? 'unknown', ['unknown', 'economy', 'premium', 'business', 'first'], 'unknown');
        $fare_class = self::choice($raw['fare_class'] ?? 'unknown', ['unknown', 'basic', 'standard', 'premium_economy', 'flexible'], 'unknown');
        $allowances = is_array($raw['allowances'] ?? null) ? $raw['allowances'] : [];
        $allowance_option = sanitize_key((string) ($allowances[$bag['type']] ?? $raw['allowance_option'] ?? 'unknown'));
        $resolved = self::resolve_rule($airline, $bag['type'], $allowance_option, $raw);
        $size = self::size_check($bag, $resolved['dimensions_mm'], $resolved['max_linear_mm']);
        $weight = self::weight_check($bag, $resolved['max_weight_g']);
        $code = self::code_from_status($size['status'], $weight['status'], $resolved['inclusion']);
        $notices = [];
        if ('unknown' === $allowance_option || str_contains($allowance_option, 'unknown') || 'official-confirmation' === $allowance_option) {
            $notices[] = __('Confirm the exact baggage allowance shown in the booking.', 'voyasee-bagfit');
        }
        if (empty($operating_slug) && !empty($raw['codeshare_unknown'])) {
            $notices[] = __('Confirm which airline operates the aircraft.', 'voyasee-bagfit');
        }
        if ('checked' === $bag['type'] && 'ticket_input' !== $resolved['source_kind']) {
            $notices[] = __('Use the checked-baggage allowance printed in the issued booking.', 'voyasee-bagfit');
        }
        if ($journey['ticket_type'] === 'separate_tickets' && 'checked' === $bag['type']) {
            $notices[] = __('Separate tickets may require baggage collection, recheck and another fee.', 'voyasee-bagfit');
        }
        if (!empty($resolved['condition_note'])) {
            $notices[] = sanitize_text_field((string) $resolved['condition_note']);
        }
        $verification = (string) ($airline['data_quality']['verification_status'] ?? '');
        $freshness = self::freshness($airline);
        $source_status = 'official_source_linked';
        if ('reviewed_against_official_page' === $verification && !$freshness['stale']) {
            $source_status = 'checked_recently';
        } elseif ('directory_only_official_site' === $verification) {
            $source_status = 'official_site_only';
            if (!in_array($code, ['FAIL_SIZE', 'FAIL_WEIGHT', 'FAIL_SIZE_WEIGHT'], true)) {
                $code = 'CHECK_REQUIRED';
            }
            $notices[] = __('No current numerical allowance is stored for this airline yet.', 'voyasee-bagfit');
        } else {
            $notices[] = __('Check the latest official airline rule before departure.', 'voyasee-bagfit');
            if ('PASS' === $code) {
                $code = 'PASS_CONDITIONAL';
            }
        }
        if ($freshness['stale'] && 'PASS' === $code) {
            $code = 'SOURCE_STALE';
            $notices[] = __('The stored rule is older than the recommended review window. Confirm the current official policy before travel.', 'voyasee-bagfit');
        }
        if ('unknown' === $size['status'] && 'unknown' === $weight['status']) {
            $code = 'CHECK_REQUIRED';
        }
        $enforcement = is_array($airline['enforcement'] ?? null) ? $airline['enforcement'] : ['level' => 'low', 'note' => ''];
        $enforcement_level = sanitize_key((string) ($enforcement['level'] ?? 'low'));
        $enforcement_note = sanitize_text_field((string) ($enforcement['note'] ?? ''));
        $fee_estimate = is_array($airline['fee_estimate'] ?? null) ? $airline['fee_estimate'] : null;
        $fare_class_info = is_array($airline['fare_class_affects'] ?? null) ? $airline['fare_class_affects'] : null;
        $fare_class_warning = '';
        if ($fare_class_info && 'basic' === $fare_class) {
            $basic = $fare_class_info['basic_economy'] ?? null;
            if (is_array($basic) && !empty($basic['note'])) {
                $fare_class_warning = sanitize_text_field((string) $basic['note']);
            }
        } elseif ($fare_class_info && 'unknown' === $fare_class) {
            $notices[] = __('This airline\'s baggage allowance changes by fare type. Select the fare type shown on the booking for an exact result.', 'voyasee-bagfit');
        }
        if ($fare_class_warning) {
            $notices[] = $fare_class_warning;
        }
        if ('high' === $enforcement_level && $enforcement_note) {
            $notices[] = $enforcement_note;
        }
        // Soft-bag leniency: only when enforcement is low and bag is soft-sided, add 5mm grace to each dimension
        $soft_leniency_applied = false;
        if ($bag['soft_sided'] && 'low' === $enforcement_level && 'fail' === $size['status']) {
            $grace_dims = $bag['dimensions_mm'];
            foreach ($grace_dims as $i => $d) {
                $grace_dims[$i] = max(0, $d - 5); // 5mm grace per axis
            }
            $grace_bag = array_merge($bag, ['dimensions_mm' => $grace_dims, 'linear_mm' => array_sum($grace_dims)]);
            $grace_size = self::size_check($grace_bag, $resolved['dimensions_mm'], $resolved['max_linear_mm']);
            if ('pass' === $grace_size['status']) {
                $soft_leniency_applied = true;
                $size = $grace_size;
                $code = self::code_from_status($size['status'], $weight['status'], $resolved['inclusion']);
                $notices[] = __('Soft-bag compression may let this bag fit — but only where manual sizer checks are used, not automated scanners.', 'voyasee-bagfit');
            }
        }
        $failed = array_merge($size['failed_checks'], $weight['failed_checks']);
        $pressure = max($size['pressure_percent'], $weight['pressure_percent']);
        return [
            'leg_number' => $number,
            'marketing_airline_slug' => $marketing_slug,
            'operating_airline_slug' => $operating_slug,
            'airline' => [
                'name' => sanitize_text_field((string) ($airline['name'] ?? '')),
                'slug' => sanitize_title((string) ($airline['slug'] ?? '')),
                'iata' => sanitize_text_field((string) ($airline['iata'] ?? '')),
                'country' => sanitize_text_field((string) ($airline['country'] ?? '')),
                'source_url' => esc_url_raw((string) ($airline['source_url'] ?? '')),
                'manage_booking_url' => esc_url_raw((string) ($airline['manage_booking_url'] ?? '')),
                'last_verified' => sanitize_text_field((string) ($airline['last_verified'] ?? '')),
                'coverage_tier' => sanitize_key((string) ($airline['coverage_tier'] ?? 'directory')),
                'enforcement_level' => $enforcement_level,
                'enforcement_note' => $enforcement_note,
            ],
            'origin' => $origin,
            'destination' => $destination,
            'date' => $date,
            'cabin_class' => $cabin_class,
            'allowance_option' => $allowance_option,
            'allowance_label' => sanitize_text_field((string) ($resolved['label'] ?? '')),
            'rule' => $resolved,
            'size_status' => $size['status'],
            'weight_status' => $weight['status'],
            'booking_status' => self::booking_status($resolved['inclusion']),
            'best_orientation_mm' => $size['best_orientation_mm'],
            'dimension_usage_percent' => $size['pressure_percent'],
            'weight_usage_percent' => $weight['pressure_percent'],
            'pressure_percent' => $pressure,
            'verdict_code' => $code,
            'public_notices' => array_values(array_unique($notices)),
            'source_status' => $source_status,
            'failed_checks' => $failed,
            'strictest_reason' => self::strictest_reason($code, $failed, $resolved['inclusion'], $pressure),
            'severity_score' => (self::VERDICT_SEVERITY[$code] ?? 50) * 1000 + $pressure,
            'enforcement' => ['level' => $enforcement_level, 'note' => $enforcement_note],
            'fee_estimate' => $fee_estimate ? [
                'gate_fee_usd' => (int) ($fee_estimate['gate_fee_usd'] ?? 0),
                'online_checked_usd' => (int) ($fee_estimate['online_checked_usd'] ?? 0),
                'gate_checked_usd' => (int) ($fee_estimate['gate_checked_usd'] ?? 0),
            ] : null,
            'soft_leniency_applied' => $soft_leniency_applied,
            'fare_class_warning' => $fare_class_warning,
        ];
    }

    private static function resolve_rule(array $airline, string $bag_type, string $option_id, array $raw): array {
        if ('checked' === $bag_type) {
            $ticket_text = sanitize_text_field((string) ($raw['ticket_allowance_text'] ?? ''));
            $parsed = self::parse_ticket_line($ticket_text);
            $ticket_weight = self::positive_float($raw['ticket_weight_kg'] ?? null) ?? ($parsed['scope'] === 'checked' ? $parsed['weight_kg'] : null);
            $ticket_linear = self::positive_float($raw['ticket_linear_cm'] ?? null) ?? $parsed['linear_cm'];
            $ticket_length = self::positive_float($raw['ticket_length_cm'] ?? null);
            $ticket_width = self::positive_float($raw['ticket_width_cm'] ?? null);
            $ticket_height = self::positive_float($raw['ticket_height_cm'] ?? null);
            $ticket_pieces = max(0, min(10, absint($raw['ticket_pieces'] ?? ($parsed['pieces'] ?? 1))));
            $dims = ($ticket_length && $ticket_width && $ticket_height) ? [(int) round($ticket_length * 10), (int) round($ticket_width * 10), (int) round($ticket_height * 10)] : null;
            if ($parsed['no_bag']) {
                return ['label' => __('No checked bag shown in the booking', 'voyasee-bagfit'), 'dimensions_mm' => null, 'max_linear_mm' => null, 'max_weight_g' => null, 'combined_weight_g' => null, 'piece_count' => 0, 'placement' => 'hold', 'inclusion' => 'not_included', 'source_kind' => 'ticket_input', 'condition_note' => ''];
            }
            if ($ticket_weight || $ticket_linear || $dims) {
                return ['label' => __('Allowance entered from the booking', 'voyasee-bagfit'), 'dimensions_mm' => $dims, 'max_linear_mm' => $ticket_linear ? (int) round($ticket_linear * 10) : null, 'max_weight_g' => $ticket_weight ? (int) round($ticket_weight * 1000) : null, 'combined_weight_g' => null, 'piece_count' => max(1, $ticket_pieces), 'placement' => 'hold', 'inclusion' => 'included', 'source_kind' => 'ticket_input', 'condition_note' => ''];
            }
        }
        $options = is_array($airline['allowance_options'][$bag_type] ?? null) ? $airline['allowance_options'][$bag_type] : [];
        $selected = null;
        foreach ($options as $option) {
            if (is_array($option) && sanitize_key((string) ($option['id'] ?? '')) === $option_id) {
                $selected = $option;
                break;
            }
        }
        if (!$selected) {
            foreach ($options as $option) {
                if (is_array($option) && (str_contains(sanitize_key((string) ($option['id'] ?? '')), 'unknown') || sanitize_key((string) ($option['id'] ?? '')) === 'official-confirmation')) {
                    $selected = $option;
                    break;
                }
            }
        }
        if (!$selected && $options) {
            $selected = $options[0];
        }
        if (!$selected) {
            $base = is_array($airline[$bag_type] ?? null) ? $airline[$bag_type] : [];
            $selected = ['id' => 'fallback', 'label' => __('Published general rule', 'voyasee-bagfit'), 'dimensions_mm' => $base['dimensions_mm'] ?? null, 'max_linear_mm' => $base['max_linear_mm'] ?? null, 'max_weight_g' => $base['max_weight_g'] ?? ($base['standard_max_weight_g'] ?? null), 'piece_count' => 1, 'inclusion' => 'conditional', 'placement' => 'unknown'];
        }
        return self::rule_from_option($selected);
    }

    private static function rule_from_option(array $selected): array {
        return [
            'label' => sanitize_text_field((string) ($selected['label'] ?? '')),
            'option_id' => sanitize_key((string) ($selected['id'] ?? '')),
            'dimensions_mm' => isset($selected['dimensions_mm']) && is_array($selected['dimensions_mm']) ? array_map('intval', $selected['dimensions_mm']) : null,
            'max_linear_mm' => !empty($selected['max_linear_mm']) ? (int) $selected['max_linear_mm'] : null,
            'max_weight_g' => !empty($selected['max_weight_g']) ? (int) $selected['max_weight_g'] : null,
            'combined_weight_g' => !empty($selected['combined_weight_g']) ? (int) $selected['combined_weight_g'] : null,
            'piece_count' => max(0, (int) ($selected['piece_count'] ?? 1)),
            'placement' => sanitize_key((string) ($selected['placement'] ?? 'unknown')),
            'inclusion' => self::choice($selected['inclusion'] ?? 'conditional', ['included', 'not_included', 'conditional', 'ticket_specific'], 'conditional'),
            'source_kind' => sanitize_key((string) ($selected['source_kind'] ?? 'airline_profile')),
            'condition_note' => sanitize_text_field((string) ($selected['condition_note'] ?? '')),
        ];
    }

    private static function apply_piece_and_combined_weight_rules(array &$bag_results, int $travellers = 1): void {
        if (!$bag_results) {
            return;
        }
        $travellers = max(1, min(9, $travellers));
        $flight_count = count($bag_results[0]['legs']);
        for ($flight = 0; $flight < $flight_count; $flight++) {
            foreach (['personal', 'cabin', 'checked'] as $type) {
                $indexes = [];
                $allowed = null;
                foreach ($bag_results as $bag_index => $bag_result) {
                    if (($bag_result['bag']['type'] ?? '') !== $type || !isset($bag_result['legs'][$flight])) {
                        continue;
                    }
                    $indexes[] = $bag_index;
                    $count = (int) ($bag_result['legs'][$flight]['rule']['piece_count'] ?? 1);
                    $allowed = null === $allowed ? $count : min($allowed, $count);
                }
                // The stored piece_count is the per-traveller allowance. A family or group
                // travelling on one booking is each entitled to their own allowance, so the
                // limit that matters here is per-person count multiplied by traveller count,
                // not the per-person count applied to the whole party's combined bags.
                $allowed_for_party = null === $allowed ? null : $allowed * $travellers;
                if (null !== $allowed_for_party && count($indexes) > $allowed_for_party) {
                    foreach (array_slice($indexes, max(0, $allowed_for_party)) as $bag_index) {
                        $leg =& $bag_results[$bag_index]['legs'][$flight];
                        $leg['booking_status'] = 'not_included';
                        if (!in_array($leg['verdict_code'], ['FAIL_SIZE', 'FAIL_WEIGHT', 'FAIL_SIZE_WEIGHT'], true)) {
                            $leg['verdict_code'] = 'NOT_INCLUDED';
                        }
                        $leg['public_notices'][] = $travellers > 1
                            ? sprintf(__('The selected allowance includes %d piece(s) of this bag type per traveller, %d total for %d travellers.', 'voyasee-bagfit'), $allowed, $allowed_for_party, $travellers)
                            : sprintf(__('The selected allowance includes %d piece(s) of this bag type.', 'voyasee-bagfit'), $allowed);
                        self::refresh_leg_score($leg);
                        unset($leg);
                    }
                }
            }

            $combined_limits = [];
            $relevant = [];
            foreach ($bag_results as $bag_index => $bag_result) {
                $type = (string) ($bag_result['bag']['type'] ?? '');
                if (!in_array($type, ['personal', 'cabin'], true) || !isset($bag_result['legs'][$flight])) {
                    continue;
                }
                $relevant[] = $bag_index;
                $limit = (int) ($bag_result['legs'][$flight]['rule']['combined_weight_g'] ?? 0);
                if ($limit > 0) {
                    $combined_limits[] = $limit;
                }
            }
            if ($combined_limits && $relevant) {
                $limit = min($combined_limits);
                $sum = 0;
                foreach ($relevant as $bag_index) {
                    $sum += (int) ($bag_results[$bag_index]['bag']['weight_g'] ?? 0);
                }
                if ($sum > $limit) {
                    foreach ($relevant as $bag_index) {
                        $leg =& $bag_results[$bag_index]['legs'][$flight];
                        $leg['weight_status'] = 'fail';
                        $leg['failed_checks'][] = ['type' => 'combined_weight', 'over_by_g' => $sum - $limit, 'bag_g' => $sum, 'limit_g' => $limit];
                        $leg['verdict_code'] = 'fail' === $leg['size_status'] ? 'FAIL_SIZE_WEIGHT' : 'FAIL_WEIGHT';
                        $leg['weight_usage_percent'] = (int) round(($sum / max(1, $limit)) * 100);
                        $leg['pressure_percent'] = max($leg['dimension_usage_percent'], $leg['weight_usage_percent']);
                        $leg['public_notices'][] = __('The combined cabin and personal-item weight exceeds the selected shared limit.', 'voyasee-bagfit');
                        self::refresh_leg_score($leg);
                        unset($leg);
                    }
                }
            }
        }
    }

    private static function refresh_leg_score(array &$leg): void {
        $leg['public_notices'] = array_values(array_unique($leg['public_notices'] ?? []));
        $leg['severity_score'] = (self::VERDICT_SEVERITY[$leg['verdict_code']] ?? 50) * 1000 + (int) ($leg['pressure_percent'] ?? 0);
        $leg['strictest_reason'] = self::strictest_reason((string) $leg['verdict_code'], (array) ($leg['failed_checks'] ?? []), (string) ($leg['rule']['inclusion'] ?? 'conditional'), (int) ($leg['pressure_percent'] ?? 0));
    }

    private static function summarise_bag_result(array $bag, array $legs): array {
        $ranked = $legs;
        usort($ranked, static fn(array $a, array $b): int => (($b['severity_score'] ?? 0) <=> ($a['severity_score'] ?? 0)) ?: (($b['pressure_percent'] ?? 0) <=> ($a['pressure_percent'] ?? 0)));
        $strictest = $ranked[0];
        $overall = self::overall_from_codes(array_column($legs, 'verdict_code'));
        $notices = [];
        if (!$bag['wheels_included']) {
            $notices[] = __('Measure again with wheels, handles and filled outer pockets included.', 'voyasee-bagfit');
        }
        if ($bag['expandable']) {
            $notices[] = __('Keep the expandable section closed unless the measured size includes it open.', 'voyasee-bagfit');
        }
        foreach ($legs as $leg) {
            $notices = array_merge($notices, (array) ($leg['public_notices'] ?? []));
        }
        return [
            'bag' => $bag,
            'overall_verdict' => $overall,
            'strictest_leg_number' => (int) ($strictest['leg_number'] ?? 1),
            'strictest_airline' => (string) ($strictest['airline']['name'] ?? ''),
            'strictest_reason' => (string) ($strictest['strictest_reason'] ?? ''),
            'pressure_percent' => (int) ($strictest['pressure_percent'] ?? 0),
            'legs' => $legs,
            'public_notices' => array_values(array_unique($notices)),
            'recommendations' => self::bag_recommendations($overall, $bag, $strictest),
        ];
    }

    private static function flight_matrix(array $bag_results, array $raw_flights): array {
        $out = [];
        foreach ($raw_flights as $index => $raw) {
            $row = ['leg_number' => $index + 1, 'route' => trim(sanitize_text_field((string) ($raw['origin'] ?? '')) . ' → ' . sanitize_text_field((string) ($raw['destination'] ?? '')), " →"), 'bags' => []];
            foreach ($bag_results as $bag_result) {
                $leg = $bag_result['legs'][$index] ?? null;
                if (!$leg) {
                    continue;
                }
                $row['airline'] = (string) ($leg['airline']['name'] ?? '');
                $row['source_url'] = (string) ($leg['airline']['source_url'] ?? '');
                $row['coverage_tier'] = (string) ($leg['airline']['coverage_tier'] ?? 'directory');
                $row['last_verified'] = (string) ($leg['airline']['last_verified'] ?? '');
                $row['bags'][] = ['bag_id' => (string) $bag_result['bag']['id'], 'bag_name' => (string) $bag_result['bag']['name'], 'verdict_code' => (string) $leg['verdict_code'], 'size_status' => (string) $leg['size_status'], 'weight_status' => (string) $leg['weight_status'], 'booking_status' => (string) $leg['booking_status'], 'pressure_percent' => (int) $leg['pressure_percent']];
            }
            $out[] = $row;
        }
        return $out;
    }

    private static function public_notices(array $bags, array $bag_results, array $journey): array {
        $out = [];
        if ($journey['ticket_type'] === 'separate_tickets') {
            $out[] = __('Separate tickets may require baggage collection, recheck and a second baggage payment.', 'voyasee-bagfit');
        }
        if ($journey['self_transfer']) {
            $out[] = __('A self-transfer can require entering the country, collecting bags and checking in again.', 'voyasee-bagfit');
        }
        foreach ($bag_results as $bag_result) {
            $out = array_merge($out, (array) ($bag_result['public_notices'] ?? []));
        }
        return array_values(array_unique($out));
    }

    private static function journey_recommendations(string $overall, array $bag_results, array $strictest, array $journey): array {
        $out = [];
        foreach ($bag_results as $bag_result) {
            foreach ((array) ($bag_result['recommendations'] ?? []) as $rec) {
                $out[] = $rec;
            }
        }
        if (count($bag_results) > 1) {
            $out[] = __('Check every bag separately at home; one passing bag does not make the other bags compliant.', 'voyasee-bagfit');
        }
        if ($journey['ticket_type'] === 'separate_tickets') {
            $out[] = __('Allow enough connection time to collect and recheck baggage between separate tickets.', 'voyasee-bagfit');
        }
        if ('FITS' === $overall) {
            $out[] = __('Keep the packed dimensions and weight at or below the measured values, then confirm the airline page close to departure.', 'voyasee-bagfit');
        }
        return array_slice(array_values(array_unique($out)), 0, 8);
    }

    private static function bag_recommendations(string $overall, array $bag, array $strictest): array {
        $out = [];
        if ('DOES_NOT_FIT' === $overall) {
            foreach ((array) ($strictest['failed_checks'] ?? []) as $failed) {
                $type = (string) ($failed['type'] ?? '');
                if ('weight' === $type) {
                    $out[] = sprintf(__('Remove at least %s kg from %s.', 'voyasee-bagfit'), self::number(((int) $failed['over_by_g']) / 1000), $bag['name']);
                } elseif ('combined_weight' === $type) {
                    $out[] = sprintf(__('Remove at least %s kg across the cabin bags sharing this allowance.', 'voyasee-bagfit'), self::number(((int) $failed['over_by_g']) / 1000));
                } elseif ('dimension' === $type) {
                    $out[] = sprintf(__('Reduce the failed side of %1$s by at least %2$s cm or use a smaller bag.', 'voyasee-bagfit'), $bag['name'], self::number(((int) $failed['over_by_mm']) / 10));
                } elseif ('linear_dimension' === $type) {
                    $over = ((int) $failed['over_by_mm']) / 10;
                    $out[] = $over >= 20
                        ? sprintf(__('%1$s is substantially larger than the selected checked-bag limit. Use a smaller suitcase or ask about oversized baggage.', 'voyasee-bagfit'), $bag['name'])
                        : sprintf(__('Reduce the combined dimensions of %1$s by at least %2$s cm.', 'voyasee-bagfit'), $bag['name'], self::number($over));
                }
            }
        } elseif ('NOT_INCLUDED' === $overall) {
            $out[] = sprintf(__('%s may fit physically, but the selected allowance does not include it. Add the bag online before airport check-in.', 'voyasee-bagfit'), $bag['name']);
        } elseif ('CHECK_REQUIRED' === $overall) {
            $out[] = sprintf(__('Open the issued booking and enter the exact allowance for %s.', 'voyasee-bagfit'), $bag['name']);
        } elseif ('FITS_WITH_CONDITIONS' === $overall) {
            $out[] = sprintf(__('%s fits the stored physical limit, but one booking or source detail still needs confirmation.', 'voyasee-bagfit'), $bag['name']);
        }
        return array_values(array_unique($out));
    }

    private static function best_option_for_reverse(array $airline, string $type, bool $free_only): ?array {
        $options = is_array($airline['allowance_options'][$type] ?? null) ? $airline['allowance_options'][$type] : [];
        $known = [];
        foreach ($options as $option) {
            if (!is_array($option) || (empty($option['dimensions_mm']) && empty($option['max_linear_mm']))) {
                continue;
            }
            if ($free_only && ('included' !== ($option['inclusion'] ?? ''))) {
                continue;
            }
            $known[] = $option;
        }
        if (!$known && !$free_only) {
            foreach ($options as $option) {
                if (is_array($option) && (!empty($option['dimensions_mm']) || !empty($option['max_linear_mm']))) {
                    $known[] = $option;
                }
            }
        }
        if (!$known) {
            return null;
        }
        usort($known, static function (array $a, array $b): int {
            $ia = 'included' === ($a['inclusion'] ?? '') ? 0 : 1;
            $ib = 'included' === ($b['inclusion'] ?? '') ? 0 : 1;
            if ($ia !== $ib) return $ia <=> $ib;
            $va = !empty($a['dimensions_mm']) ? array_product(array_map('intval', $a['dimensions_mm'])) : 0;
            $vb = !empty($b['dimensions_mm']) ? array_product(array_map('intval', $b['dimensions_mm'])) : 0;
            return $vb <=> $va;
        });
        return $known[0];
    }

    private static function reverse_item(array $airline, ?array $option, string $code, array $failed, string $reason): array {
        return ['airline' => (string) ($airline['name'] ?? ''), 'slug' => (string) ($airline['slug'] ?? ''), 'iata' => (string) ($airline['iata'] ?? ''), 'country' => (string) ($airline['country'] ?? ''), 'label' => (string) ($option['label'] ?? ''), 'dimensions_mm' => is_array($option['dimensions_mm'] ?? null) ? array_map('intval', $option['dimensions_mm']) : null, 'max_weight_g' => !empty($option['max_weight_g']) ? (int) $option['max_weight_g'] : null, 'inclusion' => (string) ($option['inclusion'] ?? 'conditional'), 'verdict_code' => $code, 'failed_checks' => $failed, 'reason' => $reason, 'source_url' => esc_url_raw((string) ($airline['source_url'] ?? '')), 'coverage_tier' => (string) ($airline['coverage_tier'] ?? 'directory')];
    }

    private static function failure_summary(array $failed): string {
        if (!$failed) return __('The stored rule is exceeded.', 'voyasee-bagfit');
        $first = $failed[0];
        if (in_array(($first['type'] ?? ''), ['weight', 'combined_weight'], true)) {
            return sprintf(__('%s kg over the stored weight limit.', 'voyasee-bagfit'), self::number(((int) $first['over_by_g']) / 1000));
        }
        return sprintf(__('%s cm over the stored size limit.', 'voyasee-bagfit'), self::number(((int) $first['over_by_mm']) / 10));
    }

    private static function size_check(array $bag, ?array $limit_dims, ?int $limit_linear): array {
        if ($limit_dims && 3 === count($limit_dims) && min($limit_dims) > 0) {
            $best = null;
            foreach (self::permutations($bag['dimensions_mm']) as $orientation) {
                $overages = []; $ratios = [];
                foreach ($orientation as $i => $dimension) {
                    $limit = max(1, (int) $limit_dims[$i]);
                    $overages[] = max(0, $dimension - $limit);
                    $ratios[] = $dimension / $limit;
                }
                $candidate = ['orientation' => $orientation, 'overages' => $overages, 'total_overage' => array_sum($overages), 'max_ratio' => max($ratios)];
                if (null === $best || $candidate['total_overage'] < $best['total_overage'] || ($candidate['total_overage'] === $best['total_overage'] && $candidate['max_ratio'] < $best['max_ratio'])) {
                    $best = $candidate;
                }
            }
            $failed = [];
            foreach ($best['orientation'] as $i => $dimension) {
                $limit = (int) $limit_dims[$i];
                if ($dimension > $limit) {
                    $failed[] = ['type' => 'dimension', 'axis_index' => $i, 'over_by_mm' => $dimension - $limit, 'bag_mm' => $dimension, 'limit_mm' => $limit];
                }
            }
            return ['status' => $failed ? 'fail' : 'pass', 'failed_checks' => $failed, 'best_orientation_mm' => $best['orientation'], 'pressure_percent' => (int) round($best['max_ratio'] * 100)];
        }
        if ($limit_linear) {
            $ratio = $bag['linear_mm'] / max(1, $limit_linear);
            $failed = $bag['linear_mm'] > $limit_linear ? [['type' => 'linear_dimension', 'over_by_mm' => $bag['linear_mm'] - $limit_linear, 'bag_mm' => $bag['linear_mm'], 'limit_mm' => $limit_linear]] : [];
            return ['status' => $failed ? 'fail' : 'pass', 'failed_checks' => $failed, 'best_orientation_mm' => null, 'pressure_percent' => (int) round($ratio * 100)];
        }
        return ['status' => 'unknown', 'failed_checks' => [], 'best_orientation_mm' => null, 'pressure_percent' => 0];
    }

    private static function weight_check(array $bag, ?int $limit_weight): array {
        if (!$limit_weight) {
            return ['status' => 'unknown', 'failed_checks' => [], 'pressure_percent' => 0];
        }
        $ratio = $bag['weight_g'] / max(1, $limit_weight);
        if ($bag['weight_g'] > $limit_weight) {
            return ['status' => 'fail', 'failed_checks' => [['type' => 'weight', 'over_by_g' => $bag['weight_g'] - $limit_weight, 'bag_g' => $bag['weight_g'], 'limit_g' => $limit_weight]], 'pressure_percent' => (int) round($ratio * 100)];
        }
        return ['status' => 'pass', 'failed_checks' => [], 'pressure_percent' => (int) round($ratio * 100)];
    }

    private static function code_from_status(string $size, string $weight, string $inclusion): string {
        if ('fail' === $size && 'fail' === $weight) return 'FAIL_SIZE_WEIGHT';
        if ('fail' === $size) return 'FAIL_SIZE';
        if ('fail' === $weight) return 'FAIL_WEIGHT';
        if ('not_included' === $inclusion) return 'NOT_INCLUDED';
        if ('unknown' === $size && 'unknown' === $weight) return 'CHECK_REQUIRED';
        if ('unknown' === $size) return 'UNKNOWN_RULE';
        if ('conditional' === $inclusion || 'ticket_specific' === $inclusion || 'unknown' === $weight) return 'PASS_CONDITIONAL';
        return 'PASS';
    }

    private static function booking_status(string $inclusion): string {
        return match ($inclusion) {
            'included' => 'included',
            'not_included' => 'not_included',
            default => 'confirm',
        };
    }

    private static function overall_from_codes(array $codes): string {
        if (array_intersect($codes, ['FAIL_SIZE', 'FAIL_WEIGHT', 'FAIL_SIZE_WEIGHT'])) return 'DOES_NOT_FIT';
        if (in_array('NOT_INCLUDED', $codes, true)) return 'NOT_INCLUDED';
        if (array_intersect($codes, ['CHECK_REQUIRED', 'UNKNOWN_RULE'])) return 'CHECK_REQUIRED';
        if (array_intersect($codes, ['PASS_CONDITIONAL', 'SOURCE_STALE'])) return 'FITS_WITH_CONDITIONS';
        return 'FITS';
    }

    private static function freshness(array $airline): array {
        $verified = (string) ($airline['last_verified'] ?? '');
        $days = max(30, (int) ($airline['data_quality']['recommended_review_days'] ?? 90));
        if (!$verified || !strtotime($verified)) return ['stale' => true, 'age_days' => null, 'recommended_days' => $days];
        $age = (int) floor((time() - strtotime($verified . ' 00:00:00 UTC')) / DAY_IN_SECONDS);
        return ['stale' => $age > $days, 'age_days' => max(0, $age), 'recommended_days' => $days];
    }

    private static function strictest_reason(string $code, array $failed, string $inclusion, int $pressure): string {
        if ($failed) {
            $first = $failed[0];
            if (in_array(($first['type'] ?? ''), ['weight', 'combined_weight'], true)) return __('The selected weight limit is exceeded.', 'voyasee-bagfit');
            return __('The selected size limit is exceeded.', 'voyasee-bagfit');
        }
        if ('not_included' === $inclusion || 'NOT_INCLUDED' === $code) return __('The bag may fit physically but is not included in the selected allowance.', 'voyasee-bagfit');
        if (in_array($code, ['UNKNOWN_RULE', 'CHECK_REQUIRED', 'SOURCE_STALE'], true)) return __('The booking or official rule needs confirmation.', 'voyasee-bagfit');
        return sprintf(__('This flight uses %d%% of the strictest stored limit.', 'voyasee-bagfit'), $pressure);
    }

    private static function permutations(array $dims): array {
        [$a, $b, $c] = array_values($dims);
        return [[$a, $b, $c], [$a, $c, $b], [$b, $a, $c], [$b, $c, $a], [$c, $a, $b], [$c, $b, $a]];
    }

    private static function positive_float(mixed $value): ?float {
        if (null === $value || '' === trim((string) $value)) return null;
        $number = (float) $value;
        return $number > 0 ? $number : null;
    }

    private static function choice(mixed $value, array $allowed, string $default): string {
        $value = sanitize_key((string) $value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function number(float $value): string {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
