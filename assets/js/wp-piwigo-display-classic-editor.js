(function ($) {
    'use strict';

    $(function () {
        var currentEditor = 'content';
        var $dialog = $('#wpd-classic-builder');

        if (window.WPDAlbumPicker) {
            window.WPDAlbumPicker.attach($dialog.find('.wpd-album-field'), $dialog.find('[data-wpd="album"]'));
        }

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
            add(parts, 'height', value('height'), true);
            add(parts, 'tag', value('tag'), true);
            add(parts, 'tags', value('tags'), true);
            add(parts, 'tag_mode', value('tag_mode'), true);
            add(parts, 'url', value('url'), true);

            parts.push('recursive="' + (checked('recursive') ? 'true' : 'false') + '"');
            if (checked('recursive')) add(parts, 'depth', value('depth') || '10', true);
            parts.push('lightbox="' + (checked('lightbox') ? 'true' : 'false') + '"');
            parts.push('rounded="' + (checked('rounded') ? 'true' : 'false') + '"');

            if (type === 'slider') {
                parts.push('autoplay="' + (checked('autoplay') ? 'true' : 'false') + '"');
                parts.push('thumbnails="' + (checked('thumbnails') ? 'true' : 'false') + '"');
                ['interval', 'speed', 'ratio', 'navigation', 'width', 'align'].forEach(function (key) {
                    add(parts, key, value(key), true);
                });
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
                tinymce.get(currentEditor).execCommand('mceInsertContent', false, shortcode);
            } else if (window.QTags && typeof QTags.insertContent === 'function') {
                QTags.insertContent(shortcode);
            } else {
                var $textarea = $('#' + currentEditor);
                $textarea.val(($textarea.val() || '') + shortcode);
            }

            $dialog.dialog('close');
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
            open: refresh
        });

        $(document).on('click', '.wpd-open-builder', function (event) {
            event.preventDefault();
            currentEditor = $(this).data('editor') || 'content';
            $dialog.dialog('open');
        });

        $dialog.on('change input', 'input, select', refresh);
    });
})(jQuery);
