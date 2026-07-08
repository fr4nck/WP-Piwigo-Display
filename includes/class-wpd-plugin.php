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
        add_action('admin_post_wpd_clear_cache', [$this, 'clear_cache']);
        add_action('admin_post_wpd_test_connection', [$this, 'test_connection']);
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
        wp_register_style('wp-piwigo-display', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display.css', [], WPD_VERSION);
        wp_register_style('wpd-splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', [], '4.1.4');
        wp_register_script('wpd-splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', [], '4.1.4', true);
        wp_register_script('wp-piwigo-display', WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display.js', ['wpd-splide'], WPD_VERSION, true);
    }

    public function register_settings(): void
    {
        WPD_Settings::register();
    }

    public function register_settings_page(): void
    {
        WPD_Settings::register_page();
    }

    public function clear_cache(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'wp-piwigo-display'));
        }

        check_admin_referer('wpd_clear_cache');
        $deleted = WPD_Cache::clear_all();

        wp_safe_redirect(add_query_arg(['page' => 'wp-piwigo-display', 'wpd_cache_cleared' => (string) $deleted], admin_url('options-general.php')));
        exit;
    }

    public function test_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'wp-piwigo-display'));
        }

        check_admin_referer('wpd_test_connection');

        $url = WPD_Settings::get_piwigo_url();
        $result = 'missing_url';

        if ($url !== '') {
            $api = new WPD_Api($url);
            $response = $api->test_connection();
            $result = is_wp_error($response) ? 'api_error' : 'success';
        }

        wp_safe_redirect(add_query_arg(['page' => 'wp-piwigo-display', 'wpd_connection_test' => $result], admin_url('options-general.php')));
        exit;
    }
}
