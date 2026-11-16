'use strict';

// S-05: run the actual attribute encoder expressions found in the two editors.
// WordPress shortcodes do not treat backslash-escaped quotes as quoted values.
// A versioned URI encoding must preserve quotes, brackets, ampersands and UTF-8.
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const classic = fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-classic-editor.js', 'utf8');
const composer = fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-composer-parity.js', 'utf8');

const classicMatch = classic.match(/(function escapeValue\(input\)\s*\{[^\n]+\})/);
const composerMatch = composer.match(/const escapeValue\s*=\s*([^\n;]+);/);
assert.ok(classicMatch, 'Classic Editor encoder must exist.');
assert.ok(composerMatch, 'Admin composer encoder must exist.');

const classicEncoder = vm.runInNewContext('(' + classicMatch[1] + ')', { encodeURIComponent });
const composerEncoder = vm.runInNewContext('(' + composerMatch[1] + ')', { encodeURIComponent });
const cases = [
    'La "Roche" [aux Fées] & la famille',
    'Nom avec apostrophe O\'Hara',
    'Ligne 1\nLigne 2',
    'https://gallery.example.test/a%20b?x=1&y=2',
    String.raw`Backslash \ et 100%`
];

cases.forEach(function(value) {
    const expected = encodeURIComponent(value);
    assert.strictEqual(classicEncoder(value), expected, 'Classic Editor lost shortcode-safe encoding of ' + value);
    assert.strictEqual(composerEncoder(value), expected, 'Composer lost shortcode-safe encoding of ' + value);
    assert.strictEqual(decodeURIComponent(expected), value, 'Codec round-trip failed.');
});
assert.ok(classic.includes('wpd_codec'), 'Classic Editor must mark URI-coded shortcodes.');
assert.ok(composer.includes('wpd_codec'), 'Composer must mark URI-coded shortcodes.');
console.log('S05_CODEC=PASS exact Classic/Composer generators preserve quotes/brackets/UTF-8.');
