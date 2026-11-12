<?php
/** Tests a migration guard in isolated WordPress shims; no live site touched. */
declare(strict_types=1);
define( 'ABSPATH', '/tmp/wpdisposable/' );

final class WPD_Migration_Blocked extends RuntimeException {}
$GLOBALS['migration_checks'] = 0;
$GLOBALS['migration_hooks'] = array();
$GLOBALS['migration_activate'] = array();
$GLOBALS['migration_deactivated'] = array();
$GLOBALS['migration_multi'] = false;
$GLOBALS['migration_blog'] = 1;
$GLOBALS['migration_blog_stack'] = array();
$GLOBALS['migration_active'] = array( 1 => array() );
$GLOBALS['migration_sitewide'] = array();
$GLOBALS['migration_installed'] = array(
	'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
	'display4piwigo/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ),
	'other-sibling/different-main.php' => array( 'Name' => 'Piwigo Display' ),
);
function plugin_basename( string $file ): string { return str_starts_with( $file, '/tmp/wpdisposable/wp-content/plugins/' ) ? substr( $file, strlen( '/tmp/wpdisposable/wp-content/plugins/' ) ) : $file; }
function register_activation_hook( string $file, callable $cb ): void { $GLOBALS['migration_activate'][ plugin_basename( $file ) ] = $cb; }
function add_action( string $hook, callable $cb, int $priority = 10 ): void { $GLOBALS['migration_hooks'][ $hook ][] = $cb; }
function get_option( string $key, $default = array() ) { return 'active_plugins' === $key ? ( $GLOBALS['migration_active'][ $GLOBALS['migration_blog'] ] ?? array() ) : $default; }
function get_site_option( string $key, $default = array() ) { return 'active_sitewide_plugins' === $key ? $GLOBALS['migration_sitewide'] : $default; }
function get_plugins(): array { return $GLOBALS['migration_installed']; }
function is_multisite(): bool { return $GLOBALS['migration_multi']; }
function get_sites( array $args ): array { return array( 1, 2 ); }
function switch_to_blog( int $site ): void { $GLOBALS['migration_blog_stack'][] = $GLOBALS['migration_blog']; $GLOBALS['migration_blog'] = $site; }
function restore_current_blog(): void { $GLOBALS['migration_blog'] = array_pop( $GLOBALS['migration_blog_stack'] ); }
function esc_html__( string $text, string $domain ): string { return $text; }
function wp_die( string $message, string $title = '', array $params = array() ): void { throw new WPD_Migration_Blocked( $message ); }
function current_user_can( string $capability ): bool { return (bool) ( $GLOBALS['migration_can_activate'] ?? false ); }
function deactivate_plugins( $plugin, bool $silent = false, $network_wide = null ): void { $GLOBALS['migration_deactivated'][] = array( $plugin, $silent, $network_wide ); }
function migration_check( string $label, bool $pass ): void {
	++$GLOBALS['migration_checks'];
	if ( ! $pass ) { fwrite( STDERR, 'FAIL ' . $label . "\n" ); exit( 1 ); }
	echo 'PASS ' . $label . "\n";
}
require dirname( __DIR__ ) . '/includes/class-wpd-migration-guard.php';
$self = '/tmp/wpdisposable/wp-content/plugins/display4piwigo/wp-piwigo-display.php';
$name = plugin_basename( $self );
$old = 'wp-piwigo-display/wp-piwigo-display.php';

WPD_Migration_Guard::register( $self );
$activate = $GLOBALS['migration_activate'][ $name ];
$activate( false );
migration_check( 'fresh activation permitted', true );

$GLOBALS['migration_active'][1] = array( $old );
try {
	$activate( false );
	migration_check( 'legacy conflict must block activation', false );
} catch ( WPD_Migration_Blocked $e ) {
	migration_check( 'legacy activation blocked', str_contains( $e->getMessage(), 'Désactivez' ) );
}
migration_check( 'blocked activation keeps old active', $GLOBALS['migration_active'][1] === array( $old ) );

$GLOBALS['migration_active'][1] = array( $old, $name );
$GLOBALS['migration_can_activate'] = false;
ob_start();
foreach ( $GLOBALS['migration_hooks']['admin_notices'] as $callback ) { $callback(); }
$subscriber_notice = ob_get_clean();
migration_check( 'subscriber sees no repair action', '' === $subscriber_notice && array() === $GLOBALS['migration_deactivated'] );

$GLOBALS['migration_can_activate'] = true;
ob_start();
foreach ( $GLOBALS['migration_hooks']['admin_notices'] as $callback ) { $callback(); }
$administrator_notice = ob_get_clean();
migration_check( 'admin sees warning without mutation', str_contains( $administrator_notice, 'Deux copies' ) && array() === $GLOBALS['migration_deactivated'] );

$GLOBALS['migration_active'][1] = array( $name );
$GLOBALS['migration_deactivated'] = array();
foreach ( $GLOBALS['migration_hooks']['admin_notices'] as $callback ) { $callback(); }
migration_check( 'lone new plugin remains active', $GLOBALS['migration_deactivated'] === array() );

$GLOBALS['migration_multi'] = true;
$GLOBALS['migration_active'] = array( 1 => array(), 2 => array( $old ) );
$GLOBALS['migration_blog'] = 1;
try {
	$activate( true );
	migration_check( 'network activation must scan other sites', false );
} catch ( WPD_Migration_Blocked $e ) {
	migration_check( 'network conflict found on site 2', 1 === $GLOBALS['migration_blog'] );
}

$GLOBALS['migration_active'] = array( 1 => array(), 2 => array() );
$GLOBALS['migration_sitewide'] = array( $old => 1 );
try {
	$activate( false );
	migration_check( 'network active old must block site activation', false );
} catch ( WPD_Migration_Blocked $e ) {
	migration_check( 'old network copy blocks new site copy', true );
}

$GLOBALS['migration_multi'] = false;
$GLOBALS['migration_sitewide'] = array();
$GLOBALS['migration_active'][1] = array( 'other-sibling/different-main.php' );
try {
	$activate( false );
	migration_check( 'other same-name plugin must block', false );
} catch ( WPD_Migration_Blocked $e ) {
	migration_check( 'same name different entrypoint detected', true );
}
echo 'TOTAL ' . $GLOBALS['migration_checks'] . " migration guard PASS (mock WordPress only)\n";
