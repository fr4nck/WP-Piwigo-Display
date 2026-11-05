<?php
/**
 * Uninstall cleanup for WP Piwigo Display.
 *
 * @package WP_Piwigo_Display
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Removes plugin options and WordPress transients for the current site.
 */
function wp_piwigo_display_uninstall_site_data(): void {
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
        "SELECT option_name FROM {$wpdb->options}
        WHERE option_name LIKE %s
           OR option_name LIKE %s
           OR option_name LIKE %s
           OR option_name LIKE %s
           OR option_name LIKE %s
           OR option_name LIKE %s",
        $patterns[0],
        $patterns[1],
        $patterns[2],
        $patterns[3],
        $patterns[4],
        $patterns[5]
    );
    $names = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Only plugin-owned transient keys are selected.

    foreach ( is_array( $names ) ? $names : array() as $name ) {
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
    foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $wp_piwigo_display_site_id ) {
        switch_to_blog( (int) $wp_piwigo_display_site_id );
        try {
            wp_piwigo_display_uninstall_site_data();
        } finally {
            restore_current_blog();
        }
    }
} else {
    wp_piwigo_display_uninstall_site_data();
}
