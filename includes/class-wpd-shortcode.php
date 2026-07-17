<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Shortcode
{
    public static function register(): void
    {
        add_shortcode('piwigo', [self::class, 'render']);
    }

    public static function render(array $atts = []): string
    {
        $defaults = array_merge(
            [
                'album' => '',
                'preset' => '',
                'latest' => '0',
                'random' => '0',
                'max' => '0',
                'limit' => '0',
                'sort' => 'manual',
                'order' => 'desc',
                'url' => '',
                'recursive' => 'false',
                'depth' => '10',
                'caption' => 'default',
                'style' => 'default',
                'orientation' => 'all',
                'tag' => '',
                'tags' => '',
                'tag_mode' => 'any',
            ],
            WPD_Settings::get_shortcode_defaults()
        );

        $defaults = apply_filters('wp_piwigo_display_shortcode_defaults', $defaults);
        $atts = shortcode_atts($defaults, $atts, 'piwigo');
        $atts = self::apply_preset($atts);
        $atts = self::sanitize_atts($atts);

        $album_value = trim((string) $atts['album']);

        if ($album_value === '') {
            return self::render_error(__('Aucun album Piwigo n’a été indiqué. Exemple : [piwigo album="154"].', 'wp-piwigo-display'));
        }

        $piwigo_url = isset($atts['url']) && $atts['url'] !== ''
            ? (string) $atts['url']
            : WPD_Settings::get_piwigo_url();

        if ($piwigo_url === '') {
            return self::render_error(__('URL de la galerie Piwigo non configurée. Vérifiez les réglages du plugin.', 'wp-piwigo-display'));
        }

        $api = new WPD_Api($piwigo_url);
        $album_id = $api->resolve_album_id($album_value);

        if (is_wp_error($album_id)) {
            return self::render_error($album_id->get_error_message());
        }

        $recursive = filter_var($atts['recursive'], FILTER_VALIDATE_BOOLEAN);
        $depth = max(0, absint($atts['depth']));
        $tag_filter = self::normalize_tag_filter((string) ($atts['tag'] ?? ''), (string) ($atts['tags'] ?? ''));
        $has_tag_filter = !empty($tag_filter);
        $fetch_max = (string) $atts['orientation'] === 'all' && !$has_tag_filter ? absint($atts['max']) : 0;
        $images_prefiltered_by_tag = false;
        $images = WPD_Cache::get_album_images(absint($album_id), $fetch_max, $piwigo_url, $recursive, $depth);

        if (is_wp_error($images)) {
            return self::render_error($images->get_error_message());
        }

        if (empty($images)) {
            return self::render_error(__('Aucune image n’a été trouvée dans cet album Piwigo.', 'wp-piwigo-display'));
        }

        if ($has_tag_filter && !self::images_contain_tag_data($images)) {
            $images = WPD_Cache::get_album_images_by_tags(absint($album_id), $tag_filter, (string) $atts['tag_mode'], $piwigo_url, $recursive, $depth);

            if (is_wp_error($images)) {
                return self::render_error($images->get_error_message());
            }

            $images_prefiltered_by_tag = true;
        }

        if (!$images_prefiltered_by_tag) {
            $images = self::filter_images_by_tags($images, $tag_filter, (string) $atts['tag_mode']);
        }

        if (empty($images) && $has_tag_filter) {
            return self::render_error(__('Aucune image ne correspond aux tags demandés.', 'wp-piwigo-display'));
        }

        $images = self::filter_images_by_orientation($images, (string) $atts['orientation']);

        if (empty($images)) {
            return self::render_error(__('Aucune image ne correspond à l’orientation demandée.', 'wp-piwigo-display'));
        }

        $html = WPD_Renderer::render($images, $atts);

        if (WPD_Settings::get_debug_mode() && current_user_can('manage_options')) {
            $html .= self::render_debug(absint($album_id), $piwigo_url, $atts, is_array($images) ? count($images) : 0);
        }

        return $html;
    }



    private static function images_contain_tag_data(array $images): bool
    {
        foreach ($images as $image) {
            if (is_array($image) && array_key_exists('tags', $image)) {
                return true;
            }
        }

        return false;
    }

    private static function filter_images_by_tags(array $images, array $requested_tags, string $tag_mode): array
    {
        if (empty($requested_tags)) {
            return $images;
        }

        return array_values(array_filter($images, static function (array $image) use ($requested_tags, $tag_mode): bool {
            $image_tags = self::get_normalized_image_tags($image);

            if (empty($image_tags)) {
                return false;
            }

            $matches = array_intersect($requested_tags, $image_tags);

            if ($tag_mode === 'all') {
                return count($matches) === count($requested_tags);
            }

            return !empty($matches);
        }));
    }

    private static function normalize_tag_filter(string $tag, string $tags): array
    {
        $values = array_merge(explode(',', $tag), explode(',', $tags));
        $normalized = [];

        foreach ($values as $value) {
            $tag_name = self::normalize_tag_name($value);

            if ($tag_name !== '') {
                $normalized[$tag_name] = $tag_name;
            }
        }

        return array_values($normalized);
    }

    private static function get_normalized_image_tags(array $image): array
    {
        if (!isset($image['tags'])) {
            return [];
        }

        $tags = $image['tags'];

        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        if (!is_array($tags)) {
            return [];
        }

        $normalized = [];

        foreach ($tags as $tag) {
            $value = '';

            if (is_array($tag)) {
                foreach (['name', 'url_name', 'value'] as $key) {
                    if (isset($tag[$key])) {
                        $value = (string) $tag[$key];
                        break;
                    }
                }
            } elseif (is_scalar($tag)) {
                $value = (string) $tag;
            }

            $tag_name = self::normalize_tag_name($value);

            if ($tag_name !== '') {
                $normalized[$tag_name] = $tag_name;
            }
        }

        return array_values($normalized);
    }

    private static function normalize_tag_name(string $tag): string
    {
        $tag = trim(wp_strip_all_tags($tag));

        if ($tag === '') {
            return '';
        }

        return function_exists('mb_strtolower') ? mb_strtolower($tag, 'UTF-8') : strtolower($tag);
    }

    private static function filter_images_by_orientation(array $images, string $orientation): array
    {
        $orientation = self::sanitize_orientation($orientation);

        if ($orientation === 'all') {
            return $images;
        }

        $orientations = explode(',', $orientation);

        return array_values(array_filter($images, static function (array $image) use ($orientations): bool {
            $width = absint($image['width'] ?? 0);
            $height = absint($image['height'] ?? 0);

            if ($width <= 0 || $height <= 0) {
                return false;
            }

            if ($height > $width) {
                return in_array('portrait', $orientations, true);
            }

            if ($width > $height) {
                return in_array('landscape', $orientations, true);
            }

            return in_array('square', $orientations, true);
        }));
    }

    private static function sanitize_orientation(string $value): string
    {
        $value = sanitize_text_field($value);
        $orientations = [];

        foreach (explode(',', $value) as $orientation) {
            $orientation = trim($orientation);
            $orientation = function_exists('mb_strtolower') ? mb_strtolower($orientation, 'UTF-8') : strtolower($orientation);

            if ($orientation === 'all') {
                return 'all';
            }

            $normalized = [
                'portrait' => 'portrait',
                'paysage' => 'landscape',
                'landscape' => 'landscape',
                'carré' => 'square',
                'carre' => 'square',
                'square' => 'square',
            ][$orientation] ?? '';

            if ($normalized !== '') {
                $orientations[$normalized] = $normalized;
            }
        }

        return empty($orientations) ? 'all' : implode(',', array_values($orientations));
    }

    private static function sanitize_atts(array $atts): array
    {
        $atts['album'] = isset($atts['album']) ? sanitize_text_field((string) $atts['album']) : '';
        $atts['preset'] = isset($atts['preset']) ? sanitize_key((string) $atts['preset']) : '';
        $atts['type'] = self::sanitize_choice((string) ($atts['type'] ?? 'gallery'), ['gallery', 'slider'], 'gallery');
        $atts['sort'] = self::sanitize_choice((string) ($atts['sort'] ?? 'manual'), ['manual', 'date', 'name', 'id', 'random'], 'manual');
        $atts['order'] = self::sanitize_choice((string) ($atts['order'] ?? 'desc'), ['asc', 'desc'], 'desc');
        $atts['fit'] = self::sanitize_choice((string) ($atts['fit'] ?? 'contain'), ['cover', 'contain', 'auto', 'raw'], 'contain');
        $atts['navigation'] = self::sanitize_choice((string) ($atts['navigation'] ?? 'thumbnails'), ['thumbnails', 'dots', 'none'], 'thumbnails');
        $atts['caption'] = self::sanitize_choice(
            (string) ($atts['caption'] ?? 'default'),
            ['default', 'none', 'title', 'description', 'title-description'],
            'default'
        );
        $atts['style'] = self::sanitize_choice(
            (string) ($atts['style'] ?? 'default'),
            ['default', 'theme', 'minimal', 'none'],
            'default'
        );
        $atts['orientation'] = self::sanitize_orientation((string) ($atts['orientation'] ?? 'all'));
        $atts['tag'] = isset($atts['tag']) ? sanitize_text_field((string) $atts['tag']) : '';
        $atts['tags'] = isset($atts['tags']) ? sanitize_text_field((string) $atts['tags']) : '';
        $atts['tag_mode'] = self::sanitize_choice((string) ($atts['tag_mode'] ?? 'any'), ['any', 'all'], 'any');
        $atts['ratio'] = preg_match('/^\d+\/\d+$/', (string) ($atts['ratio'] ?? '16/9')) === 1 ? (string) $atts['ratio'] : '16/9';
        $atts['height'] = preg_match('/^\d+(px|rem|em|vh|vw|%)$/', (string) ($atts['height'] ?? '')) === 1 ? (string) $atts['height'] : '';
        $atts['autoplay'] = self::sanitize_bool_string($atts['autoplay'] ?? 'true');
        $atts['rounded'] = self::sanitize_bool_string($atts['rounded'] ?? 'false');
        $atts['lightbox'] = self::sanitize_bool_string($atts['lightbox'] ?? 'true');
        $atts['thumbnails'] = self::sanitize_bool_string($atts['thumbnails'] ?? 'true');
        $atts['recursive'] = self::sanitize_bool_string($atts['recursive'] ?? 'false');
        $atts['interval'] = (string) max(1000, absint($atts['interval'] ?? 5000));
        $atts['speed'] = (string) max(0, absint($atts['speed'] ?? 500));
        $atts['limit'] = (string) absint($atts['limit'] ?? 0);
        $atts['max'] = (string) absint($atts['max'] ?? 0);
        $atts['latest'] = (string) absint($atts['latest'] ?? 0);
        $atts['random'] = (string) absint($atts['random'] ?? 0);
        $atts['depth'] = (string) min(10, absint($atts['depth'] ?? 10));

        if (isset($atts['url']) && (string) $atts['url'] !== '') {
            $url = esc_url_raw((string) $atts['url']);
            $scheme = $url !== '' ? wp_parse_url($url, PHP_URL_SCHEME) : '';
            $atts['url'] = in_array($scheme, ['http', 'https'], true) ? untrailingslashit($url) : '';
        } else {
            $atts['url'] = '';
        }

        return $atts;
    }

    private static function sanitize_choice(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function sanitize_bool_string($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    private static function apply_preset(array $atts): array
    {
        $preset = sanitize_key((string) ($atts['preset'] ?? ''));
        $presets = [
            'galerie' => ['type' => 'gallery', 'fit' => 'contain', 'lightbox' => 'true'],
            'slider' => ['type' => 'slider', 'fit' => 'contain', 'navigation' => 'thumbnails', 'autoplay' => 'true'],
            'actualites' => ['type' => 'slider', 'fit' => 'contain', 'navigation' => 'thumbnails', 'sort' => 'date', 'order' => 'desc', 'limit' => '12', 'autoplay' => 'true'],
        ];

        $presets = apply_filters('wp_piwigo_display_presets', $presets);

        if ($preset === '' || !isset($presets[$preset]) || !is_array($presets[$preset])) {
            return $atts;
        }

        foreach ($presets[$preset] as $key => $value) {
            if (!isset($atts[$key]) || $atts[$key] === '') {
                $atts[$key] = (string) $value;
            }
        }

        return $atts;
    }

    private static function render_error(string $message): string
    {
        return '<div class="wp-piwigo-display-error">' . esc_html($message) . '</div>';
    }

    private static function render_debug(int $album_id, string $piwigo_url, array $atts, int $count): string
    {
        ob_start();
        ?>
        <details class="wp-piwigo-display-debug">
            <summary><?php esc_html_e('Debug WP Piwigo Display', 'wp-piwigo-display'); ?></summary>
            <ul>
                <li><?php echo esc_html(sprintf(__('Album : %d', 'wp-piwigo-display'), $album_id)); ?></li>
                <li><?php echo esc_html(sprintf(__('URL Piwigo : %s', 'wp-piwigo-display'), $piwigo_url)); ?></li>
                <li><?php echo esc_html(sprintf(__('Images récupérées : %d', 'wp-piwigo-display'), $count)); ?></li>
                <li><?php echo esc_html(sprintf(__('Type : %s', 'wp-piwigo-display'), (string) ($atts['type'] ?? ''))); ?></li>
                <li><?php echo esc_html(sprintf(__('Tri : %s / %s', 'wp-piwigo-display'), (string) ($atts['sort'] ?? ''), (string) ($atts['order'] ?? ''))); ?></li>
            </ul>
        </details>
        <?php
        return (string) ob_get_clean();
    }
}
