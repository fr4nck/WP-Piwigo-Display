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
            return '<div class="wp-piwigo-display-error">' .
                esc_html__('WP Piwigo Display : identifiant d\'album manquant ou invalide.', 'wp-piwigo-display') .
                '</div>';
        }

        $piwigo_url = WPD_Settings::get_piwigo_url();

        if ($piwigo_url === '') {
            return '<div class="wp-piwigo-display-error">' .
                esc_html__('WP Piwigo Display : URL de la galerie Piwigo non configurée.', 'wp-piwigo-display') .
                '</div>';
        }

        $autoplay = filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN);
        $interval = max(1000, absint($atts['interval']));
        $fit = in_array($atts['fit'], ['contain', 'cover'], true) ? $atts['fit'] : 'contain';
        $max = absint($atts['max']);

        ob_start();
        ?>
        <div class="wp-piwigo-display"
             data-piwigo-url="<?php echo esc_url($piwigo_url); ?>"
             data-album="<?php echo esc_attr((string) $album_id); ?>"
             data-autoplay="<?php echo esc_attr($autoplay ? 'true' : 'false'); ?>"
             data-interval="<?php echo esc_attr((string) $interval); ?>"
             data-fit="<?php echo esc_attr($fit); ?>"
             data-max="<?php echo esc_attr((string) $max); ?>">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        __('WP Piwigo Display : shortcode détecté pour l\'album Piwigo #%1$d sur %2$s.', 'wp-piwigo-display'),
                        $album_id,
                        $piwigo_url
                    )
                );
                ?>
            </p>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
