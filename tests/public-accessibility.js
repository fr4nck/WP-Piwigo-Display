'use strict';

const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

function testLightbox() {
    const handlers = {};
    const links = [];
    const document = {
        activeElement: null,
        body: null,
        addEventListener: function (name, fn) { handlers[name] = fn; },
        querySelectorAll: function () { return links; },
        createElement: function (tag) {
            const attrs = {};
            const classes = new Set();
            const node = {
                tagName: tag,
                attrs: attrs,
                children: [],
                events: {},
                classList: {
                    add: function (name) { classes.add(name); },
                    remove: function (name) { classes.delete(name); },
                    contains: function (name) { return classes.has(name); }
                },
                appendChild: function (child) { this.children.push(child); },
                setAttribute: function (name, value) { attrs[name] = String(value); },
                addEventListener: function (name, fn) { this.events[name] = fn; },
                focus: function () { document.activeElement = this; }
            };
            return node;
        }
    };
    document.body = document.createElement('body');
    const link = document.createElement('a');
    link.dataset = { wpdTitle: 'Photo de test' };
    link.href = 'https://example.test/image.jpg';
    links.push(link);
    document.activeElement = link;

    vm.runInNewContext(fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display.js', 'utf8'), { document: document });
    handlers.DOMContentLoaded();
    const overlay = document.body.children[0];
    const close = overlay.children[0];
    const next = overlay.children[3];

    assert.strictEqual(overlay.attrs.role, 'dialog');
    assert.strictEqual(overlay.attrs['aria-modal'], 'true');
    link.events.click({ preventDefault: function () {} });
    assert.strictEqual(document.activeElement, close, 'Le focus doit entrer dans la lightbox.');

    let prevented = false;
    handlers.keydown({ key: 'Tab', shiftKey: true, preventDefault: function () { prevented = true; } });
    assert.ok(prevented, 'Maj-Tab doit être intercepté au début.');
    assert.strictEqual(document.activeElement, next);

    prevented = false;
    handlers.keydown({ key: 'Tab', shiftKey: false, preventDefault: function () { prevented = true; } });
    assert.ok(prevented, 'Tab doit être intercepté à la fin.');
    assert.strictEqual(document.activeElement, close);

    handlers.keydown({ key: 'Escape', preventDefault: function () {} });
    assert.strictEqual(document.activeElement, link, 'Le focus doit revenir au lien initial.');
    assert.ok(!overlay.classList.contains('is-open'));
}

function testLightboxIgnoresSliderClones() {
    const handlers = {};
    const document = {
        activeElement: null,
        addEventListener: function (name, fn) { handlers[name] = fn; },
        createElement: function () {
            return {
                children: [],
                classList: { add: function () {}, remove: function () {}, contains: function () { return true; } },
                appendChild: function (child) { this.children.push(child); },
                setAttribute: function () {},
                addEventListener: function () {},
                focus: function () {}
            };
        }
    };
    document.body = document.createElement('body');
    function link(name, clone) {
        return {
            href: 'https://example.test/' + name + '.jpg',
            dataset: { wpdTitle: name },
            events: {},
            addEventListener: function (type, fn) { this.events[type] = fn; },
            closest: function () { return clone ? {} : null; }
        };
    }
    // Ordre du DOM après montage d'un diaporama Splide en boucle de trois images.
    const links = [link('3', true), link('1'), link('2'), link('3'), link('1', true)];
    document.querySelectorAll = function () { return links; };

    vm.runInNewContext(fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display.js', 'utf8'), { document: document });
    handlers.DOMContentLoaded();
    const image = document.body.children[0].children[2];
    const seen = [];

    links[1].events.click({ preventDefault: function () {} });
    for (let i = 0; i < 5; i++) {
        seen.push(image.alt);
        handlers.keydown({ key: 'ArrowRight' });
    }
    assert.deepStrictEqual(seen, ['1', '2', '3', '1', '2'], 'La lightbox ne doit pas parcourir les clones Splide.');

    links[0].events.click({ preventDefault: function () {} });
    assert.strictEqual(image.alt, '3', 'Un clone doit ouvrir l’image d’origine.');
}

function testAutoplay(autoplayEnabled, reducedMotion) {
    const created = [];
    const slider = {
        dataset: { autoplay: autoplayEnabled ? 'true' : 'false', navigation: 'thumbnails', interval: '5000', speed: '500', direction: 'ltr', transition: 'slide' },
        children: [],
        attrs: {},
        appendChild: function (node) { this.children.push(node); },
        setAttribute: function (key, val) { this.attrs[key] = val; },
        querySelectorAll: function (query) {
            if (query === '.splide__slide') return this.slides;
            if (query === '.wp-piwigo-display-slider-thumbnail') return [];
            if (query === '.wpd-native-slider-controls, .wpd-native-slider-pagination') return [];
            return [];
        }
    };
    const document = {
        readyState: 'complete',
        querySelectorAll: function (selector) {
            assert.strictEqual(selector, '.wp-piwigo-display-slider.splide');
            return [slider];
        },
        createElement: function (name) {
            const node = {
                name: name,
                attrs: {},
                events: {},
                children: [],
                setAttribute: function (key, value) { this.attrs[key] = String(value); },
                addEventListener: function (key, fn) { this.events[key] = fn; },
                appendChild: function (child) { this.children.push(child); }
            };
            created.push(node);
            return node;
        }
    };
    slider.slides = [0,1].map(function () {
        return {
            hidden: false,
            attrs: {},
            setAttribute: function (key, value) { this.attrs[key] = value; },
            removeAttribute: function (key) { delete this.attrs[key]; }
        };
    });
    let pause = 0;
    let play = 0;
    function Splide(_slider, options) {
        this.index = 0;
        this.options = options;
        this.Components = {
            Autoplay: {
                pause: function () { pause++; },
                play: function () { play++; }
            }
        };
        this.on = function () {};
        this.mount = function () { return this; };
        this.go = function () {};
    }
    const window = {
        matchMedia: function () { return { matches: reducedMotion }; },
        WPDSliderI18n: {
            pauseAutoplay: 'Suspendre',
            resumeAutoplay: 'Reprendre'
        }
    };
    vm.runInNewContext(
        fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-slider.js', 'utf8'),
        { document: document, window: window, Splide: Splide }
    );
    const controls = slider.children.filter(function (x) { return x.className === 'wpd-slider-autoplay-toggle'; });
    if (!autoplayEnabled || reducedMotion) {
        assert.strictEqual(controls.length, 0, 'Aucune commande si autoplay est désactivé.');
        return;
    }
    assert.strictEqual(controls.length, 1, 'Un bouton pause doit exister.');
    const toggle = controls[0];
    assert.strictEqual(toggle.attrs['aria-label'], 'Suspendre');
    toggle.events.click();
    assert.strictEqual(pause, 1, 'Pause doit arrêter Splide.');
    assert.strictEqual(toggle.attrs['aria-pressed'], 'true');
    toggle.events.click();
    assert.strictEqual(play, 1, 'Reprise doit relancer Splide.');
    assert.strictEqual(toggle.attrs['aria-pressed'], 'false');
}

testLightbox();
testLightboxIgnoresSliderClones();
testAutoplay(true, false);
testAutoplay(false, false);
testAutoplay(true, true);
console.log('Accessibilité publique : lightbox, clones de diaporama et pause/reprise : PASS.');
