<?php
/** Regression CA-301-002: visiting a site profile must not deactivate network plugins. */
declare(strict_types=1);
define( 'ABSPATH', '/tmp/wp-disposable/' );
$GLOBALS['hooks'] = array();
$GLOBALS['deleted'] = array();
$GLOBALS['authorized'] = false;
function plugin_basename( string $file ): string {
	return str_starts_with( $file, '/tmp/wp-disposable/wp-content/plugins/' )
		? substr( $file, strlen( '/tmp/wp-disposable/wp-content/plugins/' ) )
		: $file;
}
function register_activation_hook( string $entrypoint, callable $callback ): void {}
function add_action( string $name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
	$GLOBALS['hooks'][ $name ][] = $callback;
}
function get_option( string $key, $default = array() ) {
	return 'active_plugins' === $key ? array( 'wp-piwigo-display/wp-piwigo-display.php' ) : $default;
}
function get_site_option( string $key, $default = array() ) {
	return 'active_sitewide_plugins' === $key ? array( 'display4piwigo/wp-piwigo-display.php' => 1 ) : $default;
}
function get_plugins(): array {
	return array(
		'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
		'display4piwigo/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
	);
}
function is_multisite(): bool { return true; }
function current_user_can( string $capability ): bool { return $GLOBALS['authorized']; }
function esc_html__( string $string, string $domain ): string { return $string; }
function wp_die( string $message, string $title = '', array $opts = array() ): void { throw new RuntimeException( $message ); }
function deactivate_plugins( $plugin, bool $silent = false, $network_wide = null ): void {
	$GLOBALS['deleted'][] = array( $plugin, $network_wide );
}
require dirname( __DIR__ ) . '/includes/class-wpd-migration-guard.php';
WPD_Migration_Guard::register( '/tmp/wp-disposable/wp-content/plugins/display4piwigo/wp-piwigo-display.php' );

ob_start();
foreach ( $GLOBALS['hooks']['admin_notices'] ?? array() as $callback ) {
	$callback();
}
$subscriber_html = ob_get_clean();
if ( '' !== $subscriber_html || array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL subscriber triggered a network mutation\n" );
	exit( 1 );
}
$GLOBALS['authorized'] = true;
ob_start();
foreach ( $GLOBALS['hooks']['admin_notices'] ?? array() as $callback ) {
	$callback();
}
$admin_html = ob_get_clean();
if ( ! str_contains( $admin_html, 'Deux copies' ) || array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL administrator notice mutated network plugin state\n" );
	exit( 1 );
}
echo "PASS subscriber and admin profile visits never deactivate network copy\n";
