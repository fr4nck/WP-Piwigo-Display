<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Settings
{
    public const OPTION_NAME = 'wp_piwigo_display_options';

    public static function register(): void
    {
        register_setting('wp_piwigo_display', self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_options'],
            'default' => self::default_options(),
        ]);

        add_settings_section('wp_piwigo_display_main', __('Connexion Piwigo', 'wp-piwigo-display'), '__return_false', 'wp-piwigo-display');
        add_settings_field('piwigo_url', __('URL de la galerie Piwigo', 'wp-piwigo-display'), [self::class, 'render_piwigo_url_field'], 'wp-piwigo-display', 'wp_piwigo_display_main');
        add_settings_field('cache_duration', __('Durée du cache', 'wp-piwigo-display'), [self::class, 'render_cache_duration_field'], 'wp-piwigo-display', 'wp_piwigo_display_main');

        add_settings_section('wp_piwigo_display_defaults', __('Affichage par défaut', 'wp-piwigo-display'), [self::class, 'render_defaults_section'], 'wp-piwigo-display');
        add_settings_field('default_type', __('Type d’affichage', 'wp-piwigo-display'), [self::class, 'render_default_type_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_fit', __('Respect des images', 'wp-piwigo-display'), [self::class, 'render_default_fit_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_ratio', __('Ratio du diaporama', 'wp-piwigo-display'), [self::class, 'render_default_ratio_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_height', __('Hauteur du diaporama', 'wp-piwigo-display'), [self::class, 'render_default_height_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_autoplay', __('Lecture automatique', 'wp-piwigo-display'), [self::class, 'render_default_autoplay_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_interval', __('Tempo', 'wp-piwigo-display'), [self::class, 'render_default_interval_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_speed', __('Vitesse de transition', 'wp-piwigo-display'), [self::class, 'render_default_speed_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_lightbox', __('Lightbox', 'wp-piwigo-display'), [self::class, 'render_default_lightbox_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_navigation', __('Navigation du diaporama', 'wp-piwigo-display'), [self::class, 'render_default_navigation_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_rounded', __('Coins arrondis', 'wp-piwigo-display'), [self::class, 'render_default_rounded_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
    }

    public static function register_page(): void
    {
        add_options_page(__('WP Piwigo Display', 'wp-piwigo-display'), __('WP Piwigo Display', 'wp-piwigo-display'), 'manage_options', 'wp-piwigo-display', [self::class, 'render_page']);
    }

    public static function default_options(): array
    {
        return [
            'piwigo_url' => 'https://phototheque.pelemele.org/',
            'cache_duration' => 3600,
            'default_type' => 'gallery',
            'default_fit' => 'contain',
            'default_ratio' => '16/9',
            'default_height' => '',
            'default_autoplay' => 'true',
            'default_interval' => 5000,
            'default_speed' => 500,
            'default_lightbox' => 'true',
            'default_navigation' => 'thumbnails',
            'default_rounded' => 'false',
        ];
    }

    public static function get_options(): array
    {
        $options = get_option(self::OPTION_NAME, []);
        return wp_parse_args(is_array($options) ? $options : [], self::default_options());
    }

    public static function get_shortcode_defaults(): array
    {
        $options = self::get_options();
        return [
            'type' => (string) $options['default_type'],
            'autoplay' => (string) $options['default_autoplay'],
            'interval' => (string) $options['default_interval'],
            'speed' => (string) $options['default_speed'],
            'fit' => (string) $options['default_fit'],
            'height' => (string) $options['default_height'],
            'ratio' => (string) $options['default_ratio'],
            'rounded' => (string) $options['default_rounded'],
            'lightbox' => (string) $options['default_lightbox'],
            'navigation' => (string) $options['default_navigation'],
            'thumbnails' => ((string) $options['default_navigation'] === 'thumbnails') ? 'true' : 'false',
        ];
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
        $sanitized['default_type'] = self::sanitize_choice($options['default_type'] ?? 'gallery', ['gallery', 'slider'], 'gallery');
        $sanitized['default_fit'] = self::sanitize_choice($options['default_fit'] ?? 'contain', ['cover', 'contain', 'auto', 'raw'], 'contain');
        $sanitized['default_ratio'] = self::sanitize_ratio((string) ($options['default_ratio'] ?? '16/9'));
        $sanitized['default_height'] = self::sanitize_height((string) ($options['default_height'] ?? ''));
        $sanitized['default_autoplay'] = self::sanitize_bool($options['default_autoplay'] ?? 'true');
        $sanitized['default_interval'] = max(1000, absint($options['default_interval'] ?? 5000));
        $sanitized['default_speed'] = max(0, absint($options['default_speed'] ?? 500));
        $sanitized['default_lightbox'] = self::sanitize_bool($options['default_lightbox'] ?? 'true');
        $sanitized['default_navigation'] = self::sanitize_choice($options['default_navigation'] ?? 'thumbnails', ['thumbnails', 'dots', 'none'], 'thumbnails');
        $sanitized['default_rounded'] = self::sanitize_bool($options['default_rounded'] ?? 'false');
        return $sanitized;
    }

    public static function render_defaults_section(): void
    {
        echo '<p>' . esc_html__('Ces valeurs sont utilisées par défaut. Un shortcode peut les remplacer.', 'wp-piwigo-display') . '</p>';
    }

    public static function render_piwigo_url_field(): void
    {
        $o = self::get_options();
        self::input('url', 'piwigo_url', (string) $o['piwigo_url'], 'regular-text', 'https://phototheque.example.org/');
    }

    public static function render_cache_duration_field(): void
    {
        $o = self::get_options();
        self::input('number', 'cache_duration', (string) $o['cache_duration'], 'small-text', '', ['min' => '60', 'step' => '60']);
        echo ' <span>' . esc_html__('secondes', 'wp-piwigo-display') . '</span>';
    }

    public static function render_default_type_field(): void { self::select('default_type', ['gallery' => 'Galerie', 'slider' => 'Diaporama']); }
    public static function render_default_fit_field(): void { self::select('default_fit', ['contain' => 'Image entière', 'cover' => 'Cadre rempli', 'auto' => 'Automatique', 'raw' => 'Brut']); }
    public static function render_default_ratio_field(): void { $o = self::get_options(); self::input('text', 'default_ratio', (string) $o['default_ratio'], 'small-text', '16/9'); }
    public static function render_default_height_field(): void { $o = self::get_options(); self::input('text', 'default_height', (string) $o['default_height'], 'small-text', 'ex : 520px'); }
    public static function render_default_autoplay_field(): void { self::select_bool('default_autoplay'); }
    public static function render_default_lightbox_field(): void { self::select_bool('default_lightbox'); }
    public static function render_default_navigation_field(): void { self::select('default_navigation', ['thumbnails' => 'Miniatures', 'dots' => 'Points', 'none' => 'Aucune']); }
    public static function render_default_rounded_field(): void { self::select_bool('default_rounded'); }

    public static function render_default_interval_field(): void
    {
        $o = self::get_options();
        self::input('number', 'default_interval', (string) $o['default_interval'], 'small-text', '', ['min' => '1000', 'step' => '500']);
        echo ' <span>ms</span>';
    }

    public static function render_default_speed_field(): void
    {
        $o = self::get_options();
        self::input('number', 'default_speed', (string) $o['default_speed'], 'small-text', '', ['min' => '0', 'step' => '100']);
        echo ' <span>ms</span>';
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Piwigo Display', 'wp-piwigo-display'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_piwigo_display'); do_settings_sections('wp-piwigo-display'); submit_button(__('Enregistrer les réglages', 'wp-piwigo-display')); ?>
            </form>
        </div>
        <?php
    }

    private static function input(string $type, string $key, string $value, string $class = '', string $placeholder = '', array $attrs = []): void
    {
        $attr_html = '';
        foreach ($attrs as $name => $attr_value) {
            $attr_html .= ' ' . esc_attr($name) . '="' . esc_attr($attr_value) . '"';
        }
        printf('<input type="%1$s" name="%2$s[%3$s]" value="%4$s" class="%5$s" placeholder="%6$s"%7$s />', esc_attr($type), esc_attr(self::OPTION_NAME), esc_attr($key), esc_attr($value), esc_attr($class), esc_attr($placeholder), $attr_html);
    }

    private static function select(string $key, array $choices): void
    {
        $options = self::get_options();
        $current = (string) ($options[$key] ?? '');
        echo '<select name="' . esc_attr(self::OPTION_NAME) . '[' . esc_attr($key) . ']">';
        foreach ($choices as $value => $label) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($value), selected($current, (string) $value, false), esc_html($label));
        }
        echo '</select>';
    }

    private static function select_bool(string $key): void
    {
        self::select($key, ['true' => 'Oui', 'false' => 'Non']);
    }

    private static function sanitize_choice(string $value, array $allowed, string $default): string { return in_array($value, $allowed, true) ? $value : $default; }
    private static function sanitize_bool($value): string { return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'; }
    private static function sanitize_ratio(string $ratio): string { return preg_match('/^\d+\/\d+$/', $ratio) === 1 ? $ratio : '16/9'; }
    private static function sanitize_height(string $height): string { $height = trim($height); return preg_match('/^\d+(px|rem|em|vh|vw|%)$/', $height) === 1 ? $height : ''; }
}
