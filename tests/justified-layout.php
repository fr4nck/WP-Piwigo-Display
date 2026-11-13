<?php

$justified  = file_get_contents( __DIR__ . '/../includes/class-wpd-justified.php' );
$classic    = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$css        = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-justified.css' );
$bootstrap  = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$block      = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );
$block_json = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$gutenberg  = file_get_contents( __DIR__ . '/../blocks/piwigo/masonry-controls.js' );
$admin      = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-composer-parity.js' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( $justified, "'layout'] = 'justified'" ), 'type=justified doit activer le layout justifié.' );
$assert( false !== strpos( $justified, 'min( 600, max( 100' ), 'La hauteur cible doit être bornée entre 100 et 600 px.' );
$assert( false !== strpos( $justified, 'min( 64, max( 0' ), 'L’espacement doit être borné entre 0 et 64 px.' );
$assert(
	1 === preg_match(
		"/return\\s+array\\(\\s*'width'\\s*=>\\s*4,\\s*'height'\\s*=>\\s*3,?\\s*\\);/s",
		$justified
	),
	'Les dimensions absentes doivent utiliser un fallback 4:3.'
);
$assert( false !== strpos( $justified, "wp_enqueue_script( 'wp-piwigo-display' )" ), 'La lightbox doit rester disponible.' );
$assert( false !== strpos( $css, 'flex-wrap: wrap' ), 'La galerie justifiée doit utiliser des lignes flex responsives.' );
$assert( false !== strpos( $css, 'flex-grow: 999999' ), 'La dernière ligne ne doit pas être étirée comme une ligne complète.' );
$assert( false !== strpos( $bootstrap, 'WPD_Justified::register()' ), 'Le module Justified doit être enregistré au chargement.' );
$assert( 1 === preg_match( "/'justifiedRowHeight'\\s*=>\\s*'justified_row_height'/", $block ), 'Le bloc doit transmettre la hauteur cible.' );
$assert( 1 === preg_match( "/'justifiedGap'\\s*=>\\s*'justified_gap'/", $block ), 'Le bloc doit transmettre l’espacement.' );
$assert( false !== strpos( $block_json, '"justifiedRowHeight"' ), 'Gutenberg doit déclarer la hauteur cible.' );
$assert( false !== strpos( $block_json, '"justifiedGap"' ), 'Gutenberg doit déclarer l’espacement.' );
$assert( false !== strpos( $gutenberg, "value: 'justified'" ), 'Gutenberg doit proposer le mode justifié.' );
$assert( false !== strpos( $classic, "type === 'justified'" ), 'Classic Editor doit proposer les options justifiées.' );
$assert( false !== strpos( $classic, 'justified_row_height' ), 'Classic Editor doit générer la hauteur cible.' );
$assert( false !== strpos( $admin, "type.value === 'justified'" ), 'Le composeur admin doit proposer le mode justifié.' );
$assert( false !== strpos( $admin, 'justified_row_height' ), 'Le composeur admin doit générer la hauteur cible.' );

echo "Justified gallery layout: OK\n";
