<?php
/**
 * Regression checks for public/service-account album picker routing.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root      = dirname( __DIR__ );
$bootstrap = file_get_contents( $root . '/wp-piwigo-display.php' );
$plugin    = file_get_contents( $root . '/includes/class-wpd-plugin.php' );
$service   = file_get_contents( $root . '/includes/class-wpd-service-account.php' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $plugin, "add_action( 'wp_ajax_wpd_get_albums', array( \$this, 'ajax_get_albums' ) )" ), 'The anonymous album-picker AJAX handler must remain registered.' );
$assert( false !== strpos( (string) $service, "add_action( 'wp_ajax_wpd_get_albums', array( self::class, 'ajax_get_albums' ), 1 )" ), 'The service-account album-picker handler must retain priority over the anonymous handler when configured.' );
$assert( false !== strpos( (string) $bootstrap, "if ( ! WPD_Service_Account::is_configured() )" ), 'The bootstrap must detect the absence of a configured service account.' );
$assert( false !== strpos( (string) $bootstrap, "remove_action( 'wp_ajax_wpd_get_albums', array( WPD_Service_Account::class, 'ajax_get_albums' ), 1 )" ), 'Without a service account, the priority handler must be removed so the public picker can answer.' );

$remove_position = strpos( (string) $bootstrap, "remove_action( 'wp_ajax_wpd_get_albums'" );
$classic_position = strpos( (string) $bootstrap, 'WPD_Classic_Editor::register()' );
$assert( false !== $remove_position && false !== $classic_position && $remove_position < $classic_position, 'Album-picker routing must be settled during bootstrap before editor integrations run.' );

echo "Album picker public/service routing: OK\n";
