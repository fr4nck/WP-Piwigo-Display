<?php
/**
 * Deletes data owned by Piwigo Display when the plugin is uninstalled.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes plugin options and transients for the current site.
 *
 * @return void
 */
function piwigo_display_uninstall_site_data(): void {
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
	$names = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Prepared above.

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

if ( is_multisite() ) {
	$piwigo_display_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $piwigo_display_site_ids as $piwigo_display_site_id ) {
		switch_to_blog( (int) $piwigo_display_site_id );
		piwigo_display_uninstall_site_data();
		restore_current_blog();
	}
} else {
	piwigo_display_uninstall_site_data();
}
