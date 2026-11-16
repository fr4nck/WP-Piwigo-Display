'use strict';

// Multi-instance React hook fixture: shared HTTP cache, separate picker state.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(__dirname + '/../blocks/piwigo/gutenberg-parity.js', 'utf8');

const makeElement = (type, props, ...children) => ({type, props: props || {}, children});
const flatten = node => !node || typeof node !== 'object' ? [] :
    Array.isArray(node) ? node.flatMap(flatten) : [node, ...node.children.flatMap(flatten)];
const response = albums => ({
    ok: true, json: () => Promise.resolve({success: true, data: {albums}})
});
const flush = () => new Promise(resolve => setImmediate(resolve));
let registered, calls = 0, active, cursor = 0;
const answers = [];
const useState = initial => {
    const owner = active, index = cursor++;
    if (!(index in owner.slots)) owner.slots[index] = initial;
    return [owner.slots[index], value => {
        owner.slots[index] = typeof value === 'function' ? value(owner.slots[index]) : value;
    }];
};
const useRef = initial => {
    const owner = active, index = cursor++;
    if (!(index in owner.slots)) owner.slots[index] = {current: initial};
    return owner.slots[index];
};
const useEffect = (callback, dependencies) => {
    const owner = active, index = cursor++, previous = owner.effects[index];
    if (!previous || dependencies.some((value, i) => value !== previous.dependencies[i])) {
        if (previous && typeof previous.cleanup === 'function') previous.cleanup();
        owner.effects[index] = {dependencies: dependencies.slice(), cleanup: callback()};
    }
};
const components = Object.fromEntries(
    ['PanelBody', 'SelectControl', 'TextControl', 'Button', 'Spinner', 'Notice'].map(x => [x, x])
);
const window = {
    WPDAlbumPickerConfig: {ajaxUrl: '/wp-admin/admin-ajax.php', nonce: 'fixture'},
    FormData: class { append() {} },
    fetch: () => {
        calls++;
        const result = answers.shift();
        if (!result) throw new Error('Unexpected additional HTTP request');
        return Promise.resolve(result);
    },
    wp: {
        hooks: {addFilter: (hook, name, callback) => { registered = callback; }},
        compose: {createHigherOrderComponent: callback => callback},
        element: {createElement: makeElement, Fragment: 'Fragment', useState, useRef, useEffect},
        blockEditor: {InspectorControls: 'InspectorControls'},
        components,
        i18n: {__: text => text}
    }
};
vm.runInNewContext(source, {window, Promise});
assert.equal(typeof registered, 'function', 'Gutenberg parity filter must register.');
const wrapper = registered('BlockEdit')({
    name: 'wp-piwigo-display/gallery', attributes: {}, setAttributes() {}
});
const picker = flatten(wrapper).find(node => typeof node.type === 'function' && node.type.name === 'AlbumPicker');
assert.ok(picker, 'AlbumPicker component must be present.');

const instances = [0, 1].map(() => ({
    slots: [], effects: {}, props: {value: '', onChange() {}}
}));
function render(index) {
    active = instances[index];
    cursor = 0;
    return flatten(picker.type(active.props));
}
function click(index, label) {
    const button = render(index).find(node => node.type === 'Button' && node.children[0] === label);
    assert.ok(button, 'Missing action: ' + label);
    button.props.onClick();
}
const rows = index => render(index).filter(node => node.props.role === 'treeitem');
for (let index = 0; index < instances.length; index++) render(index);

(async () => {
    // Two simultaneous blocks must share the same initial request.
    answers.push(response([{id: 17, name: 'Ancien', depth: 0}]));
    click(0, 'Choisir dans Piwigo');
    click(1, 'Choisir dans Piwigo');
    assert.equal(calls, 1, 'Both blocks must share one in-flight request.');
    await flush();
    assert.equal(rows(0).length, 1);
    assert.equal(rows(1).length, 1);

    // Refresh in block A updates block B without another network request.
    answers.push(response([{id: 17, name: 'Ancien', depth: 0}, {id: 42, name: 'Nouveau', depth: 0}]));
    click(0, 'Actualiser la liste des albums');
    await flush();
    assert.equal(calls, 2, 'One refresh must cause exactly one new request.');
    assert.equal(rows(0).length, 2);
    assert.equal(rows(1).length, 2, 'The other open block must see refreshed albums.');

    // A pending old response must not overwrite a later refresh from block B.
    let resolveOld;
    const oldRequest = new Promise(resolve => { resolveOld = resolve; });
    answers.push(oldRequest);
    click(0, 'Actualiser la liste des albums');
    answers.push(response([{id: 91, name: 'Version récente', depth: 0}]));
    click(1, 'Actualiser la liste des albums');
    await flush();
    assert.equal(rows(0).length, 1);
    assert.equal(rows(1).length, 1);
    assert.equal(instances[0].slots[1][0].id, 91);
    assert.equal(instances[1].slots[1][0].id, 91);
    resolveOld(response([{id: 66, name: 'Réponse obsolète', depth: 0}]));
    await flush();
    assert.equal(instances[0].slots[1][0].id, 91, 'Old response replaced the list in block A.');
    assert.equal(instances[1].slots[1][0].id, 91, 'Old response replaced the list in block B.');

    // Undo/redo remains controlled separately for each block.
    instances[1].props.value = '91';
    render(1);
    assert.equal(rows(1)[0].props['aria-selected'], true);
    instances[1].props.value = '17';
    render(1);
    assert.equal(rows(1)[0].props['aria-selected'], false);

    // Unmount one picker: updates must not leak to an abandoned instance.
    const effect = Object.values(instances[1].effects).find(value =>
        value.dependencies.length === 0 && typeof value.cleanup === 'function'
    );
    assert.ok(effect, 'Subscription must have an unmount cleanup.');
    effect.cleanup();
    answers.push(response([{id: 93, name: 'Après démontage', depth: 0}]));
    click(0, 'Actualiser la liste des albums');
    await flush();
    assert.equal(instances[0].slots[1][0].id, 93);
    assert.equal(instances[1].slots[1][0].id, 91, 'An unmounted picker was updated.');

    console.log('Gutenberg multi-block cache refresh, stale-response isolation and unmount: PASS.');
})().catch(error => { console.error(error); process.exitCode = 1; });
