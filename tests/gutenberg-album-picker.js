'use strict';

// Les promesses sont celles de JavaScript ; seules les frontières React et HTTP sont simulées.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync(__dirname + '/../blocks/piwigo/gutenberg-parity.js', 'utf8');

function fixture(responses) {
    let registered, slots = [], cursor = 0, calls = 0;
    const createElement = (type, props, ...children) => ({ type, props: props || {}, children });
    const components = {};
    for (const name of ['PanelBody', 'SelectControl', 'TextControl', 'Button', 'Spinner', 'Notice']) components[name] = name;
    const window = {
        WPDAlbumPickerConfig: { ajaxUrl: '/albums', nonce: 'fixture' },
        FormData: class { append() {} },
        fetch: () => { calls++; const response = responses.shift(); return response instanceof Error ? Promise.reject(response) : Promise.resolve(response); },
        wp: {
            hooks: { addFilter: (hook, name, callback) => { registered = callback; } },
            compose: { createHigherOrderComponent: callback => callback },
            element: { createElement, Fragment: 'Fragment', useState: initial => {
                const index = cursor++;
                if (!(index in slots)) slots[index] = initial;
                return [slots[index], value => { slots[index] = value; }];
            } },
            blockEditor: { InspectorControls: 'InspectorControls' },
            components,
            i18n: { __: value => value }
        }
    };
    vm.runInNewContext(code, { window, Promise });
    function flatten(node) {
        if (!node || typeof node !== 'object') return [];
        if (Array.isArray(node)) return node.flatMap(flatten);
        return [node, ...node.children.flatMap(flatten)];
    }
    const wrapper = registered('BlockEdit')({ name: 'wp-piwigo-display/gallery', attributes: {}, setAttributes() {} });
    const picker = flatten(wrapper).find(node => typeof node.type === 'function' && node.type.name === 'AlbumPicker');
    function render() { cursor = 0; return flatten(picker.type(picker.props)); }
    return {
        toggle: () => render().find(node => node.type === 'Button').props.onClick(),
        state: () => slots,
        calls: () => calls,
        render
    };
}
const response = (ok, data) => ({ ok, json: () => Promise.resolve(data) });
const flush = () => new Promise(resolve => setImmediate(resolve));

(async () => {
    for (const [http, message] of [[400, 'URL non configurée'], [403, 'Accès refusé'], [502, 'Compte non configuré']]) {
        const test = fixture([response(false, { success: false, data: { message } })]);
        test.toggle(); await flush();
        assert.equal(test.state()[3], message, 'Message serveur perdu pour HTTP ' + http);
        assert.equal(test.state()[2], false, 'Chargement bloqué.');
    }
    for (const data of [null, { success: true, data: { albums: {} } }, { success: true, data: { albums: [null] } }]) {
        const test = fixture([response(true, data)]);
        test.toggle(); await flush();
        assert.ok(test.state()[3], 'Une réponse invalide est acceptée.');
        assert.equal(test.state()[2], false);
        assert.doesNotThrow(test.render);
    }
    const invalidJson = fixture([{ ok: false, json: () => Promise.reject(new SyntaxError('JSON')) }]);
    invalidJson.toggle(); await flush();
    assert.ok(invalidJson.state()[3]);
    const retry = fixture([new Error('Réseau indisponible'), response(true, { success: true, data: { albums: [] } })]);
    retry.toggle(); await flush();
    assert.equal(retry.state()[3], 'Réseau indisponible');
    retry.toggle(); retry.toggle(); await flush();
    assert.equal(retry.calls(), 2);
    assert.equal(retry.state()[3], '');
    console.log('Gutenberg : erreurs HTTP, JSON, tableaux invalides et nouvelle tentative : PASS.');
})().catch(error => { console.error(error); process.exitCode = 1; });
