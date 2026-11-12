<?php
/**
 * Regression contract for distinct update channels and intact ZIP workflows.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$plugin = (string) file_get_contents( dirname( __DIR__ ) . '/wp-piwigo-display.php' );
if ( 1 !== preg_match( '/^ \* Update URI: https:\/\/github\.com\/fr4nck\/WP-Piwigo-Display\s*$/m', $plugin ) ) {
	fwrite( STDERR, "The GitHub edition must retain its Update URI.\n" );
	exit( 1 );
}

$workflows = array( 'php-lint.yml', 'build-plugin.yml' );
foreach ( $workflows as $workflow ) {
	$text = (string) file_get_contents( dirname( __DIR__ ) . '/.github/workflows/' . $workflow );
	if ( ! str_contains( $text, 'php .github/scripts/prepare-org-candidate.php build/plugin-check/piwigo-display piwigo-display' )
		|| ! str_contains( $text, "ignore-codes: 'WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound'" )
		|| str_contains( $text, 'TextDomainMismatch' )
		|| str_contains( $text, 'textdomain_mismatch' )
		|| str_contains( $text, 'plugin_updater_detected' ) ) {
		fwrite( STDERR, "WordPress.org i18n check is missing or weakened in {$workflow}.\n" );
		exit( 1 );
	}
}

$package = (string) file_get_contents( dirname( __DIR__ ) . '/.github/workflows/build-plugin.yml' );
if ( 1 !== substr_count( $package, '  build-package:' )
	|| 1 !== substr_count( $package, '  prepare-release-draft:' )
	|| 1 !== substr_count( $package, "grep -c '^wp-piwigo-display/wp-piwigo-display.php$'" )
	|| 1 !== substr_count( $package, "grep -c '^wp-piwigo-display/includes/class-wpd-migration-guard.php$'" ) ) {
	fwrite( STDERR, "Package workflow is duplicated or its ZIP integrity checks are malformed.\n" );
	exit( 1 );
}

echo "Update channels and package workflow integrity: PASS.\n";
