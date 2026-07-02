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
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'register_settings_page']);
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

    public function register_assets(): void
    {
        wp_register_style(
            'wp-piwigo-display',
            WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display.css',
            [],
            WPD_VERSION
        );
    }

    public function register_settings(): void
    {
        WPD_Settings::register();
    }

    public function register_settings_page(): void
    {
        WPD_Settings::register_page();
    }
}
