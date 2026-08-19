<?php
/**
 * Plugin Name: Piwigo Display
 * Description: Affiche des albums Piwigo dans WordPress sans importer les images dans la médiathèque.
 * Version: 3.1.0-dev
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
	define( 'WPD_VERSION', '3.1.0-dev' );
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

$wp_piwigo_display_loaded_classes = array(
	'WPD_Plugin',
	'WPD_Settings',
	'WPD_Service_Account',
	'WPD_Service_Api',
	'WPD_Api_Metrics',
	'WPD_Api',
	'WPD_Cache',
	'WPD_Diagnostic',
	'WPD_Renderer',
	'WPD_Shortcode',
	'WPD_Block',
	'WPD_Classic_Editor',
	'WPD_Slider_Transitions',
	'WPD_Masonry',
	'WPD_Justified',
	'WPD_Composer_Parity',
	'WPD_Gutenberg_Parity',
	'WPD_Shapes',
	'WPD_Piwigo_Response_Compat',
);

foreach ( $wp_piwigo_display_loaded_classes as $wp_piwigo_display_class ) {
	if ( class_exists( $wp_piwigo_display_class, false ) ) {
		return;
	}
}

unset( $wp_piwigo_display_class, $wp_piwigo_display_loaded_classes );

require_once WPD_PLUGIN_DIR . 'includes/class-wpd-plugin.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-settings.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-service-account.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-service-api.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-api-metrics.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-api.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-cache.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-diagnostic.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-renderer.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-shortcode.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-block.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-classic-editor.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-slider-transitions.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-masonry.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-justified.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-composer-parity.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-gutenberg-parity.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-shapes.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-piwigo-response-compat.php';

/**
 * Registers the plugin components after all plugins are loaded.
 *
 * @return void
 */
function wp_piwigo_display_bootstrap_plugin(): void {
	WPD_Api_Metrics::register();
	WPD_Plugin::init();
	WPD_Service_Account::register();
	WPD_Classic_Editor::register();
	WPD_Slider_Transitions::register();
	WPD_Masonry::register();
	WPD_Justified::register();
	WPD_Composer_Parity::register();
	WPD_Gutenberg_Parity::register();
	WPD_Shapes::register();
	WPD_Piwigo_Response_Compat::register();
}

add_action( 'plugins_loaded', 'wp_piwigo_display_bootstrap_plugin' );
