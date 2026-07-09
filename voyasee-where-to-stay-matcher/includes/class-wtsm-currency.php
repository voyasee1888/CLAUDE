<?php
/**
 * WTSM_Currency
 *
 * Live USD-based exchange rates from Frankfurter (frankfurter.dev),
 * tracking European Central Bank reference rates. Free, open-source, no
 * API key, no rate limit for normal use, explicitly unrestricted
 * commercial use -- see https://frankfurter.dev/.
 *
 * Never called on a live visitor request directly for a *fresh* fetch --
 * results are cached in a transient for 12 hours, and a request that hits
 * a cold cache still only costs one small JSON call (this endpoint is
 * lightweight and fast, unlike Overpass, so no batch/cron job is needed
 * for it).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WTSM_Currency {

	const ENDPOINT  = 'https://api.frankfurter.dev/v1/latest';
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Get the current rate to convert 1 USD into $currency_code.
	 * Returns null (not a guessed number) if the currency is unknown to
	 * Frankfurter, or the request fails for any reason.
	 */
	public static function usd_to( $currency_code ) {
		$currency_code = strtoupper( trim( (string) $currency_code ) );
		if ( '' === $currency_code || 'USD' === $currency_code ) {
			return 'USD' === $currency_code ? 1.0 : null;
		}

		$cache_key = 'wtsm_fx_usd_' . $currency_code;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return ( '' === $cached ) ? null : (float) $cached;
		}

		$url = add_query_arg(
			array( 'base' => 'USD', 'symbols' => $currency_code ),
			self::ENDPOINT
		);

		$response = wp_remote_get( $url, array( 'timeout' => 8 ) );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Negative-cache briefly so a currency that errors (unsupported
			// code, transient network issue) doesn't retry on every page
			// load in the same few minutes.
			set_transient( $cache_key, '', 10 * MINUTE_IN_SECONDS );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$rate = $body['rates'][ $currency_code ] ?? null;

		if ( null === $rate ) {
			set_transient( $cache_key, '', 10 * MINUTE_IN_SECONDS );
			return null;
		}

		set_transient( $cache_key, (string) $rate, self::CACHE_TTL );
		return (float) $rate;
	}

	/**
	 * Rough per-night price estimate in a destination's local currency,
	 * derived from the 1-5 price_band scale using simple, clearly
	 * approximate USD anchors per band, adjusted by the destination's
	 * cost_index so "$$$" in Tokyo reads differently from "$$$" in Hanoi.
	 *
	 * @param int    $price_band      1-5 neighborhood price band.
	 * @param string $currency_code   ISO currency code.
	 * @param string $currency_symbol Display symbol.
	 * @param int    $cost_index      1-5 destination cost level (3 = global median).
	 * @return array{low:int,high:int,currency:string,symbol:string}|null
	 */
	public static function estimate_nightly_range( $price_band, $currency_code, $currency_symbol, $cost_index = 3 ) {
		$bands = array(
			1 => array( 20, 45 ),
			2 => array( 40, 80 ),
			3 => array( 75, 150 ),
			4 => array( 140, 280 ),
			5 => array( 260, 600 ),
		);
		$band = $bands[ (int) $price_band ] ?? $bands[3];

		$cost_index = max( 1, min( 5, (int) $cost_index ) );
		$multiplier = 1.0 + ( ( $cost_index - 3 ) * 0.25 );

		$rate = self::usd_to( $currency_code );
		if ( null === $rate ) {
			return null;
		}

		return array(
			'low'      => (int) round( $band[0] * $multiplier * $rate ),
			'high'     => (int) round( $band[1] * $multiplier * $rate ),
			'currency' => strtoupper( $currency_code ),
			'symbol'   => $currency_symbol ?: strtoupper( $currency_code ),
		);
	}
}
