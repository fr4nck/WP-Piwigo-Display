<?php
/**
 * Cache helpers for Piwigo album data.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages request, transient and stale caches for Piwigo album data.
 */
final class WPD_Cache {
	/**
	 * Values already loaded during the current request.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $request_cache = array();

	/**
	 * Cache lock lifetime in seconds.
	 */
	private const LOCK_TTL = 20;

	/**
	 * Resolves an album identifier using the same access context as rendering.
	 *
	 * This matters for private albums: names and paths must be resolved with the
	 * service account when the configured Piwigo URL is used, while a custom URL
	 * must remain anonymous.
	 *
	 * @param string $album      Album name, path or numeric identifier.
	 * @param string $piwigo_url Optional Piwigo base URL.
	 * @return int|WP_Error
	 */
	public static function resolve_album_id( string $album, string $piwigo_url = '' ) {
		$album = sanitize_text_field( $album );
		if ( '' === $album ) {
			return new WP_Error( 'wpd_empty_album', __( 'Album non renseigné.', 'wp-piwigo-display' ) );
		}

		if ( ctype_digit( $album ) ) {
			return absint( $album );
		}

		$piwigo_url = '' !== $piwigo_url ? untrailingslashit( $piwigo_url ) : WPD_Settings::get_piwigo_url();
		$api        = self::create_api( $piwigo_url );
		$categories = $api->get_all_categories();
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		return self::resolve_album_from_categories( $album, $categories );
	}

	/**
	 * Retrieves album images through the cache layer.
	 *
	 * @param int    $album_id   Piwigo album identifier.
	 * @param int    $max        Maximum number of images. Zero means unlimited.
	 * @param string $piwigo_url Optional Piwigo base URL.
	 * @param bool   $recursive  Whether to include child albums.
	 * @param int    $depth      Maximum recursion depth.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public static function get_album_images( int $album_id, int $max = 0, string $piwigo_url = '', bool $recursive = false, int $depth = 10 ) {
		$piwigo_url = '' !== $piwigo_url ? untrailingslashit( $piwigo_url ) : WPD_Settings::get_piwigo_url();
		$context    = self::get_access_context( $piwigo_url );
		$cache_key  = self::get_album_cache_key( $album_id, $max, $piwigo_url, $recursive, $depth, $context );

		return self::remember(
			$cache_key,
			static function () use ( $album_id, $max, $piwigo_url, $recursive, $depth ) {
				$api = self::create_api( $piwigo_url );

				return $recursive
					? $api->get_images_from_album_recursive( $album_id, $max, $depth )
					: $api->get_images_from_album( $album_id, $max );
			}
		);
	}

	/**
	 * Retrieves album images matching one or more tags.
	 *
	 * @param int                $album_id   Piwigo album identifier.
	 * @param array<int, string> $tags       Requested tag names.
	 * @param string             $tag_mode   Tag matching mode.
	 * @param string             $piwigo_url Optional Piwigo base URL.
	 * @param bool               $recursive  Whether to include child albums.
	 * @param int                $depth      Maximum recursion depth.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public static function get_album_images_by_tags( int $album_id, array $tags, string $tag_mode = 'any', string $piwigo_url = '', bool $recursive = false, int $depth = 10 ) {
		$album_images = self::get_album_images( $album_id, 0, $piwigo_url, $recursive, $depth );
		if ( is_wp_error( $album_images ) || empty( $tags ) ) {
			return $album_images;
		}

		$piwigo_url = '' !== $piwigo_url ? untrailingslashit( $piwigo_url ) : WPD_Settings::get_piwigo_url();
		$context    = self::get_access_context( $piwigo_url );
		$cache_key  = self::get_album_tag_cache_key( $album_id, $tags, $tag_mode, $piwigo_url, $recursive, $depth, $context );

		return self::remember(
			$cache_key,
			static function () use ( $album_images, $tags, $tag_mode, $piwigo_url ) {
				$api           = self::create_api( $piwigo_url );
				$tagged_images = $api->get_images_by_tags( $tags, $tag_mode );
				if ( is_wp_error( $tagged_images ) ) {
					return $tagged_images;
				}

				$tagged_ids = array();
				foreach ( $tagged_images as $image ) {
					$image_id = absint( $image['id'] ?? 0 );
					if ( 0 < $image_id ) {
						$tagged_ids[ $image_id ] = true;
					}
				}

				return array_values(
					array_filter(
						$album_images,
						static function ( array $image ) use ( $tagged_ids ): bool {
							$image_id = absint( $image['id'] ?? 0 );

							return 0 < $image_id && isset( $tagged_ids[ $image_id ] );
						}
					)
				);
			}
		);
	}

	/**
	 * Clears all plugin album, stale and lock transients.
	 *
	 * @return int Number of deleted transients.
	 */
	public static function clear_all(): int {
		global $wpdb;

		self::$request_cache = array();
		$deleted             = 0;
		$patterns            = array(
			'_transient_wpd_album_',
			'_transient_timeout_wpd_album_',
			'_transient_wpd_stale_',
			'_transient_timeout_wpd_stale_',
			'_transient_wpd_lock_',
			'_transient_timeout_wpd_lock_',
		);
		$values              = array_map(
			static function ( string $pattern ) use ( $wpdb ): string {
				return $wpdb->esc_like( $pattern ) . '%';
			},
			$patterns
		);

		$query = $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s
			OR option_name LIKE %s
			OR option_name LIKE %s
			OR option_name LIKE %s
			OR option_name LIKE %s",
			$values[0],
			$values[1],
			$values[2],
			$values[3],
			$values[4],
			$values[5]
		);

