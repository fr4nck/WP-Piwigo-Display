<?php
/** Cache des noms d'albums, erreurs courtes et clients authentifiés en mémoire. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );
final class WP_Error {
    public function __construct( public string $code, public string $message ) {}
}
final class WPD_Settings {
    public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
    public static function get_cache_duration(): int { return 60; }
}
final class WPD_Service_Account {
    public static string $context = 'service-one';
    public static function is_configured(): bool { return true; }
    public static function get_context_hash(): string { return self::$context; }
}
class WPD_Api {
    public static int $categories_calls = 0;
    public static int $image_calls = 0;
    public function __construct( string $url ) {}
    public function get_all_categories(): array { ++self::$categories_calls; return array(); }
    public function get_images_from_album( int $album_id, int $max ) { ++self::$image_calls; return array(); }
}
final class WPD_Service_Api {
    public static int $instances = 0;
    public static int $categories_calls = 0;
    public static int $image_calls = 0;
    public static bool $fail = false;
    public static bool $categories_unavailable = false;
    public static array $categories = array( array( 'id' => 7, 'name' => 'Privé' ), array( 'id' => 77, 'name' => '#2024' ) );
    public function __construct( string $url ) { ++self::$instances; }
    public function get_all_categories() {
        ++self::$categories_calls;
        return self::$categories_unavailable ? new WP_Error( 'http', 'Service Piwigo indisponible' ) : self::$categories;
    }
    public function get_images_from_album( int $id, int $max ) {
        ++self::$image_calls;
        return self::$fail ? new WP_Error( 'http', 'secret-password=leak' ) : array( array( 'id' => $id ) );
    }
}
final class WPD_Api_Metrics {
    public static function cache_hit( string $source ): void {}
    public static function cache_miss( string $source ): void {}
}
function __( $value, $domain = '' ) { return $value; }
function untrailingslashit( $value ) { return rtrim( $value, '/\\' ); }
function sanitize_text_field( $value ) { return (string) $value; }
function absint( $value ) { return abs( (int) $value ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; $GLOBALS['ttls'][ $key ] = $ttl; return true; }
function wp_using_ext_object_cache() { return true; }
function wp_cache_add( ...$args ) { return true; }
function wp_cache_delete( ...$args ) { return true; }
function is_preview() { return $GLOBALS['preview'] ?? false; }
function current_user_can( $cap ) { return $GLOBALS['publisher'] ?? false; }
require ABSPATH . 'includes/class-wpd-cache.php';
function check( bool $value, string $message ): void { if ( ! $value ) { throw new RuntimeException( $message ); } }
function next_request(): void {
    foreach ( array( 'request_cache', 'api_clients' ) as $name ) {
        $property = new ReflectionProperty( WPD_Cache::class, $name );
        $property->setValue( null, array() );
    }
}
$GLOBALS['transients'] = array();
check( 7 === WPD_Cache::resolve_album_id( 'Privé' ), 'Résolution privée perdue.' );
check( is_wp_error( WPD_Cache::resolve_album_id( '#30' ) ), 'Un nom absent #30 ne doit pas etre converti en identifiant 30.' );
check( 77 === WPD_Cache::resolve_album_id( '#2024' ), 'Un album nommé #2024 ne doit pas être confondu avec l’ID 2024.' );
WPD_Cache::get_album_images( 1 );
WPD_Cache::get_album_images( 2 );
check( 1 === WPD_Service_Api::$instances, 'Connexion répétée dans la même requête PHP.' );
next_request();
check( 7 === WPD_Cache::resolve_album_id( 'Privé' ), 'Cache de résolution perdu.' );
check( 1 === WPD_Service_Api::$categories_calls, 'Les noms d’albums ne sont pas mis en cache entre les requêtes.' );
check( is_wp_error( WPD_Cache::resolve_album_id( 'Privé', 'https://other.example.test' ) ), 'Le cache privé fuit vers une autre URL.' );
WPD_Service_Api::$fail = true;
$error = WPD_Cache::get_album_images( 99 );
$before = WPD_Service_Api::$image_calls;
check( is_wp_error( $error ) && ! str_contains( $error->message, 'secret' ), 'Erreur brute conservée dans le cache.' );
next_request();
check( is_wp_error( WPD_Cache::get_album_images( 99 ) ), 'La panne ne reste pas mémorisée.' );
check( $before === WPD_Service_Api::$image_calls, 'La panne relance immédiatement Piwigo.' );
foreach ( $GLOBALS['transients'] as $key => $value ) {
    if ( str_starts_with( $key, 'wpd_album_error_' ) ) {
        check( 15 === $GLOBALS['ttls'][ $key ], 'Durée de panne incorrecte.' );
        unset( $GLOBALS['transients'][ $key ] );
    }
    check( ! str_starts_with( $key, 'wpd_stale_' ), 'Un album privé écrit une copie stale.' );
}
next_request();
WPD_Service_Api::$fail = false;
check( is_array( WPD_Cache::get_album_images( 99 ) ), 'La récupération après expiration échoue.' );
$GLOBALS['preview'] = true;
next_request();
check( is_wp_error( WPD_Cache::resolve_album_id( 'Privé' ) ), 'Un contributeur prévisualise un album privé.' );
$GLOBALS['publisher'] = true;
check( 7 === WPD_Cache::resolve_album_id( 'Privé' ), 'Un auteur ne peut plus prévisualiser les albums autorisés.' );
// N1: A renamed #name must never silently turn into another album ID.
$GLOBALS['preview'] = false;
$GLOBALS['publisher'] = false;
WPD_Service_Api::$categories = array(
    array( 'id' => 5, 'name' => 'Album 5 privé' ),
    array( 'id' => 70, 'name' => '#5' ),
    array( 'id' => 77, 'name' => '#2024' )
);
$GLOBALS['transients'] = array();
next_request();
check( 70 === WPD_Cache::resolve_album_id( '#5' ), 'Le nom literal #5 doit initialement designer l album 70.' );
WPD_Service_Api::$categories = array(
    array( 'id' => 5, 'name' => 'Album 5 privé' ),
    array( 'id' => 70, 'name' => 'Renommé' ),
    array( 'id' => 77, 'name' => '#2024' )
);
$GLOBALS['transients'] = array();
next_request();
check( is_wp_error( WPD_Cache::resolve_album_id( '#5' ) ), 'P1: apres renommage, #5 ne doit jamais exposer l album prive 5.' );
check( 5 === WPD_Cache::resolve_album_id( '5' ), 'Un identifiant 5 explicitement fourni doit rester compatible.' );
check( is_wp_error( WPD_Cache::resolve_album_id( '#7' ) ), 'La notation ambigue #7 ne doit pas contourner la recherche de nom.' );
check( 77 === WPD_Cache::resolve_album_id( '#2024' ), 'Les noms literaux #2024 restent utilisables.' );
// A missing #name must never fall back to a numeric private album during API failure.
// This catches reintroductions of the former dangerous fallback in error paths.
WPD_Service_Api::$categories_unavailable = true;
$GLOBALS['transients'] = array();
next_request();
check( is_wp_error( WPD_Cache::resolve_album_id( '#5' ) ),
    'P1: une panne de catalogue ne doit jamais convertir #5 en album prive ID 5.' );
check( 5 === WPD_Cache::resolve_album_id( '5' ),
    'Les identifiants numeriques explicites restent inchanges durant une panne.' );
WPD_Service_Api::$categories_unavailable = false;
echo "Cache : résolution, panne, contexte privé, rétablissement et session locale : PASS.\n";
