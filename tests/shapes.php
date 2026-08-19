<?php
/**
 * Static regression checks for shape support.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$bootstrap  = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$module     = file_get_contents( __DIR__ . '/../includes/class-wpd-shapes.php' );
$block_php  = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );
$css        = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-shapes.css' );
$block      = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$editor     = file_get_contents( __DIR__ . '/../blocks/piwigo/shapes.js' );
$classic    = file_get_contents( __DIR__ . '/../includes/class-wpd-classic-editor.php' );
$classic_js = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$composer   = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-composer-parity.js' );
$compact    = preg_replace( '/\s+/', '', (string) $module );
$block_compact = preg_replace( '/\s+/', '', (string) $block_php );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $bootstrap, 'class-wpd-shapes.php' ), 'Le module de formes doit être chargé.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Shapes::register()' ), 'Le module de formes doit être enregistré.' );
$assert( false !== strpos( (string) $compact, "add_filter('do_shortcode_tag'" ), 'Le rendu final du shortcode doit recevoir la forme.' );
$assert( false !== strpos( (string) $module, "'star'" ) && false !== strpos( (string) $module, "'hexagon'" ), 'Les formes complexes doivent être autorisées.' );
$assert( false !== strpos( (string) $css, 'clip-path: polygon' ), 'Les formes complexes doivent utiliser clip-path.' );
$assert( false !== strpos( (string) $css, '@supports not (clip-path: polygon(0 0))' ), 'Un repli sans clip-path doit être prévu.' );
$assert( false !== strpos( (string) $module, "'wpd-shapes-editor'" ) && false !== strpos( (string) $module, 'assets/css/wp-piwigo-display-shapes.css' ), 'Gutenberg doit charger le CSS des formes pour que l’aperçu visuel corresponde au rendu public.' );
$assert( false !== strpos( (string) $block, '"shape"' ) && false !== strpos( (string) $block, '"radius"' ), 'Les attributs Gutenberg doivent être déclarés.' );
$assert( false !== strpos( (string) $block, '"title": "Piwigo Display"' ), 'Le bloc Gutenberg doit utiliser le nom public Piwigo Display.' );
$assert( false !== strpos( (string) $block_compact, "'shape'=>'shape'" ) && false !== strpos( (string) $block_compact, "'radius'=>'radius'" ), 'Le bloc serveur doit transmettre forme et rayon au shortcode.' );
$assert( false !== strpos( (string) $block_php, "WPD_Shapes::apply_shape( \$output, 'piwigo', \$atts, array() )" ), 'Le rendu serveur Gutenberg doit appliquer explicitement la forme car do_shortcode_tag n’est pas exécuté.' );
$assert( false !== strpos( (string) $editor, 'Arrondi des angles (%)' ), 'Le réglage fin de l’arrondi doit être disponible dans Gutenberg.' );
$assert( false !== strpos( (string) $classic, 'data-wpd="shape"' ) && false !== strpos( (string) $classic, 'data-wpd="radius"' ), 'Classic Editor doit proposer la forme et le rayon.' );
$assert( false !== strpos( (string) $classic_js, "add(parts, 'shape'" ) && false !== strpos( (string) $classic_js, "add(parts, 'radius'" ), 'Classic Editor doit générer les attributs de forme.' );
$assert( false !== strpos( (string) $composer, 'wpd-c-shape' ) && false !== strpos( (string) $composer, 'wpd-c-radius' ), 'Le composeur d’administration doit proposer la forme et le rayon.' );

echo "Shape support checks passed.\n";
