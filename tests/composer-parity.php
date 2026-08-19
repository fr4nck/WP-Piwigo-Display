<?php

$module = file_get_contents(__DIR__ . '/../includes/class-wpd-composer-parity.php');
$bootstrap = file_get_contents(__DIR__ . '/../wp-piwigo-display.php');
$classic = file_get_contents(__DIR__ . '/../includes/class-wpd-classic-editor.php');
$block = file_get_contents(__DIR__ . '/../blocks/piwigo/masonry-controls.js');
$matrix = file_get_contents(__DIR__ . '/../docs/PARITE-COMPOSEURS.md');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($bootstrap, "class-wpd-composer-parity.php") !== false, 'Le module de parité doit être chargé.');
$assert(strpos($bootstrap, 'WPD_Composer_Parity::register()') !== false, 'Le module de parité doit être enregistré.');
$assert(strpos($module, "option.value = 'masonry'") !== false, 'Le composeur d’administration doit proposer Masonry.');
$assert(strpos($module, 'wpd-c-transition') !== false, 'Le composeur d’administration doit proposer les transitions.');
$assert(strpos($module, 'wpd-c-direction') !== false, 'Le composeur d’administration doit proposer la direction.');
$assert(strpos($module, 'masonry_columns') !== false, 'Le composeur d’administration doit générer masonry_columns.');
$assert(strpos($module, 'masonry_gap') !== false, 'Le composeur d’administration doit générer masonry_gap.');
$assert(strpos($classic, 'data-wpd="transition"') !== false, 'Classic Editor doit conserver le réglage de transition.');
$assert(strpos($classic, 'data-wpd="masonry_columns"') !== false, 'Classic Editor doit conserver le réglage Masonry.');
$assert(strpos($block, 'masonryColumns') !== false, 'Gutenberg doit conserver le réglage des colonnes Masonry.');
$assert(strpos($matrix, 'Sélecteur visuel d’albums') !== false, 'La matrice doit documenter la sélection visuelle des albums.');
$assert(strpos($matrix, 'À compléter') !== false, 'Les écarts Gutenberg doivent rester explicites.');
