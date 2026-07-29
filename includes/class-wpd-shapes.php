<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Shapes
{
    private const SHAPES = ['rectangle', 'rounded', 'circle', 'oval', 'pill', 'star', 'hexagon', 'diamond'];

    public static function register(): void
    {
        add_filter('wp_piwigo_display_shortcode_defaults', [self::class, 'add_defaults']);
        add_filter('do_shortcode_tag', [self::class, 'apply_shape'], 10, 4);
        add_action('wp_enqueue_scripts', [self::class, 'register_style']);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_assets']);
    }

    public static function add_defaults(array $defaults): array
    {
        $defaults['shape'] = $defaults['shape'] ?? 'rectangle';
        $defaults['radius'] = $defaults['radius'] ?? '0';
        return $defaults;
    }

    public static function register_style(): void
    {
        wp_register_style(
            'wpd-shapes',
            WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-shapes.css',
            ['wp-piwigo-display'],
            WPD_VERSION
        );
    }

    public static function enqueue_editor_assets(): void
    {
        wp_enqueue_script(
            'wpd-shapes-editor',
            WPD_PLUGIN_URL . 'blocks/piwigo/shapes.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            WPD_VERSION,
            true
        );
    }

    public static function apply_shape(string $output, string $tag, array $attr, array $match): string
    {
        if ($tag !== 'piwigo' || $output === '') {
            return $output;
        }

        $shape = self::sanitize_shape((string) ($attr['shape'] ?? ''));
        $radius = self::sanitize_radius($attr['radius'] ?? 0);

        if ($shape === 'rectangle' && filter_var($attr['rounded'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $shape = 'rounded';
            if ($radius === 0) {
                $radius = 8;
            }
        }

        if ($shape === 'rectangle' && $radius === 0) {
            return $output;
        }

        wp_enqueue_style('wpd-shapes');

        return (string) preg_replace_callback(
            '/<div\b([^>]*\bclass="[^"]*\bwp-piwigo-display\b[^"]*"[^>]*)>/',
            static function (array $matches) use ($shape, $radius): string {
                $attributes = $matches[1];
                $class = 'wpd-shape-' . $shape;

                $attributes = preg_replace(
                    '/\bclass="([^"]*)"/',
                    'class="$1 ' . esc_attr($class) . '"',
                    $attributes,
                    1
                );

                if (preg_match('/\bstyle="([^"]*)"/', $attributes)) {
                    $attributes = preg_replace(
                        '/\bstyle="([^"]*)"/',
                        'style="$1 --wpd-shape-radius:' . esc_attr((string) $radius) . '%;"',
                        $attributes,
                        1
                    );
                } else {
                    $attributes .= ' style="--wpd-shape-radius:' . esc_attr((string) $radius) . '%;"';
                }

                return '<div' . $attributes . '>';
            },
            $output,
            1
        );
    }

    private static function sanitize_shape(string $shape): string
    {
        $shape = sanitize_key($shape);
        $aliases = [
            '' => 'rectangle',
            'none' => 'rectangle',
            'arrondi' => 'rounded',
            'rond' => 'circle',
            'cercle' => 'circle',
            'ovale' => 'oval',
            'etoile' => 'star',
            'étoile' => 'star',
            'hexagone' => 'hexagon',
            'losange' => 'diamond',
        ];
        $shape = $aliases[$shape] ?? $shape;
        return in_array($shape, self::SHAPES, true) ? $shape : 'rectangle';
    }

    private static function sanitize_radius($radius): int
    {
        return min(50, max(0, absint($radius)));
    }
}
