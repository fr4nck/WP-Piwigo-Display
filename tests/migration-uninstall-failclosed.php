<?php
/** Regression MG13: missing inventory must never trigger destructive cleanup. */
declare(strict_types=1);
define( 'ABSPATH', '/tmp/no-real-wordpress-install/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
$GLOBALS['deleted'] = array();
function delete_option( string $key ): bool { $GLOBALS['deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { return true; }
function is_multisite(): bool { return false; }
// Deliberately no get_plugins() and no readable wp-admin/includes/plugin.php.
final class WPD_Mutant_Test_Db {
	public string $options = 'wp_options';
	public function esc_like( string $pattern ): string { return $pattern; }
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_col( string $sql ): array { return array(); }
}
$GLOBALS['wpdb'] = new WPD_Mutant_Test_Db();
require dirname( __DIR__ ) . '/uninstall.php';
if ( array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL uninstall purged despite unknown installed copies\n" );
	exit( 1 );
}
echo "PASS unknown plugin inventory retains sensitive data\n";
