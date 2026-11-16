<?php
/** F-04 : enveloppe issue de Piwigo réel, gonflée et contaminée de façon contrôlée. */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) ) { exit( 2 ); }
$raw = '';
$url = 'http://127.0.0.1:8090/ws.php?format=json';
add_filter( 'http_response', static function ( $response ) use ( &$raw ) {
    $raw = wp_remote_retrieve_body( $response );
    return $response;
}, 1 );
$args = array( 'user-agent' => 'Piwigo Display/CI', 'body' => array( 'method' => 'pwg.categories.getImages', 'cat_id' => (int) getenv( 'WPD_ALBUM' ), 'per_page' => 1, 'page' => 0 ) );
$response = wp_safe_remote_post( $url, $args );
if ( is_wp_error( $response ) ) { throw new RuntimeException( $response->get_error_message() ); }
$data = json_decode( wp_remote_retrieve_body( $response ), true );
if ( 'ok' !== ( $data['stat'] ?? '' ) || count( $data['result']['images'] ?? array() ) !== 1 ) { throw new RuntimeException( 'F04 enveloppe Piwigo réelle absente.' ); }
$kind = getenv( 'WPD_F04_KIND' );
$bytes = (int) ( (float) getenv( 'WPD_F04_MIB' ) * 1024 * 1024 );
$prefix = '';
$prefix_source = 'none';
if ( 'polluted' === $kind ) {
    $start = strpos( $raw, '{"stat"' );
    $prefix = false !== $start && 0 < $start ? substr( $raw, 0, $start ) : '<script>/* préfixe OSM synthétique */</script>';
    $prefix_source = false !== $start && 0 < $start ? 'observed-piwigo-prefix' : 'synthetic-prefix';
}
$data['result']['volume_test_padding'] = '';
$overhead = strlen( wp_json_encode( $data ) ) + strlen( $prefix );
$data['result']['volume_test_padding'] = str_repeat( 'x', max( 0, $bytes - $overhead ) );
$response['body'] = $prefix . wp_json_encode( $data );
unset( $data, $raw );
$started = microtime( true );
$clean = WPD_Piwigo_Response_Compat::clean_response( $response, $args, $url );
$valid = is_array( json_decode( $clean['body'], true ) );
$expected = 'clean' === $kind || strlen( $response['body'] ) <= 4 * 1024 * 1024;
echo 'WP_F04=' . wp_json_encode( array( 'phase' => getenv( 'WPD_F04_PHASE' ), 'kind' => $kind, 'prefix_source' => $prefix_source, 'bytes' => strlen( $response['body'] ), 'valid' => $valid, 'expected' => $expected, 'seconds' => microtime( true ) - $started, 'peak_bytes' => memory_get_peak_usage( true ) ) ) . "\n";
exit( $valid === $expected ? 0 : 1 );
