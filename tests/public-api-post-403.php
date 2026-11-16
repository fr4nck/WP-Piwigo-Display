<?php
/**
 * Real PHP regression for the RC2 public Piwigo HTTP 403 handling.
 * All HTTP and WordPress functions are stubbed; no network or secrets.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'WPD_VERSION', '3.1.0-rc.2' );

class WP_Error {
    private string $code;
    private string $message;

    public function __construct( string $code = '', string $message = '' ) {
        $this->code = $code;
        $this->message = $message;
    }
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_text_field( $value ): string { return trim( (string) $value ); }
function absint( $value ): int { return abs( (int) $value ); }
function wp_json_encode( $value ): string { return (string) json_encode( $value ); }
function untrailingslashit( string $url ): string { return rtrim( $url, '/' ); }
function esc_url_raw( string $url, array $protocols = array() ): string { return $url; }
function wp_http_validate_url( string $url ): bool { return str_starts_with( $url, 'https://public-' ); }

$GLOBALS['requests'] = array();
$GLOBALS['post_reply'] = null;
$GLOBALS['get_reply'] = null;
function wp_safe_remote_post( string $url, array $args ) {
    $GLOBALS['requests'][] = array( 'POST', $url, $args );
    return $GLOBALS['post_reply'];
}
function wp_safe_remote_get( string $url, array $args ) {
    $GLOBALS['requests'][] = array( 'GET', $url, $args );
    if ( null === $GLOBALS['get_reply'] ) {
        throw new RuntimeException( 'GET was not expected in this test' );
    }
    return $GLOBALS['get_reply'];
}
function wp_remote_retrieve_response_code( $response ): int {
    return (int) ( $response['response']['code'] ?? 0 );
}
function wp_remote_retrieve_body( $response ): string {
    return (string) ( $response['body'] ?? '' );
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-api.php';

function expect( bool $condition, string $message ): void {
    if ( ! $condition ) {
        fwrite( STDERR, "RC2 HTTP 403 regression: $message\n" );
        exit( 1 );
    }
}
function response( int $status, array|string $body = '' ): array {
    return array(
        'response' => array( 'code' => $status ),
        'body' => is_array( $body ) ? (string) json_encode( $body ) : $body,
    );
}
function prepare_case( $post, $get = null ): void {
    $GLOBALS['requests'] = array();
    $GLOBALS['post_reply'] = $post;
    $GLOBALS['get_reply'] = $get;
}
function new_api( string $id ): WPD_Api {
    return new WPD_Api( 'https://public-' . $id . '.example.test/piwigo' );
}
function request_methods(): array {
    return array_column( $GLOBALS['requests'], 0 );
}

$albums = response( 200, array( 'stat' => 'ok', 'result' => array(
    'categories' => array( array( 'id' => 154, 'name' => 'Public album' ) ),
) ) );

// The reported case: POST blocked with HTTP 403 but normal public GET works.
prepare_case( response( 403, '<html>WAF denied POST</html>' ), $albums );
$api = new_api( 'public-album' );
$found = $api->get_all_categories();
expect( is_array( $found ) && 154 === (int) ( $found[0]['id'] ?? 0 ), 'public GET must recover the album list' );
expect( array( 'POST', 'GET' ) === request_methods(), '403 must retry exactly once as GET' );
$get_url = $GLOBALS['requests'][1][1];
expect( str_contains( $get_url, '/ws.php?format=json&method=pwg.categories.getList' ), 'GET must target the same Piwigo web-service method' );
expect( str_contains( $get_url, 'recursive=1' ), 'GET must preserve recursion' );
expect( ! preg_match( '/(?:username|password|token|cookie)=/i', $get_url ), 'GET URL must not contain secrets' );
expect( ! isset( $GLOBALS['requests'][1][2]['cookies'] ) && ! isset( $GLOBALS['requests'][1][2]['body'] ), 'GET must not carry cookies or POST body' );
$again = $api->get_all_categories();
expect( $again === $found && 2 === count( $GLOBALS['requests'] ), 'recovered result must use the existing request-local cache' );

// Normal hosts keep their original POST behavior.
prepare_case( $albums );
$normal = new_api( 'no-fallback' )->get_all_categories();
expect( is_array( $normal ) && array( 'POST' ) === request_methods(), 'successful POST must not add GET' );

// No access granted by either method: preserve the actual denied status.
prepare_case( response( 403 ), response( 403 ) );
$denied = new_api( 'both-denied' )->get_all_categories();
expect( is_wp_error( $denied ) && 'wpd_http_status' === $denied->get_error_code(), 'double 403 must remain WP_Error' );
expect( str_contains( $denied->get_error_message(), 'HTTP 403' ), 'double 403 must preserve the HTTP 403 diagnosis' );
expect( array( 'POST', 'GET' ) === request_methods(), 'double 403 must not cause additional retries' );

// Network error on the GET path must not conceal the original 403.
prepare_case( response( 403 ), new WP_Error( 'timeout', 'simulated timeout' ) );
$timeout = new_api( 'get-timeout' )->get_all_categories();
expect( is_wp_error( $timeout ) && str_contains( $timeout->get_error_message(), 'HTTP 403' ), 'GET transport failure must preserve the original POST 403' );

// The numbered album path also needs GET for the photo list, not just albums.
$images = response( 200, array( 'stat' => 'ok', 'result' => array(
    'images' => array( array( 'id' => 77, 'name' => 'Fixture' ) ),
) ) );
prepare_case( response( 403 ), $images );
$image_list = new_api( 'images' )->get_images_from_album( 154 );
expect( is_array( $image_list ) && 77 === (int) ( $image_list[0]['id'] ?? 0 ), 'album 154 images must be recoverable' );
expect( array( 'POST', 'GET' ) === request_methods(), 'photo list must retry exactly once' );
$image_url = $GLOBALS['requests'][1][1];
expect( str_contains( $image_url, 'method=pwg.categories.getImages' ), 'photo GET method must match the POST' );
expect( str_contains( $image_url, 'cat_id=154' ) && str_contains( $image_url, 'per_page=500' ), 'photo GET must preserve album and pagination' );

// A Piwigo JSON failure is still a failure even when the HTTP retry succeeds.
prepare_case( response( 403 ), response( 200, array( 'stat' => 'fail', 'message' => 'not allowed' ) ) );
$api_error = new_api( 'piwigo-failure' )->get_all_categories();
expect( is_wp_error( $api_error ) && 'wpd_api_error' === $api_error->get_error_code(), 'a Piwigo stat=fail must never be treated as success' );

// Do not retry unrelated methods, despite a 403.
prepare_case( response( 403 ) );
$status = new_api( 'status' )->test_connection();
expect( is_wp_error( $status ) && array( 'POST' ) === request_methods(), 'unknown methods must not use GET' );
prepare_case( response( 403 ) );
$tagged = new_api( 'tagged' )->get_images_by_tags( array( 'public-tag' ) );
expect( is_wp_error( $tagged ) && array( 'POST' ) === request_methods(), 'tag search must not silently gain a new GET path' );

// Authenticated requests use a separate client and must never fall back to GET.
$service_code = file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-service-api.php' );
expect( is_string( $service_code ) && str_contains( $service_code, 'wp_safe_remote_post(' ), 'service account client must still use POST' );
expect( ! str_contains( (string) $service_code, 'wp_safe_remote_get(' ), 'service account must never put credentials in GET' );


// S-04: a repeated full page must stop at the global image-volume cap,
// even when the same image IDs are repeated and deduplicated in the result.
$bulk_rows = array();
for ( $i = 0; $i < 500; ++$i ) {
	$bulk_rows[] = array( 'id' => $i + 1, 'name' => 'Synthetic image' );
}
$bulk_reply = response( 200, array( 'stat' => 'ok', 'result' => array( 'images' => $bulk_rows ) ) );
prepare_case( $bulk_reply );
$bulk_result = new_api( 's04-bulk-limit' )->get_images_from_album( 154 );
expect( is_wp_error( $bulk_result ) && 'wpd_api_budget_exceeded' === $bulk_result->get_error_code(), 'Repeated full pages must fail closed at the global image cap.' );
expect( 21 === count( $GLOBALS['requests'] ), 'The 10000-row limit must stop on the 21st full page.' );
expect( WPD_Request_Budget::MAX_RESPONSE_BYTES === $GLOBALS['requests'][0][2]['limit_response_size'], 'Public POST must limit HTTP response bytes.' );

// A small explicit max should request only that many rows, not 500.
prepare_case( $images );
$limited = new_api( 's04-small-max' )->get_images_from_album( 154, 1 );
expect( is_array( $limited ) && 1 === count( $limited ), 'Explicit max=1 must still render one image.' );
expect( 1 === $GLOBALS['requests'][0][2]['body']['per_page'], 'Explicit max=1 must reduce the Piwigo page size.' );

// The budget is checked before network IO.
prepare_case( $images );
$expired_budget = new WPD_Request_Budget( microtime( true ) - 30 );
$expired_result = new_api( 's04-expired-budget' )->get_images_from_album( 154, 0, false, $expired_budget );
expect( is_wp_error( $expired_result ) && 'wpd_api_budget_exceeded' === $expired_result->get_error_code(), 'Expired traversal must fail before HTTP.' );
expect( 0 === count( $GLOBALS['requests'] ), 'Expired traversal must perform zero HTTP calls.' );

// On POST=403, public GET fallbacks consume the same HTTP budget.
// The private service-account API must remain POST-only.
prepare_case( response( 403 ), $bulk_reply );
$retry_limit = new_api( 's04-get-budget' )->get_images_from_album( 154 );
expect( is_wp_error( $retry_limit ) && 'wpd_api_budget_exceeded' === $retry_limit->get_error_code(), 'Repeated 403 fallbacks must stop at the request limit.' );
expect( 32 === count( $GLOBALS['requests'] ), 'POST and GET must share one 32-call budget.' );
expect( array( 'POST', 'GET' ) === array_slice( request_methods(), 0, 2 ), 'A public 403 may retry once using guest GET.' );

// S-04: thirty-three shortcode traversals of the same Piwigo endpoint
// must share one request-wide HTTP budget, even with separate API clients.
$small_page = response( 200, array( 'stat' => 'ok', 'result' => array(
    'images' => array( array( 'id' => 900, 'name' => 'One photo' ) ),
) ) );
prepare_case( $small_page );
for ( $album = 1001; $album <= 1032; ++$album ) {
    $ok = new_api( 's04-shared-page' )->get_images_from_album( $album, 1 );
    expect( is_array( $ok ) && 1 === count( $ok ), 'One photo in each of the first 32 same-site galleries must render.' );
}
$over_budget = new_api( 's04-shared-page' )->get_images_from_album( 1033, 1 );
expect( is_wp_error( $over_budget ) && 'wpd_api_budget_exceeded' === $over_budget->get_error_code(), 'The 33rd gallery on the same endpoint must exhaust a shared HTTP budget.' );
expect( 32 === count( $GLOBALS['requests'] ), 'Multiple gallery shortcodes must share at most 32 outbound calls.' );

echo "RC2 public Piwigo POST 403 -> safe guest GET regression: PASS\n";
