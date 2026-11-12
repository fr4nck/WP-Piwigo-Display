<?php
/** Explicit purge for sole copy works across all test sites, restoring context. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
define( 'WPD_PURGE_DATA_ON_UNINSTALL', true );
$GLOBALS['migration_deleted'] = array();
$GLOBALS['migration_blog'] = 1;
$GLOBALS['migration_stack'] = array();
function get_plugins(): array { return array(
	'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
); }
function delete_option( string $key ): bool { $GLOBALS['migration_deleted'][] = array( $GLOBALS['migration_blog'], $key ); return true; }
function delete_transient( string $key ): bool { return true; }
function is_multisite(): bool { return true; }
function get_sites( array $options ): array { return array( 1, 2 ); }
function switch_to_blog( int $site ): void { $GLOBALS['migration_stack'][] = $GLOBALS['migration_blog']; $GLOBALS['migration_blog'] = $site; }
function restore_current_blog(): void { $GLOBALS['migration_blog'] = array_pop( $GLOBALS['migration_stack'] ); }
final class WPD_Migration_Db {
	public string $options = 'wp_options';
	public function esc_like( string $pattern ): string { return $pattern; }
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_col( string $sql ): array { return array(); }
}
$GLOBALS['wpdb'] = new WPD_Migration_Db();
require dirname( __DIR__ ) . '/uninstall.php';
if ( 4 !== count( $GLOBALS['migration_deleted'] ) || 1 !== $GLOBALS['migration_blog'] ) {
	fwrite( STDERR, "FAIL explicit multisite purge\n" ); exit( 1 );
}
echo "PASS explicitly requested purge only, per site\n";
