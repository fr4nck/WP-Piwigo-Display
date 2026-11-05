<?php
/**
 * Cleans up Piwigo Display data during a WordPress plugin uninstall.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Runs plugin-owned data cleanup without loading the plugin itself.
 */
final class WPD_Uninstall {

	/**
	 * Removes settings, credentials and cached data for one WordPress site.
	 *
	 * @return void
	 */
	public static function site_data(): void {
		global $wpdb;

		delete_option( 'wp_piwigo_display_options' );
		delete_option( 'wp_piwigo_display_service_account' );
		delete_transient( 'wpd_api_health_metrics' );

		$prefixes = array(
			'_transient_wpd_album_',
			'_transient_timeout_wpd_album_',
			'_transient_wpd_stale_',
			'_transient_timeout_wpd_stale_',
			'_transient_wpd_lock_',
			'_transient_timeout_wpd_lock_',
		);
		$patterns = array_map(
			static function ( string $prefix ) use ( $wpdb ): string {
				return $wpdb->esc_like( $prefix ) . '%';
			},
			$prefixes
		);
		$query = $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$patterns[0],
			$patterns[1],
			$patterns[2],
			$patterns[3],
			$patterns[4],
			$patterns[5]
		);
		$names = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query prepared above.

		foreach ( $names as $name ) {
			if ( str_starts_with( $name, '_transient_timeout_' ) ) {
				$transient = substr( $name, strlen( '_transient_timeout_' ) );
			} elseif ( str_starts_with( $name, '_transient_' ) ) {
				$transient = substr( $name, strlen( '_transient_' ) );
			} else {
				continue;
			}
			delete_transient( $transient );
		}
	}

	/**
	 * Deletes plugin data for one site or every site in a multisite network.
	 *
	 * @return void
	 */
	public static function run(): void {
		if ( is_multisite() ) {
			$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::site_data();
				restore_current_blog();
			}
			return;
		}
		self::site_data();
	}
}

WPD_Uninstall::run();
