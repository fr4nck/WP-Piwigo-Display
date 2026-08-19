<?php
/**
 * Regression test for the nullable initial frontend filter value.
 *
 * @package WP_Piwigo_Display
 */

define( 'ABSPATH', __DIR__ . '/../' );

require_once __DIR__ . '/../includes/class-wpd-masonry.php';

try {
	$result = WPD_Masonry::render( null, array(), array(), 'slider' );
} catch ( Throwable $error ) {
	fwrite( STDERR, 'Masonry render filter failed: ' . $error->getMessage() . PHP_EOL );
	exit( 1 );
}

if ( null !== $result ) {
	fwrite( STDERR, "Masonry render filter failed: expected the initial null value.\n" );
	exit( 1 );
}

echo "Masonry nullable render filter: OK\n";
