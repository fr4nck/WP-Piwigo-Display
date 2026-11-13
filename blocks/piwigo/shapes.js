(function (hooks, compose, element, blockEditor, components, i18n) {
    'use strict';

    var addFilter = hooks.addFilter;
    var createHigherOrderComponent = compose.createHigherOrderComponent;
    var createElement = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var Button = components.Button;
    var RangeControl = components.RangeControl;
    var __ = i18n.__;

    var shapes = [
        ['rectangle', __('Rectangle', 'wp-piwigo-display')],
        ['rounded', __('Rectangle arrondi', 'wp-piwigo-display')],
        ['circle', __('Cercle', 'wp-piwigo-display')],
        ['oval', __('Ovale', 'wp-piwigo-display')],
        ['pill', __('Pilule', 'wp-piwigo-display')],
        ['star', __('Étoile', 'wp-piwigo-display')],
        ['hexagon', __('Hexagone', 'wp-piwigo-display')],
        ['diamond', __('Losange', 'wp-piwigo-display')],
        ['cloud', __('Nuage', 'wp-piwigo-display')],
        ['heart', __('Cœur', 'wp-piwigo-display')],
        ['drop', __('Goutte', 'wp-piwigo-display')],
        ['triangle', __('Triangle', 'wp-piwigo-display')],
        ['pentagon', __('Pentagone', 'wp-piwigo-display')],
        ['octagon', __('Octogone', 'wp-piwigo-display')],
        ['card-spade', __('Pique', 'wp-piwigo-display')],
        ['card-heart', __('Cœur carte', 'wp-piwigo-display')],
        ['card-diamond', __('Carreau', 'wp-piwigo-display')],
        ['card-club', __('Trèfle', 'wp-piwigo-display')]
    ];

    var customMasks = Array.isArray(window.WPDCustomMasks) ? window.WPDCustomMasks : [];

    function customPreviewStyle(mask) {
        return {
            background: '#1d2327',
            WebkitMaskImage: 'url("' + mask.dataUri + '")',
            maskImage: 'url("' + mask.dataUri + '")',
            WebkitMaskSize: 'contain',
            maskSize: 'contain',
            WebkitMaskPosition: 'center',
            maskPosition: 'center',
            WebkitMaskRepeat: 'no-repeat',
            maskRepeat: 'no-repeat'
        };
    }

    function shapeButton(item, selected, setShape) {
        var value = item[0];
        var label = item[1];

        return createElement(
            Button,
            {
                key: value,
                className: 'wpd-shape-picker-button',
                isPressed: selected === value,
                'aria-pressed': selected === value ? 'true' : 'false',
                'aria-label': label,
                title: label,
                onClick: function () { setShape(value); }
            },
            createElement('span', {
                className: 'wpd-shape-picker-preview wpd-shape-preview-' + value,
                'aria-hidden': 'true'
            }),
            createElement('span', null, label)
        );
    }

    function customMaskButton(mask, selected, setShape) {
        return createElement(
            Button,
            {
                key: mask.value,
                className: 'wpd-shape-picker-button',
                isPressed: selected === mask.value,
                'aria-pressed': selected === mask.value ? 'true' : 'false',
                'aria-label': mask.name,
                title: mask.name,
                onClick: function () { setShape(mask.value); }
            },
            createElement('span', {
                className: 'wpd-shape-picker-preview',
                style: customPreviewStyle(mask),
                'aria-hidden': 'true'
            }),
            createElement('span', null, mask.name)
        );
    }

    var withShapeControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (props.name !== 'wp-piwigo-display/gallery') {
                return createElement(BlockEdit, props);
            }

            var attributes = props.attributes || {};
            var shape = attributes.shape || 'rectangle';
            var setShape = function (value) {
                props.setAttributes({ shape: value });
            };

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
                        createElement('strong', null, __('Formes intégrées', 'wp-piwigo-display')),
                        createElement(
                            'div',
                            {
                                className: 'wpd-shape-picker-grid',
                                role: 'group',
                                'aria-label': __('Choisir une forme', 'wp-piwigo-display')
                            },
                            shapes.map(function (item) {
                                return shapeButton(item, shape, setShape);
                            })
                        ),
                        customMasks.length ? createElement(
                            Fragment,
                            null,
                            createElement('strong', null, __('Masques SVG personnalisés', 'wp-piwigo-display')),
                            createElement(
                                'div',
                                {
                                    className: 'wpd-shape-picker-grid',
                                    role: 'group',
                                    'aria-label': __('Choisir un masque SVG personnalisé', 'wp-piwigo-display')
                                },
                                customMasks.map(function (mask) {
                                    return customMaskButton(mask, shape, setShape);
                                })
                            )
                        ) : null,
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
