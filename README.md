<?php

if (!defined('ABSPATH')) {
    exit;
    public static function clear_all(): int
    {
        global $wpdb;

        $deleted = 0;
        $prefix = '_transient_wpd_album_';
        $timeout_prefix = '_transient_timeout_wpd_album_';

        $names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like($prefix) . '%',
                $wpdb->esc_like($timeout_prefix) . '%'
            )
        );

        foreach ($names as $name) {
            if (str_starts_with($name, '_transient_timeout_')) {
                $transient = substr($name, strlen('_transient_timeout_'));
            } elseif (str_starts_with($name, '_transient_')) {
                $transient = substr($name, strlen('_transient_'));
            } else {
                continue;
            }

            if (delete_transient($transient)) {
                $deleted++;
            }
        }

        return $deleted;
    }

}

final class WPD_Cache
{
    public static function get_album_images(int $album_id, int $max = 0)
    {
        $cache_key = self::get_album_cache_key($album_id, $max);
        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $api = new WPD_Api(WPD_Settings::get_piwigo_url());
        $images = $api->get_images_from_album($album_id, $max);

        if (is_wp_error($images)) {
            return $images;
        }

        set_transient($cache_key, $images, WPD_Settings::get_cache_duration());

        return $images;
    }

    private static function get_album_cache_key(int $album_id, int $max): string
    {
        return 'wpd_album_' . md5(WPD_Settings::get_piwigo_url() . '|' . $album_id . '|' . $max);
    }
    public static function clear_all(): int
    {
        global $wpdb;

        $deleted = 0;
        $prefix = '_transient_wpd_album_';
        $timeout_prefix = '_transient_timeout_wpd_album_';

        $names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like($prefix) . '%',
                $wpdb->esc_like($timeout_prefix) . '%'
            )
        );

        foreach ($names as $name) {
            if (str_starts_with($name, '_transient_timeout_')) {
                $transient = substr($name, strlen('_transient_timeout_'));
            } elseif (str_starts_with($name, '_transient_')) {
                $transient = substr($name, strlen('_transient_'));
            } else {
                continue;
            }

            if (delete_transient($transient)) {
                $deleted++;
            }
        }

        return $deleted;
    }

}
