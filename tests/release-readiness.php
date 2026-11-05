<?php
/**
 * Static release-readiness checks for the 3.1 development line.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);
$root = dirname( __DIR__ );
$plugin = file_get_contents( $root . '/wp-piwigo-display.php' );
$readme = file_get_contents( $root . '/README.md' );
$roadmap = file_get_contents( $root . '/ROADMAP.md' );
$recipe = file_get_contents( $root . '/docs/RECETTE-3X.md' );
$parity = file_get_contents( $root . '/docs/PARITE-COMPOSEURS.md' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};
preg_match( '/^\s*\* Version:\s*([^\s]+)\s*$/m', (string) $plugin, $header_match );
preg_match( "/define\(\s*'WPD_VERSION'\s*,\s*'([^']+)'\s*\)/", (string) $plugin, $constant_match );
$assert( isset( $header_match[1], $constant_match[1] ), 'Les deux versions doivent être détectables.' );
$assert( $header_match[1] === $constant_match[1], 'WPD_VERSION et l’en-tête doivent correspondre.' );
$assert( '3.1.0-rc.1' === $header_match[1], 'La branche de développement doit signaler sa candidate actuelle.' );
$assert( false !== strpos( (string) $readme, '3.1.0-rc.1' ), 'Le README doit mentionner la candidate 3.1.' );
$assert( false !== strpos( (string) $readme, '1.10.0' ), 'Le README doit refléter la dernière release GitHub non préversion.' );
$assert( false !== strpos( (string) $readme, '2.0.0' ) && false !== strpos( (string) $readme, 'jamais' ), 'La ligne 2.0.0 non distribuée doit être distinguée.' );
$assert( false !== strpos( (string) $roadmap, '3.1.0-rc.1' ), 'La feuille de route doit mentionner la RC 3.1.' );
$assert( false !== strpos( (string) $recipe, 'Décision de préversion' ), 'La recette doit imposer une décision de publication.' );
$assert( false !== strpos( (string) $recipe, '**GO / NO GO**' ), 'La recette doit conserver le GO/NO GO.' );
$assert( false !== strpos( (string) $recipe, 'vérifications manuelles non réalisables' ), 'Les vérifications non faites doivent être consignées.' );
$assert( false !== strpos( (string) $parity, '| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |' ), 'La matrice de parité doit rester disponible.' );
echo "Release readiness checks passed for 3.1.0-rc.1.\n";