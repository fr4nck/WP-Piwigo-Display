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
    var TextareaControl = components.TextareaControl;
    var ToggleControl = components.ToggleControl;
    var __ = i18n.__;
    var userFonts = Array.isArray(window.WPDUserFonts) ? window.WPDUserFonts : [];

    function photoTextFontOptions() {
        var options = [
            {label: __('Police du thème', 'wp-piwigo-display'), value: 'inherit'},
            {label: __('Système', 'wp-piwigo-display'), value: 'system'},
            {label: __('Serif', 'wp-piwigo-display'), value: 'serif'},
            {label: __('Monospace', 'wp-piwigo-display'), value: 'mono'}
        ];

        userFonts.forEach(function (font) {
            if (font && font.value && font.name) {
                options.push({label: font.name, value: font.value});
            }
        });

        return options;
    }

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
            var photoTextSize = Math.min(300, Math.max(120, parseInt(attributes.photoTextSize || 230, 10)));
            var photoTextLetterSpacing = Math.min(80, Math.max(-20, parseInt(attributes.photoTextLetterSpacing || 0, 10)));
            var photoTextLineHeight = Math.min(160, Math.max(70, parseInt(attributes.photoTextLineHeight || 100, 10)));
            var photoTextMaxWidth = Math.min(100, Math.max(20, parseInt(attributes.photoTextMaxWidth || 100, 10)));
            var photoTextDensity = Math.min(200, Math.max(50, parseInt(attributes.photoTextDensity || 100, 10)));
            var photoTextRotation = Math.min(15, Math.max(0, parseInt(attributes.photoTextRotation || 6, 10)));
            var photoTextSpread = Math.min(50, Math.max(0, parseInt(attributes.photoTextSpread || 18, 10)));
            var photoTextOutlineWidth = Math.min(12, Math.max(0, parseInt(attributes.photoTextOutlineWidth || 3, 10)));
            var photoTextMaxImages = Math.min(40, Math.max(1, parseInt(attributes.photoTextMaxImages || 20, 10)));
            var photoTextFillMode = attributes.photoTextFillMode || 'grid';

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
                    displayType === 'photo-text' && el(TextareaControl, {label: __('Texte', 'wp-piwigo-display'), value: attributes.photoText || 'PÊLE-MÊLE', help: __('Utilisez un retour à la ligne pour composer jusqu’à quatre lignes.', 'wp-piwigo-display'), rows: 3, onChange: function (value) { setAttributes({photoText: value}); }}),
                    displayType === 'photo-text' && el(TextControl, {label: __('Graine de composition', 'wp-piwigo-display'), value: attributes.photoTextSeed || '0', help: __('Même graine + mêmes photos = même remplissage.', 'wp-piwigo-display'), onChange: function (value) { setAttributes({photoTextSeed: value}); }}),
                    displayType === 'photo-text' && el(SelectControl, {label: __('Police', 'wp-piwigo-display'), value: attributes.photoTextFont || 'inherit', options: photoTextFontOptions(), onChange: function (value) { setAttributes({photoTextFont: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Graisse', 'wp-piwigo-display'), value: photoTextWeight, min: 100, max: 900, step: 100, onChange: function (value) { setAttributes({photoTextWeight: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Taille du texte', 'wp-piwigo-display'), value: photoTextSize, min: 120, max: 300, onChange: function (value) { setAttributes({photoTextSize: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Interlettrage', 'wp-piwigo-display'), value: photoTextLetterSpacing, min: -20, max: 80, onChange: function (value) { setAttributes({photoTextLetterSpacing: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Hauteur de ligne (%)', 'wp-piwigo-display'), value: photoTextLineHeight, min: 70, max: 160, step: 5, onChange: function (value) { setAttributes({photoTextLineHeight: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Largeur maximale (%)', 'wp-piwigo-display'), value: photoTextMaxWidth, min: 20, max: 100, onChange: function (value) { setAttributes({photoTextMaxWidth: value}); }}),
                    displayType === 'photo-text' && el(SelectControl, {label: __('Alignement', 'wp-piwigo-display'), value: attributes.photoTextAlign || 'center', options: [
                        {label: __('Gauche', 'wp-piwigo-display'), value: 'left'},
                        {label: __('Centre', 'wp-piwigo-display'), value: 'center'},
                        {label: __('Droite', 'wp-piwigo-display'), value: 'right'}
                    ], onChange: function (value) { setAttributes({photoTextAlign: value}); }}),
                    displayType === 'photo-text' && el(SelectControl, {label: __('Remplissage des lettres', 'wp-piwigo-display'), value: photoTextFillMode, options: [
                        {label: __('Grille', 'wp-piwigo-display'), value: 'grid'},
                        {label: __('Masonry', 'wp-piwigo-display'), value: 'masonry'},
                        {label: __('Pêle-mêle', 'wp-piwigo-display'), value: 'collage'}
                    ], onChange: function (value) { setAttributes({photoTextFillMode: value}); }}),
                    displayType === 'photo-text' && el(RangeControl, {label: __('Densité du remplissage (%)', 'wp-piwigo-display'), value: photoTextDensity, min: 50, max: 200, step: 10, onChange: function (value) { setAttributes({photoTextDensity: value}); }}),
                    displayType === 'photo-text' && photoTextFillMode === 'collage' && el(RangeControl, {label: __('Rotation du pêle-mêle', 'wp-piwigo-display'), value: photoTextRotation, min: 0, max: 15, onChange: function (value) { setAttributes({photoTextRotation: value}); }}),
                    displayType === 'photo-text' && photoTextFillMode === 'collage' && el(RangeControl, {label: __('Dispersion du pêle-mêle', 'wp-piwigo-display'), value: photoTextSpread, min: 0, max: 50, onChange: function (value) { setAttributes({photoTextSpread: value}); }}),
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
