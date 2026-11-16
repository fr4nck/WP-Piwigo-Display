<?php
/**
 * S-09/S-12: credential autoload migration and admin validation in WordPress.
 *
 * Disposable WP-CLI integration only. Never prints the synthetic password.
 *
 * @package WP_Piwigo_Display
 */

if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	fwrite( STDERR, "S09_S12=BLOCKED disposable WordPress WP-CLI required\n" );
	exit( 2 );
}
if ( ! class_exists( 'WPD_Service_Account' ) || ! class_exists( 'WPD_Settings' ) ) {
	fwrite( STDERR, "S09_S12=BLOCKED candidate plugin must be active\n" );
	exit( 2 );
}

global $wpdb;
$option = WPD_Service_Account::OPTION_NAME;
$prior  = get_option( $option, null );
$fail   = static function ( string $reason ): never {
	fwrite( STDERR, "S09_S12=FAIL $reason\n" );
	exit( 1 );
};
$autoload_value = static function () use ( $wpdb, $option ) {
	// Test-only read of the real database option flag.
	return $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $option ) );
};

try {
	delete_option( $option );
	add_option(
		$option,
		array(
			'enabled'  => 'true',
			'username' => 'synthetic-piwigo-service',
			'password' => 'synthetic-private-fixture',
		),
		'',
		true
	);
	$stored = get_option( $option );
	if ( ! is_array( $stored ) || 'synthetic-private-fixture' !== ( $stored['password'] ?? null ) ) {
		$fail( 'Fixture was not written.' );
	}

	// Existing pre-upgrade options may still carry autoload=on.
	wp_set_option_autoload( $option, true );
	if ( ! in_array( $autoload_value(), array( 'yes', 'on', 'auto', 'auto-on' ), true ) ) {
		$fail( 'Could not prepare an autoloaded legacy option.' );
	}

	do_action( 'admin_init' );
	if ( in_array( $autoload_value(), array( 'yes', 'on', 'auto', 'auto-on' ), true ) ) {
		$fail( 'Admin initialization left legacy service credentials autoloaded.' );
	}
	$alloptions = wp_load_alloptions( true );
	if ( isset( $alloptions[ $option ] ) ) {
		$fail( 'Credential option remains in WordPress alloptions cache.' );
	}
	$stored = get_option( $option );
	if ( 'synthetic-private-fixture' !== ( $stored['password'] ?? null ) ) {
		$fail( 'Autoload migration altered the stored secret.' );
	}

	update_option(
		$option,
		array(
			'enabled'  => 'true',
			'username' => 'synthetic-piwigo-service',
			'password' => '',
			'clear_password' => 'true',
		)
	);
	$stored = get_option( $option );
	if ( '' !== ( $stored['password'] ?? null )
		|| isset( $stored['clear_password'] )
		|| 'true' !== ( $stored['enabled'] ?? null ) ) {
		$fail( 'Explicit password removal did not persist cleanly.' );
	}
	if ( '' !== WPD_Service_Account::get_password() ) {
		$fail( 'Deleted secret is still accessible to the service client.' );
	}
	update_option(
		$option,
		array(
			'enabled'  => 'true',
			'username' => 'synthetic-piwigo-service',
			'password' => 'fresh-synthetic-private',
		)
	);
	update_option(
		$option,
		array(
			'enabled'  => 'true',
			'username' => 'synthetic-piwigo-service',
			'password' => '',
		)
	);
	$stored = get_option( $option );
	if ( 'fresh-synthetic-private' !== ( $stored['password'] ?? null ) ) {
		$fail( 'Blank password field no longer preserves the existing secret.' );
	}

	$admin_settings = WPD_Settings::sanitize_options(
		array(
			'default_type'       => array( 'slider' ),
			'default_navigation' => new stdClass(),
			'piwigo_url'         => array( 'https://fake.invalid/' ),
		)
	);
	if ( 'gallery' !== $admin_settings['default_type']
		|| 'thumbnails' !== $admin_settings['default_navigation']
		|| '' !== $admin_settings['piwigo_url'] ) {
		$fail( 'Malformed administrator settings were not rejected.' );
	}
	if ( in_array( $autoload_value(), array( 'yes', 'on', 'auto', 'auto-on' ), true ) ) {
		$fail( 'Secret was re-autoloaded during settings updates.' );
	}
	echo "S09_S12=PASS real WordPress autoload migration, password removal/retain and malformed options\n";
} finally {
	delete_option( $option );
	if ( null !== $prior ) {
		add_option( $option, $prior, '', false );
	}
}
