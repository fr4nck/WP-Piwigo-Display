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
require_once __DIR__ . '/../includes/class-wpd-renderer.php';

function wpd_test_image(int $id, int $width, int $height): array
{
    return [
        'id' => $id,
        'width' => $width,
        'height' => $height,
        'name' => 'Image ' . $id,
    ];
}

function wpd_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$filter = new ReflectionMethod(WPD_Shortcode::class, 'filter_images_by_orientation');
$filter->setAccessible(true);

$prepare = new ReflectionMethod(WPD_Renderer::class, 'prepare_images');
$prepare->setAccessible(true);

$images = [];
for ($i = 1; $i <= 8; $i++) {
    $images[] = wpd_test_image($i, 1600, 900);
}
for ($i = 9; $i <= 20; $i++) {
    $images[] = wpd_test_image($i, 900, 1600);
}

$portraits = $filter->invoke(null, $images, 'portrait');
$limited_portraits = $prepare->invoke(null, $portraits, [
    'sort' => 'manual',
    'order' => 'asc',
    'limit' => '0',
    'latest' => '0',
    'random' => '0',
    'max' => '12',
]);

wpd_assert_same(12, count($limited_portraits), 'max="12" doit conserver 12 portraits après filtrage sur un album de 20 images mixtes.');
wpd_assert_same(range(9, 20), array_column($limited_portraits, 'id'), 'Les 12 portraits doivent être conservés dans l’ordre manuel avant application de max.');

$images_with_late_portraits = [];
for ($i = 1; $i <= 12; $i++) {
    $images_with_late_portraits[] = wpd_test_image($i, 1600, 900);
}
for ($i = 13; $i <= 24; $i++) {
    $images_with_late_portraits[] = wpd_test_image($i, 900, 1600);
}

$late_portraits = $filter->invoke(null, $images_with_late_portraits, 'portrait');
$limited_late_portraits = $prepare->invoke(null, $late_portraits, [
    'sort' => 'manual',
    'order' => 'asc',
    'limit' => '0',
    'latest' => '0',
    'random' => '0',
    'max' => '12',
]);

wpd_assert_same(12, count($limited_late_portraits), 'max="12" doit retourner 12 portraits même si les 12 premières images ne sont pas des portraits.');
wpd_assert_same(range(13, 24), array_column($limited_late_portraits, 'id'), 'La limite ne doit pas être appliquée avant le filtre portrait.');

$all_images = $filter->invoke(null, $images, 'all');
$limited_all_images = $prepare->invoke(null, $all_images, [
    'sort' => 'manual',
    'order' => 'asc',
    'limit' => '0',
    'latest' => '0',
    'random' => '0',
    'max' => '12',
]);

wpd_assert_same(range(1, 12), array_column($limited_all_images, 'id'), 'orientation="all" doit conserver le comportement historique de max sur les premières images.');
