<?php
/**
 * Remove Piwigo Display data only after the last compatible distribution goes.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Keep all variables and callbacks local. WordPress can include more than one
// distribution's uninstall.php during a single delete_plugins() invocation.
call_user_func(
	static function (): void {
		// Deliberate retention is useful during a supervised reinstall.
		if ( defined( 'WPD_KEEP_DATA_ON_UNINSTALL' )
			&& filter_var( WPD_KEEP_DATA_ON_UNINSTALL, FILTER_VALIDATE_BOOLEAN )
		) {
			return;
		}

		// Fail closed: an unknown installation state must not erase credentials.
		if ( ! function_exists( 'get_plugins' ) ) {
			$wpd_api_file = defined( 'ABSPATH' ) ? ABSPATH . 'wp-admin/includes/plugin.php' : '';
			if ( '' !== $wpd_api_file && is_readable( $wpd_api_file ) ) {
				require_once $wpd_api_file;
			}
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			return;
		}

		$wpd_installed = get_plugins();
		if ( ! is_array( $wpd_installed ) ) {
			return;
		}

		// WP_UNINSTALL_PLUGIN remains set to the FIRST plugin in a batch.
		// Resolve our own file instead. The fallback is only for isolated shims.
		$wpd_self = function_exists( 'plugin_basename' )
			? plugin_basename( __DIR__ . '/wp-piwigo-display.php' )
			: WP_UNINSTALL_PLUGIN;

		if ( ! isset( $wpd_installed[ $wpd_self ] ) ) {
			return;
		}

		foreach ( $wpd_installed as $wpd_other => $wpd_header ) {
			if ( ! is_string( $wpd_other ) || $wpd_other === $wpd_self || ! is_array( $wpd_header ) ) {
				continue;
			}

			$wpd_name = trim( (string) ( $wpd_header['Name'] ?? '' ) );
			$wpd_domain = (string) ( $wpd_header['TextDomain'] ?? '' );
			$wpd_uri = (string) ( $wpd_header['PluginURI'] ?? '' );

			// Match the historical entrypoint, renamed public titles and the
			// GitHub distribution identity. Never rely on the display name alone.
			$wpd_same_family = 'wp-piwigo-display.php' === basename( $wpd_other )
				|| 0 === stripos( $wpd_name, 'Piwigo Display' )
				|| 'wp-piwigo-display' === $wpd_domain
				|| 0 === strpos( $wpd_uri, 'https://github.com/fr4nck/WP-Piwigo-Display' );

			if ( ! $wpd_same_family ) {
				continue;
			}

			// get_plugins() may be cached during a group removal. The previous
			// package is no longer a sibling once WordPress deleted its file.
			if ( defined( 'WP_PLUGIN_DIR' )
				&& ! is_file( rtrim( WP_PLUGIN_DIR, '/\\' ) . '/' . $wpd_other )
			) {
				continue;
			}

			// Another installed copy may use these settings and credentials.
			return;
		}

		$wpd_delete_site = static function (): void {
			global $wpdb;

			delete_option( 'wp_piwigo_display_options' );
			delete_option( 'wp_piwigo_display_service_account' );
			delete_transient( 'wpd_api_health_metrics' );

			$wpd_prefixes = array(
				'_transient_wpd_album_',
				'_transient_timeout_wpd_album_',
				'_transient_wpd_stale_',
				'_transient_timeout_wpd_stale_',
				'_transient_wpd_lock_',
				'_transient_timeout_wpd_lock_',
			);
			$wpd_patterns = array_map(
				static function ( string $wpd_prefix ) use ( $wpdb ): string {
					return $wpdb->esc_like( $wpd_prefix ) . '%';
				},
				$wpd_prefixes
			);
			$wpd_query = $wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options}
				WHERE option_name LIKE %s
				   OR option_name LIKE %s
				   OR option_name LIKE %s
				   OR option_name LIKE %s
				   OR option_name LIKE %s
				   OR option_name LIKE %s",
				$wpd_patterns[0],
				$wpd_patterns[1],
				$wpd_patterns[2],
				$wpd_patterns[3],
				$wpd_patterns[4],
				$wpd_patterns[5]
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is from the trusted wpdb object.

			$wpd_names = $wpdb->get_col( $wpd_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above using six LIKE placeholders; only plugin-owned transients are deleted.
			foreach ( is_array( $wpd_names ) ? $wpd_names : array() as $wpd_name ) {
				if ( str_starts_with( $wpd_name, '_transient_timeout_' ) ) {
					$wpd_transient = substr( $wpd_name, strlen( '_transient_timeout_' ) );
				} elseif ( str_starts_with( $wpd_name, '_transient_' ) ) {
					$wpd_transient = substr( $wpd_name, strlen( '_transient_' ) );
				} else {
					continue;
				}
				delete_transient( $wpd_transient );
			}
		};

		if ( is_multisite() ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $wpd_site_id ) {
				switch_to_blog( (int) $wpd_site_id );
				try {
					$wpd_delete_site();
				} finally {
					restore_current_blog();
				}
			}
		} else {
			$wpd_delete_site();
		}
	}
);
