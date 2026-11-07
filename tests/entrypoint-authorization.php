<?php
/** Exécute les contrôles d'accès de chaque point d'entrée sensible. */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
final class RequestStopped extends RuntimeException {}
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return $text; }
function current_user_can( $capability ): bool {
    return isset( $GLOBALS['capabilities'] ) ? in_array( $capability, $GLOBALS['capabilities'], true ) : $GLOBALS['authorized'];
}
function wp_die( $message ): void { throw new RequestStopped( 'capability' ); }
function wp_send_json_error( $data, $status ): void { throw new RequestStopped( 'json:' . $status ); }
function check_ajax_referer( $action, $field ): void {
    $GLOBALS['nonce_action'] = $action;
    if ( ! $GLOBALS['valid_nonce'] ) { throw new RequestStopped( 'nonce' ); }
}
function check_admin_referer( $action ): void {
    $GLOBALS['nonce_action'] = $action;
    if ( ! $GLOBALS['valid_nonce'] ) { throw new RequestStopped( 'nonce' ); }
}

require ABSPATH . 'includes/class-wpd-plugin.php';
require ABSPATH . 'includes/class-wpd-service-account.php';
require ABSPATH . 'includes/class-wpd-diagnostic.php';
final class WPD_Settings {
    public static function get_piwigo_url(): string { return ''; }
}
$plugin = ( new ReflectionClass( WPD_Plugin::class ) )->newInstanceWithoutConstructor();
$handlers = array(
    array( array( $plugin, 'ajax_get_albums' ), 'wpd_get_albums', 'json:403' ),
    array( array( WPD_Service_Account::class, 'ajax_get_albums' ), 'wpd_get_albums', 'json:403' ),
    array( array( $plugin, 'clear_cache' ), 'wpd_clear_cache', 'capability' ),
    array( array( $plugin, 'test_connection' ), 'wpd_test_connection', 'capability' ),
    array( array( WPD_Service_Account::class, 'test_connection' ), 'wpd_test_service_account', 'capability' ),
    array( array( WPD_Diagnostic::class, 'export' ), 'wpd_export_diagnostic', 'capability' ),
);
foreach ( $handlers as list( $handler, $action, $denied ) ) {
    foreach ( array( false, true ) as $authorized ) {
        $GLOBALS['authorized'] = $authorized;
        $GLOBALS['valid_nonce'] = ! $authorized;
        $GLOBALS['nonce_action'] = '';
        try {
            call_user_func( $handler );
            throw new RuntimeException( 'Le gestionnaire a poursuivi une requête interdite.' );
        } catch ( RequestStopped $error ) {
            $expected = $authorized ? 'nonce' : $denied;
            if ( $expected !== $error->getMessage() || ( $authorized && $action !== $GLOBALS['nonce_action'] ) ) {
                throw new RuntimeException( 'Protection incorrecte : ' . $action );
            }
        }
    }
}
$GLOBALS['capabilities'] = array( 'edit_posts' );
$GLOBALS['valid_nonce'] = true;
foreach ( array( array( $plugin, 'ajax_get_albums' ), array( WPD_Service_Account::class, 'ajax_get_albums' ) ) as $index => $handler ) {
    try {
        call_user_func( $handler );
        throw new RuntimeException( 'Le gestionnaire ne termine pas sa réponse.' );
    } catch ( RequestStopped $error ) {
        if ( ( 0 === $index ? 'json:400' : 'json:403' ) !== $error->getMessage() ) {
            throw new RuntimeException( 'Le contributeur doit conserver le sélecteur public, sans accès privé.' );
        }
    }
}
echo "Autorisations : 6 gestionnaires, 12 refus et 2 contrôles contributeur exécutés : PASS.\n";
