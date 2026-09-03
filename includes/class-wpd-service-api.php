<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Client Piwigo authentifié utilisé exclusivement côté serveur.
 *
 * La session Piwigo n'est jamais persistée : les cookies restent dans cette
 * instance PHP et disparaissent à la fin de la requête WordPress.
 */
final class WPD_Service_Api
{
    private string $base_url;

    /** @var WP_Http_Cookie[] */
    private array $cookies = [];

    private bool $authenticated = false;

    public function __construct(string $base_url)
    {
        $this->base_url = self::sanitize_service_url($base_url);
    }

    public function test_connection()
    {
        $login = $this->login();
        if (is_wp_error($login)) {
            return $login;
        }

        $status = $this->request(['method' => 'pwg.session.getStatus'], false);
        if (is_wp_error($status)) {
            return $status;
        }

        $username = sanitize_text_field((string) ($status['result']['username'] ?? ''));
        if ($username === '' || strcasecmp($username, 'guest') === 0) {
            return new WP_Error(
                'wpd_service_guest_session',
                __('Piwigo a ouvert une session invitée au lieu du compte de service.', 'wp-piwigo-display')
            );
        }

        return $status;
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
                $this->add_unique_image($images, $image);

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
        if ($album_id <= 0) {
            return new WP_Error('wpd_invalid_album', __('Identifiant d\'album invalide.', 'wp-piwigo-display'));
        }

        if ($depth <= 0) {
            return $this->get_images_from_album($album_id, $max, false);
        }

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
                $this->add_unique_image($images, $image);

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

        $images = [];
        $page = 0;
        $per_page = 500;

        do {
            $response = $this->request([
                'method' => 'pwg.tags.getImages',
                'tag_name' => array_values(array_map('sanitize_text_field', $tags)),
                'tag_mode_and' => $tag_mode === 'all' ? 'true' : 'false',
                'per_page' => $per_page,
                'page' => $page,
            ]);

            if (is_wp_error($response)) {
                return $response;
            }

            $page_images = $response['result']['images'] ?? [];
            $page_images = is_array($page_images) ? $page_images : [];

            foreach ($page_images as $image) {
                $this->add_unique_image($images, $image);
            }

            $page++;
        } while (count($page_images) === $per_page && $page < 1000);

        return array_values($images);
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
            return new WP_Error(
                'wpd_service_login_failed',
                __('Échec de l’authentification du compte de service Piwigo.', 'wp-piwigo-display')
            );
        }

        if (empty($this->cookies)) {
            return new WP_Error(
                'wpd_service_cookie_missing',
                __('Piwigo n’a pas fourni de cookie de session au compte de service.', 'wp-piwigo-display')
            );
        }

        $this->authenticated = true;

        return true;
    }

    private function request(array $body, bool $authenticate = true)
    {
        if ($this->base_url === '') {
            return new WP_Error(
                'wpd_service_https_required',
                __('Le compte de service exige une URL Piwigo HTTPS valide.', 'wp-piwigo-display')
            );
        }

        if ($authenticate) {
            $login = $this->login();
            if (is_wp_error($login)) {
                return $login;
            }
        }

        $response = wp_safe_remote_post($this->base_url . '/ws.php?format=json', [
            'timeout' => 10,
            'redirection' => 0,
            'user-agent' => 'WP Piwigo Display/' . WPD_VERSION,
            'body' => $body,
            'cookies' => $this->cookies,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error(
                'wpd_http_error',
                sprintf(__('Impossible de contacter la galerie Piwigo : %s', 'wp-piwigo-display'), $response->get_error_message())
            );
        }

        $this->merge_response_cookies(wp_remote_retrieve_cookies($response));

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
                sprintf(
                    __('Erreur renvoyée par Piwigo : %s', 'wp-piwigo-display'),
                    sanitize_text_field((string) ($data['message'] ?? __('erreur inconnue', 'wp-piwigo-display')))
                )
            );
        }

        return $data;
    }

    /**
     * @param array<string, array> $images
     * @param array               $image
     */
    private function add_unique_image(array &$images, array $image): void
    {
        $id = absint($image['id'] ?? 0);
        $key = $id > 0 ? (string) $id : md5(wp_json_encode($image));
        $images[$key] = $image;
    }

    /**
     * Évite d'accumuler plusieurs cookies portant le même nom.
     *
     * @param WP_Http_Cookie[] $cookies
     */
    private function merge_response_cookies(array $cookies): void
    {
        foreach ($cookies as $cookie) {
            if (!$cookie instanceof WP_Http_Cookie || $cookie->name === '') {
                continue;
            }

            $this->cookies[$cookie->name] = $cookie;
        }
    }

    private static function sanitize_service_url(string $base_url): string
    {
        $url = untrailingslashit(esc_url_raw(trim($base_url)));
        if ($url === '' || wp_parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return '';
        }

        return $url;
    }
}
