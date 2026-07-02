<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Settings
{
    public const OPTION_NAME = 'wp_piwigo_display_options';

    public static function register(): void
    {
        register_setting(
            'wp_piwigo_display',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize_options'],
                'default' => self::default_options(),
            ]
        );

        add_settings_section(
            'wp_piwigo_display_main',
            __('Réglages principaux', 'wp-piwigo-display'),
            '__return_false',
            'wp-piwigo-display'
        );

        add_settings_field('piwigo_url', __('URL de la galerie Piwigo', 'wp-piwigo-display'), [self::class, 'render_piwigo_url_field'], 'wp-piwigo-display', 'wp_piwigo_display_main');
        add_settings_field('cache_duration', __('Durée du cache', 'wp-piwigo-display'), [self::class, 'render_cache_duration_field'], 'wp-piwigo-display', 'wp_piwigo_display_main');
    }

    public static function register_page(): void
    {
        add_options_page(
            __('WP Piwigo Display', 'wp-piwigo-display'),
            __('WP Piwigo Display', 'wp-piwigo-display'),
            'manage_options',
            'wp-piwigo-display',
            [self::class, 'render_page']
        );
    }

    public static function default_options(): array
    {
        return [
            'piwigo_url' => 'https://phototheque.pelemele.org/',
            'cache_duration' => 3600,
        ];
    }

    public static function get_options(): array
    {
        $options = get_option(self::OPTION_NAME, []);

        return wp_parse_args(is_array($options) ? $options : [], self::default_options());
    }

    public static function get_piwigo_url(): string
    {
        $options = self::get_options();

        return untrailingslashit((string) $options['piwigo_url']);
    }

    public static function get_cache_duration(): int
    {
        $options = self::get_options();

        return max(60, absint($options['cache_duration']));
    }

    public static function sanitize_options(array $options): array
    {
        $sanitized = self::default_options();

        if (isset($options['piwigo_url'])) {
            $url = esc_url_raw(trim((string) $options['piwigo_url']));
            $sanitized['piwigo_url'] = $url !== '' ? trailingslashit($url) : '';
        }

        if (isset($options['cache_duration'])) {
            $sanitized['cache_duration'] = max(60, absint($options['cache_duration']));
        }

        return $sanitized;
    }

    public static function render_piwigo_url_field(): void
    {
        $options = self::get_options();
        ?>
        <input type="url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[piwigo_url]" value="<?php echo esc_attr((string) $options['piwigo_url']); ?>" class="regular-text" placeholder="https://phototheque.example.org/" />
        <p class="description"><?php esc_html_e('Adresse publique de votre galerie Piwigo.', 'wp-piwigo-display'); ?></p>
        <?php
    }

    public static function render_cache_duration_field(): void
    {
        $options = self::get_options();
        ?>
        <input type="number" min="60" step="60" name="<?php echo esc_attr(self::OPTION_NAME); ?>[cache_duration]" value="<?php echo esc_attr((string) $options['cache_duration']); ?>" class="small-text" />
        <span><?php esc_html_e('secondes', 'wp-piwigo-display'); ?></span>
        <p class="description"><?php esc_html_e('Durée minimale : 60 secondes. Valeur conseillée : 3600 secondes.', 'wp-piwigo-display'); ?></p>
        <?php
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Piwigo Display', 'wp-piwigo-display'); ?></h1>
            <p><?php esc_html_e('Configurez ici l’adresse de votre galerie Piwigo et la durée du cache.', 'wp-piwigo-display'); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_piwigo_display');
                do_settings_sections('wp-piwigo-display');
                submit_button(__('Enregistrer les réglages', 'wp-piwigo-display'));
                ?>
            </form>
        </div>
        <?php
    }
}
