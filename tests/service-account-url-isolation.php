<?php
/**
 * Regression checks preventing service-account credential forwarding to custom URLs.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( string $value ): string {
		return rtrim( $value, '/\\' );
	}
}

final class WPD_Settings {
	public static string $url = 'https://photos.example.test';

	public static function get_piwigo_url(): string {
		return self::$url;
	}
}

final class WPD_Service_Account {
	public static bool $configured = true;

	public static function is_configured(): bool {
		return self::$configured;
	}

	public static function get_context_hash(): string {
		return 'service-context';
	}
}

final class WPD_Api {
	public function __construct( public string $url ) {}
}

final class WPD_Service_Api {
	public function __construct( public string $url ) {}
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-cache.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$may_use_service = new ReflectionMethod( WPD_Cache::class, 'should_use_service_account' );
$may_use_service->setAccessible( true );
$get_context = new ReflectionMethod( WPD_Cache::class, 'get_access_context' );
$get_context->setAccessible( true );
$create_api = new ReflectionMethod( WPD_Cache::class, 'create_api' );
$create_api->setAccessible( true );

$assert(
	true === $may_use_service->invoke( null, 'https://photos.example.test/' ),
	'The service account may be used for the exact configured Piwigo URL.'
);
$assert(
	false === $may_use_service->invoke( null, 'https://other.example.test' ),
	'A shortcode-specific Piwigo URL must never receive configured service-account credentials.'
);
$assert(
	'anonymous' === $get_context->invoke( null, 'https://other.example.test' ),
	'A custom Piwigo URL must use an anonymous cache context.'
);
$assert(
	'service-context' === $get_context->invoke( null, 'https://photos.example.test' ),
	'The configured Piwigo URL must keep its isolated service-account cache context.'
);
$assert(
	$create_api->invoke( null, 'https://other.example.test' ) instanceof WPD_Api,
	'A custom URL must instantiate the anonymous API client.'
);
$assert(
	$create_api->invoke( null, 'https://photos.example.test' ) instanceof WPD_Service_Api,
	'The configured URL may instantiate the authenticated service API client.'
);

WPD_Service_Account::$configured = false;
$assert(
	false === $may_use_service->invoke( null, 'https://photos.example.test' ),
	'A disabled or incomplete service account must never select the authenticated client.'
);

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-cache.php' );
$assert( false !== strpos( (string) $source, '$requested_url === $configured_url' ), 'Service-account use must require exact normalized URL equality.' );
$assert( false !== strpos( (string) $source, "? WPD_Service_Account::get_context_hash()" ), 'The service cache context must only be selected after URL authorization.' );

echo "Service-account URL isolation: OK\n";
