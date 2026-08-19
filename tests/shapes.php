<?php

$bootstrap = file_get_contents(__DIR__ . '/../wp-piwigo-display.php');
$module = file_get_contents(__DIR__ . '/../includes/class-wpd-shapes.php');
$css = file_get_contents(__DIR__ . '/../assets/css/wp-piwigo-display-shapes.css');
$picker_css = file_get_contents(__DIR__ . '/../assets/css/wp-piwigo-display-shape-picker.css');
$block = file_get_contents(__DIR__ . '/../blocks/piwigo/block.json');
$editor = file_get_contents(__DIR__ . '/../blocks/piwigo/shapes.js');
$classic = file_get_contents(__DIR__ . '/../includes/class-wpd-classic-editor.php');
$classic_js = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js');
$composer = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-composer-parity.js');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$built_in_shapes = [
    'rectangle',
    'rounded',
    'circle',
    'oval',
    'pill',
    'star',
    'hexagon',
    'diamond',
    'cloud',
    'heart',
    'drop',
    'triangle',
    'pentagon',
    'octagon',
    'card-spade',
    'card-heart',
    'card-diamond',
    'card-club',
];

$assert(strpos($bootstrap, 'class-wpd-shapes.php') !== false, 'Le module de formes doit être chargé.');
$assert(strpos($bootstrap, 'WPD_Shapes::register()') !== false, 'Le module de formes doit être enregistré.');
$assert(strpos($module, "add_filter( 'do_shortcode_tag'") !== false, 'Le rendu final du shortcode doit recevoir la forme.');
$assert(strpos($module, 'remove_accents') !== false, 'Les alias français accentués doivent être normalisés avant sanitize_key().');
$assert(strpos($module, 'wp-piwigo-display-shape-picker.css') !== false, 'La feuille de style du sélecteur visuel doit être chargée.');
$assert(strpos($css, 'clip-path: polygon') !== false, 'Les formes complexes doivent utiliser clip-path.');
$assert(strpos($css, '@supports not (clip-path: polygon(0 0))') !== false, 'Un repli sans clip-path doit être prévu.');
$assert(strpos($picker_css, '.wpd-shape-picker-grid') !== false, 'Le sélecteur visuel doit disposer d’une grille de miniatures.');
$assert(strpos($picker_css, '[aria-pressed="true"]') !== false, 'La forme active doit avoir un état visuel accessible.');
$assert(strpos($block, '"shape"') !== false && strpos($block, '"radius"') !== false, 'Les attributs Gutenberg doivent être déclarés.');
$assert(strpos($editor, 'wpd-shape-picker-grid') !== false, 'Gutenberg doit proposer un sélecteur visuel de formes.');
$assert(strpos($editor, "'aria-pressed'") !== false, 'Gutenberg doit exposer l’état de la forme sélectionnée.');
$assert(strpos($editor, 'Arrondi des angles (%)') !== false, 'Le réglage fin de l’arrondi doit être disponible dans Gutenberg.');
$assert(strpos($classic, 'data-wpd="shape"') !== false && strpos($classic, 'data-wpd="radius"') !== false, 'Classic Editor doit proposer la forme et le rayon.');
$assert(strpos($classic_js, "add(parts, 'shape'") !== false && strpos($classic_js, "add(parts, 'radius'") !== false, 'Classic Editor doit générer les attributs de forme.');
$assert(strpos($classic_js, 'wpd-shape-picker-grid') !== false, 'Classic Editor doit proposer les miniatures de formes.');
$assert(strpos($composer, 'wpd-c-shape') !== false && strpos($composer, 'wpd-c-radius') !== false, 'Le composeur d’administration doit proposer la forme et le rayon.');
$assert(strpos($composer, 'wpd-shape-picker-grid') !== false, 'Le composeur doit proposer les miniatures de formes.');

foreach ($built_in_shapes as $shape) {
    $quoted = "'" . $shape . "'";
    $option = 'value="' . $shape . '"';

    $assert(strpos($module, $quoted) !== false, sprintf('La forme %s doit être autorisée côté serveur.', $shape));
    $assert(strpos($editor, $quoted) !== false, sprintf('La forme %s doit être proposée dans Gutenberg.', $shape));
    $assert(strpos($classic, $option) !== false, sprintf('La forme %s doit être proposée dans Classic Editor.', $shape));
    $assert(strpos($composer, $option) !== false, sprintf('La forme %s doit être proposée dans le composeur.', $shape));
    $assert(strpos($picker_css, 'wpd-shape-preview-' . $shape) !== false || $shape === 'rectangle', sprintf('La forme %s doit disposer d’une miniature.', $shape));
}

foreach (['cloud', 'heart', 'drop', 'triangle', 'pentagon', 'octagon', 'card-spade', 'card-heart', 'card-diamond', 'card-club'] as $shape) {
    $assert(strpos($css, '.wpd-shape-' . $shape) !== false, sprintf('La forme %s doit avoir une silhouette CSS.', $shape));
}

fwrite(STDOUT, "Shape library, previews and editor parity: OK\n");
