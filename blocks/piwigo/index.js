(function (blocks, blockEditor, components, element, serverSideRender, compose, i18n) {
    var el = element.createElement, __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls, useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody, TextControl = components.TextControl, ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl, CheckboxControl = components.CheckboxControl, Button = components.Button;
    var ServerSideRender = serverSideRender;
    var useDebounce = compose.useDebounce;

    var uiDefaults = {
        albumId: '', displayType: 'gallery', recursive: false, depth: 10, limit: 0, max: 0, latest: 0, random: 0,
        sort: 'manual', order: 'desc', orientations: [], caption: 'default', lightbox: true, rounded: false,
        style: 'default', autoplay: true, interval: 5000, speed: 500, ratio: '16/9', width: '100%', height: '', fit: 'contain',
        navigation: 'thumbnails', tag: '', tags: '', tagMode: 'any'
    };

    var shortcodeMap = {albumId:'album',displayType:'type',tagMode:'tag_mode',orientations:'orientation'};
    var booleanKeys = ['recursive','lightbox','rounded','autoplay'];
    var numericKeys = ['depth','limit','max','latest','random','interval','speed'];

    var escapeShortcodeValue = function(value) {
        return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    };

    var buildShortcode = function(attributes) {
        var parts = [];
        Object.keys(attributes).forEach(function(key) {
            var value = attributes[key];
            if (typeof value === 'undefined' || value === null || value === '') return;
            if (Array.isArray(value)) {
                if (!value.length) return;
                value = value.join(',');
            } else if (booleanKeys.indexOf(key) !== -1) {
                value = value ? 'true' : 'false';
            }
            parts.push((shortcodeMap[key] || key) + '="' + escapeShortcodeValue(value) + '"');
        });
        return '[piwigo' + (parts.length ? ' ' + parts.join(' ') : '') + ']';
    };

    var options = function (values) { return values.map(function (v) { return { label: v[1], value: v[0] }; }); };
    var select = function (label, attribute, values, attrs, set) { return el(SelectControl, {label: label, value: attrs[attribute], options: options(values), onChange: function(v){var o={};o[attribute]=v;set(o);}}); };
    var number = function(label, attribute, attrs, set, help) { return el(TextControl, {label:label, type:'number', value:attrs[attribute], help:help, min:0, onChange:function(v){var o={};o[attribute]=parseInt(v || '0',10);set(o);}}); };
    var clamp = function(value, minimum, maximum) { return Math.min(maximum, Math.max(minimum, value)); };
    var dimensionNumber = function(value, fallback) {
        var parsed = parseInt(String(value || '').replace(/[^0-9]/g, ''), 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    function Edit(props) {
        var explicit = props.attributes;
        var a = Object.assign({}, uiDefaults, explicit);
        var set = props.setAttributes;
        var frame = element.useRef(null);
        var drag = element.useRef(null);
        var resizingState = element.useState('');
        var resizing = resizingState[0], setResizing = resizingState[1];
        var delayed = element.useState(explicit), delayedAttributes = delayed[0], setDelayed = delayed[1];
        var update = useDebounce(function(next){setDelayed(next);}, 500);
        element.useEffect(function(){update(explicit);}, [explicit]);
        var width = clamp(dimensionNumber(a.width, 100), 20, 100);
        var height = a.height === '' ? '' : clamp(dimensionNumber(a.height, 160), 160, 1200);

        element.useEffect(function() {
            if (!resizing) return;

            var onPointerMove = function(event) {
                if (!drag.current) return;
                event.preventDefault();
                if (drag.current.axis === 'width') {
                    var nextWidth = clamp(Math.round(drag.current.width + ((event.clientX - drag.current.x) / drag.current.availableWidth * 100)), 20, 100);
                    set({width: nextWidth + '%'});
                } else {
                    var nextHeight = clamp(Math.round(drag.current.height + event.clientY - drag.current.y), 160, 1200);
                    set({height: nextHeight + 'px'});
                }
            };
            var onPointerUp = function() {
                drag.current = null;
                setResizing('');
            };

            document.addEventListener('pointermove', onPointerMove);
            document.addEventListener('pointerup', onPointerUp);
            document.addEventListener('pointercancel', onPointerUp);
            return function() {
                document.removeEventListener('pointermove', onPointerMove);
                document.removeEventListener('pointerup', onPointerUp);
                document.removeEventListener('pointercancel', onPointerUp);
            };
        }, [resizing]);

        var startResize = function(axis, event) {
            var node = frame.current;
            if (!node) return;
            var rect = node.getBoundingClientRect();
            var parentRect = node.parentElement ? node.parentElement.getBoundingClientRect() : rect;
            drag.current = {
                axis: axis,
                x: event.clientX,
                y: event.clientY,
                width: width,
                height: height || clamp(Math.round(rect.height), 160, 1200),
                availableWidth: Math.max(1, parentRect.width)
            };
            setResizing(axis);
            event.preventDefault();
        };

        var resizeWithKeyboard = function(axis, event) {
            var direction = 0;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') direction = -1;
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') direction = 1;
            if (!direction && event.key !== 'Home' && event.key !== 'End') return;
            event.preventDefault();

            if (axis === 'width') {
                var widthStep = event.shiftKey ? 5 : 1;
                var nextWidth = event.key === 'Home' ? 20 : (event.key === 'End' ? 100 : clamp(width + direction * widthStep, 20, 100));
                set({width: nextWidth + '%'});
            } else {
                var heightStep = event.shiftKey ? 50 : 10;
                var currentHeight = height || 160;
                var nextHeight = event.key === 'Home' ? 160 : (event.key === 'End' ? 1200 : clamp(currentHeight + direction * heightStep, 160, 1200));
                set({height: nextHeight + 'px'});
            }
        };

        var orientation = function(value, label) {
            return el(CheckboxControl,{label:label,checked:a.orientations.indexOf(value)!==-1,onChange:function(checked){var values=a.orientations.filter(function(item){return item!==value;});if(checked)values.push(value);set({orientations:values});}});
        };
        var shortcode = buildShortcode(explicit);
        var content = [
            el(PanelBody,{title:__('Contenu','wp-piwigo-display'),initialOpen:true,key:'content'},
                el(TextControl,{label:__('Identifiant de l’album Piwigo','wp-piwigo-display'),type:'number',value:a.albumId,onChange:function(v){set({albumId:v.replace(/[^0-9]/g,'')});},help:__('Retrouvez cet identifiant dans l’URL de la catégorie Piwigo.','wp-piwigo-display')}),
                select(__('Type d’affichage','wp-piwigo-display'),'displayType',[['gallery',__('Galerie','wp-piwigo-display')],['slider',__('Diaporama','wp-piwigo-display')]],a,set),
                el(ToggleControl,{label:__('Affichage récursif','wp-piwigo-display'),checked:a.recursive,onChange:function(v){set({recursive:v});}}), a.recursive && number(__('Profondeur maximale','wp-piwigo-display'),'depth',a,set), number(__('Limite d’images','wp-piwigo-display'),'limit',a,set),number(__('Maximum d’images','wp-piwigo-display'),'max',a,set),number(__('Dernières images','wp-piwigo-display'),'latest',a,set),number(__('Images aléatoires','wp-piwigo-display'),'random',a,set)),
            el(PanelBody,{title:__('Tri','wp-piwigo-display'),initialOpen:false,key:'sort'},select(__('Tri','wp-piwigo-display'),'sort',[['manual',__('Ordre Piwigo','wp-piwigo-display')],['date',__('Date','wp-piwigo-display')],['name',__('Nom','wp-piwigo-display')],['id',__('Identifiant','wp-piwigo-display')],['random',__('Aléatoire','wp-piwigo-display')]],a,set),select(__('Ordre','wp-piwigo-display'),'order',[['asc',__('Croissant','wp-piwigo-display')],['desc',__('Décroissant','wp-piwigo-display')]],a,set)),
            el(PanelBody,{title:__('Orientation','wp-piwigo-display'),initialOpen:false,key:'orientation'},el('p',null,__('Toutes si aucune orientation n’est cochée.','wp-piwigo-display')),orientation('portrait',__('Portrait','wp-piwigo-display')),orientation('paysage',__('Paysage','wp-piwigo-display')),orientation('carré',__('Carré','wp-piwigo-display'))),
            el(PanelBody,{title:__('Affichage','wp-piwigo-display'),initialOpen:false,key:'display'},select(__('Légendes','wp-piwigo-display'),'caption',[['default',__('Réglage global','wp-piwigo-display')],['none',__('Aucune','wp-piwigo-display')],['title',__('Titre','wp-piwigo-display')],['description',__('Description','wp-piwigo-display')],['title-description',__('Titre et description','wp-piwigo-display')]],a,set),el(ToggleControl,{label:__('Lightbox','wp-piwigo-display'),checked:a.lightbox,onChange:function(v){set({lightbox:v});}}),el(ToggleControl,{label:__('Coins arrondis','wp-piwigo-display'),checked:a.rounded,onChange:function(v){set({rounded:v});}}),select(__('Style','wp-piwigo-display'),'style',[['default',__('Réglage global','wp-piwigo-display')],['theme',__('Thème WordPress','wp-piwigo-display')],['minimal',__('Minimal','wp-piwigo-display')],['none',__('Sans habillage','wp-piwigo-display')]],a,set)),
            a.displayType==='slider' && el(PanelBody,{title:__('Diaporama','wp-piwigo-display'),initialOpen:true,key:'slider'},el(ToggleControl,{label:__('Lecture automatique','wp-piwigo-display'),checked:a.autoplay,onChange:function(v){set({autoplay:v});}}),number(__('Tempo (ms)','wp-piwigo-display'),'interval',a,set),number(__('Vitesse de transition (ms)','wp-piwigo-display'),'speed',a,set),el(TextControl,{label:__('Ratio','wp-piwigo-display'),value:a.ratio,onChange:function(v){set({ratio:v});}}),el(TextControl,{label:__('Largeur (%)','wp-piwigo-display'),type:'number',min:20,max:100,value:width,onChange:function(v){set({width:clamp(dimensionNumber(v,100),20,100)+'%'});}}),el(TextControl,{label:__('Hauteur (px)','wp-piwigo-display'),type:'number',min:160,max:1200,value:height,onChange:function(v){set({height:v===''?'':clamp(dimensionNumber(v,160),160,1200)+'px'});},help:__('Laissez vide pour utiliser le ratio.','wp-piwigo-display')}),select(__('Respect de l’image','wp-piwigo-display'),'fit',[['contain',__('Image entière','wp-piwigo-display')],['cover',__('Cadre rempli','wp-piwigo-display')],['auto',__('Automatique','wp-piwigo-display')],['raw',__('Brut','wp-piwigo-display')]],a,set),select(__('Navigation','wp-piwigo-display'),'navigation',[['thumbnails',__('Miniatures','wp-piwigo-display')],['dots',__('Points','wp-piwigo-display')],['none',__('Aucune','wp-piwigo-display')]],a,set)),
            el(PanelBody,{title:__('Filtres avancés','wp-piwigo-display'),initialOpen:false,key:'tags'},el(TextControl,{label:__('Tag unique','wp-piwigo-display'),value:a.tag,onChange:function(v){set({tag:v});}}),el(TextControl,{label:__('Plusieurs tags (séparés par des virgules)','wp-piwigo-display'),value:a.tags,onChange:function(v){set({tags:v});}}),select(__('Mode','wp-piwigo-display'),'tagMode',[['any',__('Au moins un','wp-piwigo-display')],['all',__('Tous','wp-piwigo-display')]],a,set),el(Button,{variant:'secondary',onClick:function(){navigator.clipboard.writeText(shortcode);}},__('Copier le shortcode équivalent','wp-piwigo-display')))
        ];
        var preview = !a.albumId
            ? el(components.Placeholder,{icon:'format-gallery',label:__('WP Piwigo Display','wp-piwigo-display'),instructions:__('Renseignez l’identifiant d’un album Piwigo dans la barre latérale.','wp-piwigo-display')})
            : el(ServerSideRender,{block:'wp-piwigo-display/gallery',attributes:delayedAttributes});

        if (a.albumId && a.displayType === 'slider') {
            preview = el('div',{
                    className:'wpd-block-slider-resizer' + (resizing ? ' is-resizing' : '') + (height ? ' has-custom-height' : ''),
                    ref:frame,
                    style:{width:width+'%','--wpd-editor-slider-height':height ? height+'px' : 'auto'}
                },
                preview,
                el('span',{className:'wpd-block-slider-dimensions','aria-live':'polite'},width+' %'+(height ? ' × '+height+' px' : '')),
                el('span',{
                    className:'wpd-block-slider-handle wpd-block-slider-handle-width',
                    role:'slider',tabIndex:0,'aria-label':__('Redimensionner la largeur du diaporama','wp-piwigo-display'),
                    'aria-valuemin':20,'aria-valuemax':100,'aria-valuenow':width,'aria-valuetext':width+' %',
                    onPointerDown:function(event){startResize('width',event);},
                    onKeyDown:function(event){resizeWithKeyboard('width',event);}
                }),
                el('span',{
                    className:'wpd-block-slider-handle wpd-block-slider-handle-height',
                    role:'slider',tabIndex:0,'aria-label':__('Redimensionner la hauteur du diaporama','wp-piwigo-display'),
                    'aria-valuemin':160,'aria-valuemax':1200,'aria-valuenow':height || 160,'aria-valuetext':(height || 160)+' px',
                    onPointerDown:function(event){startResize('height',event);},
                    onKeyDown:function(event){resizeWithKeyboard('height',event);}
                })
            );
        }

        return el('div',useBlockProps(),el(InspectorControls,null,content),preview);
    }

    blocks.registerBlockType('wp-piwigo-display/gallery',{edit:Edit,save:function(){return null;},transforms:{from:[{type:'shortcode',tag:'piwigo',attributes:{
        albumId:{type:'string',shortcode:'album'},displayType:{type:'string',shortcode:'type'},recursive:{type:'boolean',shortcode:'recursive'},depth:{type:'number',shortcode:'depth'},limit:{type:'number',shortcode:'limit'},max:{type:'number',shortcode:'max'},latest:{type:'number',shortcode:'latest'},random:{type:'number',shortcode:'random'},sort:{type:'string',shortcode:'sort'},order:{type:'string',shortcode:'order'},orientations:{type:'array',shortcode:'orientation'},caption:{type:'string',shortcode:'caption'},lightbox:{type:'boolean',shortcode:'lightbox'},rounded:{type:'boolean',shortcode:'rounded'},style:{type:'string',shortcode:'style'},autoplay:{type:'boolean',shortcode:'autoplay'},interval:{type:'number',shortcode:'interval'},speed:{type:'number',shortcode:'speed'},ratio:{type:'string',shortcode:'ratio'},width:{type:'string',shortcode:'width'},height:{type:'string',shortcode:'height'},align:{type:'string',shortcode:'align'},fit:{type:'string',shortcode:'fit'},navigation:{type:'string',shortcode:'navigation'},tag:{type:'string',shortcode:'tag'},tags:{type:'string',shortcode:'tags'},tagMode:{type:'string',shortcode:'tag_mode'}
    }}]}});
})(window.wp.blocks,window.wp.blockEditor,window.wp.components,window.wp.element,window.wp.serverSideRender,window.wp.compose,window.wp.i18n);
