<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$module = file_get_contents( __DIR__ . '/../includes/class-wpd-collage.php' );
$css = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-collage.css' );
$block = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$controls = file_get_contents( __DIR__ . '/../blocks/piwigo/masonry-controls.js' );
$block_php = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );
$classic = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$composer = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-composer-parity.js' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( $bootstrap, 'class-wpd-collage.php' ), 'Le module Collage doit être chargé.' );
$assert( false !== strpos( $bootstrap, 'WPD_Collage::register()' ), 'Le module Collage doit être enregistré.' );
$assert( false !== strpos( $module, "=== 'collage'" ), 'Le shortcode type=collage doit être normalisé.' );
$assert( false !== strpos( $module, "md5( \$seed . ':' . \$id )" ), 'La composition doit être déterministe à partir de la graine et de l’identifiant.' );
$assert( false !== strpos( $module, 'collage_rotation' ) && false !== strpos( $module, 'collage_spread' ) && false !== strpos( $module, 'collage_overlap' ), 'Les réglages principaux du Collage doivent exister.' );
$assert( false !== strpos( $css, 'prefers-reduced-motion' ), 'Le Collage doit respecter prefers-reduced-motion.' );
$assert( false !== strpos( $css, ':focus-visible' ), 'Le Collage doit conserver un focus clavier visible.' );
$assert( false !== strpos( $css, 'wpd-shape-circle' ) && false !== strpos( $css, 'wpd-shape-card-spade' ), 'Le Collage doit rester compatible avec la bibliothèque de formes.' );
$assert( false !== strpos( $block, '"collageSeed"' ) && false !== strpos( $block, '"collageRotation"' ), 'Les attributs Gutenberg du Collage doivent être déclarés.' );
$assert( false !== strpos( $controls, "value: 'collage'" ), 'Gutenberg doit proposer le mode Collage.' );
$assert( false !== strpos( $controls, 'Même graine + mêmes photos = même composition.' ), 'Gutenberg doit expliquer la stabilité de la composition.' );
$assert( 1 === preg_match( "/'collageSeed'\\s*=>\\s*'collage_seed'/", $block_php ), 'Le bloc doit transmettre la graine au shortcode.' );
$assert( false !== strpos( $classic, 'Collage / Pêle-mêle' ) && false !== strpos( $classic, "type === 'collage'" ), 'Classic Editor doit proposer et générer le mode Collage.' );
$assert( false !== strpos( $classic, 'collage_variation' ), 'Classic Editor doit exposer les réglages Collage.' );
$assert( false !== strpos( $composer, "[ 'collage', 'Collage / Pêle-mêle' ]" ), 'Le composeur d’administration doit proposer le mode Collage.' );
$assert( false !== strpos( $composer, "type.value === 'collage'" ) && false !== strpos( $composer, 'collage_seed' ), 'Le composeur doit générer les attributs Collage.' );

fwrite( STDOUT, "Deterministic collage layout and editor parity: OK\n" );
