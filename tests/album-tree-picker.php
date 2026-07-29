<?php

$classic = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-album-picker.js');
$gutenberg = file_get_contents(__DIR__ . '/../blocks/piwigo/gutenberg-parity.js');
$css = file_get_contents(__DIR__ . '/../assets/css/wp-piwigo-display-album-picker.css');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($classic, 'wpd-album-toggle') !== false, 'Le sélecteur classique doit proposer une ouverture et fermeture par branche.');
$assert(strpos($classic, 'wpd-album-confirm') !== false, 'Le sélecteur classique doit proposer une validation explicite.');
$assert(strpos($classic, 'role="treeitem"') !== false, 'Le sélecteur classique doit exposer une arborescence accessible.');
$assert(strpos($classic, 'hierarchy.length = depth + 1') !== false, 'La hiérarchie doit être déduite des profondeurs existantes.');
$assert(strpos($gutenberg, "role: 'treeitem'") !== false, 'Gutenberg doit exposer une arborescence accessible.');
$assert(strpos($gutenberg, "__('Valider'") !== false, 'Gutenberg doit proposer une validation explicite.');
$assert(strpos($gutenberg, 'setExpanded') !== false, 'Gutenberg doit gérer les branches ouvertes et fermées.');
$assert(strpos($css, '.wpd-album-row.is-selected') !== false, 'L’album présélectionné doit être visible.');

echo "Album tree picker checks passed." . PHP_EOL;
