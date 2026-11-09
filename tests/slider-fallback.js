'use strict';
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

const css = fs.readFileSync(__dirname + '/../assets/css/wp-piwigo-display.css', 'utf8');
assert.match(css, /\.wp-piwigo-display-slider\.splide:not\(\.is-initialized\)[\s\S]*?visibility:\s*visible/);
assert.match(css, /\.splide__slide\[aria-hidden="false"\]:not\(\[hidden\]\)/);
// Splide mesure les diapositives avant d'ajouter is-initialized : la mise en page
// de secours ne doit plus s'appliquer pendant le montage.
const fallbackRules = (css.replace(/\/\*[\s\S]*?\*\//g, '').match(/[^{}]*:not\(\.is-initialized\)[^{}]*\{/g) || [])
    .filter(rule => /splide__(list|slide)/.test(rule));
assert.ok(fallbackRules.length >= 3);
fallbackRules.forEach(rule => {
    rule.split(',').forEach(selector => {
        if (/:not\(\.is-initialized\)/.test(selector)) {
            assert.match(selector, /:not\(\.is-initialized\):not\(\.wpd-splide-mounting\)/, 'Règle de secours active pendant le montage : ' + selector.trim());
        }
    });
});
assert.match(css, /:not\(\.wpd-splide-mounting\) \.splide__slide\s*\{[^}]*height:\s*100%/, 'La photo de secours doit tenir dans la piste.');

function slider() {
    const s = {
        dataset: { navigation: 'thumbnails', autoplay: 'false' },
        children: [], classes: new Set(),
        classList: {
            add(name) { s.classes.add(name); },
            remove(...names) { names.forEach(name => s.classes.delete(name)); },
            contains(name) { return s.classes.has(name); }
        },
        querySelectorAll(selector) {
            if (selector === '.splide__slide') return this.slides;
            if (selector === '.wp-piwigo-display-slider-thumbnail') return [];
            if (selector === '.wpd-native-slider-controls, .wpd-native-slider-pagination') return this.children.filter(x => /wpd-native-slider/.test(x.className));
            return [];
        },
        appendChild(node) { node.parent = this; this.children.push(node); }
    };
    s.slides = Array.from({ length: 3 }, () => ({
        hidden: false, attrs: {},
        setAttribute(key, value) { this.attrs[key] = value; },
        removeAttribute(key) { delete this.attrs[key]; }
    }));
    return s;
}

function run(s, Splide) {
    const clock = { callback: null };
    const win = {
        WPDSliderI18n: {}, matchMedia() { return { matches: false }; },
        setInterval(fn) { clock.callback = fn; return 1; }, clearInterval() {}
    };
    const doc = {
        readyState: 'complete', querySelectorAll() { return [s]; },
        createElement() {
            return {
                children: [], events: {}, attrs: {},
                classList: { toggle() {} },
                setAttribute(k, v) { this.attrs[k] = v; },
                addEventListener(k, fn) { this.events[k] = fn; },
                appendChild(child) { this.children.push(child); },
                remove() { this.parent.children = this.parent.children.filter(n => n !== this); }
            };
        }
    };
    const context = { window: win, document: doc };
    if (Splide) context.Splide = Splide;
    vm.runInNewContext(fs.readFileSync(__dirname + '/../assets/js/wp-piwigo-display-slider.js', 'utf8'), context);
    return { s, clock, context };
}

const absent = run(slider());
const native = absent.s.children.find(x => x.className === 'wpd-native-slider-controls');
assert.ok(native);
assert.strictEqual(absent.s.slides[0].hidden, false);
assert.strictEqual(absent.s.slides[1].hidden, true);
native.children[1].events.click();
assert.strictEqual(absent.s.slides[1].hidden, false);

const late = run(slider());
let mountingDuringMount = null;
late.context.Splide = function () {
    this.on = function () {};
    this.mount = function () { mountingDuringMount = late.s.classList.contains('wpd-splide-mounting'); return this; };
    this.go = function () {};
};
late.clock.callback();
assert.strictEqual(late.s.dataset.wpdSliderInitialized, 'true');
assert.ok(!late.s.children.some(x => x.className === 'wpd-native-slider-controls'));
assert.strictEqual(mountingDuringMount, true, 'Le CSS de secours doit être neutralisé pendant le montage.');
assert.ok(!late.s.classList.contains('wpd-splide-mounting'));

let goCalls = 0;
const brokenSlider = slider();
const thumbnail = {
    dataset: { slideIndex: '1' }, listeners: [], classList: { toggle() {} }, setAttribute() {},
    addEventListener(name, fn) { this.listeners.push(fn); }
};
brokenSlider.querySelectorAll = (function (original) {
    return function (selector) {
        return selector === '.wp-piwigo-display-slider-thumbnail' ? [thumbnail] : original.call(this, selector);
    };
}(brokenSlider.querySelectorAll));
const broken = run(brokenSlider, function () {
    this.on = function () {};
    this.mount = function () { throw Error('montage impossible'); };
    this.go = function () { goCalls += 1; throw TypeError('Splide non monté'); };
});
assert.notStrictEqual(broken.s.dataset.wpdSliderInitialized, 'true');
assert.strictEqual(broken.s.slides[0].hidden, false);
assert.ok(broken.s.children.some(x => x.className === 'wpd-native-slider-controls'));
assert.ok(!broken.s.classList.contains('wpd-splide-mounting'));
thumbnail.listeners.forEach(fn => fn({ preventDefault() {} }));
assert.strictEqual(goCalls, 0, 'Une vignette ne doit pas piloter un Splide non monté.');
assert.strictEqual(broken.s.slides[1].hidden, false, 'La vignette doit piloter le secours natif.');
console.log('Diaporama : fallback sans Splide, chargement tardif et montage défaillant PASS.');
