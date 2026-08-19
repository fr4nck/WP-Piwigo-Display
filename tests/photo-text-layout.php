<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$module    = file_get_contents( __DIR__ . '/../includes/class-wpd-photo-text.php' );
$fonts     = file_get_contents( __DIR__ . '/../includes/class-wpd-user-fonts.php' );
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
$assert( false !== strpos( $bootstrap, 'class-wpd-user-fonts.php' ), 'Le résolveur de polices locales doit être chargé.' );
$assert( false !== strpos( $module, "=== 'photo-text'" ), 'type=photo-text doit activer le layout photo-text.' );
$assert( false !== strpos( $module, '<clipPath' ) && false !== strpos( $module, '<image href=' ), 'Le rendu doit utiliser un masque SVG contenant plusieurs photos.' );
$assert( false !== strpos( $module, 'wpd-photo-text-semantic' ), 'Le texte doit rester présent sous forme sémantique.' );
$assert( false !== strpos( $module, 'aria-hidden="true"' ), 'Le SVG décoratif doit être masqué aux technologies d’assistance.' );
$assert( false !== strpos( $module, 'seeded_urls' ) && false !== strpos( $module, 'crc32( $seed )' ), 'La composition doit rester déterministe pour une même graine.' );
$assert( false !== strpos( $module, 'WPD_User_Fonts::font_stack' ), 'Le moteur Texte-photo doit déléguer la résolution des polices.' );
$assert( false !== strpos( $fonts, "'system' => 'system-ui" ), 'Une pile système locale doit être disponible.' );
$assert( false !== strpos( $fonts, "'serif'  => 'Georgia" ) && false !== strpos( $fonts, "'mono'   => 'ui-monospace" ), 'Les piles serif et monospace locales doivent rester disponibles.' );
$assert( false === strpos( $module, 'fonts.googleapis.com' ) && false === strpos( $module, 'use.typekit.net' ) && false === strpos( $fonts, 'fonts.googleapis.com' ) && false === strpos( $fonts, 'use.typekit.net' ), 'Aucune police distante tierce ne doit être chargée.' );
$assert( false !== strpos( $module, "photo_text_size']" ) && false !== strpos( $module, "photo_text_letter_spacing']" ), 'Le moteur doit exposer taille et interlettrage.' );
$assert( false !== strpos( $module, "photo_text_max_width']" ) && false !== strpos( $module, "photo_text_align']" ), 'Le moteur doit exposer largeur maximale et alignement.' );
$assert( false !== strpos( $module, 'letter-spacing:%4$dpx' ), 'L’interlettrage doit modifier réellement le SVG.' );
$assert( false === strpos( $module, 'textLength="1100"' ), 'Le SVG ne doit plus forcer une largeur fixe qui neutralise la taille et l’interlettrage.' );
$assert( false !== strpos( $module, 'text_position( $align )' ) && false !== strpos( $module, "'anchor' => 'start'" ) && false !== strpos( $module, "'anchor' => 'end'" ), 'L’alignement doit modifier la position SVG.' );
$assert( false !== strpos( $module, "photo_text_fill_mode']" ) && false !== strpos( $module, "photo_text_density']" ), 'Le moteur doit exposer mode de remplissage et densité.' );
$assert( false !== strpos( $module, "array( 'grid', 'masonry', 'collage' )" ), 'Les trois modes de remplissage doivent être autorisés.' );
$assert( false !== strpos( $module, 'grid_tiles(' ) && false !== strpos( $module, 'masonry_tiles(' ) && false !== strpos( $module, 'collage_tiles(' ), 'Chaque mode de remplissage doit disposer de son moteur déterministe.' );
$assert( false !== strpos( $module, "':masonry:'" ) && false !== strpos( $module, "':collage:'" ), 'Masonry et pêle-mêle doivent rester déterministes pour une même graine.' );
$assert( false !== strpos( $module, 'photo_text_rotation' ) && false !== strpos( $module, 'photo_text_spread' ) && false !== strpos( $module, 'signed_value(' ), 'Le pêle-mêle doit exposer rotation et dispersion.' );
$assert( false !== strpos( $css, '.wpd-photo-text-semantic' ), 'Le texte sémantique doit disposer d’un masquage visuel accessible.' );
$assert( false !== strpos( $css, '--wpd-photo-text-max-width' ) && false !== strpos( $css, '.wpd-photo-text-align-right' ), 'La largeur responsive et l’alignement du conteneur doivent être stylés.' );
$assert( false !== strpos( $block, '"photoText"' ) && false !== strpos( $block, '"photoTextMaxImages"' ), 'Les attributs Gutenberg du texte photo doivent être déclarés.' );
$assert( false !== strpos( $block, '"photoTextSize"' ) && false !== strpos( $block, '"photoTextLetterSpacing"' ) && false !== strpos( $block, '"photoTextMaxWidth"' ) && false !== strpos( $block, '"photoTextAlign"' ), 'Les attributs typographiques Gutenberg doivent être déclarés.' );
$assert( false !== strpos( $block, '"photoTextFillMode"' ) && false !== strpos( $block, '"photoTextDensity"' ) && false !== strpos( $block, '"photoTextRotation"' ) && false !== strpos( $block, '"photoTextSpread"' ), 'Les attributs Gutenberg de remplissage doivent être déclarés.' );
$assert( false !== strpos( $controls, "value: 'photo-text'" ), 'Gutenberg doit proposer le mode Texte rempli de photos.' );
$assert( false !== strpos( $controls, 'Même graine + mêmes photos = même remplissage.' ), 'Gutenberg doit expliquer la stabilité du remplissage.' );
$assert( false !== strpos( $controls, 'Taille du texte' ) && false !== strpos( $controls, 'Interlettrage' ) && false !== strpos( $controls, 'Largeur maximale (%)' ) && false !== strpos( $controls, 'Alignement' ), 'Gutenberg doit exposer les réglages typographiques.' );
$assert( false !== strpos( $controls, 'Remplissage des lettres' ) && false !== strpos( $controls, 'Densité du remplissage (%)' ) && false !== strpos( $controls, 'Rotation du pêle-mêle' ), 'Gutenberg doit exposer les modes et réglages de remplissage.' );
$assert( 1 === preg_match( "/'photoText'\\s*=>\\s*'photo_text'/", $block_php ), 'Le bloc doit transmettre le texte au shortcode.' );
$assert( 1 === preg_match( "/'photoTextSize'\\s*=>\\s*'photo_text_size'/", $block_php ) && 1 === preg_match( "/'photoTextAlign'\\s*=>\\s*'photo_text_align'/", $block_php ), 'Le bloc doit transmettre les réglages typographiques au shortcode.' );
$assert( 1 === preg_match( "/'photoTextFillMode'\\s*=>\\s*'photo_text_fill_mode'/", $block_php ) && 1 === preg_match( "/'photoTextDensity'\\s*=>\\s*'photo_text_density'/", $block_php ), 'Le bloc doit transmettre les réglages de remplissage au shortcode.' );
$assert( false !== strpos( $classic, 'Texte rempli de photos' ) && false !== strpos( $classic, "type === 'photo-text'" ), 'Classic Editor doit proposer et générer le mode Texte rempli de photos.' );
$assert( false !== strpos( $classic, 'photo_text_outline_color' ), 'Classic Editor doit exposer les réglages du texte photo.' );
$assert( false !== strpos( $classic, 'photo_text_size' ) && false !== strpos( $classic, 'photo_text_letter_spacing' ) && false !== strpos( $classic, 'photo_text_max_width' ) && false !== strpos( $classic, 'photo_text_align' ), 'Classic Editor doit exposer la typographie Texte-photo.' );
$assert( false !== strpos( $classic, 'photo_text_fill_mode' ) && false !== strpos( $classic, 'photo_text_density' ) && false !== strpos( $classic, 'wpd-photo-text-collage-option' ), 'Classic Editor doit exposer les modes de remplissage Texte-photo.' );
$assert( false !== strpos( $composer, "[ 'photo-text', 'Texte rempli de photos' ]" ), 'Le composeur doit proposer le mode Texte rempli de photos.' );
$assert( false !== strpos( $composer, "type.value === 'photo-text'" ) && false !== strpos( $composer, 'photo_text_outline_color' ), 'Le composeur doit générer les attributs du texte photo.' );
$assert( false !== strpos( $composer, 'photo_text_size' ) && false !== strpos( $composer, 'photo_text_letter_spacing' ) && false !== strpos( $composer, 'photo_text_max_width' ) && false !== strpos( $composer, 'photo_text_align' ), 'Le composeur doit générer la typographie Texte-photo.' );
$assert( false !== strpos( $composer, 'photo_text_fill_mode' ) && false !== strpos( $composer, 'photo_text_density' ) && false !== strpos( $composer, 'wpd-c-photo-text-collage' ), 'Le composeur doit générer les réglages de remplissage Texte-photo.' );
$assert( false !== strpos( $composer, "[ 'collage', 'Collage / Pêle-mêle' ]" ) && false !== strpos( $composer, 'wpd-c-shape' ), 'Le mode Texte photo ne doit pas supprimer les contrôles Core existants.' );

fwrite( STDOUT, "Photo-filled semantic text layout and editor parity: OK\n" );