		$names = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.

		foreach ( $names as $name ) {
			if ( str_starts_with( $name, '_transient_timeout_' ) ) {
				$transient = substr( $name, strlen( '_transient_timeout_' ) );
			} elseif ( str_starts_with( $name, '_transient_' ) ) {
				$transient = substr( $name, strlen( '_transient_' ) );
			} else {
				continue;
			}

			if ( delete_transient( $transient ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Returns a cached value or loads and stores a fresh value.
	 *
	 * @param string   $cache_key Cache key.
	 * @param callable $loader    Value loader.
	 * @return array<int, array<string, mixed>>|WP_Error|mixed
	 */
	private static function remember( string $cache_key, callable $loader ) {
		if ( isset( self::$request_cache[ $cache_key ] ) ) {
			WPD_Api_Metrics::cache_hit( 'request' );
			return self::$request_cache[ $cache_key ];
		}

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			WPD_Api_Metrics::cache_hit( 'transient' );
			self::$request_cache[ $cache_key ] = $cached;

			return $cached;
		}

		$stale_key     = self::get_stale_key( $cache_key );
		$stale         = get_transient( $stale_key );
		$lock_key      = self::get_lock_key( $cache_key );
		$lock_acquired = self::acquire_lock( $lock_key );

		if ( ! $lock_acquired && is_array( $stale ) ) {
			WPD_Api_Metrics::cache_hit( 'stale' );
			self::$request_cache[ $cache_key ] = $stale;

			return $stale;
		}

		WPD_Api_Metrics::cache_miss( 'loader' );

		try {
			$value = $loader();
			if ( is_wp_error( $value ) ) {
				if ( is_array( $stale ) ) {
					WPD_Api_Metrics::cache_hit( 'stale' );
					self::$request_cache[ $cache_key ] = $stale;

					return $stale;
				}

				return $value;
			}

			if ( ! is_array( $value ) ) {
				return $value;
			}

			$duration                          = max( 60, WPD_Settings::get_cache_duration() );
			self::$request_cache[ $cache_key ] = $value;
			set_transient( $cache_key, $value, $duration );
			set_transient( $stale_key, $value, max( DAY_IN_SECONDS, $duration * 7 ) );

			return $value;
		} finally {
			if ( $lock_acquired ) {
				self::release_lock( $lock_key );
			}
		}
	}

	/**
	 * Acquires a short-lived cache generation lock.
	 *
	 * @param string $lock_key Lock key.
	 * @return bool Whether the lock was acquired.
	 */
	private static function acquire_lock( string $lock_key ): bool {
		if ( function_exists( 'wp_cache_add' ) && wp_using_ext_object_cache() ) {
			return wp_cache_add( $lock_key, 1, 'wp-piwigo-display', self::LOCK_TTL );
		}

		$option_name = '_transient_' . $lock_key;
		$now         = time();
		if ( add_option( $option_name, (string) $now, '', 'no' ) ) {
			return true;
		}

		$existing = (int) get_option( $option_name, 0 );
		if ( 0 < $existing && ( $now - $existing ) > self::LOCK_TTL ) {
			delete_option( $option_name );

			return add_option( $option_name, (string) $now, '', 'no' );
		}

		return false;
	}

	/**
	 * Releases a cache generation lock.
	 *
	 * @param string $lock_key Lock key.
	 * @return void
	 */
	private static function release_lock( string $lock_key ): void {
		if ( function_exists( 'wp_cache_delete' ) && wp_using_ext_object_cache() ) {
			wp_cache_delete( $lock_key, 'wp-piwigo-display' );

			return;
		}

		delete_option( '_transient_' . $lock_key );
	}

	/**
	 * Creates the API client matching the configured account context.
	 *
	 * Service-account credentials are only valid for the configured Piwigo URL.
	 * A shortcode-specific URL always uses the anonymous client, preventing the
	 * configured credentials from ever being sent to another host.
	 *
	 * @param string $piwigo_url Piwigo base URL.
	 * @return WPD_Api|WPD_Service_Api
	 */
	private static function create_api( string $piwigo_url ) {
		return self::should_use_service_account( $piwigo_url )
			? new WPD_Service_Api( $piwigo_url )
			: new WPD_Api( $piwigo_url );
	}

	/**
	 * Returns the cache/access context for one Piwigo URL.
	 *
	 * @param string $piwigo_url Piwigo base URL.
	 * @return string
	 */
	private static function get_access_context( string $piwigo_url ): string {
		return self::should_use_service_account( $piwigo_url )
			? WPD_Service_Account::get_context_hash()
			: 'anonymous';
	}

	/**
	 * Checks whether the configured service account may be used for this URL.
	 *
	 * @param string $piwigo_url Piwigo base URL.
	 * @return bool
	 */
	private static function should_use_service_account( string $piwigo_url ): bool {
		if ( ! WPD_Service_Account::is_configured() ) {
			return false;
		}

		$requested_url  = untrailingslashit( trim( $piwigo_url ) );
		$configured_url = untrailingslashit( trim( WPD_Settings::get_piwigo_url() ) );

		return '' !== $configured_url && $requested_url === $configured_url;
	}

	/**
	 * Resolves a non-numeric album against visible Piwigo categories.
	 *
	 * @param string                   $album      Requested album name or path.
	 * @param array<int, array<mixed>> $categories Visible Piwigo categories.
	 * @return int|WP_Error
	 */
	private static function resolve_album_from_categories( string $album, array $categories ) {
		$names = array();
		foreach ( $categories as $category ) {
			if ( ! is_array( $category ) ) {
				continue;
			}
			$id = absint( $category['id'] ?? 0 );
			if ( 0 < $id ) {
				$names[ $id ] = sanitize_text_field( (string) ( $category['name'] ?? '' ) );
			}
		}

		$wanted_path = trim( $album, '/' );
		foreach ( $categories as $category ) {
			if ( ! is_array( $category ) ) {
				continue;
			}

			$id   = absint( $category['id'] ?? 0 );
			$name = $names[ $id ] ?? '';
			if ( 0 >= $id ) {
				continue;
			}

			if ( 0 === strcasecmp( $name, $album ) ) {
				return $id;
			}

			$category_path = self::build_category_path( $category, $names );
			if ( '' !== $category_path && 0 === strcasecmp( $category_path, $wanted_path ) ) {
				return $id;
			}

			$permalink = sanitize_text_field( (string) ( $category['permalink'] ?? '' ) );
			if ( '' !== $permalink && 0 === strcasecmp( trim( $permalink, '/' ), $wanted_path ) ) {
				return $id;
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
	 * Builds a human-readable category path from Piwigo's uppercats chain.
	 *
	 * @param array<string, mixed> $category Category data.
	 * @param array<int, string>   $names    Category names indexed by identifier.
	 * @return string
	 */
	private static function build_category_path( array $category, array $names ): string {
		$id       = absint( $category['id'] ?? 0 );
		$uppercat = (string) ( $category['uppercats'] ?? $id );
		$path_ids = array_values( array_filter( array_map( 'absint', explode( ',', $uppercat ) ) ) );

		if ( empty( $path_ids ) && 0 < $id ) {
			$path_ids = array( $id );
		}

		$path_names = array();
		foreach ( $path_ids as $path_id ) {
			if ( isset( $names[ $path_id ] ) && '' !== $names[ $path_id ] ) {
				$path_names[] = $names[ $path_id ];
			}
		}

		return implode( '/', $path_names );
	}

	/**
	 * Builds the stale cache key.
	 *
	 * @param string $cache_key Primary cache key.
	 * @return string
	 */
	private static function get_stale_key( string $cache_key ): string {
		return 'wpd_stale_' . md5( $cache_key );
	}

	/**
	 * Builds the lock cache key.
	 *
	 * @param string $cache_key Primary cache key.
	 * @return string
	 */
	private static function get_lock_key( string $cache_key ): string {
		return 'wpd_lock_' . md5( $cache_key );
	}

	/**
	 * Builds an album-and-tags cache key.
	 *
	 * @param int                $album_id   Piwigo album identifier.
	 * @param array<int, string> $tags       Tag names.
	 * @param string             $tag_mode   Tag matching mode.
	 * @param string             $piwigo_url Piwigo base URL.
	 * @param bool               $recursive  Whether child albums are included.
	 * @param int                $depth      Maximum recursion depth.
	 * @param string             $context    Authentication context hash.
	 * @return string
	 */
	private static function get_album_tag_cache_key( int $album_id, array $tags, string $tag_mode, string $piwigo_url, bool $recursive, int $depth, string $context ): string {
		sort( $tags, SORT_STRING );

		return 'wpd_album_' . md5( $context . '|' . $piwigo_url . '|' . $album_id . '|tags|' . implode( ',', $tags ) . '|' . $tag_mode . '|' . ( $recursive ? '1' : '0' ) . '|' . $depth );
	}

	/**
	 * Builds an album cache key.
	 *
	 * @param int    $album_id   Piwigo album identifier.
	 * @param int    $max        Maximum image count.
	 * @param string $piwigo_url Piwigo base URL.
	 * @param bool   $recursive  Whether child albums are included.
	 * @param int    $depth      Maximum recursion depth.
	 * @param string $context    Authentication context hash.
	 * @return string
	 */
	private static function get_album_cache_key( int $album_id, int $max, string $piwigo_url, bool $recursive, int $depth, string $context ): string {
		return 'wpd_album_' . md5( $context . '|' . $piwigo_url . '|' . $album_id . '|' . $max . '|' . ( $recursive ? '1' : '0' ) . '|' . $depth );
	}
}
