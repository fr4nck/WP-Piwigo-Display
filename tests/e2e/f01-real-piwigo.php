<?php
/** F-01 : clients réels, WordPress réel, Piwigo réel, TLS vérifié pour le service. */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) || ! getenv( 'WPD_F01_SOURCE' ) ) { exit( 2 ); }
$source = rtrim( getenv( 'WPD_F01_SOURCE' ), '/' );
define( 'WPD_VERSION', 'ci-counterreview' );
foreach ( array( 'settings', 'service-account', 'api-metrics', 'piwigo-response-compat', 'api', 'service-api' ) as $class ) {
    require_once $source . '/includes/class-wpd-' . $class . '.php';
}
WPD_Piwigo_Response_Compat::register();
$service = 'service' === getenv( 'WPD_F01_MODE' );
$url = $service ? 'https://127.0.0.1:8091' : 'http://127.0.0.1:8090';
define( 'WPD_PIWIGO_SERVICE_ENABLED', $service );
define( 'WPD_PIWIGO_SERVICE_USERNAME', 'ci_admin' );
define( 'WPD_PIWIGO_SERVICE_PASSWORD', 'ci-only-piwigo' );
add_filter( 'pre_option_wp_piwigo_display_options', static fn() => array_merge( WPD_Settings::default_options(), array( 'piwigo_url' => $url ) ) );
add_filter( 'http_request_host_is_external', static fn( $allowed, $host ) => '127.0.0.1' === $host ? true : $allowed, 10, 2 );
add_filter( 'http_allowed_safe_ports', static function ( $ports, $host ) {
    if ( '127.0.0.1' === $host ) { $ports[] = 8091; }
    return $ports;
}, 10, 2 );
$calls = 0;
$rows = 0;
add_filter( 'http_request_args', static function ( $args, $request_url ) use ( $url, $service, &$calls ) {
    if ( ! str_starts_with( $request_url, $url . '/ws.php?' ) ) { throw new RuntimeException( 'F01 URL non autorisée.' ); }
    if ( $service ) {
        if ( 'POST' !== $args['method'] || true !== $args['sslverify'] || 0 !== $args['redirection'] ) { throw new RuntimeException( 'F01 sécurité service modifiée.' ); }
        // CA locale du laboratoire uniquement, jamais sslverify=false.
        $args['sslcertificates'] = getenv( 'WPD_LAB' ) . '/f01-ca.pem';
    }
    ++$calls;
    return $args;
}, 20, 2 );
add_filter( 'http_response', static function ( $response ) use ( &$rows ) {
    $json = json_decode( wp_remote_retrieve_body( $response ), true );
    $rows += count( $json['result']['images'] ?? array() );
    return $response;
}, 20 );
$case = getenv( 'WPD_F01_CASE' );
$client = $service ? new WPD_Service_Api( $url ) : new WPD_Api( $url );
$max = 'zero' === $case ? 0 : ( 'large' === $case ? 1 : 3 );
$album = (int) getenv( 'large' === $case || 'zero' === $case ? 'WPD_F01_LARGE' : 'WPD_F01_SHARED' );
$result = $client->get_images_from_album_recursive( $album, $max, 2 );
$expected = array_map( 'intval', explode( ',', getenv( 'WPD_F01_IDS' ) ) );
$pass = 'zero' === $case
    ? is_wp_error( $result ) && 'wpd_api_budget_exceeded' === $result->get_error_code() && $calls <= 32
    : is_array( $result ) && count( $result ) === $max && ( 'large' === $case ? 1 === $rows : $expected === array_column( $result, 'id' ) );
echo 'WP_F01_REAL=' . ( $pass ? 'PASS ' : 'FAIL ' ) . wp_json_encode( array( 'case' => $case, 'service' => $service, 'calls' => $calls, 'rows' => $rows, 'result_count' => is_array( $result ) ? count( $result ) : null, 'error' => is_wp_error( $result ) ? $result->get_error_code() : null ) ) . "\n";
exit( $pass ? 0 : 1 );
