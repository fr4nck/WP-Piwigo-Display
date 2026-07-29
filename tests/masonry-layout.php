<?php

$masonry = file_get_contents(__DIR__ . '/../includes/class-wpd-masonry.php');
$classic = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js');
$css = file_get_contents(__DIR__ . '/../assets/css/wp-piwigo-display-masonry.css');
$bootstrap = file_get_contents(__DIR__ . '/../wp-piwigo-display.php');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($masonry, "shortcode_atts_piwigo") !== false, 'Le type masonry doit être normalisé avant le rendu.');
$assert(strpos($masonry, "'layout'] = 'masonry'") !== false, 'Le shortcode type=masonry doit activer le layout Masonry.');
$assert(strpos($masonry, 'min(6, max(2') !== false, 'Le nombre de colonnes doit être borné entre 2 et 6.');
$assert(strpos($masonry, 'min(64, max(0') !== false, 'L’espacement doit être borné entre 0 et 64 px.');
$assert(strpos($masonry, "wp_enqueue_script('wp-piwigo-display')") !== false, 'La lightbox doit rester disponible.');
$assert(strpos($classic, "type === 'masonry'") !== false, 'Le composeur doit générer les options Masonry.');
$assert(strpos($classic, "masonry_columns") !== false, 'Le composeur doit exposer le nombre de colonnes.');
$assert(strpos($css, 'column-count') !== false, 'Le rendu doit utiliser des colonnes CSS natives.');
$assert(strpos($css, '@media (max-width: 420px)') !== false, 'Le rendu doit passer à une colonne sur petit mobile.');
$assert(strpos($bootstrap, 'WPD_Masonry::register()') !== false, 'Le module Masonry doit être enregistré au chargement.');
