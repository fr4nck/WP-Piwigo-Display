<?php
/**
 * Repli Unicode des résolveurs réels sous les extensions PHP réellement actives.
 *
 * Les frontières WordPress/Piwigo utilisent un catalogue de test ; aucune fonction
 * mbstring ni classe Normalizer n'est remplacée ou simulée.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPD_VERSION', 'unicode-environment-test' );

final class WP_Error {
    public function __construct( public string $code, public string $message ) {}
    public function get_error_code(): string { return $this->code; }
}

final class WPD_Settings {
    public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
    public static function get_cache_duration(): int { return 60; }
}

final class WPD_Service_Account {
    public static function is_configured(): bool { return true; }
    public static function get_context_hash(): string { return 'unicode-service-fixture'; }
}

final class WPD_Service_Api {
    public static array $categories = array();
    public static int $calls = 0;
    public function __construct( string $url ) {}
    public function get_all_categories(): array {
        ++self::$calls;
        return self::$categories;
    }
}

final class WPD_Api_Metrics {
    public static function cache_hit( string $source ): void {}
    public static function cache_miss( string $source ): void {}
}

function __( string $value, string $domain = '' ): string { return $value; }
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function untrailingslashit( string $value ): string { return rtrim( $value, '/\\' ); }
function esc_url_raw( string $value, array $protocols = array() ): string { return $value; }
function wp_http_validate_url( string $value ): bool { return false !== filter_var( $value, FILTER_VALIDATE_URL ); }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function get_option( string $key, $default = false ) { return $default; }
function get_transient( string $key ) { return $GLOBALS['unicode_transients'][ $key ] ?? false; }
function set_transient( string $key, $value, int $ttl ): bool { $GLOBALS['unicode_transients'][ $key ] = $value; return true; }
function wp_using_ext_object_cache(): bool { return true; }
function wp_cache_add( ...$arguments ): bool { return true; }
function wp_cache_delete( ...$arguments ): bool { return true; }
function is_preview(): bool { return false; }

require_once ABSPATH . 'includes/class-wpd-api.php';
require_once ABSPATH . 'includes/class-wpd-cache.php';

$assert = static function ( bool $condition, string $message ): void {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
};
$mbstring = extension_loaded( 'mbstring' );
$intl = extension_loaded( 'intl' );
$assert( $mbstring === function_exists( 'mb_strtolower' ), 'La fonction mb_strtolower doit refléter la vraie extension, sans polyfill.' );
$assert( $intl === class_exists( 'Normalizer', false ), 'Normalizer doit refléter la vraie extension intl, sans polyfill.' );
foreach ( array( 'WPD_EXPECT_MBSTRING' => $mbstring, 'WPD_EXPECT_INTL' => $intl ) as $variable => $observed ) {
    $expected = getenv( $variable );
    if ( false !== $expected ) {
        $assert( in_array( $expected, array( '0', '1' ), true ), 'Attente de profil invalide : ' . $variable );
        $assert( ( '1' === $expected ) === $observed, 'Extension non conforme au profil CI : ' . $variable );
    }
}
echo 'UNICODE_EXTENSION_STATE=' . json_encode( array(
    'php' => PHP_VERSION, 'profile' => getenv( 'WPD_UNICODE_PROFILE' ) ?: 'ambient',
    'mbstring' => $mbstring, 'intl' => $intl,
    'mb_strtolower' => function_exists( 'mb_strtolower' ),
    'Normalizer' => class_exists( 'Normalizer', false ),
), JSON_UNESCAPED_UNICODE ) . "\n";

$unsupported = 'wpd_album_unicode_unsupported';
$ambiguous = 'wpd_album_ambiguous';
$not_found = 'wpd_album_not_found';
$ascii = array(
    array( 'id' => 10, 'name' => 'Public', 'uppercats' => '10', 'permalink' => 'public-album' ),
    array( 'id' => 50, 'name' => 'Private', 'uppercats' => '50', 'permalink' => 'private-parent' ),
    array( 'id' => 20, 'name' => 'Photos', 'uppercats' => '50,20', 'permalink' => 'private-photos' ),
    array( 'id' => 77, 'name' => '#2024', 'uppercats' => '77', 'permalink' => 'hash-album' ),
);
$precomposed = array(
    array( 'id' => 10, 'name' => 'Été', 'uppercats' => '10', 'permalink' => 'ete-public' ),
    array( 'id' => 50, 'name' => 'Privé', 'uppercats' => '50', 'permalink' => 'prive' ),
    array( 'id' => 20, 'name' => 'été', 'uppercats' => '50,20', 'permalink' => 'ete-prive' ),
    array( 'id' => 30, 'name' => 'Ete', 'uppercats' => '30', 'permalink' => 'ete-ascii' ),
    array( 'id' => 40, 'name' => 'Public', 'uppercats' => '40', 'permalink' => 'public' ),
);
$combining = array(
    array( 'id' => 10, 'name' => 'Café', 'uppercats' => '10', 'permalink' => 'cafe-public' ),
    array( 'id' => 50, 'name' => 'Prive', 'uppercats' => '50', 'permalink' => 'prive' ),
    array( 'id' => 20, 'name' => "Cafe\u{0301}", 'uppercats' => '50,20', 'permalink' => 'cafe-prive' ),
    array( 'id' => 30, 'name' => 'Cafe', 'uppercats' => '30', 'permalink' => 'cafe-ascii' ),
);
$permalinks = array(
    array( 'id' => 10, 'name' => 'Public', 'uppercats' => '10', 'permalink' => 'été' ),
    array( 'id' => 20, 'name' => 'Private', 'uppercats' => '20', 'permalink' => 'Été' ),
);
$catalogues = array(
    'ascii' => array( $ascii, array(
        'pUbLiC' => 10, '/private/photos' => 20, 'PRIVATE-PHOTOS' => 20,
        '#2024' => 77, '#20' => $not_found, 'missing' => $not_found,
    ) ),
    'unicode-precomposed' => array( $precomposed, array(
        'été' => $mbstring ? $ambiguous : $unsupported,
        'Été' => $mbstring ? $ambiguous : $unsupported,
        '/Été' => $mbstring ? 10 : $unsupported,
        '/Privé/Été' => $mbstring ? 20 : $unsupported,
        'ete-public' => $mbstring ? 10 : $unsupported,
        'Ete' => $mbstring ? 30 : $unsupported,
        // Refus global du catalogue incertain, même pour une saisie ASCII.
        'Public' => $mbstring ? 40 : $unsupported,
    ) ),
    'unicode-nfc-nfd' => array( $combining, array(
        'Café' => $mbstring && $intl ? $ambiguous : $unsupported,
        'café' => $mbstring && $intl ? $ambiguous : $unsupported,
        "Cafe\u{0301}" => $mbstring && $intl ? $ambiguous : $unsupported,
        '/Prive/Café' => $mbstring && $intl ? 20 : $unsupported,
        'cafe-prive' => $mbstring && $intl ? 20 : $unsupported,
        'Cafe' => $mbstring && $intl ? 30 : $unsupported,
        'Prive' => $mbstring && $intl ? 50 : $unsupported,
    ) ),
    'unicode-permalinks' => array( $permalinks, array(
        'Été' => $mbstring ? $ambiguous : $unsupported,
        'été' => $mbstring ? $ambiguous : $unsupported,
        'Public' => $mbstring ? 10 : $unsupported,
    ) ),
);
$counts = array( 'api' => 0, 'cache' => 0 );
foreach ( $catalogues as $scenario => [ $categories, $expectations ] ) {
    foreach ( array( $categories, array_reverse( $categories ) ) as $order => $ordered ) {
        WPD_Service_Api::$categories = $ordered;
        $GLOBALS['unicode_transients'] = array();
        foreach ( array( 'request_cache', 'api_clients' ) as $property ) {
            ( new ReflectionProperty( WPD_Cache::class, $property ) )->setValue( null, array() );
        }
        $api = new WPD_Api( WPD_Settings::get_piwigo_url() );
        $resolvers = array(
            'api' => array( $api, 'resolve_album_id' ),
            'cache' => array( WPD_Cache::class, 'resolve_album_id' ),
        );
        foreach ( $resolvers as $name => $resolve ) {
            // Deux passages sur le même cache : froid puis chaud.
            foreach ( array( 'cold', 'hot' ) as $phase ) {
                foreach ( $expectations as $input => $expected ) {
                    $result = $resolve( $input );
                    $actual = is_wp_error( $result ) ? $result->get_error_code() : $result;
                    $assert( $actual === $expected, json_encode( array(
                        'resolver' => $name, 'scenario' => $scenario, 'order' => $order,
                        'phase' => $phase, 'input' => $input, 'expected' => $expected, 'actual' => $actual,
                    ), JSON_UNESCAPED_UNICODE ) );
                    ++$counts[ $name ];
                }
                $before = WPD_Service_Api::$calls;
                $assert( 20 === $resolve( '20' ), 'Un ID privé explicite doit rester utilisable.' );
                $assert( 10 === $resolve( '10' ), 'Un ID public explicite doit rester utilisable.' );
                $assert( $before === WPD_Service_Api::$calls, 'Les ID ne doivent pas dépendre du chargement du catalogue.' );
                $counts[ $name ] += 3;
            }
        }
    }
}
echo 'UNICODE_FALLBACK_MATRIX=PASS ' . json_encode( array(
    'php' => PHP_VERSION, 'profile' => getenv( 'WPD_UNICODE_PROFILE' ) ?: 'ambient',
    'mbstring' => $mbstring, 'intl' => $intl, 'assertions' => $counts,
    'catalogues' => array_keys( $catalogues ), 'api_orders' => 2,
    'cache_phases' => array( 'cold', 'hot' ), 'numeric_ids' => 'preserved',
), JSON_UNESCAPED_UNICODE ) . "\n";
