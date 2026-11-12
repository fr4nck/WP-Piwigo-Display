<?php
/** Persistent content identifiers must never change during distribution migration. */
declare(strict_types=1);
$root = dirname( __DIR__ );
$block = json_decode( (string) file_get_contents( $root . '/blocks/piwigo/block.json' ), true );
$shortcode = (string) file_get_contents( $root . '/includes/class-wpd-shortcode.php' );
$settings = (string) file_get_contents( $root . '/includes/class-wpd-settings.php' );
$service = (string) file_get_contents( $root . '/includes/class-wpd-service-account.php' );
if ( 'wp-piwigo-display/gallery' !== ( $block['name'] ?? '' )
	|| ! str_contains( $shortcode, "add_shortcode( 'piwigo'" )
	|| ! str_contains( $settings, 'wp_piwigo_display_options' )
	|| ! str_contains( $service, 'wp_piwigo_display_service_account' ) ) {
	fwrite( STDERR, "FAIL persistent gallery/shortcode/settings identity changed\n" );
	exit( 1 );
}
echo "PASS persistent Gutenberg, shortcode and option identities unchanged\n";
