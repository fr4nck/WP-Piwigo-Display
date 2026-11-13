<?php
/**
 * Activate the real Bootstrap Darkroom theme in a disposable Piwigo only.
 * The browser test must use an actual guest-selected theme, not merely
 * files copied into the theme directory.
 *
 * @package WP_Piwigo_Display
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
require_once __DIR__ . '/admin/include/themes.class.php';

$theme = 'bootstrap_darkroom';
$themes = new themes();
if (!isset($themes->fs_themes[$theme])) {
    fwrite(STDERR, "Bootstrap Darkroom not found in Piwigo filesystem\n");
    exit(3);
}
$errors = $themes->perform_action('activate', $theme);
if (!empty($errors)) {
    fwrite(STDERR, "Bootstrap Darkroom activation failed: ".implode('; ', $errors)."\n");
    exit(4);
}
$errors = $themes->perform_action('set_default', $theme);
if (!empty($errors)) {
    fwrite(STDERR, "Bootstrap Darkroom default selection failed\n");
    exit(5);
}
$guest = (int) $conf['guest_id'];
$result = pwg_query(
    'SELECT theme FROM '.USER_INFOS_TABLE.' WHERE user_id='.$guest
);
$row = pwg_db_fetch_assoc($result);
if (!$row || $row['theme'] !== $theme) {
    fwrite(STDERR, "Guest theme is not Bootstrap Darkroom\n");
    exit(6);
}
echo "PIWIGO_GUEST_BOOTSTRAP_DARKROOM=PASS\n";
