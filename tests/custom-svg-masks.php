<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$sanitizer = file_get_contents( __DIR__ . '/../includes/class-wpd-svg-mask-sanitizer.php' );
$library   = file_get_contents( __DIR__ . '/../includes/class-wpd-custom-svg-masks.php' );
$css       = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-custom-masks.css' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( $bootstrap, 'class-wpd-svg-mask-sanitizer.php' ), 'Le sanitizer SVG doit être chargé.' );
$assert( false !== strpos( $bootstrap, 'class-wpd-custom-svg-masks.php' ), 'La bibliothèque de masques doit être chargée.' );
$assert( false !== strpos( $bootstrap, 'WPD_Custom_SVG_Masks::register()' ), 'La bibliothèque de masques doit être enregistrée.' );
$assert( false !== strpos( $sanitizer, 'LIBXML_NONET' ), 'Le parseur XML doit interdire le réseau.' );
$assert( false !== strpos( $sanitizer, '<!DOCTYPE' ) && false !== strpos( $sanitizer, '<!ENTITY' ), 'DOCTYPE et ENTITY doivent être explicitement refusés.' );
$assert( false !== strpos( $sanitizer, "'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'polygon', 'polyline'" ), 'Le sanitizer doit utiliser une liste blanche réduite.' );
$assert( false !== strpos( $sanitizer, 'javascript:' ) && false !== strpos( $sanitizer, 'data:' ) && false !== strpos( $sanitizer, 'https?:' ), 'Les références actives ou distantes doivent être refusées.' );
$assert( false !== strpos( $library, "current_user_can( 'manage_options' )" ), 'Import et suppression doivent vérifier une capacité.' );
$assert( false !== strpos( $library, "check_admin_referer( 'wpd_upload_svg_mask' )" ), 'L’import doit vérifier un nonce.' );
$assert( false !== strpos( $library, "check_admin_referer( 'wpd_delete_svg_mask' )" ), 'La suppression doit vérifier un nonce.' );
$assert( false !== strpos( $library, "'svg' !== strtolower( pathinfo" ), 'L’extension SVG doit être vérifiée.' );
$assert( false !== strpos( $library, 'finfo_open' ), 'Le MIME doit être vérifié quand Fileinfo est disponible.' );
$assert( false !== strpos( $library, 'WPD_SVG_Mask_Sanitizer::sanitize' ), 'Seule une version sanitizée doit entrer dans la bibliothèque.' );
$assert( false !== strpos( $library, "update_option( self::OPTION_NAME, \$library, false )" ), 'La bibliothèque doit être stockée localement sans autoload.' );
$assert( false !== strpos( $library, "'data:image/svg+xml,' . rawurlencode" ), 'Le rendu doit utiliser uniquement le SVG sanitizé local.' );
$assert( false !== strpos( $css, '-webkit-mask-image: var(--wpd-custom-svg-mask)' ) && false !== strpos( $css, 'mask-image: var(--wpd-custom-svg-mask)' ), 'Le rendu doit utiliser CSS mask-image avec compatibilité WebKit.' );
$assert( false !== strpos( $css, '@supports not' ), 'Un fallback sans mask-image doit exister.' );

fwrite( STDOUT, "Sanitized custom SVG mask pipeline: OK\n" );
