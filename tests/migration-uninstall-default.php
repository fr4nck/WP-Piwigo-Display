<?php
/** Default uninstall must preserve options and secrets. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
$GLOBALS['migration_deleted'] = array();
function delete_option( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function is_multisite(): bool { return false; }
require dirname( __DIR__ ) . '/uninstall.php';
if ( array() !== $GLOBALS['migration_deleted'] ) { fwrite( STDERR, "FAIL default uninstall erased data\n" ); exit( 1 ); }
echo "PASS default uninstall retains all shared data\n";
