<?php
/**
 * Compte de service et homonymes dans WordPress/Piwigo réels, sans réponses simulées.
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) || '1' !== getenv( 'WPD_EDITOR_SECURITY_LAB' ) ) {
    exit( 2 );
}
$fixture = json_decode( file_get_contents( getenv( 'WPD_LAB' ) . '/fixture.json' ), true );
$assert = static function ( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
};
$url = WPD_Settings::get_piwigo_url();
$service = new WPD_Service_Api( $url );
$status = $service->test_connection();
$assert( ! is_wp_error( $status ) && 'ci_reader' === ( $status['result']['username'] ?? '' ), 'Le vrai compte lecteur doit être authentifié.' );
$categories = $service->get_all_categories();
$assert( is_array( $categories ), 'La liste authentifiée doit être réelle et valide.' );
$homonyms = array_values( array_filter( $categories, static fn( $cat ) => 'CI Homonyme' === $cat['name'] ) );
$ids = array_map( 'intval', array_column( $homonyms, 'id' ) );
$assert( 2 === count( $ids ) && in_array( $fixture['public'], $ids, true ) && in_array( $fixture['private'], $ids, true ), 'Le lecteur doit voir les deux homonymes réels.' );
$anonymous = new WPD_Api( $url );
$guest_categories = $anonymous->get_all_categories();
$assert( is_array( $guest_categories ) && ! in_array( $fixture['private'], array_map( 'intval', array_column( $guest_categories, 'id' ) ), true ), 'L’invité ne doit pas voir l’album privé.' );
$guest_images = $anonymous->get_images_from_album( $fixture['private'] );
$assert( is_wp_error( $guest_images ) || array() === $guest_images, 'Piwigo doit refuser les images privées à l’invité.' );

$image_calls = 0;
add_filter( 'http_request_args', static function ( $args, $request_url ) use ( &$image_calls ) {
    if ( 'pwg.categories.getImages' === ( $args['body']['method'] ?? '' ) ) {
        ++$image_calls;
    }
    return $args;
}, 30, 2 );
foreach ( array( 'cold', 'hot' ) as $cache_state ) {
    foreach ( array( array( WPD_Cache::class, 'resolve_album_id' ), array( $anonymous, 'resolve_album_id' ) ) as $resolver ) {
        $result = $resolver( 'CI Homonyme' );
        $assert( is_wp_error( $result ) && 'wpd_album_ambiguous' === $result->get_error_code(), 'Un homonyme doit être refusé : ' . $cache_state );
    }
    $html = do_shortcode( '[piwigo album="CI Homonyme"]' );
    $assert( str_contains( $html, 'wp-piwigo-display-error' ) && ! str_contains( $html, '<img' )
        && ! str_contains( $html, 'CI_private_SENTINEL' ) && ! str_contains( $html, 'CI Private Parent' ),
        'Le shortcode ambigu doit échouer sans image ni détail privé.' );
}
$assert( 0 === $image_calls, 'La résolution ambiguë ne doit demander aucune image.' );
foreach ( array( '/CI Homonyme' => 'public', '/CI Private Parent/CI Homonyme' => 'private' ) as $path => $kind ) {
    $album_id = WPD_Cache::resolve_album_id( $path );
    $assert( $fixture[ $kind ] === $album_id, 'Le chemin explicite doit sélectionner le bon album.' );
    $images = WPD_Cache::get_album_images( $album_id );
    $assert( is_array( $images ) && array( $fixture[ $kind . 'Image' ] ) === array_map( 'intval', array_column( $images, 'id' ) ),
        'L’image témoin doit provenir uniquement de l’album choisi.' );
}
$empty = WPD_Cache::get_album_images( $fixture['empty'] );
$assert( array() === $empty, 'Un album privé vide autorisé doit rester vide.' );
echo 'REAL_PIWIGO_HOMONYMS=PASS ' . wp_json_encode( array(
    'piwigo' => getenv( 'WPD_PIWIGO_TAG' ), 'api_order' => $ids,
    'service_user' => 'ci_reader', 'ambiguous_image_calls' => 0,
    'private_image_positive_control' => true, 'empty_private' => true,
) ) . "\n";

// Pages synthétiques limitées au WordPress jetable, pour une lecture anonyme HTTP.
$pages = array();
foreach ( array(
    'ambiguous' => 'CI Homonyme',
    'public' => '/CI Homonyme',
    'private' => '/CI Private Parent/CI Homonyme',
) as $name => $album ) {
    $id = wp_insert_post( array(
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'CI ' . $name,
        'post_content' => '[piwigo album="' . $album . '"]',
    ), true );
    $assert( ! is_wp_error( $id ), 'Création de la page synthétique impossible.' );
    $pages[ $name ] = $id;
}
file_put_contents( getenv( 'WPD_LAB' ) . '/pages-security.json', wp_json_encode( $pages ) );
