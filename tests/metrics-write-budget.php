<?php
/** Les visites servies depuis le cache n'écrivent pas toutes en base. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function get_transient( $key ) { return $GLOBALS['stored'] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['stored'] = $value; ++$GLOBALS['writes']; return true; }
function sanitize_key( $value ) { return $value; }
function sanitize_text_field( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
require ABSPATH . 'includes/class-wpd-api-metrics.php';
$GLOBALS['writes'] = 0;
for ( $i = 0; $i < 100; ++$i ) {
    $property = new ReflectionProperty( WPD_Api_Metrics::class, 'cache_hits' );
    $property->setValue( null, 0 );
    WPD_Api_Metrics::cache_hit( 'transient' );
    WPD_Api_Metrics::persist();
}
if ( 1 !== $GLOBALS['writes'] ) { throw new RuntimeException( 'Trop d’écritures pour des pages en cache.' ); }
WPD_Api_Metrics::api_call( 'pwg.categories.getImages', 5.0, 502, 'Échec' );
WPD_Api_Metrics::persist();
if ( 2 !== $GLOBALS['writes'] ) { throw new RuntimeException( 'La nouvelle activité API doit être conservée immédiatement.' ); }
echo "Métriques : 100 visites en cache → 1 écriture ; erreur API conservée : PASS.\n";
