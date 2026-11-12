<?php
/** Regression MG05: a renamed sibling with historical entrypoint must retain data. */
declare(strict_types=1);
define( 'ABSPATH', '/tmp/wp-disposable/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
$GLOBALS['deleted'] = array();
function get_plugins(): array {
	return array(
		'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
		'unknown-catalogue/wp-piwigo-display.php' => array( 'Name' => 'A different public title' ),
	);
}
function delete_option( string $key ): bool { $GLOBALS['deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { return true; }
function is_multisite(): bool { return false; }
require dirname( __DIR__ ) . '/uninstall.php';
if ( array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL renamed sibling was not protected by entrypoint identity\n" );
	exit( 1 );
}
echo "PASS filename identity protects renamed sibling\n";
