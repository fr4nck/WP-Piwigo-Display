<?php
/**
 * Escaping functions for untrusted Piwigo gallery metadata.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escapes untrusted Piwigo text so WordPress cannot execute it as a shortcode.
 *
 * Dynamic blocks run before do_shortcode on the_content. Escaping HTML alone
 * does not neutralize square brackets; encode them after HTML escaping so the
 * original text remains visible in browsers but never becomes a shortcode.
 *
 * @param string $value     Untrusted image title, description or caption.
 * @param bool   $attribute Whether this is output in an HTML attribute.
 * @return string Escaped markup safe for WordPress's shortcode processing.
 */
function wpd_escape_piwigo_text( string $value, bool $attribute = false ): string {
	$escaped = $attribute ? esc_attr( $value ) : esc_html( $value );

	return str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $escaped );
}

