<?php
/**
 * S-12 regression: malformed settings cannot crash WordPress or coerce arrays.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

function esc_url_raw( string $url ): string {
	return $url;
}
function wp_parse_url( string $url, int $component ) {
	return parse_url( $url, $component );
}
function trailingslashit( string $value ): string {
	return rtrim( $value, '/' ) . '/';
}
function absint( $value ): int {
	if ( ! is_scalar( $value ) ) {
		throw new UnexpectedValueException( 'absint received a non-scalar value.' );
	}
	return abs( (int) $value );
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-settings.php';

$fail = static function ( string $reason ): never {
	fwrite( STDERR, "S12=FAIL $reason\n" );
	exit( 1 );
};

set_error_handler( static function ( int $level, string $message ): never {
	throw new ErrorException( $message, 0, $level );
} );

$fields = array(
	'piwigo_url', 'cache_duration', 'default_type', 'default_fit',
	'default_ratio', 'default_height', 'default_autoplay',
	'default_interval', 'default_speed', 'default_lightbox',
	'default_caption', 'default_style', 'default_navigation',
	'default_rounded', 'default_sort', 'default_order', 'default_limit',
	'debug_mode',
);

try {
	$defaults = WPD_Settings::default_options();
	foreach ( array( array(), new stdClass() ) as $bad ) {
		$malformed = array_fill_keys( $fields, $bad );
		$cleaned   = WPD_Settings::sanitize_options( $malformed );
		if ( ! is_array( $cleaned ) || array_keys( $defaults ) !== array_keys( $cleaned ) ) {
			$fail( 'The malformed payload changed the settings schema.' );
		}
		foreach ( array( 'default_type', 'default_fit', 'default_navigation', 'default_sort', 'default_order' ) as $key ) {
			if ( $cleaned[ $key ] !== $defaults[ $key ] ) {
				$fail( "Unexpected fallback for $key." );
			}
		}
		if ( '' !== $cleaned['piwigo_url'] ) {
			$fail( 'A malformed URL was accepted.' );
		}
	}
	$valid = WPD_Settings::sanitize_options(
		array(
			'piwigo_url'         => 'https://photos.example.test/gallery',
			'default_type'      => 'slider',
			'default_navigation'=> 'dots',
			'default_fit'       => 'cover',
			'default_ratio'     => '4/3',
			'default_height'    => '420px',
			'cache_duration'    => '3600',
		)
	);
	if ( 'slider' !== $valid['default_type']
		|| 'dots' !== $valid['default_navigation']
		|| 'cover' !== $valid['default_fit']
		|| '4/3' !== $valid['default_ratio']
		|| '420px' !== $valid['default_height']
		|| 'https://photos.example.test/gallery/' !== $valid['piwigo_url']
		|| 3600 !== $valid['cache_duration'] ) {
		$fail( 'Valid settings changed after hardening.' );
	}
} catch ( Throwable $exception ) {
	$fail( get_class( $exception ) . ': ' . $exception->getMessage() );
} finally {
	restore_error_handler();
}

echo "S12=PASS malformed arrays/objects rejected without PHP warnings or changed valid options\n";
