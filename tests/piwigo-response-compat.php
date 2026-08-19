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

$url = 'https://photos.example.test/ws.php?format=json';

$clean = array( 'body' => '{"stat":"ok","result":{"value":"clean"}}' );
$assert(
	$clean === WPD_Piwigo_Response_Compat::clean_response( $clean, array(), $url ),
	'A clean Piwigo JSON response must remain untouched.'
);

$polluted = array(
	'body' => '<script>window.osm=true;</script>noise {"not":"piwigo"} before {"stat":"ok","result":{"label":"brace } and \\"quote\\""}} trailing markup',
);
$recovered = WPD_Piwigo_Response_Compat::clean_response( $polluted, array(), $url );
$assert( is_array( $recovered ), 'A recoverable Piwigo response must remain an HTTP response array.' );
$assert(
	'{"stat":"ok","result":{"label":"brace } and \\"quote\\""}}' === ( $recovered['body'] ?? '' ),
	'The compatibility layer must isolate the complete Piwigo JSON object and ignore surrounding output.'
);

$unrelated = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$unrelated === WPD_Piwigo_Response_Compat::clean_response( $unrelated, array(), 'https://example.test/api.php?format=json' ),
	'Non-Piwigo endpoints must never be rewritten.'
);

$missing_format = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$missing_format === WPD_Piwigo_Response_Compat::clean_response( $missing_format, array(), 'https://photos.example.test/ws.php' ),
	'Piwigo ws.php requests without format=json must never be rewritten.'
);

$invalid = array( 'body' => '<div>noise</div>{"result":{"value":1}}tail' );
$assert(
	$invalid === WPD_Piwigo_Response_Compat::clean_response( $invalid, array(), $url ),
	'A polluted body without a Piwigo stat member must not be accepted as recovered API JSON.'
);

$bootstrap = file_get_contents( dirname( __DIR__ ) . '/wp-piwigo-display.php' );
$assert( false !== strpos( (string) $bootstrap, 'class-wpd-piwigo-response-compat.php' ), 'The compatibility class must be loaded by the plugin bootstrap.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Piwigo_Response_Compat::register()' ), 'The compatibility layer must be registered by the plugin bootstrap.' );

echo "Piwigo polluted-response compatibility: OK\n";
