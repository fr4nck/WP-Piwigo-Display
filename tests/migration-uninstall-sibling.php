<?php
/** Removing one installed distribution must preserve shared data. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
$GLOBALS['migration_deleted'] = array();
function get_plugins(): array { return array(
	'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
	'display4piwigo/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display (WordPress.org)' ),
); }
function delete_option( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function is_multisite(): bool { return false; }
require dirname( __DIR__ ) . '/uninstall.php';
if ( array() !== $GLOBALS['migration_deleted'] ) { fwrite( STDERR, "FAIL sibling data deleted\n" ); exit( 1 ); }
echo "PASS installed sibling blocks purge\n";
