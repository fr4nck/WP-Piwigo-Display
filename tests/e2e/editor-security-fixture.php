<?php
/**
 * Fixture strictement réservée au laboratoire ; jamais livrée avec le plugin.
 */
if ( '1' !== getenv( 'WPD_EDITOR_SECURITY_LAB' ) || ! getenv( 'WPD_LAB' ) ) {
    return;
}

add_filter( 'http_request_host_is_external', static function ( $allowed, $host, $url ) {
    return '127.0.0.1' === $host && 8091 === wp_parse_url( $url, PHP_URL_PORT ) ? true : $allowed;
}, 10, 3 );
add_filter( 'http_allowed_safe_ports', static function ( $ports, $host ) {
    if ( '127.0.0.1' === $host ) {
        $ports[] = 8091;
    }
    return $ports;
}, 10, 2 );
add_action( 'init', static function () {
    if ( PHP_SAPI !== 'cli' ) {
        header( 'X-WPD-CI-Piwigo-Requests: 0' );
    }
} );
add_filter( 'http_request_args', static function ( $args, $url ) {
    if ( str_starts_with( $url, 'https://127.0.0.1:8091/ws.php?' ) ) {
        if ( true !== $args['sslverify'] ) {
            throw new RuntimeException( 'La vérification TLS doit rester active.' );
        }
        $args['sslcertificates'] = getenv( 'WPD_LAB' ) . '/ca.pem';
        $GLOBALS['wpd_ci_piwigo_calls'] = ( $GLOBALS['wpd_ci_piwigo_calls'] ?? 0 ) + 1;
        if ( PHP_SAPI !== 'cli' ) {
            header( 'X-WPD-CI-Piwigo-Requests: ' . $GLOBALS['wpd_ci_piwigo_calls'] );
        }
    }
    return $args;
}, 20, 2 );

add_action( 'wp_ajax_wpd_ci_expired_nonce', static function () {
    check_ajax_referer( 'wpd_get_albums', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(), 403 );
    }
    // Même utilisateur, même session et vrai sel WordPress, mais tick expiré.
    $tick = wp_nonce_tick( 'wpd_get_albums' ) - 2;
    $expired = substr( wp_hash( $tick . '|wpd_get_albums|' . get_current_user_id() . '|' . wp_get_session_token(), 'nonce' ), -12, 10 );
    if ( false !== wp_verify_nonce( $expired, 'wpd_get_albums' ) ) {
        throw new RuntimeException( 'Le nonce témoin doit être réellement expiré.' );
    }
    wp_send_json_success( array( 'nonce' => $expired ) );
} );
