<?php
/**
 * Bundled fonts: local-only CSS, allowlist and editor options.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPD_PLUGIN_URL', 'https://wp.example.test/plugins/wp-piwigo-display/' );
define( 'WPD_VERSION', '3.0.1' );
function sanitize_key( $value ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) ); }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function current_user_can( $name ): bool { return true; }
function add_filter( ...$args ): void {}
function add_action( ...$args ): void {}
$GLOBALS['css'] = array();
$GLOBALS['scripts'] = array();
$GLOBALS['localized'] = array();
function wp_enqueue_style( ...$args ): void {}
function wp_enqueue_script( $handle, ...$args ): void { $GLOBALS['scripts'][] = $handle; }
function wp_add_inline_style( $handle, $css ): void { $GLOBALS['css'][] = $css; }
function wp_localize_script( $handle, $name, $data ): void { $GLOBALS['localized'][ $handle . '/' . $name ] = $data; }

require_once dirname( __DIR__ ) . '/includes/class-wpd-user-fonts.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-bundled-fonts.php';
$check = static function ( bool $pass, string $label ): void {
    if ( ! $pass ) { throw new RuntimeException( $label ); }
};
$fonts = WPD_Bundled_Fonts::ui_fonts();
$check( count( $fonts ) === 2, 'Unexpected bundled font catalog count' );
$check( array_column( $fonts, 'value' ) === array( 'bundled-bebas-neue', 'bundled-bungee' ), 'Unexpected allowed font identifiers' );
$check( array() === WPD_User_Fonts::ui_fonts(), 'User uploads must not be activated in this candidate' );
$html = '<div style="font-family:inherit;">Photo-filled text</div>';
foreach ( array( 'bundled-bebas-neue', 'bundled-bungee' ) as $name ) {
    $rendered = WPD_Bundled_Fonts::apply_photo_text_font( $html, array(), array( 'layout' => 'photo-text', 'photo_text_font' => $name ), 'gallery' );
    $check( $rendered !== $html && str_contains( $rendered, 'wpd-bundled-' ), 'Built-in font not applied: ' . $name );
}
$check( count( $GLOBALS['css'] ) === 2, 'Expected exactly two local @font-face declarations' );
foreach ( $GLOBALS['css'] as $css ) {
    $check( str_contains( $css, WPD_PLUGIN_URL . 'assets/fonts/' ), 'Local font asset path missing' );
    $check( ! preg_match( '~https?://(?!wp\\.example\\.test)~', $css ), 'External font host was added' );
}
$check( $html === WPD_Bundled_Fonts::apply_photo_text_font( $html, array(), array( 'layout' => 'photo-text', 'photo_text_font' => 'bundled-not-a-font' ), 'gallery' ), 'Unexpected font accepted' );
$check( $html === WPD_Bundled_Fonts::apply_photo_text_font( $html, array(), array( 'layout' => 'gallery', 'photo_text_font' => 'bundled-bungee' ), 'gallery' ), 'Unrelated gallery changed' );
WPD_Bundled_Fonts::enqueue_admin_assets( 'post.php' );
WPD_Bundled_Fonts::localize_block_editor_fonts();
$check( in_array( 'wpd-user-fonts-ui', $GLOBALS['scripts'], true ), 'Classic Editor font choices JS missing' );
$check( count( $GLOBALS['localized']['wpd-user-fonts-ui/WPDUserFonts'] ?? array() ) === 2, 'Classic Editor font choices missing' );
$check( count( $GLOBALS['localized']['wpd-block-masonry-controls/WPDUserFonts'] ?? array() ) === 2, 'Gutenberg font choices missing' );
foreach ( array( 'bebas-neue/BebasNeue-Regular.woff2', 'bungee/Bungee-Regular.woff2' ) as $file ) {
    $root = dirname( __DIR__ ) . '/assets/fonts/';
    $check( is_file( $root . $file ), 'Bundled font file not packaged' );
    $check( is_file( $root . dirname( $file ) . '/OFL.txt' ), 'Required OFL license missing' );
}
echo "Bundled fonts: local CSS, allowlist, two editor catalogs and licenses PASS.\n";
