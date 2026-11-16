<?php
/** F-01 : limite globale, ordre, doublons, profondeur et budget des deux clients. */
declare(strict_types=1);
require __DIR__ . '/fixtures/counterreview-bootstrap.php';

foreach ( array( WPD_Api::class, WPD_Service_Api::class ) as $class ) {
    for ( $depth = 1; $depth <= 9; ++$depth ) {
        cr_reset();
        $GLOBALS['albums'][1] = 10001;
        $client = new $class( WPD_Settings::get_piwigo_url() );
        $images = $client->get_images_from_album_recursive( 1, 1, $depth );
        cr_check( is_array( $images ) && array( 1 ) === array_column( $images, 'id' ), "$class depth=$depth 10001 images max=1" );
        cr_check( 1 === $GLOBALS['rows'] && ( WPD_Api::class === $class ? 2 : 3 ) === count( $GLOBALS['calls'] ), "$class depth=$depth une seule ligne, aucun descendant inutile" );
    }

    cr_reset();
    $GLOBALS['albums'] = array( 1 => array( array( 'id' => 1 ) ), 2 => array( array( 'id' => 1 ), array( 'id' => 1 ), array( 'id' => 2 ), array( 'id' => 3 ) ), 3 => array( array( 'id' => 4 ) ), 4 => array( array( 'id' => 999 ) ) );
    $images = ( new $class( WPD_Settings::get_piwigo_url() ) )->get_images_from_album_recursive( 1, 3, 1 );
    cr_check( is_array( $images ) && array( 1, 2, 3 ) === array_column( $images, 'id' ), "$class doublons inter/intra albums, ordre et quota restant" );
    $calls = array_values( array_filter( $GLOBALS['calls'], static fn( $c ) => 'pwg.categories.getImages' === $c['body']['method'] ) );
    cr_check( array( 1, 2, 2 ) === array_column( array_column( $calls, 'body' ), 'cat_id' ), "$class pagination continue après doublons et respecte depth=1" );
    cr_check( array( 3, 2, 2 ) === array_column( array_column( $calls, 'body' ), 'per_page' ), "$class taille de page fixe par album, bornée au restant initial" );

    cr_reset();
    $GLOBALS['albums'] = array( 1 => array( array( 'name' => 'sans id' ) ), 2 => array( array( 'name' => 'sans id' ), array( 'name' => 'nouvelle' ) ), 3 => array( array( 'id' => 3 ) ) );
    $images = ( new $class( WPD_Settings::get_piwigo_url() ) )->get_images_from_album_recursive( 1, 3, 2 );
    cr_check( is_array( $images ) && 3 === count( $images ) && 'sans id' === $images[0]['name'] && 'nouvelle' === $images[1]['name'] && 3 === $images[2]['id'], "$class clé historique sans ID et descendants" );

    cr_reset();
    $GLOBALS['albums'][1] = 10001;
    $result = ( new $class( WPD_Settings::get_piwigo_url() ) )->get_images_from_album_recursive( 1, 0, 2 );
    cr_check( is_wp_error( $result ) && 'wpd_api_budget_exceeded' === $result->get_error_code(), "$class max=0 conserve le plafond, aucune liste partielle" );
    cr_check( count( $GLOBALS['calls'] ) <= 32, "$class appels bornés" );

    cr_reset();
    $GLOBALS['albums'][1] = 1;
    $budget = WPD_Request_Budget::for_endpoint( WPD_Settings::get_piwigo_url() );
    for ( $i = 0; $i < 32; ++$i ) { $budget->reserve_http_call(); }
    $result = ( new $class( WPD_Settings::get_piwigo_url() ) )->get_images_from_album_recursive( 1, 1, 2 );
    cr_check( is_wp_error( $result ) && empty( $GLOBALS['calls'] ), "$class quota épuisé : aucun HTTP supplémentaire" );

    if ( WPD_Service_Api::class === $class ) {
        cr_reset();
        $GLOBALS['albums'][1] = 1;
        ( new $class( WPD_Settings::get_piwigo_url() ) )->get_images_from_album_recursive( 1, 1, 2 );
        foreach ( $GLOBALS['calls'] as $call ) {
            cr_check( 'POST' === $call['verb'] && true === $call['args']['sslverify'] && 0 === $call['args']['redirection'] && str_starts_with( $call['url'], 'https://photos.example.test/ws.php?' ), 'Service HTTPS/TLS/POST sans redirection' );
        }
    }
}
exit( $GLOBALS['failures'] ? 1 : 0 );

