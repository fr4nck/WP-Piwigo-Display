'use strict';
const assert = require('assert');
const fs = require('fs');

const css = fs.readFileSync(__dirname + '/../assets/css/wp-piwigo-display.css', 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
const php = fs.readFileSync(__dirname + '/../includes/class-wpd-shortcode.php', 'utf8');
const plugin = fs.readFileSync(__dirname + '/../includes/class-wpd-plugin.php', 'utf8');
const editor = fs.readFileSync(__dirname + '/../blocks/piwigo/editor.css', 'utf8');
const rule = (selector) => {
    const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = css.match(new RegExp('(^|\\})\\s*' + escaped + '\\s*\\{([^}]*)\\}'));
    return match ? match[2] : null;
};
assert.match(php, /<div class="wpd-slider-layout wpd-slider-align-%1\$s" style="--wpd-slider-width:%2\$s">/);
// Le conteneur garde la largeur de la colonne du thème (mise en page contrainte des thèmes à blocs).
const layout = rule('.wpd-slider-layout');
assert.ok(layout !== null, 'Règle du conteneur absente.');
assert.doesNotMatch(layout, /(^|[^-])width\s*:/, 'Le conteneur ne doit pas imposer sa largeur au thème.');
assert.doesNotMatch(plugin, /wpd-slider-layout\{width/, 'Aucun style en ligne ne doit écraser la largeur du thème.');
// Le pourcentage s'applique au diaporama, borné au conteneur.
const slider = rule('.wpd-slider-layout .wp-piwigo-display-slider');
assert.match(slider, /width:\s*var\(--wpd-slider-width,\s*100%\)/, 'La largeur doit venir du paramètre shortcode.');
assert.match(slider, /max-width:\s*100%/, 'La largeur doit être bornée au conteneur.');
assert.match(rule('.wpd-slider-layout.wpd-slider-align-left .wp-piwigo-display-slider'), /float:\s*left/);
assert.match(rule('.wpd-slider-layout.wpd-slider-align-right .wp-piwigo-display-slider'), /float:\s*right/);
assert.match(css, /@media\s*\(max-width:\s*782px\)[\s\S]*?\.wpd-slider-layout \.wp-piwigo-display-slider[^{]*\{[^}]*width:\s*100%/, 'La largeur doit s’adapter au mobile.');
// Dans l'éditeur, le cadre redimensionnable porte déjà le pourcentage.
assert.match(editor, /\.wpd-block-slider-resizer \.wpd-slider-layout \.wp-piwigo-display-slider\s*\{[^}]*width:\s*100%/);
console.log('Diaporama : contrôles CSS largeur et alignement PASS (mesure réelle requise en navigateur).');
