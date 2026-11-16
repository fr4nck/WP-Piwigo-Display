<?php
/** F-02 : erreurs locales limitées à la requête, erreurs amont toujours amorties. */
declare(strict_types=1);
require __DIR__ . '/fixtures/counterreview-bootstrap.php';

foreach ( array( false, true ) as $service ) {
    cr_reset();
    WPD_Service_Account::$configured = $service;
    $GLOBALS['albums'][1] = 1;
    // Le compte de service doit aussi préserver le quota avant son premier login.
    $budget = WPD_Request_Budget::for_endpoint( WPD_Settings::get_piwigo_url() );
    for ( $i = 0; $i < 32; ++$i ) { $budget->reserve_http_call(); }
    $first = WPD_Cache::get_album_images( 1, 1 );
    cr_check( is_wp_error( $first ) && 'wpd_api_budget_exceeded' === $first->get_error_code(), "service=$service quota identifiable" );
    cr_check( empty( array_filter( array_keys( $GLOBALS['transients'] ), static fn( $k ) => str_starts_with( $k, 'wpd_album_error_' ) ) ), "service=$service aucun transient d'erreur locale" );
    cr_next_request();
    $second = WPD_Cache::get_album_images( 1, 1 );
    cr_check( is_array( $second ) && 1 === count( $second ) && ! empty( $GLOBALS['calls'] ), "service=$service requête suivante immédiatement disponible" );
    cr_next_request();
    cr_check( is_array( WPD_Cache::get_album_images( 1, 1 ) ) && empty( $GLOBALS['calls'] ), "service=$service cache chaud conservé" );
}

cr_reset();
WPD_Service_Account::$configured = false;
$GLOBALS['post403'] = true;
$GLOBALS['albums'][1] = 1;
$budget = WPD_Request_Budget::for_endpoint( WPD_Settings::get_piwigo_url() );
for ( $i = 0; $i < 31; ++$i ) { $budget->reserve_http_call(); }
$result = WPD_Cache::get_album_images( 1, 1 );
cr_check( is_wp_error( $result ) && 'wpd_api_budget_exceeded' === $result->get_error_code(), 'Quota entre POST 403 et GET : identité locale préservée' );
cr_check( 1 === count( $GLOBALS['calls'] ), 'GET non émis après épuisement' );
cr_next_request();
cr_check( is_array( WPD_Cache::get_album_images( 1, 1 ) ), 'POST 403 puis GET autorisé à la requête suivante' );

cr_reset();
$GLOBALS['upstream_error'] = true;
$first = WPD_Cache::get_album_images( 1, 1 );
cr_next_request();
$second = WPD_Cache::get_album_images( 1, 1 );
cr_check( is_wp_error( $first ) && is_wp_error( $second ) && empty( $GLOBALS['calls'] ), 'Vraie panne amont : cache négatif conservé' );

// Le quota ne doit jamais servir de prétexte pour partager une réponse privée.
cr_reset();
$GLOBALS['albums'][1] = array( array( 'id' => 987, 'name' => 'PRIVE' ) );
cr_check( is_array( WPD_Cache::get_album_images( 1, 1 ) ), 'Cache privé préparé' );
$private_keys = array_keys( $GLOBALS['transients'] );
cr_next_request();
$GLOBALS['preview'] = true;
$GLOBALS['albums'][1] = array();
$public = WPD_Cache::get_album_images( 1, 1 );
cr_check( array() === $public && ! empty( $GLOBALS['calls'] ), 'Prévisualisation publique sans autorisation : aucune réutilisation privée' );
cr_check( count( array_diff( array_keys( $GLOBALS['transients'] ), $private_keys ) ) >= 1, 'Clés des contextes public et privé distinctes' );

// Une copie stale publique demeure utile lorsque le quota courant est épuisé.
cr_reset();
WPD_Service_Account::$configured = false;
$GLOBALS['albums'][1] = 1;
WPD_Cache::get_album_images( 1, 1 );
foreach ( array_keys( $GLOBALS['transients'] ) as $key ) {
    if ( str_starts_with( $key, 'wpd_album_' ) ) { unset( $GLOBALS['transients'][ $key ] ); }
}
cr_next_request();
$budget = WPD_Request_Budget::for_endpoint( WPD_Settings::get_piwigo_url() );
for ( $i = 0; $i < 32; ++$i ) { $budget->reserve_http_call(); }
cr_check( is_array( WPD_Cache::get_album_images( 1, 1 ) ) && empty( $GLOBALS['calls'] ), 'Stale public conservé après quota' );
exit( $GLOBALS['failures'] ? 1 : 0 );
