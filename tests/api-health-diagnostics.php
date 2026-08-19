<?php
/**
 * Regression checks for the API/cache health diagnostic.
 *
 * @package WP_Piwigo_Display
 */

$root         = dirname( __DIR__ );
$bootstrap    = file_get_contents( $root . '/wp-piwigo-display.php' );
$metrics      = file_get_contents( $root . '/includes/class-wpd-api-metrics.php' );
$cache        = file_get_contents( $root . '/includes/class-wpd-cache.php' );
$diagnostic   = file_get_contents( $root . '/includes/class-wpd-diagnostic.php' );
$requirements = array(
	'bootstrap loads metrics collector' => false !== strpos( $bootstrap, 'class-wpd-api-metrics.php' ),
	'bootstrap registers metrics'       => false !== strpos( $bootstrap, 'WPD_Api_Metrics::register()' ),
	'metrics class remains present'     => false !== strpos( $metrics, 'final class WPD_Api_Metrics' ),
	'HTTP observer remains present'     => false !== strpos( $metrics, 'http_api_debug' ),
	'API call counter remains present'  => false !== strpos( $metrics, "'api_calls'" ),
	'cache HIT remains instrumented'    => false !== strpos( $cache, 'WPD_Api_Metrics::cache_hit' ),
	'cache MISS remains instrumented'   => false !== strpos( $cache, 'WPD_Api_Metrics::cache_miss' ),
	'health panel remains present'      => false !== strpos( $diagnostic, 'Santé API & cache' ),
	'API calls remain displayed'        => false !== strpos( $diagnostic, 'Appels API' ),
	'HIT/MISS remain displayed'         => false !== strpos( $diagnostic, 'Cache HIT / MISS' ),
	'API timing remains displayed'      => false !== strpos( $diagnostic, 'Temps API cumulé' ),
);

$failed = array_keys( array_filter( $requirements, static fn ( bool $ok ): bool => ! $ok ) );
if ( ! empty( $failed ) ) {
	fwrite( STDERR, "API health diagnostics regression:\n- " . implode( "\n- ", $failed ) . "\n" );
	exit( 1 );
}

echo "API/cache health diagnostic protected: OK\n";
