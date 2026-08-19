<?php
/**
 * Regression checks for neutral release defaults and public naming.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-settings.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$defaults = WPD_Settings::default_options();
$source   = file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-settings.php' );

$assert( '' === ( $defaults['piwigo_url'] ?? null ), 'A public release must not ship with a real Piwigo instance configured by default.' );
$assert( false === strpos( (string) $source, 'phototheque.pelemele.org' ), 'The public settings source must not contain the association Piwigo URL as a default.' );
$assert( false === strpos( (string) $source, "__( 'WP Piwigo Display'" ), 'The administration UI must use the public name Piwigo Display.' );
$assert( false === strpos( (string) $source, "__( 'WP Piwigo'" ), 'The administration menu must use the public name Piwigo Display.' );
$assert( false === strpos( (string) $source, 'Le bloc « WP Piwigo Display »' ), 'Gutenberg help text must use the public name Piwigo Display.' );
$assert( false !== strpos( (string) $source, 'Le compte de service configuré n’est jamais transmis à une URL spécifique différente.' ), 'The custom URL field must disclose service-account credential isolation.' );
$assert( false !== strpos( (string) $source, "public const OPTION_NAME = 'wp_piwigo_display_options'" ), 'The historical option identifier must remain unchanged for upgrades.' );

echo "Release settings defaults and public naming: OK\n";
