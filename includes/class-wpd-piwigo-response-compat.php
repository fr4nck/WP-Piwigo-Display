<?php
/**
 * Compatibility layer for Piwigo web-service responses.
 *
 * Some Piwigo extensions can accidentally print HTML or JavaScript around the
 * JSON returned by ws.php. OpenStreetMap has been observed doing this for
 * geolocated albums, which makes an otherwise valid API response impossible to
 * decode on the WordPress side.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps Piwigo JSON API responses usable when a Piwigo plugin adds output.
 */
final class WPD_Piwigo_Response_Compat {
	/**
	 * Registers the narrowly scoped HTTP response filter.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'http_response', array( self::class, 'clean_response' ), 10, 3 );
	}

	/**
	 * Removes accidental output surrounding a valid Piwigo JSON response.
	 *
	 * The recovery path is deliberately restricted to requests emitted by
	 * Piwigo Display itself, identified by its private HTTP user-agent, and to
	 * Piwigo's ws.php JSON endpoint. Other WordPress HTTP traffic is returned
	 * byte-for-byte unchanged.
	 *
	 * @param array|WP_Error $response    HTTP response.
	 * @param array          $parsed_args HTTP request arguments.
	 * @param string         $url         Requested URL.
	 * @return array|WP_Error
	 */
	public static function clean_response( $response, array $parsed_args, string $url ) {
		if (
			is_wp_error( $response )
			|| ! self::is_piwigo_display_request( $parsed_args )
			|| ! self::is_piwigo_json_request( $url )
		) {
			return $response;
		}

		$body       = wp_remote_retrieve_body( $response );
		$normalized = self::normalize_body( $body );
		if ( $normalized === $body ) {
			return $response;
		}

		$response['body'] = $normalized;
		return $response;
	}

	/**
	 * Normalizes one Piwigo response body.
	 *
	 * Clean JSON is returned untouched. Recovery only succeeds when a complete
	 * JSON object containing a valid Piwigo `stat` value can be isolated.
	 *
	 * @param string $body Raw HTTP response body.
	 * @return string
	 */
	public static function normalize_body( string $body ): string {
		if ( '' === $body || is_array( json_decode( $body, true ) ) ) {
			return $body;
		}

		$json = self::extract_piwigo_json( $body );
		return null === $json ? $body : $json;
	}

	/**
	 * Checks whether the request was emitted by Piwigo Display.
	 *
	 * @param array $parsed_args WordPress HTTP request arguments.
	 * @return bool
	 */
	private static function is_piwigo_display_request( array $parsed_args ): bool {
		$user_agent = isset( $parsed_args['user-agent'] ) ? (string) $parsed_args['user-agent'] : '';
		return 0 === strpos( $user_agent, 'Piwigo Display/' );
	}

	/**
	 * Checks whether the URL targets Piwigo's JSON web service.
	 *
	 * @param string $url Requested URL.
	 * @return bool
	 */
	private static function is_piwigo_json_request( string $url ): bool {
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );

		if ( 'ws.php' !== basename( $path ) ) {
			return false;
		}

		parse_str( $query, $parameters );
		return isset( $parameters['format'] ) && 'json' === strtolower( (string) $parameters['format'] );
	}

	/**
	 * Extracts the first complete Piwigo JSON object from a polluted body.
	 *
	 * @param string $body Raw HTTP body.
	 * @return string|null
	 */
	private static function extract_piwigo_json( string $body ): ?string {
		$length = strlen( $body );

		for ( $start = 0; $start < $length; ++$start ) {
			if ( '{' !== $body[ $start ] ) {
				continue;
			}

			$depth   = 0;
			$string  = false;
			$escaped = false;

			for ( $position = $start; $position < $length; ++$position ) {
				$character = $body[ $position ];

				if ( $string ) {
					if ( $escaped ) {
						$escaped = false;
					} elseif ( '\\' === $character ) {
						$escaped = true;
					} elseif ( '"' === $character ) {
						$string = false;
					}
					continue;
				}

				if ( '"' === $character ) {
					$string = true;
					continue;
				}

				if ( '{' === $character ) {
					++$depth;
				} elseif ( '}' === $character ) {
					--$depth;
					if ( 0 === $depth ) {
						$candidate = substr( $body, $start, $position - $start + 1 );
						$decoded   = json_decode( $candidate, true );
						$stat      = is_array( $decoded ) ? (string) ( $decoded['stat'] ?? '' ) : '';

						if ( in_array( $stat, array( 'ok', 'fail' ), true ) ) {
							return $candidate;
						}
						break;
					}
				}
			}
		}

		return null;
	}
}
