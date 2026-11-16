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
$args = array( 'user-agent' => 'Piwigo Display/3.1.0-rc.2' );

$clean = array( 'body' => '{"stat":"ok","result":{"value":"clean"}}' );
$assert(
	$clean === WPD_Piwigo_Response_Compat::clean_response( $clean, $args, $url ),
	'A clean Piwigo JSON response must remain untouched.'
);

$polluted = array(
	'body' => '<script>window.osm=true;</script>noise {"not":"piwigo"} before {"stat":"ok","result":{"label":"brace } and \\"quote\\""}} trailing markup',
);
$recovered = WPD_Piwigo_Response_Compat::clean_response( $polluted, $args, $url );
$assert( is_array( $recovered ), 'A recoverable Piwigo response must remain an HTTP response array.' );
$assert(
	'{"stat":"ok","result":{"label":"brace } and \\"quote\\""}}' === ( $recovered['body'] ?? '' ),
	'The compatibility layer must isolate the complete Piwigo JSON object and ignore surrounding output.'
);

$unrelated = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$unrelated === WPD_Piwigo_Response_Compat::clean_response( $unrelated, $args, 'https://example.test/api.php?format=json' ),
	'Non-Piwigo endpoints must never be rewritten.'
);

$missing_format = array( 'body' => 'prefix {"stat":"ok"} suffix' );
$assert(
	$missing_format === WPD_Piwigo_Response_Compat::clean_response( $missing_format, $args, 'https://photos.example.test/ws.php' ),
	'Piwigo ws.php requests without format=json must never be rewritten.'
);

$invalid = array( 'body' => '<div>noise</div>{"result":{"value":1}}tail' );
$assert(
	$invalid === WPD_Piwigo_Response_Compat::clean_response( $invalid, $args, $url ),
	'A polluted body without a Piwigo stat member must not be accepted as recovered API JSON.'
);


$foreign = array( 'body' => '<script>not ours</script>{"stat":"ok"} noise' );
$assert(
	$foreign === WPD_Piwigo_Response_Compat::clean_response( $foreign, array( 'user-agent' => 'Another WordPress plugin' ), $url ),
	'Other plugins must never have their HTTP response rewritten, even at ws.php.'
);

$oversized = array( 'body' => str_repeat( '.', 4 * 1024 * 1024 ) . '{"stat":"ok"}' );
$assert(
	$oversized === WPD_Piwigo_Response_Compat::clean_response( $oversized, $args, $url ),
	'Recovery must not decode or modify an oversized polluted response.'
);

$trick = array( 'body' => '{"stat":"untrusted"}{"stat":"ok","result":{"album":14}}' );
$fixed = WPD_Piwigo_Response_Compat::clean_response( $trick, $args, $url );
$assert(
	'{"stat":"ok","result":{"album":14}}' === ( $fixed['body'] ?? '' ),
	'Recovery must reject an unsupported stat, then accept the actual Piwigo object.'
);

// Regression: a stray unmatched brace in HTML/JavaScript preceding otherwise
// valid Piwigo JSON must not swallow the API response.
$unclosed_prefix = array( 'body' => '<script>/* injected { */</script>{"stat":"ok","result":{"id":42}}' );
$unclosed_recovered = WPD_Piwigo_Response_Compat::clean_response( $unclosed_prefix, $args, $url );
$assert(
    '{"stat":"ok","result":{"id":42}}' === ( $unclosed_recovered['body'] ?? '' ),
    'An unclosed brace in the polluted prefix must not conceal the actual Piwigo JSON.'
);

$orphan_quote = array( 'body' => '<script>var broken = {"msg":"unterminated </script>{"stat":"ok","result":{"id":43}}' );
$quote_recovered = WPD_Piwigo_Response_Compat::clean_response( $orphan_quote, $args, $url );
$assert(
    '{"stat":"ok","result":{"id":43}}' === ( $quote_recovered['body'] ?? '' ),
    'An unclosed quote in the polluted prefix must not conceal the actual Piwigo JSON.'
);

$deep_orphans = array( 'body' => str_repeat( '{', 35000 ) . '{"stat":"ok","result":{"id":44}}' );
$start_orphans = microtime( true );
$deep_recovered = WPD_Piwigo_Response_Compat::clean_response( $deep_orphans, $args, $url );
$assert(
    '{"stat":"ok","result":{"id":44}}' === ( $deep_recovered['body'] ?? '' ),
    'Deep unmatched braces preceding a Piwigo response must be recoverable.'
);
$assert( microtime( true ) - $start_orphans < 2.0, 'Orphaned-prefix recovery exceeded the bounded time budget.' );

// Valid Piwigo JSON does not require the stat property to appear first.
// The recovery fallback must preserve this even after an orphaned brace.
$reordered = array( 'body' => '<script>/* unmatched { */</script>{"result":{"id":45},"stat":"ok"}' );
$reordered_result = WPD_Piwigo_Response_Compat::clean_response( $reordered, $args, $url );
$assert(
    '{"result":{"id":45},"stat":"ok"}' === ( $reordered_result['body'] ?? '' ),
    'Orphaned prefix must not conceal a valid Piwigo JSON object with stat after result.'
);

// Prior implementation performed a fresh nested scan from every opening
// brace (quadratic CPU on hostile bodies). Verify linear behavior and that
// scanning remains correct when a malformed object precedes valid Piwigo JSON.
$adversarial = array( 'body' => str_repeat( '{', 35000 ) . str_repeat( '}', 35000 ) . '{"stat":"ok","result":{}}' );
$started = microtime( true );
$recovered = WPD_Piwigo_Response_Compat::clean_response( $adversarial, $args, $url );
$elapsed = microtime( true ) - $started;
$assert( '{"stat":"ok","result":{}}' === ( $recovered['body'] ?? '' ), 'A valid Piwigo object following malformed input must be recovered.' );
$assert( $elapsed < 2.0, 'JSON recovery exceeds the bounded processing budget on adversarial braces.' );
echo sprintf( "Piwigo polluted-response adversarial scan: PASS %.3fs\n", $elapsed );

$bootstrap = file_get_contents( dirname( __DIR__ ) . '/wp-piwigo-display.php' );
$assert( false !== strpos( (string) $bootstrap, 'class-wpd-piwigo-response-compat.php' ), 'The compatibility class must be loaded by the plugin bootstrap.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Piwigo_Response_Compat::register()' ), 'The compatibility layer must be registered by the plugin bootstrap.' );

echo "Piwigo polluted-response compatibility: OK\n";
