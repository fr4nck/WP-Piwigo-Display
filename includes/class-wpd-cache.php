<?php
/**
 * Cache helpers for Piwigo album data.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Manages request, transient and stale caches for Piwigo album data. */
final class WPD_Cache {
	/** @var array<string, array<int, array<string, mixed>>> */
	private static array $request_cache = array();
	private const LOCK_TTL = 20;

	/** Retrieves album images through the cache layer. */
	public static function get_album_images( int $album_id, int $max = 0, string $piwigo_url = '', bool $recursive = false, int $depth = 10 ) {
		$piwigo_url = '' !== $piwigo_url ? untrailingslashit( $piwigo_url ) : WPD_Settings::get_piwigo_url();
		$context    = WPD_Service_Account::is_configured() ? WPD_Service_Account::get_context_hash() : 'anonymous';
		$cache_key  = self::get_album_cache_key( $album_id, $max, $piwigo_url, $recursive, $depth, $context );
		return self::remember( $cache_key, static function () use ( $album_id, $max, $piwigo_url, $recursive, $depth ) {
			$api = self::create_api( $piwigo_url );
			return $recursive ? $api->get_images_from_album_recursive( $album_id, $max, $depth ) : $api->get_images_from_album( $album_id, $max );
		} );
	}

	/** Retrieves album images matching one or more tags. */
	public static function get_album_images_by_tags( int $album_id, array $tags, string $tag_mode = 'any', string $piwigo_url = '', bool $recursive = false, int $depth = 10 ) {
		$album_images = self::get_album_images( $album_id, 0, $piwigo_url, $recursive, $depth );
		if ( is_wp_error( $album_images ) || empty( $tags ) ) {
			return $album_images;
		}
		$piwigo_url = '' !== $piwigo_url ? untrailingslashit( $piwigo_url ) : WPD_Settings::get_piwigo_url();
		$context    = WPD_Service_Account::is_configured() ? WPD_Service_Account::get_context_hash() : 'anonymous';
		$cache_key  = self::get_album_tag_cache_key( $album_id, $tags, $tag_mode, $piwigo_url, $recursive, $depth, $context );
		return self::remember( $cache_key, static function () use ( $album_images, $tags, $tag_mode, $piwigo_url ) {
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
			return array_values( array_filter( $album_images, static function ( array $image ) use ( $tagged_ids ): bool {
				$image_id = absint( $image['id'] ?? 0 );
				return 0 < $image_id && isset( $tagged_ids[ $image_id ] );
			} ) );
		} );
	}

	/** Clears all plugin album, stale and lock transients. */
	public static function clear_all(): int {
		global $wpdb;
		self::$request_cache = array();
		$deleted = 0;
		$patterns = array( '_transient_wpd_album_', '_transient_timeout_wpd_album_', '_transient_wpd_stale_', '_transient_timeout_wpd_stale_', '_transient_wpd_lock_', '_transient_timeout_wpd_lock_' );
		$values = array_map( static function ( string $pattern ) use ( $wpdb ): string { return $wpdb->esc_like( $pattern ) . '%'; }, $patterns );
		$query = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", $values[0], $values[1], $values[2], $values[3], $values[4], $values[5] );
		$names = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
		foreach ( $names as $name ) {
			if ( str_starts_with( $name, '_transient_timeout_' ) ) {
				$transient = substr( $name, strlen( '_transient_timeout_' ) );
			} elseif ( str_starts_with( $name, '_transient_' ) ) {
				$transient = substr( $name, strlen( '_transient_' ) );
			} else {
				continue;
			}
			if ( delete_transient( $transient ) ) { ++$deleted; }
		}
		return $deleted;
	}

