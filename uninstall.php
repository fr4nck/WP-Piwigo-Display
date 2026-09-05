<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin-owned data from the current site.
 */
function wpd_uninstall_site_data(): void
{
    global $wpdb;

    delete_option('wp_piwigo_display_options');
    delete_option('wp_piwigo_display_service_account');

    $like_transient = $wpdb->esc_like('_transient_wpd_album_') . '%';
    $like_timeout = $wpdb->esc_like('_transient_timeout_wpd_album_') . '%';

    $option_names = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like_transient,
            $like_timeout
        )
    );

    foreach ($option_names as $option_name) {
        if (str_starts_with($option_name, '_transient_timeout_')) {
            $transient = substr($option_name, strlen('_transient_timeout_'));
        } elseif (str_starts_with($option_name, '_transient_')) {
            $transient = substr($option_name, strlen('_transient_'));
        } else {
            continue;
        }

        delete_transient($transient);
    }
}

if (is_multisite()) {
    $site_ids = get_sites([
        'fields' => 'ids',
        'number' => 0,
    ]);

    foreach ($site_ids as $site_id) {
        switch_to_blog((int) $site_id);
        wpd_uninstall_site_data();
        restore_current_blog();
    }
} else {
    wpd_uninstall_site_data();
}
