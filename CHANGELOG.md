<?php

if (!defined('ABSPATH')) {
    exit;
    public function get_child_categories(int $album_id)
    {
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d’album invalide.', 'wp-piwigo-display'));
        }

        $response = wp_remote_post($this->base_url . '/ws.php?format=json', [
            'timeout' => 15,
            'body' => [
                'method' => 'pwg.categories.getList',
                'cat_id' => $album_id,
                'recursive' => false,
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('wpd_http_error', sprintf(__('Erreur HTTP lors de l’appel à Piwigo : %s', 'wp-piwigo-display'), $response->get_error_message()));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error('wpd_http_status', sprintf(__('Piwigo a répondu avec le code HTTP %d.', 'wp-piwigo-display'), $status_code));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('wpd_invalid_json', __('Réponse JSON invalide renvoyée par Piwigo.', 'wp-piwigo-display'));
        }

        if (($data['stat'] ?? '') !== 'ok') {
            return new WP_Error('wpd_api_error', sprintf(__('Erreur API Piwigo : %s', 'wp-piwigo-display'), isset($data['message']) ? (string) $data['message'] : __('erreur inconnue', 'wp-piwigo-display')));
        }

        $categories = $data['result']['categories'] ?? [];

        return is_array($categories) ? $categories : [];
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

}

final class WPD_Api
{
    private string $base_url;

    public function __construct(string $base_url)
    {
        $this->base_url = untrailingslashit($base_url);
    }

    public function get_images_from_album(int $album_id, int $max = 0)
    {
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d\'album invalide.', 'wp-piwigo-display'));
        }

        $response = wp_remote_post($this->base_url . '/ws.php?format=json', [
            'timeout' => 15,
            'body' => [
                'method' => 'pwg.categories.getImages',
                'cat_id' => $album_id,
                'per_page' => $max > 0 ? $max : 500,
                'page' => 0,
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('wpd_http_error', sprintf(__('Erreur HTTP lors de l\'appel à Piwigo : %s', 'wp-piwigo-display'), $response->get_error_message()));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error('wpd_http_status', sprintf(__('Piwigo a répondu avec le code HTTP %d.', 'wp-piwigo-display'), $status_code));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('wpd_invalid_json', __('Réponse JSON invalide renvoyée par Piwigo.', 'wp-piwigo-display'));
        }

        if (($data['stat'] ?? '') !== 'ok') {
            return new WP_Error('wpd_api_error', sprintf(__('Erreur API Piwigo : %s', 'wp-piwigo-display'), isset($data['message']) ? (string) $data['message'] : __('erreur inconnue', 'wp-piwigo-display')));
        }

        $images = $data['result']['images'] ?? [];
        return is_array($images) ? $images : [];
    }
    public function get_child_categories(int $album_id)
    {
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d’album invalide.', 'wp-piwigo-display'));
        }

        $response = wp_remote_post($this->base_url . '/ws.php?format=json', [
            'timeout' => 15,
            'body' => [
                'method' => 'pwg.categories.getList',
                'cat_id' => $album_id,
                'recursive' => false,
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('wpd_http_error', sprintf(__('Erreur HTTP lors de l’appel à Piwigo : %s', 'wp-piwigo-display'), $response->get_error_message()));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error('wpd_http_status', sprintf(__('Piwigo a répondu avec le code HTTP %d.', 'wp-piwigo-display'), $status_code));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('wpd_invalid_json', __('Réponse JSON invalide renvoyée par Piwigo.', 'wp-piwigo-display'));
        }

        if (($data['stat'] ?? '') !== 'ok') {
            return new WP_Error('wpd_api_error', sprintf(__('Erreur API Piwigo : %s', 'wp-piwigo-display'), isset($data['message']) ? (string) $data['message'] : __('erreur inconnue', 'wp-piwigo-display')));
        }

        $categories = $data['result']['categories'] ?? [];

        return is_array($categories) ? $categories : [];
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

}
