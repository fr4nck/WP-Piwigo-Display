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
            }, useRef: initial => {
                const index = cursor++;
                if (!(index in slots)) slots[index] = { current: initial };
                return slots[index];
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
        refresh: () => {
            const button = render().find(node => node.type === 'Button' && node.children[0] === 'Actualiser la liste des albums');
            assert.ok(button, 'Le bouton d’actualisation doit exister.');
            button.props.onClick();
        },
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

    const fresh = fixture([
        response(true, { success: true, data: { albums: [{ id: 17, name: 'Ancien', depth: 0 }] } }),
        response(true, { success: true, data: { albums: [{ id: 17, name: 'Ancien', depth: 0 }, { id: 42, name: 'Nouveau', depth: 0 }] } })
    ]);
    fresh.toggle(); await flush();
    assert.equal(fresh.state()[1].length, 1);
    fresh.refresh(); await flush();
    assert.equal(fresh.calls(), 2, 'Actualiser doit appeler Piwigo malgré le cache.');
    assert.equal(fresh.state()[1].length, 2, 'Le nouvel album doit être visible.');
    assert.equal(fresh.state()[3], '', 'L’actualisation ne doit pas créer d’erreur.');

    const recovered = fixture([
        new Error('Réseau indisponible'),
        response(true, { success: true, data: { albums: [{ id: 42, name: 'Nouveau', depth: 0 }] } })
    ]);
    recovered.toggle(); await flush();
    assert.equal(recovered.state()[3], 'Réseau indisponible');
    recovered.refresh(); await flush();
    assert.equal(recovered.state()[3], '', 'L’actualisation doit permettre de reprendre après une erreur.');
    assert.equal(recovered.state()[1].length, 1);

    // A1: closing during a refresh must not strand loading or disable Refresh forever.
    let resolveInFlight;
    const inFlight = new Promise(resolve => { resolveInFlight = resolve; });
    const interrupted = fixture([
        response(true, { success: true, data: { albums: [{ id: 17, name: 'Initial', depth: 0 }] } }),
        inFlight,
        response(true, { success: true, data: { albums: [{ id: 42, name: 'Nouveau', depth: 0 }] } })
    ]);
    interrupted.toggle(); await flush();
    interrupted.refresh();
    assert.equal(interrupted.state()[2], true, 'Refresh should display the spinner.');
    interrupted.toggle();
    assert.equal(interrupted.state()[2], false, 'Closing must clear the spinner immediately.');
    interrupted.toggle();
    assert.equal(interrupted.state()[2], false, 'Reopening must not keep the spinner.');
    const refreshAfterReopen = interrupted.render().find(node => node.type === 'Button' && node.children[0] === 'Actualiser la liste des albums');
    assert.ok(refreshAfterReopen && !refreshAfterReopen.props.disabled, 'Refresh must be enabled again.');
    resolveInFlight(response(true, { success: true, data: { albums: [{ id: 66, name: 'Ancienne réponse', depth: 0 }] } }));
    await flush();
    assert.equal(interrupted.state()[1][0].id, 17, 'An obsolete response must not replace the displayed list.');
    interrupted.refresh(); await flush();
    assert.equal(interrupted.state()[1][0].id, 42, 'The user must be able to refresh again.');
    assert.equal(interrupted.state()[2], false);

    // Closing after a failed refresh restores the previous album list instead of a sticky error.
    const retryAfterError = fixture([
        response(true, { success: true, data: { albums: [{ id: 7, name: 'Original', depth: 0 }] } }),
        response(false, { success: false, data: { message: 'Piwigo HTTP 502' } })
    ]);
    retryAfterError.toggle(); await flush();
    retryAfterError.refresh(); await flush();
    assert.equal(retryAfterError.state()[3], 'Piwigo HTTP 502');
    retryAfterError.toggle();
    retryAfterError.toggle();
    assert.equal(retryAfterError.state()[3], '', 'The error should not remain on reopening.');
    assert.equal(retryAfterError.state()[1][0].id, 7, 'Previously loaded albums must remain available.');

    console.log('Gutenberg : erreurs, nouvelle tentative, actualisation et reprise : PASS.');
})().catch(error => { console.error(error); process.exitCode = 1; });
