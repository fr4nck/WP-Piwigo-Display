<?php
/**
 * CA-301-R2 / R2-01: renaming the WordPress.org main file, public name, URI
 * and text domain must not delete configuration while that copy is installed.
 *
 * All data is synthetic and files are created under one isolated temp folder.
 */
declare(strict_types=1);

$root = sys_get_temp_dir() . '/wpd-r2-renamed-' . bin2hex( random_bytes( 8 ) );
$old = $root . '/legacy';
$new = $root . '/official';
mkdir( $old, 0700, true );
mkdir( $new . '/includes', 0700, true );
copy( dirname( __DIR__ ) . '/uninstall.php', $old . '/uninstall.php' );
copy( dirname( __DIR__ ) . '/uninstall.php', $new . '/uninstall.php' );
copy( dirname( __DIR__ ) . '/includes/class-wpd-migration-guard.php', $new . '/includes/class-wpd-migration-guard.php' );
file_put_contents( $old . '/wp-piwigo-display.php', "<?php\n" );
file_put_contents( $new . '/entry-renamed.php', "<?php\n" );

register_shutdown_function(
	static function () use ( $root, $old, $new ): void {
		foreach ( array(
			$old . '/uninstall.php',
			$old . '/wp-piwigo-display.php',
			$new . '/uninstall.php',
			$new . '/entry-renamed.php',
			$new . '/includes/class-wpd-migration-guard.php',
		) as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		if ( is_dir( $new . '/includes' ) ) {
			rmdir( $new . '/includes' );
		}
		foreach ( array( $old, $new, $root ) as $dir ) {
			if ( is_dir( $dir ) ) {
				rmdir( $dir );
			}
		}
	}
);

define( 'ABSPATH', $root . '/' );
define( 'WP_PLUGIN_DIR', $root );
define( 'WP_UNINSTALL_PLUGIN', 'legacy/wp-piwigo-display.php' );
$GLOBALS['deleted'] = array();
$GLOBALS['catalogue_uri'] = 'https://elsewhere.invalid/unrelated';
function plugin_basename( string $file ): string {
	return ltrim( substr( $file, strlen( WP_PLUGIN_DIR ) ), '/\\' );
}
function get_plugins(): array {
	return array(
		'legacy/wp-piwigo-display.php' => array(
			'Name'       => 'Piwigo Display',
			'Version'    => '3.0.1',
			'TextDomain' => 'wp-piwigo-display',
		),
		'official/entry-renamed.php' => array(
			'Name'       => 'Photographies partagées',
			'Version'    => '3.0.1',
			'TextDomain' => 'nouveau-domaine',
			'PluginURI'  => $GLOBALS['catalogue_uri'],
		),
	);
}
function delete_option( string $name ): bool {
	$GLOBALS['deleted'][] = $name;
	return true;
}
function delete_transient( string $name ): bool { return true; }
function is_multisite(): bool { return false; }
final class WPD_Renamed_Db {
	public string $options = 'wp_options';
	public function esc_like( string $pattern ): string { return $pattern; }
	public function prepare( string $sql, ...$args ): string { return $sql; }
	public function get_col( string $sql ): array { return array(); }
}
$GLOBALS['wpdb'] = new WPD_Renamed_Db();

// Name, URI, domain AND main filename differ: the stable guard file identifies
// the sibling independently of those public-facing identifiers.
require $old . '/uninstall.php';
if ( array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL R2-01 family guard marker did not retain data\n" );
	exit( 1 );
}

// Also prove that the GitHub URL's original capitalization is NOT required.
unlink( $new . '/includes/class-wpd-migration-guard.php' );
$GLOBALS['catalogue_uri'] = 'https://github.com/fr4nck/wp-piwigo-display';
require $old . '/uninstall.php';
if ( array() !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL R2-01 lowercase GitHub URI did not retain data\n" );
	exit( 1 );
}

// Reinstate the family signature and check that removal of the final copy
// still purges credentials, even though WP_UNINSTALL_PLUGIN refers to legacy.
copy( dirname( __DIR__ ) . '/includes/class-wpd-migration-guard.php', $new . '/includes/class-wpd-migration-guard.php' );
$GLOBALS['catalogue_uri'] = 'https://elsewhere.invalid/unrelated';
unlink( $old . '/wp-piwigo-display.php' );
require $new . '/uninstall.php';
sort( $GLOBALS['deleted'] );
$expected = array( 'wp_piwigo_display_options', 'wp_piwigo_display_service_account', 'wpd_custom_svg_masks' );
sort( $expected );
if ( $expected !== $GLOBALS['deleted'] ) {
	fwrite( STDERR, "FAIL R2-01 final renamed distribution did not clean shared secrets\n" );
	exit( 1 );
}
echo "PASS R2-01 renamed WordPress.org sibling, case-insensitive URI, final purge\n";
