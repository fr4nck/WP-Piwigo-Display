<?php
/** Regression: new distribution protects against activating/removing legacy 3.0.0. */
declare(strict_types=1);
define( 'ABSPATH', '/tmp/wp-disposable/' );
final class WPD_Activation_Blocked extends RuntimeException {}
$GLOBALS['hooks'] = array();
$GLOBALS['activation'] = array();
$GLOBALS['active'] = array( 'display4piwigo/wp-piwigo-display.php' );
function plugin_basename( string $path ): string {
	return str_starts_with( $path, '/tmp/wp-disposable/wp-content/plugins/' )
		? substr( $path, strlen( '/tmp/wp-disposable/wp-content/plugins/' ) )
		: $path;
}
function register_activation_hook( string $entrypoint, callable $callback ): void { $GLOBALS['activation'][ plugin_basename( $entrypoint ) ] = $callback; }
function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
	$GLOBALS['hooks'][ $hook ][] = $callback;
}
function get_option( string $name, $default = array() ) { return 'active_plugins' === $name ? $GLOBALS['active'] : $default; }
function get_plugins(): array {
	return array(
		'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display', 'Version' => '3.0.0' ),
		'display4piwigo/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display', 'Version' => '3.0.1' ),
	);
}
function is_multisite(): bool { return false; }
function esc_html__( string $text, string $domain ): string { return $text; }
function wp_die( string $message, string $title = '', array $options = array() ): void { throw new WPD_Activation_Blocked( $message ); }
function current_user_can( string $capability ): bool { return false; }
require dirname( __DIR__ ) . '/includes/class-wpd-migration-guard.php';
WPD_Migration_Guard::register( '/tmp/wp-disposable/wp-content/plugins/display4piwigo/wp-piwigo-display.php' );
if ( ! isset( $GLOBALS['hooks']['activate_plugin'], $GLOBALS['hooks']['pre_uninstall_plugin'] ) ) {
	fwrite( STDERR, "FAIL inverse activation and uninstall protection must register\n" );
	exit( 1 );
}
$blocked = 0;
foreach ( array( 'activate_plugin', 'pre_uninstall_plugin' ) as $hook ) {
	try {
		foreach ( $GLOBALS['hooks'][ $hook ] as $callback ) {
			$callback( 'wp-piwigo-display/wp-piwigo-display.php', false );
		}
	} catch ( WPD_Activation_Blocked $error ) {
		++$blocked;
	}
}
if ( 2 !== $blocked || array( 'display4piwigo/wp-piwigo-display.php' ) !== $GLOBALS['active'] ) {
	fwrite( STDERR, "FAIL legacy activation/removal was not refused without state mutation\n" );
	exit( 1 );
}
echo "PASS inverse activation and legacy uninstall blocked while new copy active\n";
