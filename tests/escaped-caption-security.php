<?php
/**
 * S-01 minimal stable renderer escaping contract.
 *
 * The real WordPress end-to-end probe runs separately during migration CI.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/../' );

function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
}
function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-renderer.php';

$assert = static function ( bool $pass, string $label ): void {
	if ( ! $pass ) {
		fwrite( STDERR, "S01_STABLE_UNIT=FAIL $label\n" );
		exit( 1 );
	}
};
$assert( method_exists( 'WPD_Renderer', 'escape_piwigo_text' ), 'Missing metadata escape helper.' );
$input = 'Légende [wpd_s01_stable_probe] & "originale"';
foreach ( array( false, true ) as $attr ) {
	$escaped = WPD_Renderer::escape_piwigo_text( $input, $attr );
	$assert( ! str_contains( $escaped, '[wpd_s01_stable_probe]' ), 'Shortcode tokens remain active.' );
	$assert( str_contains( $escaped, '&#91;wpd_s01_stable_probe&#93;' ), 'Shortcode delimiters not neutralized.' );
	$assert( str_contains( $escaped, '&amp;' ) && str_contains( $escaped, '&quot;' ), 'WordPress HTML escaping lost.' );
	$assert( html_entity_decode( $escaped, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) === $input, 'Original display text changed.' );
}
echo "S01_STABLE_UNIT=PASS shortcode-inert HTML and attribute escaping\n";
