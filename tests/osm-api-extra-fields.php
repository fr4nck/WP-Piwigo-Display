<?php
/**
 * Regression for Piwigo WebService payloads enriched by OpenStreetMap.
 *
 * This checks the real public API client, not the third-party plugin itself.
 * The external installation reported in issue #97 still needs confirmation.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPD_VERSION', 'osm-test' );

final class WP_Error {
    public function __construct( private string $code, private string $message = '' ) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}
function __( string $text, string $domain = '' ): string { return $text; }
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function wp_json_encode( $value ): string { return (string) json_encode( $value ); }
function esc_url_raw( string $url, array $protocols = array() ): string { return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : ''; }
function wp_http_validate_url( string $url ): bool { return false !== filter_var( $url, FILTER_VALIDATE_URL ); }
function untrailingslashit( string $url ): string { return rtrim( $url, '/' ); }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code( array $response ): int { return (int) ( $response['response']['code'] ?? 0 ); }
function wp_remote_retrieve_body( array $response ): string { return (string) ( $response['body'] ?? '' ); }

$GLOBALS['osm_http_calls'] = array();
$GLOBALS['osm_http_response'] = array();
function wp_safe_remote_post( string $url, array $args = array() ) {
    $GLOBALS['osm_http_calls'][] = array( 'url' => $url, 'args' => $args );
    return $GLOBALS['osm_http_response'];
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-api.php';
$assert = static function ( bool $ok, string $reason ): void {
    if ( ! $ok ) {
        fwrite( STDERR, 'OSM_API_CONTRACT_FAIL=' . $reason . PHP_EOL );
        exit( 1 );
    }
};

$cases = array(
    'standard' => array(
        'id' => 41, 'name' => 'Photo sans carte',
        'element_url' => 'https://photos.example.test/picture/41',
    ),
    'openstreetmap' => array(
        'id' => 42, 'name' => 'Photo avec carte',
        'element_url' => 'https://photos.example.test/picture/42',
        'latitude' => '48.1173', 'longitude' => '-1.6778',
        'osm_extra' => array( 'provider' => 'OpenStreetMap', 'marker' => array( 'zoom' => 12 ) ),
    ),
);

foreach ( $cases as $name => $image ) {
    $GLOBALS['osm_http_calls'] = array();
    $GLOBALS['osm_http_response'] = array(
        'response' => array( 'code' => 200 ),
        'body' => (string) json_encode(
            array( 'stat' => 'ok', 'result' => array( 'images' => array( $image ) ) ),
            JSON_UNESCAPED_UNICODE
        ),
    );

    $api = new WPD_Api( 'https://' . $name . '.example.test/' );
    $images = $api->get_images_from_album( 7, 1 );

    $assert( ! is_wp_error( $images ), $name . ': API unexpectedly rejected image data' );
    $assert( is_array( $images ) && 1 === count( $images ), $name . ': image count changed' );
    $assert( $image === $images[0], $name . ': unknown Piwigo fields were modified' );
    $assert( 1 === count( $GLOBALS['osm_http_calls'] ), $name . ': unexpected HTTP requests' );

    $request = $GLOBALS['osm_http_calls'][0];
    $assert( str_ends_with( $request['url'], '/ws.php?format=json' ), $name . ': wrong Piwigo endpoint' );
    $assert( 'pwg.categories.getImages' === ( $request['args']['body']['method'] ?? null ), $name . ': wrong WebService method' );
    $assert( 7 === ( $request['args']['body']['cat_id'] ?? null ), $name . ': album ID changed' );

    if ( 'openstreetmap' === $name ) {
        $assert( '48.1173' === ( $images[0]['latitude'] ?? null ), 'latitude dropped' );
        $assert( '-1.6778' === ( $images[0]['longitude'] ?? null ), 'longitude dropped' );
        $assert( 'OpenStreetMap' === ( $images[0]['osm_extra']['provider'] ?? null ), 'plugin metadata dropped' );
    }
    echo 'OSM_API_CONTRACT_PASS=' . $name . PHP_EOL;
}
