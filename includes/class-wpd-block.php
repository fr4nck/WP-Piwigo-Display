<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Gutenberg block sharing the shortcode rendering pipeline. */
final class WPD_Block
{
    public static function register(): void
    {
        register_block_type(WPD_PLUGIN_DIR . 'blocks/piwigo', [
            'render_callback' => [self::class, 'render'],
        ]);
    }

    /** Convert block attributes into the exact attribute format accepted by [piwigo]. */
    public static function attributes_to_shortcode(array $attributes): array
    {
        $map = [
            'albumId' => 'album', 'displayType' => 'type', 'recursive' => 'recursive', 'depth' => 'depth',
            'limit' => 'limit', 'max' => 'max', 'latest' => 'latest', 'random' => 'random',
            'sort' => 'sort', 'order' => 'order', 'orientations' => 'orientation', 'caption' => 'caption',
            'lightbox' => 'lightbox', 'rounded' => 'rounded', 'style' => 'style', 'autoplay' => 'autoplay',
            'interval' => 'interval', 'speed' => 'speed', 'ratio' => 'ratio', 'height' => 'height',
            'fit' => 'fit', 'navigation' => 'navigation', 'tag' => 'tag', 'tags' => 'tags', 'tagMode' => 'tag_mode',
        ];
        $atts = [];
        foreach ($map as $block_key => $shortcode_key) {
            if (!array_key_exists($block_key, $attributes)) {
                continue;
            }
            $value = $attributes[$block_key];
            if (is_array($value)) {
                $value = implode(',', array_map('sanitize_text_field', $value));
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else {
                $value = (string) $value;
            }
            $atts[$shortcode_key] = $value;
        }
        return $atts;
    }

    public static function render(array $attributes = [], string $content = '', ?WP_Block $block = null): string
    {
        // One call deliberately: the shortcode remains the only rendering engine.
        return WPD_Shortcode::render(self::attributes_to_shortcode($attributes));
    }
}
