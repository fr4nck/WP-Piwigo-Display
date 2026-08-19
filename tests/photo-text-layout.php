<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$module    = file_get_contents( __DIR__ . '/../includes/class-wpd-photo-text.php' );
$css       = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-photo-text.css' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( $bootstrap, 'class-wpd-photo-text.php' ), 'Le moteur Texte rempli de photos doit être chargé.' );
$assert( false !== strpos( $bootstrap, 'WPD_Photo_Text::register()' ), 'Le moteur Texte rempli de photos doit être enregistré.' );
$assert( false !== strpos( $module, "=== 'photo-text'" ), 'type=photo-text doit activer le layout photo-text.' );
$assert( false !== strpos( $module, '<clipPath' ) && false !== strpos( $module, '<image href=' ), 'Le rendu doit utiliser un masque SVG contenant plusieurs photos.' );
$assert( false !== strpos( $module, 'wpd-photo-text-semantic' ), 'Le texte doit rester présent sous forme sémantique.' );
$assert( false !== strpos( $module, 'aria-hidden="true"' ), 'Le SVG décoratif doit être masqué aux technologies d’assistance.' );
$assert( false !== strpos( $module, 'seeded_urls' ) && false !== strpos( $module, 'crc32( $seed )' ), 'La composition doit rester déterministe pour une même graine.' );
$assert( false !== strpos( $module, "'system' => 'system-ui" ), 'Une pile système locale doit être disponible.' );
$assert( false === strpos( $module, 'fonts.googleapis.com' ) && false === strpos( $module, 'use.typekit.net' ), 'Aucune police distante tierce ne doit être chargée.' );
$assert( false !== strpos( $css, '.wpd-photo-text-semantic' ), 'Le texte sémantique doit disposer d’un masquage visuel accessible.' );

fwrite( STDOUT, "Photo-filled semantic text layout: OK\n" );
