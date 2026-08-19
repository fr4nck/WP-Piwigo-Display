<?php
/**
 * Regression checks for album identifiers, names and human-readable paths.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'WPD_VERSION' ) ) {
	define( 'WPD_VERSION', 'test' );
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct( public string $code = '', public string $message = '' ) {}
	}
}

if ( ! class_exists( 'WPD_Settings' ) ) {
	final class WPD_Settings {
		public static string $url = 'https://photos.example.test';

		public static function get_piwigo_url(): string {
			return self::$url;
		}
	}
}

if ( ! class_exists( 'WPD_Service_Account' ) ) {
	final class WPD_Service_Account {
		public static bool $configured = false;

		public static function is_configured(): bool {
			return self::$configured;
		}
	}
}

if ( ! class_exists( 'WPD_Service_Api' ) ) {
	final class WPD_Service_Api {
		/** @var array<int,array<string,mixed>> */
		public static array $categories = array();

		public function __construct( public string $url ) {}

		/** @return array<int,array<string,mixed>> */
		public function get_all_categories(): array {
			return self::$categories;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ): bool {
		return $value instanceof WP_Error;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = '' ): string {
		unset( $domain );
		return $text;
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $value ): int {
		return abs( (int) $value );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ): string {
		return trim( strip_tags( (string) $value ) );
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url, array $protocols = array() ): string {
		unset( $protocols );
		return $url;
	}
}
if ( ! function_exists( 'wp_http_validate_url' ) ) {
	function wp_http_validate_url( string $url ): bool {
		return false !== filter_var( $url, FILTER_VALIDATE_URL );
	}
}
if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( string $value ): string {
		return rtrim( $value, '/\\' );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ): string {
		return (string) json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-api.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$base_url   = 'https://photos.example.test';
$categories = array(
	array( 'id' => 10, 'name' => 'ALSH', 'uppercats' => '10', 'permalink' => 'alsh' ),
	array( 'id' => 20, 'name' => 'Été 2026', 'uppercats' => '10,20', 'permalink' => 'ete-2026' ),
	array( 'id' => 30, 'name' => 'Séjour voile', 'uppercats' => '10,20,30', 'permalink' => 'sejour-voile' ),
	array( 'id' => 40, 'name' => 'Séjour voile', 'uppercats' => '10,40', 'permalink' => 'autre-sejour-voile' ),
);

$body = array(
	'method'    => 'pwg.categories.getList',
	'recursive' => true,
);
ksort( $body );
$key = md5( $base_url . '|' . wp_json_encode( $body ) );

$property = new ReflectionProperty( WPD_Api::class, 'request_cache' );
$property->setAccessible( true );
$property->setValue(
	null,
	array(
		$key => array(
			'stat'   => 'ok',
			'result' => array( 'categories' => $categories ),
		),
	)
);

$api = new WPD_Api( $base_url );

$assert( 30 === $api->resolve_album_id( '30' ), 'A numeric album identifier must be returned unchanged.' );
$assert( 20 === $api->resolve_album_id( 'Été 2026' ), 'An album name must still resolve.' );
$assert( 30 === $api->resolve_album_id( '/ALSH/Été 2026/Séjour voile' ), 'A full human-readable album path must resolve to the intended nested album.' );
$assert( 40 === $api->resolve_album_id( 'ALSH/Séjour voile' ), 'Human-readable paths must disambiguate duplicate album names.' );
$assert( 30 === $api->resolve_album_id( 'sejour-voile' ), 'Historical permalink lookup must remain supported.' );

$private_categories = array(
	array( 'id' => 50, 'name' => 'Privé', 'uppercats' => '50', 'permalink' => 'prive' ),
	array( 'id' => 60, 'name' => 'Équipe', 'uppercats' => '50,60', 'permalink' => 'equipe' ),
);
WPD_Service_Account::$configured = true;
WPD_Service_Api::$categories     = $private_categories;
$assert( 60 === $api->resolve_album_id( '/Privé/Équipe' ), 'A private album path must resolve through the service account on the configured Piwigo URL.' );

$other_url = 'https://other.example.test';
$other_body = array(
	'method'    => 'pwg.categories.getList',
	'recursive' => true,
);
ksort( $other_body );
$other_key = md5( $other_url . '|' . wp_json_encode( $other_body ) );
$property->setValue(
	null,
	array(
		$other_key => array(
			'stat'   => 'ok',
			'result' => array(
				'categories' => array(
					array( 'id' => 70, 'name' => 'Public externe', 'uppercats' => '70', 'permalink' => 'public-externe' ),
				),
			),
		),
	)
);
$other_api = new WPD_Api( $other_url );
$assert( 70 === $other_api->resolve_album_id( 'Public externe' ), 'A custom Piwigo URL must keep anonymous album resolution.' );
$assert( is_wp_error( $other_api->resolve_album_id( '/Privé/Équipe' ) ), 'Private configured categories must never leak into a custom Piwigo URL lookup.' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-api.php' );
$assert( false !== strpos( (string) $source, 'build_category_path' ), 'Path resolution must be derived from the Piwigo uppercats hierarchy.' );
$assert( false !== strpos( (string) $source, 'get_categories_for_resolution' ), 'Album resolution must select the appropriate anonymous or service-account category source.' );
$assert( false === strpos( (string) $source, "array( 'uppercats', 'global_rank', 'permalink' )" ), 'Numeric uppercats/global_rank values must not be mistaken for human-readable paths.' );

echo "Album identifier/name/path resolution, including private albums: OK\n";
