(function (hooks, compose, element, blockEditor, components, i18n) {
    'use strict';

    var addFilter = hooks.addFilter;
    var createHigherOrderComponent = compose.createHigherOrderComponent;
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var __ = i18n.__;

    var withLayoutControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (props.name !== 'wp-piwigo-display/gallery') return el(BlockEdit, props);

            var attributes = props.attributes || {};
            var setAttributes = props.setAttributes;
            var displayType = attributes.displayType || 'gallery';
            var columns = Math.min(6, Math.max(2, parseInt(attributes.masonryColumns || 4, 10)));
            var masonryGap = Math.min(64, Math.max(0, parseInt(attributes.masonryGap || 16, 10)));
            var justifiedRowHeight = Math.min(600, Math.max(100, parseInt(attributes.justifiedRowHeight || 220, 10)));
            var justifiedGap = Math.min(64, Math.max(0, parseInt(attributes.justifiedGap || 8, 10)));
            var collageRotation = Math.min(15, Math.max(0, parseInt(attributes.collageRotation || 6, 10)));
            var collageSpread = Math.min(50, Math.max(0, parseInt(attributes.collageSpread || 18, 10)));
            var collageOverlap = Math.min(40, Math.max(0, parseInt(attributes.collageOverlap || 12, 10)));
            var collageSize = Math.min(420, Math.max(120, parseInt(attributes.collageSize || 220, 10)));
            var collageVariation = Math.min(50, Math.max(0, parseInt(attributes.collageVariation || 20, 10)));
            var photoTextWeight = Math.min(900, Math.max(100, parseInt(attributes.photoTextWeight || 800, 10)));
            var photoTextOutlineWidth = Math.min(12, Math.max(0, parseInt(attributes.photoTextOutlineWidth || 3, 10)));
            var photoTextMaxImages = Math.min(40, Math.max(1, parseInt(attributes.photoTextMaxImages || 20, 10)));

            return el(Fragment, null, el(BlockEdit, props), el(InspectorControls, null,
                el(PanelBody, {title: __('Disposition de la galerie', 'wp-piwigo-display'), initialOpen: ['masonry','justified','collage','photo-text'].indexOf(displayType) !== -1},
                    el(SelectControl, {label: __('Mode d’affichage', 'wp-piwigo-display'), value: displayType, options: [
                        {label: __('Galerie', 'wp-piwigo-display'), value: 'gallery'},
                        {label: __('Diaporama', 'wp-piwigo-display'), value: 'slider'},
                        {label: __('Masonry', 'wp-piwigo-display'), value: 'masonry'},
                        {label: __('Galerie justifiée', 'wp-piwigo-display'), value: 'justified'},
                        {label: __('Collage / Pêle-mêle', 'wp-piwigo-display'), value: 'collage'},
                        {label: __('Texte rempli de photos', 'wp-piwigo-display'), value: 'photo-text'}
                    ], onChange: function (value) { setAttributes({displayType: value}); }}),
                    displayType === 'masonry' && el(RangeControl, {label: __('Nombre de colonnes', 'wp-piwigo-display'), value: columns, min: 2, max: 6, onChange: function (value) { setAttributes({masonryColumns: value}); }}),
                    displayType === 'masonry' && el(RangeControl, {label: __('Espacement entre les images', 'wp-piwigo-display'), value: masonryGap, min: 0, max: 64, onChange: function (value) { setAttributes({masonryGap: value}); }}),
                    displayType === 'justified' && el(RangeControl, {label: __('Hauteur cible des lignes', 'wp-piwigo-display'), value: justifiedRowHeight, min: 100, max: 600, onChange: function (value) { setAttributes({justifiedRowHeight: value}); }}),
                    displayType === 'justified' && el(RangeControl, {label: __('Espacement entre les images', 'wp-piwigo-display'), value: justifiedGap, min: 0, max: 64, onChange: function (value) { setAttributes({justifiedGap: value}); }}),
                    displayType === 'collage' && el(TextControl, {label: __('Graine de composition', 'wp-piwigo-display'), value: attributes.collageSeed || '0', help: __('Même graine + mêmes photos = même composition.', 'wp-piwigo-display'), onChange: function (value) { setAttributes({collageSeed: value}); }}),
                    displayType === 'collage' && el(RangeControl, {label: __('Rotation maximale', 'wp-piwigo-display'), value: collageRotation, min: 0, max: 15, onChange: function (value) { setAttributes({collageRotation: value}); }}),
                    displayType === 'collage' && el(RangeControl, {label: __('Dispersion', 'wp-piwigo-display'), value: collageSpread, min: 0, max: 50, onChange: function (value) { setAttributes({collageSpread: value}); }}),
                    displayType === 'collage' && el(RangeControl, {label: __('Chevauchement', 'wp-piwigo-display'), value: collageOverlap, min: 0, max: 40, onChange: function (value) { setAttributes({collageOverlap: value}); }}),
                    displayType === 'collage' && el(RangeControl, {label: __('Taille moyenne des photos', 'wp-piwigo-display'), value: collageSize, min: 120, max: 420, onChange: function (value) { setAttributes({collageSize: value}); }}),
                    displayType === 'collage' && el(RangeControl, {label: __('Variation de taille (%)', 'wp-piwigo-display'), value: collageVariation, min: 0, max: 50, onChange: function (value) { setAttributes({collageVariation: value}); }}),
                    displayType === 'photo-text' && el(TextControl, {label: __('Texte', 'wp-piwigo-display'), value: attributes.photoText || 'PÊLE-MÊLE', onChange: function (value) { setAttributes({photoText: value}); }}),
                    displayType === 'photo-text' && el(TextControl, {label: __('Graine de composition', 'wp-piwigo-display'), value: attributes.photoTextSeed || '0', help: __('Même graine + mêmes photos = même remplissage.', 'wp-piwigo-display'), onChange: function (value) { setAttributes({photoTextSeed: value}); }}),
                    displayType === 'photo-text' && el(SelectControl, {label: __('Police', 'wp-piwigo-display'), value: attributes.photoTextFont || 'inherit', options: [
                        {label: __('Police du thème', 'wp-piwigo-display'), value: 'inherit'},
                        {label: __('Système', 'wp-piwigo-display'), value: 'system'},
                        {label: __('Serif', 'wp-piwigo-display'), value: 'serif'},
                        {label: __('Monospace', 'wp-piwigo-display'), value: 'mono'}
                    ], onChange: function (value) { setAttributes({photoTextFont: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Graisse', 'wp-piwigo-display'), value: photoTextWeight, min: 100, max: 900, step: 100, onChange: function (value) { setAttributes({photoTextWeight: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Nombre maximal de photos', 'wp-piwigo-display'), value: photoTextMaxImages, min: 1, max: 40, onChange: function (value) { setAttributes({photoTextMaxImages: value}); }}),
                    displayType === 'photo-text' && el(ToggleControl, {label: __('Afficher un contour', 'wp-piwigo-display'), checked: attributes.photoTextOutline !== false, onChange: function (value) { setAttributes({photoTextOutline: value}); }}),
                    displayType === 'photo-text' && attributes.photoTextOutline !== false && el(RangeControl, {label: __('Épaisseur du contour', 'wp-piwigo-display'), value: photoTextOutlineWidth, min: 0, max: 12, onChange: function (value) { setAttributes({photoTextOutlineWidth: value}); }}),
                    displayType === 'photo-text' && attributes.photoTextOutline !== false && el(TextControl, {label: __('Couleur du contour', 'wp-piwigo-display'), value: attributes.photoTextOutlineColor || '#ffffff', help: __('Couleur hexadécimale locale, par exemple #ffffff.', 'wp-piwigo-display'), onChange: function (value) { setAttributes({photoTextOutlineColor: value}); }}),
                    displayType === 'photo-text' && el(TextControl, {label: __('Fond autour du texte', 'wp-piwigo-display'), value: attributes.photoTextBackground || 'transparent', help: __('Utilisez transparent ou une couleur hexadécimale.', 'wp-piwigo-display'), onChange: function (value) { setAttributes({photoTextBackground: value}); }})
                )
            ));
        };
    }, 'withLayoutControls');

    addFilter('editor.BlockEdit', 'wp-piwigo-display/layout-controls', withLayoutControls);
})(window.wp.hooks, window.wp.compose, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
