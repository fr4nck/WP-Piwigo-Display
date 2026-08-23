<?php
/**
 * Compatibility layer for Piwigo web-service response bodies.
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
 * Keeps Piwigo JSON API response bodies usable when a Piwigo plugin adds output.
 */
final class WPD_Piwigo_Response_Compat {
	/**
	 * Normalizes one response body returned by a Piwigo API client.
	 *
	 * Clean JSON is returned untouched. Recovery only succeeds when a complete
	 * JSON object containing the Piwigo `stat` member can be isolated. The method
	 * is called explicitly by Piwigo Display's own API clients so unrelated
	 * WordPress HTTP traffic is never filtered or rewritten globally.
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
