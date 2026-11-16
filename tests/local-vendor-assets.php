<?php

$root = dirname(__DIR__);
$plugin = file_get_contents($root . '/includes/class-wpd-plugin.php');

if ($plugin === false) {
    fwrite(STDERR, "Unable to read plugin asset registration\n");
    exit(1);
}

foreach (['cdn.jsdelivr.net', 'unpkg.com', 'cdnjs.cloudflare.com'] as $remote_host) {
    if (strpos($plugin, $remote_host) !== false) {
        fwrite(STDERR, "Remote frontend dependency detected: {$remote_host}\n");
        exit(1);
    }
}

$required = [
    'assets/vendor/splide/splide.min.js',
    'assets/vendor/splide/splide.min.css',
    'assets/vendor/splide/LICENSE',
    'assets/vendor/splide/README.md',
];

foreach ($required as $relative_path) {
    if (!is_file($root . '/' . $relative_path)) {
        fwrite(STDERR, "Missing vendored asset: {$relative_path}\n");
        exit(1);
    }
}

$license = file_get_contents($root . '/assets/vendor/splide/LICENSE');
if ($license === false || strpos($license, 'The MIT License') === false || strpos($license, 'Naotoshi Fujita') === false) {
    fwrite(STDERR, "Splide license/provenance check failed\n");
    exit(1);
}

echo "Local Splide vendor contract OK\n";
