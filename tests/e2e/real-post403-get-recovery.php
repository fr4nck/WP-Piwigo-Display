<?php
/**
 * WordPress WP-CLI integration against actual disposable Piwigo.
 *
 * Simulates an HTTP firewall refusing public POST album requests. The
 * subsequent guest GET must reach the real Piwigo ws.php and succeed.
 *
 * Never load this helper from WordPress or the packaged plugin itself.
 *
 * @package WP_Piwigo_Display
 */
declare(strict_types=1);

if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
    fwrite( STDERR, "Only permitted from WP-CLI in disposable lab\n" );
    exit( 2 );
}

$album = (int) getenv( 'WPD_TEST_ALBUM' );
if ( 0 >= $album ) {
    fwrite( STDERR, "Invalid synthetic Piwigo album ID\n" );
    exit( 3 );
}

$blocked_post = 0;
$allowed_get  = 0;
add_filter(
    'pre_http_request',
    static function ( $pre, $args, $url ) use ( &$blocked_post, &$allowed_get ) {
        if ( ! is_array( $args ) || ! is_string( $url ) ) {
            return $pre;
        }

        // Never intercept WordPress, third-party or production HTTP targets.
        if (
            '127.0.0.1' !== wp_parse_url( $url, PHP_URL_HOST )
            || 8090 !== (int) wp_parse_url( $url, PHP_URL_PORT )
            || '/ws.php' !== wp_parse_url( $url, PHP_URL_PATH )
            || ! str_contains( $url, 'format=json' )
        ) {
            return $pre;
        }

        $verb = strtoupper( (string) ( $args['method'] ?? 'GET' ) );
        if ( 'GET' === $verb ) {
            ++$allowed_get;
            return $pre;
        }

        $body = $args['body'] ?? array();
        if (
            'POST' !== $verb || ! is_array( $body )
            || ! in_array(
                $body['method'] ?? '',
                array( 'pwg.categories.getList', 'pwg.categories.getImages' ),
                true
            )
        ) {
            return $pre;
        }

        ++$blocked_post;
        return array(
            'headers'  => array(),
            'body'     => 'CI simulated POST restriction',
            'response' => array( 'code' => 403, 'message' => 'Forbidden' ),
            'cookies'  => array(),
        );
    },
    10,
    3
);

$api = new WPD_Api( 'http://127.0.0.1:8090' );
$categories = $api->get_all_categories();
if ( is_wp_error( $categories ) ) {
    fwrite( STDERR, 'Could not recover public album list: ' . $categories->get_error_message() . "\n" );
    exit( 4 );
}
$ids = array_map( 'intval', array_column( $categories, 'id' ) );
if ( ! in_array( $album, $ids, true ) ) {
    fwrite( STDERR, "The synthetic public album was not returned\n" );
    exit( 5 );
}

$images = $api->get_images_from_album( $album, 6 );
if ( is_wp_error( $images ) ) {
    fwrite( STDERR, 'Could not recover real public photo list: ' . $images->get_error_message() . "\n" );
    exit( 6 );
}
if ( count( $images ) < 6 ) {
    fwrite( STDERR, "Incomplete public album photo response\n" );
    exit( 7 );
}
if ( 2 !== $blocked_post || 2 !== $allowed_get ) {
    fwrite( STDERR, "Unexpected retry counts POST403=$blocked_post GET=$allowed_get\n" );
    exit( 8 );
}

echo "REAL_PIWIGO_POST403_GET_RECOVERY=PASS album=$album photos=" . count( $images ) . "\n";
