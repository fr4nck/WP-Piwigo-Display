'use strict';

/* Native composer events and explicit refresh, with a mocked WordPress/jQuery DOM. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-album-picker.js', 'utf8');

function fixture(initialValue = '') {
    const requests = [];
    const events = [];
    const focusEvents = [];
    let shortcode = '';
    const rootNode = {};
    const document = { activeElement: null, body: {} };
    const inputNode = {
        value: initialValue,
        dispatchEvent: function (event) {
            events.push(event.type);
            if (event.type === 'input') shortcode = '[piwigo album="' + this.value + '"]';
            return true;
        }
    };
    class Element {
        constructor(html) {
            this.length = 1;
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
        html(value) { this.children = value ? [value] : []; this.htmlValue = value; return this; }
        text(value) { this.textValue = value; return this; }
        css() { return this; }
        prop() { return this; }
        addClass(value) { this.classes.push(value); return this; }
        on(event, callback) { this.handlers[event] = callback; return this; }
        trigger(event) {
            if (event === 'focus') { focusEvents.push(this); document.activeElement = this; }
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
        contains(target) {
            if (target === this) return true;
            return this.children.some(child => child instanceof Element && child.contains(target));
        }
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
        if (value instanceof Element) return value;
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
    vm.runInNewContext(code, { jQuery: $, window, document, WPDAlbumPickerConfig: config, Event: NativeEvent });
    window.WPDAlbumPicker.attach(rootNode, inputNode);
    function find(className, rootElement = picker) {
        const result = rootElement.find('.' + className);
        assert.ok(result.length, 'Expected element: ' + className);
        return result.nodes[0];
    }
    return { requests, events, focusEvents, inputNode, picker, button, find, setExternalFocus: () => { document.activeElement = { outside: true }; }, getActive: () => document.activeElement, reload: () => window.WPDAlbumPicker.reload(), getShortcode: () => shortcode };
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

// A late response must not overwrite the album list returned by a newer request.
f.find('wpd-album-refresh').trigger('click');
assert.equal(f.requests.length, 3);
f.button.trigger('click'); // Close the in-flight picker.
f.reload(); // Invalidate shared cache and request, as another picker may do.
f.button.trigger('click'); // Reopen with a distinct request.
assert.equal(f.requests.length, 4);
f.requests[3].finish({ success: true, data: { albums: [{ id: 77, name: 'Récent', depth: 0 }] } });
f.requests[2].finish({ success: true, data: { albums: [{ id: 66, name: 'Obsolète', depth: 0 }] } });
assert.equal(f.find('wpd-album-name').textValue, 'Récent', 'An older request must not redraw the newer album list.');
f.button.trigger('click');
f.button.trigger('click');
assert.equal(f.requests.length, 4, 'The stale response must not replace the new cached result.');
assert.equal(f.find('wpd-album-name').textValue, 'Récent', 'The cache must keep the latest album list.');


const failedRefresh = fixture();
failedRefresh.button.trigger('click');
assert.equal(failedRefresh.focusEvents[0].classes.includes('wpd-album-loading'), true,
    'The loading placeholder must receive focus so Escape remains usable.');
failedRefresh.requests[0].finish({ success: true, data: { albums: [{ id: 7, name: 'Sauvegardé', depth: 0 }] } });
failedRefresh.find('wpd-album-refresh').trigger('click');
failedRefresh.requests[1].finish({ success: false, data: { message: 'Piwigo HTTP 502' } });
assert.ok(failedRefresh.find('wpd-album-refresh'), 'An AJAX error must keep an explicit Retry button.');
failedRefresh.button.trigger('click'); // Close after failure.
failedRefresh.button.trigger('click'); // Reopen without losing previously loaded albums.
assert.equal(failedRefresh.requests.length, 2, 'Failed refresh must preserve the last known-good album cache.');
assert.equal(failedRefresh.find('wpd-album-name').textValue, 'Sauvegardé');

const retry = fixture();
retry.button.trigger('click');
retry.requests[0].finish({ success: false, data: { message: 'Accès Piwigo temporairement indisponible' } });
assert.equal(retry.find('wpd-album-refresh').textValue, 'Actualiser la liste des albums');
retry.find('wpd-album-refresh').trigger('click');
assert.equal(retry.requests.length, 2, 'The error screen must permit a new request.');
retry.requests[1].finish({ success: true, data: { albums: [{ id: 8, name: 'Rétabli', depth: 0 }] } });
assert.equal(retry.find('wpd-album-name').textValue, 'Rétabli');


const manualHash = fixture('#42');
manualHash.button.trigger('click');
manualHash.requests[0].finish({ success: true, data: { albums: [{ id: 42, name: 'Album 42', depth: 0 }] } });
assert.equal(manualHash.find('wpd-album-row').attrs['aria-selected'], 'true', '#42 must highlight ID 42.');

const namedHash = fixture('#2024');
namedHash.button.trigger('click');
namedHash.requests[0].finish({ success: true, data: { albums: [
    { id: 77, name: '#2024', depth: 0 }, { id: 2024, name: 'Autre album', depth: 0 }
] } });
const selectedNamed = namedHash.picker.find('.wpd-album-row').nodes.filter(row => row.attrs['aria-selected'] === 'true');
assert.equal(selectedNamed.length, 1, 'Exactly one album must be highlighted.');
assert.ok(selectedNamed[0].attrs.id.endsWith('-album-77'), 'The literal album name #2024 must win.');

const preselection = fixture();
preselection.button.trigger('click');
preselection.requests[0].finish({ success: true, data: { albums: [{ id: 7, name: 'Présélectionné', depth: 0 }] } });
preselection.find('wpd-album-name').trigger('click');
assert.equal(preselection.inputNode.value, '', 'Clicking the name must not confirm the album.');
preselection.find('wpd-album-refresh').trigger('click');
preselection.requests[1].finish({ success: true, data: { albums: [{ id: 7, name: 'Présélectionné', depth: 0 }] } });
assert.equal(preselection.find('wpd-album-row').attrs['aria-selected'], 'true',
    'Refreshing should retain the highlighted album.');
assert.equal(preselection.inputNode.value, '', 'Refreshing must never validate the preselection.');


const focus = fixture();
focus.button.trigger('click');
focus.setExternalFocus(); // The user moves to another field while AJAX is pending.
const outside = focus.getActive();
focus.requests[0].finish({ success: true, data: { albums: [{ id: 1, name: 'Photo', depth: 0 }] } });
assert.equal(focus.getActive(), outside, 'A late success must never steal focus from another field.');
focus.find('wpd-album-refresh').trigger('click');
focus.setExternalFocus();
const other = focus.getActive();
focus.requests[1].finish({ success: false, data: { message: 'Piwigo 502' } });
assert.equal(focus.getActive(), other, 'A late AJAX error must never steal focus from another field.');

console.log('Sélecteur : validation native, actualisation et réponses concurrentes : PASS.');
