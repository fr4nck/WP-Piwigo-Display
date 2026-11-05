<?php
/**
 * Regressions: only a new tag may publish a release; branch runs are artifacts.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

$workflow = file_get_contents( dirname( __DIR__ ) . '/.github/workflows/build-plugin.yml' );
if ( false === $workflow ) {
    fwrite( STDERR, "Cannot read release workflow.\n" );
    exit( 1 );
}

$required = array(
    "if: github.ref_type != 'tag'",
    "if: github.ref_type == 'tag'",
    'actions/upload-artifact@v4',
    'gh release create',
    'refusing to overwrite an immutable tagged release',
    'test "$GITHUB_REF_NAME"',
);
$forbidden = array( 'gh release delete', 'gh release edit', '--cleanup-tag', 'Replace mutable' );
foreach ( $required as $value ) {
    if ( false === strpos( $workflow, $value ) ) {
        fwrite( STDERR, "Required release protection missing: {$value}\n" );
        exit( 1 );
    }
}
foreach ( $forbidden as $value ) {
    if ( false !== strpos( $workflow, $value ) ) {
        fwrite( STDERR, "Destructive release path detected: {$value}\n" );
        exit( 1 );
    }
}
echo "Immutable release workflow: OK\n";
