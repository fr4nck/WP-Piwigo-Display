'use strict';

const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

const code = fs.readFileSync(__dirname + '/../blocks/piwigo/index.js', 'utf8');
let registration = null;
const blocks = {
    registerBlockType: function (name, settings) {
        assert.strictEqual(name, 'wp-piwigo-display/gallery');
        registration = settings;
    },
    createBlock: function (name, attributes) {
        return { name: name, attributes: attributes };
    }
};
vm.runInNewContext(code, {
    window: {
        wp: {
            blocks: blocks,
            blockEditor: {},
            components: {},
            element: { createElement: function () {} },
            serverSideRender: {},
            compose: {},
            i18n: { __: function (text) { return text; } }
        }
    }
});
assert.ok(registration && registration.transforms && registration.transforms.from);
const converter = registration.transforms.from.find(function (candidate) {
    return candidate.type === 'shortcode' && candidate.tag === 'piwigo';
});
assert.strictEqual(typeof converter.transform, 'function', 'La conversion doit utiliser une fonction reconnue par WordPress.');
const block = converter.transform({
    named: {
        album: '111', type: 'slider', recursive: 'true', depth: '2',
        orientation: 'paysage,portrait', tag_mode: 'all', interval: '5000',
        shape: 'rounded', radius: '12', transparent_background: 'false',
        url: 'https://example.test', unknown: 'discard'
    }
});
assert.strictEqual(block.name, 'wp-piwigo-display/gallery');
const attrs = block.attributes;
assert.strictEqual(attrs.albumId, '111');
assert.strictEqual(attrs.displayType, 'slider');
assert.strictEqual(attrs.recursive, true);
assert.strictEqual(attrs.depth, 2);
assert.strictEqual(attrs.interval, 5000);
assert.strictEqual(attrs.tagMode, 'all');
assert.strictEqual(attrs.shape, 'rounded');
assert.strictEqual(attrs.radius, 12);
assert.strictEqual(attrs.transparentBackground, false);
assert.strictEqual(attrs.piwigoUrl, 'https://example.test');
assert.strictEqual(attrs.unknown, undefined);
assert.strictEqual(JSON.stringify(Array.from(attrs.orientations)), JSON.stringify(['paysage', 'portrait']));
console.log('WordPress shortcode-to-block transform: OK');
