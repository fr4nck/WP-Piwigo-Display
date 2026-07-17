<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WPD_Classic_Editor
{
    public static function register(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('media_buttons', [self::class, 'render_button'], 20);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_footer-post.php', [self::class, 'render_modal']);
        add_action('admin_footer-post-new.php', [self::class, 'render_modal']);
    }

    public static function enqueue_assets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style(
            'wpd-classic-editor',
            WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-classic-editor.css',
            [],
            WPD_VERSION
        );
        wp_enqueue_script(
            'wpd-classic-editor',
            WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-classic-editor.js',
            ['jquery', 'jquery-ui-dialog'],
            WPD_VERSION,
            true
        );
    }

    public static function render_button(string $editor_id = 'content'): void
    {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        printf(
            '<button type="button" class="button wpd-open-builder" data-editor="%1$s"><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span> %2$s</button>',
            esc_attr($editor_id),
            esc_html__('Insérer une galerie Piwigo', 'wp-piwigo-display')
        );
    }

    public static function render_modal(): void
    {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }
        ?>
        <div id="wpd-classic-builder" title="<?php echo esc_attr__('Galerie Piwigo', 'wp-piwigo-display'); ?>" style="display:none;">
            <div class="wpd-builder-grid">
                <label><?php esc_html_e('Album Piwigo', 'wp-piwigo-display'); ?><input type="number" min="1" data-wpd="album"></label>
                <label><?php esc_html_e('Affichage', 'wp-piwigo-display'); ?><select data-wpd="type"><option value="gallery">Galerie</option><option value="slider">Diaporama</option></select></label>
                <label><?php esc_html_e('Tri', 'wp-piwigo-display'); ?><select data-wpd="sort"><option value="manual">Ordre Piwigo</option><option value="date">Date</option><option value="name">Nom</option><option value="id">Identifiant</option><option value="random">Aléatoire</option></select></label>
                <label><?php esc_html_e('Ordre', 'wp-piwigo-display'); ?><select data-wpd="order"><option value="desc">Décroissant</option><option value="asc">Croissant</option></select></label>
                <label><?php esc_html_e('Limite', 'wp-piwigo-display'); ?><input type="number" min="0" data-wpd="limit" value="0"></label>
                <label><?php esc_html_e('Maximum', 'wp-piwigo-display'); ?><input type="number" min="0" data-wpd="max" value="0"></label>
                <label><?php esc_html_e('Orientation', 'wp-piwigo-display'); ?><select data-wpd="orientation"><option value="">Toutes</option><option value="portrait">Portrait</option><option value="paysage">Paysage</option><option value="carré">Carré</option><option value="portrait,paysage">Portrait + paysage</option></select></label>
                <label><?php esc_html_e('Légende', 'wp-piwigo-display'); ?><select data-wpd="caption"><option value="default">Réglage global</option><option value="none">Aucune</option><option value="title">Titre</option><option value="description">Description</option><option value="title-description">Titre et description</option></select></label>
                <label><?php esc_html_e('Style', 'wp-piwigo-display'); ?><select data-wpd="style"><option value="default">Réglage global</option><option value="theme">Thème WordPress</option><option value="minimal">Minimal</option><option value="none">Sans habillage</option></select></label>
                <label><?php esc_html_e('Tags', 'wp-piwigo-display'); ?><input type="text" data-wpd="tags" placeholder="tag1,tag2"></label>
            </div>
            <fieldset class="wpd-builder-checks">
                <label><input type="checkbox" data-wpd="recursive"> <?php esc_html_e('Inclure les sous-albums', 'wp-piwigo-display'); ?></label>
                <label><input type="checkbox" data-wpd="lightbox" checked> <?php esc_html_e('Lightbox', 'wp-piwigo-display'); ?></label>
                <label><input type="checkbox" data-wpd="rounded"> <?php esc_html_e('Coins arrondis', 'wp-piwigo-display'); ?></label>
                <label><input type="checkbox" data-wpd="autoplay" checked> <?php esc_html_e('Lecture automatique du diaporama', 'wp-piwigo-display'); ?></label>
            </fieldset>
            <div class="wpd-slider-options">
                <label><?php esc_html_e('Intervalle (ms)', 'wp-piwigo-display'); ?><input type="number" min="1000" data-wpd="interval" value="5000"></label>
                <label><?php esc_html_e('Vitesse (ms)', 'wp-piwigo-display'); ?><input type="number" min="0" data-wpd="speed" value="500"></label>
                <label><?php esc_html_e('Ratio', 'wp-piwigo-display'); ?><input type="text" data-wpd="ratio" value="16/9"></label>
                <label><?php esc_html_e('Navigation', 'wp-piwigo-display'); ?><select data-wpd="navigation"><option value="thumbnails">Miniatures</option><option value="dots">Points</option><option value="none">Aucune</option></select></label>
            </div>
            <label class="wpd-shortcode-preview"><?php esc_html_e('Shortcode généré', 'wp-piwigo-display'); ?><textarea readonly rows="3" data-wpd-preview></textarea></label>
        </div>
        <?php
    }
}
