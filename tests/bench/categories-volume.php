<?php
/** Mesure F-03, processus isolé : php -d memory_limit=128M ce-fichier.php JSON public|service. */
declare(strict_types=1);
require dirname( __DIR__ ) . '/fixtures/counterreview-bootstrap.php';
cr_reset();
$mode = $argv[2] ?? 'public';
WPD_Service_Account::$configured = 'service' === $mode;
$started = microtime( true );
$GLOBALS['category_body'] = file_get_contents( $argv[1] );
$client = 'service' === $mode ? new WPD_Service_Api( WPD_Settings::get_piwigo_url() ) : new WPD_Api( WPD_Settings::get_piwigo_url() );
$categories = $client->get_all_categories();
if ( ! is_array( $categories ) || ! $categories ) { throw new RuntimeException( 'Catégories non chargées.' ); }
$last = $categories[ count( $categories ) - 1 ];
$decode_time = microtime( true ) - $started;
$started = microtime( true );
for ( $i = 0; $i < 10; ++$i ) {
    $resolved = WPD_Cache::resolve_album_id( (string) $last['name'] );
    if ( (int) $last['id'] !== $resolved ) { throw new RuntimeException( 'Nom perdu.' ); }
}
$names_time = microtime( true ) - $started;
$method = new ReflectionMethod( WPD_Cache::class, 'build_category_path' );
$names = array_column( $categories, 'name', 'id' );
$path = $method->invoke( null, $last, $names );
unset( $names );
$started = microtime( true );
$resolved = WPD_Cache::resolve_album_id( $path );
if ( (int) $last['id'] !== $resolved ) { throw new RuntimeException( 'Chemin perdu.' ); }
echo json_encode( array( 'mode' => $mode, 'categories' => count( $categories ), 'bytes' => strlen( $GLOBALS['category_body'] ), 'decode_seconds' => $decode_time, 'ten_names_seconds' => $names_time, 'path_seconds' => microtime( true ) - $started, 'peak_bytes' => memory_get_peak_usage( true ), 'memory_limit' => ini_get( 'memory_limit' ) ) ) . PHP_EOL;
