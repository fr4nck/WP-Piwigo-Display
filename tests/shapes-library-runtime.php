<?php
/**
 * Verifies shape-library compatibility and runtime output.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function sanitize_key( $value ): string {
    return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) );
}
function remove_accents( $value ): string {
    return strtr( (string) $value, array( 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'œ' => 'oe' ) );
}
function absint( $value ): int {
    return abs( (int) $value );
}
function esc_attr( $value ): string {
    return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
$GLOBALS['shape_styles'] = array();
function wp_enqueue_style( $name ): void {
    $GLOBALS['shape_styles'][] = $name;
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-shapes.php';

$assert = static function ( bool $ok, string $message ): void {
    if ( ! $ok ) {
        throw new RuntimeException( $message );
    }
};

$allowed = array(
    'rectangle', 'rounded', 'circle', 'oval', 'pill', 'star', 'hexagon',
    'diamond', 'cloud', 'heart', 'drop', 'triangle', 'pentagon', 'octagon',
    'card-spade', 'card-heart', 'card-diamond', 'card-club',
);
$method = new ReflectionMethod( WPD_Shapes::class, 'sanitize_shape' );
foreach ( $allowed as $shape ) {
    $assert( $shape === $method->invoke( null, $shape ), 'Rejected builtin: ' . $shape );
}
foreach ( array( 'cœur' => 'heart', 'étoile' => 'star', 'nuage' => 'cloud', 'pique' => 'card-spade' ) as $input => $expected ) {
    $assert( $expected === $method->invoke( null, $input ), 'Alias failure: ' . $input );
}
$assert( 'rectangle' === $method->invoke( null, '<script>alert(1)</script>' ), 'Bad shape must fall back to rectangle' );

$markup = '<div class="wp-piwigo-display wp-piwigo-display-collage"><a href="https://photos.example.test/a">Photo</a></div>';
foreach ( $allowed as $shape ) {
    $GLOBALS['shape_styles'] = array();
    $out = WPD_Shapes::apply_shape( $markup, 'piwigo', array( 'shape' => $shape ), array() );
    if ( 'rectangle' === $shape ) {
        $assert( $markup === $out && array() === $GLOBALS['shape_styles'], 'Default shape changed legacy output' );
        continue;
    }
    $assert( str_contains( $out, 'wpd-shape-' . $shape ), 'No class for ' . $shape );
    $assert( str_contains( $out, 'href="https://photos.example.test/a"' ), 'Shape altered photo link' );
    $assert( in_array( 'wpd-shapes', $GLOBALS['shape_styles'], true ), 'Shape CSS missing' );
}
$assert(
    $markup === WPD_Shapes::apply_shape( $markup, 'other_shortcode', array( 'shape' => 'star' ), array() ),
    'Shape must not affect another shortcode'
);
$rounded = WPD_Shapes::apply_shape( $markup, 'piwigo', array( 'rounded' => 'true', 'radius' => 80 ), array() );
$assert( str_contains( $rounded, 'wpd-shape-rounded' ) && str_contains( $rounded, '--wpd-shape-radius:50%' ), 'Radius was not bounded' );

$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/wp-piwigo-display-shapes.css' );
$editor = file_get_contents( dirname( __DIR__ ) . '/blocks/piwigo/shapes.js' );
$classic = file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-classic-editor.php' );
$composer = file_get_contents( dirname( __DIR__ ) . '/assets/js/wp-piwigo-display-composer-parity.js' );
$picker = file_get_contents( dirname( __DIR__ ) . '/assets/css/wp-piwigo-display-shape-picker.css' );
foreach ( array( 'cloud', 'heart', 'drop', 'triangle', 'pentagon', 'octagon', 'card-spade', 'card-heart', 'card-diamond', 'card-club' ) as $shape ) {
    $assert( str_contains( $css, '.wpd-shape-' . $shape ), 'Missing CSS for ' . $shape );
    $assert( str_contains( $classic, 'value="' . $shape . '"' ), 'Missing Classic Editor option ' . $shape );
    $assert( str_contains( $composer, 'value="' . $shape . '"' ), 'Missing composer option ' . $shape );
    $assert( str_contains( $editor, "['" . $shape . "'" ), 'Missing Gutenberg option ' . $shape );
}
foreach ( array( 'justified', 'collage' ) as $layout ) {
    $assert( str_contains( $css, '.wp-piwigo-display-' . $layout . '-item a' ), 'Shapes do not include ' . $layout );
}
$assert( str_contains( $picker, '.wpd-shape-picker-grid' ) && str_contains( $editor, 'aria-pressed' ), 'Accessible previews missing' );
echo "Shape library: 18 ids, aliases, runtime links, CSS and editors PASS.\n";
