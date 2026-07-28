<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Branche le compte de service sur l'administration WordPress sans modifier
 * les shortcodes publics existants.
 */
final class WPD_Service_Integration
{
    public static function register(): void
    {
        add_action('admin_init', [self::class, 'register_settings'], 20);
        add_action('admin_post_wpd_test_service_account', [self::class, 'test_connection']);
        add_action('wp_ajax_wpd_get_albums', [self::class, 'ajax_get_albums'], 1);
        add_action('admin_notices', [self::class, 'render_notice']);
    }

    public static function register_settings(): void
    {
        register_setting('wp_piwigo_display', WPD_Service_Account::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_options'],
            'default' => [
                'enabled' => 'false',
                'username' => '',
                'password' => '',
            ],
        ]);

        add_settings_section(
            'wp_piwigo_display_service_account',
            __('Compte de service Piwigo', 'wp-piwigo-display'),
            [self::class, 'render_section'],
            'wp-piwigo-display'
        );

        add_settings_field('wpd_service_enabled', __('Activer', 'wp-piwigo-display'), [self::class, 'render_enabled'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
        add_settings_field('wpd_service_username', __('Identifiant Piwigo', 'wp-piwigo-display'), [self::class, 'render_username'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
        add_settings_field('wpd_service_password', __('Mot de passe Piwigo', 'wp-piwigo-display'), [self::class, 'render_password'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
        add_settings_field('wpd_service_test', __('Vérification', 'wp-piwigo-display'), [self::class, 'render_test_button'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
    }

    public static function sanitize_options($input): array
    {
        $input = is_array($input) ? $input : [];
        $previous = get_option(WPD_Service_Account::OPTION_NAME, []);
        $previous = is_array($previous) ? $previous : [];

        $password = isset($input['password']) ? (string) $input['password'] : '';
        if ($password === '') {
            $password = (string) ($previous['password'] ?? '');
        }

        $sanitized = [
            'enabled' => filter_var($input['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'username' => sanitize_text_field((string) ($input['username'] ?? '')),
            'password' => $password,
        ];

        $changed = ($previous['enabled'] ?? 'false') !== $sanitized['enabled']
            || ($previous['username'] ?? '') !== $sanitized['username']
            || ($previous['password'] ?? '') !== $sanitized['password'];

        if ($changed && class_exists('WPD_Cache')) {
            WPD_Cache::clear_all();
        }

        return $sanitized;
    }

    public static function render_section(): void
    {
        $status = WPD_Service_Account::get_public_status();
        echo '<p>' . esc_html__('Ce compte technique permet à WordPress de publier des albums privés autorisés dans Piwigo. Les visiteurs ne se connectent pas à Piwigo.', 'wp-piwigo-display') . '</p>';
        echo '<p><strong>' . esc_html__('Attention : un album privé sélectionné devient public sur la page WordPress qui l’affiche.', 'wp-piwigo-display') . '</strong></p>';
        if ($status['source'] === 'wp-config.php') {
            echo '<p><em>' . esc_html__('Les identifiants sont définis dans wp-config.php et sont prioritaires sur ces champs.', 'wp-piwigo-display') . '</em></p>';
        }
    }

    public static function render_enabled(): void
    {
        $status = WPD_Service_Account::get_public_status();
        printf(
            '<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s %3$s> %4$s</label>',
            esc_attr(WPD_Service_Account::OPTION_NAME),
            checked($status['enabled'], true, false),
            $status['source'] === 'wp-config.php' ? 'disabled' : '',
            esc_html__('Utiliser le compte technique pour le sélecteur et le rendu des albums.', 'wp-piwigo-display')
        );
    }

    public static function render_username(): void
    {
        $status = WPD_Service_Account::get_public_status();
        printf(
            '<input class="regular-text" type="text" autocomplete="off" name="%1$s[username]" value="%2$s" %3$s>',
            esc_attr(WPD_Service_Account::OPTION_NAME),
            esc_attr($status['username']),
            $status['source'] === 'wp-config.php' ? 'disabled' : ''
        );
    }

    public static function render_password(): void
    {
        $status = WPD_Service_Account::get_public_status();
        printf(
            '<input class="regular-text" type="password" autocomplete="new-password" name="%1$s[password]" value="" placeholder="%2$s" %3$s><p class="description">%4$s</p>',
            esc_attr(WPD_Service_Account::OPTION_NAME),
            esc_attr($status['configured'] ? __('Mot de passe déjà enregistré', 'wp-piwigo-display') : ''),
            $status['source'] === 'wp-config.php' ? 'disabled' : '',
            esc_html__('Laissez vide pour conserver le mot de passe actuel. Il n’est jamais réaffiché.', 'wp-piwigo-display')
        );
    }

    public static function render_test_button(): void
    {
        $url = wp_nonce_url(admin_url('admin-post.php?action=wpd_test_service_account'), 'wpd_test_service_account');
        printf('<a class="button" href="%1$s">%2$s</a>', esc_url($url), esc_html__('Tester le compte de service', 'wp-piwigo-display'));
    }

    public static function test_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'wp-piwigo-display'));
        }
        check_admin_referer('wpd_test_service_account');

        $result = 'error';
        if (!WPD_Service_Account::is_configured()) {
            $result = 'not_configured';
        } else {
            $response = (new WPD_Service_Api(WPD_Settings::get_piwigo_url()))->test_connection();
            $result = is_wp_error($response) ? 'error' : 'success';
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'wp-piwigo-display-settings',
            'wpd_service_test' => $result,
        ], admin_url('admin.php')));
        exit;
    }

    public static function render_notice(): void
    {
        if (!isset($_GET['page'], $_GET['wpd_service_test']) || sanitize_key((string) $_GET['page']) !== 'wp-piwigo-display-settings') {
            return;
        }

        $result = sanitize_key((string) wp_unslash($_GET['wpd_service_test']));
        $class = $result === 'success' ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
        $message = $result === 'success'
            ? __('Connexion du compte de service réussie.', 'wp-piwigo-display')
            : ($result === 'not_configured'
                ? __('Compte de service incomplet ou désactivé.', 'wp-piwigo-display')
                : __('Échec de connexion du compte de service. Vérifiez l’URL, HTTPS et les identifiants.', 'wp-piwigo-display'));

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public static function ajax_get_albums(): void
    {
        if (!WPD_Service_Account::is_configured()) {
            return;
        }

        check_ajax_referer('wpd_get_albums', 'nonce');
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Accès refusé.', 'wp-piwigo-display')], 403);
        }

        $categories = (new WPD_Service_Api(WPD_Settings::get_piwigo_url()))->get_all_categories();
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
                'private' => true,
            ];
        }

        usort($albums, static fn(array $a, array $b): int => strnatcasecmp((string) $a['path'], (string) $b['path']));
        wp_send_json_success(['albums' => $albums, 'serviceAccount' => true]);
    }
}
