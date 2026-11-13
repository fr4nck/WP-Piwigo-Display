<?php
/**
 * Runtime acceptance tests for accessible photo-filled typography.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPD_PLUGIN_URL', 'https://wp.example.test/plugins/wp-piwigo-display/' );
define( 'WPD_VERSION', '3.0.1' );

function sanitize_key( $value ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) ); }
function sanitize_textarea_field( $value ): string { return strip_tags( (string) $value ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_hex_color( $value ) { return preg_match( '/^#[a-f0-9]{3}(?:[a-f0-9]{3})?$/i', (string) $value ) ? $value : null; }
function absint( $value ): int { return abs( (int) $value ); }
function esc_html( $text ): string { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ): string { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ): string {
    return preg_match( '~^https?://~', (string) $url ) ? esc_attr( $url ) : '';
}
$GLOBALS['photo_styles'] = array();
function wp_enqueue_style( $name, ...$args ): void { $GLOBALS['photo_styles'][] = $name; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ): void {}

require_once dirname( __DIR__ ) . '/includes/class-wpd-user-fonts.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-photo-text.php';

$check = static function ( bool $pass, string $description ): void {
    if ( ! $pass ) { throw new RuntimeException( $description ); }
};
$images = array();
for ( $i = 1; $i <= 8; ++$i ) {
    $images[] = array(
        'id' => $i,
        'name' => 'Photo ' . $i,
        'derivatives' => array( 'medium' => array( 'url' => 'https://photos.example.test/medium-' . $i . '.jpg' ) ),
    );
}
$base = array(
    'layout' => 'photo-text',
    'photo_text' => 'PÊLE\\nMÊLE',
    'photo_text_seed' => '42',
    'photo_text_font' => 'system',
    'photo_text_fill_mode' => 'grid',
    'photo_text_max_images' => '3',
    'photo_text_outline' => 'true',
);
$check( 'unchanged' === WPD_Photo_Text::render( 'unchanged', $images, array(), 'gallery' ), 'Unrelated layout changed' );
foreach ( array( 'grid', 'masonry', 'collage' ) as $mode ) {
    $atts = array_merge( $base, array( 'photo_text_fill_mode' => $mode ) );
    $GLOBALS['photo_styles'] = array();
    $first = WPD_Photo_Text::render( null, $images, $atts, 'gallery' );
    $second = WPD_Photo_Text::render( null, $images, $atts, 'gallery' );
    $check( is_string( $first ) && str_contains( $first, 'wpd-photo-text-fill-' . $mode ), 'Missing mode ' . $mode );
    $check( str_contains( $first, '<clipPath' ) && str_contains( $first, 'wpd-photo-text-semantic' ), 'Inaccessible layout ' . $mode );
    $check( str_contains( $first, 'aria-hidden="true"' ), 'Decorative image must be hidden from screen readers' );
    $check( str_contains( $first, 'PÊLE' ) && str_contains( $first, 'MÊLE' ), 'Text lost or multiline failed' );
    $check( in_array( 'wp-piwigo-display-photo-text', $GLOBALS['photo_styles'], true ), 'Style not enqueued' );
    $check( ! str_contains( $first, '/medium-4.jpg' ) && ! str_contains( $first, '/medium-8.jpg' ), 'Image limit not respected' );
    $normalized1 = preg_replace( '/wpd-photo-text-[0-9]+-[a-f0-9]{8}/', 'wpd-photo-text-ID', $first );
    $normalized2 = preg_replace( '/wpd-photo-text-[0-9]+-[a-f0-9]{8}/', 'wpd-photo-text-ID', $second );
    $check( $normalized1 === $normalized2, 'Composition is not deterministic for mode ' . $mode );
}
$bad = array_merge( $base, array( 'photo_text' => '<script>alert(1)</script> Test', 'photo_text_outline_color' => 'url(https://bad.test)' ) );
$rendered = WPD_Photo_Text::render( null, $images, $bad, 'gallery' );
$check( ! str_contains( $rendered, '<script' ) && ! str_contains( $rendered, 'bad.test' ), 'Input reflected unsafely' );
$check( 'inherit' === WPD_User_Fonts::font_stack( 'url(https://bad.test/f.woff)' ), 'Arbitrary font injected' );
$check( 'unchanged' === WPD_Photo_Text::render( 'unchanged', array(), $base, 'gallery' ), 'Empty album changed' );
$check( 'unchanged' === WPD_Photo_Text::render( 'unchanged', $images, array_merge( $base, array( 'photo_text' => '' ) ), 'gallery' ), 'Empty text changed' );

echo "Photo-filled typography: three deterministic layouts, bounds, semantic text and injection safety PASS.\n";
