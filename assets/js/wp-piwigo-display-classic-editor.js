(function ($) {
    'use strict';

    $(function () {
        var currentEditor = 'content';
        var $dialog = $('#wpd-classic-builder');
        var editingNode = null;
        var editingLegacyHeight = '';
        var initialValues = {};

        if (window.WPDAlbumPicker) {
            window.WPDAlbumPicker.attach($dialog.find('.wpd-album-field'), $dialog.find('[data-wpd="album"]'));
        }

        $dialog.find('[data-wpd]').each(function () {
            var $field = $(this);
            var name = $field.data('wpd');
            initialValues[name] = $field.is(':checkbox') ? $field.is(':checked') : $field.val();
        });

        function value(name) {
            return $dialog.find('[data-wpd="' + name + '"]').val();
        }

        function checked(name) {
            return $dialog.find('[data-wpd="' + name + '"]').is(':checked');
        }

        function escapeValue(input) {
            return String(input).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        }

        function add(parts, key, item, allowZero) {
            if (item === undefined || item === null || item === '') return;
            if (!allowZero && item === '0') return;
            parts.push(key + '="' + escapeValue(item) + '"');
        }

        function bounded(name, minimum, maximum, fallback) {
            var parsed = parseInt(value(name), 10);
            if (!Number.isFinite(parsed)) return fallback;
            return Math.min(maximum, Math.max(minimum, parsed));
        }

        function resetFields() {
            Object.keys(initialValues).forEach(function (name) {
                var $field = $dialog.find('[data-wpd="' + name + '"]');
                if ($field.is(':checkbox')) {
                    $field.prop('checked', initialValues[name]);
                } else {
                    $field.val(initialValues[name]);
                }
            });
            editingLegacyHeight = '';
        }

        function parseShortcode(shortcode) {
            var attributes = {};
            var pattern = /([a-z_][a-z0-9_-]*)\s*=\s*"((?:\\.|[^"])*)"/gi;
            var match;
            while ((match = pattern.exec(shortcode)) !== null) {
                attributes[match[1]] = match[2].replace(/\\(["\\])/g, '$1');
            }
            return attributes;
        }

        function populateShortcode(shortcode) {
            var attributes = parseShortcode(shortcode);
            resetFields();

            Object.keys(attributes).forEach(function (name) {
                var $field = $dialog.find('[data-wpd="' + name + '"]');
                if (!$field.length) return;
                if ($field.is(':checkbox')) {
                    $field.prop('checked', attributes[name] === 'true' || attributes[name] === '1');
                    return;
                }
                if (name === 'height' && !/^\d+px$/.test(attributes[name])) {
                    editingLegacyHeight = attributes[name];
                    $field.val('');
                    return;
                }
                if (name === 'height' || name === 'width') {
                    $field.val(parseInt(attributes[name], 10));
                    return;
                }
                $field.val(attributes[name]);
            });
        }

        function buildShortcode() {
            var type = value('type');
            var parts = [];

            add(parts, 'album', value('album'), true);
            add(parts, 'preset', value('preset'), true);
            add(parts, 'type', type, true);
            add(parts, 'sort', value('sort'), true);
            add(parts, 'order', value('order'), true);
            ['limit', 'max', 'latest', 'random'].forEach(function (key) { add(parts, key, value(key), false); });
            add(parts, 'orientation', value('orientation'), true);
            add(parts, 'caption', value('caption'), true);
            add(parts, 'style', value('style'), true);
            add(parts, 'fit', value('fit'), true);
            add(parts, 'tag', value('tag'), true);
            add(parts, 'tags', value('tags'), true);
            add(parts, 'tag_mode', value('tag_mode'), true);
            add(parts, 'url', value('url'), true);
            add(parts, 'height', value('height') ? bounded('height', 160, 1200, 160) + 'px' : editingLegacyHeight, true);

            parts.push('recursive="' + (checked('recursive') ? 'true' : 'false') + '"');
            if (checked('recursive')) add(parts, 'depth', value('depth') || '10', true);
            parts.push('lightbox="' + (checked('lightbox') ? 'true' : 'false') + '"');
            parts.push('rounded="' + (checked('rounded') ? 'true' : 'false') + '"');

            if (type === 'slider') {
                parts.push('autoplay="' + (checked('autoplay') ? 'true' : 'false') + '"');
                parts.push('thumbnails="' + (checked('thumbnails') ? 'true' : 'false') + '"');
                ['interval', 'speed', 'ratio', 'navigation', 'align'].forEach(function (key) {
                    add(parts, key, value(key), true);
                });
                add(parts, 'width', bounded('width', 20, 100, 100) + '%', true);
            }

            return '[piwigo ' + parts.join(' ') + ']';
        }

        function refresh() {
            var slider = value('type') === 'slider';
            $dialog.find('.wpd-slider-options, .wpd-slider-layout-option').toggle(slider);
            $dialog.find('.wpd-depth-option').toggle(checked('recursive'));
            $dialog.find('[data-wpd-preview]').val(buildShortcode());
        }

        function insertShortcode() {
            var shortcode = buildShortcode();
            if (!value('album')) {
                window.alert('Choisissez un album Piwigo ou indiquez son identifiant, son nom ou son chemin.');
                return;
            }

            if (window.tinymce && tinymce.get(currentEditor) && !tinymce.get(currentEditor).isHidden()) {
                var editor = tinymce.get(currentEditor);
                if (editingNode && editingNode.parentNode) {
                    editor.selection.select(editingNode);
                }
                editor.execCommand('mceInsertContent', false, shortcode);
            } else if (window.QTags && typeof QTags.insertContent === 'function') {
                QTags.insertContent(shortcode);
            } else {
                var $textarea = $('#' + currentEditor);
                $textarea.val(($textarea.val() || '') + shortcode);
            }

            $dialog.dialog('close');
            editingNode = null;
        }

        function setPrimaryButtonLabel(label) {
            $dialog.parent().find('.ui-dialog-buttonpane .button-primary').text(label);
        }

        $dialog.dialog({
            autoOpen: false,
            modal: true,
            width: 900,
            maxWidth: '95%',
            buttons: [
                { text: 'Insérer dans la page', class: 'button button-primary', click: insertShortcode },
                { text: 'Annuler', click: function () { $(this).dialog('close'); } }
            ],
            open: function () {
                setPrimaryButtonLabel(editingNode ? 'Mettre à jour' : 'Insérer dans la page');
                refresh();
            },
            close: function () {
                editingNode = null;
                editingLegacyHeight = '';
            }
        });

        $(document).on('click', '.wpd-open-builder', function (event) {
            event.preventDefault();
            currentEditor = $(this).data('editor') || 'content';
            editingNode = null;
            resetFields();
            $dialog.dialog('open');
        });

        $(document).on('wpd:edit-shortcode', function (event, editorId, shortcode, node) {
            currentEditor = editorId || 'content';
            editingNode = node || null;
            populateShortcode(shortcode || '');
            $dialog.dialog('open');
        });

        $dialog.on('input', '[data-wpd="height"]', function () {
            editingLegacyHeight = '';
        });
        $dialog.on('change input', 'input, select', refresh);
    });
})(jQuery);
