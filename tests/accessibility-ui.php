<?php
/**
 * Accessibility regression checks for interactive administration components.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = array(
    'assets/js/wp-piwigo-display-album-picker.js' => array(
        "'aria-controls'",
        "'aria-expanded'",
        "'aria-live'",
        "role: 'region'",
        "role=\"tree\"",
        "role=\"treeitem\"",
        "event.key === 'ArrowDown'",
        "event.key === 'ArrowUp'",
        "event.key === 'Home'",
        "event.key === 'End'",
        "event.key === 'Escape'",
        'function closePicker(',
        'closePicker(true)',
        "class=\"screen-reader-text",
        "trigger('focus')",
    ),
    'assets/js/wp-piwigo-display-slider.js' => array(
        "matchMedia('(prefers-reduced-motion: reduce)')",
        'var autoplay = !reducedMotion',
        "var speed = reducedMotion || transition === 'none' ? 0",
    ),
    'assets/css/wp-piwigo-display-album-picker.css' => array(
        ':focus-visible',
        '@media(forced-colors:active)',
    ),
);

$failures = array();

foreach ($checks as $relativePath => $needles) {
    $path = $root . '/' . $relativePath;
    $contents = file_get_contents($path);

    if (false === $contents) {
        $failures[] = sprintf('Unreadable file: %s', $relativePath);
        continue;
    }

    foreach ($needles as $needle) {
        if (false === strpos($contents, $needle)) {
            $failures[] = sprintf('Missing accessibility invariant in %s: %s', $relativePath, $needle);
        }
    }
}

if (array() !== $failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Accessibility UI checks passed.\n";
