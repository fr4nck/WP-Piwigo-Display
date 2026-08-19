<?php
/**
 * API and cache diagnostic metrics.
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
	private const TRANSIENT_KEY = 'wpd_api_health_metrics';
	private const TRANSIENT_TTL = 7 * DAY_IN_SECONDS;

	/**
	 * API calls performed during the current request.
	 *
	 * @var int
	 */
	private static int $api_calls = 0;

	/**
	 * Plugin cache hits during the current request.
	 *
	 * @var int
	 */
	private static int $cache_hits = 0;

	/**
	 * Plugin cache misses during the current request.
	 *
	 * @var int
	 */
	private static int $cache_misses = 0;

	/**
	 * Cumulative Piwigo HTTP time during the current request.
	 *
	 * @var float
	 */
	private static float $elapsed_ms = 0.0;

	/**
	 * Slowest Piwigo HTTP request during the current request.
	 *
	 * @var float
	 */
	private static float $slowest_ms = 0.0;

	/**
	 * Last Piwigo API method observed.
	 *
	 * @var string
	 */
	private static string $last_method = '';

	/**
	 * Last plugin cache layer observed.
	 *
	 * @var string
	 */
	private static string $last_cache_source = '';

	/**
	 * Last Piwigo HTTP status observed.
	 *
	 * @var int
	 */
	private static int $last_http_status = 0;

	/**
	 * Last Piwigo HTTP error observed.
	 *
	 * @var string
	 */
	private static string $last_error = '';

	/**
	 * Request start times indexed by an opaque local key.
	 *
	 * @var array<string, float>
	 */
	private static array $request_started = array();

	/**
	 * Registers HTTP timing, persistence and admin display hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'http_request_args', array( self::class, 'start_http' ), 10, 2 );
		add_action( 'http_api_debug', array( self::class, 'observe_http' ), 10, 5 );
		add_action( 'shutdown', array( self::class, 'persist' ) );
		add_action( 'admin_notices', array( self::class, 'render_admin_health' ) );
	}

	/**
	 * Starts timing a Piwigo HTTP request.
	 *
	 * @param array<string, mixed> $args HTTP request arguments.
	 * @param string               $url  Request URL.
	 * @return array<string, mixed>
	 */
	public static function start_http( array $args, string $url ): array {
		if ( self::is_piwigo_url( $url ) ) {
			self::$request_started[ self::request_key( $url, $args ) ] = microtime( true );
		}
		return $args;
	}

	/**
	 * Observes a completed Piwigo HTTP request without retaining credentials.
	 *
	 * @param mixed                $response  HTTP response or WP_Error.
	 * @param string               $context   HTTP API debug context.
	 * @param string               $transport Transport class name.
	 * @param array<string, mixed> $args      Request arguments.
	 * @param string               $url       Requested URL.
	 * @return void
	 */
	public static function observe_http( $response, string $context, string $transport, array $args, string $url ): void {
		unset( $transport );
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

	/**
	 * Records a plugin cache hit.
	 *
	 * @param string $source Cache layer that served the value.
	 * @return void
	 */
	public static function cache_hit( string $source ): void {
		++self::$cache_hits;
		self::$last_cache_source = sanitize_key( $source );
	}

	/**
	 * Records a plugin cache miss.
	 *
	 * @param string $source Cache layer that missed.
	 * @return void
	 */
	public static function cache_miss( string $source ): void {
		++self::$cache_misses;
		self::$last_cache_source = sanitize_key( $source );
	}

	/**
	 * Records one actual HTTP API request.
	 *
	 * @param string $method      Piwigo API method.
	 * @param float  $elapsed_ms  Request duration in milliseconds.
	 * @param int    $http_status HTTP response status.
	 * @param string $error       Sanitized error message.
	 * @return void
	 */
	public static function api_call( string $method, float $elapsed_ms, int $http_status = 0, string $error = '' ): void {
		++self::$api_calls;
		self::$elapsed_ms      += max( 0.0, $elapsed_ms );
		self::$slowest_ms       = max( self::$slowest_ms, $elapsed_ms );
		self::$last_method      = sanitize_text_field( $method );
		self::$last_http_status = absint( $http_status );
		self::$last_error       = sanitize_text_field( $error );
	}

	/**
	 * Persists one aggregated record per PHP request.
	 *
	 * @return void
	 */
	public static function persist(): void {
		if ( 0 === self::$api_calls && 0 === self::$cache_hits && 0 === self::$cache_misses ) {
			return;
		}

		$stored = get_transient( self::TRANSIENT_KEY );
		if ( ! is_array( $stored ) ) {
			$stored = self::empty_summary();
		}

		$stored['api_calls']    = absint( $stored['api_calls'] ?? 0 ) + self::$api_calls;
		$stored['cache_hits']   = absint( $stored['cache_hits'] ?? 0 ) + self::$cache_hits;
		$stored['cache_misses'] = absint( $stored['cache_misses'] ?? 0 ) + self::$cache_misses;
		$stored['elapsed_ms']   = (float) ( $stored['elapsed_ms'] ?? 0.0 ) + self::$elapsed_ms;
		$stored['slowest_ms']   = max( (float) ( $stored['slowest_ms'] ?? 0.0 ), self::$slowest_ms );
		$stored['updated_at']   = time();
		$stored['started_at']   = absint( $stored['started_at'] ?? 0 );

		if ( 0 === $stored['started_at'] ) {
			$stored['started_at'] = time();
		}
		if ( '' !== self::$last_method ) {
			$stored['last_method'] = self::$last_method;
		}
		if ( '' !== self::$last_cache_source ) {
			$stored['last_cache_source'] = self::$last_cache_source;
		}
		if ( 0 < self::$last_http_status ) {
			$stored['last_http_status'] = self::$last_http_status;
		}
		if ( '' !== self::$last_error ) {
			$stored['last_error'] = self::$last_error;
		}

		set_transient( self::TRANSIENT_KEY, $stored, self::TRANSIENT_TTL );
	}

	/**
	 * Returns persisted metrics plus the current request.
	 *
	 * @return array<string, int|float|string>
	 */
	public static function summary(): array {
		$stored = get_transient( self::TRANSIENT_KEY );
		if ( ! is_array( $stored ) ) {
			$stored = self::empty_summary();
		}

		$api_calls    = absint( $stored['api_calls'] ?? 0 ) + self::$api_calls;
		$cache_hits   = absint( $stored['cache_hits'] ?? 0 ) + self::$cache_hits;
		$cache_misses = absint( $stored['cache_misses'] ?? 0 ) + self::$cache_misses;
		$elapsed_ms   = (float) ( $stored['elapsed_ms'] ?? 0.0 ) + self::$elapsed_ms;
		$total_cache  = $cache_hits + $cache_misses;

		return array(
			'api_calls'         => $api_calls,
			'cache_hits'        => $cache_hits,
			'cache_misses'      => $cache_misses,
			'cache_hit_rate'    => 0 < $total_cache ? round( ( $cache_hits / $total_cache ) * 100, 1 ) : 0.0,
			'elapsed_ms'        => round( $elapsed_ms, 1 ),
			'average_ms'        => 0 < $api_calls ? round( $elapsed_ms / $api_calls, 1 ) : 0.0,
			'slowest_ms'        => round( max( (float) ( $stored['slowest_ms'] ?? 0.0 ), self::$slowest_ms ), 1 ),
			'last_method'       => '' !== self::$last_method ? self::$last_method : sanitize_text_field( (string) ( $stored['last_method'] ?? '' ) ),
			'last_cache_source' => '' !== self::$last_cache_source ? self::$last_cache_source : sanitize_key( (string) ( $stored['last_cache_source'] ?? '' ) ),
			'last_http_status'  => 0 < self::$last_http_status ? self::$last_http_status : absint( $stored['last_http_status'] ?? 0 ),
			'last_error'        => '' !== self::$last_error ? self::$last_error : sanitize_text_field( (string) ( $stored['last_error'] ?? '' ) ),
			'started_at'        => absint( $stored['started_at'] ?? 0 ),
			'updated_at'        => absint( $stored['updated_at'] ?? 0 ),
		);
	}

	/**
	 * Renders the persistent health summary at the top of the Diagnostic page.
	 *
	 * @return void
	 */
	public static function render_admin_health(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page routing.
		if ( 'wp-piwigo-display-diagnostic' !== $page ) {
			return;
		}

		$metrics     = self::summary();
		$status      = self::health_status( $metrics );
		$last_method = '' !== (string) $metrics['last_method'] ? (string) $metrics['last_method'] : '—';
		?>
		<div class="notice notice-info wpd-api-health">
			<h2><?php esc_html_e( 'Santé API & cache', 'wp-piwigo-display' ); ?></h2>
			<p><strong><?php echo esc_html( $status ); ?></strong></p>
			<p><?php echo esc_html( sprintf( /* translators: 1: API calls, 2: cache hits, 3: cache misses, 4: cache hit rate, 5: cumulative API milliseconds, 6: average milliseconds, 7: slowest milliseconds. */ __( '%1$d appel(s) API — cache %2$d HIT / %3$d MISS (%4$s%%) — API %5$s ms cumulées, %6$s ms en moyenne, %7$s ms au plus lent.', 'wp-piwigo-display' ), $metrics['api_calls'], $metrics['cache_hits'], $metrics['cache_misses'], $metrics['cache_hit_rate'], $metrics['elapsed_ms'], $metrics['average_ms'], $metrics['slowest_ms'] ) ); ?></p>
			<?php if ( '' !== $metrics['last_method'] || 0 < $metrics['last_http_status'] ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: 1: last Piwigo API method, 2: HTTP status code. */ __( 'Dernier appel : %1$s — HTTP %2$d.', 'wp-piwigo-display' ), $last_method, $metrics['last_http_status'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $metrics['last_error'] ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: %s: last sanitized Piwigo HTTP error. */ __( 'Dernière erreur : %s', 'wp-piwigo-display' ), $metrics['last_error'] ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Returns whether an HTTP URL targets the configured Piwigo instance.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private static function is_piwigo_url( string $url ): bool {
		$base_url = WPD_Settings::get_piwigo_url();
		return '' !== $base_url && str_starts_with( untrailingslashit( $url ), untrailingslashit( $base_url ) );
	}

	/**
	 * Builds a request-local timing key without retaining request bodies.
	 *
	 * @param string               $url  Request URL.
	 * @param array<string, mixed> $args HTTP request arguments.
	 * @return string
	 */
	private static function request_key( string $url, array $args ): string {
		$method = '';
		if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
			$method = (string) ( $args['body']['method'] ?? '' );
		}
		return md5( $url . '|' . $method );
	}

	/**
	 * Returns an empty persisted summary structure.
	 *
	 * @return array<string, int|float|string>
	 */
	private static function empty_summary(): array {
		return array(
			'api_calls'         => 0,
			'cache_hits'        => 0,
			'cache_misses'      => 0,
			'elapsed_ms'        => 0.0,
			'slowest_ms'        => 0.0,
			'last_method'       => '',
			'last_cache_source' => '',
			'last_http_status'  => 0,
			'last_error'        => '',
			'started_at'        => time(),
			'updated_at'        => 0,
		);
	}

	/**
	 * Produces a concise health verdict.
	 *
	 * @param array<string, int|float|string> $metrics Aggregated health metrics.
	 * @return string
	 */
	private static function health_status( array $metrics ): string {
		if ( '' !== $metrics['last_error'] || ( 0 < $metrics['last_http_status'] && 400 <= $metrics['last_http_status'] ) ) {
			return __( '🔴 Erreur API détectée', 'wp-piwigo-display' );
		}
		if ( 2000 < $metrics['slowest_ms'] || ( 10 <= $metrics['cache_misses'] && 0.0 === (float) $metrics['cache_hit_rate'] ) ) {
			return __( '🟠 Santé dégradée', 'wp-piwigo-display' );
		}
		return __( '🟢 API et cache opérationnels', 'wp-piwigo-display' );
	}
}