	/** Returns a cached value or loads and stores a fresh value. */
	private static function remember( string $cache_key, callable $loader ) {
		if ( isset( self::$request_cache[ $cache_key ] ) ) {
			WPD_Api_Metrics::cache_hit( 'request-cache' );
			return self::$request_cache[ $cache_key ];
		}
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			WPD_Api_Metrics::cache_hit( 'transient-cache' );
			self::$request_cache[ $cache_key ] = $cached;
			return $cached;
		}
		WPD_Api_Metrics::cache_miss( 'album-cache' );
		$stale_key = self::get_stale_key( $cache_key );
		$stale = get_transient( $stale_key );
		$lock_key = self::get_lock_key( $cache_key );
		$lock_acquired = self::acquire_lock( $lock_key );
		if ( ! $lock_acquired && is_array( $stale ) ) {
			WPD_Api_Metrics::cache_hit( 'stale-cache' );
			self::$request_cache[ $cache_key ] = $stale;
			return $stale;
		}
		try {
			$value = $loader();
			if ( is_wp_error( $value ) ) {
				if ( is_array( $stale ) ) {
					WPD_Api_Metrics::cache_hit( 'stale-fallback' );
					self::$request_cache[ $cache_key ] = $stale;
					return $stale;
				}
				return $value;
			}
			if ( ! is_array( $value ) ) { return $value; }
			$duration = max( 60, WPD_Settings::get_cache_duration() );
			self::$request_cache[ $cache_key ] = $value;
			set_transient( $cache_key, $value, $duration );
			set_transient( $stale_key, $value, max( DAY_IN_SECONDS, $duration * 7 ) );
			return $value;
		} finally {
			if ( $lock_acquired ) { self::release_lock( $lock_key ); }
		}
	}

	/** Acquires a short-lived cache generation lock. */
	private static function acquire_lock( string $lock_key ): bool {
		if ( function_exists( 'wp_cache_add' ) && wp_using_ext_object_cache() ) { return wp_cache_add( $lock_key, 1, 'wp-piwigo-display', self::LOCK_TTL ); }
		$option_name = '_transient_' . $lock_key;
		$now = time();
		if ( add_option( $option_name, (string) $now, '', 'no' ) ) { return true; }
		$existing = (int) get_option( $option_name, 0 );
		if ( 0 < $existing && ( $now - $existing ) > self::LOCK_TTL ) {
			delete_option( $option_name );
			return add_option( $option_name, (string) $now, '', 'no' );
		}
		return false;
	}

	/** Releases a short-lived cache generation lock. */
	private static function release_lock( string $lock_key ): void {
		if ( function_exists( 'wp_cache_delete' ) && wp_using_ext_object_cache() ) {
			wp_cache_delete( $lock_key, 'wp-piwigo-display' );
			return;
		}
		delete_option( '_transient_' . $lock_key );
	}

	/** Creates the API client matching the configured account context. */
	private static function create_api( string $piwigo_url ) {
		return WPD_Service_Account::is_configured() ? new WPD_Service_Api( $piwigo_url ) : new WPD_Api( $piwigo_url );
	}

	/** Builds the stale cache key. */
	private static function get_stale_key( string $cache_key ): string { return 'wpd_stale_' . md5( $cache_key ); }
	/** Builds the lock cache key. */
	private static function get_lock_key( string $cache_key ): string { return 'wpd_lock_' . md5( $cache_key ); }
	/** Builds an album-and-tags cache key. */
	private static function get_album_tag_cache_key( int $album_id, array $tags, string $tag_mode, string $piwigo_url, bool $recursive, int $depth, string $context ): string {
		sort( $tags, SORT_STRING );
		return 'wpd_album_' . md5( $context . '|' . $piwigo_url . '|' . $album_id . '|tags|' . implode( ',', $tags ) . '|' . $tag_mode . '|' . ( $recursive ? '1' : '0' ) . '|' . $depth );
	}
	/** Builds an album cache key. */
	private static function get_album_cache_key( int $album_id, int $max, string $piwigo_url, bool $recursive, int $depth, string $context ): string {
		return 'wpd_album_' . md5( $context . '|' . $piwigo_url . '|' . $album_id . '|' . $max . '|' . ( $recursive ? '1' : '0' ) . '|' . $depth );
	}
}
