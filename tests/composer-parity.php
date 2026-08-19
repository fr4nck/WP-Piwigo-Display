<?php
/**
 * Static regression checks for composer parity.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$module    = file_get_contents( __DIR__ . '/../includes/class-wpd-composer-parity.php' );
$composer  = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-composer-parity.js' );
$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$classic   = file_get_contents( __DIR__ . '/../includes/class-wpd-classic-editor.php' );
$block     = file_get_contents( __DIR__ . '/../blocks/piwigo/masonry-controls.js' );
$matrix    = file_get_contents( __DIR__ . '/../docs/PARITE-COMPOSEURS.md' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $bootstrap, 'class-wpd-composer-parity.php' ), 'Le module de parité doit être chargé.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Composer_Parity::register()' ), 'Le module de parité doit être enregistré.' );
$assert( false !== strpos( (string) $module, 'wp-piwigo-display-composer-parity.js' ), 'Le module PHP doit charger le script de parité du composeur.' );
$assert( false !== strpos( (string) $composer, "option.value = 'masonry'" ), 'Le composeur d’administration doit proposer Masonry.' );
$assert( false !== strpos( (string) $composer, 'wpd-c-transition' ), 'Le composeur d’administration doit proposer les transitions.' );
$assert( false !== strpos( (string) $composer, 'wpd-c-direction' ), 'Le composeur d’administration doit proposer la direction.' );
$assert( false !== strpos( (string) $composer, 'masonry_columns' ), 'Le composeur d’administration doit générer masonry_columns.' );
$assert( false !== strpos( (string) $composer, 'masonry_gap' ), 'Le composeur d’administration doit générer masonry_gap.' );
$assert( false !== strpos( (string) $classic, 'data-wpd="transition"' ), 'Classic Editor doit conserver le réglage de transition.' );
$assert( false !== strpos( (string) $classic, 'data-wpd="masonry_columns"' ), 'Classic Editor doit conserver le réglage Masonry.' );
$assert( false !== strpos( (string) $block, 'masonryColumns' ), 'Gutenberg doit conserver le réglage des colonnes Masonry.' );
$assert( false !== strpos( (string) $matrix, '| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |' ), 'La matrice doit documenter la parité complète du sélecteur visuel.' );
$assert( false !== strpos( (string) $matrix, '## Parité atteinte' ), 'La matrice doit déclarer explicitement la parité atteinte.' );
$assert( false === strpos( (string) $matrix, 'À compléter' ), 'Une matrice déclarée complète ne doit plus conserver un ancien marqueur « À compléter ».' );

echo "Composer parity checks passed.\n";
