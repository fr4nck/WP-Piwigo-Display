<?php
/**
 * Admin composer parity integration.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds missing display options to the administration composer.
 */
final class WPD_Composer_Parity {
	/**
	 * Registers administration hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues the composer parity script on its administration page.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'wp-piwigo-display-compose' !== $page ) {
			return;
		}

		wp_enqueue_script(
			'wpd-admin-composer-parity',
			WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-composer-parity.js',
			array(),
			WPD_VERSION,
			true
		);
	}
}
