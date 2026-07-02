<?php
/**
 * WTSM_Country_Codes
 *
 * A static country-name -> ISO 3166-1 alpha-2 code lookup, used to
 * best-effort backfill the destinations table's country_code column from
 * the existing free-text "country" field. Pure reference facts (public
 * domain, ISO's own identifiers) -- no licensing question.
 *
 * This is the bridge that lets the matcher call Voyasee Country
 * Intelligence's per-country functions (which key everything off ISO
 * codes) using only the destination data this plugin already has.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Country_Codes {

	/** Country name (and common aliases) -> ISO 3166-1 alpha-2, lowercase keys. */
	private static $map = array(
		'afghanistan' => 'AF', 'albania' => 'AL', 'algeria' => 'DZ', 'andorra' => 'AD',
		'angola' => 'AO', 'antigua and barbuda' => 'AG', 'argentina' => 'AR', 'armenia' => 'AM',
		'australia' => 'AU', 'austria' => 'AT', 'azerbaijan' => 'AZ', 'bahamas' => 'BS',
		'bahrain' => 'BH', 'bangladesh' => 'BD', 'barbados' => 'BB', 'belarus' => 'BY',
		'belgium' => 'BE', 'belize' => 'BZ', 'benin' => 'BJ', 'bhutan' => 'BT',
		'bolivia' => 'BO', 'bosnia and herzegovina' => 'BA', 'bosnia' => 'BA', 'botswana' => 'BW',
		'brazil' => 'BR', 'brunei' => 'BN', 'bulgaria' => 'BG', 'burkina faso' => 'BF',
		'burundi' => 'BI', 'cambodia' => 'KH', 'cameroon' => 'CM', 'canada' => 'CA',
		'cape verde' => 'CV', 'cabo verde' => 'CV', 'central african republic' => 'CF', 'chad' => 'TD',
		'chile' => 'CL', 'china' => 'CN', 'colombia' => 'CO', 'comoros' => 'KM',
		'congo' => 'CG', 'democratic republic of the congo' => 'CD', 'dr congo' => 'CD', 'costa rica' => 'CR',
		'croatia' => 'HR', 'cuba' => 'CU', 'cyprus' => 'CY', 'czech republic' => 'CZ',
		'czechia' => 'CZ', 'denmark' => 'DK', 'djibouti' => 'DJ', 'dominica' => 'DM',
		'dominican republic' => 'DO', 'east timor' => 'TL', 'timor-leste' => 'TL', 'ecuador' => 'EC',
		'egypt' => 'EG', 'el salvador' => 'SV', 'equatorial guinea' => 'GQ', 'eritrea' => 'ER',
		'estonia' => 'EE', 'eswatini' => 'SZ', 'swaziland' => 'SZ', 'ethiopia' => 'ET',
		'fiji' => 'FJ', 'finland' => 'FI', 'france' => 'FR', 'gabon' => 'GA',
		'gambia' => 'GM', 'georgia' => 'GE', 'germany' => 'DE', 'ghana' => 'GH',
		'greece' => 'GR', 'grenada' => 'GD', 'guatemala' => 'GT', 'guinea' => 'GN',
		'guinea-bissau' => 'GW', 'guyana' => 'GY', 'haiti' => 'HT', 'honduras' => 'HN',
		'hong kong' => 'HK', 'hungary' => 'HU', 'iceland' => 'IS', 'india' => 'IN',
		'indonesia' => 'ID', 'iran' => 'IR', 'iraq' => 'IQ', 'ireland' => 'IE',
		'israel' => 'IL', 'italy' => 'IT', 'ivory coast' => 'CI', "cote d'ivoire" => 'CI',
		'jamaica' => 'JM', 'japan' => 'JP', 'jordan' => 'JO', 'kazakhstan' => 'KZ',
		'kenya' => 'KE', 'kiribati' => 'KI', 'kosovo' => 'XK', 'kuwait' => 'KW',
		'kyrgyzstan' => 'KG', 'laos' => 'LA', 'latvia' => 'LV', 'lebanon' => 'LB',
		'lesotho' => 'LS', 'liberia' => 'LR', 'libya' => 'LY', 'liechtenstein' => 'LI',
		'lithuania' => 'LT', 'luxembourg' => 'LU', 'macau' => 'MO', 'macao' => 'MO',
		'madagascar' => 'MG', 'malawi' => 'MW', 'malaysia' => 'MY', 'maldives' => 'MV',
		'mali' => 'ML', 'malta' => 'MT', 'marshall islands' => 'MH', 'mauritania' => 'MR',
		'mauritius' => 'MU', 'mexico' => 'MX', 'micronesia' => 'FM', 'moldova' => 'MD',
		'monaco' => 'MC', 'mongolia' => 'MN', 'montenegro' => 'ME', 'morocco' => 'MA',
		'mozambique' => 'MZ', 'myanmar' => 'MM', 'burma' => 'MM', 'namibia' => 'NA',
		'nauru' => 'NR', 'nepal' => 'NP', 'netherlands' => 'NL', 'holland' => 'NL',
		'new zealand' => 'NZ', 'nicaragua' => 'NI', 'niger' => 'NE', 'nigeria' => 'NG',
		'north korea' => 'KP', 'north macedonia' => 'MK', 'macedonia' => 'MK', 'norway' => 'NO',
		'oman' => 'OM', 'pakistan' => 'PK', 'palau' => 'PW', 'palestine' => 'PS',
		'panama' => 'PA', 'papua new guinea' => 'PG', 'paraguay' => 'PY', 'peru' => 'PE',
		'philippines' => 'PH', 'poland' => 'PL', 'portugal' => 'PT', 'qatar' => 'QA',
		'romania' => 'RO', 'russia' => 'RU', 'russian federation' => 'RU', 'rwanda' => 'RW',
		'saint kitts and nevis' => 'KN', 'saint lucia' => 'LC', 'saint vincent and the grenadines' => 'VC',
		'samoa' => 'WS', 'san marino' => 'SM', 'sao tome and principe' => 'ST', 'saudi arabia' => 'SA',
		'senegal' => 'SN', 'serbia' => 'RS', 'seychelles' => 'SC', 'sierra leone' => 'SL',
		'singapore' => 'SG', 'slovakia' => 'SK', 'slovenia' => 'SI', 'solomon islands' => 'SB',
		'somalia' => 'SO', 'south africa' => 'ZA', 'south korea' => 'KR', 'korea' => 'KR',
		'south sudan' => 'SS', 'spain' => 'ES', 'sri lanka' => 'LK', 'sudan' => 'SD',
		'suriname' => 'SR', 'sweden' => 'SE', 'switzerland' => 'CH', 'syria' => 'SY',
		'taiwan' => 'TW', 'tajikistan' => 'TJ', 'tanzania' => 'TZ', 'thailand' => 'TH',
		'togo' => 'TG', 'tonga' => 'TO', 'trinidad and tobago' => 'TT', 'tunisia' => 'TN',
		'turkey' => 'TR', 'turkiye' => 'TR', 'türkiye' => 'TR', 'turkmenistan' => 'TM',
		'tuvalu' => 'TV', 'uganda' => 'UG', 'ukraine' => 'UA', 'united arab emirates' => 'AE',
		'uae' => 'AE', 'united kingdom' => 'GB', 'uk' => 'GB', 'england' => 'GB',
		'scotland' => 'GB', 'wales' => 'GB', 'great britain' => 'GB', 'united states' => 'US',
		'united states of america' => 'US', 'usa' => 'US', 'u.s.a.' => 'US', 'us' => 'US',
		'uruguay' => 'UY', 'uzbekistan' => 'UZ', 'vanuatu' => 'VU', 'vatican city' => 'VA',
		'venezuela' => 'VE', 'vietnam' => 'VN', 'viet nam' => 'VN', 'yemen' => 'YE',
		'zambia' => 'ZM', 'zimbabwe' => 'ZW',
	);

	/**
	 * Resolve a free-text country name to an ISO 3166-1 alpha-2 code, or
	 * '' if not recognized. Case/whitespace-insensitive exact match only
	 * -- deliberately no fuzzy matching, since a wrong code silently
	 * pulling in another country's holidays/currency would be worse than
	 * just leaving the field blank for an admin to set by hand.
	 */
	public static function guess_code( $country_name ) {
		$key = strtolower( trim( (string) $country_name ) );
		return self::$map[ $key ] ?? '';
	}

	/**
	 * Fill in country_code for any destination that has a recognized
	 * country name but no code yet. Never overwrites a code that's
	 * already set (including one an admin deliberately cleared/changed).
	 * Safe and cheap to call on every plugin activation.
	 */
	public static function backfill_destination_codes() {
		global $wpdb;
		$table = $wpdb->prefix . VNI_TABLE_DESTINATIONS;

		$rows = $wpdb->get_results(
			"SELECT id, country FROM {$table} WHERE country_code = '' AND country != ''",
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return 0;
		}

		$updated = 0;
		foreach ( $rows as $row ) {
			$code = self::guess_code( $row['country'] );
			if ( '' === $code ) {
				continue;
			}
			$wpdb->update( $table, array( 'country_code' => $code ), array( 'id' => $row['id'] ) );
			$updated++;
		}

		return $updated;
	}
}
