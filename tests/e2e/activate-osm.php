<?php
/**
 * CI-only CLI entrypoint inside a disposable Piwigo container.
 * Calls Piwigo's actual plugin manager; never installs into production.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(2);
}
chdir(__DIR__);
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_URI'] = '/admin.php';
$_SERVER['SCRIPT_NAME'] = '/admin.php';
$_SERVER['PHP_SELF'] = '/admin.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define('PHPWG_ROOT_PATH', './');
define('IN_ADMIN', true);
require_once __DIR__ . '/include/common.inc.php';
require_once __DIR__ . '/admin/include/plugins.class.php';
$plugins = new plugins();
$errors = $plugins->perform_action('activate', 'piwigo-openstreetmap');
if (!empty($errors)) {
    fwrite(STDERR, "Piwigo OSM plugin activation returned errors\n");
    exit(3);
}
$installed = get_db_plugins(null, 'piwigo-openstreetmap');
if (!isset($installed[0]['state']) || $installed[0]['state'] !== 'active') {
    fwrite(STDERR, "Piwigo OSM plugin is not active\n");
    exit(4);
}
echo "OSM_NATIVE_ACTIVATION=PASS\n";
