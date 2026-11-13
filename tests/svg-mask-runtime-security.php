<?php
/**
 * Runtime adversarial SVG parsing tests, without WordPress or network access.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
final class WP_Error {
    public function __construct(private string $code, private string $message) {}
    public function get_error_code(): string { return $this->code; }
}
function __( $message, $domain = '' ): string { return (string) $message; }
function is_wp_error( $result ): bool { return $result instanceof WP_Error; }

require_once dirname( __DIR__ ) . '/includes/class-wpd-svg-mask-sanitizer.php';

$assert = static function ( bool $pass, string $message ): void {
    if ( ! $pass ) { throw new RuntimeException( $message ); }
};
$assert( class_exists( 'DOMDocument' ), 'PHP ext-dom is required to execute the runtime SVG security suite' );
$valid = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="#000"/></svg>';
$safe = WPD_SVG_Mask_Sanitizer::sanitize( $valid );
$assert( is_string( $safe ) && str_contains( $safe, '<circle' ), 'Legitimate geometry rejected' );

$invalid = array(
    'script element' => '<svg viewBox="0 0 100 100"><script>alert(1)</script></svg>',
    'event handler' => '<svg viewBox="0 0 100 100" onload="alert(1)"><path d="M0 0Z"/></svg>',
    'style element' => '<svg viewBox="0 0 100 100"><style>path{fill:red}</style></svg>',
    'style attribute' => '<svg viewBox="0 0 100 100"><path style="fill:red" d="M0 0Z"/></svg>',
    'embedded HTML' => '<svg viewBox="0 0 100 100"><foreignObject><div>bad</div></foreignObject></svg>',
    'external entity' => '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg viewBox="0 0 100 100"><path d="M0 0Z"/></svg>',
    'processing instruction' => '<svg viewBox="0 0 100 100"><?xml-stylesheet href="https://evil.test/x.css"?></svg>',
    'foreign namespace' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:e="https://evil.test" viewBox="0 0 100 100"><e:path d="M0 0Z"/></svg>',
    'foreign root' => '<svg xmlns="https://evil.test" viewBox="0 0 100 100"><path d="M0 0Z"/></svg>',
    'invalid viewBox token' => '<svg viewBox="text 0 100 100"><path d="M0 0Z"/></svg>',
    'non-finite viewBox' => '<svg viewBox="0 0 1e309 100"><path d="M0 0Z"/></svg>',
    'oversized viewBox' => '<svg viewBox="0 0 99999999 100"><path d="M0 0Z"/></svg>',
    'zero width' => '<svg viewBox="0 0 0 100"><path d="M0 0Z"/></svg>',
);
foreach ( $invalid as $description => $input ) {
    $result = WPD_SVG_Mask_Sanitizer::sanitize( $input );
    $assert( is_wp_error( $result ), 'Unsafe SVG unexpectedly accepted: ' . $description );
}
$malicious_attr = '<svg viewBox="0 0 100 100"><path id="untrusted" d="M0 0Z" fill="url(https://evil.test/track.svg)"/></svg>';
$result = WPD_SVG_Mask_Sanitizer::sanitize( $malicious_attr );
$assert( is_string( $result ), 'Safe subset rejected along with unapproved attributes' );
$assert( ! str_contains( $result, 'evil.test' ) && ! str_contains( $result, 'untrusted' ), 'External reference or unknown attribute survived' );
$overflow = '<svg viewBox="0 0 100 100">' . str_repeat( ' ', 262144 ) . '</svg>';
$assert( is_wp_error( WPD_SVG_Mask_Sanitizer::sanitize( $overflow ) ), '262144 byte payload limit not enforced' );
$exponent = WPD_SVG_Mask_Sanitizer::sanitize( '<svg viewBox="0 0 1.2e2 100"><path d="M0 0Z"/></svg>' );
$assert( is_string( $exponent ) && str_contains( $exponent, 'viewBox="0 0 120 100"' ), 'Valid bounded numeric exponent rejected' );

echo "SVG security runtime: 13 rejected payloads, references stripped, size limit and safe SVG PASS.\n";
