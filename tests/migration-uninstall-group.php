<?php
/**
 * Group uninstall regression: no function redeclare, preserve until last copy.
 *
 * Uses disposable copies and only fake options, never a WordPress installation.
 */
declare(strict_types=1);

$root = sys_get_temp_dir() . '/wpd-group-' . bin2hex( random_bytes( 8 ) );
$old = $root . '/wp-piwigo-display';
$new = $root . '/display4piwigo';
mkdir( $old, 0700, true );
mkdir( $new, 0700, true );
copy( dirname( __DIR__ ) . '/uninstall.php', $old . '/uninstall.php' );
copy( dirname( __DIR__ ) . '/uninstall.php', $new . '/uninstall.php' );
file_put_contents( $old . '/wp-piwigo-display.php', "<?php\n" );
file_put_contents( $new . '/wp-piwigo-display.php', "<?php\n" );
register_shutdown_function(
	static function () use ( $root, $old, $new ): void {
		foreach ( array( $old, $new ) as $folder ) {
			foreach ( array( 'uninstall.php', 'wp-piwigo-display.php' ) as $file ) {
				$path = $folder . '/' . $file;
				if ( is_file( $path ) ) {
					unlink( $path );
				}
			}
			if ( is_dir( $folder ) ) {
				rmdir( $folder );
			}
		}
		if ( is_dir( $root ) ) {
			rmdir( $root );
		}
	}
);

define( 'ABSPATH', $root . '/' );
define( 'WP_PLUGIN_DIR', $root );
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
$GLOBALS['deleted'] = array();
function plugin_basename( string $file ): string {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}
function get_plugins(): array {
	return array(
		'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
		'display4piwigo/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display (WordPress.org)' ),
	);
}
function delete_option( string $key ): bool {
	$GLOBALS['deleted'][] = $key;
	return true;
}
function delete_transient( string $key ): bool { return true; }
function is_multisite(): bool { return false; }
final class WPD_Group_Db {
	public string $options = 'wp_options';
	public function esc_like( string $value ): string { return $value; }
	public function prepare( string $sql, ...$parameters ): string { return $sql; }
	public function get_col( string $sql ): array { return array(); }
}
$GLOBALS['wpdb'] = new WPD_Group_Db();

require $old . '/uninstall.php';
if ( array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL first uninstall destroyed shared data\n" );
	exit( 1 );
}
unlink( $old . '/wp-piwigo-display.php' );
require $new . '/uninstall.php';
sort( $GLOBALS['deleted'] );
$expected = array( 'wp_piwigo_display_options', 'wp_piwigo_display_service_account', 'wpd_custom_svg_masks' );
sort( $expected );
if ( $expected !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL second uninstall did not clean credentials\n" );
	exit( 1 );
}
echo "PASS grouped uninstall preserves until last copy, no function collision\n";
