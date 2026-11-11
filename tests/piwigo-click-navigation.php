<?php
/**
 * Real renderer checks for optional navigation back to Piwigo.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPD_PLUGIN_URL', 'https://wordpress.example.test/plugins/piwigo-display/' );
define( 'WPD_VERSION', '3.0.0' );

$GLOBALS['enqueued_scripts'] = array();

function check( bool $value, string $message ): void {
	if ( ! $value ) {
		throw new RuntimeException( $message );
	}
}
function absint( $value ): int {
	return abs( (int) $value );
}
function sanitize_key( $value ): string {
	return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) );
}
function apply_filters( $name, $value, ...$args ) {
	if ( 'wp_piwigo_display_render' === $name && 'masonry' === ( $args[1]['layout'] ?? '' ) ) {
		return WPD_Masonry::render( $value, $args[0], $args[1], $args[2] );
	}
	return $value;
}
function wp_enqueue_style( $handle ): void {}
function wp_enqueue_script( $handle ): void {
	$GLOBALS['enqueued_scripts'][] = $handle;
}
function esc_attr( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $value ): string {
	return esc_attr( $value );
}
function esc_url( $url ): string {
	return esc_attr( $url );
}
function esc_url_raw( $url, $protocols = array() ): string {
	return (string) $url;
}
function wp_parse_url( $value ) {
	return parse_url( $value );
}
function untrailingslashit( $value ): string {
	return rtrim( (string) $value, '/' );
}
function wp_strip_all_tags( $value ): string {
	return strip_tags( (string) $value );
}
function esc_attr_e( $value, $domain = '' ): void {
	echo esc_attr( $value );
}
function __( $value, $domain = '' ): string {
	return (string) $value;
}
function wp_generate_uuid4(): string {
	return 'fixture-123';
}
final class WPD_Settings {
	public static function get_default_caption(): string {
		return 'none';
	}
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-renderer.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-masonry.php';

$base = 'https://photos.example.test/piwigo';
$images = array();
for ( $i = 1; $i <= 8; ++$i ) {
	$images[] = array(
		'id'          => $i,
		'name'        => 'Image ' . $i,
		'width'       => 1200,
		'height'      => 800,
		'page_url'    => $base . '/picture.php?/' . $i . '/category/42',
		'derivatives' => array(
			'medium' => array( 'url' => $base . '/medium-' . $i . '.jpg' ),
			'large'  => array( 'url' => $base . '/large-' . $i . '.jpg' ),
		),
	);
}

$atts = array(
	'type'            => 'gallery',
	'caption'         => 'none',
	'lightbox'        => 'true',
	'sort'            => 'manual',
	'order'           => 'asc',
	'limit'           => 6,
	'_wpd_album_id'   => 42,
	'_wpd_piwigo_url' => $base,
);
$local = WPD_Renderer::render( $images, $atts );
check( 6 === substr_count( $local, '<figure ' ), 'WordPress limit=6 must still show six images.' );
check( str_contains( $local, 'href="' . $base . '/large-1.jpg"' ), 'Default link must remain the media URL.' );
check( 6 === substr_count( $local, 'data-wpd-lightbox="true"' ), 'Default lightbox behavior must remain active.' );
check( str_contains( $local, 'wp-piwigo-display-lightbox-enabled' ), 'Default lightbox class missing.' );
check( in_array( 'wp-piwigo-display', $GLOBALS['enqueued_scripts'], true ), 'Default lightbox script missing.' );

$GLOBALS['enqueued_scripts'] = array();
$photo_atts = array_merge( $atts, array( 'click_action' => 'piwigo_photo' ) );
$photo = WPD_Renderer::render( $images, $photo_atts );
check( 6 === substr_count( $photo, '<figure ' ), 'Photo mode must preserve the WordPress limit.' );
check( str_contains( $photo, 'href="' . $base . '/picture.php?/1/category/42"' ), 'Click must open photo 1 in its Piwigo album.' );
check( ! str_contains( $photo, 'picture.php?/7/category/42' ), 'Hidden images must not be inserted in WordPress.' );
check( 6 === substr_count( $photo, 'data-wpd-lightbox="false"' ), 'External links must not trigger the WordPress lightbox.' );
check( ! str_contains( $photo, 'wp-piwigo-display-lightbox-enabled' ), 'Photo mode must not carry the lightbox class.' );
check( ! in_array( 'wp-piwigo-display', $GLOBALS['enqueued_scripts'], true ), 'Photo mode must not enqueue the lightbox.' );

$album_atts = array_merge( $atts, array( 'click_action' => 'piwigo_album' ) );
$album = WPD_Renderer::render( $images, $album_atts );
check( 6 === substr_count( $album, 'href="' . $base . '/index.php?/category/42"' ), 'Album mode must link every photo to the complete Piwigo album.' );

$slider_atts = array_merge( $photo_atts, array( 'type' => 'slider' ) );
$slider = WPD_Renderer::render( $images, $slider_atts );
check( 6 === substr_count( $slider, 'data-wpd-lightbox="false"' ), 'Slider mode must also open Piwigo instead of the local lightbox.' );
check( str_contains( $slider, 'href="' . $base . '/picture.php?/1/category/42"' ), 'Slider must retain the photo context.' );

$masonry_atts = array_merge( $photo_atts, array( 'layout' => 'masonry' ) );
$masonry = WPD_Renderer::render( $images, $masonry_atts );
check( is_string( $masonry ), 'Masonry renderer must be available.' );
check( 6 === substr_count( $masonry, 'data-wpd-lightbox="false"' ), 'Masonry must preserve limit=6 and open Piwigo pages.' );
check( str_contains( $masonry, 'href="' . $base . '/picture.php?/1/category/42"' ), 'Masonry should preserve native photo navigation.' );
$local_masonry = WPD_Renderer::render( $images, array_merge( $atts, array( 'layout' => 'masonry' ) ) );
check( 6 === substr_count( $local_masonry, 'data-wpd-lightbox="true"' ), 'Default Masonry links must be unchanged.' );

$bad = $images;
$bad[0]['page_url'] = 'https://evil.example.test/picture.php?/1/category/42';
$bad[1]['page_url'] = 'javascript:alert(1)';
$bad[2]['page_url'] = '//evil.example.test/picture.php?/3';
$bad[3]['page_url'] = 'https://photos.example.test/other-site/picture.php?/4';
$bad[4]['page_url'] = $base . '/redirect.php?to=https://evil.example.test';
$bad[5]['page_url'] = '';
$bad_html = WPD_Renderer::render( $bad, $photo_atts );
check( 6 === substr_count( $bad_html, 'href="' . $base . '/index.php?/category/42"' ), 'Invalid/missing photo URLs must fall back to the album on the configured host.' );
check( ! str_contains( $bad_html, 'evil.example.test' ), 'An external host must never be used as a photo destination.' );
check( ! str_contains( $bad_html, 'javascript:' ), 'Unsafe URI schemes must be rejected.' );

$relative = $images;
$relative[0]['page_url'] = '/piwigo/picture.php?/1/category/42';
$relative[1]['page_url'] = 'picture.php?/2/category/42';
$relative_html = WPD_Renderer::render( $relative, $photo_atts );
check( str_contains( $relative_html, 'href="' . $base . '/picture.php?/1/category/42"' ), 'Root-relative Piwigo page URLs must work.' );
check( str_contains( $relative_html, 'href="' . $base . '/picture.php?/2/category/42"' ), 'Site-relative Piwigo page URLs must work.' );

$invalid_url = array_merge( $photo_atts, array( '_wpd_piwigo_url' => 'javascript:evil' ) );
$invalid_html = WPD_Renderer::render( $images, $invalid_url );
check( str_contains( $invalid_html, 'href="' . $base . '/large-1.jpg"' ), 'Invalid base configuration must retain a local media fallback.' );

echo "Piwigo click navigation: gallery, slider, Masonry, limit, album context, unsafe URLs and default: PASS.\n";
