<?php
/** Regression MG12: explicit retention with a usable plugin inventory. */
declare(strict_types=1);
define( 'ABSPATH', '/tmp/wp-disposable/' );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
define( 'WPD_KEEP_DATA_ON_UNINSTALL', 1 );
$GLOBALS['deleted'] = array();
function get_plugins(): array {
	return array( 'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ) );
}
function delete_option( string $key ): bool { $GLOBALS['deleted'][] = $key; return true; }
function delete_transient( string $key ): bool { return true; }
function is_multisite(): bool { return false; }
final class WPD_Mutant_Test_Db {
	public string $options = 'wp_options';
	public function esc_like( string $pattern ): string { return $pattern; }
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_col( string $sql ): array { return array(); }
}
$GLOBALS['wpdb'] = new WPD_Mutant_Test_Db();
require dirname( __DIR__ ) . '/uninstall.php';
if ( array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL integer opt-in discarded sensitive data\n" );
	exit( 1 );
}
echo "PASS explicit integer retention with inventory available\n";
