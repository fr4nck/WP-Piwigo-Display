'use strict';

/* Runtime tests: closing during AJAX, and invalid album payloads. */
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const code = fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-album-picker.js', 'utf8');

function makePicker() {
    const rootElement = {}, inputElement = {};
    let click, successHandler, pending;
    const picker = {
        attrs: { hidden: 'hidden' },
        htmlValue: null,
        emptyCalls: 0,
        attr: function (key, value) {
            if (typeof key === 'object') { Object.assign(this.attrs, key); return this; }
            if (arguments.length === 1) return this.attrs[key];
            this.attrs[key] = value;
            return this;
        },
        removeAttr: function (key) { delete this.attrs[key]; return this; },
        empty: function () { this.emptyCalls += 1; return this; },
        html: function (value) { this.htmlValue = value; return this; },
        on: function () { return this; }
    };
    const button = {
        attrs: {},
        attr: function (key, value) {
            if (typeof key === 'object') Object.assign(this.attrs, key);
            else this.attrs[key] = value;
            return this;
        },
        on: function (event, handler) { if (event === 'click') click = handler; return this; },
        trigger: function () { return this; }
    };
    const root = {
        values: {},
        data: function (key, value) {
            if (arguments.length === 1) return this.values[key];
            this.values[key] = value;
            return this;
        },
        find: function (selector) { return selector === '.wpd-browse-albums' ? button : picker; },
        length: 1
    };
    const input = { length: 1 };
    function jquery(value) {
        if (typeof value === 'function') return;
        if (value === rootElement) return root;
        if (value === inputElement) return input;
        if (typeof value === 'string' && value.charAt(0) === '<') {
            return { text: function (msg) { this.message = String(msg); return this; } };
        }
        throw new Error('Unexpected jQuery call');
    }
    jquery.Deferred = function () {
        return {
            resolve: function (value) { this.value = value; return this; },
            reject: function (message) { this.error = message; this.rejected = true; return this; },
            promise: function () { return this; }
        };
    };
    jquery.ajax = function () {
        pending = {
            doneCallbacks: [],
            failCallbacks: [],
            always: function (callback) { this.finallyCallback = callback; return this; },
            done: function (callback) { this.doneCallbacks.push(callback); return this; },
            fail: function (callback) { this.failCallbacks.push(callback); return this; }
        };
        return { then: function (handler) { successHandler = handler; return pending; } };
    };

    const config = { ajaxUrl: '/wp-admin/admin-ajax.php', nonce: 'test', labels: { error: 'Erreur albums' } };
    const window = { WPDAlbumPickerConfig: config };
    vm.runInNewContext(code, { jQuery: jquery, window: window, WPDAlbumPickerConfig: config, document: {} });
    window.WPDAlbumPicker.attach(rootElement, inputElement);
    return {
        picker: picker,
        click: function () { click(); },
        finish: function (response) {
            const processed = successHandler(response);
            if (processed && processed.rejected) pending.failCallbacks.forEach(function (cb) { cb(processed.error); });
            else pending.doneCallbacks.forEach(function (cb) { cb(processed); });
            if (pending.finallyCallback) pending.finallyCallback();
        }
    };
}

const closed = makePicker();
closed.click();
closed.click();
assert.strictEqual(closed.picker.attr('hidden'), 'hidden');
const emptyBefore = closed.picker.emptyCalls;
closed.finish({ success: true, data: { albums: [] } });
assert.strictEqual(closed.picker.attr('hidden'), 'hidden', 'An old request reopened the picker.');
assert.strictEqual(closed.picker.emptyCalls, emptyBefore, 'A closed picker was redrawn.');

const invalid = makePicker();
invalid.click();
invalid.finish({ success: true, data: { albums: { bad: 'payload' } } });
assert.ok(invalid.picker.htmlValue, 'An invalid album list must show an error.');
assert.strictEqual(invalid.picker.htmlValue.message, 'Erreur albums');

console.log('Sélecteur : fermeture asynchrone et réponse invalide : PASS.');
