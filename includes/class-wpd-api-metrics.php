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
	private static string $last_cache_source = '';
	private static int $last_http_status = 0;
	private static string $last_error = '';
	private static array $request_started = array();

	/** Registers HTTP timing hooks. */
	public static function register(): void {
		add_filter( 'http_request_args', array( self::class, 'start_http' ), 10, 2 );
		add_action( 'http_api_debug', array( self::class, 'observe_http' ), 10, 5 );
	}

	/** Starts timing a Piwigo HTTP request. */
	public static function start_http( array $args, string $url ): array {
		if ( self::is_piwigo_url( $url ) ) {
			self::$request_started[ self::request_key( $url, $args ) ] = microtime( true );
		}
		return $args;
	}

	/** Observes a completed Piwigo HTTP request without retaining credentials. */
	public static function observe_http( $response, string $context, string $class, array $args, string $url ): void {
		unset( $class );
		if ( 'response' !== $context || ! self::is_piwigo_url( $url ) ) {
			return;
		}

		$key        = self::request_key( $url, $args );
		$started    = self::$request_started[ $key ] ?? microtime( true );
		$elapsed_ms = ( microtime( true ) - $started ) * 1000;
		unset( self::$request_started[ $key ] );

		$method = '';
		if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
			$method = sanitize_text_field( (string) ( $args['body']['method'] ?? '' ) );
		}
		if ( '' === $method ) {
			$method = sanitize_text_field( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		}

		$status = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		$error  = is_wp_error( $response ) ? $response->get_error_message() : '';
		self::api_call( $method, $elapsed_ms, $status, $error );
	}

	/** Records a plugin cache hit. */
	public static function cache_hit( string $source ): void {
		++self::$cache_hits;
		self::$last_cache_source = sanitize_key( $source );
	}

	/** Records a plugin cache miss. */
	public static function cache_miss( string $source ): void {
		++self::$cache_misses;
		self::$last_cache_source = sanitize_key( $source );
	}

	/** Records one actual HTTP API request. */
	public static function api_call( string $method, float $elapsed_ms, int $http_status = 0, string $error = '' ): void {
		++self::$api_calls;
		self::$elapsed_ms      += max( 0.0, $elapsed_ms );
		self::$slowest_ms       = max( self::$slowest_ms, $elapsed_ms );
		self::$last_method      = sanitize_text_field( $method );
		self::$last_http_status = absint( $http_status );
		self::$last_error       = sanitize_text_field( $error );
	}

	/** Returns the current request metrics. */
	public static function snapshot(): array {
		$total_cache = self::$cache_hits + self::$cache_misses;
		return array(
			'api_calls'         => self::$api_calls,
			'cache_hits'        => self::$cache_hits,
			'cache_misses'      => self::$cache_misses,
			'cache_hit_rate'    => 0 < $total_cache ? round( ( self::$cache_hits / $total_cache ) * 100, 1 ) : 0.0,
			'elapsed_ms'        => round( self::$elapsed_ms, 1 ),
			'average_ms'        => 0 < self::$api_calls ? round( self::$elapsed_ms / self::$api_calls, 1 ) : 0.0,
			'slowest_ms'        => round( self::$slowest_ms, 1 ),
			'last_method'       => self::$last_method,
			'last_cache_source' => self::$last_cache_source,
			'last_http_status'  => self::$last_http_status,
			'last_error'        => self::$last_error,
		);
	}

	/** Returns whether an HTTP URL targets the configured Piwigo instance. */
	private static function is_piwigo_url( string $url ): bool {
		$base_url = WPD_Settings::get_piwigo_url();
		return '' !== $base_url && str_starts_with( untrailingslashit( $url ), untrailingslashit( $base_url ) );
	}

	/** Builds a request-local timing key without retaining request bodies. */
	private static function request_key( string $url, array $args ): string {
		$method = '';
		if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
			$method = (string) ( $args['body']['method'] ?? '' );
		}
		return md5( $url . '|' . $method );
	}
}
