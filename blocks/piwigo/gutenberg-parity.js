(function (hooks, compose, element, blockEditor, components, i18n) {
    'use strict';

    var addFilter = hooks.addFilter;
    var createHigherOrderComponent = compose.createHigherOrderComponent;
    var createElement = element.createElement;
    var Fragment = element.Fragment;
    var useState = element.useState;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var Button = components.Button;
    var Spinner = components.Spinner;
    var Notice = components.Notice;
    var __ = i18n.__;

    var albumCache = null;
    var albumRequest = null;

    function loadAlbums() {
        if (albumCache) {
            return Promise.resolve(albumCache);
        }
        if (albumRequest) {
            return albumRequest;
        }
        if (!window.WPDAlbumPickerConfig) {
            return Promise.reject(new Error(__('Configuration du sélecteur d’albums introuvable.', 'wp-piwigo-display')));
        }

        var data = new window.FormData();
        data.append('action', 'wpd_get_albums');
        data.append('nonce', window.WPDAlbumPickerConfig.nonce);

        albumRequest = window.fetch(window.WPDAlbumPickerConfig.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            if (!response.ok) {
                throw new Error(__('Impossible de charger les albums.', 'wp-piwigo-display'));
            }
            return response.json();
        }).then(function (response) {
            if (!response || !response.success) {
                var message = response && response.data && response.data.message;
                throw new Error(message || __('Impossible de charger les albums.', 'wp-piwigo-display'));
            }
            albumCache = response.data.albums || [];
            return albumCache;
        }).finally(function () {
            albumRequest = null;
        });

        return albumRequest;
    }

    function AlbumPicker(props) {
        var state = useState(false);
        var open = state[0];
        var setOpen = state[1];
        var albumsState = useState(albumCache || []);
        var albums = albumsState[0];
        var setAlbums = albumsState[1];
        var loadingState = useState(false);
        var loading = loadingState[0];
        var setLoading = loadingState[1];
        var errorState = useState('');
        var error = errorState[0];
        var setError = errorState[1];
        var searchState = useState('');
        var search = searchState[0];
        var setSearch = searchState[1];

        function togglePicker() {
            if (open) {
                setOpen(false);
                return;
            }
            setOpen(true);
            if (albums.length) {
                return;
            }
            setLoading(true);
            setError('');
            loadAlbums().then(function (items) {
                setAlbums(items);
            }).catch(function (exception) {
                setError(exception.message || __('Impossible de charger les albums.', 'wp-piwigo-display'));
            }).finally(function () {
                setLoading(false);
            });
        }

        var query = String(search || '').toLocaleLowerCase();
        var matches = albums.filter(function (album) {
            return !query || String(album.path || album.name || '').toLocaleLowerCase().indexOf(query) !== -1;
        });

        var children = [
            createElement(TextControl, {
                key: 'manual',
                label: __('Album', 'wp-piwigo-display'),
                value: props.value || '',
                help: __('Identifiant, nom ou chemin. La saisie manuelle reste disponible.', 'wp-piwigo-display'),
                onChange: props.onChange
            }),
            createElement(Button, {
                key: 'button',
                variant: 'secondary',
                onClick: togglePicker,
                'aria-expanded': open
            }, open ? __('Fermer la liste', 'wp-piwigo-display') : __('Choisir dans Piwigo', 'wp-piwigo-display'))
        ];

        if (open) {
            if (loading) {
                children.push(createElement('div', { key: 'loading', style: { marginTop: '12px' } }, createElement(Spinner)));
            } else if (error) {
                children.push(createElement(Notice, { key: 'error', status: 'error', isDismissible: false }, error));
            } else {
                children.push(createElement(TextControl, {
                    key: 'search',
                    label: __('Rechercher un album', 'wp-piwigo-display'),
                    value: search,
                    onChange: setSearch
                }));

                children.push(createElement('div', {
                    key: 'list',
                    role: 'listbox',
                    style: { maxHeight: '320px', overflowY: 'auto', border: '1px solid #dcdcde', marginTop: '8px' }
                }, matches.length ? matches.map(function (album) {
                    return createElement(Button, {
                        key: String(album.id),
                        variant: 'tertiary',
                        onClick: function () {
                            props.onChange(String(album.id));
                            setOpen(false);
                        },
                        style: {
                            display: 'flex',
                            width: '100%',
                            justifyContent: 'space-between',
                            paddingLeft: (12 + (Number(album.depth) || 0) * 16) + 'px',
                            borderBottom: '1px solid #f0f0f1',
                            textAlign: 'left'
                        }
                    },
                    createElement('span', null, album.name),
                    createElement('small', null, '#' + album.id + (album.images ? ' · ' + album.images : '')));
                }) : createElement('p', { style: { padding: '12px', margin: 0 } }, __('Aucun album trouvé.', 'wp-piwigo-display'))));
            }
        }

        return createElement('div', null, children);
    }

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
                            title: __('Album Piwigo', 'wp-piwigo-display'),
                            initialOpen: true
                        },
                        createElement(AlbumPicker, {
                            value: attributes.albumId || '',
                            onChange: function (value) {
                                setAttributes({ albumId: value });
                            }
                        })
                    ),
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
