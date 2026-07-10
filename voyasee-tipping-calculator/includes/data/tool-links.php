<?php
/**
 * Default Voyasee tool + affiliate URLs used to populate the footer out of the
 * box, so a fresh install never ships an empty footer. These are the same
 * verified links used across the Voyasee tool family (never invented here). An
 * admin can override, correct, or blank any field from Tipping Calculator ->
 * Settings; a value saved there always wins over the value here.
 *
 * Booking.com uses two region-specific approved-market links (EU/EEA vs.
 * Asia-Pacific/Middle East); both are included since this plugin has no
 * per-visitor geolocation to pick one automatically.
 */

defined('ABSPATH') || exit;

return [
    // Voyasee tools
    'tool_interactive_world_map'   => 'https://voyasee.com/interactive-world-map/',
    'tool_best_area_to_stay'       => 'https://voyasee.com/best-area-to-stay/',
    'tool_trip_budget_calculator'  => 'https://voyasee.com/trip-budget-calculator/',
    'tool_smart_travel_hub'        => 'https://voyasee.com/free-smart-travel-hub/',
    'tool_travel_month_planner'    => 'https://voyasee.com/best-time-to-visit-travel-planner/',
    'tool_packing_list'            => 'https://voyasee.com/packing-list-generator/',
    'tool_trip_readiness'          => 'https://voyasee.com/trip-readiness-checklist/',
    'tool_scam_checker'            => 'https://voyasee.com/travel-scam-checker/',
    // Affiliate partners
    'affiliate_booking_eu'   => 'https://www.dpbolvw.net/click-101719993-11891539',
    'affiliate_booking_apac' => 'https://www.kqzyfj.com/click-101719993-17289006',
    'affiliate_aviasales'    => 'https://aviasales.tpm.li/jergleAu',
    'affiliate_kiwi'         => 'https://kiwi.tpm.li/tDvkubl3',
    'affiliate_safetywing'   => 'https://safetywing.com/?referenceID=26504574&utm_source=26504574&utm_medium=Ambassador',
    'affiliate_visa'         => 'https://www.visahq.co.uk/?a_aid=vaff18435',
];
