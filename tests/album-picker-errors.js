'use strict';

const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

const source = fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-album-picker.js', 'utf8');

function simulateFailure(payload, status, businessError) {
    const rootNode = {};
    const inputNode = {};
    let click;
    let onSuccess;
    let pending;

    const picker = {
        length: 1,
        attrs: { hidden: 'hidden' },
        attr: function (key, value) {
            if (typeof key === 'object') { Object.assign(this.attrs, key); return this; }
            if (arguments.length === 1) return this.attrs[key];
            this.attrs[key] = value;
            return this;
        },
        removeAttr: function (key) { delete this.attrs[key]; return this; },
        html: function (value) { this.content = value; return this; },
        empty: function () { return this; },
        on: function () { return this; }
    };
    const button = {
        length: 1,
        attrs: {},
        on: function (event, handler) {
            if (event === 'click') click = handler;
            return this;
        },
        attr: function (key, value) {
            if (typeof key === 'object') Object.assign(this.attrs, key);
            else this.attrs[key] = value;
            return this;
        }
    };
    const root = {
        length: 1,
        ready: false,
        data: function (key, value) {
            if (arguments.length === 1) return this[key];
            this[key] = value;
            return this;
        },
        find: function (selector) {
            if (selector === '.wpd-browse-albums') return button;
            if (selector === '.wpd-album-picker') return picker;
            throw new Error('Sélecteur inattendu : ' + selector);
        }
    };
    const input = { length: 1 };

    function jquery(value) {
        if (typeof value === 'function') return;
        if (value === rootNode) return root;
        if (value === inputNode) return input;
        if (typeof value === 'string' && value.charAt(0) === '<') {
            return {
                text: function (message) { this.message = String(message); return this; }
            };
        }
        throw new Error('Appel jQuery inattendu');
    }
    jquery.ajax = function (options) {
        assert.strictEqual(options.data.action, 'wpd_get_albums');
        pending = {
            always: function () { return this; },
            done: function () { return this; },
            fail: function (handler) { this.failHandler = handler; return this; }
        };
        return {
            then: function (handler) { onSuccess = handler; return pending; }
        };
    };
    jquery.Deferred = function () {
        return {
            reject: function (message) { this.rejection = message; return this; },
            promise: function () { return this; }
        };
    };

    const config = {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: 'test',
        labels: { error: 'Impossible de charger les albums.' }
    };
    const window = {};
    vm.runInNewContext(source, {
        jQuery: jquery,
        window: window,
        document: {},
        WPDAlbumPickerConfig: config
    });

    window.WPDAlbumPicker.attach(rootNode, inputNode);
    assert.strictEqual(typeof click, 'function');
    click();
    assert.strictEqual(typeof pending.failHandler, 'function');

    if (businessError) {
        const rejected = onSuccess({ success: false, data: { message: businessError } });
        pending.failHandler(rejected.rejection);
    } else {
        pending.failHandler(payload, status);
    }
    assert.ok(picker.content, 'Le message doit être affiché.');
    assert.strictEqual(typeof picker.content.message, 'string', 'Le message doit passer par .text().');
    return picker.content.message;
}

const fallback = 'Impossible de charger les albums.';
const cases = [
    [{ responseJSON: { data: { message: 'Refus Piwigo' } }, status: 502 }, 'error', '', 'Refus Piwigo'],
    [{ responseJSON: { data: { message: 'Accès refusé' } }, status: 403 }, 'error', '', 'Accès refusé'],
    [{ status: 502, responseText: '<html>Erreur proxy</html>' }, 'error', '', fallback + ' (HTTP 502.)'],
    [{ status: 200 }, 'parsererror', '', fallback + ' (Réponse JSON invalide.)'],
    [{ status: 0 }, 'error', '', fallback],
    [null, null, 'Erreur métier', 'Erreur métier']
];

cases.forEach(function (test, index) {
    assert.strictEqual(
        simulateFailure(test[0], test[1], test[2]),
        test[3],
        'Erreur du sélecteur, cas ' + (index + 1)
    );
});
console.log('Sélecteur Piwigo : 6 erreurs HTTP/AJAX prises en charge.');