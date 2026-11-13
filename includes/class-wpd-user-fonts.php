<?php
/**
 * Local font stacks for the photo-filled-text layout.
 *
 * This first candidate deliberately does not register upload endpoints.
 * Imported WOFF/WOFF2 fonts will be qualified in a separate change.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolves a small allowlist of native, no-network font stacks. */
final class WPD_User_Fonts {
	/**
	 * Returns a CSS-safe font stack without interpolating arbitrary input.
	 *
	 * @param string $font Requested font identifier.
	 * @return string
	 */
	public static function font_stack( string $font ): string {
		$font = sanitize_key( $font );

		return match ( $font ) {
			'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'serif'  => 'Georgia, "Times New Roman", serif',
			'mono'   => 'ui-monospace, "SFMono-Regular", Consolas, monospace',
			default  => 'inherit',
		};
	}
}
