<?php
/**
 * Regression checks for Piwigo API JSON response parsing.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$path = dirname(__DIR__) . '/includes/class-wpd-api.php';
$contents = file_get_contents($path);

if (false === $contents) {
    fwrite(STDERR, "Unable to read API client.\n");
    exit(1);
}

$required = array(
    'private function decode_json_response(',
    'json_decode(',
    'json_last_error()',
    'wpd_invalid_json',
);

$failures = array();
foreach ($required as $needle) {
    if (false === strpos($contents, $needle)) {
        $failures[] = sprintf('Missing JSON resilience invariant: %s', $needle);
    }
}

if (array() !== $failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Piwigo API JSON resilience checks passed.\n";
