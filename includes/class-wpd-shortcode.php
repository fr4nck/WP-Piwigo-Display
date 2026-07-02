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
        $atts = shortcode_atts(
            [
                'album' => '',
                'type' => 'gallery',
                'autoplay' => 'true',
                'interval' => '5000',
                'fit' => 'auto',
                'height' => '',
                'ratio' => '16/9',
                'rounded' => 'false',
                'random' => '0',
                'latest' => '0',
                'lightbox' => 'true',
                'thumbnails' => 'true',
                'max' => '0',
            ],
            $atts,
            'piwigo'
        );

        $album_id = absint($atts['album']);

        if ($album_id <= 0) {
            return self::render_error(__('WP Piwigo Display : identifiant d\'album manquant ou invalide.', 'wp-piwigo-display'));
        }

        if (WPD_Settings::get_piwigo_url() === '') {
            return self::render_error(__('WP Piwigo Display : URL de la galerie Piwigo non configurée.', 'wp-piwigo-display'));
        }

        $images = WPD_Cache::get_album_images($album_id, absint($atts['max']));

        if (is_wp_error($images)) {
            return self::render_error($images->get_error_message());
        }

        if (empty($images)) {
            return self::render_error(__('WP Piwigo Display : aucune image trouvée dans cet album.', 'wp-piwigo-display'));
        }

        return WPD_Renderer::render($images, $atts);
    }

    private static function render_error(string $message): string
    {
        return '<div class="wp-piwigo-display-error">' . esc_html($message) . '</div>';
    }
}
