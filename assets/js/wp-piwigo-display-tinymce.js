(function () {
    'use strict';

    if (!window.tinymce) return;

    tinymce.PluginManager.add('wpd_shortcode_preview', function (editor) {
        function preview(shortcode) {
            var album = shortcode.match(/\balbum="([^"]*)"/);
            var slider = /\btype="slider"/.test(shortcode);
            var label = slider ? 'Diaporama Piwigo' : 'Galerie Piwigo';
            if (album && album[1]) label += ' — ' + album[1];

            return '<span class="wpd-tinymce-shortcode" contenteditable="false" data-wpd-shortcode="' +
                editor.dom.encode(shortcode) +
                '" style="display:inline-block;padding:8px 12px;border:1px solid #8c8f94;border-radius:3px;background:#f6f7f7;">' +
                editor.dom.encode(label) +
                '</span>';
        }

        editor.on('BeforeSetContent', function (event) {
            event.content = event.content.replace(/\[piwigo(?:\s[^\]]*)?\]/g, preview);
        });

        editor.on('PostProcess', function (event) {
            if (!event.get || event.content.indexOf('wpd-tinymce-shortcode') === -1) return;

            var container = document.createElement('div');
            container.innerHTML = event.content;
            Array.prototype.forEach.call(container.querySelectorAll('.wpd-tinymce-shortcode[data-wpd-shortcode]'), function (node) {
                node.parentNode.replaceChild(document.createTextNode(node.getAttribute('data-wpd-shortcode')), node);
            });
            event.content = container.innerHTML;
        });
    });
})();
