<?php
/**
 * Runtime contract for rendering a stored SVG mask on old and new layouts.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
final class WP_Error {
    public function __construct(private string $code, private string $message) {}
    public function get_error_code(): string { return $this->code; }
}
function __( $message, $domain = '' ): string { return (string) $message; }
function is_wp_error( $result ): bool { return $result instanceof WP_Error; }
function sanitize_key( $text ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $text ) ); }
function sanitize_text_field( $text ): string { return strip_tags( trim( (string) $text ) ); }
function esc_attr( $text ): string { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
$GLOBALS['shape_styles'] = array();
function wp_enqueue_style( $handle, ...$args ): void { $GLOBALS['shape_styles'][] = $handle; }
function get_option( $name, $default = array() ) {
    return 'wpd_custom_svg_masks' === $name ? $GLOBALS['masks'] : $default;
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-svg-mask-sanitizer.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-custom-svg-masks.php';

$assert = static function ( bool $pass, string $message ): void {
    if ( ! $pass ) { throw new RuntimeException( $message ); }
};
$assert( class_exists( 'DOMDocument' ), 'DOM extension required for custom mask runtime qualification' );
$valid = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40"/></svg>';
$GLOBALS['masks'] = array(
    'a1b2c3d4e5f6' => array( 'name' => '<b>Valid</b>', 'svg' => $valid ),
    'eeeeeeeeeeee' => array( 'name' => 'Invalid', 'svg' => '<svg viewBox="0 0 100 100"><script>alert(1)</script></svg>' ),
);
foreach ( array( 'gallery', 'justified', 'collage' ) as $layout ) {
    $html = '<div class="wp-piwigo-display wp-piwigo-display-' . $layout . '"><a href="https://photos.example.test/picture.php?/1">Photo</a></div>';
    $GLOBALS['shape_styles'] = array();
    $rendered = WPD_Custom_SVG_Masks::apply_mask( $html, 'piwigo', array( 'shape' => 'custom-a1b2c3d4e5f6' ), array() );
    $assert( str_contains( $rendered, 'wpd-custom-svg-mask' ), 'Mask class missing for ' . $layout );
    $assert( str_contains( $rendered, '--wpd-custom-svg-mask:url(' ), 'Mask style missing for ' . $layout );
    $assert( str_contains( $rendered, 'href="https://photos.example.test/picture.php?/1"' ), 'Mask replaced photo URL' );
    $assert( ! str_contains( $rendered, '<script' ) && ! str_contains( $rendered, 'javascript:' ), 'Unsanitized XML leaked' );
    $assert( in_array( 'wpd-custom-masks', $GLOBALS['shape_styles'], true ), 'Mask CSS not enqueued' );
    $assert( $html === WPD_Custom_SVG_Masks::apply_mask( $html, 'piwigo', array( 'shape' => 'custom-eeeeeeeeeeee' ), array() ), 'Unsafe stored SVG was rendered' );
    $assert( $html === WPD_Custom_SVG_Masks::apply_mask( $html, 'piwigo', array( 'shape' => 'custom-000000000000' ), array() ), 'Unknown mask altered HTML' );
    $assert( $html === WPD_Custom_SVG_Masks::apply_mask( $html, 'not-piwigo', array( 'shape' => 'custom-a1b2c3d4e5f6' ), array() ), 'Mask altered an unrelated shortcode' );
}
echo "Custom mask rendering runtime: three layouts, safe CSS, isolation and fail-closed behavior PASS.\n";
