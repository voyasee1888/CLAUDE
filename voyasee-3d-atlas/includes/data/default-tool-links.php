<?php
/**
 * Default Voyasee tool/affiliate URLs, sourced verbatim from
 * VOYASEE_TOOLS_AND_AFFILIATES_REGISTRY.md (the standing registry this
 * plugin must never invent URLs outside of). These populate the footer out
 * of the box on a fresh install so it never ships empty -- an admin can
 * still override, correct, or blank out any individual field any time via
 * 3D Atlas -> Settings; a value explicitly saved there (including an
 * intentionally blank one) always wins over the value here.
 *
 * Booking.com uses two region-specific approved-market links per the
 * registry (EU/EEA vs. Asia-Pacific/Middle East) rather than a single
 * generic URL -- both are included since this plugin has no per-visitor
 * geolocation to pick one automatically.
 */

defined('ABSPATH') || exit;

return [
    // Voyasee tools
    'tool_trip_readiness' => 'https://voyasee.com/trip-readiness-checklist/',
    'tool_smart_travel_hub' => 'https://voyasee.com/free-smart-travel-hub/',
    'tool_interactive_map' => 'https://voyasee.com/interactive-travel-map/',
    'tool_travel_month_planner' => 'https://voyasee.com/best-time-to-visit-travel-planner/',
    'tool_trip_budget_calculator' => 'https://voyasee.com/trip-budget-calculator/',
    'tool_destination_quiz' => 'https://voyasee.com/destination-quiz/',
    'tool_destination_comparison' => 'https://voyasee.com/travel-destination-comparison-tool/',
    'tool_smart_packing_list' => 'https://voyasee.com/packing-list-generator/',
    'tool_travel_scam_shield' => 'https://voyasee.com/travel-scam-checker/',
    'tool_jet_lag_planner' => 'https://voyasee.com/jet-lag-recovery-planner/',
    // Affiliate partners
    'affiliate_booking_eu' => 'https://www.dpbolvw.net/click-101719993-11891539',
    'affiliate_booking_apac' => 'https://www.kqzyfj.com/click-101719993-17289006',
    'affiliate_aviasales' => 'https://aviasales.tpm.li/jergleAu',
    'affiliate_kiwi' => 'https://kiwi.tpm.li/tDvkubl3',
    'affiliate_safetywing' => 'https://safetywing.com/?referenceID=26504574&utm_source=26504574&utm_medium=Ambassador',
    'affiliate_visa' => 'https://www.visahq.co.uk/?a_aid=vaff18435',
];
