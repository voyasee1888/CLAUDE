<?php
/**
 * Default destination dataset auto-seeded on first activation (only if the
 * destinations table is empty — see V3DA_DB::maybe_seed_defaults()).
 *
 * content_term_slug is set to the destination's own slug as a best-guess
 * default, since this plugin has no way to know this site's real category
 * taxonomy. Edit each destination's "Content term slug" field in
 * 3D Atlas → Destinations to point at the matching real category/tag once
 * one exists — until then, related articles simply show none and the
 * public-facing link falls back to a site search for the destination name
 * (see templates/atlas.php), so nothing is ever broken or hidden.
 *
 * Coordinates are approximate city-center points, sufficient for globe
 * marker placement.
 */

defined('ABSPATH') || exit;

return [
    // --- Europe ---
    ['name' => 'Paris', 'country' => 'France', 'country_code' => 'FR', 'region' => 'Europe', 'lat' => 48.8566, 'lng' => 2.3522],
    ['name' => 'London', 'country' => 'United Kingdom', 'country_code' => 'GB', 'region' => 'Europe', 'lat' => 51.5072, 'lng' => -0.1276],
    ['name' => 'Rome', 'country' => 'Italy', 'country_code' => 'IT', 'region' => 'Europe', 'lat' => 41.9028, 'lng' => 12.4964],
    ['name' => 'Barcelona', 'country' => 'Spain', 'country_code' => 'ES', 'region' => 'Europe', 'lat' => 41.3874, 'lng' => 2.1686],
    ['name' => 'Amsterdam', 'country' => 'Netherlands', 'country_code' => 'NL', 'region' => 'Europe', 'lat' => 52.3676, 'lng' => 4.9041],
    ['name' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'region' => 'Europe', 'lat' => 52.5200, 'lng' => 13.4050],
    ['name' => 'Prague', 'country' => 'Czech Republic', 'country_code' => 'CZ', 'region' => 'Europe', 'lat' => 50.0755, 'lng' => 14.4378],
    ['name' => 'Vienna', 'country' => 'Austria', 'country_code' => 'AT', 'region' => 'Europe', 'lat' => 48.2082, 'lng' => 16.3738],
    ['name' => 'Lisbon', 'country' => 'Portugal', 'country_code' => 'PT', 'region' => 'Europe', 'lat' => 38.7223, 'lng' => -9.1393],
    ['name' => 'Athens', 'country' => 'Greece', 'country_code' => 'GR', 'region' => 'Europe', 'lat' => 37.9838, 'lng' => 23.7275],
    ['name' => 'Venice', 'country' => 'Italy', 'country_code' => 'IT', 'region' => 'Europe', 'lat' => 45.4408, 'lng' => 12.3155],
    ['name' => 'Florence', 'country' => 'Italy', 'country_code' => 'IT', 'region' => 'Europe', 'lat' => 43.7696, 'lng' => 11.2558],
    ['name' => 'Santorini', 'country' => 'Greece', 'country_code' => 'GR', 'region' => 'Europe', 'lat' => 36.3932, 'lng' => 25.4615],
    ['name' => 'Dubrovnik', 'country' => 'Croatia', 'country_code' => 'HR', 'region' => 'Europe', 'lat' => 42.6507, 'lng' => 18.0944],
    ['name' => 'Reykjavik', 'country' => 'Iceland', 'country_code' => 'IS', 'region' => 'Europe', 'lat' => 64.1466, 'lng' => -21.9426],
    ['name' => 'Copenhagen', 'country' => 'Denmark', 'country_code' => 'DK', 'region' => 'Europe', 'lat' => 55.6761, 'lng' => 12.5683],
    ['name' => 'Stockholm', 'country' => 'Sweden', 'country_code' => 'SE', 'region' => 'Europe', 'lat' => 59.3293, 'lng' => 18.0686],
    ['name' => 'Budapest', 'country' => 'Hungary', 'country_code' => 'HU', 'region' => 'Europe', 'lat' => 47.4979, 'lng' => 19.0402],
    ['name' => 'Edinburgh', 'country' => 'United Kingdom', 'country_code' => 'GB', 'region' => 'Europe', 'lat' => 55.9533, 'lng' => -3.1883],
    ['name' => 'Dublin', 'country' => 'Ireland', 'country_code' => 'IE', 'region' => 'Europe', 'lat' => 53.3498, 'lng' => -6.2603],
    ['name' => 'Zurich', 'country' => 'Switzerland', 'country_code' => 'CH', 'region' => 'Europe', 'lat' => 47.3769, 'lng' => 8.5417],
    ['name' => 'Munich', 'country' => 'Germany', 'country_code' => 'DE', 'region' => 'Europe', 'lat' => 48.1351, 'lng' => 11.5820],
    ['name' => 'Porto', 'country' => 'Portugal', 'country_code' => 'PT', 'region' => 'Europe', 'lat' => 41.1579, 'lng' => -8.6291],
    ['name' => 'Seville', 'country' => 'Spain', 'country_code' => 'ES', 'region' => 'Europe', 'lat' => 37.3891, 'lng' => -5.9845],
    ['name' => 'Krakow', 'country' => 'Poland', 'country_code' => 'PL', 'region' => 'Europe', 'lat' => 50.0647, 'lng' => 19.9450],
    ['name' => 'Interlaken', 'country' => 'Switzerland', 'country_code' => 'CH', 'region' => 'Europe', 'lat' => 46.6863, 'lng' => 7.8632],
    ['name' => 'Nice', 'country' => 'France', 'country_code' => 'FR', 'region' => 'Europe', 'lat' => 43.7102, 'lng' => 7.2620],

    // --- Asia ---
    ['name' => 'Tokyo', 'country' => 'Japan', 'country_code' => 'JP', 'region' => 'Asia', 'lat' => 35.6762, 'lng' => 139.6503],
    ['name' => 'Kyoto', 'country' => 'Japan', 'country_code' => 'JP', 'region' => 'Asia', 'lat' => 35.0116, 'lng' => 135.7681],
    ['name' => 'Osaka', 'country' => 'Japan', 'country_code' => 'JP', 'region' => 'Asia', 'lat' => 34.6937, 'lng' => 135.5023],
    ['name' => 'Seoul', 'country' => 'South Korea', 'country_code' => 'KR', 'region' => 'Asia', 'lat' => 37.5665, 'lng' => 126.9780],
    ['name' => 'Bangkok', 'country' => 'Thailand', 'country_code' => 'TH', 'region' => 'Asia', 'lat' => 13.7563, 'lng' => 100.5018],
    ['name' => 'Singapore', 'country' => 'Singapore', 'country_code' => 'SG', 'region' => 'Asia', 'lat' => 1.3521, 'lng' => 103.8198],
    ['name' => 'Bali', 'country' => 'Indonesia', 'country_code' => 'ID', 'region' => 'Asia', 'lat' => -8.4095, 'lng' => 115.1889],
    ['name' => 'Hong Kong', 'country' => 'Hong Kong', 'country_code' => 'HK', 'region' => 'Asia', 'lat' => 22.3193, 'lng' => 114.1694],
    ['name' => 'Shanghai', 'country' => 'China', 'country_code' => 'CN', 'region' => 'Asia', 'lat' => 31.2304, 'lng' => 121.4737],
    ['name' => 'Beijing', 'country' => 'China', 'country_code' => 'CN', 'region' => 'Asia', 'lat' => 39.9042, 'lng' => 116.4074],
    ['name' => 'Hanoi', 'country' => 'Vietnam', 'country_code' => 'VN', 'region' => 'Asia', 'lat' => 21.0278, 'lng' => 105.8342],
    ['name' => 'Ho Chi Minh City', 'country' => 'Vietnam', 'country_code' => 'VN', 'region' => 'Asia', 'lat' => 10.8231, 'lng' => 106.6297],
    ['name' => 'Siem Reap', 'country' => 'Cambodia', 'country_code' => 'KH', 'region' => 'Asia', 'lat' => 13.3671, 'lng' => 103.8448],
    ['name' => 'Kuala Lumpur', 'country' => 'Malaysia', 'country_code' => 'MY', 'region' => 'Asia', 'lat' => 3.1390, 'lng' => 101.6869],
    ['name' => 'Taipei', 'country' => 'Taiwan', 'country_code' => 'TW', 'region' => 'Asia', 'lat' => 25.0330, 'lng' => 121.5654],
    ['name' => 'Manila', 'country' => 'Philippines', 'country_code' => 'PH', 'region' => 'Asia', 'lat' => 14.5995, 'lng' => 120.9842],
    ['name' => 'Mumbai', 'country' => 'India', 'country_code' => 'IN', 'region' => 'Asia', 'lat' => 19.0760, 'lng' => 72.8777],
    ['name' => 'Delhi', 'country' => 'India', 'country_code' => 'IN', 'region' => 'Asia', 'lat' => 28.7041, 'lng' => 77.1025],
    ['name' => 'Jaipur', 'country' => 'India', 'country_code' => 'IN', 'region' => 'Asia', 'lat' => 26.9124, 'lng' => 75.7873],
    ['name' => 'Kathmandu', 'country' => 'Nepal', 'country_code' => 'NP', 'region' => 'Asia', 'lat' => 27.7172, 'lng' => 85.3240],
    ['name' => 'Colombo', 'country' => 'Sri Lanka', 'country_code' => 'LK', 'region' => 'Asia', 'lat' => 6.9271, 'lng' => 79.8612],
    ['name' => 'Male', 'country' => 'Maldives', 'country_code' => 'MV', 'region' => 'Asia', 'lat' => 4.1755, 'lng' => 73.5093],
    ['name' => 'Chiang Mai', 'country' => 'Thailand', 'country_code' => 'TH', 'region' => 'Asia', 'lat' => 18.7883, 'lng' => 98.9853],
    ['name' => 'Phuket', 'country' => 'Thailand', 'country_code' => 'TH', 'region' => 'Asia', 'lat' => 7.8804, 'lng' => 98.3923],
    ['name' => 'Boracay', 'country' => 'Philippines', 'country_code' => 'PH', 'region' => 'Asia', 'lat' => 11.9674, 'lng' => 121.9248],

    // --- Middle East ---
    ['name' => 'Dubai', 'country' => 'United Arab Emirates', 'country_code' => 'AE', 'region' => 'Middle East', 'lat' => 25.2048, 'lng' => 55.2708],
    ['name' => 'Abu Dhabi', 'country' => 'United Arab Emirates', 'country_code' => 'AE', 'region' => 'Middle East', 'lat' => 24.4539, 'lng' => 54.3773],
    ['name' => 'Doha', 'country' => 'Qatar', 'country_code' => 'QA', 'region' => 'Middle East', 'lat' => 25.2854, 'lng' => 51.5310],
    ['name' => 'Istanbul', 'country' => 'Turkey', 'country_code' => 'TR', 'region' => 'Middle East', 'lat' => 41.0082, 'lng' => 28.9784],
    ['name' => 'Jerusalem', 'country' => 'Israel', 'country_code' => 'IL', 'region' => 'Middle East', 'lat' => 31.7683, 'lng' => 35.2137],
    ['name' => 'Amman', 'country' => 'Jordan', 'country_code' => 'JO', 'region' => 'Middle East', 'lat' => 31.9454, 'lng' => 35.9284],
    ['name' => 'Muscat', 'country' => 'Oman', 'country_code' => 'OM', 'region' => 'Middle East', 'lat' => 23.5880, 'lng' => 58.3829],
    ['name' => 'Riyadh', 'country' => 'Saudi Arabia', 'country_code' => 'SA', 'region' => 'Middle East', 'lat' => 24.7136, 'lng' => 46.6753],
    ['name' => 'Petra', 'country' => 'Jordan', 'country_code' => 'JO', 'region' => 'Middle East', 'lat' => 30.3285, 'lng' => 35.4444],

    // --- Africa ---
    ['name' => 'Cape Town', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Africa', 'lat' => -33.9249, 'lng' => 18.4241],
    ['name' => 'Marrakech', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'Africa', 'lat' => 31.6295, 'lng' => -7.9811],
    ['name' => 'Cairo', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'Africa', 'lat' => 30.0444, 'lng' => 31.2357],
    ['name' => 'Nairobi', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'Africa', 'lat' => -1.2921, 'lng' => 36.8219],
    ['name' => 'Zanzibar City', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'Africa', 'lat' => -6.1659, 'lng' => 39.2026],
    ['name' => 'Victoria Falls', 'country' => 'Zambia', 'country_code' => 'ZM', 'region' => 'Africa', 'lat' => -17.9243, 'lng' => 25.8572],
    ['name' => 'Accra', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'Africa', 'lat' => 5.6037, 'lng' => -0.1870],
    ['name' => 'Lagos', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'Africa', 'lat' => 6.5244, 'lng' => 3.3792],
    ['name' => 'Tunis', 'country' => 'Tunisia', 'country_code' => 'TN', 'region' => 'Africa', 'lat' => 36.8065, 'lng' => 10.1815],
    ['name' => 'Casablanca', 'country' => 'Morocco', 'country_code' => 'MA', 'region' => 'Africa', 'lat' => 33.5731, 'lng' => -7.5898],
    ['name' => 'Victoria', 'country' => 'Seychelles', 'country_code' => 'SC', 'region' => 'Africa', 'lat' => -4.6191, 'lng' => 55.4513],
    ['name' => 'Port Louis', 'country' => 'Mauritius', 'country_code' => 'MU', 'region' => 'Africa', 'lat' => -20.1609, 'lng' => 57.5012],
    ['name' => 'Luxor', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'Africa', 'lat' => 25.6872, 'lng' => 32.6396],

    // --- North America ---
    ['name' => 'New York City', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 40.7128, 'lng' => -74.0060],
    ['name' => 'Los Angeles', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 34.0522, 'lng' => -118.2437],
    ['name' => 'San Francisco', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 37.7749, 'lng' => -122.4194],
    ['name' => 'Las Vegas', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 36.1699, 'lng' => -115.1398],
    ['name' => 'Miami', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 25.7617, 'lng' => -80.1918],
    ['name' => 'Chicago', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 41.8781, 'lng' => -87.6298],
    ['name' => 'New Orleans', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 29.9511, 'lng' => -90.0715],
    ['name' => 'Toronto', 'country' => 'Canada', 'country_code' => 'CA', 'region' => 'North America', 'lat' => 43.6532, 'lng' => -79.3832],
    ['name' => 'Vancouver', 'country' => 'Canada', 'country_code' => 'CA', 'region' => 'North America', 'lat' => 49.2827, 'lng' => -123.1207],
    ['name' => 'Montreal', 'country' => 'Canada', 'country_code' => 'CA', 'region' => 'North America', 'lat' => 45.5019, 'lng' => -73.5674],
    ['name' => 'Banff', 'country' => 'Canada', 'country_code' => 'CA', 'region' => 'North America', 'lat' => 51.1784, 'lng' => -115.5708],
    ['name' => 'Mexico City', 'country' => 'Mexico', 'country_code' => 'MX', 'region' => 'North America', 'lat' => 19.4326, 'lng' => -99.1332],
    ['name' => 'Cancun', 'country' => 'Mexico', 'country_code' => 'MX', 'region' => 'North America', 'lat' => 21.1619, 'lng' => -86.8515],
    ['name' => 'Tulum', 'country' => 'Mexico', 'country_code' => 'MX', 'region' => 'North America', 'lat' => 20.2114, 'lng' => -87.4654],
    ['name' => 'Honolulu', 'country' => 'United States', 'country_code' => 'US', 'region' => 'North America', 'lat' => 21.3069, 'lng' => -157.8583],

    // --- Caribbean ---
    ['name' => 'Havana', 'country' => 'Cuba', 'country_code' => 'CU', 'region' => 'Caribbean', 'lat' => 23.1136, 'lng' => -82.3666],
    ['name' => 'Nassau', 'country' => 'Bahamas', 'country_code' => 'BS', 'region' => 'Caribbean', 'lat' => 25.0343, 'lng' => -77.3963],
    ['name' => 'Punta Cana', 'country' => 'Dominican Republic', 'country_code' => 'DO', 'region' => 'Caribbean', 'lat' => 18.5820, 'lng' => -68.4055],
    ['name' => 'Montego Bay', 'country' => 'Jamaica', 'country_code' => 'JM', 'region' => 'Caribbean', 'lat' => 18.4762, 'lng' => -77.8939],
    ['name' => 'San Juan', 'country' => 'Puerto Rico', 'country_code' => 'PR', 'region' => 'Caribbean', 'lat' => 18.4655, 'lng' => -66.1057],
    ['name' => 'Bridgetown', 'country' => 'Barbados', 'country_code' => 'BB', 'region' => 'Caribbean', 'lat' => 13.1132, 'lng' => -59.5988],
    ['name' => 'Oranjestad', 'country' => 'Aruba', 'country_code' => 'AW', 'region' => 'Caribbean', 'lat' => 12.5092, 'lng' => -70.0086],
    ['name' => 'Castries', 'country' => 'Saint Lucia', 'country_code' => 'LC', 'region' => 'Caribbean', 'lat' => 14.0101, 'lng' => -60.9875],

    // --- South America ---
    ['name' => 'Rio de Janeiro', 'country' => 'Brazil', 'country_code' => 'BR', 'region' => 'South America', 'lat' => -22.9068, 'lng' => -43.1729],
    ['name' => 'Buenos Aires', 'country' => 'Argentina', 'country_code' => 'AR', 'region' => 'South America', 'lat' => -34.6037, 'lng' => -58.3816],
    ['name' => 'Lima', 'country' => 'Peru', 'country_code' => 'PE', 'region' => 'South America', 'lat' => -12.0464, 'lng' => -77.0428],
    ['name' => 'Cusco', 'country' => 'Peru', 'country_code' => 'PE', 'region' => 'South America', 'lat' => -13.5320, 'lng' => -71.9675],
    ['name' => 'Santiago', 'country' => 'Chile', 'country_code' => 'CL', 'region' => 'South America', 'lat' => -33.4489, 'lng' => -70.6693],
    ['name' => 'Bogota', 'country' => 'Colombia', 'country_code' => 'CO', 'region' => 'South America', 'lat' => 4.7110, 'lng' => -74.0721],
    ['name' => 'Cartagena', 'country' => 'Colombia', 'country_code' => 'CO', 'region' => 'South America', 'lat' => 10.3910, 'lng' => -75.4794],
    ['name' => 'Quito', 'country' => 'Ecuador', 'country_code' => 'EC', 'region' => 'South America', 'lat' => -0.1807, 'lng' => -78.4678],
    ['name' => 'Puerto Ayora', 'country' => 'Ecuador', 'country_code' => 'EC', 'region' => 'South America', 'lat' => -0.7393, 'lng' => -90.3518],
    ['name' => 'Montevideo', 'country' => 'Uruguay', 'country_code' => 'UY', 'region' => 'South America', 'lat' => -34.9011, 'lng' => -56.1645],
    ['name' => 'Salvador', 'country' => 'Brazil', 'country_code' => 'BR', 'region' => 'South America', 'lat' => -12.9777, 'lng' => -38.5016],

    // --- Oceania ---
    ['name' => 'Sydney', 'country' => 'Australia', 'country_code' => 'AU', 'region' => 'Oceania', 'lat' => -33.8688, 'lng' => 151.2093],
    ['name' => 'Melbourne', 'country' => 'Australia', 'country_code' => 'AU', 'region' => 'Oceania', 'lat' => -37.8136, 'lng' => 144.9631],
    ['name' => 'Auckland', 'country' => 'New Zealand', 'country_code' => 'NZ', 'region' => 'Oceania', 'lat' => -36.8485, 'lng' => 174.7633],
    ['name' => 'Queenstown', 'country' => 'New Zealand', 'country_code' => 'NZ', 'region' => 'Oceania', 'lat' => -45.0312, 'lng' => 168.6626],
    ['name' => 'Suva', 'country' => 'Fiji', 'country_code' => 'FJ', 'region' => 'Oceania', 'lat' => -18.1416, 'lng' => 178.4419],
    ['name' => 'Bora Bora', 'country' => 'French Polynesia', 'country_code' => 'PF', 'region' => 'Oceania', 'lat' => -16.5004, 'lng' => -151.7415],
    ['name' => 'Gold Coast', 'country' => 'Australia', 'country_code' => 'AU', 'region' => 'Oceania', 'lat' => -28.0167, 'lng' => 153.4000],
    ['name' => 'Perth', 'country' => 'Australia', 'country_code' => 'AU', 'region' => 'Oceania', 'lat' => -31.9505, 'lng' => 115.8605],
    ['name' => 'Wellington', 'country' => 'New Zealand', 'country_code' => 'NZ', 'region' => 'Oceania', 'lat' => -41.2865, 'lng' => 174.7762],
];
