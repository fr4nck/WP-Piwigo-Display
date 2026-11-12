<?php
/**
 * CA-301-R2 / R2-03: a reintroduced admin_init network deactivation must
 * be detected by the subscriber test through its precise assertion.
 */
declare(strict_types=1);

$source = dirname( __DIR__ );
$root = sys_get_temp_dir() . '/wpd-admin-mutation-' . bin2hex( random_bytes( 8 ) );
$includes = $root . '/includes';
$tests = $root . '/tests';
mkdir( $includes, 0700, true );
mkdir( $tests, 0700, true );
$guard_file = $includes . '/class-wpd-migration-guard.php';
$test_file = $tests . '/migration-network-subscriber.php';
$original = (string) file_get_contents( $source . '/includes/class-wpd-migration-guard.php' );
copy( __DIR__ . '/migration-network-subscriber.php', $test_file );

$invoke = static function () use ( $test_file ): array {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $test_file ) . ' 2>&1';
	$output = array();
	$status = 0;
	exec( $command, $output, $status );
	return array(
		'exit'   => $status,
		'output' => implode( "\n", $output ),
	);
};

try {
	file_put_contents( $guard_file, $original );
	$baseline = $invoke();
	if ( 0 !== $baseline['exit'] ) {
		throw new RuntimeException( 'Baseline failed: ' . $baseline['output'] );
	}

	$needle = 'self::$registered[ $self ] = true;';
	if ( 1 !== substr_count( $original, $needle ) ) {
		throw new RuntimeException( 'Mutation anchor missing or ambiguous' );
	}

	$mutation = $needle . "\n"
		. "\t\tadd_action( 'admin_init', static function (): void {"
		. " deactivate_plugins( 'display4piwigo/wp-piwigo-display.php', true, true );"
		. " } );";
	file_put_contents( $guard_file, str_replace( $needle, $mutation, $original ) );
	$mutant = $invoke();
	if ( 0 === $mutant['exit']
		|| ! str_contains( $mutant['output'], 'FAIL subscriber triggered a network mutation' )
		|| preg_match( '/(?:Fatal error|Parse error|Uncaught)/i', $mutant['output'] )
	) {
		throw new RuntimeException( 'Network deactivation mutant escaped test assertion: ' . $mutant['output'] );
	}

	echo "PASS R2-03 admin_init network deactivation mutation killed by subscriber assertion\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, 'FAIL network mutation test: ' . $error->getMessage() . "\n" );
	exit( 1 );
} finally {
	foreach ( array( $test_file, $guard_file ) as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file );
		}
	}
	foreach ( array( $includes, $tests, $root ) as $dir ) {
		if ( is_dir( $dir ) ) {
			rmdir( $dir );
		}
	}
}
