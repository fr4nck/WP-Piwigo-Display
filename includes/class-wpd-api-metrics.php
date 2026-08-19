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

	/** Registers the HTTP observation hook. */
	public static function register(): void {
		add_action( 'http_api_debug', array( self::class, 'observe_http' ), 10, 5 );
	}

	/**
	 * Observes completed Piwigo HTTP requests without reading credentials.
	 *
	 * @param mixed  $response HTTP response or WP_Error.
	 * @param string $context  HTTP API debug context.
	 * @param string $class    Transport class name.
	 * @param array  $args     Request arguments.
	 * @param string $url      Requested URL.
	 */
	public static function observe_http( $response, string $context, string $class, array $args, string $url ): void {
		unset( $class );
		if ( 'response' !== $context ) {
			return;
		}
		$base_url = WPD_Settings::get_piwigo_url();
		if ( '' === $base_url || ! str_starts_with( untrailingslashit( $url ), untrailingslashit( $base_url ) ) ) {
			return;
		}
		$method = '';
		if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
			$method = sanitize_key( (string) ( $args['body']['method'] ?? '' ) );
		}
		if ( '' === $method ) {
			$method = sanitize_key( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		}
		$status = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		$error  = is_wp_error( $response ) ? $response->get_error_message() : '';
		self::api_call( $method, 0.0, $status, $error );
	}

	/** Records a cache hit. */
	public static function cache_hit( string $method ): void {
		++self::$cache_hits;
		self::$last_method = sanitize_key( $method );
	}

	/** Records a cache miss. */
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
