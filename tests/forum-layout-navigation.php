<?php
/**
 * Runtime checks of optional Piwigo navigation in Justified and Collage.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPD_PLUGIN_URL', 'https://wp.example.test/plugins/wp-piwigo-display/' );
define( 'WPD_VERSION', '3.0.1' );

$GLOBALS['scripts'] = array();
function add_filter( $name, $callable, $priority = 10, $args = 1 ): void {}
function wp_enqueue_style( ...$args ): void {}
function wp_enqueue_script( $name ): void { $GLOBALS['scripts'][] = $name; }
function absint( $value ): int { return abs( (int) $value ); }
function untrailingslashit( $value ): string { return rtrim( (string) $value, '/' ); }
function wp_parse_url( $url ) { return parse_url( $url ); }
function esc_url_raw( $url, $protocols = array() ): string { return (string) $url; }
function esc_url( $url ): string { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ): string { return esc_attr( $value ); }
function wp_strip_all_tags( $value ): string { return strip_tags( (string) $value ); }
final class WPD_Settings {
    public static function get_default_caption(): string { return 'none'; }
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-renderer.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-justified.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-collage.php';

$check = static function ( bool $pass, string $message ): void {
    if ( ! $pass ) {
        throw new RuntimeException( $message );
    }
};

$base   = 'https://photos.example.test/piwigo';
$images = array();
for ( $i = 1; $i <= 6; ++$i ) {
    $images[] = array(
        'id' => $i,
        'name' => 'Image ' . $i,
        'width' => 1200,
        'height' => 800,
        'page_url' => $base . '/picture.php?/' . $i . '/category/42',
        'derivatives' => array(
            'medium' => array( 'url' => $base . '/medium-' . $i . '.jpg' ),
            'large' => array( 'url' => $base . '/large-' . $i . '.jpg' ),
        ),
    );
}

$base_atts = array(
    '_wpd_album_id' => 42,
    '_wpd_piwigo_url' => $base,
    'lightbox' => 'true',
    'caption' => 'none',
);
$layouts = array(
    'justified' => array( WPD_Justified::class, 'render' ),
    'collage' => array( WPD_Collage::class, 'render' ),
);
foreach ( $layouts as $layout => $renderer ) {
    foreach ( array( 'default', 'piwigo_photo', 'piwigo_album' ) as $action ) {
        $atts = array_merge( $base_atts, array( 'layout' => $layout, 'click_action' => $action ) );
        $GLOBALS['scripts'] = array();
        $html = call_user_func( $renderer, null, $images, $atts, 'gallery' );
        $check( is_string( $html ), $layout . ' must render HTML' );
        $check( 6 === substr_count( $html, '<figure ' ), $layout . ': expected six images' );
        $expected = 'default' === $action
            ? $base . '/large-1.jpg'
            : ( 'piwigo_photo' === $action ? $base . '/picture.php?/1/category/42' : $base . '/index.php?/category/42' );
        $check( str_contains( $html, 'href="' . $expected . '"' ), $layout . ': wrong link in ' . $action );
        $flag = 'default' === $action ? 'true' : 'false';
        $check( 6 === substr_count( $html, 'data-wpd-lightbox="' . $flag . '"' ), $layout . ': wrong lightbox in ' . $action );
        $check( ( 'default' === $action ) === in_array( 'wp-piwigo-display', $GLOBALS['scripts'], true ), $layout . ': wrong scripts in ' . $action );
        $check( ! str_contains( $html, '/picture.php?/7' ), $layout . ': inserted an extra image' );
    }

    $bad = $images;
    $bad[0]['page_url'] = 'https://outside.example.test/picture.php?/1/category/42';
    $atts = array_merge( $base_atts, array( 'layout' => $layout, 'click_action' => 'piwigo_photo' ) );
    $html = call_user_func( $renderer, null, $bad, $atts, 'gallery' );
    $check( ! str_contains( $html, 'outside.example.test' ), $layout . ': unsafe external photo URL' );
    $check( str_contains( $html, 'href="' . $base . '/index.php?/category/42"' ), $layout . ': missing safe album fallback' );

    $atts = array_merge( $base_atts, array( 'layout' => $layout, 'click_action' => 'default', 'lightbox' => 'false' ) );
    $GLOBALS['scripts'] = array();
    $html = call_user_func( $renderer, null, $images, $atts, 'gallery' );
    $check( 6 === substr_count( $html, 'data-wpd-lightbox="false"' ), $layout . ': lightbox-disabled links must be inactive' );
    $check( ! in_array( 'wp-piwigo-display', $GLOBALS['scripts'], true ), $layout . ': disabled lightbox script enqueued' );
}

echo "Navigation Piwigo Justified/Collage : rendu, liens, sécurité et lightbox PASS.\n";
