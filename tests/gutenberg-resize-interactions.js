'use strict';
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

let block;
let attrs = { albumId: '154', displayType: 'slider', width: '50%', height: '300px' };
const refs = [], states = [], listeners = {};
let refIndex = 0, stateIndex = 0, effects = [], layouts = [];
const document = {
    activeElement: null,
    addEventListener(name, fn) { listeners[name] = fn; },
    removeEventListener(name, fn) { if (listeners[name] === fn) delete listeners[name]; }
};
const element = {
    createElement(type, props, ...children) { return { type, props: props || {}, children }; },
    useRef(initial) {
        const i = refIndex++;
        if (!(i in refs)) refs[i] = { current: initial };
        return refs[i];
    },
    useState(initial) {
        const i = stateIndex++;
        if (!(i in states)) states[i] = initial;
        return [states[i], value => { states[i] = value; }];
    },
    useEffect(fn) { effects.push(fn); },
    useLayoutEffect(fn) { layouts.push(fn); }
};
const wp = {
    blocks: { registerBlockType(name, config) { block = config; }, createBlock() {} },
    blockEditor: { InspectorControls: 'inspector', useBlockProps() { return {}; } },
    components: Object.fromEntries(['PanelBody', 'TextControl', 'ToggleControl', 'SelectControl', 'CheckboxControl', 'Button', 'Placeholder'].map(x => [x, x])),
    element,
    serverSideRender: 'server-render',
    compose: { useDebounce(fn) { return fn; } },
    i18n: { __(x) { return x; } }
};
vm.runInNewContext(fs.readFileSync(__dirname + '/../blocks/piwigo/index.js', 'utf8'), { window: { wp }, document });
function find(node, name) {
    if (!node || typeof node !== 'object') return null;
    if (node.props && String(node.props.className || '').includes(name)) return node;
    for (const child of (node.children || []).flat(Infinity)) {
        const found = find(child, name);
        if (found) return found;
    }
    return null;
}
function render() {
    refIndex = 0; stateIndex = 0; effects = []; layouts = [];
    const tree = block.edit({ attributes: attrs, setAttributes(value) { attrs = Object.assign({}, attrs, value); } });
    const panel = find(tree, 'wpd-block-slider-resizer');
    assert.ok(panel);
    panel.props.ref.current = {
        parentElement: { getBoundingClientRect() { return { width: 1000 }; } },
        getBoundingClientRect() { return { width: 500, height: 300 }; },
        querySelector() { return { getBoundingClientRect() { return { height: 300 }; } }; }
    };
    for (const name of ['wpd-block-slider-handle-width', 'wpd-block-slider-handle-height']) {
        const node = find(tree, name);
        assert.ok(node && node.props.ref);
        node.props.ref.current = { focus() { document.activeElement = this; } };
    }
    layouts.forEach(fn => fn());
    effects.forEach(fn => fn());
    return tree;
}

let tree = render();
let handle = find(tree, 'wpd-block-slider-handle-width');
let prevented = false, stopped = false;
handle.props.onKeyDown({key: 'ArrowRight', shiftKey: false, preventDefault() { prevented = true; }, stopPropagation() { stopped = true; }});
assert.strictEqual(attrs.width, '51%');
document.activeElement = null;
tree = render();
handle = find(tree, 'wpd-block-slider-handle-width');
assert.strictEqual(document.activeElement, handle.props.ref.current, 'Le focus clavier doit être restauré.');
assert.ok(prevented && stopped);
let captured = false;
handle.props.onPointerDown({
    pointerId: 1, button: 0, clientX: 100, clientY: 20,
    currentTarget: {setPointerCapture(id) { captured = id === 1; }},
    preventDefault() {}, stopPropagation() {}
});
assert.ok(captured, 'La poignée doit capturer les événements du pointeur.');
render();
assert.strictEqual(typeof listeners.pointermove, 'function');
listeners.pointermove({clientX: 150, clientY: 20, preventDefault() {}});
assert.strictEqual(attrs.width, '56%', 'Le glissement de 50 px sur 1000 px doit augmenter de 5 %.');
listeners.pointerup();
assert.strictEqual(states[0], '');
console.log('Redimensionnement Gutenberg : clavier, focus et pointeur PASS.');
