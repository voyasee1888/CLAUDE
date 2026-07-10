<?php
/**
 * Voyasee Tipping Calculator — tipping intelligence dataset.
 *
 * This is the plugin's OWN curated intelligence. No third-party API (paid or
 * free) is ever called at runtime; every figure here is a compiled, reviewed
 * guideline drawn from widely-documented tipping customs (government/tourism
 * etiquette guidance, established travel references and the public record of
 * tipping norms), normalised into a single honest model.
 *
 * MODEL
 * -----
 * Every country belongs to one of five "tipping cultures" (clusters). The
 * cluster decides the default behaviour AND the message; a country may then
 * override individual services, add flags, and carry its own one-line note.
 *
 * Percentage services (restaurant, cafe_bar, taxi, tour_guide, spa_salon,
 * food_delivery) are stored as [low, standard, high] percent bands. Because
 * they are a percentage of the bill the user enters, they are exact in any
 * currency.
 *
 * Flat per-unit services (hotel_housekeeping per night, hotel_porter per bag)
 * are stored as [low, standard, high] anchors in USD. At calculation time the
 * engine converts the anchor into the destination's local currency using the
 * bundled reference-rate table and rounds it to a sensible local amount. These
 * are always presented as a "guideline", never as false precision.
 *
 * HONESTY
 * -------
 * `confidence` is 'high' where the norm is well established and widely agreed,
 * or 'medium' where guidance is thinner and the country leans on its regional
 * cluster default. The UI surfaces 'medium' as general guidance rather than
 * pretending to a precision the data does not support. No entry is invented to
 * look complete.
 *
 * @return array{clusters:array,services:array,countries:array}
 */

defined('ABSPATH') || exit;

