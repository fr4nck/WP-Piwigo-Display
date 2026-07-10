<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Api
{
    private string $base_url;

    public function __construct(string $base_url)
    {
        $this->base_url = self::sanitize_base_url($base_url);
    }

    public function get_images_from_album(int $album_id, int $max = 0)
    {
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d\'album invalide.', 'wp-piwigo-display'));
        }

        $response = $this->request([
            'method' => 'pwg.categories.getImages',
            'cat_id' => $album_id,
            'per_page' => $max > 0 ? $max : 500,
            'page' => 0,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $images = $response['result']['images'] ?? [];

        return is_array($images) ? $images : [];
    }

    public function get_images_from_album_recursive(int $album_id, int $max = 0, int $depth = 10)
    {
        $images = $this->get_images_from_album($album_id, 0);

        if (is_wp_error($images)) {
            return $images;
        }

        if ($depth <= 0) {
            return $max > 0 ? array_slice($images, 0, $max) : $images;
        }

        $children = $this->get_child_categories($album_id);

        if (is_wp_error($children)) {
            return $children;
        }

        foreach ($children as $child) {
            $child_id = absint($child['id'] ?? 0);

            if ($child_id <= 0) {
                continue;
            }

            $child_images = $this->get_images_from_album_recursive($child_id, 0, $depth - 1);

            if (is_wp_error($child_images)) {
                return $child_images;
            }

            $images = array_merge($images, $child_images);

            if ($max > 0 && count($images) >= $max) {
                return array_slice($images, 0, $max);
            }
        }

        return $max > 0 ? array_slice($images, 0, $max) : $images;
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

        return $data;
    }
}
