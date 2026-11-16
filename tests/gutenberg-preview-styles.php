<?php
/**
 * S-07 regression: dynamic layouts must have their real frontend CSS in
 * Gutenberg preview, including styles normally enqueued during AJAX rendering.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'WPD_PLUGIN_URL', 'https://wp.example.test/wp-content/plugins/wp-piwigo-display/' );
define( 'WPD_VERSION', '3.1.0-rc.2' );

$GLOBALS['preview_styles'] = array();
$GLOBALS['editor_context'] = false;

function is_admin(): bool {
	return (bool) $GLOBALS['editor_context'];
}

function wp_enqueue_style( string $handle, string $src = '', array $dependencies = array(), string $version = '' ): void {
	$GLOBALS['preview_styles'][ $handle ] = array( $src, $dependencies, $version );
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-block.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "S07=FAIL $message\n" );
		exit( 1 );
	}
};

WPD_Block::enqueue_preview_styles();
$assert( array() === $GLOBALS['preview_styles'], 'Editor CSS must not be loaded for non-admin block assets.' );

$GLOBALS['editor_context'] = true;
WPD_Block::enqueue_preview_styles();

$expected = array(
	'wpd-splide'                 => 'assets/vendor/splide/splide.min.css',
	'wp-piwigo-display'         => 'assets/css/wp-piwigo-display.css',
	'wp-piwigo-display-masonry' => 'assets/css/wp-piwigo-display-masonry.css',
	'wp-piwigo-display-justified' => 'assets/css/wp-piwigo-display-justified.css',
	'wp-piwigo-display-collage' => 'assets/css/wp-piwigo-display-collage.css',
	'wp-piwigo-display-photo-text' => 'assets/css/wp-piwigo-display-photo-text.css',
	'wpd-shapes'                => 'assets/css/wp-piwigo-display-shapes.css',
	'wpd-custom-masks'         => 'assets/css/wp-piwigo-display-custom-masks.css',
);
foreach ( $expected as $handle => $asset ) {
	$actual = $GLOBALS['preview_styles'][ $handle ] ?? null;
	$assert( is_array( $actual ), "Missing preview style: $handle." );
	$assert( WPD_PLUGIN_URL . $asset === $actual[0], "Wrong asset URL for $handle." );
	if ( 'wpd-splide' !== $handle ) {
		$assert( WPD_VERSION === $actual[2], "Version mismatch for $handle." );
	}
	if ( 'wpd-splide' !== $handle && 'wp-piwigo-display' !== $handle ) {
		$assert( in_array( 'wp-piwigo-display', $actual[1], true ), "Missing base CSS dependency for $handle." );
	}
}
$assert( count( $expected ) === count( $GLOBALS['preview_styles'] ), 'Unexpected editor style handles loaded.' );

echo "S07=PASS editor preview styles and frontend isolation\n";
