(function ($) {
    'use strict';

    var cache = null;
    var pending = null;

    function labels() {
        return (window.WPDAlbumPickerConfig && WPDAlbumPickerConfig.labels) || {};
    }

    function loadAlbums() {
        if (cache) return $.Deferred().resolve(cache).promise();
        if (pending) return pending;
        pending = $.ajax({
            url: WPDAlbumPickerConfig.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: { action: 'wpd_get_albums', nonce: WPDAlbumPickerConfig.nonce }
        }).then(function (response) {
            if (!response || !response.success) {
                return $.Deferred().reject(response && response.data && response.data.message).promise();
            }
            cache = response.data.albums || [];
            return cache;
        });
        pending.always(function () { pending = null; });
        return pending;
    }

    function render($picker, albums, input) {
        var l = labels();
        $picker.empty().removeAttr('hidden');
        var $search = $('<input type="search" class="wpd-album-search">').attr('placeholder', l.search || 'Rechercher un album…');
        var $list = $('<div class="wpd-album-list" role="listbox"></div>');
        $picker.append($search, $list);

        function draw(query) {
            query = String(query || '').toLocaleLowerCase();
            $list.empty();
            var matches = albums.filter(function (album) {
                return !query || String(album.path || album.name).toLocaleLowerCase().indexOf(query) !== -1;
            });
            if (!matches.length) {
                $list.append($('<p class="wpd-album-empty"></p>').text(l.empty || 'Aucun album trouvé.'));
                return;
            }
            matches.forEach(function (album) {
                var $button = $('<button type="button" class="wpd-album-option" role="option"></button>');
                $button.css('--wpd-depth', album.depth || 0);
                $button.attr('data-value', album.path || album.id);
                $button.append($('<span class="dashicons dashicons-category" aria-hidden="true"></span>'));
                $button.append($('<span class="wpd-album-option-name"></span>').text(album.name));
                $button.append($('<span class="wpd-album-option-meta"></span>').text('#' + album.id + (album.images ? ' · ' + album.images + ' photo(s)' : '')));
                $button.on('click', function () {
                    $(input).val(album.path || album.id).trigger('input').trigger('change');
                    $picker.attr('hidden', 'hidden').empty();
                });
                $list.append($button);
            });
        }
        $search.on('input', function () { draw(this.value); });
        draw('');
        $search.trigger('focus');
    }

    function attach(root, input) {
        var $root = $(root);
        var $input = $(input);
        if (!$root.length || !$input.length || $root.data('wpd-picker-ready')) return;
        $root.data('wpd-picker-ready', true);
        var $button = $root.find('.wpd-browse-albums');
        var $picker = $root.find('.wpd-album-picker');
        $button.on('click', function () {
            if (!$picker.attr('hidden')) {
                $picker.attr('hidden', 'hidden').empty();
                return;
            }
            $picker.removeAttr('hidden').html('<p class="wpd-album-loading">' + (labels().loading || 'Chargement des albums…') + '</p>');
            loadAlbums().done(function (albums) { render($picker, albums, $input); })
                .fail(function (message) {
                    $picker.removeAttr('hidden').html($('<p class="notice notice-error inline"></p>').text(message || labels().error || 'Impossible de charger les albums.'));
                });
        });
    }

    function attachAll(context) {
        $(context || document).find('.wpd-album-field').each(function () {
            var input = $(this).find('input[type="text"]').get(0);
            if (input) attach(this, input);
        });
    }

    window.WPDAlbumPicker = {
        attach: attach,
        attachAll: attachAll,
        reload: function () { cache = null; }
    };

    $(function () { attachAll(document); });
})(jQuery);
