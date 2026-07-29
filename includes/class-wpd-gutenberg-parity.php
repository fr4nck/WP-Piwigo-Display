<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Gutenberg_Parity
{
    public static function register(): void
    {
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_assets']);
    }

    public static function enqueue_editor_assets(): void
    {
        wp_enqueue_script(
            'wpd-gutenberg-parity',
            WPD_PLUGIN_URL . 'blocks/piwigo/gutenberg-parity.js',
            ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-i18n'],
            WPD_VERSION,
            true
        );
    }
}
