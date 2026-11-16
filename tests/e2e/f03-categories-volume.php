<?php
/** F-03 : WordPress réel, catégories synthétiques, HTTP intercepté avant réseau. */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) || ! getenv( 'WPD_F03_SOURCE' ) ) { exit( 2 ); }
ini_set( 'memory_limit', '128M' );
$source = rtrim( getenv( 'WPD_F03_SOURCE' ), '/' );
define( 'WPD_VERSION', 'ci-counterreview' );
foreach ( array( 'settings', 'service-account', 'api-metrics', 'api', 'service-api', 'cache' ) as $class ) {
    require_once $source . '/includes/class-wpd-' . $class . '.php';
}
$number = (int) getenv( 'WPD_F03_COUNT' );
$depth = max( 1, (int) getenv( 'WPD_F03_DEPTH' ) );
$service = 'service' === getenv( 'WPD_F03_MODE' );
define( 'WPD_PIWIGO_SERVICE_ENABLED', $service );
define( 'WPD_PIWIGO_SERVICE_USERNAME', 'ci-synthetic' );
define( 'WPD_PIWIGO_SERVICE_PASSWORD', 'ci-synthetic-only' );
$url = 'https://example.com/ci-f03-' . $number . '-' . $depth;
add_filter( 'pre_option_wp_piwigo_display_options', static fn() => array_merge( WPD_Settings::default_options(), array( 'piwigo_url' => $url, 'cache_duration' => 60 ) ) );
$body = '{"stat":"ok","result":{"categories":[';
for ( $i = 1; $i <= $number; ++$i ) {
    $path = range( 1, min( $i, $depth ) );
    if ( $path[ count( $path ) - 1 ] !== $i ) { $path[] = $i; }
    $body .= ( 1 === $i ? '' : ',' ) . wp_json_encode( array( 'id' => $i, 'name' => 'Album ' . $i, 'uppercats' => implode( ',', $path ), 'permalink' => '' ) );
}
$body .= ']}}';
if ( strlen( $body ) > WPD_Request_Budget::MAX_RESPONSE_BYTES ) { throw new RuntimeException( 'Banc hors plafond HTTP.' ); }
add_filter( 'pre_http_request', static function ( $pre, $args, $request_url ) use ( &$body, $url ) {
    if ( $request_url !== $url . '/ws.php?format=json' ) { throw new RuntimeException( 'F03 aucun réseau autorisé.' ); }
    $login = 'pwg.session.login' === $args['body']['method'];
    return array( 'headers' => array(), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'body' => $login ? '{"stat":"ok","result":true}' : $body, 'cookies' => $login ? array( new WP_Http_Cookie( array( 'name' => 'pwg_id', 'value' => 'ci' ) ) ) : array() );
}, 1, 3 );
$started = microtime( true );
for ( $i = 0; $i < 10; ++$i ) {
    if ( $number !== WPD_Cache::resolve_album_id( 'Album ' . $number ) ) { throw new RuntimeException( 'F03 résolution perdue.' ); }
}
echo 'WP_F03=' . wp_json_encode( array( 'count' => $number, 'depth' => $depth, 'service' => $service, 'bytes' => strlen( $body ), 'ten_resolutions_seconds' => microtime( true ) - $started, 'peak_bytes' => memory_get_peak_usage( true ), 'memory_limit' => ini_get( 'memory_limit' ) ) ) . "\n";
