<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Diagnostic
{
    public static function register_page(): void
    {
        add_submenu_page(
            'wp-piwigo-display',
            __('Diagnostic WP Piwigo Display', 'wp-piwigo-display'),
            __('Diagnostic Piwigo', 'wp-piwigo-display'),
            'manage_options',
            'wp-piwigo-display-diagnostic',
            [self::class, 'render_page']
        );
    }

    public static function export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'wp-piwigo-display'));
        }

        check_admin_referer('wpd_export_diagnostic');

        $report = self::build_report();
        $filename = 'wp-piwigo-display-diagnostic-' . gmdate('Ymd-His') . '.txt';

        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $report;
        exit;
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $diagnostic = self::collect();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Diagnostic WP Piwigo Display', 'wp-piwigo-display'); ?></h1>
            <p><?php esc_html_e('Cette page résume l’état technique utile au support. Le rapport exporté exclut volontairement mots de passe, jetons, clés API et cookies.', 'wp-piwigo-display'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wpd_export_diagnostic" />
                <?php wp_nonce_field('wpd_export_diagnostic'); ?>
                <?php submit_button(__('Exporter le diagnostic (.txt)', 'wp-piwigo-display'), 'primary', 'submit', false); ?>
            </form>

            <table class="widefat striped" style="margin-top: 1rem; max-width: 960px;">
                <tbody>
                    <?php foreach ($diagnostic as $label => $value) : ?>
                        <tr>
                            <th scope="row" style="width: 280px;"><?php echo esc_html($label); ?></th>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function build_report(): string
    {
        $lines = [
            'Diagnostic WP Piwigo Display',
            'Généré le : ' . gmdate('Y-m-d H:i:s') . ' UTC',
            'Note : rapport sans mots de passe, jetons, clés API ni cookies.',
            '',
        ];

        foreach (self::collect() as $label => $value) {
            $lines[] = $label . ' : ' . $value;
        }

        return implode("\n", $lines) . "\n";
    }

    private static function collect(): array
    {
        $api = self::probe_api();
        $piwigo_url = WPD_Settings::get_piwigo_url();

        return [
            __('Version du plugin', 'wp-piwigo-display') => WPD_VERSION,
            __('Version de WordPress', 'wp-piwigo-display') => get_bloginfo('version'),
            __('Version de PHP', 'wp-piwigo-display') => PHP_VERSION,
            __('Version de Piwigo détectée', 'wp-piwigo-display') => $api['piwigo_version'],
            __('URL de l’API', 'wp-piwigo-display') => self::safe_api_url($piwigo_url),
            __('État de la connexion API', 'wp-piwigo-display') => $api['status'],
            __('Temps de réponse de l’API', 'wp-piwigo-display') => $api['response_time'],
            __('État du cache mémoire', 'wp-piwigo-display') => self::memory_cache_status(),
            __('État des transients', 'wp-piwigo-display') => self::transients_status(),
            __('Configuration SSL', 'wp-piwigo-display') => self::ssl_status($piwigo_url),
            __('Extensions PHP nécessaires', 'wp-piwigo-display') => self::extensions_status(),
        ];
    }

    private static function probe_api(): array
    {
        $piwigo_url = WPD_Settings::get_piwigo_url();

        if ($piwigo_url === '') {
            return [
                'status' => __('Non testée : URL Piwigo manquante', 'wp-piwigo-display'),
                'response_time' => __('Non disponible', 'wp-piwigo-display'),
                'piwigo_version' => __('Non détectée', 'wp-piwigo-display'),
            ];
        }

        $endpoint = add_query_arg(['format' => 'json', 'method' => 'pwg.session.getStatus'], untrailingslashit($piwigo_url) . '/ws.php');
        $start = microtime(true);
        $response = wp_remote_get($endpoint, [
            'timeout' => 10,
            'redirection' => 3,
            'user-agent' => 'WP Piwigo Display/' . WPD_VERSION,
        ]);
        $elapsed_ms = (int) round((microtime(true) - $start) * 1000);

        if (is_wp_error($response)) {
            return [
                'status' => sprintf(__('Erreur HTTP : %s', 'wp-piwigo-display'), $response->get_error_code()),
                'response_time' => sprintf(__('%d ms', 'wp-piwigo-display'), $elapsed_ms),
                'piwigo_version' => __('Non détectée', 'wp-piwigo-display'),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $is_ok = $status_code >= 200 && $status_code < 300 && is_array($data) && ($data['stat'] ?? '') === 'ok';
        $result = is_array($data['result'] ?? null) ? $data['result'] : [];
        $version = self::sanitize_report_value((string) ($result['pwg_version'] ?? $result['version'] ?? ''));

        return [
            'status' => $is_ok ? __('OK', 'wp-piwigo-display') : sprintf(__('Réponse inattendue (HTTP %d)', 'wp-piwigo-display'), $status_code),
            'response_time' => sprintf(__('%d ms', 'wp-piwigo-display'), $elapsed_ms),
            'piwigo_version' => $version !== '' ? $version : __('Non détectée', 'wp-piwigo-display'),
        ];
    }

    private static function memory_cache_status(): string
    {
        return __('Actif pendant la requête PHP courante pour les réponses API et les images d’album.', 'wp-piwigo-display');
    }

    private static function transients_status(): string
    {
        global $wpdb;

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_wpd_album_') . '%'
            )
        );

        return sprintf(__('%d transient(s) WP Piwigo Display trouvé(s), durée configurée : %d secondes.', 'wp-piwigo-display'), $count, WPD_Settings::get_cache_duration());
    }

    private static function safe_api_url(string $piwigo_url): string
    {
        if ($piwigo_url === '') {
            return __('Non configurée', 'wp-piwigo-display');
        }

        $scheme = (string) wp_parse_url($piwigo_url, PHP_URL_SCHEME);
        $host = (string) wp_parse_url($piwigo_url, PHP_URL_HOST);
        $port = wp_parse_url($piwigo_url, PHP_URL_PORT);
        $path = (string) wp_parse_url($piwigo_url, PHP_URL_PATH);

        if ($scheme === '' || $host === '') {
            return __('Non configurée', 'wp-piwigo-display');
        }

        $authority = $host . (is_int($port) ? ':' . $port : '');

        return untrailingslashit($scheme . '://' . $authority . $path) . '/ws.php?format=json';
    }

    private static function ssl_status(string $piwigo_url): string
    {
        $scheme = $piwigo_url !== '' ? wp_parse_url($piwigo_url, PHP_URL_SCHEME) : '';
        $openssl = extension_loaded('openssl') ? __('OpenSSL disponible', 'wp-piwigo-display') : __('OpenSSL indisponible', 'wp-piwigo-display');
        $curl = function_exists('curl_version') ? __('cURL disponible', 'wp-piwigo-display') : __('cURL indisponible', 'wp-piwigo-display');
        $url_status = $scheme === 'https' ? __('URL Piwigo en HTTPS', 'wp-piwigo-display') : __('URL Piwigo non HTTPS ou absente', 'wp-piwigo-display');

        return $url_status . ' — ' . $openssl . ' — ' . $curl;
    }

    private static function extensions_status(): string
    {
        $requirements = [
            'json' => extension_loaded('json'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'curl' => extension_loaded('curl'),
        ];

        $parts = [];
        foreach ($requirements as $extension => $loaded) {
            $parts[] = $extension . '=' . ($loaded ? 'OK' : 'Manquante');
        }

        return implode(', ', $parts);
    }

    private static function sanitize_report_value(string $value): string
    {
        $value = sanitize_text_field($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }
}
