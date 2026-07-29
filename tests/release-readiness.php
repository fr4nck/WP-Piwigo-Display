<?php

$plugin = file_get_contents(__DIR__ . '/../wp-piwigo-display.php');
$readme = file_get_contents(__DIR__ . '/../README.md');
$roadmap = file_get_contents(__DIR__ . '/../ROADMAP.md');
$recipe = file_get_contents(__DIR__ . '/../docs/RECETTE-3X.md');
$parity = file_get_contents(__DIR__ . '/../docs/PARITE-COMPOSEURS.md');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

preg_match('/^\s*\* Version:\s*([^\s]+)\s*$/m', $plugin, $headerMatch);
preg_match("/define\('WPD_VERSION',\s*'([^']+)'\)/", $plugin, $constantMatch);

$assert(isset($headerMatch[1]), 'La version de l’en-tête du plugin doit être détectable.');
$assert(isset($constantMatch[1]), 'La constante WPD_VERSION doit être détectable.');
$assert($headerMatch[1] === $constantMatch[1], 'L’en-tête du plugin et WPD_VERSION doivent être identiques.');
$assert(strpos($readme, 'Version stable — 2.0.0') !== false, 'Le README doit identifier explicitement la version stable distribuée.');
$assert(strpos($readme, 'recette WordPress réelle') !== false, 'Le README doit signaler que le socle 3.x reste soumis à recette.');
$assert(strpos($roadmap, 'stabilisation et recette 3.x') !== false, 'La feuille de route doit désigner la stabilisation 3.x comme prochaine étape.');
$assert(strpos($recipe, 'Décision de préversion') !== false, 'La recette doit contenir une décision explicite avant préversion.');
$assert(strpos($recipe, '**GO / NO GO**') !== false, 'La recette doit imposer une décision GO ou NO GO.');
$assert(strpos($recipe, 'vérifications manuelles non réalisables') !== false, 'La recette doit consigner les contrôles manuels non réalisables.');
$assert(strpos($parity, '| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |') !== false, 'La matrice doit confirmer la parité du sélecteur visuel.');

echo "Release readiness checks passed for version {$headerMatch[1]}." . PHP_EOL;
