(function (hooks, compose, element, blockEditor, components, i18n) {
    'use strict';

    var addFilter = hooks.addFilter;
    var createHigherOrderComponent = compose.createHigherOrderComponent;
    var createElement = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var __ = i18n.__;

    var withShapeControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (props.name !== 'wp-piwigo-display/gallery') {
                return createElement(BlockEdit, props);
            }

            var attributes = props.attributes || {};
            var shape = attributes.shape || 'rectangle';

            return createElement(
                Fragment,
                null,
                createElement(BlockEdit, props),
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        {
                            title: __('Forme des images', 'wp-piwigo-display'),
                            initialOpen: false
                        },
                        createElement(SelectControl, {
                            label: __('Forme', 'wp-piwigo-display'),
                            value: shape,
                            options: [
                                { label: __('Rectangle', 'wp-piwigo-display'), value: 'rectangle' },
                                { label: __('Rectangle arrondi', 'wp-piwigo-display'), value: 'rounded' },
                                { label: __('Cercle', 'wp-piwigo-display'), value: 'circle' },
                                { label: __('Ovale', 'wp-piwigo-display'), value: 'oval' },
                                { label: __('Pilule', 'wp-piwigo-display'), value: 'pill' },
                                { label: __('Étoile', 'wp-piwigo-display'), value: 'star' },
                                { label: __('Hexagone', 'wp-piwigo-display'), value: 'hexagon' },
                                { label: __('Losange', 'wp-piwigo-display'), value: 'diamond' },
                                { label: __('Nuage', 'wp-piwigo-display'), value: 'cloud' },
                                { label: __('Cœur', 'wp-piwigo-display'), value: 'heart' },
                                { label: __('Goutte', 'wp-piwigo-display'), value: 'drop' },
                                { label: __('Triangle', 'wp-piwigo-display'), value: 'triangle' },
                                { label: __('Pentagone', 'wp-piwigo-display'), value: 'pentagon' },
                                { label: __('Octogone', 'wp-piwigo-display'), value: 'octagon' },
                                { label: __('Carte — Pique ♠', 'wp-piwigo-display'), value: 'card-spade' },
                                { label: __('Carte — Cœur ♥', 'wp-piwigo-display'), value: 'card-heart' },
                                { label: __('Carte — Carreau ♦', 'wp-piwigo-display'), value: 'card-diamond' },
                                { label: __('Carte — Trèfle ♣', 'wp-piwigo-display'), value: 'card-club' }
                            ],
                            onChange: function (value) {
                                props.setAttributes({ shape: value });
                            }
                        }),
                        shape === 'rounded' ? createElement(RangeControl, {
                            label: __('Arrondi des angles (%)', 'wp-piwigo-display'),
                            value: Number(attributes.radius || 0),
                            min: 0,
                            max: 50,
                            step: 1,
                            onChange: function (value) {
                                props.setAttributes({ radius: Number(value || 0) });
                            }
                        }) : null
                    )
                )
            );
        };
    }, 'withWpdShapeControls');

    addFilter('editor.BlockEdit', 'wp-piwigo-display/shape-controls', withShapeControls);
})(window.wp.hooks, window.wp.compose, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
