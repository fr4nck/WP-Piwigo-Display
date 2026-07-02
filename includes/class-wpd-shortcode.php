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
                'autoplay' => 'true',
                'interval' => '5000',
                'fit' => 'contain',
                'max' => '0',
            ],
            $atts,
            'piwigo'
        );

        $album_id = absint($atts['album']);

        if ($album_id <= 0) {
            return self::render_error(__('WP Piwigo Display : identifiant d\'album manquant ou invalide.', 'wp-piwigo-display'));
        }

        $piwigo_url = WPD_Settings::get_piwigo_url();

        if ($piwigo_url === '') {
            return self::render_error(__('WP Piwigo Display : URL de la galerie Piwigo non configurée.', 'wp-piwigo-display'));
        }

        $api = new WPD_Api($piwigo_url);
        $images = $api->get_images_from_album($album_id, absint($atts['max']));

        if (is_wp_error($images)) {
            return self::render_error($images->get_error_message());
        }

        if (empty($images)) {
            return self::render_error(__('WP Piwigo Display : aucune image trouvée dans cet album.', 'wp-piwigo-display'));
        }

        ob_start();
        ?>
        <div class="wp-piwigo-display">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        _n(
                            'WP Piwigo Display : %d image trouvée dans cet album.',
                            'WP Piwigo Display : %d images trouvées dans cet album.',
                            count($images),
                            'wp-piwigo-display'
                        ),
                        count($images)
                    )
                );
                ?>
            </p>

            <ul>
                <?php foreach ($images as $image) : ?>
                    <li>
                        <?php
                        echo esc_html(
                            isset($image['name']) && $image['name'] !== ''
                                ? (string) $image['name']
                                : sprintf(__('Image #%d', 'wp-piwigo-display'), absint($image['id'] ?? 0))
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_error(string $message): string
    {
        return '<div class="wp-piwigo-display-error">' . esc_html($message) . '</div>';
    }
}
