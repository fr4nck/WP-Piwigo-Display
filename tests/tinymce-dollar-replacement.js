'use strict';

const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const handlers = {};
const tinymce = {
    PluginManager: {
        add: function (name, factory) {
            if (name !== 'wpd_shortcode_preview') return;
            factory({
                dom: { encode: function (value) { return String(value); } },
                on: function (event, callback) { handlers[event] = callback; }
            });
        }
    }
};
const document = {
    createTextNode: function (text) { return { textContent: text }; },
    createElement: function () {
        return {
            set innerHTML(html) { this.html = html; },
            get innerHTML() { return this.html; },
            querySelectorAll: function () {
                const match = this.html.match(/data-wpd-shortcode="([^"]+)"/);
                if (!match) return [];
                const container = this;
                const node = {
                    getAttribute: function () { return match[1]; },
                    parentNode: {
                        replaceChild: function (placeholder) {
                            container.html = container.html.replace(/<span[^>]*>.*?<\/span>/, placeholder.textContent);
                        }
                    }
                };
                return [node];
            }
        };
    }
};
vm.runInNewContext(
    fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-tinymce.js', 'utf8'),
    { window: { tinymce: tinymce }, tinymce: tinymce, document: document, encodeURIComponent: encodeURIComponent, decodeURIComponent: decodeURIComponent }
);
assert.strictEqual(typeof handlers.PostProcess, 'function');
const shortcode = '[piwigo album="154" tag="O$\'Hara$&"]';
const original = '<p>AVANT</p><span class="wpd-tinymce-shortcode" data-wpd-shortcode="' +
    encodeURIComponent(shortcode) + '">Piwigo</span><p>APRES</p>';
const event = { get: true, content: original };
handlers.PostProcess(event);
assert.strictEqual(event.content, '<p>AVANT</p>' + shortcode + '<p>APRES</p>');
console.log('TinyMCE : préservation littérale des $ dans les shortcodes : OK.');