return [

    /* ---------------------------------------------------------------------
     * Service catalogue
     * ------------------------------------------------------------------- */
    'services' => [
        'restaurant'         => ['label' => 'Restaurant (sit-down)', 'type' => 'percent',    'icon' => "\u{1F37D}"],
        'cafe_bar'           => ['label' => 'Cafe / Bar',            'type' => 'percent',    'icon' => "\u{2615}"],
        'taxi'               => ['label' => 'Taxi / Rideshare',      'type' => 'percent',    'icon' => "\u{1F695}"],
        'food_delivery'      => ['label' => 'Food delivery',         'type' => 'percent',    'icon' => "\u{1F6F5}"],
        'tour_guide'         => ['label' => 'Tour guide / Driver',   'type' => 'percent',    'icon' => "\u{1F9ED}"],
        'spa_salon'          => ['label' => 'Spa / Salon / Barber',  'type' => 'percent',    'icon' => "\u{2702}"],
        'hotel_housekeeping' => ['label' => 'Hotel housekeeping',    'type' => 'flat_night', 'unit' => 'night', 'icon' => "\u{1F6CE}"],
        'hotel_porter'       => ['label' => 'Hotel porter / Bellhop','type' => 'flat_bag',   'unit' => 'bag',   'icon' => "\u{1F9F3}"],
    ],

    /* ---------------------------------------------------------------------
     * Culture clusters — defaults inherited by every country in the cluster.
     * Percent services: [low, standard, high] as %.
     * Flat services:    [low, standard, high] as USD anchors (converted later).
     * ------------------------------------------------------------------- */
    'clusters' => [
        1 => [
            'key'     => 'expected',
            'label'   => 'Tipping expected',
            'verdict' => 'Tipping is expected here',
            'note'    => 'Service staff often rely on tips as a real part of their income, so a tip is expected rather than optional.',
            'flags'   => [],
            'services' => [
                'restaurant'         => [15, 18, 20],
                'cafe_bar'           => [10, 12, 15],
                'taxi'               => [10, 15, 15],
                'food_delivery'      => [10, 15, 20],
                'tour_guide'         => [10, 15, 20],
                'spa_salon'          => [15, 18, 20],
                'hotel_housekeeping' => [2, 3, 5],
                'hotel_porter'       => [1, 2, 3],
            ],
        ],
        2 => [
            'key'     => 'appreciated',
            'label'   => 'Appreciated, not required',
            'verdict' => 'Tipping is appreciated but modest',
            'note'    => 'Rounding up or leaving around 5-10% is normal and welcome, but it is a genuine choice, not an obligation.',
            'flags'   => [],
            'services' => [
                'restaurant'         => [5, 10, 10],
                'cafe_bar'           => [0, 5, 10],
                'taxi'               => [0, 5, 10],
                'food_delivery'      => [0, 5, 10],
                'tour_guide'         => [5, 10, 10],
                'spa_salon'          => [5, 10, 10],
                'hotel_housekeeping' => [1, 2, 3],
                'hotel_porter'       => [1, 1, 2],
            ],
        ],
        3 => [
            'key'     => 'service_included',
            'label'   => 'Service usually included',
            'verdict' => 'A service charge is usually already added',
            'note'    => 'A service charge is commonly added to the bill, so an extra tip is optional. Check the bill first so you do not tip twice.',
            'flags'   => ['service_charge_common' => true],
            'services' => [
                'restaurant'         => [0, 5, 10],
                'cafe_bar'           => [0, 0, 5],
                'taxi'               => [0, 5, 10],
                'food_delivery'      => [0, 5, 10],
                'tour_guide'         => [0, 5, 10],
                'spa_salon'          => [0, 5, 10],
                'hotel_housekeeping' => [1, 2, 3],
                'hotel_porter'       => [1, 1, 2],
            ],
        ],
        4 => [
            'key'     => 'not_customary',
            'label'   => 'Tipping not customary',
            'verdict' => 'Tipping is not expected here',
            'note'    => 'Tipping is not part of the local culture and is not expected. Good service is simply standard, and staff are paid accordingly.',
            'flags'   => ['not_customary' => true],
            'services' => [
                'restaurant'         => [0, 0, 0],
                'cafe_bar'           => [0, 0, 0],
                'taxi'               => [0, 0, 0],
                'food_delivery'      => [0, 0, 0],
                'tour_guide'         => [0, 0, 0],
                'spa_salon'          => [0, 0, 0],
                'hotel_housekeeping' => [0, 0, 0],
                'hotel_porter'       => [0, 0, 0],
            ],
        ],
        5 => [
            'key'     => 'baksheesh',
            'label'   => 'Small tips customary (baksheesh)',
            'verdict' => 'Small tips are customary across many services',
            'note'    => 'Small tips (baksheesh) are common across many everyday services, so carry small change. A service charge may also be added to restaurant bills.',
            'flags'   => ['cash_preferred' => true],
            'services' => [
                'restaurant'         => [10, 10, 15],
                'cafe_bar'           => [5, 10, 10],
                'taxi'               => [0, 5, 10],
                'food_delivery'      => [5, 10, 10],
                'tour_guide'         => [10, 10, 15],
                'spa_salon'          => [10, 10, 10],
                'hotel_housekeeping' => [1, 2, 3],
                'hotel_porter'       => [1, 1, 2],
            ],
        ],
    ],

    /* ---------------------------------------------------------------------
     * Countries & territories. Keyed by ISO 3166-1 alpha-2.
     *   name, region, currency (ISO 4217), cluster (1-5), confidence,
     *   flags (merged over cluster flags), overrides (per-service), note.
     * ------------------------------------------------------------------- */
    'countries' => [

        /* ---- North America ---- */
        'US' => ['name' => 'United States', 'region' => 'North America', 'currency' => 'USD', 'cluster' => 1, 'confidence' => 'high', 'flags' => ['tip_pretax_common' => true], 'note' => 'Tips make up most of a server\'s take-home pay. 18-20% is standard at sit-down restaurants, and tipping on the pre-tax total is common.'],
        'CA' => ['name' => 'Canada', 'region' => 'North America', 'currency' => 'CAD', 'cluster' => 1, 'confidence' => 'high', 'note' => 'Tipping works much like the US - 15-20% at restaurants is standard, and card machines will usually prompt you.'],
        'MX' => ['name' => 'Mexico', 'region' => 'North America', 'currency' => 'MXN', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 15, 15], 'taxi' => [0, 0, 5]], 'note' => 'Tipping ("propina") is customary - around 10-15% at restaurants, a little more in tourist areas. Taxi drivers are not usually tipped.'],
        'GL' => ['name' => 'Greenland', 'region' => 'North America', 'currency' => 'DKK', 'cluster' => 4, 'confidence' => 'medium'],

        /* ---- Central America ---- */
        'GT' => ['name' => 'Guatemala', 'region' => 'Central America', 'currency' => 'GTQ', 'cluster' => 2, 'confidence' => 'medium'],
        'BZ' => ['name' => 'Belize', 'region' => 'Central America', 'currency' => 'BZD', 'cluster' => 2, 'confidence' => 'medium'],
        'SV' => ['name' => 'El Salvador', 'region' => 'Central America', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium'],
        'HN' => ['name' => 'Honduras', 'region' => 'Central America', 'currency' => 'HNL', 'cluster' => 2, 'confidence' => 'medium'],
        'NI' => ['name' => 'Nicaragua', 'region' => 'Central America', 'currency' => 'NIO', 'cluster' => 2, 'confidence' => 'medium'],
        'CR' => ['name' => 'Costa Rica', 'region' => 'Central America', 'currency' => 'CRC', 'cluster' => 3, 'confidence' => 'high', 'note' => 'By law a 10% service charge and tax are already included in restaurant bills, so an extra tip is optional - rounding up is a nice touch.'],
        'PA' => ['name' => 'Panama', 'region' => 'Central America', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Around 10% at restaurants is customary if a service charge is not already on the bill.'],

        /* ---- Caribbean ---- */
        'CU' => ['name' => 'Cuba', 'region' => 'Caribbean', 'currency' => 'CUP', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Tipping in cash (often preferred in convertible currency) is appreciated - around 10% at restaurants.'],
        'DO' => ['name' => 'Dominican Republic', 'region' => 'Caribbean', 'currency' => 'DOP', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Restaurants add a 10% service charge plus tax by law; leaving an extra 5-10% for good service is common.'],
        'JM' => ['name' => 'Jamaica', 'region' => 'Caribbean', 'currency' => 'JMD', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Around 10-15% at restaurants, unless a service charge is already added.'],
        'HT' => ['name' => 'Haiti', 'region' => 'Caribbean', 'currency' => 'HTG', 'cluster' => 2, 'confidence' => 'medium'],
        'TT' => ['name' => 'Trinidad & Tobago', 'region' => 'Caribbean', 'currency' => 'TTD', 'cluster' => 2, 'confidence' => 'medium'],
        'BS' => ['name' => 'Bahamas', 'region' => 'Caribbean', 'currency' => 'BSD', 'cluster' => 3, 'confidence' => 'high', 'note' => 'A 15% "gratuity" is usually added to restaurant and bar bills automatically - check before adding more.'],
        'BB' => ['name' => 'Barbados', 'region' => 'Caribbean', 'currency' => 'BBD', 'cluster' => 3, 'confidence' => 'medium', 'note' => 'A 10% service charge plus VAT is often added; extra tipping is optional.'],
        'PR' => ['name' => 'Puerto Rico', 'region' => 'Caribbean', 'currency' => 'USD', 'cluster' => 1, 'confidence' => 'high', 'note' => 'US-style tipping applies - 15-20% at restaurants is expected.'],
        'AW' => ['name' => 'Aruba', 'region' => 'Caribbean', 'currency' => 'AWG', 'cluster' => 3, 'confidence' => 'medium'],
        'KY' => ['name' => 'Cayman Islands', 'region' => 'Caribbean', 'currency' => 'KYD', 'cluster' => 3, 'confidence' => 'medium'],
        'TC' => ['name' => 'Turks & Caicos', 'region' => 'Caribbean', 'currency' => 'USD', 'cluster' => 3, 'confidence' => 'medium'],
        'AG' => ['name' => 'Antigua & Barbuda', 'region' => 'Caribbean', 'currency' => 'XCD', 'cluster' => 3, 'confidence' => 'medium'],
        'LC' => ['name' => 'Saint Lucia', 'region' => 'Caribbean', 'currency' => 'XCD', 'cluster' => 3, 'confidence' => 'medium'],
        'GD' => ['name' => 'Grenada', 'region' => 'Caribbean', 'currency' => 'XCD', 'cluster' => 3, 'confidence' => 'medium'],
        'VC' => ['name' => 'Saint Vincent & the Grenadines', 'region' => 'Caribbean', 'currency' => 'XCD', 'cluster' => 3, 'confidence' => 'medium'],
        'DM' => ['name' => 'Dominica', 'region' => 'Caribbean', 'currency' => 'XCD', 'cluster' => 3, 'confidence' => 'medium'],
        'KN' => ['name' => 'Saint Kitts & Nevis', 'region' => 'Caribbean', 'currency' => 'XCD', 'cluster' => 3, 'confidence' => 'medium'],
        'CW' => ['name' => 'Curacao', 'region' => 'Caribbean', 'currency' => 'ANG', 'cluster' => 3, 'confidence' => 'medium'],
        'SX' => ['name' => 'Sint Maarten', 'region' => 'Caribbean', 'currency' => 'ANG', 'cluster' => 3, 'confidence' => 'medium'],

        /* ---- South America ---- */
        'BR' => ['name' => 'Brazil', 'region' => 'South America', 'currency' => 'BRL', 'cluster' => 3, 'confidence' => 'high', 'note' => 'A 10% service charge ("servico") is almost always added to the bill and is the customary tip - extra is optional.'],
        'AR' => ['name' => 'Argentina', 'region' => 'South America', 'currency' => 'ARS', 'cluster' => 2, 'confidence' => 'high', 'flags' => ['cash_preferred' => true], 'overrides' => ['restaurant' => [10, 10, 10]], 'note' => 'Around 10% in cash is customary at restaurants, as tips often cannot be added to a card payment.'],
        'CL' => ['name' => 'Chile', 'region' => 'South America', 'currency' => 'CLP', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 10]], 'note' => 'A 10% "propina" is usually suggested on the bill; you can accept, adjust or decline it.'],
        'CO' => ['name' => 'Colombia', 'region' => 'South America', 'currency' => 'COP', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 10]], 'note' => 'Restaurants often ask whether to add a 10% "propina voluntaria" - it is genuinely voluntary and you may say no.'],
        'PE' => ['name' => 'Peru', 'region' => 'South America', 'currency' => 'PEN', 'cluster' => 2, 'confidence' => 'medium'],
        'EC' => ['name' => 'Ecuador', 'region' => 'South America', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Nicer restaurants add ~10% service plus tax; a little extra for good service is appreciated.'],
        'BO' => ['name' => 'Bolivia', 'region' => 'South America', 'currency' => 'BOB', 'cluster' => 2, 'confidence' => 'medium'],
        'PY' => ['name' => 'Paraguay', 'region' => 'South America', 'currency' => 'PYG', 'cluster' => 2, 'confidence' => 'medium'],
        'UY' => ['name' => 'Uruguay', 'region' => 'South America', 'currency' => 'UYU', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'VE' => ['name' => 'Venezuela', 'region' => 'South America', 'currency' => 'VES', 'cluster' => 2, 'confidence' => 'medium'],
        'GY' => ['name' => 'Guyana', 'region' => 'South America', 'currency' => 'GYD', 'cluster' => 2, 'confidence' => 'medium'],
        'SR' => ['name' => 'Suriname', 'region' => 'South America', 'currency' => 'SRD', 'cluster' => 2, 'confidence' => 'medium'],

        /* ---- Europe ---- */
        'GB' => ['name' => 'United Kingdom', 'region' => 'Europe', 'currency' => 'GBP', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 12, 15], 'taxi' => [0, 10, 10]], 'note' => 'Around 10-15% at restaurants - but check the bill, as a "discretionary service charge" is often already added, especially in London.'],
        'IE' => ['name' => 'Ireland', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 12, 15]], 'note' => 'Around 10-15% at restaurants when service is not already included.'],
        'FR' => ['name' => 'France', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Service is included by law ("service compris"). Locals simply round up or leave a euro or two for good service.'],
        'DE' => ['name' => 'Germany', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]], 'note' => 'Round up or add ~5-10%, telling the server the total as you pay rather than leaving cash on the table.'],
        'IT' => ['name' => 'Italy', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 3, 'confidence' => 'high', 'note' => 'A "coperto" (cover charge) is common and is not a tip. Extra tipping is modest and entirely optional.'],
        'ES' => ['name' => 'Spain', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]], 'note' => 'Tipping is modest - round up or leave ~5-10% for good service.'],
        'PT' => ['name' => 'Portugal', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]]],
        'NL' => ['name' => 'Netherlands', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]], 'note' => 'Service is included; rounding up or ~5-10% for good service is normal.'],
        'BE' => ['name' => 'Belgium', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [0, 5, 10]], 'note' => 'Service is included in prices; rounding up is a nice gesture rather than an expectation.'],
        'CH' => ['name' => 'Switzerland', 'region' => 'Europe', 'currency' => 'CHF', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Service is included by law; locals simply round up to the nearest franc or two for good service.'],
        'AT' => ['name' => 'Austria', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]]],
        'GR' => ['name' => 'Greece', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]]],
        'SE' => ['name' => 'Sweden', 'region' => 'Europe', 'currency' => 'SEK', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [0, 5, 10]], 'note' => 'Not expected - service is included and wages are fair. Rounding up for good service is appreciated.'],
        'NO' => ['name' => 'Norway', 'region' => 'Europe', 'currency' => 'NOK', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [0, 5, 10]], 'note' => 'Not expected; rounding up or ~5-10% for good service is a genuine bonus.'],
        'DK' => ['name' => 'Denmark', 'region' => 'Europe', 'currency' => 'DKK', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [0, 5, 10]], 'note' => 'Service is included by law; rounding up is a nice gesture, not an obligation.'],
        'FI' => ['name' => 'Finland', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [0, 5, 10]], 'note' => 'Not expected; service is included and rounding up is optional.'],
        'IS' => ['name' => 'Iceland', 'region' => 'Europe', 'currency' => 'ISK', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [0, 5, 10]], 'note' => 'Not expected - wages are fair and service is included in prices.'],
        'PL' => ['name' => 'Poland', 'region' => 'Europe', 'currency' => 'PLN', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 15]], 'note' => 'Around 10% is normal. Say "thank you" only when you mean "keep the change", as saying it while paying can be taken that way.'],
        'CZ' => ['name' => 'Czechia', 'region' => 'Europe', 'currency' => 'CZK', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'HU' => ['name' => 'Hungary', 'region' => 'Europe', 'currency' => 'HUF', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 15]], 'note' => 'Around 10-15% is normal; check whether a service charge was already added.'],
        'RO' => ['name' => 'Romania', 'region' => 'Europe', 'currency' => 'RON', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'BG' => ['name' => 'Bulgaria', 'region' => 'Europe', 'currency' => 'BGN', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'HR' => ['name' => 'Croatia', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'RS' => ['name' => 'Serbia', 'region' => 'Europe', 'currency' => 'RSD', 'cluster' => 2, 'confidence' => 'medium'],
        'SK' => ['name' => 'Slovakia', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'SI' => ['name' => 'Slovenia', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'UA' => ['name' => 'Ukraine', 'region' => 'Europe', 'currency' => 'UAH', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 15]]],
        'RU' => ['name' => 'Russia', 'region' => 'Europe', 'currency' => 'RUB', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 15]]],
        'BY' => ['name' => 'Belarus', 'region' => 'Europe', 'currency' => 'BYN', 'cluster' => 2, 'confidence' => 'medium'],
        'LT' => ['name' => 'Lithuania', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'LV' => ['name' => 'Latvia', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'EE' => ['name' => 'Estonia', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'LU' => ['name' => 'Luxembourg', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'MT' => ['name' => 'Malta', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'CY' => ['name' => 'Cyprus', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'AL' => ['name' => 'Albania', 'region' => 'Europe', 'currency' => 'ALL', 'cluster' => 2, 'confidence' => 'medium'],
        'MK' => ['name' => 'North Macedonia', 'region' => 'Europe', 'currency' => 'MKD', 'cluster' => 2, 'confidence' => 'medium'],
        'BA' => ['name' => 'Bosnia & Herzegovina', 'region' => 'Europe', 'currency' => 'BAM', 'cluster' => 2, 'confidence' => 'medium'],
        'ME' => ['name' => 'Montenegro', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'XK' => ['name' => 'Kosovo', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'MD' => ['name' => 'Moldova', 'region' => 'Europe', 'currency' => 'MDL', 'cluster' => 2, 'confidence' => 'medium'],
        'AD' => ['name' => 'Andorra', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 2, 'confidence' => 'medium'],
        'MC' => ['name' => 'Monaco', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 3, 'confidence' => 'medium'],
        'SM' => ['name' => 'San Marino', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 3, 'confidence' => 'medium'],
        'LI' => ['name' => 'Liechtenstein', 'region' => 'Europe', 'currency' => 'CHF', 'cluster' => 3, 'confidence' => 'medium'],
        'VA' => ['name' => 'Vatican City', 'region' => 'Europe', 'currency' => 'EUR', 'cluster' => 3, 'confidence' => 'medium'],

        /* ---- Middle East ---- */
        'AE' => ['name' => 'United Arab Emirates', 'region' => 'Middle East', 'currency' => 'AED', 'cluster' => 5, 'confidence' => 'high', 'note' => 'A 10% service charge is often added to bills; an extra 10-15% is appreciated for good service, especially in cash.'],
        'SA' => ['name' => 'Saudi Arabia', 'region' => 'Middle East', 'currency' => 'SAR', 'cluster' => 5, 'confidence' => 'high', 'note' => 'A service charge is commonly added; a small extra tip is appreciated for good service.'],
        'QA' => ['name' => 'Qatar', 'region' => 'Middle East', 'currency' => 'QAR', 'cluster' => 5, 'confidence' => 'medium', 'note' => 'A 10% service charge is often added; extra tipping is optional and appreciated.'],
        'KW' => ['name' => 'Kuwait', 'region' => 'Middle East', 'currency' => 'KWD', 'cluster' => 5, 'confidence' => 'medium'],
        'BH' => ['name' => 'Bahrain', 'region' => 'Middle East', 'currency' => 'BHD', 'cluster' => 5, 'confidence' => 'medium'],
        'OM' => ['name' => 'Oman', 'region' => 'Middle East', 'currency' => 'OMR', 'cluster' => 5, 'confidence' => 'medium'],
        'JO' => ['name' => 'Jordan', 'region' => 'Middle East', 'currency' => 'JOD', 'cluster' => 5, 'confidence' => 'high', 'note' => 'A service charge is often added to bills; small extra tips (baksheesh) are customary across services.'],
        'LB' => ['name' => 'Lebanon', 'region' => 'Middle East', 'currency' => 'LBP', 'cluster' => 5, 'confidence' => 'medium', 'flags' => ['cash_preferred' => true]],
        'IL' => ['name' => 'Israel', 'region' => 'Middle East', 'currency' => 'ILS', 'cluster' => 2, 'confidence' => 'high', 'flags' => ['cash_preferred' => true], 'overrides' => ['restaurant' => [10, 12, 15]], 'note' => 'Service is usually NOT included - around 12% is expected at restaurants, ideally in cash handed to the server.'],
        'TR' => ['name' => 'Turkey', 'region' => 'Middle East', 'currency' => 'TRY', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]], 'note' => 'Around 5-10% at restaurants; check whether a service charge ("servis") was already added.'],
        'IQ' => ['name' => 'Iraq', 'region' => 'Middle East', 'currency' => 'IQD', 'cluster' => 5, 'confidence' => 'medium'],
        'IR' => ['name' => 'Iran', 'region' => 'Middle East', 'currency' => 'IRR', 'cluster' => 5, 'confidence' => 'medium'],
        'YE' => ['name' => 'Yemen', 'region' => 'Middle East', 'currency' => 'YER', 'cluster' => 5, 'confidence' => 'medium'],
        'SY' => ['name' => 'Syria', 'region' => 'Middle East', 'currency' => 'SYP', 'cluster' => 5, 'confidence' => 'medium'],
        'PS' => ['name' => 'Palestine', 'region' => 'Middle East', 'currency' => 'ILS', 'cluster' => 5, 'confidence' => 'medium'],

        /* ---- Asia ---- */
        'JP' => ['name' => 'Japan', 'region' => 'Asia', 'currency' => 'JPY', 'cluster' => 4, 'confidence' => 'high', 'flags' => ['tipping_offensive' => true], 'note' => 'Tipping is not customary and can cause confusion or even offence - excellent service is simply expected. Do not leave a tip; a sincere thank you is the right gesture.'],
        'KR' => ['name' => 'South Korea', 'region' => 'Asia', 'currency' => 'KRW', 'cluster' => 4, 'confidence' => 'high', 'note' => 'Tipping is not part of the culture and is not expected; staff may even chase after you to return the extra.'],
        'CN' => ['name' => 'China', 'region' => 'Asia', 'currency' => 'CNY', 'cluster' => 4, 'confidence' => 'high', 'note' => 'Tipping is not customary in most of mainland China, though upscale hotels and private tour guides serving tourists are an increasing exception.'],
        'HK' => ['name' => 'Hong Kong', 'region' => 'Asia', 'currency' => 'HKD', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Restaurants usually add a 10% service charge; leaving the small coins in change on top is common.'],
        'TW' => ['name' => 'Taiwan', 'region' => 'Asia', 'currency' => 'TWD', 'cluster' => 4, 'confidence' => 'high', 'note' => 'Tipping is not customary; restaurants and hotels often add a 10% service charge instead.'],
        'MO' => ['name' => 'Macau', 'region' => 'Asia', 'currency' => 'MOP', 'cluster' => 3, 'confidence' => 'medium'],
        'TH' => ['name' => 'Thailand', 'region' => 'Asia', 'currency' => 'THB', 'cluster' => 2, 'confidence' => 'high', 'note' => 'Tipping is appreciated, not required - round up, or ~10% at nicer restaurants. Upscale places may add a service charge.'],
        'VN' => ['name' => 'Vietnam', 'region' => 'Asia', 'currency' => 'VND', 'cluster' => 2, 'confidence' => 'high', 'note' => 'Not traditionally expected but increasingly appreciated in tourist areas - rounding up or a small note is welcome.'],
        'KH' => ['name' => 'Cambodia', 'region' => 'Asia', 'currency' => 'KHR', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Appreciated but not obligatory; small tips go a long way and are common in tourist areas.'],
        'LA' => ['name' => 'Laos', 'region' => 'Asia', 'currency' => 'LAK', 'cluster' => 2, 'confidence' => 'medium'],
        'MM' => ['name' => 'Myanmar', 'region' => 'Asia', 'currency' => 'MMK', 'cluster' => 2, 'confidence' => 'medium'],
        'MY' => ['name' => 'Malaysia', 'region' => 'Asia', 'currency' => 'MYR', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Many restaurants add a 10% service charge; beyond that, tipping is not generally expected.'],
        'SG' => ['name' => 'Singapore', 'region' => 'Asia', 'currency' => 'SGD', 'cluster' => 3, 'confidence' => 'high', 'note' => 'A 10% service charge is standard on bills; tipping on top is not expected and is officially discouraged in some venues.'],
        'ID' => ['name' => 'Indonesia', 'region' => 'Asia', 'currency' => 'IDR', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Upmarket restaurants and hotels usually add a 10% service charge plus tax; small tips elsewhere are appreciated.'],
        'PH' => ['name' => 'Philippines', 'region' => 'Asia', 'currency' => 'PHP', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 10]], 'note' => 'A 10% service charge is common; an extra 5-10% for good service is appreciated.'],
        'IN' => ['name' => 'India', 'region' => 'Asia', 'currency' => 'INR', 'cluster' => 5, 'confidence' => 'high', 'overrides' => ['restaurant' => [5, 10, 10]], 'note' => 'Around 5-10% at restaurants - but check for a "service charge", which is optional in India and can be removed on request.'],
        'NP' => ['name' => 'Nepal', 'region' => 'Asia', 'currency' => 'NPR', 'cluster' => 5, 'confidence' => 'medium', 'note' => 'A 10% service charge is often added; tipping trekking guides and porters is an established, important custom.'],
        'LK' => ['name' => 'Sri Lanka', 'region' => 'Asia', 'currency' => 'LKR', 'cluster' => 5, 'confidence' => 'medium', 'note' => 'A 10% service charge is common; small tips across services are customary.'],
        'BD' => ['name' => 'Bangladesh', 'region' => 'Asia', 'currency' => 'BDT', 'cluster' => 5, 'confidence' => 'medium'],
        'PK' => ['name' => 'Pakistan', 'region' => 'Asia', 'currency' => 'PKR', 'cluster' => 5, 'confidence' => 'medium'],
        'BT' => ['name' => 'Bhutan', 'region' => 'Asia', 'currency' => 'BTN', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Tips are increasingly common on guided tours; tipping your guide and driver is a kind gesture at the end of a trip.'],
        'MV' => ['name' => 'Maldives', 'region' => 'Asia', 'currency' => 'MVR', 'cluster' => 3, 'confidence' => 'high', 'note' => 'Resorts usually add a 10% service charge plus a green tax; small tips for personal service (housekeeping, dive guides) are appreciated.'],
        'MN' => ['name' => 'Mongolia', 'region' => 'Asia', 'currency' => 'MNT', 'cluster' => 2, 'confidence' => 'medium'],
        'KZ' => ['name' => 'Kazakhstan', 'region' => 'Asia', 'currency' => 'KZT', 'cluster' => 2, 'confidence' => 'medium'],
        'UZ' => ['name' => 'Uzbekistan', 'region' => 'Asia', 'currency' => 'UZS', 'cluster' => 2, 'confidence' => 'medium'],
        'KG' => ['name' => 'Kyrgyzstan', 'region' => 'Asia', 'currency' => 'KGS', 'cluster' => 2, 'confidence' => 'medium'],
        'TJ' => ['name' => 'Tajikistan', 'region' => 'Asia', 'currency' => 'TJS', 'cluster' => 2, 'confidence' => 'medium'],
        'TM' => ['name' => 'Turkmenistan', 'region' => 'Asia', 'currency' => 'TMT', 'cluster' => 2, 'confidence' => 'medium'],
        'AF' => ['name' => 'Afghanistan', 'region' => 'Asia', 'currency' => 'AFN', 'cluster' => 5, 'confidence' => 'medium'],
        'AM' => ['name' => 'Armenia', 'region' => 'Asia', 'currency' => 'AMD', 'cluster' => 2, 'confidence' => 'medium'],
        'AZ' => ['name' => 'Azerbaijan', 'region' => 'Asia', 'currency' => 'AZN', 'cluster' => 2, 'confidence' => 'medium'],
        'GE' => ['name' => 'Georgia', 'region' => 'Asia', 'currency' => 'GEL', 'cluster' => 2, 'confidence' => 'medium', 'overrides' => ['restaurant' => [10, 10, 10]]],
        'BN' => ['name' => 'Brunei', 'region' => 'Asia', 'currency' => 'BND', 'cluster' => 4, 'confidence' => 'medium'],
        'TL' => ['name' => 'Timor-Leste', 'region' => 'Asia', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium'],

        /* ---- Africa ---- */
        'ZA' => ['name' => 'South Africa', 'region' => 'Africa', 'currency' => 'ZAR', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 12, 15]], 'note' => 'Tipping is an important part of income here - 10-15% at restaurants is expected, plus a few rand for petrol attendants and car guards.'],
        'EG' => ['name' => 'Egypt', 'region' => 'Africa', 'currency' => 'EGP', 'cluster' => 5, 'confidence' => 'high', 'note' => 'Baksheesh (small tipping) is expected almost everywhere - carry small change. Restaurants often add ~12% service, and an extra 5-10% is customary.'],
        'MA' => ['name' => 'Morocco', 'region' => 'Africa', 'currency' => 'MAD', 'cluster' => 5, 'confidence' => 'high', 'note' => 'Small tips are customary across many services, so carry coins. Around 10% at restaurants if service is not already included.'],
        'TN' => ['name' => 'Tunisia', 'region' => 'Africa', 'currency' => 'TND', 'cluster' => 5, 'confidence' => 'medium'],
        'DZ' => ['name' => 'Algeria', 'region' => 'Africa', 'currency' => 'DZD', 'cluster' => 5, 'confidence' => 'medium'],
        'KE' => ['name' => 'Kenya', 'region' => 'Africa', 'currency' => 'KES', 'cluster' => 2, 'confidence' => 'high', 'overrides' => ['restaurant' => [10, 10, 10]], 'note' => 'Around 10% at restaurants if no service charge; tipping safari guides and drivers at the end of a trip is a strong custom.'],
        'TZ' => ['name' => 'Tanzania', 'region' => 'Africa', 'currency' => 'TZS', 'cluster' => 2, 'confidence' => 'high', 'note' => 'Around 10% at restaurants; tipping safari guides, cooks and porters (e.g. on Kilimanjaro) is an established and expected custom.'],
        'NG' => ['name' => 'Nigeria', 'region' => 'Africa', 'currency' => 'NGN', 'cluster' => 2, 'confidence' => 'medium'],
        'GH' => ['name' => 'Ghana', 'region' => 'Africa', 'currency' => 'GHS', 'cluster' => 2, 'confidence' => 'medium'],
        'ET' => ['name' => 'Ethiopia', 'region' => 'Africa', 'currency' => 'ETB', 'cluster' => 2, 'confidence' => 'medium'],
        'UG' => ['name' => 'Uganda', 'region' => 'Africa', 'currency' => 'UGX', 'cluster' => 2, 'confidence' => 'medium'],
        'RW' => ['name' => 'Rwanda', 'region' => 'Africa', 'currency' => 'RWF', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Tipping gorilla-trekking guides and trackers is a well-established, appreciated custom.'],
        'SN' => ['name' => 'Senegal', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'CI' => ['name' => 'Cote d\'Ivoire', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'CM' => ['name' => 'Cameroon', 'region' => 'Africa', 'currency' => 'XAF', 'cluster' => 2, 'confidence' => 'medium'],
        'ZW' => ['name' => 'Zimbabwe', 'region' => 'Africa', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium'],
        'ZM' => ['name' => 'Zambia', 'region' => 'Africa', 'currency' => 'ZMW', 'cluster' => 2, 'confidence' => 'medium'],
        'BW' => ['name' => 'Botswana', 'region' => 'Africa', 'currency' => 'BWP', 'cluster' => 2, 'confidence' => 'medium', 'note' => 'Around 10% at restaurants; tipping safari guides at lodges is customary.'],
        'NA' => ['name' => 'Namibia', 'region' => 'Africa', 'currency' => 'NAD', 'cluster' => 2, 'confidence' => 'medium'],
        'MZ' => ['name' => 'Mozambique', 'region' => 'Africa', 'currency' => 'MZN', 'cluster' => 2, 'confidence' => 'medium'],
        'MU' => ['name' => 'Mauritius', 'region' => 'Africa', 'currency' => 'MUR', 'cluster' => 2, 'confidence' => 'medium'],
        'SC' => ['name' => 'Seychelles', 'region' => 'Africa', 'currency' => 'SCR', 'cluster' => 3, 'confidence' => 'medium'],
        'MG' => ['name' => 'Madagascar', 'region' => 'Africa', 'currency' => 'MGA', 'cluster' => 2, 'confidence' => 'medium'],
        'AO' => ['name' => 'Angola', 'region' => 'Africa', 'currency' => 'AOA', 'cluster' => 2, 'confidence' => 'medium'],
        'MW' => ['name' => 'Malawi', 'region' => 'Africa', 'currency' => 'MWK', 'cluster' => 2, 'confidence' => 'medium'],
        'ML' => ['name' => 'Mali', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'BF' => ['name' => 'Burkina Faso', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'NE' => ['name' => 'Niger', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'TD' => ['name' => 'Chad', 'region' => 'Africa', 'currency' => 'XAF', 'cluster' => 2, 'confidence' => 'medium'],
        'SD' => ['name' => 'Sudan', 'region' => 'Africa', 'currency' => 'SDG', 'cluster' => 5, 'confidence' => 'medium'],
        'SS' => ['name' => 'South Sudan', 'region' => 'Africa', 'currency' => 'SSP', 'cluster' => 2, 'confidence' => 'medium'],
        'SO' => ['name' => 'Somalia', 'region' => 'Africa', 'currency' => 'SOS', 'cluster' => 5, 'confidence' => 'medium'],
        'LY' => ['name' => 'Libya', 'region' => 'Africa', 'currency' => 'LYD', 'cluster' => 5, 'confidence' => 'medium'],
        'MR' => ['name' => 'Mauritania', 'region' => 'Africa', 'currency' => 'MRU', 'cluster' => 5, 'confidence' => 'medium'],
        'GA' => ['name' => 'Gabon', 'region' => 'Africa', 'currency' => 'XAF', 'cluster' => 2, 'confidence' => 'medium'],
        'CG' => ['name' => 'Republic of the Congo', 'region' => 'Africa', 'currency' => 'XAF', 'cluster' => 2, 'confidence' => 'medium'],
        'CD' => ['name' => 'DR Congo', 'region' => 'Africa', 'currency' => 'CDF', 'cluster' => 2, 'confidence' => 'medium'],
        'BJ' => ['name' => 'Benin', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'TG' => ['name' => 'Togo', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'GN' => ['name' => 'Guinea', 'region' => 'Africa', 'currency' => 'GNF', 'cluster' => 2, 'confidence' => 'medium'],
        'SL' => ['name' => 'Sierra Leone', 'region' => 'Africa', 'currency' => 'SLE', 'cluster' => 2, 'confidence' => 'medium'],
        'LR' => ['name' => 'Liberia', 'region' => 'Africa', 'currency' => 'LRD', 'cluster' => 2, 'confidence' => 'medium'],
        'GM' => ['name' => 'Gambia', 'region' => 'Africa', 'currency' => 'GMD', 'cluster' => 2, 'confidence' => 'medium'],
        'GW' => ['name' => 'Guinea-Bissau', 'region' => 'Africa', 'currency' => 'XOF', 'cluster' => 5, 'confidence' => 'medium'],
        'ER' => ['name' => 'Eritrea', 'region' => 'Africa', 'currency' => 'ERN', 'cluster' => 2, 'confidence' => 'medium'],
        'DJ' => ['name' => 'Djibouti', 'region' => 'Africa', 'currency' => 'DJF', 'cluster' => 5, 'confidence' => 'medium'],
        'BI' => ['name' => 'Burundi', 'region' => 'Africa', 'currency' => 'BIF', 'cluster' => 2, 'confidence' => 'medium'],
        'LS' => ['name' => 'Lesotho', 'region' => 'Africa', 'currency' => 'LSL', 'cluster' => 2, 'confidence' => 'medium'],
        'SZ' => ['name' => 'Eswatini', 'region' => 'Africa', 'currency' => 'SZL', 'cluster' => 2, 'confidence' => 'medium'],
        'CV' => ['name' => 'Cape Verde', 'region' => 'Africa', 'currency' => 'CVE', 'cluster' => 2, 'confidence' => 'medium'],
        'ST' => ['name' => 'Sao Tome & Principe', 'region' => 'Africa', 'currency' => 'STN', 'cluster' => 2, 'confidence' => 'medium'],
        'KM' => ['name' => 'Comoros', 'region' => 'Africa', 'currency' => 'KMF', 'cluster' => 2, 'confidence' => 'medium'],
        'GQ' => ['name' => 'Equatorial Guinea', 'region' => 'Africa', 'currency' => 'XAF', 'cluster' => 2, 'confidence' => 'medium'],
        'CF' => ['name' => 'Central African Republic', 'region' => 'Africa', 'currency' => 'XAF', 'cluster' => 2, 'confidence' => 'medium'],

        /* ---- Oceania ---- */
        'AU' => ['name' => 'Australia', 'region' => 'Oceania', 'currency' => 'AUD', 'cluster' => 4, 'confidence' => 'high', 'note' => 'Tipping is not expected - staff are paid a full minimum wage. It is a genuine bonus for standout service, never an obligation.'],
        'NZ' => ['name' => 'New Zealand', 'region' => 'Oceania', 'currency' => 'NZD', 'cluster' => 4, 'confidence' => 'high', 'note' => 'Tipping is not part of the culture and is not expected; it is a nice extra reserved for exceptional service only.'],
        'FJ' => ['name' => 'Fiji', 'region' => 'Oceania', 'currency' => 'FJD', 'cluster' => 4, 'confidence' => 'medium', 'note' => 'Tipping is not traditional; many resorts run a shared staff fund box instead of individual tips.'],
        'PG' => ['name' => 'Papua New Guinea', 'region' => 'Oceania', 'currency' => 'PGK', 'cluster' => 4, 'confidence' => 'medium'],
        'SB' => ['name' => 'Solomon Islands', 'region' => 'Oceania', 'currency' => 'SBD', 'cluster' => 4, 'confidence' => 'medium'],
        'VU' => ['name' => 'Vanuatu', 'region' => 'Oceania', 'currency' => 'VUV', 'cluster' => 4, 'confidence' => 'medium'],
        'WS' => ['name' => 'Samoa', 'region' => 'Oceania', 'currency' => 'WST', 'cluster' => 4, 'confidence' => 'medium'],
        'TO' => ['name' => 'Tonga', 'region' => 'Oceania', 'currency' => 'TOP', 'cluster' => 4, 'confidence' => 'medium'],
        'KI' => ['name' => 'Kiribati', 'region' => 'Oceania', 'currency' => 'AUD', 'cluster' => 4, 'confidence' => 'medium'],
        'FM' => ['name' => 'Micronesia', 'region' => 'Oceania', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium'],
        'MH' => ['name' => 'Marshall Islands', 'region' => 'Oceania', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium'],
        'PW' => ['name' => 'Palau', 'region' => 'Oceania', 'currency' => 'USD', 'cluster' => 2, 'confidence' => 'medium'],
        'NR' => ['name' => 'Nauru', 'region' => 'Oceania', 'currency' => 'AUD', 'cluster' => 4, 'confidence' => 'medium'],
        'TV' => ['name' => 'Tuvalu', 'region' => 'Oceania', 'currency' => 'AUD', 'cluster' => 4, 'confidence' => 'medium'],
        'PF' => ['name' => 'French Polynesia', 'region' => 'Oceania', 'currency' => 'XPF', 'cluster' => 4, 'confidence' => 'medium', 'note' => 'Tipping is not part of Polynesian culture and is not expected, though it is accepted for exceptional service.'],
        'NC' => ['name' => 'New Caledonia', 'region' => 'Oceania', 'currency' => 'XPF', 'cluster' => 2, 'confidence' => 'medium'],
        'GU' => ['name' => 'Guam', 'region' => 'Oceania', 'currency' => 'USD', 'cluster' => 1, 'confidence' => 'medium', 'note' => 'As a US territory, US-style tipping (15-20% at restaurants) applies.'],
    ],
];
