<?php
/**
 * Plugin Name: WP Piwigo Display
 * Description: Affiche simplement des albums Piwigo dans WordPress à l'aide d'un shortcode.
 * Version: 1.9.0
 * Author: Franck Bellardie
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-piwigo-display
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPD_VERSION', '1.9.0');
define('WPD_PLUGIN_FILE', __FILE__);
define('WPD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPD_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPD_PLUGIN_DIR . 'includes/class-wpd-plugin.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-settings.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-api.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-cache.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-diagnostic.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-renderer.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-shortcode.php';

add_action('plugins_loaded', static function () {
    WPD_Plugin::init();
});
