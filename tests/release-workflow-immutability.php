<?php
/**
 * Guards against rebuilding or deleting a published release from a branch push.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

$workflow = file_get_contents( dirname( __DIR__ ) . '/.github/workflows/build-plugin.yml' );
$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};
$assert( is_string( $workflow ) && '' !== $workflow, 'Packaging workflow must be readable.' );
$assert( false === strpos( $workflow, 'gh release delete' ), 'CI must never delete published releases.' );
$assert( false === strpos( $workflow, '--cleanup-tag' ), 'CI must never remove published tags.' );
$assert( false === strpos( $workflow, "'release/**'" ), 'Release branch pushes must never publish automatically.' );
$assert( false !== strpos( $workflow, "if: github.ref_type == 'tag'" ), 'Release publishing must require a tag.' );
$assert( false !== strpos( $workflow, 'already exists; refusing to overwrite' ), 'Published tags must not be overwritten.' );
echo "Release workflow immutability: OK\n";