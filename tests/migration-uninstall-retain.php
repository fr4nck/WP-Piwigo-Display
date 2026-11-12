<?php
/** Explicit retention for a planned reinstall takes priority over default cleanup. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
define( 'WPD_KEEP_DATA_ON_UNINSTALL', 'true' );
$GLOBALS['migration_deleted'] = array();
function get_plugins(): array { return array( 'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ) ); }
function delete_option( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function is_multisite(): bool { return false; }
require dirname( __DIR__ ) . '/uninstall.php';
if ( array() !== $GLOBALS['migration_deleted'] ) {
	fwrite( STDERR, "FAIL explicit retention was ignored\n" );
	exit( 1 );
}
echo "PASS explicit keep-data opt-in does not delete credentials\n";
