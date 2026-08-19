<?php
/**
 * Local and bundled font security/editor parity regression checks.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root      = dirname(__DIR__);
$bootstrap = file_get_contents($root . '/wp-piwigo-display.php');
$library   = file_get_contents($root . '/includes/class-wpd-user-fonts.php');
$bundled   = file_get_contents($root . '/includes/class-wpd-bundled-fonts.php');
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

$assert(false !== strpos((string) $bootstrap, 'class-wpd-user-fonts.php'), 'La bibliothèque de polices utilisateur doit être chargée.');
$assert(false !== strpos((string) $bootstrap, 'WPD_User_Fonts::register()'), 'La bibliothèque de polices utilisateur doit être enregistrée.');
$assert(false !== strpos((string) $bootstrap, 'class-wpd-bundled-fonts.php'), 'La bibliothèque de polices incluses doit être chargée.');
$assert(false !== strpos((string) $bootstrap, 'WPD_Bundled_Fonts::register()'), 'La bibliothèque de polices incluses doit être enregistrée.');
$assert(false !== strpos((string) $library, "current_user_can( 'manage_options' )"), 'Import et suppression doivent vérifier la capacité administrateur.');
$assert(false !== strpos((string) $library, "check_admin_referer( 'wpd_upload_user_font' )"), 'L’import doit vérifier un nonce.');
$assert(false !== strpos((string) $library, "check_admin_referer( 'wpd_delete_user_font' )"), 'La suppression doit vérifier un nonce.');
$assert(false !== strpos((string) $library, 'is_uploaded_file( $tmp_name )'), 'Le fichier temporaire doit provenir d’un upload HTTP valide.');
$assert(false !== strpos((string) $library, "'wOF2'") && false !== strpos((string) $library, "'wOFF'"), 'Les signatures WOFF2/WOFF doivent être vérifiées.');
$assert(false !== strpos((string) $library, 'finfo_open'), 'Le MIME doit être vérifié quand Fileinfo est disponible.');
$assert(false !== strpos((string) $library, 'wp_handle_upload('), 'Le déplacement doit passer par l’API WordPress.');
$assert(false !== strpos((string) $library, "UPLOAD_SUBDIR = '/piwigo-display-fonts'"), 'Les polices utilisateur doivent rester dans un répertoire uploads dédié.');
$assert(false !== strpos((string) $library, 'piwigo_display_user_font_max_bytes'), 'La taille maximale doit être configurable par filtre préfixé.');
$assert(false !== strpos((string) $library, 'wp_delete_file('), 'La suppression doit passer par l’API WordPress.');
$assert(false !== strpos((string) $library, 'update_option( self::OPTION_NAME, $library, false )'), 'Les métadonnées ne doivent pas être autoloadées.');
$assert(false !== strpos((string) $photo, 'WPD_User_Fonts::font_stack'), 'Le rendu Texte-photo doit conserver la résolution des polices utilisateur.');

$assert(false !== strpos((string) $bundled, "'bebas-neue'"), 'Bebas Neue doit être proposé dans la sélection embarquée.');
$assert(false !== strpos((string) $bundled, "'bungee'"), 'Bungee doit être proposé dans la sélection embarquée.');
$assert(false !== strpos((string) $bundled, "'bundled-' . $id"), 'Les polices incluses doivent disposer d’identifiants dédiés.');
$assert(false !== strpos((string) $bundled, "add_filter( 'wp_piwigo_display_render'"), 'Le rendu Texte-photo doit accepter les polices incluses.');
$assert(false !== strpos((string) $bundled, "str_replace( 'font-family:inherit;'"), 'La police incluse doit remplacer uniquement le fallback typographique du rendu Texte-photo.');
$assert(false !== strpos((string) $bundled, 'WPD_PLUGIN_URL . $font[\'path\']'), 'Les polices incluses doivent être servies localement depuis le plugin.');
$assert(false === stripos((string) $bundled, 'fonts.googleapis.com') && false === stripos((string) $bundled, 'use.typekit.net'), 'Aucune police distante ne doit être chargée.');

$allowedFonts = array(
	'assets/fonts/bebas-neue/BebasNeue-Regular.woff2',
	'assets/fonts/bungee/Bungee-Regular.woff2',
);
$foundFonts = array();
$iterator   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
	if (!$file instanceof SplFileInfo || !$file->isFile()) {
		continue;
	}
	$extension = strtolower($file->getExtension());
	if (!in_array($extension, array('woff', 'woff2'), true)) {
		continue;
	}
	$relative     = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
	$foundFonts[] = $relative;
}
sort($allowedFonts);
sort($foundFonts);
$assert($allowedFonts === $foundFonts, 'Seules les deux polices libres approuvées doivent être redistribuées avec le plugin.');

foreach (array('bebas-neue', 'bungee') as $fontDir) {
	$license = $root . '/assets/fonts/' . $fontDir . '/OFL.txt';
	$assert(is_file($license), 'Chaque police incluse doit conserver sa licence OFL.');
	$licenseContents = file_get_contents($license);
	$assert(false !== stripos((string) $licenseContents, 'SIL OPEN FONT LICENSE Version 1.1'), 'La licence OFL 1.1 doit accompagner chaque police incluse.');
}

$assert(false !== strpos((string) $gutenberg, 'window.WPDUserFonts'), 'Gutenberg doit proposer le catalogue local de polices.');
$assert(false !== strpos((string) $ui, '[data-wpd="photo_text_font"]') && false !== strpos((string) $ui, '#wpd-c-photo-text-font'), 'Classic Editor et le composeur doivent proposer les polices locales.');
$assert(false !== strpos((string) $ui, "'bundled', 'Polices incluses'"), 'Classic Editor et le composeur doivent distinguer les polices incluses.');
$assert(false !== strpos((string) $ui, "'user', 'Polices locales'"), 'Classic Editor et le composeur doivent conserver les polices importées par l’utilisateur.');
$assert(false !== strpos((string) $css, '.wpd-user-font-preview'), 'La bibliothèque doit conserver un aperçu visuel.');

fwrite(STDOUT, "Local and bundled font pipeline: OK\n");
