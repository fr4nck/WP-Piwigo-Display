<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$module    = file_get_contents( __DIR__ . '/../includes/class-wpd-photo-text.php' );
$css       = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-photo-text.css' );
$block     = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$controls  = file_get_contents( __DIR__ . '/../blocks/piwigo/masonry-controls.js' );
$block_php = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );
$classic   = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$composer  = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-composer-parity.js' );

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
$assert( false !== strpos( $block, '"photoText"' ) && false !== strpos( $block, '"photoTextMaxImages"' ), 'Les attributs Gutenberg du texte photo doivent être déclarés.' );
$assert( false !== strpos( $controls, "value: 'photo-text'" ), 'Gutenberg doit proposer le mode Texte rempli de photos.' );
$assert( false !== strpos( $controls, 'Même graine + mêmes photos = même remplissage.' ), 'Gutenberg doit expliquer la stabilité du remplissage.' );
$assert( 1 === preg_match( "/'photoText'\\s*=>\\s*'photo_text'/", $block_php ), 'Le bloc doit transmettre le texte au shortcode.' );
$assert( false !== strpos( $classic, 'Texte rempli de photos' ) && false !== strpos( $classic, "type === 'photo-text'" ), 'Classic Editor doit proposer et générer le mode Texte rempli de photos.' );
$assert( false !== strpos( $classic, 'photo_text_outline_color' ), 'Classic Editor doit exposer les réglages du texte photo.' );
$assert( false !== strpos( $composer, "[ 'photo-text', 'Texte rempli de photos' ]" ), 'Le composeur doit proposer le mode Texte rempli de photos.' );
$assert( false !== strpos( $composer, "type.value === 'photo-text'" ) && false !== strpos( $composer, 'photo_text_outline_color' ), 'Le composeur doit générer les attributs du texte photo.' );
$assert( false !== strpos( $composer, "[ 'collage', 'Collage / Pêle-mêle' ]" ) && false !== strpos( $composer, 'wpd-c-shape' ), 'Le mode Texte photo ne doit pas supprimer les contrôles Core existants.' );

fwrite( STDOUT, "Photo-filled semantic text layout and editor parity: OK\n" );
