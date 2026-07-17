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
        add_action('init', [$this, 'register_block']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_album_picker']);
        add_action('wp_ajax_wpd_get_albums', [$this, 'ajax_get_albums']);
        add_action('admin_post_wpd_clear_cache', [$this, 'clear_cache']);
        add_action('admin_post_wpd_test_connection', [$this, 'test_connection']);
        add_action('admin_post_wpd_export_diagnostic', [WPD_Diagnostic::class, 'export']);
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

    public function register_block(): void
    {
        WPD_Block::register();
    }

    public function register_assets(): void
    {
        wp_register_style('wp-piwigo-display', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display.css', [], WPD_VERSION);
        wp_register_style('wpd-splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', [], '4.1.4');
        wp_register_script('wpd-splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', [], '4.1.4', true);
        wp_register_script('wp-piwigo-display-slider', WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-slider.js', ['wpd-splide'], WPD_VERSION, true);
        wp_register_script('wp-piwigo-display', WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display.js', [], WPD_VERSION, true);
    }

    public function register_settings(): void
    {
        WPD_Settings::register();
    }

    public function register_settings_page(): void
    {
        WPD_Settings::register_page();
        WPD_Diagnostic::register_page();
    }

    public function enqueue_admin_album_picker(string $hook): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_editor = in_array($hook, ['post.php', 'post-new.php'], true);
        $is_plugin_page = $screen && strpos((string) $screen->id, 'wp-piwigo-display') !== false;

        if (!$is_editor && !$is_plugin_page) {
            return;
        }

        wp_enqueue_style(
            'wpd-album-picker',
            WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-album-picker.css',
            [],
            WPD_VERSION
        );
        wp_enqueue_script(
            'wpd-album-picker',
            WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-album-picker.js',
            ['jquery'],
            WPD_VERSION,
            true
        );
        wp_localize_script('wpd-album-picker', 'WPDAlbumPickerConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpd_get_albums'),
            'labels' => [
                'loading' => __('Chargement des albums…', 'wp-piwigo-display'),
                'error' => __('Impossible de charger les albums. La saisie manuelle reste disponible.', 'wp-piwigo-display'),
                'empty' => __('Aucun album trouvé.', 'wp-piwigo-display'),
                'search' => __('Rechercher un album…', 'wp-piwigo-display'),
            ],
        ]);
    }

    public function ajax_get_albums(): void
    {
        check_ajax_referer('wpd_get_albums', 'nonce');

        if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Accès refusé.', 'wp-piwigo-display')], 403);
        }

        $url = WPD_Settings::get_piwigo_url();
        if ($url === '') {
            wp_send_json_error(['message' => __('URL Piwigo non configurée.', 'wp-piwigo-display')], 400);
        }

        $categories = (new WPD_Api($url))->get_all_categories();
        if (is_wp_error($categories)) {
            wp_send_json_error(['message' => $categories->get_error_message()], 502);
        }

        $names = [];
        foreach ($categories as $category) {
            $id = absint($category['id'] ?? 0);
            if ($id > 0) {
                $names[$id] = sanitize_text_field((string) ($category['name'] ?? ('Album ' . $id)));
            }
        }

        $albums = [];
        foreach ($categories as $category) {
            $id = absint($category['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $path_ids = array_values(array_filter(array_map('absint', explode(',', (string) ($category['uppercats'] ?? $id)))));
            if (empty($path_ids)) {
                $path_ids = [$id];
            }
            $path_names = [];
            foreach ($path_ids as $path_id) {
                if (isset($names[$path_id])) {
                    $path_names[] = $names[$path_id];
                }
            }
            $albums[] = [
                'id' => $id,
                'name' => $names[$id] ?? ('Album ' . $id),
                'path' => implode('/', $path_names),
                'depth' => max(0, count($path_ids) - 1),
                'images' => absint($category['nb_images'] ?? $category['total_nb_images'] ?? 0),
            ];
        }

        usort($albums, static function (array $a, array $b): int {
            return strnatcasecmp((string) $a['path'], (string) $b['path']);
        });

        wp_send_json_success(['albums' => $albums]);
    }

    public function clear_cache(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'wp-piwigo-display'));
        }

        check_admin_referer('wpd_clear_cache');
        $deleted = WPD_Cache::clear_all();

        wp_safe_redirect(add_query_arg(['page' => 'wp-piwigo-display', 'wpd_cache_cleared' => (string) $deleted], admin_url('admin.php')));
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
                $api = new WPD_Api($url);
                $response = $api->test_connection();

                if (is_wp_error($response)) {
                    $result = $this->get_connection_test_result($response);
                } else {
                    $result = 'success';
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
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    private function get_connection_test_result(WP_Error $error): string
    {
        switch ($error->get_error_code()) {
            case 'wpd_http_error':
                return 'http_error';

            case 'wpd_http_status':
                return 'http_status';

            case 'wpd_invalid_json':
                return 'invalid_response';

            case 'wpd_api_error':
                return 'api_error';

            default:
                return 'unknown_error';
        }
    }
}
