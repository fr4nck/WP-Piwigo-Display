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
        style: 'default', autoplay: true, interval: 5000, speed: 500, ratio: '16/9', height: '', fit: 'contain',
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

    function Edit(props) {
        var explicit = props.attributes;
        var a = Object.assign({}, uiDefaults, explicit);
        var set = props.setAttributes;
        var delayed = element.useState(explicit), delayedAttributes = delayed[0], setDelayed = delayed[1];
        var update = useDebounce(function(next){setDelayed(next);}, 500);
        element.useEffect(function(){update(explicit);}, [explicit]);

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
            a.displayType==='slider' && el(PanelBody,{title:__('Diaporama','wp-piwigo-display'),initialOpen:true,key:'slider'},el(ToggleControl,{label:__('Lecture automatique','wp-piwigo-display'),checked:a.autoplay,onChange:function(v){set({autoplay:v});}}),number(__('Tempo (ms)','wp-piwigo-display'),'interval',a,set),number(__('Vitesse de transition (ms)','wp-piwigo-display'),'speed',a,set),el(TextControl,{label:__('Ratio','wp-piwigo-display'),value:a.ratio,onChange:function(v){set({ratio:v});}}),el(TextControl,{label:__('Hauteur','wp-piwigo-display'),value:a.height,onChange:function(v){set({height:v});}}),select(__('Respect de l’image','wp-piwigo-display'),'fit',[['contain',__('Image entière','wp-piwigo-display')],['cover',__('Cadre rempli','wp-piwigo-display')],['auto',__('Automatique','wp-piwigo-display')],['raw',__('Brut','wp-piwigo-display')]],a,set),select(__('Navigation','wp-piwigo-display'),'navigation',[['thumbnails',__('Miniatures','wp-piwigo-display')],['dots',__('Points','wp-piwigo-display')],['none',__('Aucune','wp-piwigo-display')]],a,set)),
            el(PanelBody,{title:__('Filtres avancés','wp-piwigo-display'),initialOpen:false,key:'tags'},el(TextControl,{label:__('Tag unique','wp-piwigo-display'),value:a.tag,onChange:function(v){set({tag:v});}}),el(TextControl,{label:__('Plusieurs tags (séparés par des virgules)','wp-piwigo-display'),value:a.tags,onChange:function(v){set({tags:v});}}),select(__('Mode','wp-piwigo-display'),'tagMode',[['any',__('Au moins un','wp-piwigo-display')],['all',__('Tous','wp-piwigo-display')]],a,set),el(Button,{variant:'secondary',onClick:function(){navigator.clipboard.writeText(shortcode);}},__('Copier le shortcode équivalent','wp-piwigo-display')))
        ];
        return el('div',useBlockProps(),el(InspectorControls,null,content),!a.albumId ? el(components.Placeholder,{icon:'format-gallery',label:__('WP Piwigo Display','wp-piwigo-display'),instructions:__('Renseignez l’identifiant d’un album Piwigo dans la barre latérale.','wp-piwigo-display')}) : el(ServerSideRender,{block:'wp-piwigo-display/gallery',attributes:delayedAttributes}));
    }

    blocks.registerBlockType('wp-piwigo-display/gallery',{edit:Edit,save:function(){return null;},transforms:{from:[{type:'shortcode',tag:'piwigo',attributes:{
        albumId:{type:'string',shortcode:'album'},displayType:{type:'string',shortcode:'type'},recursive:{type:'boolean',shortcode:'recursive'},depth:{type:'number',shortcode:'depth'},limit:{type:'number',shortcode:'limit'},max:{type:'number',shortcode:'max'},latest:{type:'number',shortcode:'latest'},random:{type:'number',shortcode:'random'},sort:{type:'string',shortcode:'sort'},order:{type:'string',shortcode:'order'},orientations:{type:'array',shortcode:'orientation'},caption:{type:'string',shortcode:'caption'},lightbox:{type:'boolean',shortcode:'lightbox'},rounded:{type:'boolean',shortcode:'rounded'},style:{type:'string',shortcode:'style'},autoplay:{type:'boolean',shortcode:'autoplay'},interval:{type:'number',shortcode:'interval'},speed:{type:'number',shortcode:'speed'},ratio:{type:'string',shortcode:'ratio'},height:{type:'string',shortcode:'height'},fit:{type:'string',shortcode:'fit'},navigation:{type:'string',shortcode:'navigation'},tag:{type:'string',shortcode:'tag'},tags:{type:'string',shortcode:'tags'},tagMode:{type:'string',shortcode:'tag_mode'}
    }}]}});
})(window.wp.blocks,window.wp.blockEditor,window.wp.components,window.wp.element,window.wp.serverSideRender,window.wp.compose,window.wp.i18n);
