<?php
/**
 * Static regression checks for the Gutenberg album picker.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$controls = file_get_contents( __DIR__ . '/../blocks/piwigo/gutenberg-parity.js' );
$plugin   = file_get_contents( __DIR__ . '/../includes/class-wpd-plugin.php' );
$matrix   = file_get_contents( __DIR__ . '/../docs/PARITE-COMPOSEURS.md' );
$compact  = preg_replace( '/\s+/', '', (string) $plugin );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $controls, 'function AlbumPicker' ), 'Le composant AlbumPicker doit exister.' );
$assert( false !== strpos( (string) $controls, "data.append('action', 'wpd_get_albums')" ), 'Le sélecteur doit utiliser l’endpoint albums existant.' );
$assert( false !== strpos( (string) $controls, 'WPDAlbumPickerConfig.nonce' ), 'Le nonce du sélecteur doit être transmis.' );
$assert( false !== strpos( (string) $controls, 'setAttributes({ albumId: value })' ), 'La sélection doit mettre à jour albumId.' );
$assert( false !== strpos( (string) $controls, 'Rechercher un album' ), 'La recherche d’album doit être disponible.' );
$assert( false !== strpos( (string) $controls, 'La saisie manuelle reste disponible' ), 'Le secours par saisie manuelle doit être explicite.' );
$assert( false !== strpos( (string) $compact, "add_action('wp_ajax_wpd_get_albums',array(\$this,'ajax_get_albums'))" ), 'L’endpoint AJAX albums public doit rester enregistré.' );
$assert( false !== strpos( (string) $matrix, '| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |' ), 'La matrice doit confirmer la parité complète.' );

echo "Gutenberg album picker checks passed.\n";
