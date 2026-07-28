<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Slider_Transitions
{
    public static function register(): void
    {
        add_filter('wp_piwigo_display_shortcode_defaults', [self::class, 'add_defaults']);
        add_filter('do_shortcode_tag', [self::class, 'inject_slider_attributes'], 10, 4);
    }

    public static function add_defaults(array $defaults): array
    {
        $defaults['transition'] = 'slide';
        $defaults['direction'] = 'ltr';

        return $defaults;
    }

    public static function inject_slider_attributes(string $output, string $tag, array $attr, array $match): string
    {
        if ($tag !== 'piwigo' || ($attr['type'] ?? 'gallery') !== 'slider') {
            return $output;
        }

        $transition = self::sanitize_transition((string) ($attr['transition'] ?? 'slide'));
        $direction = self::sanitize_direction((string) ($attr['direction'] ?? 'ltr'));
        $attributes = sprintf(
            ' data-transition="%s" data-direction="%s"',
            esc_attr($transition),
            esc_attr($direction)
        );

        return (string) preg_replace(
            '/(<div\b[^>]*class="[^"]*\bwp-piwigo-display-slider\b[^"]*"[^>]*)(>)/',
            '$1' . $attributes . '$2',
            $output,
            1
        );
    }

    private static function sanitize_transition(string $transition): string
    {
        return in_array($transition, ['slide', 'fade', 'none'], true) ? $transition : 'slide';
    }

    private static function sanitize_direction(string $direction): string
    {
        return in_array($direction, ['ltr', 'rtl'], true) ? $direction : 'ltr';
    }
}
