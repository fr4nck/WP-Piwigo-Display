'use strict';

/* Native composer events and explicit refresh, with a mocked WordPress/jQuery DOM. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-album-picker.js', 'utf8');

function fixture() {
    const requests = [];
    const events = [];
    let shortcode = '';
    const rootNode = {};
    const inputNode = {
        value: '',
        dispatchEvent: function (event) {
            events.push(event.type);
            if (event.type === 'input') shortcode = '[piwigo album="' + this.value + '"]';
            return true;
        }
    };
    class Element {
        constructor(html) {
            this.children = [];
            this.handlers = {};
            this.attrs = {};
            this.classes = (String(html).match(/class="([^"]*)"/) || [,''])[1].split(/\s+/);
            this.value = '';
        }
        attr(key, value) {
            if (typeof key === 'object') { Object.assign(this.attrs, key); return this; }
            if (arguments.length === 1) return this.attrs[key];
            this.attrs[key] = value; return this;
        }
        removeAttr(key) { delete this.attrs[key]; return this; }
        data(key, value) {
            if (!this.dataset) this.dataset = {};
            if (arguments.length === 1) return this.dataset[key];
            this.dataset[key] = value; return this;
        }
        append(...nodes) { this.children.push(...nodes); return this; }
        empty() { this.children = []; return this; }
        html(value) { this.children = []; this.htmlValue = value; return this; }
        text(value) { this.textValue = value; return this; }
        css() { return this; }
        prop() { return this; }
        addClass(value) { this.classes.push(value); return this; }
        on(event, callback) { this.handlers[event] = callback; return this; }
        trigger(event) {
            if (this.handlers[event]) this.handlers[event].call(this, { type: event, preventDefault() {} });
            return this;
        }
        val(value) {
            if (arguments.length === 0) return this.native ? this.native.value : this.value;
            if (this.native) this.native.value = value;
            else this.value = value;
            return this;
        }
        get(index) { return index === 0 ? (this.native || this) : undefined; }
        find(selector) {
            const classMatch = selector.match(/\.([a-z0-9-]+)/);
            const className = classMatch && classMatch[1];
            const dataMatch = selector.match(/data-album-id="([^"]+)"/);
            const result = [];
            function visit(node) {
                for (const child of node.children || []) {
                    if (child instanceof Element) {
                        if ((!className || child.classes.includes(className)) &&
                            (!dataMatch || child.attrs['data-album-id'] === dataMatch[1])) result.push(child);
                        visit(child);
                    }
                }
            }
            visit(this);
            return new Collection(result);
        }
    }
    class Collection {
        constructor(nodes) { this.nodes = nodes; this.length = nodes.length; }
        trigger(event) { this.nodes.forEach(node => node.trigger(event)); return this; }
        first() { return new Collection(this.nodes.slice(0, 1)); }
        eq(n) { return new Collection(this.nodes.slice(n, n + 1)); }
        index(node) { return this.nodes.indexOf(node); }
    }
    const root = new Element('');
    const button = new Element('');
    const picker = new Element('');
    picker.attr('hidden', 'hidden');
    root.find = selector => selector === '.wpd-browse-albums' ? button : picker;
    const input = new Element('');
    input.native = inputNode;
    function $(value) {
        if (typeof value === 'function') return;
        if (value === rootNode) return root;
        if (value === inputNode) return input;
        if (typeof value === 'string' && value.startsWith('<')) return new Element(value);
        throw new Error('Unexpected jQuery value');
    }
    $.ajax = function () {
        const request = {
            then(fn) { this.success = fn; return this; },
            always(fn) { this.alwaysHandler = fn; return this; },
            done(fn) { this.doneHandler = fn; return this; },
            fail(fn) { this.failHandler = fn; return this; },
            finish(response) {
                const result = this.success(response);
                if (result && result.isError) {
                    if (this.failHandler) this.failHandler(result.message);
                } else if (this.doneHandler) this.doneHandler(result);
                if (this.alwaysHandler) this.alwaysHandler();
            }
        };
        requests.push(request);
        return request;
    };
    $.Deferred = function () {
        return {
            resolve(value) { this.value = value; return this; },
            reject(message) { this.isError = true; this.message = message; return this; },
            promise() { return this; },
            done(fn) { if (!this.isError) fn(this.value); return this; },
            fail(fn) { if (this.isError) fn(this.message); return this; }
        };
    };
    const config = { ajaxUrl: '/wp-admin/admin-ajax.php', nonce: 'test', labels: {} };
    const window = { WPDAlbumPickerConfig: config };
    class NativeEvent { constructor(type, options) { this.type = type; this.bubbles = options.bubbles; } }
    vm.runInNewContext(code, { jQuery: $, window, document: {}, WPDAlbumPickerConfig: config, Event: NativeEvent });
    window.WPDAlbumPicker.attach(rootNode, inputNode);
    function find(className, rootElement = picker) {
        const result = rootElement.find('.' + className);
        assert.ok(result.length, 'Expected element: ' + className);
        return result.nodes[0];
    }
    return { requests, events, inputNode, picker, button, find, getShortcode: () => shortcode };
}

const f = fixture();
f.button.trigger('click');
assert.equal(f.requests.length, 1, 'Opening the picker calls Piwigo.');
f.requests[0].finish({ success: true, data: { albums: [{ id: 17, name: 'Existant', path: 'Existant', depth: 0 }] } });
f.find('wpd-album-name').trigger('click');
assert.equal(f.inputNode.value, '', 'Clicking a name must only preselect the album.');
assert.deepEqual(f.events, [], 'Events must wait for explicit validation.');
f.find('wpd-album-confirm').trigger('click');
assert.equal(f.inputNode.value, '17', 'Confirmation must populate the input.');
assert.deepEqual(f.events, ['input', 'change'], 'The composer needs native DOM events.');
assert.equal(f.getShortcode(), '[piwigo album="17"]');
f.events.length = 0;
f.find('wpd-album-search').val('Nouveau').trigger('input');
f.find('wpd-album-refresh').trigger('click');
assert.equal(f.requests.length, 2, 'Refresh must fetch a fresh list rather than reuse the JS cache.');
f.requests[1].finish({ success: true, data: { albums: [
    { id: 17, name: 'Existant', path: 'Existant', depth: 0 },
    { id: 42, name: 'Nouveau', path: 'Nouveau', depth: 0 }
] } });
assert.equal(f.find('wpd-album-search').val(), 'Nouveau', 'Refresh preserves the search filter.');
f.find('wpd-album-name').trigger('click');
assert.equal(f.inputNode.value, '17', 'A preselection must not change the existing shortcode.');
f.find('wpd-album-confirm').trigger('click');
assert.equal(f.inputNode.value, '42', 'New albums must be selectable after refresh.');
assert.equal(f.getShortcode(), '[piwigo album="42"]');
console.log('Sélecteur : sélection native, mise à jour du shortcode et actualisation : PASS.');
