<?php
/**
 * User-font security and editor parity regression checks.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root      = dirname(__DIR__);
$bootstrap = file_get_contents($root . '/wp-piwigo-display.php');
$library   = file_get_contents($root . '/includes/class-wpd-user-fonts.php');
$photo     = file_get_contents($root . '/includes/class-wpd-photo-text.php');
$gutenberg = file_get_contents($root . '/blocks/piwigo/masonry-controls.js');
$ui        = file_get_contents($root . '/assets/js/wp-piwigo-display-user-fonts-ui.js');
$css       = file_get_contents($root . '/assets/css/wp-piwigo-display-user-fonts.css');

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
};

$assert(false !== strpos((string) $bootstrap, 'class-wpd-user-fonts.php'), 'La bibliothèque de polices doit être chargée.');
$assert(false !== strpos((string) $bootstrap, 'WPD_User_Fonts::register()'), 'La bibliothèque de polices doit être enregistrée.');
$assert(false !== strpos((string) $library, "current_user_can( 'manage_options' )"), 'Import et suppression doivent vérifier la capacité administrateur.');
$assert(false !== strpos((string) $library, "check_admin_referer( 'wpd_upload_user_font' )"), 'L’import doit vérifier un nonce.');
$assert(false !== strpos((string) $library, "check_admin_referer( 'wpd_delete_user_font' )"), 'La suppression doit vérifier un nonce.');
$assert(false !== strpos((string) $library, 'is_uploaded_file( $tmp_name )'), 'Le fichier temporaire doit provenir d’un upload HTTP valide.');
$assert(false !== strpos((string) $library, "'wOF2'") && false !== strpos((string) $library, "'wOFF'"), 'Les signatures WOFF2/WOFF doivent être vérifiées.');
$assert(false !== strpos((string) $library, 'finfo_open'), 'Le MIME doit être vérifié quand Fileinfo est disponible.');
$assert(false !== strpos((string) $library, 'wp_handle_upload('), 'Le déplacement doit passer par l’API WordPress.');
$assert(false !== strpos((string) $library, "UPLOAD_SUBDIR = '/piwigo-display-fonts'"), 'Les polices doivent rester dans un répertoire uploads dédié.');
$assert(false !== strpos((string) $library, 'wp_piwigo_display_user_font_max_bytes'), 'La taille maximale doit être configurable par filtre.');
$assert(false !== strpos((string) $library, 'wp_delete_file('), 'La suppression doit passer par l’API WordPress.');
$assert(false !== strpos((string) $library, 'update_option( self::OPTION_NAME, $library, false )'), 'Les métadonnées ne doivent pas être autoloadées.');
$assert(false !== strpos((string) $photo, 'WPD_User_Fonts::font_stack'), 'Le rendu Texte-photo doit résoudre les polices locales via la bibliothèque.');
$assert(false !== strpos((string) $gutenberg, 'window.WPDUserFonts'), 'Gutenberg doit proposer les polices utilisateur.');
$assert(false !== strpos((string) $ui, '[data-wpd="photo_text_font"]') && false !== strpos((string) $ui, '#wpd-c-photo-text-font'), 'Classic Editor et le composeur doivent proposer les polices utilisateur.');
$assert(false !== strpos((string) $css, '.wpd-user-font-preview'), 'La bibliothèque doit fournir un aperçu visuel.');
$assert(array() === (glob($root . '/**/*.woff2') ?: array()), 'Aucune police WOFF2 utilisateur ne doit être redistribuée avec le plugin.');
$assert(array() === (glob($root . '/**/*.woff') ?: array()), 'Aucune police WOFF utilisateur ne doit être redistribuée avec le plugin.');

fwrite(STDOUT, "Local user font pipeline: OK\n");
