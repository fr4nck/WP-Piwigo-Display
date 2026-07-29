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
        var expanded = {};
        var selectedId = String($(input).val() || '');
        var childrenByParent = {};
        var hierarchy = [];

        albums.forEach(function (album) {
            var id = String(album.id);
            var depth = Number(album.depth || 0);
            hierarchy[depth] = id;
            hierarchy.length = depth + 1;
            album.parentId = album.parentId || (depth > 0 ? hierarchy[depth - 1] : 0);
            album.pathIds = album.pathIds || hierarchy.slice(0);
            var parentId = String(album.parentId || 0);
            if (!childrenByParent[parentId]) childrenByParent[parentId] = [];
            childrenByParent[parentId].push(album);
        });

        $picker.empty().removeAttr('hidden');
        var $search = $('<input type="search" class="wpd-album-search">').attr('placeholder', l.search || 'Rechercher un album…');
        var $list = $('<div class="wpd-album-list" role="tree"></div>');
        $picker.append($search, $list);

        function matchingIds(query) {
            var visible = {};
            if (!query) return visible;
            albums.forEach(function (album) {
                if (String(album.path || album.name).toLocaleLowerCase().indexOf(query) === -1) return;
                var ids = album.pathIds || [album.id];
                ids.forEach(function (id) { visible[String(id)] = true; });
            });
            return visible;
        }

        function draw(query) {
            query = String(query || '').toLocaleLowerCase();
            var searchVisible = matchingIds(query);
            $list.empty();
            var count = 0;

            albums.forEach(function (album) {
                var id = String(album.id);
                var parentId = String(album.parentId || 0);
                var hasChildren = !!(childrenByParent[id] && childrenByParent[id].length);
                var parentVisible = !parentId || parentId === '0' || expanded[parentId];
                var visible = query ? !!searchVisible[id] : (Number(album.depth || 0) === 0 || parentVisible);
                if (!visible) return;
                count += 1;

                var $row = $('<div class="wpd-album-row" role="treeitem"></div>');
                $row.css('--wpd-depth', album.depth || 0).attr('aria-level', Number(album.depth || 0) + 1);
                if (hasChildren) $row.attr('aria-expanded', query ? 'true' : (expanded[id] ? 'true' : 'false'));
                if (selectedId === id) $row.addClass('is-selected').attr('aria-selected', 'true');

                var $toggle = $('<button type="button" class="wpd-album-toggle"></button>');
                if (hasChildren) {
                    $toggle.attr('aria-label', expanded[id] ? 'Fermer les sous-albums' : 'Ouvrir les sous-albums');
                    $toggle.append($('<span class="dashicons" aria-hidden="true"></span>').addClass(query || expanded[id] ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2'));
                    $toggle.on('click', function () {
                        expanded[id] = !expanded[id];
                        draw($search.val());
                    });
                } else {
                    $toggle.attr('aria-hidden', 'true').prop('disabled', true);
                }

                var $name = $('<button type="button" class="wpd-album-name"></button>').text(album.name);
                $name.on('click', function () {
                    selectedId = id;
                    draw($search.val());
                });

                var $meta = $('<span class="wpd-album-option-meta"></span>').text('#' + album.id + (album.images ? ' · ' + album.images + ' photo(s)' : ''));
                var $confirm = $('<button type="button" class="button button-small wpd-album-confirm"></button>')
                    .attr('aria-label', (l.choose || 'Choisir cet album') + ' : ' + album.name)
                    .text(l.validate || 'Valider');
                $confirm.on('click', function () {
                    selectedId = id;
                    $(input).val(album.id).trigger('input').trigger('change');
                    draw($search.val());
                });

                $row.append($toggle, $name, $meta, $confirm);
                $list.append($row);
            });

            if (!count) {
                $list.append($('<p class="wpd-album-empty"></p>').text(l.empty || 'Aucun album trouvé.'));
            }
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
