<?php
/**
 * S-15 / S-16 distribution contract: min WP API, private documentation
 * exclusion and mask-library preservation across migrations.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root = dirname( __DIR__ );
$fail = static function ( string $message ): never {
	fwrite( STDERR, "S15_S16=FAIL $message\n" );
	exit( 1 );
};
$entry = (string) file_get_contents( $root . '/wp-piwigo-display.php' );
$readme = (string) file_get_contents( $root . '/readme.txt' );
$block = json_decode( (string) file_get_contents( $root . '/blocks/piwigo/block.json' ), true );
if ( ! is_array( $block ) || 3 !== ( $block['apiVersion'] ?? null ) ) {
	$fail( 'Expected Gutenberg Block API v3 metadata.' );
}
foreach ( array( 'plugin header' => $entry, 'readme' => $readme ) as $label => $source ) {
	if ( ! preg_match( '/^ *(?:\* )?Requires at least: ([0-9]+\.[0-9]+)/m', $source, $match )
		|| version_compare( $match[1], '6.3', '<' ) ) {
		$fail( "Minimum WordPress version in $label does not support Block API v3." );
	}
}

$workflow_cases = array(
	'.github/workflows/build-plugin.yml' => 1,
	'.github/workflows/php-lint.yml' => 2,
	'tests/e2e/start-lab.sh' => 1,
);
foreach ( $workflow_cases as $filename => $expected ) {
	$contents = (string) file_get_contents( $root . '/' . $filename );
	if ( substr_count( $contents, "--exclude '/docs/'" ) < $expected ) {
		$fail( "Internal documentation may leak into packages produced by $filename." );
	}
}
$build = (string) file_get_contents( $root . '/.github/workflows/build-plugin.yml' );
$ci = (string) file_get_contents( $root . '/.github/workflows/php-lint.yml' );
if ( ! str_contains( $build, 'wp-piwigo-display/(docs/' )
	|| ! str_contains( $ci, 'wp-piwigo-display/(\.github|docs|' )
	|| ! str_contains( $ci, 'display4piwigo/(\.github|docs|' ) ) {
	$fail( 'Both generated ZIPs must explicitly reject internal docs/.');
}

$migration = (string) file_get_contents( $root . '/tests/ci-wordpress-migration.sh' );
if ( ! str_contains( $migration, 'get_option("wpd_custom_svg_masks")' )
	|| ! str_contains( $migration, 'wpd_custom_svg_masks' )
	|| ! str_contains( $migration, 'WP_MIGRATION_MASKS=PASS' ) ) {
	$fail( 'Migration must preserve custom SVG masks through upgrade/rollback and scrub them on last uninstall.' );
}

// Test the oldest advertised Block API v3 compatible WordPress branch.
if ( ! preg_match( "/- php: '8\.1'\s*\n\s*wordpress: '6\.3\.14'\s*\n\s*mode: single/", $ci ) ) {
	$fail( 'The minimum supported WordPress 6.3 line requires a real single-site CI migration.' );
}

$action_workflows = array(
	'.github/workflows/build-plugin.yml',
	'.github/workflows/php-lint.yml',
	'.github/workflows/piwigo-browser.yml',
);
foreach ( $action_workflows as $path ) {
	$contents = (string) file_get_contents( $root . '/' . $path );
	preg_match_all( '/^\s*uses:\s*[a-z0-9_-]+\/[a-z0-9_-]+@([^\s#]+)/m', $contents, $matches );
	if ( empty( $matches[1] ) ) {
		$fail( "No GitHub Action dependency found in $path." );
	}
	foreach ( $matches[1] as $ref ) {
		if ( 1 !== preg_match( '/^[a-f0-9]{40}$/D', $ref ) ) {
			$fail( "GitHub Action dependency is not commit-pinned in $path." );
		}
	}
}
$wpcli_digest = 'ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c';
$wpcli_url = 'https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar';
foreach ( array( '.github/workflows/php-lint.yml', 'tests/e2e/start-lab.sh' ) as $path ) {
	$contents = (string) file_get_contents( $root . '/' . $path );
	if ( ! str_contains( $contents, $wpcli_url )
		|| ! str_contains( $contents, $wpcli_digest )
		|| ! str_contains( $contents, 'sha256sum --check' ) ) {
		$fail( "Downloaded WP-CLI in $path must be pinned and SHA256-verified." );
	}
}

echo "S15_S16=PASS Block API min WP, ZIP privacy, and SVG mask migration assertions\n";
