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

        function ensurePhotoTextControls() {
            var $type = $dialog.find('[data-wpd="type"]');
            if ($type.length && !$type.find('option[value="photo-text"]').length) {
                $type.append($('<option value="photo-text">Texte rempli de photos</option>'));
            }
            if (!$dialog.find('[data-wpd="photo_text"]').length) {
                var $anchor = $dialog.find('.wpd-collage-options').last();
                var $controls = $(
                    '<div class="wpd-photo-text-options">' +
                    '<label>Texte<textarea data-wpd="photo_text" rows="3">PÊLE-MÊLE</textarea><span class="description">Jusqu’à quatre lignes.</span></label>' +
                    '<label>Graine<input type="text" data-wpd="photo_text_seed" value="0"></label>' +
                    '<label>Police<select data-wpd="photo_text_font"><option value="inherit">Police du thème</option><option value="system">Système</option><option value="serif">Serif</option><option value="mono">Monospace</option></select></label>' +
                    '<label>Graisse<input type="number" min="100" max="900" step="100" data-wpd="photo_text_weight" value="800"></label>' +
                    '<label>Taille du texte<input type="number" min="120" max="300" data-wpd="photo_text_size" value="230"></label>' +
                    '<label>Interlettrage<input type="number" min="-20" max="80" data-wpd="photo_text_letter_spacing" value="0"></label>' +
                    '<label>Hauteur de ligne (%)<input type="number" min="70" max="160" step="5" data-wpd="photo_text_line_height" value="100"></label>' +
                    '<label>Largeur maximale (%)<input type="number" min="20" max="100" data-wpd="photo_text_max_width" value="100"></label>' +
                    '<label>Alignement<select data-wpd="photo_text_align"><option value="left">Gauche</option><option value="center" selected>Centre</option><option value="right">Droite</option></select></label>' +
                    '<label>Remplissage<select data-wpd="photo_text_fill_mode"><option value="grid">Grille</option><option value="masonry">Masonry</option><option value="collage">Pêle-mêle</option></select></label>' +
                    '<label>Densité (%)<input type="number" min="50" max="200" step="10" data-wpd="photo_text_density" value="100"></label>' +
                    '<label class="wpd-photo-text-collage-option">Rotation pêle-mêle (°)<input type="number" min="0" max="15" data-wpd="photo_text_rotation" value="6"></label>' +
                    '<label class="wpd-photo-text-collage-option">Dispersion pêle-mêle<input type="number" min="0" max="50" data-wpd="photo_text_spread" value="18"></label>' +
                    '<label>Nombre maximal de photos<input type="number" min="1" max="40" data-wpd="photo_text_max_images" value="20"></label>' +
                    '<label><input type="checkbox" data-wpd="photo_text_outline" checked> Afficher un contour</label>' +
                    '<label class="wpd-photo-text-outline-option">Épaisseur du contour<input type="number" min="0" max="12" data-wpd="photo_text_outline_width" value="3"></label>' +
                    '<label class="wpd-photo-text-outline-option">Couleur du contour<input type="text" data-wpd="photo_text_outline_color" value="#ffffff"></label>' +
                    '<label>Fond<input type="text" data-wpd="photo_text_background" value="transparent" placeholder="transparent ou #ffffff"></label>' +
                    '</div>'
                );
                if ($anchor.length) $controls.insertAfter($anchor); else $dialog.find('.wpd-builder-grid').append($controls);
            }
        }

        ensureCollageControls();
        ensurePhotoTextControls();

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
        function escapeValue(input) { return String(input).replace(/\\/g, '\\\\').replace(/\r?\n/g, '\\n').replace(/"/g, '\\"'); }
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
                if (name === 'photo_text') { $field.val(attributes[name].replace(/\\n/g, '\n')); return; }
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
                add(parts, 'collage_seed', value('collage_seed') || '0', true);
                add(parts, 'collage_rotation', bounded('collage_rotation', 0, 15, 6), true);
                add(parts, 'collage_spread', bounded('collage_spread', 0, 50, 18), true);
                add(parts, 'collage_overlap', bounded('collage_overlap', 0, 40, 12), true);
                add(parts, 'collage_size', bounded('collage_size', 120, 420, 220), true);
                add(parts, 'collage_variation', bounded('collage_variation', 0, 50, 20), true);
            }
            if (type === 'photo-text') {
                add(parts, 'photo_text', value('photo_text') || 'PÊLE-MÊLE', true);
                add(parts, 'photo_text_seed', value('photo_text_seed') || '0', true);
                add(parts, 'photo_text_font', value('photo_text_font') || 'inherit', true);
                add(parts, 'photo_text_weight', bounded('photo_text_weight', 100, 900, 800), true);
                add(parts, 'photo_text_size', bounded('photo_text_size', 120, 300, 230), true);
                add(parts, 'photo_text_letter_spacing', bounded('photo_text_letter_spacing', -20, 80, 0), true);
                add(parts, 'photo_text_line_height', bounded('photo_text_line_height', 70, 160, 100), true);
                add(parts, 'photo_text_max_width', bounded('photo_text_max_width', 20, 100, 100), true);
                add(parts, 'photo_text_align', value('photo_text_align') || 'center', true);
                add(parts, 'photo_text_fill_mode', value('photo_text_fill_mode') || 'grid', true);
                add(parts, 'photo_text_density', bounded('photo_text_density', 50, 200, 100), true);
                if ((value('photo_text_fill_mode') || 'grid') === 'collage') {
                    add(parts, 'photo_text_rotation', bounded('photo_text_rotation', 0, 15, 6), true);
                    add(parts, 'photo_text_spread', bounded('photo_text_spread', 0, 50, 18), true);
                }
                add(parts, 'photo_text_max_images', bounded('photo_text_max_images', 1, 40, 20), true);
                parts.push('photo_text_outline="' + (checked('photo_text_outline') ? 'true' : 'false') + '"');
                if (checked('photo_text_outline')) {
                    add(parts, 'photo_text_outline_width', bounded('photo_text_outline_width', 0, 12, 3), true);
                    add(parts, 'photo_text_outline_color', value('photo_text_outline_color') || '#ffffff', true);
                }
                add(parts, 'photo_text_background', value('photo_text_background') || 'transparent', true);
            }
            return '[piwigo ' + parts.join(' ') + ']';
        }
        function refresh() {
            var type = value('type');
            $dialog.find('.wpd-slider-options, .wpd-slider-layout-option').toggle(type === 'slider');
            $dialog.find('.wpd-masonry-options').toggle(type === 'masonry');
            $dialog.find('.wpd-justified-options').toggle(type === 'justified');
            $dialog.find('.wpd-collage-options').toggle(type === 'collage');
            $dialog.find('.wpd-photo-text-options').toggle(type === 'photo-text');
            $dialog.find('.wpd-photo-text-collage-option').toggle(type === 'photo-text' && (value('photo_text_fill_mode') || 'grid') === 'collage');
            $dialog.find('.wpd-photo-text-outline-option').toggle(type === 'photo-text' && checked('photo_text_outline'));
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
        $dialog.on('change input', 'input, textarea, select', refresh);
    });
})(jQuery);
