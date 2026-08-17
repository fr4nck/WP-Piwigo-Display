<?php
/**
 * Authenticated server-side Piwigo API client.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles authenticated requests to the configured Piwigo service account.
 *
 * Piwigo session cookies are kept in memory for the current PHP request only.
 */
final class WPD_Service_Api {
	/**
	 * Sanitized Piwigo base URL.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Session cookies returned by Piwigo.
	 *
	 * @var WP_Http_Cookie[]
	 */
	private array $cookies = array();

	/**
	 * Whether the service account has authenticated during this request.
	 *
	 * @var bool
	 */
	private bool $authenticated = false;

	/**
	 * Creates the API client.
	 *
	 * @param string $base_url Configured Piwigo base URL.
	 */
	public function __construct( string $base_url ) {
		$this->base_url = self::sanitize_service_url( $base_url );
	}

	/**
	 * Tests the service-account connection.
	 *
	 * @return array|true|WP_Error Piwigo status response, true, or an error.
	 */
	public function test_connection() {
		$login = $this->login();
		if ( is_wp_error( $login ) ) {
			return $login;
		}

		$status = $this->request( array( 'method' => 'pwg.session.getStatus' ), false );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$username = sanitize_text_field( (string) ( $status['result']['username'] ?? '' ) );
		if ( '' === $username || 0 === strcasecmp( $username, 'guest' ) ) {
			return new WP_Error(
				'wpd_service_guest_session',
				__( 'Piwigo a ouvert une session invitée au lieu du compte de service.', 'wp-piwigo-display' )
			);
		}

		return $status;
	}

