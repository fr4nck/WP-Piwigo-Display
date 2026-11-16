<?php
/** F-02 : un processus WP-CLI par requête, vrais transients WordPress en base. */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) || ! getenv( 'WPD_F02_SOURCE' ) ) {
    exit( 2 );
}
$source = rtrim( getenv( 'WPD_F02_SOURCE' ), '/' );
if ( class_exists( 'WPD_Cache', false ) ) {
    fwrite( STDERR, "WP_F02=HARNESS_ERROR --skip-plugins requis\n" );
    exit( 2 );
}
define( 'WPD_VERSION', 'ci-counterreview' );
foreach ( array( 'settings', 'service-account', 'api-metrics', 'api', 'service-api', 'cache' ) as $class ) {
    require_once $source . '/includes/class-wpd-' . $class . '.php';
}
$service = 'service' === getenv( 'WPD_F02_MODE' );
$phase = getenv( 'WPD_F02_PHASE' );
$variant = getenv( 'WPD_F02_VARIANT' ) ?: 'candidate';
define( 'WPD_PIWIGO_SERVICE_ENABLED', $service );
define( 'WPD_PIWIGO_SERVICE_USERNAME', 'ci-synthetic' );
define( 'WPD_PIWIGO_SERVICE_PASSWORD', 'ci-synthetic-only' );
$url = 'https://example.com';
add_filter( 'pre_option_wp_piwigo_display_options', static function () use ( $url ) {
    return array_merge( WPD_Settings::default_options(), array( 'piwigo_url' => $url, 'cache_duration' => 60 ) );
} );
$calls = 0;
add_filter( 'pre_http_request', static function ( $pre, $args, $request_url ) use ( &$calls, $phase, $url, $service ) {
    // Chaque envoi est intercepté avant réseau, y compris login et cookies.
    if ( $request_url !== $url . '/ws.php?format=json' || 'POST' !== $args['method'] ) {
        throw new RuntimeException( 'WP_F02 transport inattendu ; aucun réseau autorisé.' );
    }
    if ( $service && ( true !== $args['sslverify'] || 0 !== $args['redirection'] ) ) {
        throw new RuntimeException( 'WP_F02 TLS/redirection modifié.' );
    }
    ++$calls;
    if ( 'upstream' === $phase || 'upstream-hot' === $phase ) {
        return new WP_Error( 'http_request_failed', 'Panne amont synthétique.' );
    }
    $login = 'pwg.session.login' === $args['body']['method'];
    return array(
        'headers' => array(),
        'response' => array( 'code' => 200, 'message' => 'OK' ),
        'body' => wp_json_encode( array( 'stat' => 'ok', 'result' => $login ? true : array( 'images' => array( array( 'id' => 919001, 'name' => 'CI' ) ) ) ) ),
        'cookies' => $login ? array( new WP_Http_Cookie( array( 'name' => 'pwg_id', 'value' => 'synthetic-only' ) ) ) : array(),
    );
}, 1, 3 );
$album = ( 'baseline' === $variant ? 919100 : 919200 ) + ( $service ? 10 : 0 ) + ( str_starts_with( $phase, 'upstream' ) ? 1 : 0 );
if ( 'exhaust' === $phase ) {
    $budget = WPD_Request_Budget::for_endpoint( $url );
    for ( $i = 0; $i < 32; ++$i ) { $budget->reserve_http_call(); }
}
$result = WPD_Cache::get_album_images( $album, 1 );
$context = $service ? WPD_Service_Account::get_context_hash() : 'anonymous';
$key_method = new ReflectionMethod( WPD_Cache::class, 'get_album_cache_key' );
$key = $key_method->invoke( null, $album, 1, $url, false, 10, $context );
$negative = get_transient( 'wpd_album_error_' . md5( $key ) );
$pass = match ( $phase ) {
    'exhaust' => is_wp_error( $result ) && 'wpd_api_budget_exceeded' === $result->get_error_code() && false === $negative && 0 === $calls,
    'recover' => is_array( $result ) && 1 === count( $result ) && 0 < $calls,
    'hot' => is_array( $result ) && 0 === $calls,
    'upstream' => is_wp_error( $result ) && is_wp_error( $negative ) && 0 < $calls,
    'upstream-hot' => is_wp_error( $result ) && 0 === $calls,
    default => false,
};
echo 'WP_F02=' . ( $pass ? 'PASS ' : 'FAIL ' ) . wp_json_encode( array( 'variant' => $variant, 'service' => $service, 'phase' => $phase, 'http_calls' => $calls, 'error' => is_wp_error( $result ) ? $result->get_error_code() : null, 'negative_transient' => is_wp_error( $negative ), 'pid' => getmypid() ) ) . "\n";
exit( $pass ? 0 : 1 );
