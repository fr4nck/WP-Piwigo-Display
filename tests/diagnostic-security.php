<?php

$source = file_get_contents(__DIR__ . '/../includes/class-wpd-diagnostic.php');
if ($source === false) {
    fwrite(STDERR, "Unable to read diagnostic source\n");
    exit(1);
}

$required = [
    'wp_safe_remote_get($endpoint',
    "Configurée en HTTPS (hôte masqué)",
    "Configurée en HTTP (hôte masqué)",
];

foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "Missing diagnostic security contract: {$needle}\n");
        exit(1);
    }
}

if (strpos($source, 'wp_remote_get($endpoint') !== false) {
    fwrite(STDERR, "Unsafe diagnostic HTTP transport detected\n");
    exit(1);
}

$method_start = strpos($source, 'private static function safe_api_url');
$method_end = strpos($source, 'private static function ssl_status', $method_start ?: 0);
$method = ($method_start !== false && $method_end !== false)
    ? substr($source, $method_start, $method_end - $method_start)
    : '';

foreach (['PHP_URL_HOST', 'PHP_URL_PORT', 'PHP_URL_PATH', '/ws.php?format=json'] as $forbidden) {
    if ($method === '' || strpos($method, $forbidden) !== false) {
        fwrite(STDERR, "Diagnostic URL disclosure contract failed: {$forbidden}\n");
        exit(1);
    }
}

echo "Diagnostic security contract OK\n";
