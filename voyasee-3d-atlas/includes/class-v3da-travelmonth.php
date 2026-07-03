<?php
defined('ABSPATH') || exit;

/**
 * Best-time-to-visit snapshot sourced from a bundled export of Voyasee
 * Travel Month Planner v6.6.0's own dataset (see includes/data/
 * seasonal-appeal.php) -- not a live integration, since no REST/PHP
 * contract for that plugin was available the way Weather Bridge and
 * Country Intelligence's were.
 *
 * Most destinations in that export only have a flat regional-fallback
 * score (no real month-to-month variation yet, per Travel Month Planner's
 * own data), and showing "best time to visit: X" against a flat score
 * would just be fabricated precision. This deliberately returns null for
 * those, the same graceful-degradation standard used everywhere else in
 * this plugin.
 */
final class V3DA_TravelMonth {
    private const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    private const MIN_VARIATION = 10; // appeal-score spread required before a "best month" is meaningful

    private static ?array $data = null;

    private static function data(): array {
        if (null === self::$data) {
            self::$data = require V3DA_DIR . 'includes/data/seasonal-appeal.php';
        }
        return self::$data;
    }

    /**
     * @return array{months:array<int,string>,highlight:?string}|null
     */
    public static function best_time(string $slug): ?array {
        $entry = self::data()[$slug] ?? null;
        if (!$entry || empty($entry['appeal']) || 12 !== count($entry['appeal'])) return null;

        $appeal = $entry['appeal'];
        $max = max($appeal);
        $min = min($appeal);
        if (($max - $min) < self::MIN_VARIATION) return null;

        $months = [];
        foreach ($appeal as $i => $score) {
            if ($score >= $max - 3) $months[] = self::MONTHS[$i];
        }

        return [
            'months' => array_slice($months, 0, 3),
            'highlight' => $entry['highlights'][0] ?? null,
        ];
    }
}