	/**
	 * Retrieves every visible Piwigo category.
	 *
	 * @return array|WP_Error Category list or an error.
	 */
	public function get_all_categories() {
		$response = $this->request(
			array(
				'method'    => 'pwg.categories.getList',
				'recursive' => 'true',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$categories = $response['result']['categories'] ?? array();

		return is_array( $categories ) ? $categories : array();
	}

	/**
	 * Retrieves images from one album.
	 *
	 * @param int  $album_id Album identifier.
	 * @param int  $max      Maximum number of images, or zero for all images.
	 * @param bool $recursive Whether Piwigo should include descendants.
	 * @return array|WP_Error Image list or an error.
	 */
	public function get_images_from_album( int $album_id, int $max = 0, bool $recursive = false ) {
		if ( 0 >= $album_id ) {
			return new WP_Error( 'wpd_invalid_album', __( 'Identifiant d\'album invalide.', 'wp-piwigo-display' ) );
		}

		$images   = array();
		$page     = 0;
		$per_page = 500;

		do {
			$response = $this->request(
				array(
					'method'    => 'pwg.categories.getImages',
					'cat_id'    => $album_id,
					'recursive' => $recursive ? 'true' : 'false',
					'per_page'  => $per_page,
					'page'      => $page,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$page_images = $response['result']['images'] ?? array();
			$page_images = is_array( $page_images ) ? $page_images : array();

			foreach ( $page_images as $image ) {
				if ( ! is_array( $image ) ) {
					continue;
				}

				$this->add_unique_image( $images, $image );

				if ( 0 < $max && count( $images ) >= $max ) {
					return array_slice( array_values( $images ), 0, $max );
				}
			}

			++$page;
			$page_image_count = count( $page_images );
		} while ( $per_page === $page_image_count && 1000 > $page );

		return array_values( $images );
	}

	/**
	 * Retrieves images from one album and descendants up to a depth.
	 *
	 * @param int $album_id Album identifier.
	 * @param int $max      Maximum number of images, or zero for all images.
	 * @param int $depth    Descendant depth.
	 * @return array|WP_Error Image list or an error.
	 */
	public function get_images_from_album_recursive( int $album_id, int $max = 0, int $depth = 10 ) {
		if ( 0 >= $album_id ) {
			return new WP_Error( 'wpd_invalid_album', __( 'Identifiant d\'album invalide.', 'wp-piwigo-display' ) );
		}

		if ( 0 >= $depth ) {
			return $this->get_images_from_album( $album_id, $max, false );
		}

		if ( 10 <= $depth ) {
			return $this->get_images_from_album( $album_id, $max, true );
		}

		$categories = $this->get_all_categories();
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		$album_ids = array( $album_id );
		foreach ( $categories as $category ) {
			$category_id = absint( $category['id'] ?? 0 );
			$path        = array_values(
				array_filter(
					array_map( 'absint', explode( ',', (string) ( $category['uppercats'] ?? '' ) ) )
				)
			);
			$root        = array_search( $album_id, $path, true );

			if ( 0 < $category_id && false !== $root ) {
				$relative_depth = count( $path ) - $root - 1;
				if ( 1 <= $relative_depth && $depth >= $relative_depth ) {
					$album_ids[] = $category_id;
				}
			}
		}

		$images = array();
		foreach ( array_unique( $album_ids ) as $current_album_id ) {
			$current = $this->get_images_from_album( (int) $current_album_id, 0, false );
			if ( is_wp_error( $current ) ) {
				return $current;
			}

			foreach ( $current as $image ) {
				$this->add_unique_image( $images, $image );

				if ( 0 < $max && count( $images ) >= $max ) {
					return array_slice( array_values( $images ), 0, $max );
				}
			}
		}

		return array_values( $images );
	}

	/**
	 * Retrieves images matching one or more tags.
	 *
	 * @param array  $tags     Tag names.
	 * @param string $tag_mode Match mode: any or all.
	 * @return array|WP_Error Image list or an error.
	 */
	public function get_images_by_tags( array $tags, string $tag_mode = 'any' ) {
		if ( empty( $tags ) ) {
			return array();
		}

		$images   = array();
		$page     = 0;
		$per_page = 500;
		$tags     = array_values( array_filter( array_map( 'sanitize_text_field', $tags ) ) );

		do {
			$response = $this->request(
				array(
					'method'       => 'pwg.tags.getImages',
					'tag_name'     => $tags,
					'tag_mode_and' => 'all' === $tag_mode ? 'true' : 'false',
					'per_page'     => $per_page,
					'page'         => $page,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$page_images = $response['result']['images'] ?? array();
			$page_images = is_array( $page_images ) ? $page_images : array();

			foreach ( $page_images as $image ) {
				if ( is_array( $image ) ) {
					$this->add_unique_image( $images, $image );
				}
			}

			++$page;
			$page_image_count = count( $page_images );
		} while ( $per_page === $page_image_count && 1000 > $page );

		return array_values( $images );
	}

	/**
	 * Authenticates the configured service account.
	 *
	 * @return true|WP_Error True on success or an error.
	 */
	private function login() {
		if ( $this->authenticated ) {
			return true;
		}

		if ( ! WPD_Service_Account::is_configured() ) {
			return new WP_Error( 'wpd_service_not_configured', __( 'Compte de service Piwigo non configuré.', 'wp-piwigo-display' ) );
		}

		$response = $this->request(
			array(
				'method'   => 'pwg.session.login',
				'username' => WPD_Service_Account::get_username(),
				'password' => WPD_Service_Account::get_password(),
			),
			false
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'wpd_service_login_failed',
				__( 'Échec de l’authentification du compte de service Piwigo.', 'wp-piwigo-display' )
			);
		}

		if ( empty( $this->cookies ) ) {
			return new WP_Error(
				'wpd_service_cookie_missing',
				__( 'Piwigo n’a pas fourni de cookie de session au compte de service.', 'wp-piwigo-display' )
			);
		}

		$this->authenticated = true;

		return true;
	}

	/**
	 * Sends one POST request to the Piwigo API.
	 *
	 * @param array $body         API request body.
	 * @param bool  $authenticate Whether the service account must be logged in first.
	 * @return array|WP_Error Decoded API response or an error.
	 */
	private function request( array $body, bool $authenticate = true ) {
		if ( '' === $this->base_url ) {
			return new WP_Error(
				'wpd_service_https_required',
				__( 'Le compte de service exige une URL Piwigo HTTPS valide.', 'wp-piwigo-display' )
			);
		}

		if ( $authenticate ) {
			$login = $this->login();
			if ( is_wp_error( $login ) ) {
				return $login;
			}
		}

		$response = wp_safe_remote_post(
			$this->base_url . '/ws.php?format=json',
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'user-agent'  => 'WP Piwigo Display/' . WPD_VERSION,
				'body'        => $body,
				'cookies'     => $this->cookies,
				'sslverify'   => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'wpd_http_error',
				sprintf(
					/* translators: %s: WordPress HTTP error message. */
					__( 'Impossible de contacter la galerie Piwigo : %s', 'wp-piwigo-display' ),
					sanitize_text_field( $response->get_error_message() )
				)
			);
		}

		$this->merge_response_cookies( wp_remote_retrieve_cookies( $response ) );

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 > $status_code || 300 <= $status_code ) {
			return new WP_Error(
				'wpd_http_status',
				sprintf(
					/* translators: %d: HTTP status code returned by Piwigo. */
					__( 'La galerie Piwigo a répondu avec le code HTTP %d.', 'wp-piwigo-display' ),
					$status_code
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'wpd_invalid_json', __( 'La galerie Piwigo a renvoyé une réponse illisible.', 'wp-piwigo-display' ) );
		}

		if ( 'ok' !== ( $data['stat'] ?? '' ) ) {
			$message = sanitize_text_field( (string) ( $data['message'] ?? __( 'erreur inconnue', 'wp-piwigo-display' ) ) );

			return new WP_Error(
				'wpd_api_error',
				sprintf(
					/* translators: %s: Error message returned by Piwigo. */
					__( 'Erreur renvoyée par Piwigo : %s', 'wp-piwigo-display' ),
					$message
				)
			);
		}

		return $data;
	}

	/**
	 * Adds one image to a deduplicated collection.
	 *
	 * @param array<string, array> $images Existing image collection.
	 * @param array                $image  Image payload.
	 * @return void
	 */
	private function add_unique_image( array &$images, array $image ): void {
		$id             = absint( $image['id'] ?? 0 );
		$encoded_image  = wp_json_encode( $image );
		$key            = 0 < $id ? (string) $id : md5( (string) $encoded_image );
		$images[ $key ] = $image;
	}

	/**
	 * Merges response cookies without retaining duplicate names.
	 *
	 * @param WP_Http_Cookie[] $cookies Response cookies.
	 * @return void
	 */
	private function merge_response_cookies( array $cookies ): void {
		foreach ( $cookies as $cookie ) {
			if ( ! $cookie instanceof WP_Http_Cookie || '' === $cookie->name ) {
				continue;
			}

			$this->cookies[ $cookie->name ] = $cookie;
		}
	}

	/**
	 * Sanitizes and validates the service URL.
	 *
	 * @param string $base_url Raw configured URL.
	 * @return string Valid HTTPS URL or an empty string.
	 */
	private static function sanitize_service_url( string $base_url ): string {
		$url = untrailingslashit( esc_url_raw( trim( $base_url ), array( 'https' ) ) );
		if ( '' === $url || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		return $url;
	}
}
