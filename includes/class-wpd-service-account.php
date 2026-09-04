<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Service_Account
{
    public const OPTION_NAME = 'wp_piwigo_display_service_account';
    public const ENABLED_CONSTANT = 'WPD_PIWIGO_SERVICE_ENABLED';
    public const USERNAME_CONSTANT = 'WPD_PIWIGO_SERVICE_USERNAME';
    public const PASSWORD_CONSTANT = 'WPD_PIWIGO_SERVICE_PASSWORD';
    public const API_KEY_CONSTANT = 'WPD_PIWIGO_SERVICE_API_KEY';

    public static function register(): void
    {
        add_action('admin_init', [self::class, 'register_settings'], 20);
        add_action('wp_ajax_wpd_get_albums', [self::class, 'ajax_get_albums'], 1);
        add_action('admin_post_wpd_test_service_account', [self::class, 'test_connection']);
        add_action('admin_notices', [self::class, 'render_notice']);
    }

    public static function register_settings(): void
    {
        register_setting('wp_piwigo_display', self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_options'],
            'default' => ['enabled' => 'false', 'username' => '', 'password' => '', 'api_key' => ''],
        ]);

        add_settings_section(
            'wp_piwigo_display_service_account',
            __('Compte de service Piwigo', 'wp-piwigo-display'),
            [self::class, 'render_section'],
            'wp-piwigo-display'
        );
        add_settings_field('wpd_service_enabled', __('Activer', 'wp-piwigo-display'), [self::class, 'render_enabled_field'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
        add_settings_field('wpd_service_api_key', __('Clé API Piwigo', 'wp-piwigo-display'), [self::class, 'render_api_key_field'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
        add_settings_field('wpd_service_username', __('Utilisateur Piwigo', 'wp-piwigo-display'), [self::class, 'render_username_field'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
        add_settings_field('wpd_service_password', __('Mot de passe Piwigo', 'wp-piwigo-display'), [self::class, 'render_password_field'], 'wp-piwigo-display', 'wp_piwigo_display_service_account');
    }

    public static function sanitize_options($options): array
    {
        $options = is_array($options) ? $options : [];
        $previous = self::get_options();
        $password = isset($options['password']) ? (string) $options['password'] : '';
        $api_key = isset($options['api_key']) ? trim((string) $options['api_key']) : '';

        $sanitized = [
            'enabled' => filter_var($options['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'username' => sanitize_text_field((string) ($options['username'] ?? '')),
            'password' => $password !== '' ? $password : (string) ($previous['password'] ?? ''),
            'api_key' => $api_key !== '' ? sanitize_text_field($api_key) : (string) ($previous['api_key'] ?? ''),
        ];

        $changed = ($previous['enabled'] ?? 'false') !== $sanitized['enabled']
            || ($previous['username'] ?? '') !== $sanitized['username']
            || ($previous['password'] ?? '') !== $sanitized['password']
            || ($previous['api_key'] ?? '') !== $sanitized['api_key'];

        if ($changed && class_exists('WPD_Cache')) {
            WPD_Cache::clear_all();
        }

        return $sanitized;
    }

    public static function render_section(): void
    {
        echo '<p>' . esc_html__('Ce compte permet à WordPress de publier des albums privés autorisés dans Piwigo. Les visiteurs ne se connectent pas à Piwigo.', 'wp-piwigo-display') . '</p>';
        echo '<p><strong>' . esc_html__('Attention : les photos sélectionnées deviennent publiques sur la page WordPress.', 'wp-piwigo-display') . '</strong></p>';
        echo '<p class="description">' . esc_html__('Piwigo 16+ : utilisez de préférence une clé API. Le couple utilisateur/mot de passe reste disponible pour compatibilité.', 'wp-piwigo-display') . '</p>';
        if (self::is_managed_by_constants()) {
            echo '<p class="description">' . esc_html__('Les identifiants définis dans wp-config.php sont prioritaires sur les champs ci-dessous.', 'wp-piwigo-display') . '</p>';
        }
    }

    public static function render_enabled_field(): void
    {
        printf(
            '<label><input type="checkbox" name="%1$s[enabled]" value="true" %2$s %3$s> %4$s</label>',
            esc_attr(self::OPTION_NAME),
            checked(self::is_enabled(), true, false),
            disabled(defined(self::ENABLED_CONSTANT), true, false),
            esc_html__('Utiliser le compte technique pour les albums privés', 'wp-piwigo-display')
        );
    }

    public static function render_api_key_field(): void
    {
        printf(
            '<input type="password" class="regular-text" name="%1$s[api_key]" value="" placeholder="%2$s" autocomplete="new-password" %3$s><p class="description">%4$s</p>',
            esc_attr(self::OPTION_NAME),
            esc_attr(self::get_api_key() !== '' ? __('Clé API enregistrée — laisser vide pour la conserver', 'wp-piwigo-display') : ''),
            disabled(defined(self::API_KEY_CONSTANT), true, false),
            esc_html__('Prioritaire sur le login/mot de passe. La clé n’est jamais réaffichée dans l’administration.', 'wp-piwigo-display')
        );
    }

    public static function render_username_field(): void
    {
        printf(
            '<input type="text" class="regular-text" name="%1$s[username]" value="%2$s" autocomplete="off" %3$s>',
            esc_attr(self::OPTION_NAME),
            esc_attr(self::get_username()),
            disabled(defined(self::USERNAME_CONSTANT), true, false)
        );
    }

    public static function render_password_field(): void
    {
        printf(
            '<input type="password" class="regular-text" name="%1$s[password]" value="" placeholder="%2$s" autocomplete="new-password" %3$s><p class="description">%4$s</p>',
            esc_attr(self::OPTION_NAME),
            esc_attr(self::get_password() !== '' ? __('Mot de passe enregistré — laisser vide pour le conserver', 'wp-piwigo-display') : ''),
            disabled(defined(self::PASSWORD_CONSTANT), true, false),
            esc_html__('Compatibilité Piwigo historique. Le mot de passe n’est jamais réaffiché.', 'wp-piwigo-display')
        );

        $url = wp_nonce_url(admin_url('admin-post.php?action=wpd_test_service_account'), 'wpd_test_service_account');
        echo '<p><a class="button" href="' . esc_url($url) . '">' . esc_html__('Tester le compte de service', 'wp-piwigo-display') . '</a></p>';
    }

    public static function test_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'wp-piwigo-display'));
        }
        check_admin_referer('wpd_test_service_account');

        if (!self::is_configured()) {
            $result = 'not_configured';
        } elseif (WPD_Settings::get_piwigo_url() === '') {
            $result = 'missing_url';
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
        if (!isset($_GET['page'], $_GET['wpd_service_test'])) {
            return;
        }
        if (sanitize_key((string) wp_unslash($_GET['page'])) !== 'wp-piwigo-display-settings') {
            return;
        }

        $result = sanitize_key((string) wp_unslash($_GET['wpd_service_test']));
        $class = $result === 'success' ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
        $messages = [
            'success' => __('Connexion du compte de service réussie.', 'wp-piwigo-display'),
            'not_configured' => __('Compte de service incomplet ou désactivé.', 'wp-piwigo-display'),
            'missing_url' => __('URL Piwigo manquante.', 'wp-piwigo-display'),
            'error' => __('Échec de connexion. Vérifiez HTTPS, la clé API ou les identifiants et les droits Piwigo.', 'wp-piwigo-display'),
        ];
        $message = $messages[$result] ?? $messages['error'];
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public static function ajax_get_albums(): void
    {
        if (!self::is_configured()) {
            return;
        }

        check_ajax_referer('wpd_get_albums', 'nonce');
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Accès refusé.', 'wp-piwigo-display')], 403);
        }

        $url = WPD_Settings::get_piwigo_url();
        $categories = (new WPD_Service_Api($url))->get_all_categories();
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
            foreach ($path_ids ?: [$id] as $path_id) {
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
                'serviceAccount' => true,
            ];
        }

        usort($albums, static fn(array $a, array $b): int => strnatcasecmp((string) $a['path'], (string) $b['path']));
        wp_send_json_success(['albums' => $albums, 'serviceAccount' => true]);
    }

    public static function is_enabled(): bool
    {
        if (defined(self::ENABLED_CONSTANT)) {
            return filter_var(constant(self::ENABLED_CONSTANT), FILTER_VALIDATE_BOOLEAN);
        }
        return filter_var(self::get_options()['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function get_api_key(): string
    {
        if (defined(self::API_KEY_CONSTANT)) {
            return trim((string) constant(self::API_KEY_CONSTANT));
        }
        return trim((string) (self::get_options()['api_key'] ?? ''));
    }

    public static function uses_api_key(): bool
    {
        return self::is_enabled() && self::get_api_key() !== '';
    }

    public static function get_username(): string
    {
        if (defined(self::USERNAME_CONSTANT)) {
            return sanitize_text_field((string) constant(self::USERNAME_CONSTANT));
        }
        return sanitize_text_field((string) (self::get_options()['username'] ?? ''));
    }

    public static function get_password(): string
    {
        if (defined(self::PASSWORD_CONSTANT)) {
            return (string) constant(self::PASSWORD_CONSTANT);
        }
        return (string) (self::get_options()['password'] ?? '');
    }

    public static function is_configured(): bool
    {
        if (!self::is_enabled()) {
            return false;
        }

        return self::get_api_key() !== '' || (self::get_username() !== '' && self::get_password() !== '');
    }

    public static function is_managed_by_constants(): bool
    {
        return defined(self::ENABLED_CONSTANT)
            || defined(self::API_KEY_CONSTANT)
            || defined(self::USERNAME_CONSTANT)
            || defined(self::PASSWORD_CONSTANT);
    }

    public static function get_context_hash(): string
    {
        if (!self::is_configured()) {
            return 'anonymous';
        }

        $identity = self::uses_api_key()
            ? 'api:' . hash('sha256', self::get_api_key())
            : 'user:' . self::get_username();

        return hash('sha256', WPD_Settings::get_piwigo_url() . '|' . $identity);
    }

    public static function get_public_status(): array
    {
        return [
            'enabled' => self::is_enabled(),
            'configured' => self::is_configured(),
            'authentication' => self::uses_api_key() ? 'api_key' : 'password',
            'username' => self::uses_api_key() ? '' : self::get_username(),
            'source' => self::is_managed_by_constants() ? 'wp-config.php' : 'database',
        ];
    }

    private static function get_options(): array
    {
        $options = get_option(self::OPTION_NAME, []);
        return is_array($options) ? $options : [];
    }
}
