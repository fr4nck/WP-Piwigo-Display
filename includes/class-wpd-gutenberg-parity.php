<?php
/**
 * Gutenberg editor parity integration.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the additional Gutenberg editor assets used by the plugin.
 */
final class WPD_Gutenberg_Parity {

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_assets' ) );
	}

	/**
	 * Enqueues the Gutenberg parity script.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets(): void {
		wp_enqueue_script(
			'wpd-gutenberg-parity',
			WPD_PLUGIN_URL . 'blocks/piwigo/gutenberg-parity.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-i18n' ),
			WPD_VERSION,
			true
		);
	}
}
