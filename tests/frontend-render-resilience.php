<?php
/**
 * Regression test for malformed image records reaching frontend rendering.
 *
 * @package WP_Piwigo_Display
 */

define( 'ABSPATH', __DIR__ . '/../' );

function sanitize_key( $value ): string {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function apply_filters( $hook, $value ) {
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

function esc_html( $value ): string {
	return esc_attr( $value );
}

function wp_strip_all_tags( $value ): string {
	return strip_tags( (string) $value );
}

function absint( $value ): int {
	return abs( (int) $value );
}

require_once __DIR__ . '/../includes/class-wpd-renderer.php';

$images = array(
	array(
		'id'          => 1,
		'name'        => 'Image valide',
		'element_url' => 'https://example.org/valid.jpg',
	),
	'legacy-cache-marker',
	null,
);

$html = WPD_Renderer::render(
	$images,
	array(
		'type'     => 'gallery',
		'sort'     => 'manual',
		'order'    => 'asc',
		'caption'  => 'none',
		'lightbox' => 'false',
	)
);

if ( false === strpos( $html, 'https://example.org/valid.jpg' ) ) {
	fwrite( STDERR, "L'image valide doit rester rendue.\n" );
	exit( 1 );
}

if ( false !== strpos( $html, 'legacy-cache-marker' ) ) {
	fwrite( STDERR, "Les entrées de cache non tabulaires doivent être ignorées.\n" );
	exit( 1 );
}

echo "Frontend mixed-image resilience: OK\n";
