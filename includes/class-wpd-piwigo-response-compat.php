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
	 * Maximum response size inspected by the compatibility recovery parser.
	 * Normal JSON responses are not truncated by this filter.
	 */
	private const MAX_RECOVERY_BYTES = 4 * 1024 * 1024;

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
		// Do not transform another plugin's response merely because the URL
		// happens to end in ws.php?format=json. Both Piwigo Display clients
		// explicitly identify themselves in their WordPress HTTP requests.
		if ( is_wp_error( $response ) || ! self::is_piwigo_json_request( $url ) || ! self::is_own_api_request( $parsed_args ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		// A polluted response may be attacker-controlled. Avoid quadratic work
		// and unbounded JSON decoding. Clean larger responses are untouched.
		if ( '' === $body || self::MAX_RECOVERY_BYTES < strlen( $body ) || is_array( json_decode( $body, true ) ) ) {
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
	 * Matches only HTTP requests sent by this plugin's API clients.
	 *
	 * @param array<string, mixed> $parsed_args WordPress HTTP request options.
	 * @return bool Whether the request came from Piwigo Display.
	 */
	private static function is_own_api_request( array $parsed_args ): bool {
		$user_agent = $parsed_args['user-agent'] ?? null;

		return is_string( $user_agent ) && 0 === strpos( $user_agent, 'Piwigo Display/' );
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
		$length  = strlen( $body );
		$start   = -1;
		$depth   = 0;
		$quoted  = false;
		$escaped = false;

		// Scan each input byte once. Decode only complete, disjoint objects.
		// A nested or malformed object cannot trigger repeated full scans.
		for ( $position = 0; $position < $length; ++$position ) {
			$character = $body[ $position ];

			if ( -1 === $start ) {
				if ( '{' === $character ) {
					$start = $position;
					$depth = 1;
				}
				continue;
			}

			if ( $quoted ) {
				if ( $escaped ) {
					$escaped = false;
				} elseif ( '\\' === $character ) {
					$escaped = true;
				} elseif ( '"' === $character ) {
					$quoted = false;
				}
				continue;
			}

			if ( '"' === $character ) {
				$quoted = true;
			} elseif ( '{' === $character ) {
				++$depth;
			} elseif ( '}' === $character ) {
				--$depth;
				if ( 0 === $depth ) {
					$candidate = substr( $body, $start, $position - $start + 1 );
					$decoded   = json_decode( $candidate, true );
					if ( is_array( $decoded ) && in_array( $decoded['stat'] ?? null, array( 'ok', 'fail' ), true ) ) {
						return $candidate;
					}

					$start   = -1;
					$quoted  = false;
					$escaped = false;
				}
			}
		}

		return null;
	}}
