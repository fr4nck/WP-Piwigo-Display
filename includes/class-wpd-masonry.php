<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Masonry
{
    public static function register(): void
    {
        add_filter('wp_piwigo_display_shortcode_defaults', [self::class, 'add_defaults']);
        add_filter('shortcode_atts_piwigo', [self::class, 'normalize_shortcode'], 10, 4);
        add_filter('wp_piwigo_display_render', [self::class, 'render'], 10, 4);
    }

    public static function add_defaults(array $defaults): array
    {
        $defaults['layout'] = $defaults['layout'] ?? '';
        $defaults['masonry_columns'] = $defaults['masonry_columns'] ?? '4';
        $defaults['masonry_gap'] = $defaults['masonry_gap'] ?? '16';
        return $defaults;
    }

    public static function normalize_shortcode(array $out, array $pairs, array $atts, string $shortcode): array
    {
        if (($atts['type'] ?? '') === 'masonry') {
            $out['type'] = 'gallery';
            $out['layout'] = 'masonry';
        }

        if (($atts['layout'] ?? '') === 'masonry') {
            $out['layout'] = 'masonry';
        }

        if (isset($atts['columns']) && !isset($atts['masonry_columns'])) {
            $out['masonry_columns'] = $atts['columns'];
        }

        if (isset($atts['gap']) && !isset($atts['masonry_gap'])) {
            $out['masonry_gap'] = $atts['gap'];
        }

        return $out;
    }

    public static function render($html, array $images, array $atts, string $type)
    {
        if (($atts['layout'] ?? '') !== 'masonry') {
            return $html;
        }

        wp_enqueue_style(
            'wp-piwigo-display-masonry',
            WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-masonry.css',
            ['wp-piwigo-display'],
            WPD_VERSION
        );

        $lightbox = filter_var($atts['lightbox'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        if ($lightbox) {
            wp_enqueue_script('wp-piwigo-display');
        }

        $columns = min(6, max(2, absint($atts['masonry_columns'] ?? 4)));
        $gap = min(64, max(0, absint($atts['masonry_gap'] ?? 16)));
        $caption_mode = self::caption_mode((string) ($atts['caption'] ?? 'default'));
        $rounded = filter_var($atts['rounded'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $style = self::style((string) ($atts['style'] ?? 'default'));
        $classes = 'wp-piwigo-display wp-piwigo-display-masonry wp-piwigo-display-style-' . $style;
        if ($rounded) {
            $classes .= ' wp-piwigo-display-rounded';
        }
        if ($lightbox) {
            $classes .= ' wp-piwigo-display-lightbox-enabled';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" style="--wpd-masonry-columns:<?php echo esc_attr((string) $columns); ?>;--wpd-masonry-gap:<?php echo esc_attr((string) $gap); ?>px;">
            <?php foreach ($images as $image) : ?>
                <?php
                $image_url = self::image_url($image);
                if ($image_url === '') {
                    continue;
                }
                $large_url = self::large_url($image);
                $title = self::title($image);
                $description = self::description($image);
                $caption = self::caption_text($title, $description, $caption_mode);
                ?>
                <figure class="wp-piwigo-display-masonry-item">
                    <a href="<?php echo esc_url($large_url !== '' ? $large_url : $image_url); ?>" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr($caption); ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async" />
                    </a>
                    <?php echo self::render_caption($title, $description, $caption_mode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private static function image_url(array $image): string
    {
        foreach ([['derivatives', 'medium', 'url'], ['derivatives', 'small', 'url'], ['derivatives', 'thumb', 'url']] as $path) {
            if (isset($image[$path[0]][$path[1]][$path[2]])) {
                return (string) $image[$path[0]][$path[1]][$path[2]];
            }
        }
        return isset($image['element_url']) ? (string) $image['element_url'] : '';
    }

    private static function large_url(array $image): string
    {
        if (isset($image['derivatives']['large']['url'])) {
            return (string) $image['derivatives']['large']['url'];
        }
        return self::image_url($image);
    }

    private static function title(array $image): string
    {
        return wp_strip_all_tags((string) ($image['name'] ?? $image['file'] ?? ''));
    }

    private static function description(array $image): string
    {
        $value = (string) ($image['comment'] ?? $image['description'] ?? '');
        return trim(html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function caption_mode(string $mode): string
    {
        if ($mode === 'default') {
            $mode = WPD_Settings::get_default_caption();
        }
        return in_array($mode, ['none', 'title', 'description', 'title-description'], true) ? $mode : 'none';
    }

    private static function style(string $style): string
    {
        return in_array($style, ['default', 'theme', 'minimal', 'none'], true) ? $style : 'default';
    }

    private static function caption_text(string $title, string $description, string $mode): string
    {
        if ($mode === 'title') return $title;
        if ($mode === 'description') return $description;
        if ($mode === 'title-description') return trim(implode(' — ', array_filter([$title, $description])));
        return '';
    }

    private static function render_caption(string $title, string $description, string $mode): string
    {
        $show_title = in_array($mode, ['title', 'title-description'], true) && $title !== '';
        $show_description = in_array($mode, ['description', 'title-description'], true) && $description !== '';
        if (!$show_title && !$show_description) {
            return '';
        }

        $html = '<figcaption class="wp-piwigo-display-caption">';
        if ($show_title) {
            $html .= '<span class="wp-piwigo-display-caption-title">' . esc_html($title) . '</span>';
        }
        if ($show_description) {
            $html .= '<span class="wp-piwigo-display-caption-description">' . esc_html($description) . '</span>';
        }
        return $html . '</figcaption>';
    }
}
