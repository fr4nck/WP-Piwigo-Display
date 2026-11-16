<?php
/** Mesure F-04 : JSON propre/contaminé à plusieurs volumes et préfixes adverses. */
declare(strict_types=1);
define( 'ABSPATH', __DIR__ . '/' );
function is_wp_error( $value ) { return false; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_parse_url( $url, $part ) { return parse_url( $url, $part ); }
require dirname( __DIR__, 2 ) . '/includes/class-wpd-piwigo-response-compat.php';
$bytes = (int) ( (float) ( $argv[1] ?? '1' ) * 1024 * 1024 );
$kind = $argv[2] ?? 'clean';
$prefix = 'polluted' === $kind ? '<script>OSM();</script>' : ( 'unmatched' === $kind ? '<script>{"unclosed":' : '' );
$shell = '{"stat":"ok","result":{"padding":""}}';
$json = '{"stat":"ok","result":{"padding":"' . str_repeat( 'x', max( 0, $bytes - strlen( $prefix ) - strlen( $shell ) ) ) . '"}}';
$response = array( 'body' => $prefix . $json );
$started = microtime( true );
$cleaned = WPD_Piwigo_Response_Compat::clean_response( $response, array( 'user-agent' => 'Piwigo Display/CI' ), 'https://photos.example.test/ws.php?format=json' );
$valid = is_array( json_decode( $cleaned['body'], true ) );
$seconds = microtime( true ) - $started;
$expected = 'clean' === $kind || strlen( $response['body'] ) <= 4 * 1024 * 1024;
echo json_encode( array( 'bytes' => strlen( $response['body'] ), 'kind' => $kind, 'valid' => $valid, 'expected' => $expected, 'seconds' => $seconds, 'peak_bytes' => memory_get_peak_usage( true ) ) ) . PHP_EOL;
exit( $valid === $expected ? 0 : 1 );
