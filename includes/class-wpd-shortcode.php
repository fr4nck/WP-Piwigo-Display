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

        $html = WPD_Renderer::render($images, $atts);

        if (WPD_Settings::get_debug_mode() && current_user_can('manage_options')) {
            $html .= self::render_debug($album_id, $piwigo_url, $atts, is_array($images) ? count($images) : 0);
        }

        return $html;
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
                <li><?php echo esc_html(sprintf(__('Images affichées : %d', 'wp-piwigo-display'), $count)); ?></li>
                <li><?php echo esc_html(sprintf(__('Type : %s', 'wp-piwigo-display'), (string) ($atts['type'] ?? ''))); ?></li>
                <li><?php echo esc_html(sprintf(__('Tri : %s / %s', 'wp-piwigo-display'), (string) ($atts['sort'] ?? ''), (string) ($atts['order'] ?? ''))); ?></li>
            </ul>
        </details>
        <?php
        return (string) ob_get_clean();
    }
}
