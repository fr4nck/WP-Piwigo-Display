<?php
/**
 * Request-level API diagnostic metrics.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects lightweight, credential-free API/cache metrics for diagnostics.
 */
final class WPD_Api_Metrics {
	private static int $api_calls = 0;
	private static int $cache_hits = 0;
	private static int $cache_misses = 0;
	private static float $elapsed_ms = 0.0;
	private static float $slowest_ms = 0.0;
	private static string $last_method = '';
	private static int $last_http_status = 0;
	private static string $last_error = '';

	/** Records a request-cache hit. */
	public static function cache_hit( string $method ): void {
		++self::$cache_hits;
		self::$last_method = sanitize_key( $method );
	}

	/** Records a request-cache miss. */
	public static function cache_miss( string $method ): void {
		++self::$cache_misses;
		self::$last_method = sanitize_key( $method );
	}

	/** Records one actual HTTP API request. */
	public static function api_call( string $method, float $elapsed_ms, int $http_status = 0, string $error = '' ): void {
		++self::$api_calls;
		self::$elapsed_ms      += max( 0.0, $elapsed_ms );
		self::$slowest_ms       = max( self::$slowest_ms, $elapsed_ms );
		self::$last_method      = sanitize_key( $method );
		self::$last_http_status = absint( $http_status );
		self::$last_error       = sanitize_text_field( $error );
	}

	/** Returns the current request metrics. */
	public static function snapshot(): array {
		$total_cache = self::$cache_hits + self::$cache_misses;
		return array(
			'api_calls'        => self::$api_calls,
			'cache_hits'       => self::$cache_hits,
			'cache_misses'     => self::$cache_misses,
			'cache_hit_rate'   => 0 < $total_cache ? round( ( self::$cache_hits / $total_cache ) * 100, 1 ) : 0.0,
			'elapsed_ms'       => round( self::$elapsed_ms, 1 ),
			'average_ms'       => 0 < self::$api_calls ? round( self::$elapsed_ms / self::$api_calls, 1 ) : 0.0,
			'slowest_ms'       => round( self::$slowest_ms, 1 ),
			'last_method'      => self::$last_method,
			'last_http_status' => self::$last_http_status,
			'last_error'       => self::$last_error,
		);
	}
}
