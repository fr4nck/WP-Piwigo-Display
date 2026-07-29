(function (hooks, compose, element, blockEditor, components, i18n) {
    'use strict';

    var addFilter = hooks.addFilter;
    var createHigherOrderComponent = compose.createHigherOrderComponent;
    var createElement = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var __ = i18n.__;

    var withParityControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            if (props.name !== 'wp-piwigo-display/gallery') {
                return createElement(BlockEdit, props);
            }

            var attributes = props.attributes || {};
            var setAttributes = props.setAttributes;

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
                            title: __('Options avancées Piwigo', 'wp-piwigo-display'),
                            initialOpen: false
                        },
                        createElement(SelectControl, {
                            label: __('Preset', 'wp-piwigo-display'),
                            value: attributes.preset || '',
                            options: [
                                { label: __('Aucun', 'wp-piwigo-display'), value: '' },
                                { label: __('Slider', 'wp-piwigo-display'), value: 'slider' },
                                { label: __('Actualités', 'wp-piwigo-display'), value: 'actualites' }
                            ],
                            onChange: function (value) {
                                setAttributes({ preset: value });
                            }
                        }),
                        createElement(TextControl, {
                            label: __('URL Piwigo spécifique', 'wp-piwigo-display'),
                            type: 'url',
                            value: attributes.piwigoUrl || '',
                            help: __('Laissez vide pour utiliser l’URL configurée dans les réglages du plugin.', 'wp-piwigo-display'),
                            onChange: function (value) {
                                setAttributes({ piwigoUrl: value });
                            }
                        })
                    )
                )
            );
        };
    }, 'withWpdParityControls');

    addFilter('editor.BlockEdit', 'wp-piwigo-display/gutenberg-parity', withParityControls);
})(window.wp.hooks, window.wp.compose, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
