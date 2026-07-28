<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Client Piwigo authentifié utilisé exclusivement côté serveur.
 */
final class WPD_Service_Api
{
    private string $base_url;
    /** @var WP_Http_Cookie[] */
    private array $cookies = [];
    private bool $authenticated = false;

    public function __construct(string $base_url)
    {
        $this->base_url = untrailingslashit(esc_url_raw($base_url));
    }

    public function test_connection()
    {
        $login = $this->login();
        if (is_wp_error($login)) {
            return $login;
        }

        return $this->request(['method' => 'pwg.session.getStatus'], false);
    }

    public function get_all_categories()
    {
        $response = $this->request([
            'method' => 'pwg.categories.getList',
            'recursive' => 'true',
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $categories = $response['result']['categories'] ?? [];
        return is_array($categories) ? $categories : [];
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
            $page_images = is_array($page_images) ? $page_images : [];

            foreach ($page_images as $image) {
                $id = absint($image['id'] ?? 0);
                $key = $id > 0 ? (string) $id : md5(wp_json_encode($image));
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
        if ($depth >= 10) {
            return $this->get_images_from_album($album_id, $max, true);
        }

        $categories = $this->get_all_categories();
        if (is_wp_error($categories)) {
            return $categories;
        }

        $album_ids = [$album_id];
        foreach ($categories as $category) {
            $category_id = absint($category['id'] ?? 0);
            $path = array_values(array_filter(array_map('absint', explode(',', (string) ($category['uppercats'] ?? '')))));
            $root = array_search($album_id, $path, true);
            if ($category_id > 0 && $root !== false) {
                $relative_depth = count($path) - $root - 1;
                if ($relative_depth >= 1 && $relative_depth <= $depth) {
                    $album_ids[] = $category_id;
                }
            }
        }

        $images = [];
        foreach (array_unique($album_ids) as $current_album_id) {
            $current = $this->get_images_from_album((int) $current_album_id, 0, false);
            if (is_wp_error($current)) {
                return $current;
            }
            foreach ($current as $image) {
                $id = absint($image['id'] ?? 0);
                $key = $id > 0 ? (string) $id : md5(wp_json_encode($image));
                $images[$key] = $image;
                if ($max > 0 && count($images) >= $max) {
                    return array_slice(array_values($images), 0, $max);
                }
            }
        }

        return array_values($images);
    }

    public function get_images_by_tags(array $tags, string $tag_mode = 'any')
    {
        if (empty($tags)) {
            return [];
        }

        $response = $this->request([
            'method' => 'pwg.tags.getImages',
            'tag_name' => $tags,
            'tag_mode_and' => $tag_mode === 'all' ? 'true' : 'false',
            'per_page' => 500,
            'page' => 0,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $images = $response['result']['images'] ?? [];
        return is_array($images) ? $images : [];
    }

    private function login()
    {
        if ($this->authenticated) {
            return true;
        }

        if (!WPD_Service_Account::is_configured()) {
            return new WP_Error('wpd_service_not_configured', __('Compte de service Piwigo non configuré.', 'wp-piwigo-display'));
        }

        $response = $this->request([
            'method' => 'pwg.session.login',
            'username' => WPD_Service_Account::get_username(),
            'password' => WPD_Service_Account::get_password(),
        ], false);

        if (is_wp_error($response)) {
            return $response;
        }

        $this->authenticated = true;
        return true;
    }

    private function request(array $body, bool $authenticate = true)
    {
        if ($this->base_url === '') {
            return new WP_Error('wpd_invalid_url', __('URL Piwigo invalide ou non configurée.', 'wp-piwigo-display'));
        }

        if ($authenticate) {
            $login = $this->login();
            if (is_wp_error($login)) {
                return $login;
            }
        }

        $response = wp_remote_post($this->base_url . '/ws.php?format=json', [
            'timeout' => 10,
            'redirection' => 3,
            'user-agent' => 'WP Piwigo Display/' . WPD_VERSION,
            'body' => $body,
            'cookies' => $this->cookies,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('wpd_http_error', sprintf(__('Impossible de contacter la galerie Piwigo : %s', 'wp-piwigo-display'), $response->get_error_message()));
        }

        $this->cookies = array_merge($this->cookies, wp_remote_retrieve_cookies($response));
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error('wpd_http_status', sprintf(__('La galerie Piwigo a répondu avec le code HTTP %d.', 'wp-piwigo-display'), $status_code));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('wpd_invalid_json', __('La galerie Piwigo a renvoyé une réponse illisible.', 'wp-piwigo-display'));
        }

        if (($data['stat'] ?? '') !== 'ok') {
            return new WP_Error('wpd_api_error', sprintf(__('Erreur renvoyée par Piwigo : %s', 'wp-piwigo-display'), sanitize_text_field((string) ($data['message'] ?? __('erreur inconnue', 'wp-piwigo-display')))));
        }

        return $data;
    }
}
