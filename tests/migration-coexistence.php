<?php
/** Compares exact GitHub 3.0.0 entrypoint with guarded newer entrypoint. */
declare(strict_types=1);
if ( ! isset( $argv[1] ) ) {
	foreach ( array( 'legacy-first', 'new-first' ) as $order ) {
		$cmd = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( $order );
		passthru( $cmd, $status );
		if ( 0 !== $status ) { exit( $status ); }
	}
	exit( 0 );
}
$order = $argv[1];
if ( ! in_array( $order, array( 'legacy-first', 'new-first' ), true ) ) { exit( 2 ); }
$root = sys_get_temp_dir() . '/wpd-coexist-' . bin2hex( random_bytes( 8 ) );
$legacy_path = $root . '/wp-piwigo-display';
$new_path = $root . '/display4piwigo';
function cleanup_migration( string $directory ): void {
	if ( ! is_dir( $directory ) ) { return; }
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $iterator as $file ) { $file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() ); }
	rmdir( $directory );
}
register_shutdown_function( static function () use ( $root ): void { cleanup_migration( $root ); } );
$short = array( 'plugin', 'settings', 'service-account', 'service-api', 'api-metrics', 'api', 'cache', 'diagnostic', 'renderer', 'shortcode', 'block', 'classic-editor', 'slider-transitions', 'masonry', 'justified', 'collage', 'composer-parity', 'gutenberg-parity', 'shapes', 'svg-mask-sanitizer', 'custom-svg-masks', 'user-fonts', 'bundled-fonts', 'photo-text', 'piwigo-response-compat' );
$names = array( 'WPD_Plugin', 'WPD_Settings', 'WPD_Service_Account', 'WPD_Service_Api', 'WPD_Api_Metrics', 'WPD_Api', 'WPD_Cache', 'WPD_Diagnostic', 'WPD_Renderer', 'WPD_Shortcode', 'WPD_Block', 'WPD_Classic_Editor', 'WPD_Slider_Transitions', 'WPD_Masonry', 'WPD_Justified', 'WPD_Collage', 'WPD_Composer_Parity', 'WPD_Gutenberg_Parity', 'WPD_Shapes', 'WPD_SVG_Mask_Sanitizer', 'WPD_Custom_SVG_Masks', 'WPD_User_Fonts', 'WPD_Bundled_Fonts', 'WPD_Photo_Text', 'WPD_Piwigo_Response_Compat' );
foreach ( array( $legacy_path, $new_path ) as $folder ) {
	mkdir( $folder . '/includes', 0700, true );
	foreach ( $short as $i => $stub ) {
		file_put_contents( $folder . '/includes/class-wpd-' . $stub . '.php', '<?php final class ' . $names[$i] . " {}\n" );
	}
}
copy( __DIR__ . '/fixtures/wp-piwigo-display-3.0.0.php', $legacy_path . '/wp-piwigo-display.php' );
copy( dirname( __DIR__ ) . '/wp-piwigo-display.php', $new_path . '/wp-piwigo-display.php' );
copy( dirname( __DIR__ ) . '/includes/class-wpd-migration-guard.php', $new_path . '/includes/class-wpd-migration-guard.php' );
define( 'ABSPATH', $root . '/' );
$GLOBALS['migration_hooks'] = array();
function plugin_basename( string $file ): string { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function plugin_dir_path( string $file ): string { return dirname( $file ) . '/'; }
function plugin_dir_url( string $file ): string { return 'https://example.test/' . basename( dirname( $file ) ) . '/'; }
function add_action( string $hook, callable $callback ): void { $GLOBALS['migration_hooks'][ $hook ][] = $callback; }
function register_activation_hook( string $entrypoint, callable $callback ): void { $GLOBALS['migration_hooks']['activation'][] = $callback; }
$load_order = 'legacy-first' === $order ? array( $legacy_path, $new_path ) : array( $new_path, $legacy_path );
foreach ( $load_order as $dir ) { require $dir . '/wp-piwigo-display.php'; }
if ( 1 !== count( $GLOBALS['migration_hooks']['plugins_loaded'] ?? array() ) || 1 !== count( $GLOBALS['migration_hooks']['activation'] ?? array() ) ) {
	fwrite( STDERR, "FAIL duplicate hooks\n" ); exit( 1 );
}
if ( realpath( WPD_PLUGIN_DIR ) !== realpath( $load_order[0] ) ) {
	fwrite( STDERR, "FAIL winning plugin directory\n" ); exit( 1 );
}
echo 'PASS ' . $order . " no fatal and one bootstrap (stub dependencies)\n";
