<?php
/**
 * Regression contract for GitHub and WordPress.org update-channel separation.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$plugin = (string) file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );

if ( 1 !== preg_match( '/^ \* Update URI: https:\/\/github\.com\/fr4nck\/WP-Piwigo-Display\s*$/m', $plugin ) ) {
	fwrite( STDERR, "The GitHub edition must carry its explicit Update URI.\n" );
	exit( 1 );
}

foreach ( array( 'php-lint.yml', 'build-plugin.yml' ) as $workflow ) {
	$text = (string) file_get_contents( __DIR__ . '/../.github/workflows/' . $workflow );

	if ( ! str_contains( $text, 'Check the WordPress.org candidate without GitHub Update URI' )
		|| ! str_contains( $text, 'Unexpected Update URI in WordPress.org candidate' )
		|| str_contains( $text, 'plugin_updater_detected' ) ) {
		fwrite( STDERR, "WordPress.org candidate staging guard missing in {$workflow}.\n" );
		exit( 1 );
	}
}

echo "Update channel separation contract: PASS.\n";
