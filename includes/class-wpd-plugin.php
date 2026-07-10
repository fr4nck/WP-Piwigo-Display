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

        $result = 'unknown_error';
        $url = WPD_Settings::get_piwigo_url();

        if ($url === '') {
            $result = 'missing_url';
        } else {
            try {
                $endpoint = add_query_arg(
                    [
                        'format' => 'json',
                        'method' => 'pwg.session.getStatus',
                    ],
                    untrailingslashit($url) . '/ws.php'
                );

                $response = wp_remote_get(
                    $endpoint,
                    [
                        'timeout' => 10,
                        'redirection' => 3,
                        'user-agent' => 'WP Piwigo Display/' . WPD_VERSION,
                    ]
                );

                if (is_wp_error($response)) {
                    $result = 'http_error';
                } else {
                    $status_code = wp_remote_retrieve_response_code($response);
                    $data = json_decode(wp_remote_retrieve_body($response), true);

                    if ($status_code < 200 || $status_code >= 300) {
                        $result = 'http_status';
                    } elseif (!is_array($data)) {
                        $result = 'invalid_response';
                    } elseif (($data['stat'] ?? '') !== 'ok') {
                        $result = 'api_error';
                    } else {
                        $result = 'success';
                    }
                }
            } catch (Throwable $exception) {
                $result = 'internal_error';

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('WP Piwigo Display connection test: ' . $exception->getMessage());
                }
            }
        }

        $redirect_url = add_query_arg(
            [
                'page' => 'wp-piwigo-display',
                'wpd_connection_test' => $result,
            ],
            admin_url('options-general.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }
}
