<?php
/**
 * Plugin Name: WP Piwigo Display
 * Description: Affiche simplement des albums Piwigo dans WordPress à l'aide d'un shortcode.
 * Version: 2.0.0
 * Author: Franck Bellardie
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-piwigo-display
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WPD_VERSION')) {
    define('WPD_VERSION', '2.0.0');
}

if (!defined('WPD_PLUGIN_FILE')) {
    define('WPD_PLUGIN_FILE', __FILE__);
}

if (!defined('WPD_PLUGIN_DIR')) {
    define('WPD_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('WPD_PLUGIN_URL')) {
    define('WPD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

foreach (['WPD_Plugin', 'WPD_Settings', 'WPD_Service_Account', 'WPD_Service_Api', 'WPD_Api', 'WPD_Cache', 'WPD_Diagnostic', 'WPD_Renderer', 'WPD_Shortcode', 'WPD_Block', 'WPD_Classic_Editor', 'WPD_Slider_Transitions', 'WPD_Masonry', 'WPD_Composer_Parity'] as $wpd_class) {
    if (class_exists($wpd_class, false)) {
        return;
    }
}
unset($wpd_class);

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
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-slider-transitions.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-masonry.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-composer-parity.php';

add_action('plugins_loaded', static function () {
    WPD_Plugin::init();
    WPD_Service_Account::register();
    WPD_Classic_Editor::register();
    WPD_Slider_Transitions::register();
    WPD_Masonry::register();
    WPD_Composer_Parity::register();
});
