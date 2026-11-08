'use strict';
const assert = require('assert');
const fs = require('fs');

const css = fs.readFileSync(__dirname + '/../assets/css/wp-piwigo-display.css', 'utf8');
const php = fs.readFileSync(__dirname + '/../includes/class-wpd-shortcode.php', 'utf8');
assert.match(php, /<div class="wpd-slider-layout wpd-slider-align-%1\$s" style="--wpd-slider-width:%2\$s">/);
assert.match(css, /\.wpd-slider-layout\s*\{[^}]*width:\s*var\(--wpd-slider-width,\s*100%\)/s, 'La largeur doit venir du paramètre shortcode.');
assert.match(css, /\.wpd-slider-layout\s*\{[^}]*max-width:\s*100%/s, 'La largeur doit être bornée au conteneur.');
assert.match(css, /\.wpd-slider-layout \.wp-piwigo-display-slider\s*\{[^}]*width:\s*100%/s, 'La piste doit tenir dans le conteneur.');
assert.match(css, /@media\s*\(max-width:\s*600px\)[\s\S]*?\.wpd-slider-layout\s*\{[^}]*width:\s*100%/s, 'La largeur doit s’adapter au mobile.');
assert.match(css, /\.wpd-slider-layout\.wpd-slider-align-left[\s\S]*?float:\s*left/);
assert.match(css, /\.wpd-slider-layout\.wpd-slider-align-right[\s\S]*?float:\s*right/);
console.log('Diaporama : contrôles CSS largeur et alignement PASS (mesure réelle à requalifier).');
