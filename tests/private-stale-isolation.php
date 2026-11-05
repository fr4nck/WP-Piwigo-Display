<?php
/**
 * Executable isolation checks for private-album stale-cache fallback.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);
define( 'ABSPATH', __DIR__ . '/../' );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['wpd_test_transients'] = array();
$GLOBALS['wpd_test_reads'] = array();
function get_transient( string $key ) {
	$GLOBALS['wpd_test_reads'][] = $key;
	return $GLOBALS['wpd_test_transients'][ $key ] ?? false;
}
function set_transient( string $key, $value, int $ttl ): bool {
	$GLOBALS['wpd_test_transients'][ $key ] = $value;
	return true;
}
function add_option( string $key, $value, string $deprecated = '', string $autoload = '' ): bool { return true; }
function delete_option( string $key ): bool { return true; }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
final class WP_Error {}
final class WPD_Api_Metrics {
	public static function cache_hit( string $type ): void {}
	public static function cache_miss( string $type ): void {}
}
final class WPD_Settings {
	public static function get_cache_duration(): int { return 300; }
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-cache.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};
$remember = new ReflectionMethod( WPD_Cache::class, 'remember' );
$remember->setAccessible( true );

$private_key = 'wpd_album_private_no_stale';
$private_stale = 'wpd_stale_' . md5( $private_key );
$GLOBALS['wpd_test_transients'][ $private_stale ] = array( array( 'id' => 42 ) );
$result = $remember->invoke( null, $private_key, static fn() => new WP_Error(), false );
$assert( $result instanceof WP_Error, 'Une erreur du compte de service ne doit jamais retourner une galerie périmée.' );
$assert( ! in_array( $private_stale, $GLOBALS['wpd_test_reads'], true ), 'La copie privée périmée ne doit même pas être lue.' );

$public_key = 'wpd_album_public_stale_ok';
$public_stale = 'wpd_stale_' . md5( $public_key );
$GLOBALS['wpd_test_transients'][ $public_stale ] = array( array( 'id' => 53 ) );
$result = $remember->invoke( null, $public_key, static fn() => new WP_Error(), true );
$assert( 53 === ( $result[0]['id'] ?? null ), 'La galerie publique doit conserver le repli stale.' );

$private_success = 'wpd_album_private_success';
$private_success_stale = 'wpd_stale_' . md5( $private_success );
$result = $remember->invoke( null, $private_success, static fn() => array( array( 'id' => 61 ) ), false );
$assert( 61 === ( $result[0]['id'] ?? null ), 'Une galerie privée autorisée doit rester lisible.' );
$assert( ! isset( $GLOBALS['wpd_test_transients'][ $private_success_stale ] ), 'Le cache privé ne doit créer aucune copie stale longue durée.' );

echo "Private stale cache isolation: OK\n";