<?php
/**
 * Voyasee Tipping Calculator — currency metadata + bundled reference rates.
 *
 * NO runtime API. The tool's core (a tip as a % of the bill you enter) needs
 * no conversion at all, because the tip is returned in the same local currency
 * you typed. The optional "approximately in your home currency" line uses the
 * static, plugin-owned reference table below.
 *
 * `rate` = units of the currency per 1 US dollar. These are approximate
 * reference values compiled at release time and clearly presented in the UI as
 * a rough guide only, never as a live or exact rate. Confirm the real rate
 * with your bank or card provider before relying on it for a transaction.
 *
 * `decimals` follows ISO 4217 minor units so amounts are rounded and shown the
 * way each currency is actually used (e.g. JPY has none, KWD has three).
 * `symbol` is limited to widely-supported glyphs; where a currency has no
 * common, safe glyph its ISO code is used as the symbol so nothing renders as
 * a missing-character box.
 *
 * @return array{meta:array,currencies:array<string,array{symbol:string,decimals:int,name:string,rate:float}>}
 */

defined('ABSPATH') || exit;

return [
    'meta' => [
        'base'  => 'USD',
        'as_of' => '2025',
        'note'  => 'Approximate reference rates, bundled with the tool and refreshed at each release. For rough guidance only.',
    ],
    'currencies' => [
        // North & Central America / Caribbean
        'USD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'US Dollar',                'rate' => 1.0],
        'CAD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Canadian Dollar',          'rate' => 1.36],
        'MXN' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Mexican Peso',             'rate' => 17.0],
        'GTQ' => ['symbol' => 'Q',   'decimals' => 2, 'name' => 'Guatemalan Quetzal',       'rate' => 7.8],
        'BZD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Belize Dollar',            'rate' => 2.0],
        'HNL' => ['symbol' => 'L',   'decimals' => 2, 'name' => 'Honduran Lempira',         'rate' => 24.7],
        'NIO' => ['symbol' => 'C$',  'decimals' => 2, 'name' => 'Nicaraguan Cordoba',       'rate' => 36.8],
        'CRC' => ['symbol' => "\u{20A1}", 'decimals' => 2, 'name' => 'Costa Rican Colon',   'rate' => 515.0],
        'CUP' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Cuban Peso',               'rate' => 24.0],
        'DOP' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Dominican Peso',           'rate' => 59.0],
        'JMD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Jamaican Dollar',          'rate' => 156.0],
        'HTG' => ['symbol' => 'G',   'decimals' => 2, 'name' => 'Haitian Gourde',           'rate' => 132.0],
        'TTD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Trinidad & Tobago Dollar', 'rate' => 6.8],
        'BSD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Bahamian Dollar',          'rate' => 1.0],
        'BBD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Barbadian Dollar',         'rate' => 2.0],
        'AWG' => ['symbol' => "\u{0192}", 'decimals' => 2, 'name' => 'Aruban Florin',       'rate' => 1.79],
        'KYD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Cayman Islands Dollar',    'rate' => 0.83],
        'XCD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'East Caribbean Dollar',    'rate' => 2.70],
        'ANG' => ['symbol' => "\u{0192}", 'decimals' => 2, 'name' => 'Netherlands Antillean Guilder', 'rate' => 1.79],

        // South America
        'BRL' => ['symbol' => 'R$',  'decimals' => 2, 'name' => 'Brazilian Real',           'rate' => 5.0],
        'ARS' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Argentine Peso',           'rate' => 900.0],
        'CLP' => ['symbol' => '$',   'decimals' => 0, 'name' => 'Chilean Peso',             'rate' => 950.0],
        'COP' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Colombian Peso',           'rate' => 3900.0],
        'PEN' => ['symbol' => 'S/',  'decimals' => 2, 'name' => 'Peruvian Sol',             'rate' => 3.75],
        'BOB' => ['symbol' => 'Bs',  'decimals' => 2, 'name' => 'Bolivian Boliviano',       'rate' => 6.9],
        'PYG' => ['symbol' => "\u{20B2}", 'decimals' => 0, 'name' => 'Paraguayan Guarani',  'rate' => 7300.0],
        'UYU' => ['symbol' => '$U',  'decimals' => 2, 'name' => 'Uruguayan Peso',           'rate' => 39.0],
        'VES' => ['symbol' => 'Bs',  'decimals' => 2, 'name' => 'Venezuelan Bolivar',       'rate' => 36.0],
        'GYD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Guyanese Dollar',          'rate' => 209.0],
        'SRD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Surinamese Dollar',        'rate' => 35.0],

        // Europe
        'GBP' => ['symbol' => "\u{00A3}", 'decimals' => 2, 'name' => 'British Pound',       'rate' => 0.79],
        'EUR' => ['symbol' => "\u{20AC}", 'decimals' => 2, 'name' => 'Euro',                'rate' => 0.92],
        'CHF' => ['symbol' => 'CHF', 'decimals' => 2, 'name' => 'Swiss Franc',              'rate' => 0.88],
        'SEK' => ['symbol' => 'kr',  'decimals' => 2, 'name' => 'Swedish Krona',            'rate' => 10.5],
        'NOK' => ['symbol' => 'kr',  'decimals' => 2, 'name' => 'Norwegian Krone',          'rate' => 10.7],
        'DKK' => ['symbol' => 'kr',  'decimals' => 2, 'name' => 'Danish Krone',             'rate' => 6.9],
        'ISK' => ['symbol' => 'kr',  'decimals' => 0, 'name' => 'Icelandic Krona',          'rate' => 138.0],
        'PLN' => ['symbol' => "z\u{0142}", 'decimals' => 2, 'name' => 'Polish Zloty',       'rate' => 4.0],
        'CZK' => ['symbol' => "K\u{010D}", 'decimals' => 2, 'name' => 'Czech Koruna',       'rate' => 23.0],
        'HUF' => ['symbol' => 'Ft',  'decimals' => 2, 'name' => 'Hungarian Forint',         'rate' => 360.0],
        'RON' => ['symbol' => 'lei', 'decimals' => 2, 'name' => 'Romanian Leu',             'rate' => 4.6],
        'BGN' => ['symbol' => 'лв',  'decimals' => 2, 'name' => 'Bulgarian Lev',            'rate' => 1.80],
        'RSD' => ['symbol' => 'din', 'decimals' => 2, 'name' => 'Serbian Dinar',            'rate' => 108.0],
        'UAH' => ['symbol' => "\u{20B4}", 'decimals' => 2, 'name' => 'Ukrainian Hryvnia',   'rate' => 39.0],
        'RUB' => ['symbol' => "\u{20BD}", 'decimals' => 2, 'name' => 'Russian Ruble',       'rate' => 92.0],
        'BYN' => ['symbol' => 'Br',  'decimals' => 2, 'name' => 'Belarusian Ruble',         'rate' => 3.3],
        'ALL' => ['symbol' => 'L',   'decimals' => 2, 'name' => 'Albanian Lek',             'rate' => 93.0],
        'MKD' => ['symbol' => 'ден', 'decimals' => 2, 'name' => 'Macedonian Denar',         'rate' => 57.0],
        'BAM' => ['symbol' => 'KM',  'decimals' => 2, 'name' => 'Bosnia-Herzegovina Mark',  'rate' => 1.80],
        'MDL' => ['symbol' => 'L',   'decimals' => 2, 'name' => 'Moldovan Leu',             'rate' => 17.8],

        // Middle East
        'AED' => ['symbol' => 'AED', 'decimals' => 2, 'name' => 'UAE Dirham',               'rate' => 3.67],
        'SAR' => ['symbol' => 'SAR', 'decimals' => 2, 'name' => 'Saudi Riyal',              'rate' => 3.75],
        'QAR' => ['symbol' => 'QAR', 'decimals' => 2, 'name' => 'Qatari Riyal',             'rate' => 3.64],
        'KWD' => ['symbol' => 'KWD', 'decimals' => 3, 'name' => 'Kuwaiti Dinar',            'rate' => 0.31],
        'BHD' => ['symbol' => 'BHD', 'decimals' => 3, 'name' => 'Bahraini Dinar',           'rate' => 0.376],
        'OMR' => ['symbol' => 'OMR', 'decimals' => 3, 'name' => 'Omani Rial',               'rate' => 0.385],
        'JOD' => ['symbol' => 'JOD', 'decimals' => 3, 'name' => 'Jordanian Dinar',          'rate' => 0.71],
        'LBP' => ['symbol' => 'LBP', 'decimals' => 2, 'name' => 'Lebanese Pound',           'rate' => 89000.0],
        'ILS' => ['symbol' => "\u{20AA}", 'decimals' => 2, 'name' => 'Israeli New Shekel',  'rate' => 3.7],
        'TRY' => ['symbol' => "\u{20BA}", 'decimals' => 2, 'name' => 'Turkish Lira',        'rate' => 32.0],
        'IQD' => ['symbol' => 'IQD', 'decimals' => 3, 'name' => 'Iraqi Dinar',              'rate' => 1310.0],
        'IRR' => ['symbol' => 'IRR', 'decimals' => 2, 'name' => 'Iranian Rial',             'rate' => 42000.0],
        'YER' => ['symbol' => 'YER', 'decimals' => 2, 'name' => 'Yemeni Rial',              'rate' => 250.0],
        'SYP' => ['symbol' => 'SYP', 'decimals' => 2, 'name' => 'Syrian Pound',             'rate' => 13000.0],

        // Asia
        'JPY' => ['symbol' => "\u{00A5}", 'decimals' => 0, 'name' => 'Japanese Yen',        'rate' => 150.0],
        'KRW' => ['symbol' => "\u{20A9}", 'decimals' => 0, 'name' => 'South Korean Won',    'rate' => 1330.0],
        'CNY' => ['symbol' => "\u{00A5}", 'decimals' => 2, 'name' => 'Chinese Yuan',        'rate' => 7.2],
        'HKD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Hong Kong Dollar',         'rate' => 7.8],
        'TWD' => ['symbol' => 'NT$', 'decimals' => 2, 'name' => 'New Taiwan Dollar',        'rate' => 32.0],
        'MOP' => ['symbol' => 'MOP', 'decimals' => 2, 'name' => 'Macanese Pataca',          'rate' => 8.0],
        'THB' => ['symbol' => "\u{0E3F}", 'decimals' => 2, 'name' => 'Thai Baht',           'rate' => 35.0],
        'VND' => ['symbol' => "\u{20AB}", 'decimals' => 0, 'name' => 'Vietnamese Dong',     'rate' => 25000.0],
        'KHR' => ['symbol' => "\u{17DB}", 'decimals' => 2, 'name' => 'Cambodian Riel',      'rate' => 4100.0],
        'LAK' => ['symbol' => "\u{20AD}", 'decimals' => 2, 'name' => 'Lao Kip',             'rate' => 21000.0],
        'MMK' => ['symbol' => 'K',   'decimals' => 2, 'name' => 'Myanmar Kyat',             'rate' => 2100.0],
        'MYR' => ['symbol' => 'RM',  'decimals' => 2, 'name' => 'Malaysian Ringgit',        'rate' => 4.6],
        'SGD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Singapore Dollar',         'rate' => 1.35],
        'IDR' => ['symbol' => 'Rp',  'decimals' => 2, 'name' => 'Indonesian Rupiah',        'rate' => 15800.0],
        'PHP' => ['symbol' => "\u{20B1}", 'decimals' => 2, 'name' => 'Philippine Peso',     'rate' => 57.0],
        'INR' => ['symbol' => "\u{20B9}", 'decimals' => 2, 'name' => 'Indian Rupee',        'rate' => 83.0],
        'NPR' => ['symbol' => "\u{20A8}", 'decimals' => 2, 'name' => 'Nepalese Rupee',      'rate' => 133.0],
        'LKR' => ['symbol' => "\u{20A8}", 'decimals' => 2, 'name' => 'Sri Lankan Rupee',    'rate' => 300.0],
        'BDT' => ['symbol' => "\u{09F3}", 'decimals' => 2, 'name' => 'Bangladeshi Taka',    'rate' => 117.0],
        'PKR' => ['symbol' => "\u{20A8}", 'decimals' => 2, 'name' => 'Pakistani Rupee',     'rate' => 278.0],
        'BTN' => ['symbol' => 'Nu', 'decimals' => 2, 'name' => 'Bhutanese Ngultrum',        'rate' => 83.0],
        'MVR' => ['symbol' => 'Rf',  'decimals' => 2, 'name' => 'Maldivian Rufiyaa',        'rate' => 15.4],
        'MNT' => ['symbol' => "\u{20AE}", 'decimals' => 2, 'name' => 'Mongolian Tugrik',    'rate' => 3450.0],
        'KZT' => ['symbol' => "\u{20B8}", 'decimals' => 2, 'name' => 'Kazakhstani Tenge',   'rate' => 470.0],
        'UZS' => ['symbol' => 'soum','decimals' => 2, 'name' => 'Uzbekistani Som',          'rate' => 12600.0],
        'KGS' => ['symbol' => 'с',   'decimals' => 2, 'name' => 'Kyrgyzstani Som',          'rate' => 89.0],
        'TJS' => ['symbol' => 'SM',  'decimals' => 2, 'name' => 'Tajikistani Somoni',       'rate' => 10.9],
        'TMT' => ['symbol' => 'm',   'decimals' => 2, 'name' => 'Turkmenistani Manat',      'rate' => 3.5],
        'AFN' => ['symbol' => "\u{060B}", 'decimals' => 2, 'name' => 'Afghan Afghani',      'rate' => 71.0],
        'AMD' => ['symbol' => "\u{058F}", 'decimals' => 2, 'name' => 'Armenian Dram',       'rate' => 400.0],
        'AZN' => ['symbol' => "\u{20BC}", 'decimals' => 2, 'name' => 'Azerbaijani Manat',   'rate' => 1.7],
        'GEL' => ['symbol' => "\u{20BE}", 'decimals' => 2, 'name' => 'Georgian Lari',       'rate' => 2.7],
        'BND' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Brunei Dollar',            'rate' => 1.35],

        // Africa
        'ZAR' => ['symbol' => 'R',   'decimals' => 2, 'name' => 'South African Rand',       'rate' => 18.5],
        'EGP' => ['symbol' => "E\u{00A3}", 'decimals' => 2, 'name' => 'Egyptian Pound',     'rate' => 48.0],
        'MAD' => ['symbol' => 'DH',  'decimals' => 2, 'name' => 'Moroccan Dirham',          'rate' => 10.0],
        'TND' => ['symbol' => 'DT',  'decimals' => 3, 'name' => 'Tunisian Dinar',           'rate' => 3.1],
        'DZD' => ['symbol' => 'DA',  'decimals' => 2, 'name' => 'Algerian Dinar',           'rate' => 134.0],
        'KES' => ['symbol' => 'KSh', 'decimals' => 2, 'name' => 'Kenyan Shilling',          'rate' => 130.0],
        'TZS' => ['symbol' => 'TSh', 'decimals' => 2, 'name' => 'Tanzanian Shilling',       'rate' => 2600.0],
        'NGN' => ['symbol' => "\u{20A6}", 'decimals' => 2, 'name' => 'Nigerian Naira',      'rate' => 1500.0],
        'GHS' => ['symbol' => "\u{20B5}", 'decimals' => 2, 'name' => 'Ghanaian Cedi',       'rate' => 15.0],
        'ETB' => ['symbol' => 'Br',  'decimals' => 2, 'name' => 'Ethiopian Birr',           'rate' => 120.0],
        'UGX' => ['symbol' => 'USh', 'decimals' => 0, 'name' => 'Ugandan Shilling',         'rate' => 3800.0],
        'RWF' => ['symbol' => 'FRw', 'decimals' => 0, 'name' => 'Rwandan Franc',            'rate' => 1300.0],
        'XOF' => ['symbol' => 'CFA', 'decimals' => 0, 'name' => 'West African CFA Franc',   'rate' => 600.0],
        'XAF' => ['symbol' => 'FCFA','decimals' => 0, 'name' => 'Central African CFA Franc','rate' => 600.0],
        'ZMW' => ['symbol' => 'ZK',  'decimals' => 2, 'name' => 'Zambian Kwacha',           'rate' => 26.0],
        'BWP' => ['symbol' => 'P',   'decimals' => 2, 'name' => 'Botswana Pula',            'rate' => 13.6],
        'NAD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Namibian Dollar',          'rate' => 18.5],
        'MZN' => ['symbol' => 'MT',  'decimals' => 2, 'name' => 'Mozambican Metical',       'rate' => 64.0],
        'MUR' => ['symbol' => "\u{20A8}", 'decimals' => 2, 'name' => 'Mauritian Rupee',     'rate' => 46.0],
        'SCR' => ['symbol' => "\u{20A8}", 'decimals' => 2, 'name' => 'Seychellois Rupee',   'rate' => 13.5],
        'MGA' => ['symbol' => 'Ar',  'decimals' => 2, 'name' => 'Malagasy Ariary',          'rate' => 4500.0],
        'AOA' => ['symbol' => 'Kz',  'decimals' => 2, 'name' => 'Angolan Kwanza',           'rate' => 900.0],
        'MWK' => ['symbol' => 'MK',  'decimals' => 2, 'name' => 'Malawian Kwacha',          'rate' => 1700.0],
        'SDG' => ['symbol' => 'SDG', 'decimals' => 2, 'name' => 'Sudanese Pound',           'rate' => 600.0],
        'SSP' => ['symbol' => 'SSP', 'decimals' => 2, 'name' => 'South Sudanese Pound',     'rate' => 1300.0],
        'SOS' => ['symbol' => 'Sh',  'decimals' => 2, 'name' => 'Somali Shilling',          'rate' => 570.0],
        'LYD' => ['symbol' => 'LD',  'decimals' => 3, 'name' => 'Libyan Dinar',             'rate' => 4.8],
        'MRU' => ['symbol' => 'UM',  'decimals' => 2, 'name' => 'Mauritanian Ouguiya',      'rate' => 40.0],
        'CDF' => ['symbol' => 'FC',  'decimals' => 2, 'name' => 'Congolese Franc',          'rate' => 2800.0],
        'GNF' => ['symbol' => 'FG',  'decimals' => 0, 'name' => 'Guinean Franc',            'rate' => 8600.0],
        'SLE' => ['symbol' => 'Le',  'decimals' => 2, 'name' => 'Sierra Leonean Leone',     'rate' => 22.0],
        'LRD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Liberian Dollar',          'rate' => 190.0],
        'GMD' => ['symbol' => 'D',   'decimals' => 2, 'name' => 'Gambian Dalasi',           'rate' => 68.0],
        'ERN' => ['symbol' => 'Nfk', 'decimals' => 2, 'name' => 'Eritrean Nakfa',           'rate' => 15.0],
        'DJF' => ['symbol' => 'Fdj', 'decimals' => 0, 'name' => 'Djiboutian Franc',         'rate' => 178.0],
        'BIF' => ['symbol' => 'FBu', 'decimals' => 0, 'name' => 'Burundian Franc',          'rate' => 2900.0],
        'LSL' => ['symbol' => 'L',   'decimals' => 2, 'name' => 'Lesotho Loti',             'rate' => 18.5],
        'SZL' => ['symbol' => 'E',   'decimals' => 2, 'name' => 'Swazi Lilangeni',          'rate' => 18.5],
        'CVE' => ['symbol' => "$",   'decimals' => 2, 'name' => 'Cape Verdean Escudo',      'rate' => 101.0],
        'STN' => ['symbol' => 'Db',  'decimals' => 2, 'name' => 'Sao Tome & Principe Dobra','rate' => 22.5],
        'KMF' => ['symbol' => 'CF',  'decimals' => 0, 'name' => 'Comorian Franc',           'rate' => 450.0],

        // Oceania
        'AUD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Australian Dollar',        'rate' => 1.52],
        'NZD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'New Zealand Dollar',       'rate' => 1.65],
        'FJD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Fijian Dollar',            'rate' => 2.25],
        'PGK' => ['symbol' => 'K',   'decimals' => 2, 'name' => 'Papua New Guinean Kina',   'rate' => 3.9],
        'SBD' => ['symbol' => '$',   'decimals' => 2, 'name' => 'Solomon Islands Dollar',   'rate' => 8.5],
        'VUV' => ['symbol' => 'VT',  'decimals' => 0, 'name' => 'Vanuatu Vatu',             'rate' => 120.0],
        'WST' => ['symbol' => 'WS$', 'decimals' => 2, 'name' => 'Samoan Tala',              'rate' => 2.75],
        'TOP' => ['symbol' => 'T$',  'decimals' => 2, 'name' => 'Tongan Paanga',            'rate' => 2.4],
        'XPF' => ['symbol' => 'F',   'decimals' => 0, 'name' => 'CFP Franc',                'rate' => 110.0],
    ],
];
