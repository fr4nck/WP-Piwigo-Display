<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Api
{
    /**
     * Cache mémoire des réponses API pendant la requête PHP courante.
     *
     * @var array<string, array>
     */
    private static array $request_cache = [];

    private string $base_url;

    public function __construct(string $base_url)
    {
        $this->base_url = self::sanitize_base_url($base_url);
    }

    public function get_images_from_album(int $album_id, int $max = 0, bool $recursive = false)
    {
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d\'album invalide.', 'wp-piwigo-display'));
        }

        $images = [];
        $page = 0;
        $per_page = 500;

        do {
            $response = $this->request([
                'method' => 'pwg.categories.getImages',
                'cat_id' => $album_id,
                'recursive' => $recursive ? 'true' : 'false',
                'per_page' => $per_page,
                'page' => $page,
            ]);

            if (is_wp_error($response)) {
                return $response;
            }

            $page_images = $response['result']['images'] ?? [];

            if (!is_array($page_images)) {
                $page_images = [];
            }

            foreach ($page_images as $image) {
                $image_id = absint($image['id'] ?? 0);
                $key = $image_id > 0 ? (string) $image_id : md5(wp_json_encode($image));
                $images[$key] = $image;

                if ($max > 0 && count($images) >= $max) {
                    return array_slice(array_values($images), 0, $max);
                }
            }

            $page++;
        } while (count($page_images) === $per_page && $page < 1000);

        return array_values($images);
    }

    public function get_images_from_album_recursive(int $album_id, int $max = 0, int $depth = 10)
    {
        if ($depth <= 0) {
            return $this->get_images_from_album($album_id, $max, false);
        }

        // À la profondeur maximale, l'API Piwigo sait récupérer toute la descendance
        // en une seule série de requêtes paginées.
        if ($depth >= 10) {
            return $this->get_images_from_album($album_id, $max, true);
        }

        $album_ids = $this->get_descendant_album_ids($album_id, $depth);

        if (is_wp_error($album_ids)) {
            return $album_ids;
        }

        $images = [];

        foreach ($album_ids as $current_album_id) {
            $album_images = $this->get_images_from_album($current_album_id, 0, false);

            if (is_wp_error($album_images)) {
                return $album_images;
            }

            foreach ($album_images as $image) {
                $image_id = absint($image['id'] ?? 0);
                $key = $image_id > 0 ? (string) $image_id : md5(wp_json_encode($image));
                $images[$key] = $image;

                if ($max > 0 && count($images) >= $max) {
                    return array_slice(array_values($images), 0, $max);
                }
            }
        }

        return array_values($images);
    }

    private function get_descendant_album_ids(int $album_id, int $depth)
    {
        $categories = $this->get_all_categories();

        if (is_wp_error($categories)) {
            return $categories;
        }

        $album_ids = [$album_id];

        foreach ($categories as $category) {
            $category_id = absint($category['id'] ?? 0);
            $uppercats = trim((string) ($category['uppercats'] ?? ''));

            if ($category_id <= 0 || $uppercats === '') {
                continue;
            }

            $path = array_values(array_filter(array_map('absint', explode(',', $uppercats))));
            $root_position = array_search($album_id, $path, true);

            if ($root_position === false) {
                continue;
            }

            $relative_depth = count($path) - $root_position - 1;

            if ($relative_depth >= 1 && $relative_depth <= $depth) {
                $album_ids[] = $category_id;
            }
        }

        return array_values(array_unique(array_map('absint', $album_ids)));
    }

    public function get_child_categories(int $album_id)
    {
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d\'album invalide.', 'wp-piwigo-display'));
        }

        $response = $this->request([
            'method' => 'pwg.categories.getList',
            'cat_id' => $album_id,
            'recursive' => false,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $categories = $response['result']['categories'] ?? [];

        return is_array($categories) ? $categories : [];
    }

    public function get_all_categories()
    {
        $response = $this->request([
            'method' => 'pwg.categories.getList',
            'recursive' => true,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $categories = $response['result']['categories'] ?? [];

        return is_array($categories) ? $categories : [];
    }

    public function resolve_album_id(string $album)
    {
        $album = sanitize_text_field($album);

        if ($album === '') {
            return new WP_Error('wpd_empty_album', __('Album non renseigné.', 'wp-piwigo-display'));
        }

        if (ctype_digit($album)) {
            return absint($album);
        }

        $categories = $this->get_all_categories();

        if (is_wp_error($categories)) {
            return $categories;
        }

        $wanted_path = trim($album, '/');

        foreach ($categories as $category) {
            $id = absint($category['id'] ?? 0);
            $name = sanitize_text_field((string) ($category['name'] ?? ''));

            if ($id <= 0) {
                continue;
            }

            if (strcasecmp($name, $album) === 0) {
                return $id;
            }

            foreach (['uppercats', 'global_rank', 'permalink'] as $key) {
                if (isset($category[$key]) && strcasecmp(trim(sanitize_text_field((string) $category[$key]), '/'), $wanted_path) === 0) {
                    return $id;
                }
            }
        }

        return new WP_Error(
            'wpd_album_not_found',
            sprintf(
                __('Album introuvable : %s. Vérifiez le nom, le chemin ou utilisez directement son identifiant Piwigo.', 'wp-piwigo-display'),
                $album
            )
        );
    }

    public function test_connection()
    {
        return $this->request([
            'method' => 'pwg.session.getStatus',
        ]);
    }

    private function request(array $body)
    {
        if ($this->base_url === '') {
            return new WP_Error('wpd_invalid_url', __('URL Piwigo invalide ou non configurée.', 'wp-piwigo-display'));
        }

        $cache_key = $this->get_request_cache_key($body);

        if (isset(self::$request_cache[$cache_key])) {
            return self::$request_cache[$cache_key];
        }

        $response = wp_remote_post($this->base_url . '/ws.php?format=json', [
            'timeout' => 10,
            'redirection' => 3,
            'user-agent' => 'WP Piwigo Display/' . WPD_VERSION,
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error(
                'wpd_http_error',
                sprintf(__('Impossible de contacter la galerie Piwigo : %s', 'wp-piwigo-display'), $response->get_error_message())
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error(
                'wpd_http_status',
                sprintf(__('La galerie Piwigo a répondu avec le code HTTP %d.', 'wp-piwigo-display'), $status_code)
            );
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            return new WP_Error('wpd_invalid_json', __('La galerie Piwigo a renvoyé une réponse illisible.', 'wp-piwigo-display'));
        }

        if (($data['stat'] ?? '') !== 'ok') {
            return new WP_Error(
                'wpd_api_error',
                sprintf(__('Erreur renvoyée par Piwigo : %s', 'wp-piwigo-display'), isset($data['message']) ? sanitize_text_field((string) $data['message']) : __('erreur inconnue', 'wp-piwigo-display'))
            );
        }

        self::$request_cache[$cache_key] = $data;

        return $data;
    }

    private function get_request_cache_key(array $body): string
    {
        ksort($body);

        return md5($this->base_url . '|' . wp_json_encode($body));
    }

    private static function sanitize_base_url(string $base_url): string
    {
        $base_url = trim($base_url);

        if ($base_url === '') {
            return '';
        }

        $url = esc_url_raw($base_url);

        if ($url === '') {
            return '';
        }

        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return untrailingslashit($url);
    }

}
