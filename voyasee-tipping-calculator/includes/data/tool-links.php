<?php
/**
 * Default Voyasee tool + affiliate URLs used to populate the footer out of the
 * box. These are the verified URLs from the Voyasee travel-tools hub (all 16
 * sibling tools). An admin can override, correct, or blank any field from
 * Tipping Calculator -> Settings; a value saved there always wins.
 *
 * Booking.com uses two region-specific approved-market links (EU/EEA vs.
 * Asia-Pacific/Middle East); both are included since this plugin has no
 * per-visitor geolocation to pick one automatically.
 */

defined('ABSPATH') || exit;

return [
    // Voyasee tools (all 16 siblings)
    'tool_travel_passport'        => 'https://voyasee.com/trip-readiness-checklist/',
    'tool_interactive_travel_map' => 'https://voyasee.com/interactive-travel-map/',
    'tool_interactive_world_map'  => 'https://voyasee.com/interactive-world-map/',
    'tool_smart_travel_hub'       => 'https://voyasee.com/free-smart-travel-hub/',
    'tool_trip_budget_calculator' => 'https://voyasee.com/trip-budget-calculator/',
    'tool_packing_list'           => 'https://voyasee.com/packing-list-generator/',
    'tool_carry_on_checker'       => 'https://voyasee.com/carry-on-size-checker/',
    'tool_scam_checker'           => 'https://voyasee.com/travel-scam-checker/',
    'tool_destination_quiz'       => 'https://voyasee.com/destination-quiz/',
    'tool_comparison'             => 'https://voyasee.com/travel-destination-comparison-tool/',
    'tool_month_planner'          => 'https://voyasee.com/best-time-to-visit-travel-planner/',
    'tool_medicine_checker'       => 'https://voyasee.com/medicine-restricted-item-checker/',
    'tool_jet_lag'                => 'https://voyasee.com/jet-lag-recovery-planner/',
    'tool_transit_visa'           => 'https://voyasee.com/transit-visa-layover-risk-checker/',
    'tool_schengen'               => 'https://voyasee.com/schengen-90-180-day-calculator/',
    'tool_best_area'              => 'https://voyasee.com/best-area-to-stay/',
    // Affiliate partners
    'affiliate_booking_eu'   => 'https://www.dpbolvw.net/click-101719993-11891539',
    'affiliate_booking_apac' => 'https://www.kqzyfj.com/click-101719993-17289006',
    'affiliate_aviasales'    => 'https://aviasales.tpm.li/jergleAu',
    'affiliate_kiwi'         => 'https://kiwi.tpm.li/tDvkubl3',
    'affiliate_safetywing'   => 'https://safetywing.com/?referenceID=26504574&utm_source=26504574&utm_medium=Ambassador',
    'affiliate_visa'         => 'https://www.visahq.co.uk/?a_aid=vaff18435',
];
