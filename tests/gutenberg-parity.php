<?php
/**
 * Static regression checks for Gutenberg parity.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$block     = file_get_contents( __DIR__ . '/../includes/class-wpd-block.php' );
$metadata  = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$controls  = file_get_contents( __DIR__ . '/../blocks/piwigo/gutenberg-parity.js' );
$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$matrix    = file_get_contents( __DIR__ . '/../docs/PARITE-COMPOSEURS.md' );
$compact   = preg_replace( '/\s+/', '', (string) $block );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $metadata, '"preset"' ), 'Le bloc doit déclarer preset.' );
$assert( false !== strpos( (string) $metadata, '"piwigoUrl"' ), 'Le bloc doit déclarer piwigoUrl.' );
$assert( false !== strpos( (string) $compact, "'preset'=>'preset'" ), 'Le preset Gutenberg doit être transmis au shortcode.' );
$assert( false !== strpos( (string) $compact, "'piwigoUrl'=>'url'" ), 'L’URL Gutenberg doit être transmise au shortcode.' );
$assert( false !== strpos( (string) $controls, 'Options avancées Piwigo' ), 'Le panneau avancé Gutenberg doit être présent.' );
$assert( false !== strpos( (string) $controls, 'setAttributes({ preset: value })' ), 'Le contrôle preset doit mettre à jour le bloc.' );
$assert( false !== strpos( (string) $controls, 'setAttributes({ piwigoUrl: value })' ), 'Le contrôle URL doit mettre à jour le bloc.' );
$assert( false !== strpos( (string) $bootstrap, 'WPD_Gutenberg_Parity::register()' ), 'Le module Gutenberg doit être enregistré.' );
$assert( false !== strpos( (string) $matrix, '| Presets | Oui | Oui | Oui | Oui |' ), 'La matrice doit confirmer la parité des presets.' );
$assert( false !== strpos( (string) $matrix, '| URL Piwigo spécifique | Oui | Oui | Oui | Oui |' ), 'La matrice doit confirmer la parité de l’URL.' );

echo "Gutenberg parity checks passed.\n";
