<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Renderer
{
    public static function render(array $images, array $atts): string
    {
        $images = self::prepare_images($images, $atts);
        $type = isset($atts['type']) ? sanitize_key((string) $atts['type']) : 'gallery';

        $custom_render = apply_filters('wp_piwigo_display_render', null, $images, $atts, $type);
        if (is_string($custom_render)) {
            return $custom_render;
        }

        if ($type === 'slider') {
            return self::render_slider($images, $atts);
        }

        return self::render_gallery($images, $atts);
    }

    private static function render_gallery(array $images, array $atts): string
    {
        wp_enqueue_style('wp-piwigo-display');
        wp_enqueue_script('wp-piwigo-display');

        $fit = self::sanitize_fit($atts['fit'] ?? 'cover');
        $height = self::sanitize_height((string) ($atts['height'] ?? ''), '180px');
        $rounded_class = self::is_enabled($atts['rounded'] ?? 'false') ? ' wp-piwigo-display-rounded' : '';
        $raw_class = $fit === 'raw' ? ' wp-piwigo-display-raw' : '';
        $raw_class = $fit === 'raw' ? ' wp-piwigo-display-raw' : '';
        $lightbox_class = self::is_enabled($atts['lightbox'] ?? 'true') ? ' wp-piwigo-display-lightbox-enabled' : '';
        $style_class = ' wp-piwigo-display-style-' . self::sanitize_style((string) ($atts['style'] ?? 'default'));
        $style_class = ' wp-piwigo-display-style-' . self::sanitize_style((string) ($atts['style'] ?? 'default'));

        ob_start();
        ?>
        <div class="wp-piwigo-display wp-piwigo-display-gallery<?php echo esc_attr($rounded_class . $raw_class . $lightbox_class . $style_class); ?>" style="--wpd-image-fit: <?php echo esc_attr($fit); ?>; --wpd-image-height: <?php echo esc_attr($height); ?>;">
            <?php foreach ($images as $image) : ?>
                <?php
                $image_url = self::get_image_url($image);
                $large_url = self::get_large_url($image);
                $title = self::get_image_title($image);
                $description = self::get_image_description($image);
                $caption_mode = self::resolve_caption_mode((string) ($atts['caption'] ?? 'default'));
                $lightbox_caption = self::get_caption_text($title, $description, $caption_mode);
                $orientation = self::get_orientation_class($image);
                $image_fit = self::get_image_fit($image, $fit);

                if ($image_url === '') {
                    continue;
                }
                ?>
                <figure class="wp-piwigo-display-item <?php echo esc_attr($orientation); ?>" style="--wpd-current-image-fit: <?php echo esc_attr($image_fit); ?>;">
                    <a href="<?php echo esc_url($large_url !== '' ? $large_url : $image_url); ?>" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr($lightbox_caption); ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
                    </a>
                    <?php echo self::render_caption($title, $description, $caption_mode, 'figcaption'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

        $fit = self::sanitize_fit($atts['fit'] ?? 'contain');

        if ($fit === 'raw' || $fit === 'auto') {
            $fit = 'contain';
        }

        $height = self::sanitize_height((string) ($atts['height'] ?? ''), '');
        $ratio = self::sanitize_ratio((string) ($atts['ratio'] ?? '16/9'));
        $autoplay = self::is_enabled($atts['autoplay'] ?? 'true');
        $interval = max(1000, absint($atts['interval'] ?? 5000));
        $speed = max(0, absint($atts['speed'] ?? 500));
        $rounded_class = self::is_enabled($atts['rounded'] ?? 'false') ? ' wp-piwigo-display-rounded' : '';
        $lightbox_class = self::is_enabled($atts['lightbox'] ?? 'true') ? ' wp-piwigo-display-lightbox-enabled' : '';
        $style_class = ' wp-piwigo-display-style-' . self::sanitize_style((string) ($atts['style'] ?? 'default'));
        $navigation = self::sanitize_navigation((string) ($atts['navigation'] ?? 'thumbnails'));
        $thumbnails = $navigation === 'thumbnails';
        $dots = $navigation === 'dots';
        $slider_id = 'wpd-slider-' . wp_generate_uuid4();

        ob_start();
        ?>
        <div id="<?php echo esc_attr($slider_id); ?>"
             class="wp-piwigo-display wp-piwigo-display-slider splide<?php echo esc_attr($rounded_class . $lightbox_class . $style_class); ?>"
             style="--wpd-slider-height: <?php echo esc_attr($height); ?>; --wpd-slider-ratio: <?php echo esc_attr($ratio); ?>; --wpd-image-fit: <?php echo esc_attr($fit); ?>;"
             data-autoplay="<?php echo esc_attr($autoplay ? 'true' : 'false'); ?>"
             data-interval="<?php echo esc_attr((string) $interval); ?>"
             data-speed="<?php echo esc_attr((string) $speed); ?>"
             data-navigation="<?php echo esc_attr($navigation); ?>"
             aria-label="<?php esc_attr_e('Diaporama Piwigo', 'wp-piwigo-display'); ?>">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php foreach ($images as $image) : ?>
                        <?php
                        $image_url = self::get_large_url($image);
                        $title = self::get_image_title($image);
                        $description = self::get_image_description($image);
                        $caption_mode = self::resolve_caption_mode((string) ($atts['caption'] ?? 'default'));
                        $lightbox_caption = self::get_caption_text($title, $description, $caption_mode);

                        if ($image_url === '') {
                            continue;
                        }
                        ?>
                        <li class="splide__slide">
                            <a href="<?php echo esc_url($image_url); ?>" class="wp-piwigo-display-slide-link" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr($lightbox_caption); ?>">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
                            </a>
                            <?php echo self::render_caption($title, $description, $caption_mode, 'div'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($thumbnails) : ?>
                <div class="wp-piwigo-display-slider-thumbnails" aria-label="<?php esc_attr_e('Miniatures du diaporama', 'wp-piwigo-display'); ?>">
                    <?php foreach ($images as $index => $image) : ?>
                        <?php
                        $thumb_url = self::get_image_url($image);
                        $title = self::get_image_title($image);

                        if ($thumb_url === '') {
                            continue;
                        }
                        ?>
                        <button type="button" class="wp-piwigo-display-slider-thumbnail<?php echo $index === 0 ? ' is-active' : ''; ?>" data-slide-index="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr(sprintf(__('Afficher l’image %d', 'wp-piwigo-display'), $index + 1)); ?>">
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function prepare_images(array $images, array $atts): array
    {
        $sort = self::sanitize_sort((string) ($atts['sort'] ?? 'manual'));
        $order = self::sanitize_order((string) ($atts['order'] ?? 'desc'));
        $limit = absint($atts['limit'] ?? 0);
        $latest = absint($atts['latest'] ?? 0);
        $random = absint($atts['random'] ?? 0);
        $max = absint($atts['max'] ?? 0);

        if ($random > 0) {
            $sort = 'random';
            $limit = $random;
        }

        if ($latest > 0) {
            $sort = 'date';
            $order = 'desc';
            $limit = $latest;
        }

        if ($limit <= 0 && $max > 0) {
            $limit = $max;
        }

        switch ($sort) {
            case 'random':
                shuffle($images);
                break;

            case 'name':
                usort($images, static function (array $a, array $b): int {
                    return strnatcasecmp((string) ($a['name'] ?? $a['file'] ?? ''), (string) ($b['name'] ?? $b['file'] ?? ''));
                });
                break;

            case 'date':
                usort($images, static function (array $a, array $b): int {
                    return strcmp((string) ($a['date_available'] ?? $a['date_creation'] ?? ''), (string) ($b['date_available'] ?? $b['date_creation'] ?? ''));
                });
                break;

            case 'id':
                usort($images, static function (array $a, array $b): int {
                    return absint($a['id'] ?? 0) <=> absint($b['id'] ?? 0);
                });
                break;

            case 'manual':
            default:
                break;
        }

        if ($sort !== 'random' && $order === 'desc') {
            $images = array_reverse($images);
        }

        if ($limit > 0) {
            $images = array_slice($images, 0, $limit);
        }

        return $images;
    }

    private static function sanitize_style(string $style): string
    {
        return in_array($style, ['default', 'theme', 'minimal', 'none'], true)
            ? $style
            : 'default';
    }

    private static function sanitize_sort(string $sort): string
    {
        return in_array($sort, ['manual', 'date', 'name', 'random', 'id'], true) ? $sort : 'manual';
    }

    private static function sanitize_order(string $order): string
    {
        return in_array($order, ['asc', 'desc'], true) ? $order : 'desc';
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
            return wp_strip_all_tags((string) $image['name']);
        }

        if (isset($image['file']) && (string) $image['file'] !== '') {
            return wp_strip_all_tags((string) $image['file']);
        }

        return '';
    }


    private static function get_image_description(array $image): string
    {
        foreach (['comment', 'description'] as $key) {
            if (!isset($image[$key]) || (string) $image[$key] === '') {
                continue;
            }

            $description = html_entity_decode(
                wp_strip_all_tags((string) $image[$key]),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            return trim($description);
        }

        return '';
    }

    private static function resolve_caption_mode(string $caption): string
    {
        if ($caption === 'default') {
            return WPD_Settings::get_default_caption();
        }

        return in_array($caption, ['none', 'title', 'description', 'title-description'], true)
            ? $caption
            : WPD_Settings::get_default_caption();
    }

    private static function get_caption_text(string $title, string $description, string $mode): string
    {
        if ($mode === 'title') {
            return $title;
        }

        if ($mode === 'description') {
            return $description;
        }

        if ($mode === 'title-description') {
            return trim(implode(' — ', array_filter([$title, $description], static fn(string $value): bool => $value !== '')));
        }

        return '';
    }

    private static function render_caption(string $title, string $description, string $mode, string $element): string
    {
        $show_title = in_array($mode, ['title', 'title-description'], true) && $title !== '';
        $show_description = in_array($mode, ['description', 'title-description'], true) && $description !== '';

        if (!$show_title && !$show_description) {
            return '';
        }

        $tag = $element === 'figcaption' ? 'figcaption' : 'div';
        $class = $tag === 'figcaption'
            ? 'wp-piwigo-display-caption'
            : 'wp-piwigo-display-slide-caption wp-piwigo-display-caption';

        $html = '<' . $tag . ' class="' . esc_attr($class) . '">';

        if ($show_title) {
            $html .= '<span class="wp-piwigo-display-caption-title">' . esc_html($title) . '</span>';
        }

        if ($show_description) {
            $html .= '<span class="wp-piwigo-display-caption-description">' . esc_html($description) . '</span>';
        }

        $html .= '</' . $tag . '>';

        return $html;
    }


    private static function get_image_fit(array $image, string $fit): string
    {
        if ($fit === 'raw') {
            return 'contain';
        }

        if ($fit !== 'auto') {
            return $fit;
        }

        return self::is_portrait($image) ? 'contain' : 'cover';
    }

    private static function get_orientation_class(array $image): string
    {
        if (self::is_portrait($image)) {
            return 'wp-piwigo-display-portrait';
        }

        if (self::is_landscape($image)) {
            return 'wp-piwigo-display-landscape';
        }

        return 'wp-piwigo-display-orientation-unknown';
    }

    private static function is_portrait(array $image): bool
    {
        $width = absint($image['width'] ?? 0);
        $height = absint($image['height'] ?? 0);

        return $width > 0 && $height > 0 && $height > $width;
    }

    private static function is_landscape(array $image): bool
    {
        $width = absint($image['width'] ?? 0);
        $height = absint($image['height'] ?? 0);

        return $width > 0 && $height > 0 && $width >= $height;
    }

    private static function sanitize_navigation(string $navigation): string
    {
        return in_array($navigation, ['thumbnails', 'dots', 'none'], true)
            ? $navigation
            : 'thumbnails';
    }

    private static function sanitize_fit(string $fit): string
    {
        return in_array($fit, ['cover', 'contain', 'auto', 'raw'], true) ? $fit : 'raw';
    }

    private static function is_enabled($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function sanitize_height(string $height, string $default): string
    {
        $height = trim($height);

        if (preg_match('/^\d+(px|rem|em|vh|vw|%)$/', $height) === 1) {
            return $height;
        }

        return $default;
    }

    private static function sanitize_ratio(string $ratio): string
    {
        $ratio = trim($ratio);

        if (preg_match('/^\d+\/\d+$/', $ratio) === 1) {
            return $ratio;
        }

        return '16/9';
    }
}
