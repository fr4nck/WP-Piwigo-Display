<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Renderer
{
    public static function render(array $images, array $atts): string
    {
        $type = isset($atts['type']) ? sanitize_key((string) $atts['type']) : 'gallery';

        if ($type === 'slider') {
            return self::render_slider_placeholder($images, $atts);
        }

        return self::render_gallery($images, $atts);
    }

    private static function render_gallery(array $images, array $atts): string
    {
        wp_enqueue_style('wp-piwigo-display');

        $fit = self::sanitize_fit($atts['fit'] ?? 'cover');
        $height = self::sanitize_height($atts['height'] ?? '180px');

        ob_start();
        ?>
        <div class="wp-piwigo-display wp-piwigo-display-gallery" style="--wpd-image-fit: <?php echo esc_attr($fit); ?>; --wpd-image-height: <?php echo esc_attr($height); ?>;">
            <?php foreach ($images as $image) : ?>
                <?php
                $image_url = self::get_image_url($image);
                $large_url = self::get_large_url($image);
                $title = self::get_image_title($image);

                if ($image_url === '') {
                    continue;
                }
                ?>
                <figure class="wp-piwigo-display-item">
                    <a href="<?php echo esc_url($large_url !== '' ? $large_url : $image_url); ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
                    </a>
                    <?php if ($title !== '') : ?>
                        <figcaption><?php echo esc_html($title); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_slider_placeholder(array $images, array $atts): string
    {
        wp_enqueue_style('wp-piwigo-display');

        return '<div class="wp-piwigo-display wp-piwigo-display-notice">' .
            esc_html__('Le rendu slider sera intégré dans la prochaine version. Utilisez type="gallery" pour l’instant.', 'wp-piwigo-display') .
            '</div>' .
            self::render_gallery($images, $atts);
    }

    private static function get_image_url(array $image): string
    {
        if (isset($image['derivatives']['medium']['url'])) {
            return (string) $image['derivatives']['medium']['url'];
        }

        if (isset($image['derivatives']['small']['url'])) {
            return (string) $image['derivatives']['small']['url'];
        }

        if (isset($image['derivatives']['thumb']['url'])) {
            return (string) $image['derivatives']['thumb']['url'];
        }

        if (isset($image['element_url'])) {
            return (string) $image['element_url'];
        }

        return '';
    }

    private static function get_large_url(array $image): string
    {
        if (isset($image['derivatives']['large']['url'])) {
            return (string) $image['derivatives']['large']['url'];
        }

        if (isset($image['element_url'])) {
            return (string) $image['element_url'];
        }

        return '';
    }

    private static function get_image_title(array $image): string
    {
        if (isset($image['name']) && (string) $image['name'] !== '') {
            return (string) $image['name'];
        }

        if (isset($image['file']) && (string) $image['file'] !== '') {
            return (string) $image['file'];
        }

        return '';
    }

    private static function sanitize_fit(string $fit): string
    {
        return in_array($fit, ['cover', 'contain'], true) ? $fit : 'cover';
    }

    private static function sanitize_height(string $height): string
    {
        $height = trim($height);

        if (preg_match('/^\d+(px|rem|em|vh|vw|%)$/', $height) === 1) {
            return $height;
        }

        return '180px';
    }
}
