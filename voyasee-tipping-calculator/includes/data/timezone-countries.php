<?php
/**
 * IANA timezone -> ISO 3166-1 alpha-2 country, for fully offline country
 * auto-detection (from the browser's own timezone — no GPS, no permission, no
 * API). Curated from the public IANA zone.tab, covering the common zones for
 * the countries this plugin supports. Zones not listed simply skip
 * auto-detection and fall back to the default country.
 *
 * @return array<string,string>
 */

defined('ABSPATH') || exit;

return [
    // North America
    'America/New_York' => 'US', 'America/Detroit' => 'US', 'America/Chicago' => 'US', 'America/Denver' => 'US',
    'America/Phoenix' => 'US', 'America/Los_Angeles' => 'US', 'America/Anchorage' => 'US', 'Pacific/Honolulu' => 'US',
    'America/Indiana/Indianapolis' => 'US', 'America/Boise' => 'US', 'America/Kentucky/Louisville' => 'US',
    'America/Toronto' => 'CA', 'America/Vancouver' => 'CA', 'America/Edmonton' => 'CA', 'America/Winnipeg' => 'CA',
    'America/Halifax' => 'CA', 'America/St_Johns' => 'CA', 'America/Regina' => 'CA',
    'America/Mexico_City' => 'MX', 'America/Cancun' => 'MX', 'America/Monterrey' => 'MX', 'America/Tijuana' => 'MX',
    'America/Merida' => 'MX', 'America/Chihuahua' => 'MX',

    // Central America & Caribbean
    'America/Guatemala' => 'GT', 'America/Belize' => 'BZ', 'America/El_Salvador' => 'SV', 'America/Tegucigalpa' => 'HN',
    'America/Managua' => 'NI', 'America/Costa_Rica' => 'CR', 'America/Panama' => 'PA',
    'America/Havana' => 'CU', 'America/Santo_Domingo' => 'DO', 'America/Jamaica' => 'JM', 'America/Port-au-Prince' => 'HT',
    'America/Port_of_Spain' => 'TT', 'America/Nassau' => 'BS', 'America/Barbados' => 'BB', 'America/Puerto_Rico' => 'PR',
    'America/Aruba' => 'AW', 'America/Cayman' => 'KY', 'America/Grand_Turk' => 'TC', 'America/Antigua' => 'AG',
    'America/St_Lucia' => 'LC', 'America/Grenada' => 'GD', 'America/St_Vincent' => 'VC', 'America/Dominica' => 'DM',
    'America/St_Kitts' => 'KN', 'America/Curacao' => 'CW', 'America/Lower_Princes' => 'SX',

    // South America
    'America/Sao_Paulo' => 'BR', 'America/Bahia' => 'BR', 'America/Fortaleza' => 'BR', 'America/Manaus' => 'BR', 'America/Recife' => 'BR',
    'America/Argentina/Buenos_Aires' => 'AR', 'America/Argentina/Cordoba' => 'AR', 'America/Argentina/Mendoza' => 'AR',
    'America/Santiago' => 'CL', 'America/Bogota' => 'CO', 'America/Lima' => 'PE', 'America/Guayaquil' => 'EC',
    'America/La_Paz' => 'BO', 'America/Asuncion' => 'PY', 'America/Montevideo' => 'UY', 'America/Caracas' => 'VE',
    'America/Guyana' => 'GY', 'America/Paramaribo' => 'SR',

    // Europe
    'Europe/London' => 'GB', 'Europe/Dublin' => 'IE', 'Europe/Paris' => 'FR', 'Europe/Berlin' => 'DE',
    'Europe/Rome' => 'IT', 'Europe/Madrid' => 'ES', 'Europe/Lisbon' => 'PT', 'Europe/Amsterdam' => 'NL',
    'Europe/Brussels' => 'BE', 'Europe/Zurich' => 'CH', 'Europe/Vienna' => 'AT', 'Europe/Athens' => 'GR',
    'Europe/Stockholm' => 'SE', 'Europe/Oslo' => 'NO', 'Europe/Copenhagen' => 'DK', 'Europe/Helsinki' => 'FI',
    'Atlantic/Reykjavik' => 'IS', 'Europe/Warsaw' => 'PL', 'Europe/Prague' => 'CZ', 'Europe/Budapest' => 'HU',
    'Europe/Bucharest' => 'RO', 'Europe/Sofia' => 'BG', 'Europe/Zagreb' => 'HR', 'Europe/Belgrade' => 'RS',
    'Europe/Bratislava' => 'SK', 'Europe/Ljubljana' => 'SI', 'Europe/Kiev' => 'UA', 'Europe/Kyiv' => 'UA',
    'Europe/Moscow' => 'RU', 'Europe/Minsk' => 'BY', 'Europe/Vilnius' => 'LT', 'Europe/Riga' => 'LV',
    'Europe/Tallinn' => 'EE', 'Europe/Luxembourg' => 'LU', 'Europe/Malta' => 'MT', 'Asia/Nicosia' => 'CY',
    'Europe/Tirane' => 'AL', 'Europe/Skopje' => 'MK', 'Europe/Sarajevo' => 'BA', 'Europe/Podgorica' => 'ME',
    'Europe/Chisinau' => 'MD', 'Europe/Andorra' => 'AD', 'Europe/Monaco' => 'MC', 'Europe/San_Marino' => 'SM',
    'Europe/Vaduz' => 'LI', 'Europe/Vatican' => 'VA', 'Europe/Belfast' => 'GB',

    // Middle East
    'Asia/Dubai' => 'AE', 'Asia/Riyadh' => 'SA', 'Asia/Qatar' => 'QA', 'Asia/Kuwait' => 'KW', 'Asia/Bahrain' => 'BH',
    'Asia/Muscat' => 'OM', 'Asia/Amman' => 'JO', 'Asia/Beirut' => 'LB', 'Asia/Jerusalem' => 'IL', 'Asia/Tel_Aviv' => 'IL',
    'Europe/Istanbul' => 'TR', 'Asia/Istanbul' => 'TR', 'Asia/Baghdad' => 'IQ', 'Asia/Tehran' => 'IR',
    'Asia/Aden' => 'YE', 'Asia/Damascus' => 'SY', 'Asia/Gaza' => 'PS', 'Asia/Hebron' => 'PS',

    // Asia
    'Asia/Tokyo' => 'JP', 'Asia/Seoul' => 'KR', 'Asia/Shanghai' => 'CN', 'Asia/Beijing' => 'CN', 'Asia/Hong_Kong' => 'HK',
    'Asia/Taipei' => 'TW', 'Asia/Macau' => 'MO', 'Asia/Bangkok' => 'TH', 'Asia/Ho_Chi_Minh' => 'VN', 'Asia/Saigon' => 'VN',
    'Asia/Phnom_Penh' => 'KH', 'Asia/Vientiane' => 'LA', 'Asia/Yangon' => 'MM', 'Asia/Kuala_Lumpur' => 'MY',
    'Asia/Singapore' => 'SG', 'Asia/Jakarta' => 'ID', 'Asia/Makassar' => 'ID', 'Asia/Manila' => 'PH',
    'Asia/Kolkata' => 'IN', 'Asia/Calcutta' => 'IN', 'Asia/Kathmandu' => 'NP', 'Asia/Colombo' => 'LK',
    'Asia/Dhaka' => 'BD', 'Asia/Karachi' => 'PK', 'Asia/Thimphu' => 'BT', 'Indian/Maldives' => 'MV',
    'Asia/Ulaanbaatar' => 'MN', 'Asia/Almaty' => 'KZ', 'Asia/Tashkent' => 'UZ', 'Asia/Bishkek' => 'KG',
    'Asia/Dushanbe' => 'TJ', 'Asia/Ashgabat' => 'TM', 'Asia/Kabul' => 'AF', 'Asia/Yerevan' => 'AM',
    'Asia/Baku' => 'AZ', 'Asia/Tbilisi' => 'GE', 'Asia/Brunei' => 'BN', 'Asia/Dili' => 'TL',

    // Africa
    'Africa/Johannesburg' => 'ZA', 'Africa/Cairo' => 'EG', 'Africa/Casablanca' => 'MA', 'Africa/Tunis' => 'TN',
    'Africa/Algiers' => 'DZ', 'Africa/Nairobi' => 'KE', 'Africa/Dar_es_Salaam' => 'TZ', 'Africa/Lagos' => 'NG',
    'Africa/Accra' => 'GH', 'Africa/Addis_Ababa' => 'ET', 'Africa/Kampala' => 'UG', 'Africa/Kigali' => 'RW',
    'Africa/Dakar' => 'SN', 'Africa/Abidjan' => 'CI', 'Africa/Douala' => 'CM', 'Africa/Harare' => 'ZW',
    'Africa/Lusaka' => 'ZM', 'Africa/Gaborone' => 'BW', 'Africa/Windhoek' => 'NA', 'Africa/Maputo' => 'MZ',
    'Indian/Mauritius' => 'MU', 'Indian/Mahe' => 'SC', 'Indian/Antananarivo' => 'MG', 'Africa/Luanda' => 'AO',
    'Africa/Blantyre' => 'MW', 'Africa/Bamako' => 'ML', 'Africa/Ouagadougou' => 'BF', 'Africa/Niamey' => 'NE',
    'Africa/Ndjamena' => 'TD', 'Africa/Khartoum' => 'SD', 'Africa/Juba' => 'SS', 'Africa/Mogadishu' => 'SO',
    'Africa/Tripoli' => 'LY', 'Africa/Nouakchott' => 'MR', 'Africa/Libreville' => 'GA', 'Africa/Brazzaville' => 'CG',
    'Africa/Kinshasa' => 'CD', 'Africa/Lubumbashi' => 'CD', 'Africa/Porto-Novo' => 'BJ', 'Africa/Lome' => 'TG',
    'Africa/Conakry' => 'GN', 'Africa/Freetown' => 'SL', 'Africa/Monrovia' => 'LR', 'Africa/Banjul' => 'GM',
    'Africa/Bissau' => 'GW', 'Africa/Asmara' => 'ER', 'Africa/Djibouti' => 'DJ', 'Africa/Bujumbura' => 'BI',
    'Africa/Maseru' => 'LS', 'Africa/Mbabane' => 'SZ', 'Atlantic/Cape_Verde' => 'CV', 'Africa/Sao_Tome' => 'ST',
    'Indian/Comoro' => 'KM', 'Africa/Malabo' => 'GQ', 'Africa/Bangui' => 'CF', 'Africa/Nouakchott ' => 'MR',

    // Oceania
    'Australia/Sydney' => 'AU', 'Australia/Melbourne' => 'AU', 'Australia/Brisbane' => 'AU', 'Australia/Perth' => 'AU',
    'Australia/Adelaide' => 'AU', 'Australia/Darwin' => 'AU', 'Australia/Hobart' => 'AU',
    'Pacific/Auckland' => 'NZ', 'Pacific/Fiji' => 'FJ', 'Pacific/Port_Moresby' => 'PG', 'Pacific/Guadalcanal' => 'SB',
    'Pacific/Efate' => 'VU', 'Pacific/Apia' => 'WS', 'Pacific/Tongatapu' => 'TO', 'Pacific/Tarawa' => 'KI',
    'Pacific/Pohnpei' => 'FM', 'Pacific/Majuro' => 'MH', 'Pacific/Palau' => 'PW', 'Pacific/Nauru' => 'NR',
    'Pacific/Funafuti' => 'TV', 'Pacific/Tahiti' => 'PF', 'Pacific/Noumea' => 'NC', 'Pacific/Guam' => 'GU',
    'America/Godthab' => 'GL', 'America/Nuuk' => 'GL',
];
