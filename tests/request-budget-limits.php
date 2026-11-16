<?php
/**
 * S-04: deterministic HTTP, elapsed-time and image-volume budget tests.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

class WP_Error {
	private string $code;

	public function __construct( string $code = '', string $message = '' ) {
		unset( $message );
		$this->code = $code;
	}

	public function get_error_code(): string {
		return $this->code;
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function __( string $value, string $domain = '' ): string {
	unset( $domain );
	return $value;
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-request-budget.php';

$assert = static function ( bool $pass, string $message ): void {
	if ( ! $pass ) {
		fwrite( STDERR, 'S04=FAIL ' . $message . PHP_EOL );
		exit( 1 );
	}
};

$requests = new WPD_Request_Budget();
for ( $i = 0; $i < 32; ++$i ) {
	$timeout = $requests->reserve_http_call();
	$assert( is_int( $timeout ) && 1 <= $timeout && 10 >= $timeout, 'Valid requests must have a bounded timeout.' );
}
$limit = $requests->reserve_http_call();
$assert( is_wp_error( $limit ) && 'wpd_api_budget_exceeded' === $limit->get_error_code(), 'A 33rd HTTP request must fail.' );

$rows = new WPD_Request_Budget();
for ( $i = 0; $i < 20; ++$i ) {
	$assert( true === $rows->accept_page( 500, 500 ), 'A normal page below 10000 rows must succeed.' );
}
$limit = $rows->accept_page( 1, 500 );
$assert( is_wp_error( $limit ) && 'wpd_api_budget_exceeded' === $limit->get_error_code(), 'A page beyond 10000 rows must fail.' );

$invalid = new WPD_Request_Budget();
$assert( is_wp_error( $invalid->accept_page( 501, 500 ) ), 'A server exceeding per_page must be rejected.' );
$assert( is_wp_error( $invalid->accept_page( -1, 500 ) ), 'Negative page size must be rejected.' );

$expired = new WPD_Request_Budget( microtime( true ) - 30 );
$assert( is_wp_error( $expired->reserve_http_call() ), 'Expired time budget must fail without HTTP.' );
$assert( 8 * 1024 * 1024 === WPD_Request_Budget::MAX_RESPONSE_BYTES, 'Response-size cap changed unexpectedly.' );

echo "S04=PASS bounded HTTP calls, response bytes, image volume and elapsed time\n";
