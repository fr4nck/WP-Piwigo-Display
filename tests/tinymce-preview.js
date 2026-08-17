'use strict';

const fs = require('fs');
const vm = require('vm');

let pluginFactory = null;
const handlers = {};
const tinymce = {
    PluginManager: {
        add: function (name, factory) {
            if (name === 'wpd_shortcode_preview') pluginFactory = factory;
        }
    }
};

vm.runInNewContext(
    fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-tinymce.js', 'utf8'),
    { window: { tinymce: tinymce }, tinymce: tinymce, encodeURIComponent: encodeURIComponent, decodeURIComponent: decodeURIComponent }
);

if (typeof pluginFactory !== 'function') {
    throw new Error('Le plugin TinyMCE ne s’est pas enregistré.');
}

pluginFactory({
    dom: {
        encode: function (value) { return String(value); }
    },
    on: function (name, callback) { handlers[name] = callback; }
});

const shortcode = '[piwigo album="225" type="slider" transparent_background="true"]';
const event = { content: shortcode };
handlers.BeforeSetContent(event);

const encoded = encodeURIComponent(shortcode);
if (event.content.indexOf('data-wpd-shortcode="' + encoded + '"') === -1) {
    throw new Error('Le shortcode doit être encodé intégralement dans l’attribut de prévisualisation.');
}

if (event.content.indexOf('data-wpd-shortcode="[piwigo album="') !== -1) {
    throw new Error('Un shortcode brut ne doit jamais être injecté dans un attribut HTML.');
}

console.log('TinyMCE shortcode preview encoding: OK');
