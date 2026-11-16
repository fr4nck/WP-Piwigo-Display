<?php
/**
 * Un nonce invalide doit arrêter les deux routes avant tout accès à Piwigo.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

final class WPD_Nonce_Rejection extends RuntimeException {
    public function __construct( public array $data, public int $status ) {
        parent::__construct( 'JSON response' );
    }
}

function check_ajax_referer( $action, $argument, $stop = true ) {
    if ( 'wpd_get_albums' !== $action || 'nonce' !== $argument || false !== $stop ) {
        throw new RuntimeException( 'La vérification doit permettre une erreur JSON explicite.' );
    }
    return false;
}

function __( $message, $domain ) {
    return $message;
}

function wp_send_json_error( $data, $status = null ) {
    throw new WPD_Nonce_Rejection( $data, $status );
}

require_once dirname( __DIR__ ) . '/includes/class-wpd-plugin.php';
require_once dirname( __DIR__ ) . '/includes/class-wpd-service-account.php';

$plugin = ( new ReflectionClass( WPD_Plugin::class ) )->newInstanceWithoutConstructor();
foreach ( array( array( $plugin, 'ajax_get_albums' ), array( WPD_Service_Account::class, 'ajax_get_albums' ) ) as $handler ) {
    try {
        $handler();
        throw new RuntimeException( 'Un nonce invalide a été accepté.' );
    } catch ( WPD_Nonce_Rejection $response ) {
        if ( 403 !== $response->status || 'wpd_nonce_expired' !== ( $response->data['code'] ?? '' )
            || false === strpos( $response->data['message'] ?? '', 'rechargez la page' ) ) {
            throw new RuntimeException( 'Le conseil de récupération du nonce est absent.' );
        }
    }
}

echo "Album picker nonce: public/service rejection before any API call: PASS\n";
