(function ($) {
    'use strict';

    var currentEditor = 'content';
    var $dialog = $('#wpd-classic-builder');

    function value(name) {
        return $dialog.find('[data-wpd="' + name + '"]').val();
    }

    function checked(name) {
        return $dialog.find('[data-wpd="' + name + '"]').is(':checked');
    }

    function escapeValue(input) {
        return String(input).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function buildShortcode() {
        var album = value('album');
        var type = value('type');
        var parts = [];

        if (album) parts.push('album="' + escapeValue(album) + '"');
        parts.push('type="' + escapeValue(type) + '"');
        parts.push('recursive="' + (checked('recursive') ? 'true' : 'false') + '"');

        ['limit', 'max', 'sort', 'order', 'orientation', 'caption', 'style', 'tags'].forEach(function (key) {
            var item = value(key);
            if (item !== '' && item !== '0') parts.push(key + '="' + escapeValue(item) + '"');
        });

        parts.push('lightbox="' + (checked('lightbox') ? 'true' : 'false') + '"');
        parts.push('rounded="' + (checked('rounded') ? 'true' : 'false') + '"');

        if (type === 'slider') {
            parts.push('autoplay="' + (checked('autoplay') ? 'true' : 'false') + '"');
            ['interval', 'speed', 'ratio', 'navigation'].forEach(function (key) {
                var item = value(key);
                if (item !== '') parts.push(key + '="' + escapeValue(item) + '"');
            });
        }

        return '[piwigo ' + parts.join(' ') + ']';
    }

    function refresh() {
        var slider = value('type') === 'slider';
        $dialog.find('.wpd-slider-options').toggle(slider);
        $dialog.find('[data-wpd-preview]').val(buildShortcode());
    }

    function insertShortcode() {
        var shortcode = buildShortcode();
        if (!value('album')) {
            window.alert('Indiquez un identifiant d’album Piwigo.');
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
        width: 760,
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
})(jQuery);
