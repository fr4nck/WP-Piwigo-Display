<?php

if (!defined('ABSPATH')) {
    exit;
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
}
