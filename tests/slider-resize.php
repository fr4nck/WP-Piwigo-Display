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
