<?php

$block = file_get_contents(__DIR__ . '/../includes/class-wpd-block.php');
$metadata = file_get_contents(__DIR__ . '/../blocks/piwigo/block.json');
$controls = file_get_contents(__DIR__ . '/../blocks/piwigo/gutenberg-parity.js');
$bootstrap = file_get_contents(__DIR__ . '/../wp-piwigo-display.php');
$matrix = file_get_contents(__DIR__ . '/../docs/PARITE-COMPOSEURS.md');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($metadata, '"preset"') !== false, 'Le bloc doit déclarer preset.');
$assert(strpos($metadata, '"piwigoUrl"') !== false, 'Le bloc doit déclarer piwigoUrl.');
$assert(strpos($block, "'preset' => 'preset'") !== false, 'Le preset Gutenberg doit être transmis au shortcode.');
$assert(strpos($block, "'piwigoUrl' => 'url'") !== false, 'L’URL Gutenberg doit être transmise au shortcode.');
$assert(strpos($controls, 'Options avancées Piwigo') !== false, 'Le panneau avancé Gutenberg doit être présent.');
$assert(strpos($controls, 'setAttributes({ preset: value })') !== false, 'Le contrôle preset doit mettre à jour le bloc.');
$assert(strpos($controls, 'setAttributes({ piwigoUrl: value })') !== false, 'Le contrôle URL doit mettre à jour le bloc.');
$assert(strpos($bootstrap, 'WPD_Gutenberg_Parity::register()') !== false, 'Le module Gutenberg doit être enregistré.');
$assert(strpos($matrix, '| Presets | Oui | Oui | Oui | Oui |') !== false, 'La matrice doit confirmer la parité des presets.');
$assert(strpos($matrix, '| URL Piwigo spécifique | Oui | Oui | Oui | Oui |') !== false, 'La matrice doit confirmer la parité de l’URL.');
