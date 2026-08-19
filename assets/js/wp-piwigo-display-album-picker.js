(function ($) {
    'use strict';

    var cache = null;
    var pending = null;
    var pickerSequence = 0;

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
        var pickerId = $picker.attr('id');

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

        $picker.empty().removeAttr('hidden').attr({
            role: 'region',
            'aria-label': l.picker || 'Sélecteur d’albums Piwigo'
        });

        var searchId = pickerId + '-search';
        var statusId = pickerId + '-status';
        var listId = pickerId + '-list';
        var $searchLabel = $('<label class="screen-reader-text"></label>')
            .attr('for', searchId)
            .text(l.search || 'Rechercher un album…');
        var $search = $('<input type="search" class="wpd-album-search">')
            .attr({
                id: searchId,
                placeholder: l.search || 'Rechercher un album…',
                'aria-controls': listId,
                autocomplete: 'off'
            });
        var $status = $('<p class="screen-reader-text wpd-album-status" role="status" aria-live="polite"></p>')
            .attr('id', statusId);
        var $list = $('<div class="wpd-album-list" role="tree"></div>')
            .attr({
                id: listId,
                'aria-label': l.results || 'Résultats des albums',
                'aria-describedby': statusId
            });
        $picker.append($searchLabel, $search, $status, $list);

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

        function branchIsVisible(album) {
            var ids = album.pathIds || [album.id];
            var lastAncestor = Math.max(0, ids.length - 1);
            for (var index = 0; index < lastAncestor; index += 1) {
                if (!expanded[String(ids[index])]) return false;
            }
            return true;
        }

        function focusRelative(current, direction) {
            var $buttons = $list.find('.wpd-album-name:visible');
            var index = $buttons.index(current);
            if (!$buttons.length || index < 0) return;
            var next = Math.max(0, Math.min($buttons.length - 1, index + direction));
            $buttons.eq(next).trigger('focus');
        }

        function draw(query, restoreFocusId) {
            query = String(query || '').toLocaleLowerCase();
            var searchVisible = matchingIds(query);
            $list.empty();
            var count = 0;

            albums.forEach(function (album) {
                var id = String(album.id);
                var hasChildren = !!(childrenByParent[id] && childrenByParent[id].length);
                var branchExpanded = query ? true : !!expanded[id];
                var visible = query ? !!searchVisible[id] : (Number(album.depth || 0) === 0 || branchIsVisible(album));
                if (!visible) return;
                count += 1;

                var rowId = pickerId + '-album-' + id;
                var $row = $('<div class="wpd-album-row" role="treeitem"></div>');
                $row.css('--wpd-depth', album.depth || 0).attr({
                    id: rowId,
                    'aria-level': Number(album.depth || 0) + 1,
                    'aria-selected': selectedId === id ? 'true' : 'false'
                });
                if (hasChildren) $row.attr('aria-expanded', branchExpanded ? 'true' : 'false');
                if (selectedId === id) $row.addClass('is-selected');

                var $toggle = $('<button type="button" class="wpd-album-toggle"></button>');
                if (hasChildren) {
                    $toggle.attr({
                        'aria-label': branchExpanded ? 'Fermer les sous-albums de ' + album.name : 'Ouvrir les sous-albums de ' + album.name,
                        'aria-expanded': branchExpanded ? 'true' : 'false'
                    });
                    $toggle.append($('<span class="dashicons" aria-hidden="true"></span>').addClass(branchExpanded ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2'));
                    $toggle.on('click', function () {
                        expanded[id] = !expanded[id];
                        draw($search.val(), id);
                    });
                } else {
                    $toggle.attr({ 'aria-hidden': 'true', tabindex: '-1' }).prop('disabled', true);
                }

                var $name = $('<button type="button" class="wpd-album-name"></button>')
                    .attr({
                        'data-album-id': id,
                        'aria-label': album.name + ', album ' + album.id
                    })
                    .text(album.name);
                $name.on('click', function () {
                    selectedId = id;
                    draw($search.val(), id);
                }).on('keydown', function (event) {
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        focusRelative(this, 1);
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        focusRelative(this, -1);
                    } else if (event.key === 'Home') {
                        event.preventDefault();
                        $list.find('.wpd-album-name:visible').first().trigger('focus');
                    } else if (event.key === 'End') {
                        event.preventDefault();
                        $list.find('.wpd-album-name:visible').last().trigger('focus');
                    }
                });

                var $meta = $('<span class="wpd-album-option-meta"></span>').text('#' + album.id + (album.images ? ' · ' + album.images + ' photo(s)' : ''));
                var $confirm = $('<button type="button" class="button button-small wpd-album-confirm"></button>')
                    .attr('aria-label', (l.choose || 'Choisir cet album') + ' : ' + album.name)
                    .text(l.validate || 'Valider');
                $confirm.on('click', function () {
                    selectedId = id;
                    $(input).val(album.id).trigger('input').trigger('change').trigger('focus');
                    draw($search.val());
                });

                $row.append($toggle, $name, $meta, $confirm);
                $list.append($row);
            });

            if (!count) {
                $list.append($('<p class="wpd-album-empty"></p>').text(l.empty || 'Aucun album trouvé.'));
            }

            $status.text(count + (count > 1 ? ' albums affichés.' : ' album affiché.'));
            if (restoreFocusId) {
                $list.find('.wpd-album-name[data-album-id="' + restoreFocusId + '"]').trigger('focus');
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
        var pickerId = $picker.attr('id');

        if (!pickerId) {
            pickerSequence += 1;
            pickerId = 'wpd-album-picker-' + pickerSequence;
            $picker.attr('id', pickerId);
        }

        function closePicker(restoreFocus) {
            if ($picker.attr('hidden')) return;
            $picker.attr('hidden', 'hidden').empty();
            $button.attr('aria-expanded', 'false');
            if (restoreFocus) $button.trigger('focus');
        }

        $button.attr({
            'aria-controls': pickerId,
            'aria-expanded': 'false',
            'aria-haspopup': 'dialog'
        });

        $button.on('click', function () {
            if (!$picker.attr('hidden')) {
                closePicker(true);
                return;
            }
            $button.attr('aria-expanded', 'true');
            $picker.removeAttr('hidden').attr({ role: 'status', 'aria-live': 'polite' })
                .html($('<p class="wpd-album-loading"></p>').text(labels().loading || 'Chargement des albums…'));
            loadAlbums().done(function (albums) { render($picker, albums, $input); })
                .fail(function (message) {
                    $picker.removeAttr('hidden').attr({ role: 'alert', 'aria-live': 'assertive' })
                        .html($('<p class="notice notice-error inline"></p>').text(message || labels().error || 'Impossible de charger les albums.'));
                });
        });

        $picker.on('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                closePicker(true);
            }
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
