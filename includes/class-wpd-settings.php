<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Settings
{
    public const OPTION_NAME = 'wp_piwigo_display_options';

    public static function register(): void
    {
        register_setting('wp_piwigo_display', self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_options'],
            'default' => self::default_options(),
        ]);

        add_settings_section('wp_piwigo_display_main', __('Connexion Piwigo', 'wp-piwigo-display'), '__return_false', 'wp-piwigo-display');
        add_settings_field('piwigo_url', __('URL de la galerie Piwigo', 'wp-piwigo-display'), [self::class, 'render_piwigo_url_field'], 'wp-piwigo-display', 'wp_piwigo_display_main');
        add_settings_field('cache_duration', __('Durée du cache', 'wp-piwigo-display'), [self::class, 'render_cache_duration_field'], 'wp-piwigo-display', 'wp_piwigo_display_main');

        add_settings_section('wp_piwigo_display_defaults', __('Affichage par défaut', 'wp-piwigo-display'), [self::class, 'render_defaults_section'], 'wp-piwigo-display');
        add_settings_field('default_type', __('Type d’affichage', 'wp-piwigo-display'), [self::class, 'render_default_type_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_fit', __('Respect des images', 'wp-piwigo-display'), [self::class, 'render_default_fit_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_ratio', __('Ratio du diaporama', 'wp-piwigo-display'), [self::class, 'render_default_ratio_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_height', __('Hauteur du diaporama', 'wp-piwigo-display'), [self::class, 'render_default_height_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_autoplay', __('Lecture automatique', 'wp-piwigo-display'), [self::class, 'render_default_autoplay_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_interval', __('Tempo', 'wp-piwigo-display'), [self::class, 'render_default_interval_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_speed', __('Vitesse de transition', 'wp-piwigo-display'), [self::class, 'render_default_speed_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_lightbox', __('Lightbox', 'wp-piwigo-display'), [self::class, 'render_default_lightbox_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_caption', __('Légendes', 'wp-piwigo-display'), [self::class, 'render_default_caption_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_style', __('Intégration graphique', 'wp-piwigo-display'), [self::class, 'render_default_style_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_navigation', __('Navigation du diaporama', 'wp-piwigo-display'), [self::class, 'render_default_navigation_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_rounded', __('Coins arrondis', 'wp-piwigo-display'), [self::class, 'render_default_rounded_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_sort', __('Tri des images', 'wp-piwigo-display'), [self::class, 'render_default_sort_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_order', __('Ordre du tri', 'wp-piwigo-display'), [self::class, 'render_default_order_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');
        add_settings_field('default_limit', __('Limite d’images', 'wp-piwigo-display'), [self::class, 'render_default_limit_field'], 'wp-piwigo-display', 'wp_piwigo_display_defaults');

        add_settings_section('wp_piwigo_display_advanced', __('Avancé', 'wp-piwigo-display'), [self::class, 'render_advanced_section'], 'wp-piwigo-display');
        add_settings_field('debug_mode', __('Mode debug', 'wp-piwigo-display'), [self::class, 'render_debug_mode_field'], 'wp-piwigo-display', 'wp_piwigo_display_advanced');
    }

    public static function register_page(): void
    {
        add_menu_page(
            __('WP Piwigo Display', 'wp-piwigo-display'),
            __('WP Piwigo', 'wp-piwigo-display'),
            'manage_options',
            'wp-piwigo-display',
            [self::class, 'render_dashboard_page'],
            'dashicons-format-gallery',
            58
        );

        add_submenu_page('wp-piwigo-display', __('Tableau de bord', 'wp-piwigo-display'), __('Tableau de bord', 'wp-piwigo-display'), 'manage_options', 'wp-piwigo-display', [self::class, 'render_dashboard_page']);
        add_submenu_page('wp-piwigo-display', __('Composer une galerie', 'wp-piwigo-display'), __('Composer', 'wp-piwigo-display'), 'manage_options', 'wp-piwigo-display-compose', [self::class, 'render_composer_page']);
        add_submenu_page('wp-piwigo-display', __('Réglages', 'wp-piwigo-display'), __('Réglages', 'wp-piwigo-display'), 'manage_options', 'wp-piwigo-display-settings', [self::class, 'render_page']);
    }

    public static function render_dashboard_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $url = self::get_piwigo_url();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Piwigo Display', 'wp-piwigo-display'); ?></h1>
            <p><?php esc_html_e('Accès rapide aux outils du plugin.', 'wp-piwigo-display'); ?></p>
            <div class="card" style="max-width:900px">
                <h2><?php esc_html_e('État', 'wp-piwigo-display'); ?></h2>
                <p><strong><?php esc_html_e('Galerie Piwigo :', 'wp-piwigo-display'); ?></strong> <?php echo $url !== '' ? esc_html($url) : esc_html__('non configurée', 'wp-piwigo-display'); ?></p>
                <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=wp-piwigo-display-compose')); ?>"><?php esc_html_e('Composer une galerie ou un diaporama', 'wp-piwigo-display'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wp-piwigo-display-settings')); ?>"><?php esc_html_e('Réglages', 'wp-piwigo-display'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wp-piwigo-display-diagnostic')); ?>"><?php esc_html_e('Diagnostic / debug', 'wp-piwigo-display'); ?></a></p>
            </div>
        </div>
        <?php
    }

    public static function render_composer_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Composer une galerie ou un diaporama', 'wp-piwigo-display'); ?></h1>
            <p><?php esc_html_e('Toutes les options prises en charge par le shortcode sont disponibles ci-dessous.', 'wp-piwigo-display'); ?></p>
            <div id="wpd-admin-composer" class="card" style="max-width:1050px">
                <table class="form-table"><tbody>
                    <tr><th><label for="wpd-c-album">Album</label></th><td><div class="wpd-album-field"><input id="wpd-c-album" class="regular-text" type="text" placeholder="154, nom ou chemin"><button type="button" class="button wpd-browse-albums">Choisir dans Piwigo</button><div class="wpd-album-picker" hidden></div></div></td></tr>
                    <tr><th><label for="wpd-c-type">Affichage</label></th><td><select id="wpd-c-type"><option value="gallery">Galerie</option><option value="slider">Diaporama</option></select></td></tr>
                    <tr><th><label for="wpd-c-preset">Preset</label></th><td><select id="wpd-c-preset"><option value="">Aucun</option><option value="slider">Slider</option><option value="actualites">Actualités</option></select></td></tr>
                    <tr><th><label for="wpd-c-sort">Tri</label></th><td><select id="wpd-c-sort"><option value="manual">Ordre Piwigo</option><option value="date">Date</option><option value="name">Nom</option><option value="id">Identifiant</option><option value="random">Aléatoire</option></select> <select id="wpd-c-order"><option value="desc">Décroissant</option><option value="asc">Croissant</option></select></td></tr>
                    <tr><th>Quantité</th><td><label>Limite <input id="wpd-c-limit" class="small-text" type="number" min="0" value="0"></label> <label>Maximum <input id="wpd-c-max" class="small-text" type="number" min="0" value="0"></label> <label>Dernières <input id="wpd-c-latest" class="small-text" type="number" min="0" value="0"></label> <label>Aléatoires <input id="wpd-c-random" class="small-text" type="number" min="0" value="0"></label></td></tr>
                    <tr><th><label for="wpd-c-orientation">Orientation</label></th><td><select id="wpd-c-orientation"><option value="">Toutes</option><option value="portrait">Portrait</option><option value="paysage">Paysage</option><option value="carré">Carré</option><option value="portrait,paysage">Portrait + paysage</option></select></td></tr>
                    <tr><th>Tags</th><td><label>Tag <input id="wpd-c-tag" type="text"></label> <label>Tags <input id="wpd-c-tags" type="text" placeholder="tag1,tag2"></label> <select id="wpd-c-tag-mode"><option value="any">Au moins un</option><option value="all">Tous</option></select></td></tr>
                    <tr><th>Présentation</th><td><select id="wpd-c-caption"><option value="default">Légende globale</option><option value="none">Sans légende</option><option value="title">Titre</option><option value="description">Description</option><option value="title-description">Titre + description</option></select> <select id="wpd-c-style"><option value="default">Style global</option><option value="theme">Thème WordPress</option><option value="minimal">Minimal</option><option value="none">Sans habillage</option></select> <select id="wpd-c-fit"><option value="contain">Image entière</option><option value="cover">Cadre rempli</option><option value="auto">Automatique</option><option value="raw">Brut</option></select> <label>Hauteur <input id="wpd-c-height" class="small-text" type="text" placeholder="520px"></label></td></tr>
                    <tr><th>Options générales</th><td><label><input id="wpd-c-recursive" type="checkbox"> Inclure les sous-albums</label> <label><input id="wpd-c-lightbox" type="checkbox" checked> Lightbox</label> <label><input id="wpd-c-rounded" type="checkbox"> Coins arrondis</label></td></tr>
                    <tr id="wpd-c-depth-row"><th><label for="wpd-c-depth">Profondeur des sous-albums</label></th><td><input id="wpd-c-depth" class="small-text" type="number" min="1" max="10" value="10"> <span class="description">1 = enfants directs uniquement, 10 = tous les niveaux.</span></td></tr>
                    <tr class="wpd-c-slider"><th>Diaporama</th><td><label><input id="wpd-c-autoplay" type="checkbox" checked> Lecture automatique</label> <label><input id="wpd-c-thumbnails" type="checkbox" checked> Miniatures (compatibilité)</label> <label>Intervalle <input id="wpd-c-interval" class="small-text" type="number" min="1000" value="5000"></label> <label>Vitesse <input id="wpd-c-speed" class="small-text" type="number" min="0" value="500"></label></td></tr>
                    <tr class="wpd-c-slider"><th>Format du diaporama</th><td><label>Ratio <input id="wpd-c-ratio" class="small-text" type="text" value="16/9"></label> <select id="wpd-c-navigation"><option value="thumbnails">Miniatures</option><option value="dots">Points</option><option value="none">Aucune navigation</option></select> <select id="wpd-c-width"><option value="100%">100 %</option><option value="75%">75 %</option><option value="66%">66 %</option><option value="50%">50 %</option><option value="33%">33 %</option></select> <select id="wpd-c-align"><option value="center">Centré</option><option value="left">À gauche, texte autour</option><option value="right">À droite, texte autour</option></select></td></tr>
                    <tr><th><label for="wpd-c-url">URL Piwigo spécifique</label></th><td><input id="wpd-c-url" class="regular-text" type="url" placeholder="https://phototheque.example.org"> <span class="description">Laissez vide pour utiliser le réglage global.</span></td></tr>
                    <tr><th><label for="wpd-c-output">Shortcode</label></th><td><textarea id="wpd-c-output" class="large-text code" rows="6" readonly></textarea><p><button type="button" class="button button-primary" id="wpd-c-copy">Copier le shortcode</button></p></td></tr>
                </tbody></table>
            </div>
            <h2><?php esc_html_e('Exemples', 'wp-piwigo-display'); ?></h2>
            <?php self::render_shortcode_examples(); ?>
        </div>
        <script>
        (function(){
            const q=id=>document.getElementById(id);
            const esc=value=>String(value).replace(/\\/g,'\\\\').replace(/"/g,'\\"');
            const add=(parts,key,value,allowZero=false)=>{if(value!=='' && (allowZero || value!=='0')) parts.push(key+'="'+esc(value)+'"');};
            function initAlbumPicker(){
                if(window.WPDAlbumPicker){
                    window.WPDAlbumPicker.attach(document.querySelector('#wpd-admin-composer .wpd-album-field'), q('wpd-c-album'));
                }
            }
            function updateVisibility(){
                const recursive=q('wpd-c-recursive').checked;
                q('wpd-c-depth').disabled=!recursive;
                q('wpd-c-depth-row').style.opacity=recursive?'1':'0.55';
                const slider=q('wpd-c-type').value==='slider';
                document.querySelectorAll('.wpd-c-slider').forEach(row=>row.style.display=slider?'table-row':'none');
            }
            function build(){
                updateVisibility();
                const parts=[];
                add(parts,'album',q('wpd-c-album').value.trim(),true);
                add(parts,'preset',q('wpd-c-preset').value,true);
                add(parts,'type',q('wpd-c-type').value,true);
                add(parts,'sort',q('wpd-c-sort').value,true);
                add(parts,'order',q('wpd-c-order').value,true);
                ['limit','max','latest','random'].forEach(k=>add(parts,k,q('wpd-c-'+k).value,false));
                add(parts,'orientation',q('wpd-c-orientation').value,true);
                add(parts,'tag',q('wpd-c-tag').value.trim(),true);
                add(parts,'tags',q('wpd-c-tags').value.trim(),true);
                add(parts,'tag_mode',q('wpd-c-tag-mode').value,true);
                add(parts,'caption',q('wpd-c-caption').value,true);
                add(parts,'style',q('wpd-c-style').value,true);
                add(parts,'fit',q('wpd-c-fit').value,true);
                add(parts,'height',q('wpd-c-height').value.trim(),true);
                parts.push('recursive="'+(q('wpd-c-recursive').checked?'true':'false')+'"');
                if(q('wpd-c-recursive').checked) add(parts,'depth',q('wpd-c-depth').value||'10',true);
                parts.push('lightbox="'+(q('wpd-c-lightbox').checked?'true':'false')+'"');
                parts.push('rounded="'+(q('wpd-c-rounded').checked?'true':'false')+'"');
                if(q('wpd-c-type').value==='slider'){
                    parts.push('autoplay="'+(q('wpd-c-autoplay').checked?'true':'false')+'"');
                    parts.push('thumbnails="'+(q('wpd-c-thumbnails').checked?'true':'false')+'"');
                    ['interval','speed','ratio','navigation','width','align'].forEach(k=>add(parts,k,q('wpd-c-'+k).value,true));
                }
                add(parts,'url',q('wpd-c-url').value.trim(),true);
                q('wpd-c-output').value='[piwigo '+parts.join(' ')+']';
            }
            document.querySelectorAll('#wpd-admin-composer input,#wpd-admin-composer select').forEach(el=>{el.addEventListener('input',build);el.addEventListener('change',build);});
            q('wpd-c-copy').addEventListener('click',()=>navigator.clipboard.writeText(q('wpd-c-output').value));
            document.addEventListener('DOMContentLoaded', initAlbumPicker, {once:true});
            window.addEventListener('load', initAlbumPicker, {once:true});
            initAlbumPicker();
            build();
        })();
        </script>
        <?php
    }

    public static function default_options(): array
    {
        return [
            'piwigo_url' => 'https://phototheque.pelemele.org/',
            'cache_duration' => 3600,
            'default_type' => 'gallery',
            'default_fit' => 'contain',
            'default_ratio' => '16/9',
            'default_height' => '',
            'default_autoplay' => 'true',
            'default_interval' => 5000,
            'default_speed' => 500,
            'default_lightbox' => 'true',
            'default_caption' => 'title',
            'default_style' => 'theme',
            'default_navigation' => 'thumbnails',
            'default_rounded' => 'false',
            'default_sort' => 'manual',
            'default_order' => 'desc',
            'default_limit' => 0,
            'debug_mode' => 'false',
        ];
    }

    public static function get_options(): array
    {
        $options = get_option(self::OPTION_NAME, []);
        return wp_parse_args(is_array($options) ? $options : [], self::default_options());
    }

    public static function get_shortcode_defaults(): array
    {
        $options = self::get_options();
        return [
            'type' => (string) $options['default_type'],
            'autoplay' => (string) $options['default_autoplay'],
            'interval' => (string) $options['default_interval'],
            'speed' => (string) $options['default_speed'],
            'fit' => (string) $options['default_fit'],
            'height' => (string) $options['default_height'],
            'ratio' => (string) $options['default_ratio'],
            'rounded' => (string) $options['default_rounded'],
            'lightbox' => (string) $options['default_lightbox'],
            'caption' => (string) $options['default_caption'],
            'style' => (string) $options['default_style'],
            'navigation' => (string) $options['default_navigation'],
            'thumbnails' => ((string) $options['default_navigation'] === 'thumbnails') ? 'true' : 'false',
            'sort' => (string) $options['default_sort'],
            'order' => (string) $options['default_order'],
            'limit' => (string) $options['default_limit'],
        ];
    }

    public static function get_piwigo_url(): string
    {
        $options = self::get_options();
        return untrailingslashit((string) $options['piwigo_url']);
    }

    public static function get_cache_duration(): int
    {
        $options = self::get_options();
        return max(60, absint($options['cache_duration']));
    }

    public static function get_default_caption(): string
    {
        $options = self::get_options();
        return self::sanitize_choice(
            (string) ($options['default_caption'] ?? 'title'),
            ['none', 'title', 'description', 'title-description'],
            'title'
        );
    }

    public static function get_debug_mode(): bool
    {
        $options = self::get_options();
        return filter_var($options['debug_mode'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    }

    public static function sanitize_options($options): array
    {
        $options = is_array($options) ? $options : [];
        $sanitized = self::default_options();
        if (isset($options['piwigo_url'])) {
            $url = esc_url_raw(trim((string) $options['piwigo_url']));
            $scheme = $url !== '' ? wp_parse_url($url, PHP_URL_SCHEME) : '';
            $sanitized['piwigo_url'] = in_array($scheme, ['http', 'https'], true) ? trailingslashit($url) : '';
        }
        if (isset($options['cache_duration'])) {
            $sanitized['cache_duration'] = max(60, absint($options['cache_duration']));
        }
        $sanitized['default_type'] = self::sanitize_choice($options['default_type'] ?? 'gallery', ['gallery', 'slider'], 'gallery');
        $sanitized['default_fit'] = self::sanitize_choice($options['default_fit'] ?? 'contain', ['cover', 'contain', 'auto', 'raw'], 'contain');
        $sanitized['default_ratio'] = self::sanitize_ratio((string) ($options['default_ratio'] ?? '16/9'));
        $sanitized['default_height'] = self::sanitize_height((string) ($options['default_height'] ?? ''));
        $sanitized['default_autoplay'] = self::sanitize_bool($options['default_autoplay'] ?? 'true');
        $sanitized['default_interval'] = max(1000, absint($options['default_interval'] ?? 5000));
        $sanitized['default_speed'] = max(0, absint($options['default_speed'] ?? 500));
        $sanitized['default_lightbox'] = self::sanitize_bool($options['default_lightbox'] ?? 'true');
        $sanitized['default_caption'] = self::sanitize_choice(
            (string) ($options['default_caption'] ?? 'title'),
            ['none', 'title', 'description', 'title-description'],
            'title'
        );
        $sanitized['default_style'] = self::sanitize_choice(
            (string) ($options['default_style'] ?? 'theme'),
            ['default', 'theme', 'minimal', 'none'],
            'theme'
        );
        $sanitized['default_navigation'] = self::sanitize_choice($options['default_navigation'] ?? 'thumbnails', ['thumbnails', 'dots', 'none'], 'thumbnails');
        $sanitized['default_rounded'] = self::sanitize_bool($options['default_rounded'] ?? 'false');
        $sanitized['default_sort'] = self::sanitize_choice($options['default_sort'] ?? 'manual', ['manual', 'date', 'name', 'id', 'random'], 'manual');
        $sanitized['default_order'] = self::sanitize_choice($options['default_order'] ?? 'desc', ['asc', 'desc'], 'desc');
        $sanitized['default_limit'] = absint($options['default_limit'] ?? 0);
        $sanitized['debug_mode'] = self::sanitize_bool($options['debug_mode'] ?? 'false');
        return $sanitized;
    }

    public static function render_defaults_section(): void
    {
        echo '<p>' . esc_html__('Ces valeurs sont utilisées par défaut. Un shortcode peut les remplacer.', 'wp-piwigo-display') . '</p>';
    }

    public static function render_advanced_section(): void
    {
        echo '<p>' . esc_html__('Options utiles pour tester ou diagnostiquer le plugin.', 'wp-piwigo-display') . '</p>';
    }

    public static function render_piwigo_url_field(): void
    {
        $o = self::get_options();
        self::input('url', 'piwigo_url', (string) $o['piwigo_url'], 'regular-text', 'https://phototheque.example.org/');
    }

    public static function render_cache_duration_field(): void
    {
        $o = self::get_options();
        self::input('number', 'cache_duration', (string) $o['cache_duration'], 'small-text', '', ['min' => '60', 'step' => '60']);
        echo ' <span>' . esc_html__('secondes', 'wp-piwigo-display') . '</span>';
    }

    public static function render_default_type_field(): void { self::select('default_type', ['gallery' => 'Galerie', 'slider' => 'Diaporama']); }
    public static function render_default_fit_field(): void { self::select('default_fit', ['contain' => 'Image entière', 'cover' => 'Cadre rempli', 'auto' => 'Automatique', 'raw' => 'Brut']); }
    public static function render_default_ratio_field(): void { $o = self::get_options(); self::input('text', 'default_ratio', (string) $o['default_ratio'], 'small-text', '16/9'); }
    public static function render_default_height_field(): void { $o = self::get_options(); self::input('text', 'default_height', (string) $o['default_height'], 'small-text', 'ex : 520px'); }
    public static function render_default_autoplay_field(): void { self::select_bool('default_autoplay'); }
    public static function render_default_lightbox_field(): void { self::select_bool('default_lightbox'); }
    public static function render_default_style_field(): void
    {
        self::select('default_style', [
            'theme' => 'Thème WordPress',
            'default' => 'Style standard du plugin',
            'minimal' => 'Minimal',
            'none' => 'Sans habillage graphique',
        ]);
        echo '<p class="description">' . esc_html__('Le mode Thème WordPress utilise les variables CSS du thème lorsqu’elles sont disponibles.', 'wp-piwigo-display') . '</p>';
    }

    public static function render_default_caption_field(): void
    {
        self::select('default_caption', [
            'none' => 'Aucune',
            'title' => 'Titre',
            'description' => 'Description',
            'title-description' => 'Titre et description',
        ]);
        echo '<p class="description">' . esc_html__('Ce choix peut être remplacé dans chaque shortcode avec le paramètre caption.', 'wp-piwigo-display') . '</p>';
    }
    public static function render_default_navigation_field(): void { self::select('default_navigation', ['thumbnails' => 'Miniatures', 'dots' => 'Points', 'none' => 'Aucune']); }
    public static function render_default_rounded_field(): void { self::select_bool('default_rounded'); }
    public static function render_default_sort_field(): void { self::select('default_sort', ['manual' => 'Ordre Piwigo', 'date' => 'Date', 'name' => 'Nom', 'id' => 'Identifiant', 'random' => 'Aléatoire']); }
    public static function render_default_order_field(): void { self::select('default_order', ['asc' => 'Croissant', 'desc' => 'Décroissant']); }
    public static function render_default_limit_field(): void { $o = self::get_options(); self::input('number', 'default_limit', (string) $o['default_limit'], 'small-text', '', ['min' => '0', 'step' => '1']); echo ' <span>' . esc_html__('0 = aucune limite', 'wp-piwigo-display') . '</span>'; }
    public static function render_debug_mode_field(): void { self::select_bool('debug_mode'); echo '<p class="description">' . esc_html__('Affiche un résumé technique sous les galeries pour les administrateurs connectés.', 'wp-piwigo-display') . '</p>'; }

    public static function render_default_interval_field(): void
    {
        $o = self::get_options();
        self::input('number', 'default_interval', (string) $o['default_interval'], 'small-text', '', ['min' => '1000', 'step' => '500']);
        echo ' <span>ms</span>';
    }

    public static function render_default_speed_field(): void
    {
        $o = self::get_options();
        self::input('number', 'default_speed', (string) $o['default_speed'], 'small-text', '', ['min' => '0', 'step' => '100']);
        echo ' <span>ms</span>';
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Piwigo Display', 'wp-piwigo-display'); ?></h1>
            <p><?php esc_html_e('Réglages de connexion, d’affichage, de diaporama, de cache et de diagnostic.', 'wp-piwigo-display'); ?></p>

            <?php if (isset($_GET['wpd_cache_cleared'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        printf(
                            esc_html__('Cache vidé. %d entrée(s) supprimée(s).', 'wp-piwigo-display'),
                            absint(wp_unslash($_GET['wpd_cache_cleared']))
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['wpd_connection_test'])) : ?>
                <?php $connection_result = sanitize_key((string) wp_unslash($_GET['wpd_connection_test'])); ?>
                <div class="notice <?php echo $connection_result === 'success' ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                    <p>
                        <?php
                        if ($connection_result === 'success') {
                            esc_html_e('Connexion Piwigo réussie.', 'wp-piwigo-display');
                        } elseif ($connection_result === 'missing_url') {
                            esc_html_e('Impossible de tester la connexion : URL Piwigo manquante.', 'wp-piwigo-display');
                        } else {
                            esc_html_e('L’API Piwigo n’a pas répondu correctement.', 'wp-piwigo-display');
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('wp_piwigo_display'); do_settings_sections('wp-piwigo-display'); submit_button(__('Enregistrer les réglages', 'wp-piwigo-display')); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Diagnostic', 'wp-piwigo-display'); ?></h2>
            <p><?php esc_html_e('Vérifiez rapidement que WordPress peut joindre l’API de votre galerie Piwigo.', 'wp-piwigo-display'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wpd_test_connection" />
                <?php wp_nonce_field('wpd_test_connection'); ?>
                <?php submit_button(__('Tester la connexion Piwigo', 'wp-piwigo-display'), 'secondary'); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Bloc Gutenberg', 'wp-piwigo-display'); ?></h2>
            <p><?php esc_html_e('Le bloc « WP Piwigo Display » est disponible dans la catégorie Médias de l’éditeur Gutenberg. Renseignez l’identifiant de l’album pour créer une galerie sans écrire de shortcode.', 'wp-piwigo-display'); ?></p>
            <p><code>WP Piwigo Display → album 154</code></p>
            <p><?php esc_html_e('Dans l’éditeur classique, le shortcode [piwigo album="154"] reste disponible.', 'wp-piwigo-display'); ?></p>

            <hr />

            <h2><?php esc_html_e('Générateur de shortcodes', 'wp-piwigo-display'); ?></h2>
            <p><?php esc_html_e('Copiez un exemple puis remplacez l’identifiant d’album par celui de votre galerie Piwigo.', 'wp-piwigo-display'); ?></p>
            <?php self::render_shortcode_examples(); ?>

            <hr />

            <h2><?php esc_html_e('Cache', 'wp-piwigo-display'); ?></h2>
            <p><?php esc_html_e('Vous pouvez vider le cache si vous venez de modifier des albums ou d’ajouter des photos dans Piwigo.', 'wp-piwigo-display'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wpd_clear_cache" />
                <?php wp_nonce_field('wpd_clear_cache'); ?>
                <?php submit_button(__('Vider le cache', 'wp-piwigo-display'), 'secondary'); ?>
            </form>
        </div>
        <?php
    }


    private static function render_shortcode_examples(): void
    {
        $examples = [
            __('Galerie simple', 'wp-piwigo-display') => '[piwigo album="154"]',
            __('Slider', 'wp-piwigo-display') => '[piwigo album="154" type="slider"]',
            __('Album récursif', 'wp-piwigo-display') => '[piwigo album="154" recursive="true"]',
            __('Profondeur limitée', 'wp-piwigo-display') => '[piwigo album="154" recursive="true" depth="2"]',
            __('Tri par date décroissante', 'wp-piwigo-display') => '[piwigo album="154" sort="date" order="desc"]',
            __('Limitation du nombre d’images', 'wp-piwigo-display') => '[piwigo album="154" limit="20"]',
            __('Preset actualités', 'wp-piwigo-display') => '[piwigo album="154" preset="actualites"]',
            __('Style', 'wp-piwigo-display') => '[piwigo album="154" style="minimal"]',
            __('Caption', 'wp-piwigo-display') => '[piwigo album="154" caption="title-description"]',
            __('Lightbox', 'wp-piwigo-display') => '[piwigo album="154" lightbox="true"]',
            __('Orientation portrait', 'wp-piwigo-display') => '[piwigo album="154" orientation="portrait"]',
            __('Orientation paysage', 'wp-piwigo-display') => '[piwigo album="154" orientation="paysage"]',
            __('Orientation carrée', 'wp-piwigo-display') => '[piwigo album="154" orientation="carré"]',
            __('Tag unique', 'wp-piwigo-display') => '[piwigo album="154" tag="nature"]',
            __('Plusieurs tags', 'wp-piwigo-display') => '[piwigo album="154" tags="nature,animaux"]',
            __('Tous les tags', 'wp-piwigo-display') => '[piwigo album="154" tags="nature,animaux" tag_mode="all"]',
            __('Max', 'wp-piwigo-display') => '[piwigo album="154" max="12"]',
            __('Latest', 'wp-piwigo-display') => '[piwigo album="154" latest="12"]',
            __('Random', 'wp-piwigo-display') => '[piwigo album="154" random="12"]',
        ];

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Usage', 'wp-piwigo-display') . '</th><th>' . esc_html__('Shortcode', 'wp-piwigo-display') . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($examples as $label => $shortcode) {
            echo '<tr>';
            echo '<td>' . esc_html($label) . '</td>';
            echo '<td><code>' . esc_html($shortcode) . '</code></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    private static function input(string $type, string $key, string $value, string $class = '', string $placeholder = '', array $attrs = []): void
    {
        $attr_html = '';
        foreach ($attrs as $name => $attr_value) {
            $attr_html .= ' ' . esc_attr($name) . '="' . esc_attr($attr_value) . '"';
        }
        printf('<input type="%1$s" name="%2$s[%3$s]" value="%4$s" class="%5$s" placeholder="%6$s"%7$s />', esc_attr($type), esc_attr(self::OPTION_NAME), esc_attr($key), esc_attr($value), esc_attr($class), esc_attr($placeholder), $attr_html);
    }

    private static function select(string $key, array $choices): void
    {
        $options = self::get_options();
        $current = (string) ($options[$key] ?? '');
        echo '<select name="' . esc_attr(self::OPTION_NAME) . '[' . esc_attr($key) . ']">';
        foreach ($choices as $value => $label) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($value), selected($current, (string) $value, false), esc_html($label));
        }
        echo '</select>';
    }

    private static function select_bool(string $key): void
    {
        self::select($key, ['true' => 'Oui', 'false' => 'Non']);
    }

    private static function sanitize_choice(string $value, array $allowed, string $default): string { return in_array($value, $allowed, true) ? $value : $default; }
    private static function sanitize_bool($value): string { return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'; }
    private static function sanitize_ratio(string $ratio): string { return preg_match('/^\d+\/\d+$/', $ratio) === 1 ? $ratio : '16/9'; }
    private static function sanitize_height(string $height): string { $height = trim($height); return preg_match('/^\d+(px|rem|em|vh|vw|%)$/', $height) === 1 ? $height : ''; }
}
