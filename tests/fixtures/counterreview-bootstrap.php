<?php
/** Banc HTTP déterministe : vrais clients du plugin, primitives WordPress simulées. */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
define( 'WPD_VERSION', '3.1.0-rc.2' );
define( 'DAY_IN_SECONDS', 86400 );

class WP_Error {
    public function __construct( private string $code = '', private string $message = '' ) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}
class WP_Http_Cookie {
    public string $name = 'pwg_id';
}
class WPD_Settings {
    public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
    public static function get_cache_duration(): int { return 60; }
}
class WPD_Service_Account {
    public static bool $configured = true;
    public static function is_configured(): bool { return self::$configured; }
    public static function get_context_hash(): string { return 'synthetic-service'; }
    public static function get_username(): string { return 'ci-synthetic'; }
    public static function get_password(): string { return 'ci-synthetic-only'; }
}
class WPD_Api_Metrics {
    public static function cache_hit( string $source ): void {}
    public static function cache_miss( string $source ): void {}
}
function __( $value, $domain = '' ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_text_field( $value ) { return (string) $value; }
function untrailingslashit( $value ) { return rtrim( $value, '/' ); }
function esc_url_raw( $value, $schemes = array() ) { return $value; }
function wp_http_validate_url( $value ) { return true; }
function wp_parse_url( $value, $part = -1 ) { return parse_url( $value, $part ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function is_preview() { return $GLOBALS['preview'] ?? false; }
function current_user_can( $capability ) { return false; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function wp_using_ext_object_cache() { return true; }
function wp_cache_add( ...$arguments ) { return true; }
function wp_cache_delete( ...$arguments ) { return true; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_cookies( $response ) { return $response['cookies'] ?? array(); }
function wp_safe_remote_get( $url, $arguments ) {
    parse_str( parse_url( $url, PHP_URL_QUERY ), $body );
    $arguments['body'] = $body;
    return cr_http( $url, $arguments, 'GET' );
}
function wp_safe_remote_post( $url, $arguments ) { return cr_http( $url, $arguments, 'POST' ); }
function cr_http( $url, $arguments, $verb ) {
    $body = $arguments['body'];
    $GLOBALS['calls'][] = array( 'verb' => $verb, 'body' => $body, 'args' => $arguments, 'url' => $url );
    if ( $GLOBALS['post403'] && 'POST' === $verb ) {
        return array( 'response' => array( 'code' => 403 ), 'body' => '' );
    }
    if ( $GLOBALS['upstream_error'] ) {
        return new WP_Error( 'http_request_failed', 'synthetic upstream failure' );
    }
    $cookies = array();
    switch ( $body['method'] ) {
        case 'pwg.session.login':
            $result = true;
            $cookies = array( new WP_Http_Cookie() );
            break;
        case 'pwg.categories.getList':
            if ( isset( $GLOBALS['category_body'] ) ) {
                return array( 'response' => array( 'code' => 200 ), 'body' => $GLOBALS['category_body'] );
            }
            $result = array( 'categories' => $GLOBALS['categories'] );
            break;
        case 'pwg.categories.getImages':
            $album = $GLOBALS['albums'][ (int) $body['cat_id'] ] ?? array();
            $size = (int) $body['per_page'];
            $offset = (int) $body['page'] * $size;
            if ( is_int( $album ) ) {
                $images = array();
                for ( $i = $offset; $i < min( $album, $offset + $size ); ++$i ) {
                    $images[] = array( 'id' => $i + 1 );
                }
            } else {
                $images = array_slice( $album, $offset, $size );
            }
            $GLOBALS['rows'] += count( $images );
            $result = array( 'images' => $images );
            break;
        default:
            throw new RuntimeException( 'Unexpected API method: ' . $body['method'] );
    }
    return array( 'response' => array( 'code' => 200 ), 'body' => json_encode( array( 'stat' => 'ok', 'result' => $result ) ), 'cookies' => $cookies );
}

require ABSPATH . 'includes/class-wpd-api.php';
require ABSPATH . 'includes/class-wpd-service-api.php';
require ABSPATH . 'includes/class-wpd-cache.php';

function cr_next_request(): void {
    foreach ( array( WPD_Api::class => array( 'request_cache' ), WPD_Request_Budget::class => array( 'request_budgets' ), WPD_Cache::class => array( 'request_cache', 'api_clients' ) ) as $class => $properties ) {
        foreach ( $properties as $property ) {
            ( new ReflectionProperty( $class, $property ) )->setValue( null, array() );
        }
    }
    $GLOBALS['calls'] = array();
    $GLOBALS['rows'] = 0;
}
function cr_reset(): void {
    cr_next_request();
    $GLOBALS['transients'] = array();
    $GLOBALS['categories'] = array( array( 'id' => 1, 'uppercats' => '1' ), array( 'id' => 2, 'uppercats' => '1,2' ), array( 'id' => 3, 'uppercats' => '1,2,3' ), array( 'id' => 4, 'uppercats' => '4' ) );
    $GLOBALS['albums'] = array();
    $GLOBALS['post403'] = false;
    $GLOBALS['upstream_error'] = false;
    $GLOBALS['preview'] = false;
    WPD_Service_Account::$configured = true;
}
function cr_check( bool $pass, string $message ): void {
    echo ( $pass ? 'PASS ' : 'FAIL ' ) . $message . PHP_EOL;
    if ( ! $pass ) { ++$GLOBALS['failures']; }
}
$GLOBALS['failures'] = 0;

