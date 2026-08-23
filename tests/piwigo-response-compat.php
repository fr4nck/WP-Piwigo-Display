<?php
/**
 * Regression checks for polluted Piwigo JSON responses.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ): bool {
		return $value instanceof WP_Error;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ): string {
		return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ) {
		return parse_url( $url, $component );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-piwigo-response-compat.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$url         = 'https://photos.example.test/ws.php?format=json';
$plugin_args = array( 'user-agent' => 'Piwigo Display/3.0.0-rc.3' );

$clean = array( 'body' => '{"stat":"ok","result":{"value":"clean"}}' );
$assert(
	$clean === WPD_Piwigo_Response_Compat::clean_response( $clean, $plugin_args, $url ),
	'A clean Piwigo JSON response must remain untouched.'
);

$polluted = array(
	'body' => '<script>window.osm=true;</script>noise {"not":"piwigo"} {"stat":"maybe"} before {"stat":"ok","result":{"label":"brace } and \\"quote\\""}} trailing markup',
);
$recovered = WPD_Piwigo_Response_Compat::clean_response( $polluted, $plugin_args, $url );
$assert( is_array( $recovered ), 'A recoverable Piwigo response must remain an HTTP response array.' );
$assert(
	'{"stat":"ok","result":{"label":"brace } and \\"quote\\""}}' === ( $recovered['body'] ?? '' ),
	'The compatibility layer must isolate a complete Piwigo JSON object and skip unrelated or invalid stat objects.'
);

$failed_piwigo = array( 'body' => '<b>warning</b>{"stat":"fail","err":999,"message":"Example"}tail' );
$failed_recovered = WPD_Piwigo_Response_Compat::clean_response( $failed_piwigo, $plugin_args, $url );
$assert(
	'{"stat":"fail","err":999,"message":"Example"}' === ( $failed_recovered['body'] ?? '' ),
	'A valid Piwigo failure response must also be recoverable so the API client can report its error.'
);

$foreign_same_endpoint = array( 'body' => 'prefix {"stat":"ok","result":{"value":1}} suffix' );
$assert(
	$foreign_same_endpoint === WPD_Piwigo_Response_Compat::clean_response(
		$foreign_same_endpoint,
		array( 'user-agent' => 'WordPress/7.1' ),
		$url
	),
	'An unrelated WordPress request to ws.php?format=json must never be rewritten.'
);

$missing_identity = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$missing_identity === WPD_Piwigo_Response_Compat::clean_response( $missing_identity, array(), $url ),
	'A request without the Piwigo Display user-agent must never be rewritten.'
);

$unrelated_endpoint = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$unrelated_endpoint === WPD_Piwigo_Response_Compat::clean_response(
		$unrelated_endpoint,
		$plugin_args,
		'https://example.test/api.php?format=json'
	),
	'Non-Piwigo endpoints must never be rewritten.'
);

$missing_format = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$missing_format === WPD_Piwigo_Response_Compat::clean_response( $missing_format, $plugin_args, 'https://photos.example.test/ws.php' ),
	'Piwigo ws.php requests without format=json must never be rewritten.'
);

$invalid = array( 'body' => '<div>noise</div>{"result":{"value":1}}{"stat":"unknown"}tail' );
$assert(
	$invalid === WPD_Piwigo_Response_Compat::clean_response( $invalid, $plugin_args, $url ),
	'A polluted body without a valid Piwigo stat value must not be accepted as recovered API JSON.'
);

$valid_foreign_json = '{"service":"other","result":{"value":1}}';
$assert(
	$valid_foreign_json === WPD_Piwigo_Response_Compat::normalize_body( $valid_foreign_json ),
	'Already-valid JSON must remain byte-for-byte unchanged.'
);

$bootstrap = file_get_contents( dirname( __DIR__ ) . '/wp-piwigo-display.php' );
$assert( false !== strpos( (string) $bootstrap, 'class-wpd-piwigo-response-compat.php' ), 'The compatibility class must be loaded by the plugin bootstrap.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Piwigo_Response_Compat::register()' ), 'The compatibility layer must be registered by the plugin bootstrap.' );

$compatibility_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-piwigo-response-compat.php' );
$assert( false !== strpos( (string) $compatibility_source, "'user-agent'" ), 'Recovery must explicitly check the request user-agent.' );
$assert( false !== strpos( (string) $compatibility_source, "'Piwigo Display/'" ), 'Recovery must be scoped to Piwigo Display HTTP requests.' );
$assert( false !== strpos( (string) $compatibility_source, "array( 'ok', 'fail' )" ), 'Recovery must only accept documented Piwigo stat values.' );

echo "Piwigo polluted-response compatibility: OK\n";
