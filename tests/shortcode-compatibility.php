<?php
/**
 * Executable shortcode regression checks for the main development line.
 */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function __( string $text, string $domain = '' ): string { return $text; }
function esc_html( $value ): string { return htmlspecialchars( (string ) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return esc_html( $value ); }
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function wp_strip_all_tags( $value ): string { return strip_tags( (string) $value ); }
function sanitize_key( $value ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function apply_filters( $name, $value ) { return $value; }
function shortcode_atts( array $defaults, array $atts, string $tag ): array { return array_merge( $defaults, array_intersect_key( $atts, $defaults ) ); }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
final class WP_Error {
	public function __construct( private string $message ) {}
	public function get_error_message(): string { return $this->message; }
}
final class WPD_Settings {
	public static function get_shortcode_defaults(): array { return array( 'type' => 'gallery', 'sort' => 'manual', 'limit' => '0' ); }
	public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
	public static function get_debug_mode(): bool { return false; }
}
final class WPD_Api {
	public static bool $fail_lookup = false;
	public function __construct( string $url ) {}
	public function resolve_album_id( string $id ) { return self::$fail_lookup ? new WP_Error( 'internal-password=secret' ) : (int) $id; }
}
final class WPD_Cache {
	public static function get_album_images( int $id, int $max, string $url, bool $recursive, int $depth ) {
		return array( array( 'id' => 2, 'width' => 640, 'height' => 480 ) );
	}
}
final class WPD_Renderer {
	public static array $last = array();
	public static function render( array $images, array $atts ): string {
		self::$last = $atts;
		return '<div class="gallery"></div>';
	}
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-shortcode.php';

$assert = static function ( bool $truth, string $message ): void {
	if ( ! $truth ) { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
};
$assert( false !== strpos( WPD_Shortcode::render( '' ), 'Aucun album' ), 'Un shortcode vide ne doit pas provoquer une erreur fatale PHP.' );
WPD_Shortcode::render( array( 'album' => '12', 'preset' => 'slider' ) );
$assert( 'slider' === ( WPD_Renderer::$last['type'] ?? '' ), 'Le preset doit définir le mode slider.' );
WPD_Shortcode::render( array( 'album' => '12', 'preset' => 'slider', 'type' => 'gallery' ) );
$assert( 'gallery' === ( WPD_Renderer::$last['type'] ?? '' ), 'Un attribut explicite doit primer sur le preset.' );
WPD_Api::$fail_lookup = true;
$html = WPD_Shortcode::render( array( 'album' => '12' ) );
$assert( false === strpos( $html, 'secret' ), 'Les erreurs API brutes ne doivent pas apparaître publiquement.' );
echo "Main shortcode contracts: OK\n";