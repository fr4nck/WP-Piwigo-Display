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

    var withMasonryControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (props.name !== 'wp-piwigo-display/gallery') {
                return el(BlockEdit, props);
            }

            var attributes = props.attributes || {};
            var setAttributes = props.setAttributes;
            var displayType = attributes.displayType || 'gallery';
            var columns = Math.min(6, Math.max(2, parseInt(attributes.masonryColumns || 4, 10)));
            var gap = Math.min(64, Math.max(0, parseInt(attributes.masonryGap || 16, 10)));
            var rowHeight = Math.min(600, Math.max(100, parseInt(attributes.justifiedRowHeight || 220, 10)));
            var justifiedGap = Math.min(64, Math.max(0, parseInt(attributes.justifiedGap || 8, 10)));
            var collageRotation = Math.min(15, Math.max(0, parseInt(attributes.collageRotation || 6, 10)));
            var collageSpread = Math.min(50, Math.max(0, parseInt(attributes.collageSpread || 18, 10)));
            var collageOverlap = Math.min(40, Math.max(0, parseInt(attributes.collageOverlap || 12, 10)));
            var collageSize = Math.min(420, Math.max(120, parseInt(attributes.collageSize || 220, 10)));
            var collageVariation = Math.min(50, Math.max(0, parseInt(attributes.collageVariation || 20, 10)));
            var bounded = function (name, min, max, fallback) {
                var value = parseInt(attributes[name], 10);
                return Number.isFinite(value) ? Math.min(max, Math.max(min, value)) : fallback;
            };
            var photoFill = attributes.photoTextFillMode || 'grid';
            var fontChoices = [
                { label: __('Police du thème', 'wp-piwigo-display'), value: 'inherit' },
                { label: __('Système', 'wp-piwigo-display'), value: 'system' },
                { label: __('Serif', 'wp-piwigo-display'), value: 'serif' },
                { label: __('Monospace', 'wp-piwigo-display'), value: 'mono' }
            ];
            var packagedFonts = Array.isArray(window.WPDUserFonts) ? window.WPDUserFonts : [];
            packagedFonts.forEach(function (font) {
                if (font && font.source === 'bundled'
                    && ['bundled-bebas-neue', 'bundled-bungee'].indexOf(font.value) !== -1
                    && typeof font.name === 'string') {
                    fontChoices.push({ label: font.name, value: font.value });
                }
            });

            return el(
                Fragment,
                null,
                el(BlockEdit, props),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Disposition de la galerie', 'wp-piwigo-display'), initialOpen: displayType === 'masonry' || displayType === 'justified' || displayType === 'collage' || displayType === 'photo-text' },
                        el(SelectControl, {
                            label: __('Mode d’affichage', 'wp-piwigo-display'),
                            value: displayType,
                            options: [
                                { label: __('Galerie', 'wp-piwigo-display'), value: 'gallery' },
                                { label: __('Diaporama', 'wp-piwigo-display'), value: 'slider' },
                                { label: __('Masonry', 'wp-piwigo-display'), value: 'masonry' },
                                { label: __('Galerie justifiée', 'wp-piwigo-display'), value: 'justified' },
                                { label: __('Collage / Pêle-mêle', 'wp-piwigo-display'), value: 'collage' },
                                { label: __('Texte rempli de photos', 'wp-piwigo-display'), value: 'photo-text' }
                            ],
                            onChange: function (value) { setAttributes({ displayType: value }); }
                        }),
                        displayType === 'masonry' && el(RangeControl, {
                            label: __('Nombre de colonnes', 'wp-piwigo-display'),
                            value: columns,
                            min: 2,
                            max: 6,
                            onChange: function (value) { setAttributes({ masonryColumns: value }); }
                        }),
                        displayType === 'masonry' && el(RangeControl, {
                            label: __('Espacement entre les images', 'wp-piwigo-display'),
                            value: gap,
                            min: 0,
                            max: 64,
                            help: __('Valeur en pixels.', 'wp-piwigo-display'),
                            onChange: function (value) { setAttributes({ masonryGap: value }); }
                        }),
                        displayType === 'justified' && el(RangeControl, {
                            label: __('Hauteur cible des lignes', 'wp-piwigo-display'),
                            value: rowHeight,
                            min: 100,
                            max: 600,
                            onChange: function (value) { setAttributes({ justifiedRowHeight: value }); }
                        }),
                        displayType === 'justified' && el(RangeControl, {
                            label: __('Espacement entre les images', 'wp-piwigo-display'),
                            value: justifiedGap,
                            min: 0,
                            max: 64,
                            onChange: function (value) { setAttributes({ justifiedGap: value }); }
                        }),
                        displayType === 'collage' && el(TextControl, {
                            label: __('Graine de composition', 'wp-piwigo-display'),
                            value: attributes.collageSeed || '0',
                            help: __('Même graine + mêmes photos = même composition.', 'wp-piwigo-display'),
                            onChange: function (value) { setAttributes({ collageSeed: value }); }
                        }),
                        displayType === 'collage' && el(RangeControl, {
                            label: __('Rotation maximale', 'wp-piwigo-display'),
                            value: collageRotation, min: 0, max: 15,
                            onChange: function (value) { setAttributes({ collageRotation: value }); }
                        }),
                        displayType === 'collage' && el(RangeControl, {
                            label: __('Dispersion', 'wp-piwigo-display'),
                            value: collageSpread, min: 0, max: 50,
                            onChange: function (value) { setAttributes({ collageSpread: value }); }
                        }),
                        displayType === 'collage' && el(RangeControl, {
                            label: __('Chevauchement', 'wp-piwigo-display'),
                            value: collageOverlap, min: 0, max: 40,
                            onChange: function (value) { setAttributes({ collageOverlap: value }); }
                        }),
                        displayType === 'collage' && el(RangeControl, {
                            label: __('Taille moyenne', 'wp-piwigo-display'),
                            value: collageSize, min: 120, max: 420,
                            onChange: function (value) { setAttributes({ collageSize: value }); }
                        }),
                        displayType === 'collage' && el(RangeControl, {
                            label: __('Variation de taille (%)', 'wp-piwigo-display'),
                            value: collageVariation, min: 0, max: 50,
                            onChange: function (value) { setAttributes({ collageVariation: value }); }
                        }),
                        displayType === 'photo-text' && el(TextareaControl, {
                            label: __('Texte', 'wp-piwigo-display'), value: attributes.photoText || 'PÊLE-MÊLE',
                            help: __('Utilisez un retour à la ligne pour composer jusqu’à quatre lignes.', 'wp-piwigo-display'),
                            rows: 3, onChange: function (value) { setAttributes({ photoText: value }); }
                        }),
                        displayType === 'photo-text' && el(TextControl, {
                            label: __('Graine de composition', 'wp-piwigo-display'), value: attributes.photoTextSeed || '0',
                            help: __('Même graine + mêmes photos = même remplissage.', 'wp-piwigo-display'),
                            onChange: function (value) { setAttributes({ photoTextSeed: value }); }
                        }),
                        displayType === 'photo-text' && el(SelectControl, {
                            label: __('Police locale', 'wp-piwigo-display'), value: attributes.photoTextFont || 'inherit',
                            options: fontChoices,
                            onChange: function (value) { setAttributes({ photoTextFont: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Graisse', 'wp-piwigo-display'), value: bounded('photoTextWeight', 100, 900, 800),
                            min: 100, max: 900, onChange: function (value) { setAttributes({ photoTextWeight: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Taille du texte', 'wp-piwigo-display'), value: bounded('photoTextSize', 120, 300, 230),
                            min: 120, max: 300, onChange: function (value) { setAttributes({ photoTextSize: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Interlettrage', 'wp-piwigo-display'), value: bounded('photoTextLetterSpacing', -20, 80, 0),
                            min: -20, max: 80, onChange: function (value) { setAttributes({ photoTextLetterSpacing: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Hauteur de ligne (%)', 'wp-piwigo-display'), value: bounded('photoTextLineHeight', 70, 160, 100),
                            min: 70, max: 160, onChange: function (value) { setAttributes({ photoTextLineHeight: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Largeur maximale (%)', 'wp-piwigo-display'), value: bounded('photoTextMaxWidth', 20, 100, 100),
                            min: 20, max: 100, onChange: function (value) { setAttributes({ photoTextMaxWidth: value }); }
                        }),
                        displayType === 'photo-text' && el(SelectControl, {
                            label: __('Alignement', 'wp-piwigo-display'), value: attributes.photoTextAlign || 'center',
                            options: [
                                { label: __('Gauche', 'wp-piwigo-display'), value: 'left' },
                                { label: __('Centre', 'wp-piwigo-display'), value: 'center' },
                                { label: __('Droite', 'wp-piwigo-display'), value: 'right' }
                            ], onChange: function (value) { setAttributes({ photoTextAlign: value }); }
                        }),
                        displayType === 'photo-text' && el(SelectControl, {
                            label: __('Remplissage des lettres', 'wp-piwigo-display'), value: photoFill,
                            options: [
                                { label: __('Grille', 'wp-piwigo-display'), value: 'grid' },
                                { label: __('Masonry', 'wp-piwigo-display'), value: 'masonry' },
                                { label: __('Pêle-mêle', 'wp-piwigo-display'), value: 'collage' }
                            ], onChange: function (value) { setAttributes({ photoTextFillMode: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Densité du remplissage (%)', 'wp-piwigo-display'), value: bounded('photoTextDensity', 50, 200, 100),
                            min: 50, max: 200, onChange: function (value) { setAttributes({ photoTextDensity: value }); }
                        }),
                        displayType === 'photo-text' && photoFill === 'collage' && el(RangeControl, {
                            label: __('Rotation du pêle-mêle', 'wp-piwigo-display'), value: bounded('photoTextRotation', 0, 15, 6),
                            min: 0, max: 15, onChange: function (value) { setAttributes({ photoTextRotation: value }); }
                        }),
                        displayType === 'photo-text' && photoFill === 'collage' && el(RangeControl, {
                            label: __('Dispersion du pêle-mêle', 'wp-piwigo-display'), value: bounded('photoTextSpread', 0, 50, 18),
                            min: 0, max: 50, onChange: function (value) { setAttributes({ photoTextSpread: value }); }
                        }),
                        displayType === 'photo-text' && el(RangeControl, {
                            label: __('Nombre maximal de photos', 'wp-piwigo-display'), value: bounded('photoTextMaxImages', 1, 40, 20),
                            min: 1, max: 40, onChange: function (value) { setAttributes({ photoTextMaxImages: value }); }
                        }),
                        displayType === 'photo-text' && el(ToggleControl, {
                            label: __('Afficher un contour', 'wp-piwigo-display'), checked: attributes.photoTextOutline !== false,
                            onChange: function (value) { setAttributes({ photoTextOutline: value }); }
                        }),
                        displayType === 'photo-text' && attributes.photoTextOutline !== false && el(RangeControl, {
                            label: __('Épaisseur du contour', 'wp-piwigo-display'), value: bounded('photoTextOutlineWidth', 0, 12, 3),
                            min: 0, max: 12, onChange: function (value) { setAttributes({ photoTextOutlineWidth: value }); }
                        }),
                        displayType === 'photo-text' && el(TextControl, {
                            label: __('Couleur du contour', 'wp-piwigo-display'),
                            value: attributes.photoTextOutlineColor || '#ffffff',
                            onChange: function (value) { setAttributes({ photoTextOutlineColor: value }); }
                        }),
                        displayType === 'photo-text' && el(TextControl, {
                            label: __('Fond autour du texte', 'wp-piwigo-display'),
                            value: attributes.photoTextBackground || 'transparent',
                            onChange: function (value) { setAttributes({ photoTextBackground: value }); }
                        })
                    )
                )
            );
        };
    }, 'withMasonryControls');

    addFilter('editor.BlockEdit', 'wp-piwigo-display/masonry-controls', withMasonryControls);
})(window.wp.hooks, window.wp.compose, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
