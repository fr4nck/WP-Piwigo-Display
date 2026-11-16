(function () {
    'use strict';

    if (!window.tinymce) return;

    tinymce.PluginManager.add('wpd_shortcode_preview', function (editor) {
        function encodeShortcode(shortcode) {
            return encodeURIComponent(shortcode);
        }

        function decodeShortcode(value) {
            try {
                return decodeURIComponent(value || '');
            } catch (error) {
                return '';
            }
        }

        function preview(shortcode) {
            var album = shortcode.match(/\balbum="([^"]*)"/);
            var slider = /\btype="slider"/.test(shortcode);
            var label = slider ? 'Diaporama Piwigo' : 'Galerie Piwigo';
            if (album && album[1]) label += ' — ' + album[1];

            return '<span class="wpd-tinymce-shortcode" contenteditable="false" role="button" tabindex="0" title="Double-cliquez pour modifier" data-wpd-shortcode="' +
                encodeShortcode(shortcode) +
                '" style="display:inline-block;padding:8px 12px;border:1px solid #8c8f94;border-radius:3px;background:#f6f7f7;">' +
                editor.dom.encode(label) +
                '</span>';
        }

        // Preserve shortcode values containing literal brackets, quotes, or
        // backslash-escaped quotes. A regular expression ending at the first
        // ] corrupts shortcodes when that bracket occurs inside an attribute.
        function renderShortcodePreviews(content) {
            var output = '';
            var cursor = 0;
            var index = 0;

            while ((index = content.indexOf('[piwigo', cursor)) !== -1) {
                var afterName = content.charAt(index + 7);
                var previous = index > 0 ? content.charAt(index - 1) : '';
                if (previous === '[' || (afterName !== ']' && !/\s/.test(afterName))) {
                    output += content.slice(cursor, index + 7);
                    cursor = index + 7;
                    continue;
                }

                var quote = '';
                var escaped = false;
                var end = -1;

                for (var position = index + 7; position < content.length; position++) {
                    var character = content.charAt(position);
                    if (quote) {
                        if (escaped) {
                            escaped = false;
                        } else if (character === '\\') {
                            escaped = true;
                        } else if (character === quote) {
                            quote = '';
                        }
                    } else if (character === '"' || character === "'") {
                        quote = character;
                    } else if (character === ']') {
                        end = position;
                        break;
                    }
                }

                if (end === -1) {
                    return output + content.slice(cursor);
                }

                output += content.slice(cursor, index) + preview(content.slice(index, end + 1));
                cursor = end + 1;
            }

            return output + content.slice(cursor);
        }

        function requestEdit(node) {
            if (!node || !window.jQuery) return;
            window.jQuery(document).trigger('wpd:edit-shortcode', [
                editor.id,
                decodeShortcode(node.getAttribute('data-wpd-shortcode')),
                node
            ]);
        }

        editor.on('BeforeSetContent', function (event) {
            event.content = renderShortcodePreviews(event.content);
        });

        editor.on('PostProcess', function (event) {
            if (!event.get || event.content.indexOf('wpd-tinymce-shortcode') === -1) return;

            var container = document.createElement('div');
            var shortcodes = [];
            container.innerHTML = event.content;
            Array.prototype.forEach.call(container.querySelectorAll('.wpd-tinymce-shortcode[data-wpd-shortcode]'), function (node) {
                var token = 'WPD_SHORTCODE_' + shortcodes.length + '_PLACEHOLDER';
                shortcodes.push(decodeShortcode(node.getAttribute('data-wpd-shortcode')));
                node.parentNode.replaceChild(document.createTextNode(token), node);
            });
            event.content = container.innerHTML;
            shortcodes.forEach(function (shortcode, index) {
                event.content = event.content.replace('WPD_SHORTCODE_' + index + '_PLACEHOLDER', function () { return shortcode; });
            });
        });

        editor.on('dblclick', function (event) {
            requestEdit(editor.dom.getParent(event.target, '.wpd-tinymce-shortcode[data-wpd-shortcode]'));
        });

        editor.on('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            var node = editor.dom.getParent(editor.selection.getNode(), '.wpd-tinymce-shortcode[data-wpd-shortcode]');
            if (!node) return;
            event.preventDefault();
            requestEdit(node);
        });
    });
})();
