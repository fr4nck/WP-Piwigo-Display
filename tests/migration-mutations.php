<?php
/**
 * In a disposable directory, inject regression mutations to prove
 * that the corresponding safety assertions actually detect failures.
 *
 * Never mutates source files, Git state, WordPress or production data.
 */
declare(strict_types=1);

$source_root = dirname( __DIR__ );
$uninstall = (string) file_get_contents( $source_root . '/uninstall.php' );
$fixture_root = sys_get_temp_dir() . '/wpd-mutation-' . bin2hex( random_bytes( 8 ) );
$fixture_tests = $fixture_root . '/tests';
mkdir( $fixture_tests, 0700, true );
$fixture_scripts = array(
	'MG05' => 'migration-uninstall-filename.php',
	'MG12' => 'migration-uninstall-keep-integer.php',
	'MG13' => 'migration-uninstall-failclosed.php',
);
$fixture_files = array();
$mutant_results = array();

$expected_failures = array(
	'MG05' => 'FAIL renamed sibling was not protected by entrypoint identity',
	'MG12' => 'FAIL integer opt-in discarded sensitive data',
	'MG13' => 'FAIL uninstall purged despite unknown installed copies',
);

$run_fixture = static function ( string $file ) use ( $fixture_tests ): array {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $fixture_tests . '/' . $file ) . ' 2>&1';
	$output = array();
	$exit_code = 0;
	exec( $command, $output, $exit_code );
	return array(
		'exit'   => $exit_code,
		'output' => implode( "\n", $output ),
	);
};

try {
	foreach ( $fixture_scripts as $fixture ) {
		$target = $fixture_tests . '/' . $fixture;
		if ( ! copy( __DIR__ . '/' . $fixture, $target ) ) {
			throw new RuntimeException( 'Could not copy fixture: ' . $fixture );
		}
		$fixture_files[] = $target;
	}

	$uninstall_file = $fixture_root . '/uninstall.php';
	foreach ( $fixture_scripts as $label => $fixture ) {
		$mutant = $uninstall;
		switch ( $label ) {
			case 'MG05':
				$needle = "'wp-piwigo-display.php' === basename( \$wpd_other )";
				if ( 1 !== substr_count( $mutant, $needle ) ) {
					throw new RuntimeException( 'MG05 mutation anchor ambiguous or absent' );
				}
				$mutant = str_replace( $needle, 'false', $mutant );
				break;
			case 'MG12':
				$needle = 'filter_var( WPD_KEEP_DATA_ON_UNINSTALL, FILTER_VALIDATE_BOOLEAN )';
				if ( 1 !== substr_count( $mutant, $needle ) ) {
					throw new RuntimeException( 'MG12 mutation anchor ambiguous or absent' );
				}
				$mutant = str_replace( $needle, 'false', $mutant );
				break;
			case 'MG13':
				$regex = "/if\s*\(\s*!\s*function_exists\(\s*'get_plugins'\s*\)\s*\)\s*\{\s*return;\s*\}/";
				// Simulate a destructive assumption: when the plugin inventory
				// cannot be loaded, pretend that only the current copy exists.
				// This must trigger the fixture's assertion, not a PHP fatal.
				$replacement = "if ( ! function_exists( 'get_plugins' ) ) { "
					. "function get_plugins(): array { return array( "
					. "'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ) "
					. "); } }";
				$mutant = preg_replace( $regex, $replacement, $mutant, 1, $replacements );
				if ( 1 !== $replacements ) {
					throw new RuntimeException( 'MG13 mutation anchor ambiguous or absent' );
				}
				break;
		}

		file_put_contents( $uninstall_file, $uninstall );
		$baseline = $run_fixture( $fixture );
		if ( 0 !== $baseline['exit'] ) {
			throw new RuntimeException( $label . ': unmodified baseline failed: ' . $baseline['output'] );
		}
		file_put_contents( $uninstall_file, $mutant );
		$result = $run_fixture( $fixture );
		if ( 0 === $result['exit']
			|| ! str_contains( $result['output'], $expected_failures[ $label ] )
			|| preg_match( '/(?:Fatal error|Parse error|Uncaught|Call to undefined function)/i', $result['output'] )
		) {
			throw new RuntimeException( $label . ': mutant not killed by its assertion: ' . $result['output'] );
		}
		$mutant_results[] = $label;
		echo 'PASS ' . $label . " killed by explicit test assertion (not PHP fatal)\n";
	}
} catch ( Throwable $exception ) {
	fwrite( STDERR, 'FAIL migration mutation harness: ' . $exception->getMessage() . "\n" );
	exit( 1 );
} finally {
	foreach ( $fixture_files as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file );
		}
	}
	if ( is_file( $fixture_root . '/uninstall.php' ) ) {
		unlink( $fixture_root . '/uninstall.php' );
	}
	if ( is_dir( $fixture_tests ) ) {
		rmdir( $fixture_tests );
	}
	if ( is_dir( $fixture_root ) ) {
		rmdir( $fixture_root );
	}
}
if ( 3 !== count( $mutant_results ) ) {
	fwrite( STDERR, "FAIL incomplete mutation campaign\n" );
	exit( 1 );
}
echo "PASS 3/3 targeted migration mutants detected\n";
