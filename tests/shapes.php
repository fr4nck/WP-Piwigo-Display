<?php

$bootstrap = file_get_contents(__DIR__ . '/../wp-piwigo-display.php');
$module = file_get_contents(__DIR__ . '/../includes/class-wpd-shapes.php');
$css = file_get_contents(__DIR__ . '/../assets/css/wp-piwigo-display-shapes.css');
$block = file_get_contents(__DIR__ . '/../blocks/piwigo/block.json');
$editor = file_get_contents(__DIR__ . '/../blocks/piwigo/shapes.js');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($bootstrap, "class-wpd-shapes.php") !== false, 'Le module de formes doit être chargé.');
$assert(strpos($bootstrap, 'WPD_Shapes::register()') !== false, 'Le module de formes doit être enregistré.');
$assert(strpos($module, "add_filter('do_shortcode_tag'") !== false, 'Le rendu final du shortcode doit recevoir la forme.');
$assert(strpos($module, "'star'") !== false && strpos($module, "'hexagon'") !== false, 'Les formes complexes doivent être autorisées.');
$assert(strpos($css, 'clip-path: polygon') !== false, 'Les formes complexes doivent utiliser clip-path.');
$assert(strpos($css, '@supports not (clip-path: polygon(0 0))') !== false, 'Un repli sans clip-path doit être prévu.');
$assert(strpos($block, '"shape"') !== false && strpos($block, '"radius"') !== false, 'Les attributs Gutenberg doivent être déclarés.');
$assert(strpos($editor, 'Arrondi des angles (%)') !== false, 'Le réglage fin de l’arrondi doit être disponible.');
