<?php

define('ABSPATH', __DIR__ . '/../');
define('WPD_PLUGIN_DIR', dirname(__DIR__) . '/');

function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function register_block_type($path, array $settings): void { $GLOBALS['wpd_registered_block'] = [$path, $settings]; }

require_once __DIR__ . '/../includes/class-wpd-block.php';

function wpd_block_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

WPD_Block::register();
wpd_block_assert_same(dirname(__DIR__) . '/blocks/piwigo', $GLOBALS['wpd_registered_block'][0], 'Le bloc doit être enregistré depuis block.json.');
wpd_block_assert_same([WPD_Block::class, 'render'], $GLOBALS['wpd_registered_block'][1]['render_callback'], 'Le bloc doit utiliser son rendu serveur.');

$atts = WPD_Block::attributes_to_shortcode([
    'albumId' => '154', 'displayType' => 'slider', 'recursive' => true, 'depth' => 2,
    'orientations' => ['portrait', 'paysage', 'carré'], 'lightbox' => false,
    'autoplay' => true, 'tagMode' => 'all', 'limit' => -4, 'unknown' => 'ignored',
]);
wpd_block_assert_same('154', $atts['album'], 'L’album doit être transmis au shortcode.');
wpd_block_assert_same('slider', $atts['type'], 'Le diaporama doit être transmis au shortcode.');
wpd_block_assert_same('true', $atts['recursive'], 'Les booléens doivent être convertis au format shortcode.');
wpd_block_assert_same('false', $atts['lightbox'], 'Les booléens faux doivent être convertis au format shortcode.');
wpd_block_assert_same('portrait,paysage,carré', $atts['orientation'], 'Les orientations françaises multiples doivent être conservées.');
wpd_block_assert_same('all', $atts['tag_mode'], 'Le mode de tags doit être transmis.');
wpd_block_assert_same(false, isset($atts['unknown']), 'Les attributs inconnus ne doivent pas être transmis.');
