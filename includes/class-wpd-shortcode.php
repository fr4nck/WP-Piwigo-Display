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
                'latest' => '0',
                'random' => '0',
                'max' => '0',
                'limit' => '0',
                'sort' => 'manual',
                'order' => 'desc',
                'url' => '',
                'recursive' => 'false',
                'depth' => '10',
            ],
            WPD_Settings::get_shortcode_defaults()
        );

        $defaults = apply_filters('wp_piwigo_display_shortcode_defaults', $defaults);

        $atts = shortcode_atts($defaults, $atts, 'piwigo');

        $album_id = absint($atts['album']);

        if ($album_id <= 0) {
            return self::render_error(__('WP Piwigo Display : identifiant d\'album manquant ou invalide.', 'wp-piwigo-display'));
        }

        $piwigo_url = isset($atts['url']) && $atts['url'] !== ''
            ? esc_url_raw((string) $atts['url'])
            : WPD_Settings::get_piwigo_url();

        if ($piwigo_url === '') {
            return self::render_error(__('WP Piwigo Display : URL de la galerie Piwigo non configurée.', 'wp-piwigo-display'));
        }

        $recursive = filter_var($atts['recursive'], FILTER_VALIDATE_BOOLEAN);
        $depth = max(0, absint($atts['depth']));

        $images = WPD_Cache::get_album_images($album_id, absint($atts['max']), $piwigo_url, $recursive, $depth);

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
