<?php
/**
 * Verifies that registered CSS and JavaScript files exist in a plugin tree.
 *
 * @package WP_Piwigo_Display
 */

$root = isset( $argv[1] ) ? (string) $argv[1] : dirname( __DIR__ );
$root = realpath( $root );

if ( false === $root || ! is_dir( $root ) ) {
	fwrite( STDERR, "Packaged asset check failed: invalid plugin root.\n" );
	exit( 1 );
}

$references = array();
$iterator   = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	$relative = ltrim( substr( $file->getPathname(), strlen( $root ) ), DIRECTORY_SEPARATOR );
	$top      = explode( DIRECTORY_SEPARATOR, $relative )[0];
	if ( in_array( $top, array( '.git', 'build', 'tests', 'vendor' ), true ) ) {
		continue;
	}

	$content = file_get_contents( $file->getPathname() );
	if ( false === $content ) {
		continue;
	}

	preg_match_all( "/WPD_PLUGIN_URL\\s*\\.\\s*'([^']+\\.(?:css|js))'/", $content, $matches );
	foreach ( $matches[1] as $reference ) {
		$references[ $reference ] = true;
	}
}

if ( empty( $references ) ) {
	fwrite( STDERR, "Packaged asset check failed: no registered assets found.\n" );
	exit( 1 );
}

$missing = array();
foreach ( array_keys( $references ) as $reference ) {
	$asset = $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $reference );
	if ( ! is_file( $asset ) ) {
		$missing[] = $reference;
	}
}

if ( ! empty( $missing ) ) {
	fwrite( STDERR, 'Packaged asset check failed: missing ' . implode( ', ', $missing ) . PHP_EOL );
	exit( 1 );
}

echo 'Packaged frontend assets: OK (' . count( $references ) . ")\n";
