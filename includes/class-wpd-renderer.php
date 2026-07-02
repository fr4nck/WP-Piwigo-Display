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
            return self::render_slider($images, $atts);
        }

        return self::render_gallery($images, $atts);
    }

    private static function render_gallery(array $images, array $atts): string
    {
        wp_enqueue_style('wp-piwigo-display');

        $fit = self::sanitize_fit($atts['fit'] ?? 'cover');
        $height = self::sanitize_height((string) ($atts['height'] ?? ''), '180px');

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

    private static function render_slider(array $images, array $atts): string
    {
        wp_enqueue_style('wp-piwigo-display');
        wp_enqueue_style('wpd-splide');
        wp_enqueue_script('wpd-splide');
        wp_enqueue_script('wp-piwigo-display');

        $fit = self::sanitize_fit($atts['fit'] ?? 'cover');
        $height = self::sanitize_height((string) ($atts['height'] ?? ''), '420px');
        $autoplay = filter_var($atts['autoplay'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $interval = max(1000, absint($atts['interval'] ?? 5000));
        $slider_id = 'wpd-slider-' . wp_generate_uuid4();

        ob_start();
        ?>
        <div id="<?php echo esc_attr($slider_id); ?>"
             class="wp-piwigo-display wp-piwigo-display-slider splide"
             style="--wpd-slider-height: <?php echo esc_attr($height); ?>; --wpd-image-fit: <?php echo esc_attr($fit); ?>;"
             data-autoplay="<?php echo esc_attr($autoplay ? 'true' : 'false'); ?>"
             data-interval="<?php echo esc_attr((string) $interval); ?>">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php foreach ($images as $image) : ?>
                        <?php
                        $image_url = self::get_large_url($image);
                        $title = self::get_image_title($image);

                        if ($image_url === '') {
                            continue;
                        }
                        ?>
                        <li class="splide__slide">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
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

        if (isset($image['derivatives']['medium']['url'])) {
            return (string) $image['derivatives']['medium']['url'];
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

        return '420px';
    }
}
