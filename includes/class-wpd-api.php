<?php
/**
 * Piwigo API client.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides read-only access to the Piwigo web service.
 */
final class WPD_Api {
	/**
	 * In-memory responses cached during the current PHP request.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $request_cache = array();

	/**
	 * Sanitized Piwigo base URL.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Creates an API client.
	 *
	 * @param string $base_url Piwigo base URL.
	 */
	public function __construct( string $base_url ) {
		$this->base_url = self::sanitize_base_url( $base_url );
	}

	/**
	 * Retrieves images from one album.
	 *
	 * @param int  $album_id Album identifier.
	 * @param int  $max      Maximum number of images, or zero for unlimited.
	 * @param bool $recursive Whether Piwigo should include descendant albums.
	 * @return array<int, array<string, mixed>>|WP_Error
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
			if ( ! is_array( $page_images ) ) {
				$page_images = array();
			}

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
		} while ( count( $page_images ) === $per_page && 1000 > $page );

		return array_values( $images );
	}

	/**
	 * Retrieves images matching tags.
	 *
	 * @param array<int, string> $tags     Tag names.
	 * @param string             $tag_mode Matching mode: any or all.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function get_images_by_tags( array $tags, string $tag_mode = 'any' ) {
		$tags = array_values( array_filter( array_map( 'sanitize_text_field', $tags ) ) );
		if ( empty( $tags ) ) {
			return array();
		}

		$images   = array();
		$page     = 0;
		$per_page = 500;

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
			if ( ! is_array( $page_images ) ) {
				$page_images = array();
			}

			foreach ( $page_images as $image ) {
				if ( is_array( $image ) ) {
					$this->add_unique_image( $images, $image );
				}
			}

			++$page;
		} while ( count( $page_images ) === $per_page && 1000 > $page );

		return array_values( $images );
	}

	/**
	 * Retrieves images from an album and a limited descendant depth.
	 *
	 * @param int $album_id Album identifier.
	 * @param int $max      Maximum number of images, or zero for unlimited.
	 * @param int $depth    Descendant depth.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function get_images_from_album_recursive( int $album_id, int $max = 0, int $depth = 10 ) {
		if ( 0 >= $depth ) {
			return $this->get_images_from_album( $album_id, $max, false );
		}

		if ( 10 <= $depth ) {
			return $this->get_images_from_album( $album_id, $max, true );
		}

		$album_ids = $this->get_descendant_album_ids( $album_id, $depth );
		if ( is_wp_error( $album_ids ) ) {
			return $album_ids;
		}

		$images = array();
		foreach ( $album_ids as $current_album_id ) {
			$album_images = $this->get_images_from_album( $current_album_id, 0, false );
			if ( is_wp_error( $album_images ) ) {
				return $album_images;
			}

			foreach ( $album_images as $image ) {
				$this->add_unique_image( $images, $image );
				if ( 0 < $max && count( $images ) >= $max ) {
					return array_slice( array_values( $images ), 0, $max );
				}
			}
		}

		return array_values( $images );
	}

	/**
	 * Adds an image while preserving the historical deduplication key.
	 *
	 * @param array<string, array<string, mixed>> $images Images indexed by deduplication key.
	 * @param array<string, mixed>                $image  Image data.
	 * @return void
	 */
	private function add_unique_image( array &$images, array $image ): void {
		$image_id = absint( $image['id'] ?? 0 );
		$key      = 0 < $image_id ? (string) $image_id : md5( (string) wp_json_encode( $image ) );

		$images[ $key ] = $image;
	}

	/**
	 * Resolves album identifiers up to a relative depth.
	 *
	 * @param int $album_id Root album identifier.
	 * @param int $depth    Maximum relative depth.
	 * @return array<int, int>|WP_Error
	 */
	private function get_descendant_album_ids( int $album_id, int $depth ) {
		$categories = $this->get_all_categories();
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		$album_ids = array( $album_id );
		foreach ( $categories as $category ) {
			$category_id = absint( $category['id'] ?? 0 );
			$uppercats   = trim( (string) ( $category['uppercats'] ?? '' ) );
			if ( 0 >= $category_id || '' === $uppercats ) {
				continue;
			}

			$path          = array_values( array_filter( array_map( 'absint', explode( ',', $uppercats ) ) ) );
			$root_position = array_search( $album_id, $path, true );
			if ( false === $root_position ) {
				continue;
			}

			$relative_depth = count( $path ) - $root_position - 1;
			if ( 1 <= $relative_depth && $relative_depth <= $depth ) {
				$album_ids[] = $category_id;
			}
		}

		return array_values( array_unique( array_map( 'absint', $album_ids ) ) );
	}

