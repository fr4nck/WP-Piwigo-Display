<?php
/**
 * Static regression checks for the hierarchical album picker.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$classic   = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-album-picker.js' );
$gutenberg = file_get_contents( __DIR__ . '/../blocks/piwigo/gutenberg-parity.js' );
$css       = file_get_contents( __DIR__ . '/../assets/css/wp-piwigo-display-album-picker.css' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $classic, 'wpd-album-toggle' ), 'Le sélecteur classique doit proposer une ouverture et fermeture par branche.' );
$assert( false !== strpos( (string) $classic, 'wpd-album-confirm' ), 'Le sélecteur classique doit proposer une validation explicite.' );
$assert( false !== strpos( (string) $classic, 'role="treeitem"' ), 'Le sélecteur classique doit exposer une arborescence accessible.' );
$assert( false !== strpos( (string) $classic, 'hierarchy.length = depth + 1' ), 'La hiérarchie classique doit être déduite des profondeurs existantes.' );
$assert( false !== strpos( (string) $classic, 'function branchIsVisible(album)' ), 'Le sélecteur classique doit vérifier toute la chaîne des ancêtres avant d’afficher un descendant.' );
$assert( false !== strpos( (string) $classic, 'if (!expanded[String(ids[index])]) return false;' ), 'Un descendant classique doit rester caché dès qu’un ancêtre est replié.' );
$assert( false !== strpos( (string) $classic, "'aria-expanded': branchExpanded ? 'true' : 'false'" ), 'Le bouton d’ouverture classique doit exposer son état ARIA.' );
$assert( false !== strpos( (string) $classic, '$(input).val(album.id)' ), 'Le champ album ne doit être modifié qu’au clic de validation explicite.' );

$assert( false !== strpos( (string) $gutenberg, "role: 'treeitem'" ), 'Gutenberg doit exposer une arborescence accessible.' );
$assert( false !== strpos( (string) $gutenberg, "__('Valider'" ), 'Gutenberg doit proposer une validation explicite.' );
$assert( false !== strpos( (string) $gutenberg, 'setExpanded' ), 'Gutenberg doit gérer les branches ouvertes et fermées.' );
$assert( false !== strpos( (string) $gutenberg, 'function branchIsVisible(album)' ), 'Gutenberg doit vérifier toute la chaîne des ancêtres avant d’afficher un descendant.' );
$assert( false !== strpos( (string) $gutenberg, 'if (!expanded[String(ids[index])]) {' ), 'Un descendant Gutenberg doit rester caché dès qu’un ancêtre est replié.' );
$assert( false !== strpos( (string) $gutenberg, "'aria-expanded': hasChildren ? isExpanded : undefined" ), 'Le bouton d’ouverture Gutenberg doit exposer son état ARIA.' );
$assert( false !== strpos( (string) $gutenberg, 'setSelectedId(id);' ) && false !== strpos( (string) $gutenberg, 'props.onChange(id);' ), 'Gutenberg doit séparer présélection et validation.' );

$assert( false !== strpos( (string) $css, '.wpd-album-row.is-selected' ), 'L’album présélectionné doit être visible.' );
$assert( false !== strpos( (string) $css, 'max-height:min(360px,50vh)' ), 'Le panneau du sélecteur ne doit pas dépasser 50vh sur les petits écrans.' );
$assert( false !== strpos( (string) $css, 'overscroll-behavior:contain' ), 'Le défilement du sélecteur doit rester contenu dans son panneau.' );

echo "Album tree picker checks passed.\n";
