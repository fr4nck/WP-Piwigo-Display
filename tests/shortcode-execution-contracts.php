<?php
/**
 * Executable regression checks for shortcode hooks, presets and public errors.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

function __( string $text, string $domain = '' ): string { return $text; }
function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return esc_html( $value ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( $value ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function wp_strip_all_tags( $value ): string { return strip_tags( (string) $value ); }
function absint( $value ): int { return abs( (int) $value ); }
function apply_filters( string $tag, $value ) { return $value; }
function shortcode_atts( array $defaults, array $atts, string $tag = '' ): array {
	return array_merge( $defaults, array_intersect_key( $atts, $defaults ) );
}

final class WP_Error {
	public function __construct( private string $message ) {}
	public function get_error_message(): string { return $this->message; }
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }

final class WPD_Settings {
	public static function get_shortcode_defaults(): array {
		return array( 'type' => 'gallery', 'fit' => 'contain', 'lightbox' => 'true', 'navigation' => 'thumbnails', 'sort' => 'manual', 'order' => 'desc', 'limit' => '0', 'autoplay' => 'true' );
	}
	public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
	public static function get_debug_mode(): bool { return false; }
}
final class WPD_Cache {
	public static bool $api_error = false;
	public static bool $lookup_error = false;
	public static function resolve_album_id( string $album, string $url = '' ) {
		return self::$lookup_error ? new WP_Error( 'token=private-secret' ) : (int) $album;
	}
	public static function get_album_images( int $id, int $max, string $url, bool $recursive, int $depth ) {
		return self::$api_error
			? new WP_Error( 'curl: internal-host=10.0.0.12' )
			: array( array( 'id' => 42, 'width' => 640, 'height' => 480 ) );
	}
}
final class WPD_Renderer {
	public static array $last = array();
	public static function render( array $images, array $atts ): string {
		self::$last = $atts;
		return '<div class="wp-piwigo-display-' . $atts['type'] . '"></div>';
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-shapes.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-slider-transitions.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-shortcode.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( 'test' === WPD_Shapes::apply_shape( 'test', 'gallery', '', array() ), 'A shortcode without attributes must not break shape hooks.' );
$assert( 'test' === WPD_Slider_Transitions::inject_slider_attributes( 'test', 'gallery', '', array() ), 'A shortcode without attributes must not break slider hooks.' );
$assert( 'test' === WPD_Slider_Transitions::inject_slider_attributes( 'test', 'piwigo', '', array() ), 'A Piwigo shortcode without attributes must be accepted by slider hooks.' );
$assert( false !== strpos( WPD_Shortcode::render( '' ), 'Aucun album' ), 'A Piwigo shortcode without attributes must not throw a TypeError.' );

WPD_Shortcode::render( array( 'album' => '42', 'preset' => 'slider' ) );
$assert( 'slider' === ( WPD_Renderer::$last['type'] ?? '' ), 'The slider preset must take effect.' );
WPD_Shortcode::render( array( 'album' => '42', 'preset' => 'slider', 'type' => 'gallery' ) );
$assert( 'gallery' === ( WPD_Renderer::$last['type'] ?? '' ), 'An explicit display type must override the preset.' );
WPD_Shortcode::render( array( 'album' => '42', 'preset' => 'actualites' ) );
$assert( 'date' === ( WPD_Renderer::$last['sort'] ?? '' ), 'The news preset must apply date sorting.' );
$assert( '12' === ( WPD_Renderer::$last['limit'] ?? '' ), 'The news preset must apply its item limit.' );

WPD_Cache::$lookup_error = true;
$render = WPD_Shortcode::render( array( 'album' => '42' ) );
$assert( false === strpos( $render, 'private-secret' ), 'Album lookup must not leak backend error details.' );
WPD_Cache::$lookup_error = false;
WPD_Cache::$api_error = true;
$render = WPD_Shortcode::render( array( 'album' => '42' ) );
$assert( false === strpos( $render, '10.0.0.12' ), 'A backend transport error must not be exposed to visitors.' );
$assert( false !== strpos( $render, 'Impossible de charger' ), 'API failure must return a safe public error.' );

echo "Shortcode hooks, presets and error redaction: OK\n";