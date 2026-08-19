<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$module = file_get_contents( __DIR__ . '/../includes/class-wpd-collage.php' );
$css = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-collage.css' );
$block = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$controls = file_get_contents( __DIR__ . '/../blocks/piwigo/masonry-controls.js' );
$block_php = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );

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
$assert( false !== strpos( $block, '"collageSeed"' ) && false !== strpos( $block, '"collageRotation"' ), 'Les attributs Gutenberg du Collage doivent être déclarés.' );
$assert( false !== strpos( $controls, "value: 'collage'" ), 'Gutenberg doit proposer le mode Collage.' );
$assert( false !== strpos( $controls, 'Même graine + mêmes photos = même composition.' ), 'Gutenberg doit expliquer la stabilité de la composition.' );
$assert( false !== strpos( $block_php, "'collageSeed' => 'collage_seed'" ), 'Le bloc doit transmettre la graine au shortcode.' );

fwrite( STDOUT, "Deterministic collage layout: OK\n" );
