<?php
/**
 * Regression checks for the independent transparent slider background option.
 *
 * @package WP_Piwigo_Display
 */

$shortcode = file_get_contents( __DIR__ . '/../includes/class-wpd-shortcode.php' );
$renderer  = file_get_contents( __DIR__ . '/../includes/class-wpd-renderer.php' );
$css       = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display.css' );
$block     = file_get_contents( __DIR__ . '/../blocks/piwigo/index.js' );
$metadata  = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$classic   = file_get_contents( __DIR__ . '/../includes/class-wpd-classic-editor.php' );
$classic_js = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$settings  = file_get_contents( __DIR__ . '/../includes/class-wpd-settings.php' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( $shortcode, "\$defaults['transparent_background'] = 'false'" ), 'Le shortcode doit désactiver le fond transparent par défaut.' );
$assert( false !== strpos( $renderer, 'wp-piwigo-display-transparent-background' ), 'Le rendu doit exposer une classe de fond transparent.' );
$assert( false !== strpos( $css, '.wp-piwigo-display-slider.wp-piwigo-display-transparent-background .splide__track' ), 'Le fond transparent doit cibler uniquement la piste du diaporama.' );
$assert( false !== strpos( $css, 'background: transparent;' ), 'Le fond transparent doit utiliser une vraie transparence CSS.' );
$assert( false !== strpos( $metadata, '"transparentBackground"' ), 'Gutenberg doit déclarer l’attribut de fond transparent.' );
$assert( false !== strpos( $block, "__('Fond transparent'" ), 'Gutenberg doit proposer le contrôle de fond transparent.' );
$assert( false !== strpos( $classic, 'data-wpd="transparent_background"' ), 'Classic Editor doit proposer le fond transparent.' );
$assert( false !== strpos( $classic_js, "checked('transparent_background')" ), 'Classic Editor doit sérialiser le fond transparent.' );
$assert( false !== strpos( $settings, 'wpd-c-transparent-background' ), 'Le composeur d’administration doit proposer le fond transparent.' );

define( 'ABSPATH', __DIR__ . '/../' );

function sanitize_key( $value ): string {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function apply_filters( $hook, $value ) {
	unset( $hook );
	return $value;
}

function wp_enqueue_style( $handle ): void {
	unset( $handle );
}

function wp_enqueue_script( $handle ): void {
	unset( $handle );
}

function esc_attr( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $value ): string {
	return esc_attr( $value );
}

function esc_attr_e( $value ): void {
	echo esc_attr( $value );
}

function wp_strip_all_tags( $value ): string {
	return strip_tags( (string) $value );
}

function absint( $value ): int {
	return abs( (int) $value );
}

function wp_generate_uuid4(): string {
	return '00000000-0000-4000-8000-000000000000';
}

require_once __DIR__ . '/../includes/class-wpd-renderer.php';

$html = WPD_Renderer::render(
	array(
		array(
			'name'        => 'Photo',
			'element_url' => 'https://example.org/photo.jpg',
		),
	),
	array(
		'type'                   => 'slider',
		'caption'                => 'none',
		'navigation'             => 'none',
		'lightbox'               => 'false',
		'transparent_background' => 'true',
	)
);

$assert( false !== strpos( $html, 'wp-piwigo-display-transparent-background' ), 'Le rendu actif doit contenir la classe de fond transparent.' );

echo "Transparent slider background: OK\n";
