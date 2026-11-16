<?php
/**
 * S-09 regression for the WordPress 6.0–6.3 autoload migration fallback.
 *
 * Runs in a fresh PHP process without wp_set_option_autoload().
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

final class WPD_Legacy_DB {
	public string $options = 'wp_options';
	public string $autoload = 'yes';
	public int $updates = 0;

	public function prepare( string $query, string $name ): string {
		if ( 'wp_piwigo_display_service_account' !== $name ) {
			throw new UnexpectedValueException( 'Unexpected option passed to legacy SQL.' );
		}
		return str_replace( '%s', "'" . $name . "'", $query );
	}

	public function get_var( string $query ): string {
		if ( ! str_contains( $query, 'WHERE option_name' ) ) {
			throw new UnexpectedValueException( 'Legacy query must target the option name.' );
		}
		return $this->autoload;
	}

	public function update( string $table, array $value, array $where, array $formats, array $where_formats ): int {
		if ( 'wp_options' !== $table
			|| array( 'autoload' => 'no' ) !== $value
			|| array( 'option_name' => 'wp_piwigo_display_service_account' ) !== $where ) {
			throw new UnexpectedValueException( 'Legacy update changed unrelated option columns.' );
		}
		++$this->updates;
		$this->autoload = 'no';
		return 1;
	}
}

$GLOBALS['wpdb'] = new WPD_Legacy_DB();
$GLOBALS['wpd_old_cache_deletes'] = array();

function wp_cache_delete( string $key, string $group ): bool {
	$GLOBALS['wpd_old_cache_deletes'][] = array( $key, $group );
	return true;
}
function register_setting( string $group, string $option, array $args ): void {}
function add_settings_section( ...$args ): void {}
function add_settings_field( ...$args ): void {}
function __( string $value, string $domain = '' ): string {
	return $value;
}
require_once dirname( __DIR__ ) . '/includes/class-wpd-service-account.php';

$assert = static function ( bool $ok, string $reason ): void {
	if ( ! $ok ) {
		fwrite( STDERR, "S09_LEGACY=FAIL $reason\n" );
		exit( 1 );
	}
};

WPD_Service_Account::register_settings();
$assert( 1 === $GLOBALS['wpdb']->updates, 'Legacy WordPress must migrate one existing autoloaded option.' );
$assert( 'no' === $GLOBALS['wpdb']->autoload, 'Legacy WordPress option autoload must be disabled.' );
$assert(
	array( array( 'alloptions', 'options' ), array( WPD_Service_Account::OPTION_NAME, 'options' ) ) === $GLOBALS['wpd_old_cache_deletes'],
	'Legacy migration must invalidate both relevant WordPress option caches.'
);

WPD_Service_Account::register_settings();
$assert( 1 === $GLOBALS['wpdb']->updates, 'Repeated admin_init should not write an unchanged autoload flag.' );

$GLOBALS['wpdb']->autoload = 'yes';
WPD_Service_Account::enforce_non_autoload( 'an_unrelated_option' );
$assert( 1 === $GLOBALS['wpdb']->updates, 'Unrelated options must never be touched.' );
WPD_Service_Account::enforce_non_autoload( WPD_Service_Account::OPTION_NAME );
$assert( 2 === $GLOBALS['wpdb']->updates, 'New/updated credential options must be migrated.' );

echo "S09_LEGACY=PASS WordPress 6.0–6.3 conditional migration and cache invalidation\n";
