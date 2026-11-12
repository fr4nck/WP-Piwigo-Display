<?php
/** A final uninstall without a sibling must clean up service-account credentials. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
$GLOBALS['migration_deleted'] = array();
function get_plugins(): array { return array(
	'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
); }
function delete_option( string $key ): bool { $GLOBALS['migration_deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { return true; }
function is_multisite(): bool { return false; }
final class WPD_Migration_Uninstall_Db {
	public string $options = 'wp_options';
	public function esc_like( string $pattern ): string { return $pattern; }
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_col( string $sql ): array { return array(); }
}
$GLOBALS['wpdb'] = new WPD_Migration_Uninstall_Db();
require dirname( __DIR__ ) . '/uninstall.php';
sort( $GLOBALS['migration_deleted'] );
$expected = array( 'wp_piwigo_display_options', 'wp_piwigo_display_service_account' );
sort( $expected );
if ( $expected !== $GLOBALS['migration_deleted'] ) {
	fwrite( STDERR, "FAIL last-copy uninstall did not clean credentials\n" );
	exit( 1 );
}
echo "PASS final uninstall removes both sensitive plugin options\n";
