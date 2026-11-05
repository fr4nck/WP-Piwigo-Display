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
console.log('Gutenberg : conversion de shortcode en bloc : PASS.');
