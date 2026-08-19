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
    var __ = i18n.__;

    var withLayoutControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (props.name !== 'wp-piwigo-display/gallery') {
                return el(BlockEdit, props);
            }

            var attributes = props.attributes || {};
            var setAttributes = props.setAttributes;
            var displayType = attributes.displayType || 'gallery';
            var columns = Math.min(6, Math.max(2, parseInt(attributes.masonryColumns || 4, 10)));
            var masonryGap = Math.min(64, Math.max(0, parseInt(attributes.masonryGap || 16, 10)));
            var justifiedRowHeight = Math.min(600, Math.max(100, parseInt(attributes.justifiedRowHeight || 220, 10)));
            var justifiedGap = Math.min(64, Math.max(0, parseInt(attributes.justifiedGap || 8, 10)));

            return el(
                Fragment,
                null,
                el(BlockEdit, props),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Disposition de la galerie', 'wp-piwigo-display'), initialOpen: displayType === 'masonry' || displayType === 'justified' },
                        el(SelectControl, {
                            label: __('Mode d’affichage', 'wp-piwigo-display'),
                            value: displayType,
                            options: [
                                { label: __('Galerie', 'wp-piwigo-display'), value: 'gallery' },
                                { label: __('Diaporama', 'wp-piwigo-display'), value: 'slider' },
                                { label: __('Masonry', 'wp-piwigo-display'), value: 'masonry' },
                                { label: __('Galerie justifiée', 'wp-piwigo-display'), value: 'justified' }
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
                            value: masonryGap,
                            min: 0,
                            max: 64,
                            help: __('Valeur en pixels.', 'wp-piwigo-display'),
                            onChange: function (value) { setAttributes({ masonryGap: value }); }
                        }),
                        displayType === 'justified' && el(RangeControl, {
                            label: __('Hauteur cible des lignes', 'wp-piwigo-display'),
                            value: justifiedRowHeight,
                            min: 100,
                            max: 600,
                            help: __('Valeur en pixels. Les lignes s’adaptent pour remplir la largeur.', 'wp-piwigo-display'),
                            onChange: function (value) { setAttributes({ justifiedRowHeight: value }); }
                        }),
                        displayType === 'justified' && el(RangeControl, {
                            label: __('Espacement entre les images', 'wp-piwigo-display'),
                            value: justifiedGap,
                            min: 0,
                            max: 64,
                            help: __('Valeur en pixels.', 'wp-piwigo-display'),
                            onChange: function (value) { setAttributes({ justifiedGap: value }); }
                        })
                    )
                )
            );
        };
    }, 'withLayoutControls');

    addFilter('editor.BlockEdit', 'wp-piwigo-display/layout-controls', withLayoutControls);
})(window.wp.hooks, window.wp.compose, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
