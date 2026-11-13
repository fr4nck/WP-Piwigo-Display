<?php
/**
 * Behavioral and editor-contract tests for click destinations.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

function sanitize_key( $value ): string {
	return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $value ) );
}
function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}
function absint( $value ): int {
	return abs( (int) $value );
}
function esc_url_raw( $value, $protocols = array() ): string {
	return (string) $value;
}
function wp_parse_url( $value, $component = -1 ) {
	return parse_url( $value, $component );
}
function untrailingslashit( $value ): string {
	return rtrim( (string) $value, '/' );
}
function check( bool $condition, string $description ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $description );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-shortcode.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-block.php';

$method = new ReflectionMethod( WPD_Shortcode::class, 'sanitize_atts' );
foreach ( array( 'default', 'piwigo_photo', 'piwigo_album' ) as $action ) {
	$sanitized = $method->invoke( null, array( 'click_action' => $action ) );
	check( $action === $sanitized['click_action'], 'Valid shortcode action rejected: ' . $action );
}
$invalid = $method->invoke( null, array( 'click_action' => 'https://malicious.example.test' ) );
check( 'default' === $invalid['click_action'], 'Unknown shortcode values must fall back to the legacy behavior.' );
$missing = $method->invoke( null, array() );
check( 'default' === $missing['click_action'], 'Legacy shortcodes must preserve the default click action.' );

$mapped = WPD_Block::attributes_to_shortcode(
	array(
		'albumId'     => '42',
		'clickAction' => 'piwigo_photo',
		'limit'       => 6,
	)
);
check( 'piwigo_photo' === $mapped['click_action'], 'Gutenberg must map clickAction to click_action.' );
check( '6' === $mapped['limit'], 'Gutenberg must preserve the preview limit.' );

$block = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/blocks/piwigo/block.json' ), true );
check( 'default' === ( $block['attributes']['clickAction']['default'] ?? '' ), 'Gutenberg block metadata must default to legacy click behavior.' );

$admin   = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-settings.php' );
$classic = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-wpd-classic-editor.php' );
$js      = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/wp-piwigo-display-classic-editor.js' );
$editor  = (string) file_get_contents( dirname( __DIR__ ) . '/blocks/piwigo/index.js' );

check( str_contains( $admin, 'wpd-c-click-action' ) && str_contains( $admin, "'click_action'" ), 'Admin composer must generate click_action.' );
check( str_contains( $classic, 'data-wpd="click_action"' ) && str_contains( $js, "'click_action'" ), 'Classic Editor must support click_action.' );
check( str_contains( $editor, "'clickAction'" ) && str_contains( $editor, 'click_action' ), 'Gutenberg must offer a click destination control.' );

echo "Click destination contracts: shortcode validation, Gutenberg, admin and Classic Editor: PASS.\n";
