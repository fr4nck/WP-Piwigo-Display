<?php
/**
 * Plugin Name: Piwigo Display
 * Description: Affiche des albums Piwigo sans importer les images dans la médiathèque.
 * Version: 3.0.0-rc.1
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Franck Bellardie
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-piwigo-display
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPD_VERSION' ) ) {
	define( 'WPD_VERSION', '3.0.0-rc.1' );
}

if ( ! defined( 'WPD_PLUGIN_FILE' ) ) {
	define( 'WPD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WPD_PLUGIN_DIR' ) ) {
	define( 'WPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPD_PLUGIN_URL' ) ) {
	define( 'WPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

foreach ( array( 'WPD_Plugin', 'WPD_Settings', 'WPD_Service_Account', 'WPD_Service_Api', 'WPD_Api', 'WPD_Cache', 'WPD_Diagnostic', 'WPD_Renderer', 'WPD_Shortcode', 'WPD_Block', 'WPD_Classic_Editor' ) as $wpd_class ) {
	if ( class_exists( $wpd_class, false ) ) {
		return;
	}
}
unset( $wpd_class );

require_once WPD_PLUGIN_DIR . 'includes/class-wpd-plugin.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-settings.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-service-account.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-service-api.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-api.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-cache.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-diagnostic.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-renderer.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-shortcode.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-block.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-classic-editor.php';

add_action(
	'plugins_loaded',
	static function () {
		WPD_Plugin::init();
		WPD_Service_Account::register();
		WPD_Classic_Editor::register();
	}
);
