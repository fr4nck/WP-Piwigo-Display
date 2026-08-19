(function ($) {
    'use strict';

    $(function () {
        var currentEditor = 'content';
        var $dialog = $('#wpd-classic-builder');
        var editingNode = null;
        var editingLegacyHeight = '';
        var initialValues = {};

        function ensureCollageControls() {
            var $type = $dialog.find('[data-wpd="type"]');
            if ($type.length && !$type.find('option[value="collage"]').length) {
                $type.append($('<option value="collage">Collage / Pêle-mêle</option>'));
            }
            if (!$dialog.find('[data-wpd="collage_seed"]').length) {
                var $anchor = $dialog.find('.wpd-justified-options').last();
                var $controls = $(
                    '<div class="wpd-collage-options">' +
                    '<label>Graine<input type="number" data-wpd="collage_seed" value="0"></label>' +
                    '<label>Rotation maximale (°)<input type="number" min="0" max="15" data-wpd="collage_rotation" value="6"></label>' +
                    '<label>Dispersion (px)<input type="number" min="0" max="50" data-wpd="collage_spread" value="18"></label>' +
                    '<label>Chevauchement (px)<input type="number" min="0" max="40" data-wpd="collage_overlap" value="12"></label>' +
                    '<label>Taille moyenne (px)<input type="number" min="120" max="420" data-wpd="collage_size" value="220"></label>' +
                    '<label>Variation de taille (%)<input type="number" min="0" max="50" data-wpd="collage_variation" value="20"></label>' +
                    '</div>'
                );
                if ($anchor.length) $controls.insertAfter($anchor); else $dialog.find('.wpd-builder-grid').append($controls);
            }
        }

        ensureCollageControls();

        if (window.WPDAlbumPicker) {
            window.WPDAlbumPicker.attach($dialog.find('.wpd-album-field'), $dialog.find('[data-wpd="album"]'));
        }

        $dialog.find('[data-wpd]').each(function () {
            var $field = $(this);
            var name = $field.data('wpd');
            initialValues[name] = $field.is(':checkbox') ? $field.is(':checked') : $field.val();
        });

        function buildShapePicker() {
            var $select = $dialog.find('[data-wpd="shape"]');
            var $picker = $dialog.find('[data-wpd-shape-picker]');
            if (!$select.length) return;
            if (!$picker.length) {
                $picker = $('<div class="wpd-shape-picker-grid" data-wpd-shape-picker></div>').insertAfter($select);
            }
            if ($picker.children().length) return;
            $picker.attr('role', 'group').attr('aria-label', 'Choisir une forme');
            $select.find('option').each(function () {
                var shapeValue = String($(this).val());
                var label = String($(this).text());
                var $button = $('<button type="button" class="wpd-shape-picker-button" aria-pressed="false"></button>');
                $button.attr('data-wpd-shape-value', shapeValue).attr('aria-label', label).attr('title', label);
                $button.append($('<span aria-hidden="true"></span>').addClass('wpd-shape-picker-preview wpd-shape-preview-' + shapeValue));
                $button.append($('<span></span>').text(label));
                $picker.append($button);
            });
        }

        function syncShapePicker() {
            var selected = String(value('shape') || 'rectangle');
            $dialog.find('[data-wpd-shape-value]').each(function () {
                var active = String($(this).attr('data-wpd-shape-value')) === selected;
                $(this).attr('aria-pressed', active ? 'true' : 'false');
            });
        }

        function value(name) { return $dialog.find('[data-wpd="' + name + '"]').val(); }
        function checked(name) { return $dialog.find('[data-wpd="' + name + '"]').is(':checked'); }
        function escapeValue(input) { return String(input).replace(/\\/g, '\\\\').replace(/"/g, '\\"'); }
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
                if ($field.is(':checkbox')) $field.prop('checked', initialValues[name]); else $field.val(initialValues[name]);
            });
            editingLegacyHeight = '';
        }
        function parseShortcode(shortcode) {
            var attributes = {};
            var pattern = /([a-z_][a-z0-9_-]*)\s*=\s*"((?:\\.|[^"])*)"/gi;
            var match;
            while ((match = pattern.exec(shortcode)) !== null) attributes[match[1]] = match[2].replace(/\\(["\\])/g, '$1');
            return attributes;
        }
        function populateShortcode(shortcode) {
            var attributes = parseShortcode(shortcode);
            resetFields();
            Object.keys(attributes).forEach(function (name) {
                var $field = $dialog.find('[data-wpd="' + name + '"]');
                if (!$field.length) return;
                if ($field.is(':checkbox')) { $field.prop('checked', attributes[name] === 'true' || attributes[name] === '1'); return; }
                if (name === 'height' && !/^\d+px$/.test(attributes[name])) { editingLegacyHeight = attributes[name]; $field.val(''); return; }
                if (name === 'height' || name === 'width') { $field.val(parseInt(attributes[name], 10)); return; }
                $field.val(attributes[name]);
            });
        }
        function buildShortcode() {
            var type = value('type');
            var shape = value('shape') || 'rectangle';
            var parts = [];
            add(parts, 'album', value('album'), true); add(parts, 'preset', value('preset'), true); add(parts, 'type', type, true);
            add(parts, 'sort', value('sort'), true); add(parts, 'order', value('order'), true);
            ['limit', 'max', 'latest', 'random'].forEach(function (key) { add(parts, key, value(key), false); });
            add(parts, 'orientation', value('orientation'), true); add(parts, 'caption', value('caption'), true); add(parts, 'style', value('style'), true);
            add(parts, 'fit', value('fit'), true); add(parts, 'shape', shape, true);
            if (shape === 'rounded') add(parts, 'radius', bounded('radius', 0, 50, 8), true);
            add(parts, 'tag', value('tag'), true); add(parts, 'tags', value('tags'), true); add(parts, 'tag_mode', value('tag_mode'), true); add(parts, 'url', value('url'), true);
            add(parts, 'height', value('height') ? bounded('height', 160, 1200, 160) + 'px' : editingLegacyHeight, true);
            parts.push('recursive="' + (checked('recursive') ? 'true' : 'false') + '"'); if (checked('recursive')) add(parts, 'depth', value('depth') || '10', true);
            parts.push('lightbox="' + (checked('lightbox') ? 'true' : 'false') + '"'); parts.push('rounded="' + (checked('rounded') ? 'true' : 'false') + '"');
            if (type === 'slider') {
                parts.push('transparent_background="' + (checked('transparent_background') ? 'true' : 'false') + '"'); parts.push('autoplay="' + (checked('autoplay') ? 'true' : 'false') + '"'); parts.push('thumbnails="' + (checked('thumbnails') ? 'true' : 'false') + '"');
                ['interval', 'speed', 'transition', 'direction', 'ratio', 'navigation', 'align'].forEach(function (key) { add(parts, key, value(key), true); }); add(parts, 'width', bounded('width', 20, 100, 100) + '%', true);
            }
            if (type === 'masonry') { add(parts, 'masonry_columns', bounded('masonry_columns', 2, 6, 4), true); add(parts, 'masonry_gap', bounded('masonry_gap', 0, 64, 16), true); }
            if (type === 'justified') { add(parts, 'justified_row_height', bounded('justified_row_height', 100, 600, 220), true); add(parts, 'justified_gap', bounded('justified_gap', 0, 64, 8), true); }
            if (type === 'collage') {
                add(parts, 'collage_seed', parseInt(value('collage_seed') || '0', 10) || 0, true);
                add(parts, 'collage_rotation', bounded('collage_rotation', 0, 15, 6), true);
                add(parts, 'collage_spread', bounded('collage_spread', 0, 50, 18), true);
                add(parts, 'collage_overlap', bounded('collage_overlap', 0, 40, 12), true);
                add(parts, 'collage_size', bounded('collage_size', 120, 420, 220), true);
                add(parts, 'collage_variation', bounded('collage_variation', 0, 50, 20), true);
            }
            return '[piwigo ' + parts.join(' ') + ']';
        }
        function refresh() {
            var type = value('type');
            $dialog.find('.wpd-slider-options, .wpd-slider-layout-option').toggle(type === 'slider');
            $dialog.find('.wpd-masonry-options').toggle(type === 'masonry');
            $dialog.find('.wpd-justified-options').toggle(type === 'justified');
            $dialog.find('.wpd-collage-options').toggle(type === 'collage');
            $dialog.find('.wpd-radius-option').toggle(value('shape') === 'rounded'); $dialog.find('.wpd-depth-option').toggle(checked('recursive'));
            syncShapePicker(); $dialog.find('[data-wpd-preview]').val(buildShortcode());
        }
        function insertShortcode() {
            var shortcode = buildShortcode();
            if (!value('album')) { window.alert('Choisissez un album Piwigo ou indiquez son identifiant, son nom ou son chemin.'); return; }
            if (window.tinymce && tinymce.get(currentEditor) && !tinymce.get(currentEditor).isHidden()) {
                var editor = tinymce.get(currentEditor); if (editingNode && editingNode.parentNode) editor.selection.select(editingNode); editor.execCommand('mceInsertContent', false, shortcode);
            } else if (window.QTags && typeof QTags.insertContent === 'function') QTags.insertContent(shortcode); else { var $textarea = $('#' + currentEditor); $textarea.val(($textarea.val() || '') + shortcode); }
            $dialog.dialog('close'); editingNode = null;
        }
        function setPrimaryButtonLabel(label) { $dialog.parent().find('.ui-dialog-buttonpane .button-primary').text(label); }

        buildShapePicker();
        $dialog.dialog({ autoOpen: false, modal: true, width: 900, maxWidth: '95%', buttons: [
            { text: 'Insérer dans la page', class: 'button button-primary', click: insertShortcode }, { text: 'Annuler', click: function () { $(this).dialog('close'); } }
        ], open: function () { setPrimaryButtonLabel(editingNode ? 'Mettre à jour' : 'Insérer dans la page'); refresh(); }, close: function () { editingNode = null; editingLegacyHeight = ''; } });
        $(document).on('click', '.wpd-open-builder', function (event) { event.preventDefault(); currentEditor = $(this).data('editor') || 'content'; editingNode = null; resetFields(); $dialog.dialog('open'); });
        $(document).on('wpd:edit-shortcode', function (event, editorId, shortcode, node) { currentEditor = editorId || 'content'; editingNode = node || null; populateShortcode(shortcode || ''); $dialog.dialog('open'); });
        $dialog.on('click', '[data-wpd-shape-value]', function () { $dialog.find('[data-wpd="shape"]').val($(this).attr('data-wpd-shape-value')).trigger('change'); });
        $dialog.on('input', '[data-wpd="height"]', function () { editingLegacyHeight = ''; });
        $dialog.on('change input', 'input, select', refresh);
    });
})(jQuery);