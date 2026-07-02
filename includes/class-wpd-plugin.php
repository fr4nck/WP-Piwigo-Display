<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Plugin
{
    private static ?self $instance = null;

    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_shortcodes']);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'wp-piwigo-display',
            false,
            dirname(plugin_basename(WPD_PLUGIN_FILE)) . '/languages'
        );
    }

    public function register_shortcodes(): void
    {
        WPD_Shortcode::register();
    }
}
