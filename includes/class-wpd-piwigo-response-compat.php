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
	 * Registers the HTTP response filter.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'http_response', array( self::class, 'clean_response' ), 10, 3 );
	}

	/**
	 * Removes accidental output surrounding a valid Piwigo JSON response.
	 *
	 * Clean responses are returned untouched. Recovery is deliberately limited
	 * to Piwigo ws.php JSON requests and only succeeds when a complete JSON
	 * object containing the Piwigo `stat` member can be isolated.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @param array          $parsed_args HTTP request arguments.
	 * @param string         $url Requested URL.
	 * @return array|WP_Error
	 */
	public static function clean_response( $response, array $parsed_args, string $url ) {
		unset( $parsed_args );

		if ( is_wp_error( $response ) || ! self::is_piwigo_json_request( $url ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body || is_array( json_decode( $body, true ) ) ) {
			return $response;
		}

		$json = self::extract_piwigo_json( $body );
		if ( null === $json ) {
			return $response;
		}

		$response['body'] = $json;
		return $response;
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

						if ( is_array( $decoded ) && isset( $decoded['stat'] ) ) {
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
