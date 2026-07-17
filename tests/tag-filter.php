<?php

define('ABSPATH', __DIR__ . '/../');

if (!function_exists('absint')) {
    function absint($maybeint): int
    {
        return abs((int) $maybeint);
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text): string
    {
        return strip_tags((string) $text);
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

function wpd_tag_test_image(int $id, array $tags = null, int $width = 1600, int $height = 900): array
{
    $image = [
        'id' => $id,
        'width' => $width,
        'height' => $height,
        'name' => 'Image ' . $id,
    ];

    if ($tags !== null) {
        $image['tags'] = $tags;
    }

    return $image;
}

function wpd_tag_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$normalize = new ReflectionMethod(WPD_Shortcode::class, 'normalize_tag_filter');
$normalize->setAccessible(true);

$filter_tags = new ReflectionMethod(WPD_Shortcode::class, 'filter_images_by_tags');
$filter_tags->setAccessible(true);

$filter_orientation = new ReflectionMethod(WPD_Shortcode::class, 'filter_images_by_orientation');
$filter_orientation->setAccessible(true);

$prepare = new ReflectionMethod(WPD_Renderer::class, 'prepare_images');
$prepare->setAccessible(true);

$images = [
    wpd_tag_test_image(1, [['name' => 'Nature'], ['name' => 'Animaux']]),
    wpd_tag_test_image(2, [['name' => 'nature']]),
    wpd_tag_test_image(3, [['name' => 'Architecture']]),
    wpd_tag_test_image(4),
    wpd_tag_test_image(5, [['name' => 'ANIMAUX']]),
];

wpd_tag_assert_same(['nature'], $normalize->invoke(null, ' Nature ', ''), 'Un tag unique doit être normalisé.');
wpd_tag_assert_same(['nature', 'animaux'], $normalize->invoke(null, 'nature', ' animaux, Nature , animaux '), 'tag et tags doivent être fusionnés, nettoyés et dédupliqués.');

wpd_tag_assert_same([1, 2], array_column($filter_tags->invoke(null, $images, ['nature'], 'any'), 'id'), 'Le filtre sur un tag unique doit être insensible à la casse.');
wpd_tag_assert_same([1, 2, 5], array_column($filter_tags->invoke(null, $images, ['nature', 'animaux'], 'any'), 'id'), 'tag_mode="any" doit conserver les images avec au moins un tag demandé.');
wpd_tag_assert_same([1], array_column($filter_tags->invoke(null, $images, ['nature', 'animaux'], 'all'), 'id'), 'tag_mode="all" doit conserver uniquement les images avec tous les tags demandés.');
wpd_tag_assert_same([1, 2, 3, 4, 5], array_column($filter_tags->invoke(null, $images, [], 'any'), 'id'), 'Sans filtre tag, le comportement historique doit conserver les images sans tags.');
wpd_tag_assert_same([1, 2, 5], array_column($filter_tags->invoke(null, $images, $normalize->invoke(null, '', ' Nature, animaux, nature '), 'any'), 'id'), 'Les doublons et espaces dans tags ne doivent pas changer les résultats.');

$combo_images = [];
for ($i = 1; $i <= 8; $i++) {
    $combo_images[] = wpd_tag_test_image($i, [['name' => 'nature']], 1600, 900);
}
for ($i = 9; $i <= 20; $i++) {
    $combo_images[] = wpd_tag_test_image($i, [['name' => 'nature']], 900, 1600);
}
for ($i = 21; $i <= 24; $i++) {
    $combo_images[] = wpd_tag_test_image($i, [['name' => 'ville']], 900, 1600);
}

$tagged = $filter_tags->invoke(null, $combo_images, ['nature'], 'any');
$portraits = $filter_orientation->invoke(null, $tagged, 'portrait');
$limited = $prepare->invoke(null, $portraits, [
    'sort' => 'manual',
    'order' => 'asc',
    'limit' => '0',
    'latest' => '0',
    'random' => '0',
    'max' => '12',
]);

wpd_tag_assert_same(range(9, 20), array_column($limited, 'id'), 'tags + orientation + max="12" doit retourner jusqu’à 12 images correspondant vraiment aux filtres.');
