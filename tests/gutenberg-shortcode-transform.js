'use strict';
const vm = require('vm');
const fs = require('fs');
const assert = require('assert');
let settings = null;
const blocks = {
    registerBlockType: function (name, cfg) { settings = cfg; },
    createBlock: function (name, attributes) { return { name: name, attributes: attributes }; }
};
const wp = { blocks: blocks, blockEditor: {}, components: {}, element: {}, serverSideRender: {}, compose: {}, i18n: { __: function (s) { return s; } } };
vm.runInNewContext(fs.readFileSync(__dirname + '/../blocks/piwigo/index.js', 'utf8'), { window: { wp: wp } });
assert.ok(settings);
const entry = settings.transforms.from[0];
assert.strictEqual(typeof entry.transform, 'function');
const converted = entry.transform({ named: {
    album: '154', type: 'slider', recursive: 'true', depth: '2', limit: '12',
    orientation: 'paysage,portrait', tag_mode: 'all', url: 'https://gallery.example.test',
    transparent_background: 'false', radius: '8', align: 'right'
} });
assert.strictEqual(converted.name, 'wp-piwigo-display/gallery');
assert.strictEqual(converted.attributes.albumId, '154');
assert.strictEqual(converted.attributes.displayType, 'slider');
assert.strictEqual(converted.attributes.recursive, true);
assert.strictEqual(converted.attributes.depth, 2);
assert.strictEqual(converted.attributes.limit, 12);
assert.deepStrictEqual(Array.from(converted.attributes.orientations), ['paysage','portrait']);
assert.strictEqual(converted.attributes.tagMode, 'all');
assert.strictEqual(converted.attributes.piwigoUrl, 'https://gallery.example.test');
assert.strictEqual(converted.attributes.transparentBackground, false);
assert.strictEqual(converted.attributes.radius, 8);
assert.strictEqual(converted.attributes.align, 'right');
// S-06: extra block attributes must round-trip the canonical snake_case
// shortcode keys, including photo-text and all layout-specific options.
const additional = entry.transform({ named: {
    album: '154', masonry_columns: '3', justified_row_height: '275',
    collage_seed: '42', collage_rotation: '8', photo_text: 'Pêle-Mêle',
    photo_text_line_height: '115', photo_text_letter_spacing: '-7',
    photo_text_outline: 'false', photo_text_max_images: '12'
} });
assert.strictEqual(additional.attributes.masonryColumns, 3);
assert.strictEqual(additional.attributes.justifiedRowHeight, 275);
assert.strictEqual(additional.attributes.collageSeed, '42');
assert.strictEqual(additional.attributes.collageRotation, 8);
assert.strictEqual(additional.attributes.photoText, 'Pêle-Mêle');
assert.strictEqual(additional.attributes.photoTextLineHeight, 115);
assert.strictEqual(additional.attributes.photoTextLetterSpacing, -7);
assert.strictEqual(additional.attributes.photoTextOutline, false);
assert.strictEqual(additional.attributes.photoTextMaxImages, 12);

console.log('Gutenberg : conversion de shortcode en bloc : PASS.');
