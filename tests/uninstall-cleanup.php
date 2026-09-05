<?php

$root = dirname(__DIR__);
$uninstall = $root . '/uninstall.php';

if (!is_file($uninstall)) {
    fwrite(STDERR, "uninstall.php missing\n");
    exit(1);
}

$source = file_get_contents($uninstall);
if ($source === false) {
    fwrite(STDERR, "Unable to read uninstall.php\n");
    exit(1);
}

$required = [
    "defined('WP_UNINSTALL_PLUGIN')",
    "delete_option('wp_piwigo_display_options')",
    "delete_option('wp_piwigo_display_service_account')",
    "_transient_wpd_album_",
    "_transient_timeout_wpd_album_",
    'delete_transient($transient)',
];

foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "Missing uninstall contract: {$needle}\n");
        exit(1);
    }
}

echo "Uninstall cleanup contract OK\n";
