<?php
/**
 * Build a disposable WordPress.org translation candidate without touching
 * the installed GitHub distribution or the persistent block/option identities.
 *
 * Usage: php .github/scripts/prepare-org-candidate.php build/plugin-check/<slug> <slug>
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( 'cli' !== PHP_SAPI || 3 !== $argc ) {
	fwrite( STDERR, "Usage: php prepare-org-candidate.php build/plugin-check/<slug> <slug>\n" );
	exit( 2 );
}

$slug = $argv[2];
if ( 1 !== preg_match( '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $slug ) ) {
	fwrite( STDERR, "Invalid temporary candidate slug.\n" );
	exit( 2 );
}

$project_root = dirname( __DIR__, 2 );
$staging_root = realpath( $project_root . '/build/plugin-check' );
$target = realpath( $argv[1] );
if ( false === $staging_root || false === $target
	|| ! str_starts_with( $target, $staging_root . DIRECTORY_SEPARATOR )
	|| basename( $target ) !== $slug
) {
	fwrite( STDERR, "Refusing to modify anything outside the disposable build/plugin-check/<slug> tree.\n" );
	exit( 2 );
}

$entry_file = $target . '/wp-piwigo-display.php';
$block_file = $target . '/blocks/piwigo/block.json';
$guard_file = $target . '/includes/class-wpd-migration-guard.php';
if ( ! is_file( $entry_file ) || ! is_file( $block_file ) || ! is_file( $guard_file ) ) {
	fwrite( STDERR, "Incomplete distribution tree; refusing to transform.\n" );
	exit( 2 );
}

$entry = (string) file_get_contents( $entry_file );
$entry = preg_replace_callback(
	'/^([ \t]*\*[ \t]*Text Domain:[ \t]*)wp-piwigo-display[ \t]*$/m',
	static function ( array $match ) use ( $slug ): string {
		return $match[1] . $slug;
	},
	$entry,
	1,
	$header_changes
);
$entry = preg_replace(
	'/^[ \t]*\*[ \t]*Update URI:[^\r\n]*(?:\r?\n|$)/m',
	'',
	$entry,
	-1,
	$update_uri_removed
);
if ( 1 !== $header_changes || 1 !== $update_uri_removed ) {
	fwrite( STDERR, "Unexpected plugin headers; refusing an incomplete WordPress.org candidate.\n" );
	exit( 2 );
}

if ( 'display4piwigo' === $slug ) {
	// The candidate's public name avoids implying it is an official Piwigo plugin.
	// This does NOT rename legacy block IDs, option names or PHP entrypoints.
	$entry = preg_replace_callback(
		'/^([ \t]*\*[ \t]*Plugin Name:[ \t]*)Piwigo Display[ \t]*$/m',
		static function ( array $match ): string {
			return $match[1] . 'Display4Piwigo';
		},
		$entry,
		1,
		$name_changes
	);
	$readme_file = $target . '/readme.txt';
	$readme = is_file( $readme_file ) ? (string) file_get_contents( $readme_file ) : '';
	if ( 1 !== $name_changes || ! str_starts_with( $readme, '=== Piwigo Display ===' ) ) {
		fwrite( STDERR, "Unexpected public product name in WordPress.org staging.\n" );
		exit( 2 );
	}
	file_put_contents(
		$readme_file,
		'=== Display4Piwigo ===' . substr( $readme, strlen( '=== Piwigo Display ===' ) )
	);
}

$block = (string) file_get_contents( $block_file );
if ( ! str_contains( $block, '"name": "wp-piwigo-display/gallery"' ) ) {
	fwrite( STDERR, "Persistent Gutenberg block name has changed.\n" );
	exit( 2 );
}
$block = preg_replace_callback(
	'/("textdomain"\s*:\s*)"wp-piwigo-display"/',
	static function ( array $match ) use ( $slug ): string {
		return $match[1] . '"' . $slug . '"';
	},
	$block,
	1,
	$block_changes
);
if ( 1 !== $block_changes ) {
	fwrite( STDERR, "Unexpected Gutenberg text domain; refusing transformation.\n" );
	exit( 2 );
}

$gettext_functions = array(
	'__', '_e', '_x', '_ex', '_n', '_nx', '_n_noop', '_nx_noop',
	'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e',
	'translate', 'translate_with_gettext_context',
);

/**
 * Convert only gettext-domain arguments in PHP. CSS handles, cache groups,
 * option keys, admin page IDs, and the Gutenberg block name are not gettext.
 *
 * @param string   $php PHP source.
 * @param string   $domain WordPress.org candidate domain.
 * @param string[] $gettext_functions Names of translation functions.
 * @param int      $modified Number of translated domain literals.
 * @return string The transformed PHP source.
 */
