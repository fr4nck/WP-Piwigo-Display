<?php
/**
 * Classic Editor integration.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Classic Editor builder and TinyMCE preview integration.
 */
final class WPD_Classic_Editor {
	/** Registers Classic Editor hooks. */
	public static function register(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'media_buttons', array( self::class, 'render_button' ), 20 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'admin_footer-post.php', array( self::class, 'render_modal' ) );
		add_action( 'admin_footer-post-new.php', array( self::class, 'render_modal' ) );
		add_filter( 'mce_external_plugins', array( self::class, 'register_tinymce_plugin' ) );
	}

	/**
	 * Registers the TinyMCE shortcode preview plugin.
	 *
	 * @param array<string,string> $plugins Registered TinyMCE plugins.
	 * @return array<string,string>
	 */
	public static function register_tinymce_plugin( array $plugins ): array {
		$plugins['wpd_shortcode_preview'] = WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-tinymce.js';
		return $plugins;
	}

	/**
	 * Enqueues Classic Editor assets on post editing screens.
	 *
	 * @param string $hook Current administration screen hook.
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wpd-classic-editor', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-classic-editor.css', array(), WPD_VERSION );
		wp_enqueue_style( 'wpd-shape-picker', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-shape-picker.css', array(), WPD_VERSION );
		wp_enqueue_script( 'wpd-classic-editor', WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-classic-editor.js', array( 'jquery', 'jquery-ui-dialog' ), WPD_VERSION, true );
	}

	/**
	 * Renders the Piwigo gallery button in Classic Editor.
	 *
	 * @param string $editor_id Target editor identifier.
	 */
	public static function render_button( string $editor_id = 'content' ): void {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		printf( '<button type="button" class="button wpd-open-builder" data-editor="%1$s"><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span> %2$s</button>', esc_attr( $editor_id ), esc_html__( 'Insérer une galerie Piwigo', 'wp-piwigo-display' ) );
	}

	/** Renders the Classic Editor shortcode builder modal. */
	public static function render_modal(): void {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		?>
		<div id="wpd-classic-builder" title="<?php echo esc_attr__( 'Galerie Piwigo', 'wp-piwigo-display' ); ?>" style="display:none;">
			<div class="wpd-builder-grid">
				<div class="wpd-album-field"><label><?php esc_html_e( 'Album Piwigo', 'wp-piwigo-display' ); ?><input type="text" data-wpd="album" placeholder="154, nom ou chemin"></label><button type="button" class="button wpd-browse-albums"><?php esc_html_e( 'Choisir dans Piwigo', 'wp-piwigo-display' ); ?></button><div class="wpd-album-picker" hidden></div></div>
				<label><?php esc_html_e( 'Affichage', 'wp-piwigo-display' ); ?><select data-wpd="type"><option value="gallery">Galerie</option><option value="slider">Diaporama</option><option value="masonry">Masonry</option><option value="justified">Galerie justifiée</option></select></label>
				<label><?php esc_html_e( 'Preset', 'wp-piwigo-display' ); ?><select data-wpd="preset"><option value="">Aucun</option><option value="slider">Slider</option><option value="actualites">Actualités</option></select></label>
				<label><?php esc_html_e( 'Tri', 'wp-piwigo-display' ); ?><select data-wpd="sort"><option value="manual">Ordre Piwigo</option><option value="date">Date</option><option value="name">Nom</option><option value="id">Identifiant</option><option value="random">Aléatoire</option></select></label>
				<label><?php esc_html_e( 'Ordre', 'wp-piwigo-display' ); ?><select data-wpd="order"><option value="desc">Décroissant</option><option value="asc">Croissant</option></select></label>
				<label><?php esc_html_e( 'Limite', 'wp-piwigo-display' ); ?><input type="number" min="0" data-wpd="limit" value="0"></label>
				<label><?php esc_html_e( 'Maximum', 'wp-piwigo-display' ); ?><input type="number" min="0" data-wpd="max" value="0"></label>
				<label><?php esc_html_e( 'Dernières images', 'wp-piwigo-display' ); ?><input type="number" min="0" data-wpd="latest" value="0"></label>
				<label><?php esc_html_e( 'Images aléatoires', 'wp-piwigo-display' ); ?><input type="number" min="0" data-wpd="random" value="0"></label>
				<label><?php esc_html_e( 'Orientation', 'wp-piwigo-display' ); ?><select data-wpd="orientation"><option value="">Toutes</option><option value="portrait">Portrait</option><option value="paysage">Paysage</option><option value="carré">Carré</option><option value="portrait,paysage">Portrait + paysage</option></select></label>
				<label><?php esc_html_e( 'Légende', 'wp-piwigo-display' ); ?><select data-wpd="caption"><option value="default">Réglage global</option><option value="none">Aucune</option><option value="title">Titre</option><option value="description">Description</option><option value="title-description">Titre et description</option></select></label>
				<label><?php esc_html_e( 'Style', 'wp-piwigo-display' ); ?><select data-wpd="style"><option value="default">Réglage global</option><option value="theme">Thème WordPress</option><option value="minimal">Minimal</option><option value="none">Sans habillage</option></select></label>
				<label><?php esc_html_e( 'Cadrage', 'wp-piwigo-display' ); ?><select data-wpd="fit"><option value="contain">Image entière</option><option value="cover">Cadre rempli</option><option value="auto">Automatique</option><option value="raw">Brut</option></select></label>
				<div class="wpd-shape-field"><label><?php esc_html_e( 'Forme', 'wp-piwigo-display' ); ?><select data-wpd="shape"><option value="rectangle">Rectangle</option><option value="rounded">Rectangle arrondi</option><option value="circle">Cercle</option><option value="oval">Ovale</option><option value="pill">Pilule</option><option value="star">Étoile</option><option value="hexagon">Hexagone</option><option value="diamond">Losange</option><option value="cloud">Nuage</option><option value="heart">Cœur</option><option value="drop">Goutte</option><option value="triangle">Triangle</option><option value="pentagon">Pentagone</option><option value="octagon">Octogone</option><option value="card-spade">Carte — Pique ♠</option><option value="card-heart">Carte — Cœur ♥</option><option value="card-diamond">Carte — Carreau ♦</option><option value="card-club">Carte — Trèfle ♣</option></select></label><div class="wpd-shape-picker-grid" data-wpd-shape-picker></div></div>
				<label class="wpd-radius-option"><?php esc_html_e( 'Arrondi (%)', 'wp-piwigo-display' ); ?><input type="number" min="0" max="50" data-wpd="radius" value="8"></label>
				<label><?php esc_html_e( 'Hauteur (px)', 'wp-piwigo-display' ); ?><input type="number" min="160" max="1200" data-wpd="height" placeholder="520"></label>
				<label><?php esc_html_e( 'Tag unique', 'wp-piwigo-display' ); ?><input type="text" data-wpd="tag"></label>
				<label><?php esc_html_e( 'Plusieurs tags', 'wp-piwigo-display' ); ?><input type="text" data-wpd="tags" placeholder="tag1,tag2"></label>
				<label><?php esc_html_e( 'Correspondance des tags', 'wp-piwigo-display' ); ?><select data-wpd="tag_mode"><option value="any">Au moins un</option><option value="all">Tous</option></select></label>
				<label><?php esc_html_e( 'URL Piwigo spécifique', 'wp-piwigo-display' ); ?><input type="url" data-wpd="url" placeholder="https://phototheque.example.org"></label>
				<label class="wpd-slider-layout-option"><?php esc_html_e( 'Largeur du diaporama (%)', 'wp-piwigo-display' ); ?><input type="number" min="20" max="100" data-wpd="width" value="100"></label>
				<label class="wpd-slider-layout-option"><?php esc_html_e( 'Alignement', 'wp-piwigo-display' ); ?><select data-wpd="align"><option value="center">Centré</option><option value="left">À gauche, texte à droite</option><option value="right">À droite, texte à gauche</option></select></label>
				<label class="wpd-masonry-options"><?php esc_html_e( 'Colonnes Masonry', 'wp-piwigo-display' ); ?><input type="number" min="2" max="6" data-wpd="masonry_columns" value="4"></label>
				<label class="wpd-masonry-options"><?php esc_html_e( 'Espacement Masonry (px)', 'wp-piwigo-display' ); ?><input type="number" min="0" max="64" data-wpd="masonry_gap" value="16"></label>
				<label class="wpd-justified-options"><?php esc_html_e( 'Hauteur cible des lignes (px)', 'wp-piwigo-display' ); ?><input type="number" min="100" max="600" data-wpd="justified_row_height" value="220"></label>
				<label class="wpd-justified-options"><?php esc_html_e( 'Espacement galerie justifiée (px)', 'wp-piwigo-display' ); ?><input type="number" min="0" max="64" data-wpd="justified_gap" value="8"></label>
			</div>
			<fieldset class="wpd-builder-checks">
				<label><input type="checkbox" data-wpd="recursive"> <?php esc_html_e( 'Inclure les sous-albums', 'wp-piwigo-display' ); ?></label>
				<label class="wpd-depth-option"><?php esc_html_e( 'Profondeur des sous-albums', 'wp-piwigo-display' ); ?> <input type="number" min="1" max="10" value="10" data-wpd="depth" class="small-text"></label>
				<label><input type="checkbox" data-wpd="lightbox" checked> <?php esc_html_e( 'Lightbox', 'wp-piwigo-display' ); ?></label>
				<label><input type="checkbox" data-wpd="rounded"> <?php esc_html_e( 'Coins arrondis (compatibilité)', 'wp-piwigo-display' ); ?></label>
				<label class="wpd-slider-options"><input type="checkbox" data-wpd="transparent_background"> <?php esc_html_e( 'Fond transparent', 'wp-piwigo-display' ); ?></label>
				<label class="wpd-slider-options"><input type="checkbox" data-wpd="autoplay" checked> <?php esc_html_e( 'Lecture automatique du diaporama', 'wp-piwigo-display' ); ?></label>
				<label class="wpd-slider-options"><input type="checkbox" data-wpd="thumbnails" checked> <?php esc_html_e( 'Miniatures (compatibilité)', 'wp-piwigo-display' ); ?></label>
			</fieldset>
			<div class="wpd-slider-options">
				<label><?php esc_html_e( 'Durée d’affichage (ms)', 'wp-piwigo-display' ); ?><input type="number" min="1000" data-wpd="interval" value="5000"></label>
				<label><?php esc_html_e( 'Vitesse de transition (ms)', 'wp-piwigo-display' ); ?><input type="number" min="0" data-wpd="speed" value="500"></label>
				<label><?php esc_html_e( 'Effet de transition', 'wp-piwigo-display' ); ?><select data-wpd="transition"><option value="slide">Glissement</option><option value="fade">Fondu</option><option value="none">Sans animation</option></select></label>
				<label><?php esc_html_e( 'Direction', 'wp-piwigo-display' ); ?><select data-wpd="direction"><option value="ltr">Vers la gauche</option><option value="rtl">Vers la droite</option></select></label>
				<label><?php esc_html_e( 'Ratio', 'wp-piwigo-display' ); ?><input type="text" data-wpd="ratio" value="16/9"></label>
				<label><?php esc_html_e( 'Navigation', 'wp-piwigo-display' ); ?><select data-wpd="navigation"><option value="thumbnails">Miniatures</option><option value="dots">Points</option><option value="none">Aucune</option></select></label>
			</div>
			<label class="wpd-shortcode-preview"><?php esc_html_e( 'Shortcode généré', 'wp-piwigo-display' ); ?><textarea readonly rows="4" data-wpd-preview></textarea></label>
		</div>
		<?php
	}
}
