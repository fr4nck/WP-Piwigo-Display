<?php

define('ABSPATH', __DIR__ . '/../');

function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function absint($value): int { return abs((int) $value); }
function esc_url_raw($value): string { return filter_var((string) $value, FILTER_SANITIZE_URL); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function untrailingslashit($value): string { return rtrim((string) $value, '/\\'); }

require_once __DIR__ . '/../includes/class-wpd-shortcode.php';

function wpd_resize_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$method = new ReflectionMethod(WPD_Shortcode::class, 'sanitize_atts');
$method->setAccessible(true);

$sanitize = static function (array $attributes) use ($method): array {
    return $method->invoke(null, $attributes);
};

wpd_resize_assert_same('20%', $sanitize(['width' => '12%'])['width'], 'La largeur publique doit être bornée à 20 %.');
wpd_resize_assert_same('87%', $sanitize(['width' => '87%'])['width'], 'Une largeur comprise dans les bornes doit être conservée.');
wpd_resize_assert_same('100%', $sanitize(['width' => '140%'])['width'], 'La largeur publique doit être plafonnée à 100 %.');
wpd_resize_assert_same('100%', $sanitize(['width' => 'calc(100%)'])['width'], 'Une largeur CSS arbitraire doit être refusée.');
wpd_resize_assert_same('480px', $sanitize(['height' => '480px'])['height'], 'La hauteur Gutenberg en pixels doit être conservée.');
wpd_resize_assert_same('50vh', $sanitize(['height' => '50vh'])['height'], 'Les anciennes unités de hauteur doivent rester compatibles.');
wpd_resize_assert_same('', $sanitize(['height' => 'calc(100vh)'])['height'], 'Une hauteur CSS arbitraire doit être refusée.');

$editor = file_get_contents(__DIR__ . '/../blocks/piwigo/index.js');
wpd_resize_assert_same(true, strpos($editor, "role:'slider'") !== false, 'Les poignées doivent exposer un rôle accessible.');
wpd_resize_assert_same(true, strpos($editor, "event.key === 'Home'") !== false, 'Les poignées doivent gérer le clavier.');
wpd_resize_assert_same(true, strpos($editor, 'pointermove') !== false, 'Les poignées doivent gérer le glissement.');
wpd_resize_assert_same(true, strpos($editor, 'legacyHeight') !== false, 'Les unités historiques doivent être distinguées des hauteurs Gutenberg en pixels.');
wpd_resize_assert_same(true, strpos($editor, "querySelector('.splide__track") !== false, 'La hauteur doit être mesurée sur la piste du diaporama.');
wpd_resize_assert_same(true, strpos($editor, 'wpd-block-slider-edit') !== false, 'Le diaporama Gutenberg doit proposer un bouton Modifier.');
wpd_resize_assert_same(true, strpos($editor, "openGeneralSidebar('edit-post/block')") !== false, 'Le bouton Modifier doit ouvrir les réglages natifs du bloc.');

$tinymce = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-tinymce.js');
wpd_resize_assert_same(true, strpos($tinymce, 'WPD_SHORTCODE_') !== false, 'TinyMCE doit restaurer le shortcode après la sérialisation HTML.');
wpd_resize_assert_same(true, strpos($tinymce, 'encodeURIComponent') !== false, 'TinyMCE doit protéger les guillemets du shortcode dans son attribut HTML.');
wpd_resize_assert_same(true, strpos($tinymce, 'decodeURIComponent') !== false, 'TinyMCE doit restituer le shortcode avant modification et sauvegarde.');
wpd_resize_assert_same(true, strpos($tinymce, "editor.on('dblclick'") !== false, 'L’aperçu TinyMCE doit être modifiable par double-clic.');
wpd_resize_assert_same(true, strpos($tinymce, 'wpd:edit-shortcode') !== false, 'TinyMCE doit transmettre le shortcode au composeur.');

$classic = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js');
wpd_resize_assert_same(true, strpos($classic, "on('wpd:edit-shortcode'") !== false, 'Le composeur classique doit rouvrir le shortcode sélectionné.');
wpd_resize_assert_same(true, strpos($classic, 'editingLegacyHeight') !== false, 'Le composeur classique doit préserver une hauteur historique inchangée.');

$asset = file_get_contents(__DIR__ . '/../blocks/piwigo/index.asset.php');
wpd_resize_assert_same(true, strpos($asset, "'wp-data'") !== false, 'Le bouton Modifier doit déclarer sa dépendance au registre de données WordPress.');