	/**
	 * Retrieves direct child categories.
	 *
	 * @param int $album_id Album identifier.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function get_child_categories( int $album_id ) {
		if ( 0 >= $album_id ) {
			return new WP_Error( 'wpd_invalid_album', __( 'Identifiant d\'album invalide.', 'wp-piwigo-display' ) );
		}

		$response = $this->request(
			array(
				'method'    => 'pwg.categories.getList',
				'cat_id'    => $album_id,
				'recursive' => false,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$categories = $response['result']['categories'] ?? array();
		return is_array( $categories ) ? $categories : array();
	}

	/**
	 * Retrieves all visible categories.
	 *
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function get_all_categories() {
		$response = $this->request(
			array(
				'method'    => 'pwg.categories.getList',
				'recursive' => true,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$categories = $response['result']['categories'] ?? array();
		return is_array( $categories ) ? $categories : array();
	}

	/**
	 * Resolves an album name, path or numeric identifier.
	 *
	 * @param string $album Album name, path or identifier.
	 * @return int|WP_Error
	 */
	public function resolve_album_id( string $album ) {
		$album = sanitize_text_field( $album );
		if ( '' === $album ) {
			return new WP_Error( 'wpd_empty_album', __( 'Album non renseigné.', 'wp-piwigo-display' ) );
		}

		if ( ctype_digit( $album ) ) {
			return absint( $album );
		}

		$categories = $this->get_all_categories();
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		$wanted_path = trim( $album, '/' );
		foreach ( $categories as $category ) {
			$id   = absint( $category['id'] ?? 0 );
			$name = sanitize_text_field( (string) ( $category['name'] ?? '' ) );
			if ( 0 >= $id ) {
				continue;
			}

			if ( 0 === strcasecmp( $name, $album ) ) {
				return $id;
			}

			foreach ( array( 'uppercats', 'global_rank', 'permalink' ) as $key ) {
				if ( isset( $category[ $key ] ) && 0 === strcasecmp( trim( sanitize_text_field( (string) $category[ $key ] ), '/' ), $wanted_path ) ) {
					return $id;
				}
			}
		}

		return new WP_Error(
			'wpd_album_not_found',
			sprintf(
				/* translators: %s: requested Piwigo album name or path. */
				__( 'Album introuvable : %s. Vérifiez le nom, le chemin ou utilisez directement son identifiant Piwigo.', 'wp-piwigo-display' ),
				$album
			)
		);
	}

	/**
	 * Tests the Piwigo connection.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function test_connection() {
		return $this->request( array( 'method' => 'pwg.session.getStatus' ) );
	}

	/**
	 * Sends one request to the Piwigo web service.
	 *
	 * @param array<string, mixed> $body Request parameters.
	 * @return array<string, mixed>|WP_Error
	 */
	private function request( array $body ) {
		if ( '' === $this->base_url ) {
			return new WP_Error( 'wpd_invalid_url', __( 'URL Piwigo invalide ou non configurée.', 'wp-piwigo-display' ) );
		}

		$cache_key = $this->get_request_cache_key( $body );
		if ( isset( self::$request_cache[ $cache_key ] ) ) {
			return self::$request_cache[ $cache_key ];
		}

		$response = wp_safe_remote_post(
			$this->base_url . '/ws.php?format=json',
			array(
				'timeout'     => 10,
				'redirection' => 3,
				'user-agent'  => 'WP Piwigo Display/' . WPD_VERSION,
				'body'        => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'wpd_http_error',
				sprintf(
					/* translators: %s: remote HTTP error message. */
					__( 'Impossible de contacter la galerie Piwigo : %s', 'wp-piwigo-display' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 > $status_code || 300 <= $status_code ) {
			return new WP_Error(
				'wpd_http_status',
				sprintf(
					/* translators: %d: HTTP response status code. */
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
			$message = isset( $data['message'] ) ? sanitize_text_field( (string) $data['message'] ) : __( 'erreur inconnue', 'wp-piwigo-display' );
			return new WP_Error(
				'wpd_api_error',
				sprintf(
					/* translators: %s: Piwigo API error message. */
					__( 'Erreur renvoyée par Piwigo : %s', 'wp-piwigo-display' ),
					$message
				)
			);
		}

		self::$request_cache[ $cache_key ] = $data;
		return $data;
	}

	/**
	 * Builds a request-local cache key.
	 *
	 * @param array<string, mixed> $body Request parameters.
	 * @return string
	 */
	private function get_request_cache_key( array $body ): string {
		ksort( $body );
		return md5( $this->base_url . '|' . (string) wp_json_encode( $body ) );
	}

	/**
	 * Sanitizes and validates a Piwigo base URL.
	 *
	 * @param string $base_url Candidate base URL.
	 * @return string
	 */
	private static function sanitize_base_url( string $base_url ): string {
		$base_url = trim( $base_url );
		if ( '' === $base_url ) {
			return '';
		}

		$url = esc_url_raw( $base_url, array( 'http', 'https' ) );
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		return untrailingslashit( $url );
	}
}
