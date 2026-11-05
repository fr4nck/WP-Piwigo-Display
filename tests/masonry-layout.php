<?php
/**
 * Static regression checks for Masonry.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$masonry    = file_get_contents( __DIR__ . '/../includes/class-wpd-masonry.php' );
$classic    = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$css        = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-masonry.css' );
$bootstrap  = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$block      = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );
$block_json = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$gutenberg  = file_get_contents( __DIR__ . '/../blocks/piwigo/masonry-controls.js' );
$masonry_compact = preg_replace( '/\s+/', '', (string) $masonry );
$block_compact   = preg_replace( '/\s+/', '', (string) $block );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $masonry, 'shortcode_atts_piwigo' ), 'Le type masonry doit être normalisé avant le rendu.' );
$assert( false !== strpos( (string) $masonry_compact, "'layout']='masonry'" ), 'Le shortcode type=masonry doit activer le layout Masonry.' );
$assert( false !== strpos( (string) $masonry_compact, 'min(6,max(2,' ), 'Le nombre de colonnes doit être borné entre 2 et 6.' );
$assert( false !== strpos( (string) $masonry_compact, 'min(64,max(0,' ), 'L’espacement doit être borné entre 0 et 64 px.' );
$assert( false !== strpos( (string) $masonry_compact, "wp_enqueue_script('wp-piwigo-display')" ), 'La lightbox doit rester disponible.' );
$assert( false !== strpos( (string) $classic, "type === 'masonry'" ), 'Le composeur doit générer les options Masonry.' );
$assert( false !== strpos( (string) $classic, 'masonry_columns' ), 'Le composeur doit exposer le nombre de colonnes.' );
$assert( false !== strpos( (string) $css, 'column-count' ), 'Le rendu doit utiliser des colonnes CSS natives.' );
$assert( false !== strpos( (string) $css, '@media (max-width: 420px)' ), 'Le rendu doit passer à une colonne sur petit mobile.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Masonry::register()' ), 'Le module Masonry doit être enregistré au chargement.' );
$assert( false !== strpos( (string) $block_compact, "'masonryColumns'=>'masonry_columns'" ), 'Le bloc doit transmettre le nombre de colonnes au shortcode.' );
$assert( false !== strpos( (string) $block_compact, "'masonryGap'=>'masonry_gap'" ), 'Le bloc doit transmettre l’espacement au shortcode.' );
$assert( false !== strpos( (string) $block_json, '"masonryColumns"' ), 'Les attributs Gutenberg doivent déclarer le nombre de colonnes.' );
$assert( false !== strpos( (string) $block_json, '"masonryGap"' ), 'Les attributs Gutenberg doivent déclarer l’espacement.' );
$assert( false !== strpos( (string) $gutenberg, "value: 'masonry'" ), 'Gutenberg doit proposer le mode Masonry.' );
$assert( false !== strpos( (string) $gutenberg, 'RangeControl' ), 'Gutenberg doit exposer les réglages bornés du mode Masonry.' );

echo "Masonry layout checks passed.\n";
