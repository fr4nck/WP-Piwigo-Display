<?php
/** Tests exécutables du diagnostic HTTP et de son état réel. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['configured_url'] = '';
$GLOBALS['metrics_transients'] = array();
function __( $text, $domain = '' ): string { return $text; }
function sanitize_text_field( $text ): string { return trim( strip_tags( (string) $text ) ); }
function sanitize_key( $text ): string { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $text ) ); }
function absint( $value ): int { return abs( (int) $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function untrailingslashit( $value ): string { return rtrim( (string) $value, '/\\' ); }
function wp_parse_str( $string, &$values ): void { parse_str( $string, $values ); }
function is_wp_error( $response ): bool { return $response instanceof WP_Error; }
function wp_remote_retrieve_response_code( $response ): int { return (int) $response['code']; }
function wp_remote_retrieve_body( $response ): string { return (string) $response['body']; }
function get_transient( $key ) { return $GLOBALS['metrics_transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['metrics_transients'][ $key ] = $value; return true; }
final class WPD_Settings {
    public static function get_piwigo_url(): string { return $GLOBALS['configured_url']; }
}
require ABSPATH . 'includes/class-wpd-api-metrics.php';
function check( bool $ok, string $message ): void { if ( ! $ok ) { throw new RuntimeException( $message ); } }

$health = new ReflectionMethod( WPD_Api_Metrics::class, 'health_status' );
$summary = WPD_Api_Metrics::summary();
check( false !== strpos( $health->invoke( null, $summary ), 'non configurée' ), 'Pas de santé verte sans URL.' );
$GLOBALS['configured_url'] = 'https://piwigo.example.test';
check( false !== strpos( $health->invoke( null, $summary ), 'Aucun appel' ), 'Pas de santé verte sans appel.' );

$url = 'https://piwigo.example.test/ws.php?format=json';
$args = array( 'body' => 'method=pwg.categories.getList&password=NE_JAMAIS_JOURNALISER' );
WPD_Api_Metrics::start_http( $args, $url );
WPD_Api_Metrics::observe_http( array( 'code' => 200, 'body' => '{"stat":"ok"}' ), 'response', '', $args, $url );
$summary = WPD_Api_Metrics::summary();
check( 'pwg.categories.getList' === $summary['last_method'], 'La méthode doit venir du corps URL-encodé.' );
check( false === strpos( json_encode( $summary ), 'NE_JAMAIS_JOURNALISER' ), 'Aucun secret dans les métriques.' );
check( '' === $summary['last_error'], 'Une réponse OK ne doit pas signaler une erreur.' );

WPD_Api_Metrics::observe_http( array( 'code' => 200, 'body' => '{"stat":"fail","message":"secret distant"}' ), 'response', '', $args, $url );
$summary = WPD_Api_Metrics::summary();
check( '' !== $summary['last_error'], 'HTTP 200 avec stat fail doit signaler une erreur API.' );
check( false === strpos( json_encode( $summary ), 'secret distant' ), 'Le texte distant ne doit pas être conservé.' );
WPD_Api_Metrics::persist();

WPD_Api_Metrics::observe_http( array( 'code' => 200, 'body' => '{"stat":"ok"}' ), 'response', '', $args, $url );
WPD_Api_Metrics::persist();
check( '' === WPD_Api_Metrics::summary()['last_error'], 'Le succès doit effacer la dernière erreur.' );
check( false !== strpos( $health->invoke( null, WPD_Api_Metrics::summary() ), 'opérationnels' ), 'La santé doit redevenir verte après succès.' );
// La sonde du diagnostic interroge Piwigo en GET : la méthode est dans l'URL.
$probe_url = 'https://piwigo.example.test/ws.php?format=json&method=pwg.session.getStatus';
WPD_Api_Metrics::start_http( array(), $probe_url );
WPD_Api_Metrics::observe_http( array( 'code' => 200, 'body' => '{"stat":"ok"}' ), 'response', '', array(), $probe_url );
check( 'pwg.session.getStatus' === WPD_Api_Metrics::summary()['last_method'], 'La méthode doit venir de l’URL des appels GET.' );

echo "Diagnostic : méthode API, erreur Piwigo, effacement et URL absente PASS.\n";
