<?php
/**
 * S-09 regression: service-account secrets are not autoloaded, may be cleared,
 * and never leak into the administrative password input.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['wpd_fake_options'] = array(
	'wp_piwigo_display_service_account' => array(
		'enabled'  => 'true',
		'username' => 'service-user',
		'password' => 'private-test-secret',
	),
);
$GLOBALS['wpd_autoload_calls']   = array();
$GLOBALS['wpd_cache_clear_count'] = 0;
$GLOBALS['wpd_registered_fields'] = array();

function get_option( string $key, $fallback = false ) {
	return $GLOBALS['wpd_fake_options'][ $key ] ?? $fallback;
}
function sanitize_text_field( string $value ): string {
	return trim( $value );
}
function register_setting( string $group, string $option, array $args ): void {
	$GLOBALS['wpd_registered_fields'][ $option ] = $args;
}
function add_settings_section( ...$args ): void {}
function add_settings_field( ...$args ): void {}
function __( string $value, string $domain = '' ): string {
	return $value;
}
function esc_html__( string $value, string $domain = '' ): string {
	return $value;
}
function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function esc_url( string $value ): string {
	return $value;
}
function disabled( bool $status, bool $check, bool $echo ): string {
	return $status === $check ? ' disabled' : '';
}
function wp_nonce_url( string $url, string $nonce ): string {
	return $url . '?' . $nonce;
}
function admin_url( string $path ): string {
	return 'https://wp.example.invalid/wp-admin/' . $path;
}
function wp_set_option_autoload( string $name, bool $autoload ): bool {
	$GLOBALS['wpd_autoload_calls'][] = array( $name, $autoload );
	return true;
}
function add_action( string $name, $callback, int $priority = 10, int $args = 1 ): void {
	$GLOBALS['wpd_actions'][ $name ] = $callback;
}

final class WPD_Cache {
	public static function clear_all(): void {
		++$GLOBALS['wpd_cache_clear_count'];
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-service-account.php';

$fail = static function ( string $message ): never {
	fwrite( STDERR, "S09=FAIL $message\n" );
	exit( 1 );
};

set_error_handler( static function ( int $level, string $message ): never {
	throw new ErrorException( $message, 0, $level );
} );

try {
	WPD_Service_Account::register();
	WPD_Service_Account::register_settings();
	$option = WPD_Service_Account::OPTION_NAME;
	if ( ! isset( $GLOBALS['wpd_registered_fields'][ $option ] ) ) {
		$fail( 'WordPress service-account settings were not registered.' );
	}
	if ( ! in_array( array( $option, false ), $GLOBALS['wpd_autoload_calls'], true ) ) {
		$fail( 'Previously saved secret option was not migrated to autoload=false.' );
	}
	if ( ! isset( $GLOBALS['wpd_actions']['added_option'] )
		|| ! isset( $GLOBALS['wpd_actions']['updated_option'] ) ) {
		$fail( 'New and updated secret options must retain autoload=false.' );
	}

	$kept = WPD_Service_Account::sanitize_options(
		array( 'enabled' => 'true', 'username' => 'service-user', 'password' => '' )
	);
	if ( 'private-test-secret' !== $kept['password'] ) {
		$fail( 'Blank password should preserve the existing secret.' );
	}

	$cleared = WPD_Service_Account::sanitize_options(
		array( 'enabled' => 'true', 'username' => 'service-user', 'password' => '', 'clear_password' => 'true' )
	);
	if ( '' !== $cleared['password'] || isset( $cleared['clear_password'] ) ) {
		$fail( 'Explicit password-clear request was ignored or leaked into settings.' );
	}
	if ( 1 !== $GLOBALS['wpd_cache_clear_count'] ) {
		$fail( 'Clearing credentials must invalidate private caches exactly once.' );
	}

	$disabled_input = WPD_Service_Account::sanitize_options(
		array( 'enabled' => 'true', 'password' => '' )
	);
	if ( 'service-user' !== $disabled_input['username'] ) {
		$fail( 'An omitted disabled username input must retain its saved value.' );
	}

	$replacement = WPD_Service_Account::sanitize_options(
		array( 'enabled' => 'true', 'username' => 'service-user', 'password' => 'new-secret', 'clear_password' => 'true' )
	);
	if ( '' !== $replacement['password'] ) {
		$fail( 'Explicit clear must take precedence over a simultaneous replacement.' );
	}

	$malformed = WPD_Service_Account::sanitize_options(
		array( 'enabled' => array( 'true' ), 'username' => array( 'invalid' ), 'password' => array( 'invalid' ), 'clear_password' => array( 'true' ) )
	);
	if ( 'private-test-secret' !== $malformed['password'] || 'service-user' !== $malformed['username'] ) {
		$fail( 'Malformed credential fields must not replace existing secrets.' );
	}

	ob_start();
	WPD_Service_Account::render_password_field();
	$html = (string) ob_get_clean();
	if ( str_contains( $html, 'private-test-secret' )
		|| ! str_contains( $html, '[clear_password]' )
		|| ! str_contains( $html, 'type="checkbox"' ) ) {
		$fail( 'The admin form does not offer a safe write-only password removal.' );
	}
} catch ( Throwable $exception ) {
	$fail( get_class( $exception ) . ': ' . $exception->getMessage() );
} finally {
	restore_error_handler();
}

echo "S09=PASS non-autoload credentials, explicit deletion, cache invalidation and safe admin form\n";
