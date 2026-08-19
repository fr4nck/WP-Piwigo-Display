<?php

$controls = file_get_contents(__DIR__ . '/../blocks/piwigo/gutenberg-parity.js');
$plugin = file_get_contents(__DIR__ . '/../includes/class-wpd-plugin.php');
$matrix = file_get_contents(__DIR__ . '/../docs/PARITE-COMPOSEURS.md');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($controls, 'function AlbumPicker') !== false, 'Le composant AlbumPicker doit exister.');
$assert(strpos($controls, "data.append('action', 'wpd_get_albums')") !== false, 'Le sélecteur doit utiliser l’endpoint albums existant.');
$assert(strpos($controls, 'WPDAlbumPickerConfig.nonce') !== false, 'Le nonce du sélecteur doit être transmis.');
$assert(strpos($controls, "setAttributes({ albumId: value })") !== false, 'La sélection doit mettre à jour albumId.');
$assert(strpos($controls, 'Rechercher un album') !== false, 'La recherche d’album doit être disponible.');
$assert(strpos($controls, 'La saisie manuelle reste disponible') !== false, 'Le secours par saisie manuelle doit être explicite.');
$assert(strpos($plugin, "add_action('wp_ajax_wpd_get_albums'") !== false, 'L’endpoint AJAX albums doit rester enregistré.');
$assert(strpos($matrix, '| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |') !== false, 'La matrice doit confirmer la parité complète.');
