<?php
/**
 * S-05: real WordPress shortcode parser compatibility for encoded editors.
 *
 * Disposable WP-CLI only; synthetic values and no external Piwigo request.
 *
 * @package WP_Piwigo_Display
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	fwrite( STDERR, "S05_CODEC=BLOCKED real disposable WordPress required\n" );
	exit( 2 );
}
$fail = static function ( string $reason ): void {
	fwrite( STDERR, "S05_CODEC=FAIL $reason\n" );
	exit( 1 );
};
$input = 'La "Roche" [aux Fées] & famille';
$encoded = rawurlencode( $input );
$parsed = null;
add_shortcode(
	'wpd_s05_codec_probe',
	static function ( $atts ) use ( &$parsed ): string {
		$parsed = $atts;
		return 'S05_PROBE_ACCEPTED';
	}
);
$shortcode = '[wpd_s05_codec_probe wpd_codec="uri" album="' . $encoded . '" tag="' . $encoded . '"]';
if ( 'S05_PROBE_ACCEPTED' !== do_shortcode( $shortcode ) ) {
	$fail( 'WordPress rejected encoded shortcode syntax.' );
}
if ( ! is_array( $parsed ) || 3 !== count( $parsed ) || ( $parsed['album'] ?? null ) !== $encoded ) {
	$fail( 'WordPress parsed unexpected attribute keys or truncated the value.' );
}
if ( ! method_exists( 'WPD_Shortcode', 'decode_editor_attributes' ) ) {
	$fail( 'No server-side decoder for editor URI attribute encoding.' );
}
$decode = new ReflectionMethod( 'WPD_Shortcode', 'decode_editor_attributes' );
$decoded = $decode->invoke( null, $parsed );
if ( $input !== ( $decoded['album'] ?? null ) || $input !== ( $decoded['tag'] ?? null ) ) {
	$fail( 'Decoded album/tag attributes differ from user input.' );
}
$legacy = $decode->invoke( null, array( 'album' => 'Nom%20historique', 'tag' => 'Mot&tag' ) );
if ( 'Nom%20historique' !== ( $legacy['album'] ?? null ) || 'Mot&tag' !== ( $legacy['tag'] ?? null ) ) {
	$fail( 'Pre-existing shortcodes must remain byte-for-byte unchanged.' );
}
echo "S05_CODEC=PASS WordPress parsed encoded quote/brackets, decoded values and preserved legacy shortcodes\n";