$rewrite_php = static function ( string $php, string $domain, array $gettext_functions, int &$modified ): string {
	$tokens = token_get_all( $php );
	$total = count( $tokens );
	$modified = 0;

	for ( $i = 0; $i < $total; ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0]
			|| ! in_array( strtolower( $tokens[ $i ][1] ), $gettext_functions, true )
		) {
			continue;
		}
		$j = $i + 1;
		while ( $j < $total && is_array( $tokens[ $j ] )
			&& in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true )
		) {
			++$j;
		}
		if ( $j >= $total || '(' !== $tokens[ $j ] ) {
			continue;
		}

		$depth = 1;
		for ( $k = $j + 1; $k < $total && $depth > 0; ++$k ) {
			if ( '(' === $tokens[ $k ] ) {
				++$depth;
			} elseif ( ')' === $tokens[ $k ] ) {
				--$depth;
			} elseif ( is_array( $tokens[ $k ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $k ][0] ) {
				$literal = $tokens[ $k ][1];
				if ( "'wp-piwigo-display'" === $literal || '"wp-piwigo-display"' === $literal ) {
					$quote = $literal[0];
					$tokens[ $k ][1] = $quote . $domain . $quote;
					++$modified;
				}
			}
		}
	}

	return implode(
		'',
		array_map(
			static function ( $token ): string {
				return is_array( $token ) ? $token[1] : $token;
			},
			$tokens
		)
	);
};

$php_changes = 0;
$js_changes = 0;
$php_changed_files = 0;
$js_changed_files = 0;

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $target, FilesystemIterator::SKIP_DOTS )
);
foreach ( $files as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$filename = $file->getPathname();
	if ( $entry_file === $filename || $block_file === $filename ) {
		continue;
	}
	$ext = strtolower( $file->getExtension() );
	if ( 'php' === $ext ) {
		$old = (string) file_get_contents( $filename );
		$count = 0;
		$new = $rewrite_php( $old, $slug, $gettext_functions, $count );
		if ( $count > 0 ) {
			file_put_contents( $filename, $new );
			$php_changes += $count;
			++$php_changed_files;
		}
	} elseif ( 'js' === $ext && ! str_contains( $filename, '/assets/vendor/' ) ) {
		$old = (string) file_get_contents( $filename );
		$new = str_replace(
			array( "'wp-piwigo-display'", '"wp-piwigo-display"' ),
			array( "'" . $slug . "'", '"' . $slug . '"' ),
			$old,
			$count
		);
		if ( $count > 0 ) {
			file_put_contents( $filename, $new );
			$js_changes += $count;
			++$js_changed_files;
		}
	}
}
file_put_contents( $entry_file, $entry );
file_put_contents( $block_file, $block );

$remaining_gettext = 0;
foreach ( $files as $file ) {
	if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
		$after = (string) file_get_contents( $file->getPathname() );
		$found = 0;
		$rewrite_php( $after, $slug, $gettext_functions, $found );
		$remaining_gettext += $found;
	}
}
if ( 0 !== $remaining_gettext || 0 === $php_changes || 0 === $js_changes ) {
	fwrite( STDERR, "Candidate translation coverage incomplete.\n" );
	exit( 1 );
}

$entry = (string) file_get_contents( $entry_file );
$block = (string) file_get_contents( $block_file );
$settings = (string) file_get_contents( $target . '/includes/class-wpd-settings.php' );
$service = (string) file_get_contents( $target . '/includes/class-wpd-service-account.php' );
$shortcode = (string) file_get_contents( $target . '/includes/class-wpd-shortcode.php' );
$cache = (string) file_get_contents( $target . '/includes/class-wpd-cache.php' );
if ( ! str_contains( $entry, 'Text Domain: ' . $slug )
	|| preg_match( '/^[ \t]*\*[ \t]*Update URI:/m', $entry )
	|| ! str_contains( $block, '"name": "wp-piwigo-display/gallery"' )
	|| ! str_contains( $block, '"textdomain": "' . $slug . '"' )
	|| ! str_contains( $settings, 'wp_piwigo_display_options' )
	|| ! str_contains( $service, 'wp_piwigo_display_service_account' )
	|| ! str_contains( $shortcode, "add_shortcode( 'piwigo'" )
	|| ! str_contains( $cache, "'wp-piwigo-display'" )
	|| ( 'display4piwigo' === $slug && ! str_contains( $entry, 'Plugin Name: Display4Piwigo' ) )
) {
	fwrite( STDERR, "Candidate changed a persistent identity or failed to separate update channels.\n" );
	exit( 1 );
}

printf(
	"WPORG_CANDIDATE_CHECK=PASS slug=%s php_domains=%d php_files=%d js_domains=%d js_files=%d\n",
	$slug,
	$php_changes,
	$php_changed_files,
	$js_changes,
	$js_changed_files
);
