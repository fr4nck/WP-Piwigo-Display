<?php
/**
 * Static release-readiness checks for the V3 candidate.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$plugin  = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$readme  = file_get_contents( __DIR__ . '/../README.md' );
$roadmap = file_get_contents( __DIR__ . '/../ROADMAP.md' );
$recipe  = file_get_contents( __DIR__ . '/../docs/RECETTE-3X.md' );
$parity  = file_get_contents( __DIR__ . '/../docs/PARITE-COMPOSEURS.md' );
$metadata = json_decode( (string) file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' ), true );
$asset    = require __DIR__ . '/../blocks/piwigo/index.asset.php';

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

preg_match( '/^\s*\* Version:\s*([^\s]+)\s*$/m', (string) $plugin, $header_match );
preg_match( "/define\(\s*'WPD_VERSION'\s*,\s*'([^']+)'\s*\)/", (string) $plugin, $constant_match );

$assert( isset( $header_match[1] ), 'La version de l’en-tête du plugin doit être détectable.' );
$assert( isset( $constant_match[1] ), 'La constante WPD_VERSION doit être détectable.' );
$assert( $header_match[1] === $constant_match[1], 'L’en-tête du plugin et WPD_VERSION doivent être identiques.' );
$assert( ( $metadata['version'] ?? '' ) === $header_match[1], 'La version du bloc Gutenberg doit correspondre au plugin.' );
$assert( ( $asset['version'] ?? '' ) === $header_match[1], 'La version des scripts Gutenberg doit invalider le cache lors de la 3.0.1.' );
$assert( '3.0.1' === $header_match[1], 'La candidate de maintenance doit être identifiée comme 3.0.1.' );
$assert( false !== strpos( (string) $readme, '1.10.0' ), 'Le README doit identifier 1.10.0 comme dernière stable GitHub avant V3.' );
$assert( false !== strpos( (string) $readme, '2.0.0' ) && false !== strpos( (string) $readme, 'jamais' ), 'Le README doit préciser que la ligne 2.0.0 n’a jamais été publiée comme release publique.' );
$assert( false !== strpos( (string) $roadmap, '## V3 — version stable 3.0.0' ), 'La feuille de route doit identifier la version V3 effectivement publiée.' );
$assert( false !== strpos( (string) $roadmap, 'recette réelle' ), 'La feuille de route doit maintenir la recette WordPress réelle comme verrou de sortie.' );
$assert( false !== strpos( (string) $recipe, 'Décision de release' ), 'La recette doit contenir une décision explicite avant release stable.' );
$assert( false !== strpos( (string) $recipe, '**GO / NO GO**' ), 'La recette doit imposer une décision GO ou NO GO.' );
$assert( false !== strpos( (string) $recipe, 'vérifications manuelles non réalisables' ), 'La recette doit consigner les contrôles manuels non réalisables.' );
$assert( false !== strpos( (string) $recipe, 'dossier racine `wp-piwigo-display`' ), 'La recette doit protéger l’identité technique du dossier installable.' );
$assert( false !== strpos( (string) $parity, '| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |' ), 'La matrice doit confirmer la parité du sélecteur visuel.' );

echo "Release readiness checks passed for version {$header_match[1]}." . PHP_EOL;
