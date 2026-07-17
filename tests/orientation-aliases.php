<?php

define('ABSPATH', __DIR__ . '/../');

if (!function_exists('absint')) {
    function absint($maybeint): int
    {
        return abs((int) $maybeint);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text): string
    {
        return trim(strip_tags((string) $text));
    }
}

require_once __DIR__ . '/../includes/class-wpd-shortcode.php';

function wpd_orientation_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$sanitize = new ReflectionMethod(WPD_Shortcode::class, 'sanitize_orientation');
$sanitize->setAccessible(true);
$filter = new ReflectionMethod(WPD_Shortcode::class, 'filter_images_by_orientation');
$filter->setAccessible(true);

wpd_orientation_assert_same('portrait', $sanitize->invoke(null, 'portrait'), 'portrait doit rester normalisé en portrait.');
wpd_orientation_assert_same('landscape', $sanitize->invoke(null, 'paysage'), 'paysage doit être normalisé en landscape.');
wpd_orientation_assert_same('landscape', $sanitize->invoke(null, 'landscape'), 'landscape doit rester accepté.');
wpd_orientation_assert_same('square', $sanitize->invoke(null, 'carré'), 'carré doit être normalisé en square.');
wpd_orientation_assert_same('square', $sanitize->invoke(null, 'carre'), 'carre doit rester accepté.');
wpd_orientation_assert_same('square', $sanitize->invoke(null, 'square'), 'square doit rester accepté.');
wpd_orientation_assert_same('all', $sanitize->invoke(null, 'all'), 'all doit rester accepté.');
wpd_orientation_assert_same('portrait,square', $sanitize->invoke(null, 'portrait,carré'), 'Les orientations françaises doivent accepter les listes.');
wpd_orientation_assert_same('landscape,square', $sanitize->invoke(null, 'paysage,carre'), 'Les alias français doivent accepter les listes.');
wpd_orientation_assert_same('landscape,square', $sanitize->invoke(null, 'landscape,square'), 'Les alias historiques doivent accepter les listes.');

$images = [
    ['id' => 1, 'width' => 900, 'height' => 1600],
    ['id' => 2, 'width' => 1600, 'height' => 900],
    ['id' => 3, 'width' => 1000, 'height' => 1000],
];
wpd_orientation_assert_same([1, 3], array_column($filter->invoke(null, $images, 'portrait,carré'), 'id'), 'La liste portrait,carré doit filtrer les deux orientations.');
wpd_orientation_assert_same([2, 3], array_column($filter->invoke(null, $images, 'paysage,carre'), 'id'), 'La liste paysage,carre doit filtrer les deux orientations.');
wpd_orientation_assert_same([2, 3], array_column($filter->invoke(null, $images, 'landscape,square'), 'id'), 'La liste historique doit conserver son fonctionnement.');
