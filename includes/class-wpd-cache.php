<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Cache
{
    /**
     * Cache mémoire limité à la requête PHP courante.
     *
     * @var array<string, array>
     */
    private static array $request_cache = [];

    public static function get_album_images(int $album_id, int $max = 0, string $piwigo_url = '', bool $recursive = false, int $depth = 10)
    {
        $piwigo_url = $piwigo_url !== '' ? untrailingslashit($piwigo_url) : WPD_Settings::get_piwigo_url();
        $cache_key = self::get_album_cache_key($album_id, $max, $piwigo_url, $recursive, $depth);

        if (isset(self::$request_cache[$cache_key])) {
            return self::$request_cache[$cache_key];
        }

        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            self::$request_cache[$cache_key] = $cached;

            return $cached;
        }

        $api = new WPD_Api($piwigo_url);
        $images = $recursive
            ? $api->get_images_from_album_recursive($album_id, $max, $depth)
            : $api->get_images_from_album($album_id, $max);

        if (is_wp_error($images)) {
            return $images;
        }

        self::$request_cache[$cache_key] = $images;
        set_transient($cache_key, $images, WPD_Settings::get_cache_duration());

        return $images;
    }


    public static function get_album_images_by_tags(int $album_id, array $tags, string $tag_mode = 'any', string $piwigo_url = '', bool $recursive = false, int $depth = 10)
    {
        $album_images = self::get_album_images($album_id, 0, $piwigo_url, $recursive, $depth);

        if (is_wp_error($album_images) || empty($tags)) {
            return $album_images;
        }

        $piwigo_url = $piwigo_url !== '' ? untrailingslashit($piwigo_url) : WPD_Settings::get_piwigo_url();
        $cache_key = self::get_album_tag_cache_key($album_id, $tags, $tag_mode, $piwigo_url, $recursive, $depth);

        if (isset(self::$request_cache[$cache_key])) {
            return self::$request_cache[$cache_key];
        }

        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            self::$request_cache[$cache_key] = $cached;

            return $cached;
        }

        $api = new WPD_Api($piwigo_url);
        $tagged_images = $api->get_images_by_tags($tags, $tag_mode);

        if (is_wp_error($tagged_images)) {
            return $tagged_images;
        }

        $tagged_ids = [];

        foreach ($tagged_images as $image) {
            $image_id = absint($image['id'] ?? 0);

            if ($image_id > 0) {
                $tagged_ids[$image_id] = true;
            }
        }

        $images = array_values(array_filter($album_images, static function (array $image) use ($tagged_ids): bool {
            $image_id = absint($image['id'] ?? 0);

            return $image_id > 0 && isset($tagged_ids[$image_id]);
        }));

        self::$request_cache[$cache_key] = $images;
        set_transient($cache_key, $images, WPD_Settings::get_cache_duration());

        return $images;
    }

    public static function clear_all(): int
    {
        global $wpdb;

        $deleted = 0;
        $names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_wpd_album_') . '%',
                $wpdb->esc_like('_transient_timeout_wpd_album_') . '%'
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

    private static function get_album_tag_cache_key(int $album_id, array $tags, string $tag_mode, string $piwigo_url, bool $recursive, int $depth): string
    {
        sort($tags, SORT_STRING);

        return 'wpd_album_' . md5($piwigo_url . '|' . $album_id . '|tags|' . implode(',', $tags) . '|' . $tag_mode . '|' . ($recursive ? '1' : '0') . '|' . $depth);
    }

    private static function get_album_cache_key(int $album_id, int $max, string $piwigo_url, bool $recursive, int $depth): string
    {
        return 'wpd_album_' . md5($piwigo_url . '|' . $album_id . '|' . $max . '|' . ($recursive ? '1' : '0') . '|' . $depth);
    }
}
